<?php
require_once '../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotCustomer();

require_once '../config/database.php';

// Get cart items with product details
$stmt = $pdo->prepare("
    SELECT ci.cart_item_id, p.product_id, p.name, p.price, ci.quantity, p.image_path, p.stock_quantity
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.product_id
    JOIN cart c ON ci.cart_id = c.cart_id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $cart_item_id = (int)$_POST['cart_item_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Validate quantity
    $product = null;
    foreach ($cart_items as $item) {
        if ($item['cart_item_id'] == $cart_item_id) {
            $product = $item;
            break;
        }
    }
    
    if ($product && $quantity > 0 && $quantity <= $product['stock_quantity']) {
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
        $stmt->execute([$quantity, $cart_item_id]);
        header("Location: /unimarket/customer/cart.php");
        exit();
    } else {
        $error = $quantity <= 0 ? "Quantity must be at least 1" : "Only {$product['stock_quantity']} available in stock";
    }
}

// Handle item removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $cart_item_id = (int)$_POST['cart_item_id'];
    
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
    $stmt->execute([$cart_item_id]);
    header("Location: /unimarket/customer/cart.php");
    exit();
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    if (empty($cart_items)) {
        $error = 'Your cart is empty.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_amount) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total]);
            $order_id = $pdo->lastInsertId();
            
            // Add order items and update stock
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE ci FROM cart_items ci JOIN cart c ON ci.cart_id = c.cart_id WHERE c.user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            $pdo->commit();
            
            header("Location: /unimarket/customer/orders/view.php?order_id=$order_id");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'An error occurred during checkout. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - UniMarket</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cart-container {
            margin-top: 30px;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .cart-table th {
            text-align: left;
            padding: 12px;
            background-color: #f5f5f5;
            border-bottom: 1px solid #ddd;
        }
        
        .cart-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .product-cell {
            display: flex;
            align-items: center;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            margin-right: 15px;
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-image img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
        }
        
        .quantity-control input {
            width: 60px;
            text-align: center;
            margin: 0 5px;
            padding: 5px;
        }
        
        .update-btn, .remove-btn {
            padding: 5px 10px;
            margin-left: 5px;
            cursor: pointer;
        }
        
        .cart-summary {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .total-row {
            font-weight: bold;
            font-size: 1.1em;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .checkout-btn {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
        }
        
        .empty-cart {
            text-align: center;
            padding: 50px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        
        .stock-warning {
            color: #e53935;
            font-size: 0.9em;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .cart-table thead {
                display: none;
            }
            
            .cart-table tr {
                display: block;
                margin-bottom: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            
            .cart-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: none;
            }
            
            .cart-table td::before {
                content: attr(data-label);
                font-weight: bold;
                margin-right: 20px;
            }
            
            .product-cell {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .product-image {
                margin-bottom: 10px;
            }
        }

        tbody td tr button{
            background-color: green;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container cart-container">
        <h1>Your Shopping Cart</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart card">
                <i class="fas fa-shopping-cart" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
                <h3>Your cart is empty</h3>
                <p>Browse our products to add items to your cart</p>
                <a href="/unimarket/customer/products/browse.php" class="btn">Continue Shopping</a>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td data-label="Product">
                                <div class="product-cell">
                                    <div class="product-image">
                                        <?php if ($item['image_path']): ?>
                                            <img src="/unimarket/assets/images/products/<?php echo $item['image_path']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-box-open" style="color: #ccc;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <?php if ($item['quantity'] > $item['stock_quantity']): ?>
                                            <p class="stock-warning">Only <?php echo $item['stock_quantity']; ?> available in stock</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Price">₱<?php echo number_format($item['price'], 2); ?></td>
                            <td data-label="Quantity">
                                <form method="POST" class="quantity-form">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                    <div class="quantity-control">
                                        <button style="padding: 2px 5px;" type="button" class="quantity-decrement btn"><i class="fas fa-minus"></i></button>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>">
                                        <button style="padding: 2px 5px;" type="button" class="quantity-increment btn"><i class="fas fa-plus"></i></button>
                                    </div>
                                    <button style="padding: 2px 32px;" type="submit" name="update_quantity" class="update-btn btn">Update</button>
                                </form>
                            </td>
                            <td data-label="Subtotal">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td data-label="Action">
                                <form method="POST">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                    <button type="submit" name="remove_item" class="remove-btn btn">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-summary card">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>₱<?php echo number_format($total, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Estimated Shipping:</span>
                    <span>Free</span>
                </div>
                <div class="summary-row total-row">
                    <span>Total:</span>
                    <span>₱<?php echo number_format($total, 2); ?></span>
                </div>
                
                <form method="POST">
                    <button type="submit" name="checkout" class="checkout-btn btn">
                        <i class="fas fa-beat  fa-shopping-bag"></i> Proceed to Checkout
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Quantity increment/decrement buttons
        document.querySelectorAll('.quantity-increment').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const max = parseInt(input.getAttribute('max'));
                const current = parseInt(input.value);
                if (current < max) {
                    input.value = current + 1;
                } else {
                    alert(`Only ${max} available in stock`);
                }
            });
        });
        
        document.querySelectorAll('.quantity-decrement').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                if (parseInt(input.value) > 1) {
                    input.value = parseInt(input.value) - 1;
                }
            });
        });
        
        // Quantity form submission
        document.querySelectorAll('.quantity-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const input = this.querySelector('input[name="quantity"]');
                const max = parseInt(input.getAttribute('max'));
                const value = parseInt(input.value);
                
                if (value < 1) {
                    e.preventDefault();
                    alert('Quantity must be at least 1');
                    input.focus();
                } else if (value > max) {
                    e.preventDefault();
                    alert(`Only ${max} available in stock`);
                    input.focus();
                }
            });
        });
        
        // Remove item confirmation
        document.querySelectorAll('button[name="remove_item"]').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to remove this item from your cart?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>