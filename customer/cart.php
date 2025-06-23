<?php
require_once '../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotCustomer();

require_once '../config/database.php';

// Initialize variables
$success_message = '';
$cart_items = [];

// Get cart items
$stmt = $pdo->prepare("
    SELECT 
        ci.cart_item_id,
        ci.product_id,
        ci.quantity,
        ci.size,
        p.name as product_name,
        p.price,
        p.image_path,
        p.stock_quantity,
        p.category,
        p.size_data
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.product_id
    JOIN cart c ON ci.cart_id = c.cart_id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

// Calculate total
$total = array_reduce($cart_items, function($sum, $item) {
    return $sum + ($item['price'] * $item['quantity']);
}, 0);

// Handle checkout process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Get cart items
        $stmt = $pdo->prepare("
            SELECT 
                ci.cart_item_id,
                ci.product_id,
                ci.quantity,
                ci.size,
                p.price,
                p.stock_quantity,
                p.owner_id,
                p.name as product_name,
                p.category,
                p.size_data
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.product_id
            JOIN cart c ON ci.cart_id = c.cart_id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_items = $stmt->fetchAll();

        // Debug cart items
        error_log("Cart items before order creation: " . print_r($cart_items, true));

        if (empty($cart_items)) {
            throw new Exception('Your cart is empty');
        }

        // Create order
        $stmt = $pdo->prepare("
            INSERT INTO orders (customer_id, total_amount, status, order_date)
            VALUES (?, ?, 'pending', NOW())
        ");
        $total_amount = array_reduce($cart_items, function($sum, $item) {
            return $sum + ($item['price'] * $item['quantity']);
        }, 0);
        $stmt->execute([$_SESSION['user_id'], $total_amount]);
        $order_id = $pdo->lastInsertId();

        // Create order items
        $stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price, size)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($cart_items as $item) {
            // Debug each item being added to order
            error_log("Adding item to order: " . print_r($item, true));

            // Check stock
            if ($item['quantity'] > $item['stock_quantity']) {
                throw new Exception("Not enough stock for {$item['product_name']}");
            }

            // For clothing items, ensure size is set
            $size = null;
            if ($item['category'] === 'clothes') {
                $size = $item['size'];
                if (empty($size)) {
                    throw new Exception("Size is required for clothing item: {$item['product_name']}");
                }
            }

            // Add order item
            $stmt->execute([
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price'],
                $size
            ]);

            // Update product stock
            $update_stock = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - ? 
                WHERE product_id = ?
            ");
            $update_stock->execute([$item['quantity'], $item['product_id']]);
        }

        // Clear cart
        $stmt = $pdo->prepare("
            DELETE ci FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.cart_id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);

        // Commit transaction
        $pdo->commit();

        // Set success message
        $success_message = 'Order placed successfully! Your order #' . $order_id . ' is being processed.';
        
        // Clear cart items array after successful checkout
        $cart_items = [];

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $success_message = $e->getMessage();
    }
} else {
    // Get cart items only if not processing checkout
    $stmt = $pdo->prepare("
        SELECT 
            ci.cart_item_id,
            ci.product_id,
            ci.quantity,
            ci.size,
            p.name as product_name,
            p.price,
            p.image_path,
            p.stock_quantity,
            p.category,
            p.size_data
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        JOIN cart c ON ci.cart_id = c.cart_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll();
}

// Calculate total
$total = array_reduce($cart_items, function($sum, $item) {
    return $sum + ($item['price'] * $item['quantity']);
}, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - UniMarket</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="/unimarket/assets/js/notifications.js"></script>
    <style>
        /* Cart item styles */
        .cart-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .cart-items {
            flex: 1;
        }

        .cart-item {
            display: flex;
            gap: 20px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .item-image {
            width: 120px;
            height: 120px;
            flex-shrink: 0;
            background: #f8f9fa;
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-image .no-image {
            color: #ccc;
            font-size: 2em;
        }

        .item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .item-details h3 {
            margin: 0;
            font-size: 1.1em;
        }

        .item-price {
            font-weight: 500;
            color: var(--primary-color);
        }

        .item-size {
            color: #666;
            font-size: 0.9em;
            margin: 5px 0;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-controls button {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .remove-item {
            color: #dc3545;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px 0;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .remove-item:hover {
            color: #c82333;
        }

        .cart-summary {
            width: 300px;
            padding: 20px;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.2em;
            font-weight: 500;
            margin: 20px 0;
        }

        .cart-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .empty-cart {
            text-align: center;
            padding: 50px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .empty-cart i {
            font-size: 3em;
            color: #ccc;
            margin-bottom: 20px;
        }

        /* Purchase confirmation modal styles */
        .purchase-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .purchase-modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .purchase-modal h2 {
            margin-bottom: 20px;
            color: var(--primary-dark);
        }

        .purchase-modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }

        .purchase-modal-buttons button {
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .purchase-modal-buttons .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }

        .purchase-modal-buttons .btn-cancel:hover {
            background: #d0d0d0;
        }

        .purchase-modal-buttons .btn-confirm {
            background: var(--primary-color);
            color: white;
        }

        .purchase-modal-buttons .btn-confirm:hover {
            background: var(--primary-dark);
        }

        .payment-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            text-align: left;
        }

        .payment-info i {
            color: var(--primary-color);
            margin-right: 10px;
        }

        .payment-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .cart-container {
                flex-direction: column;
            }

            .cart-summary {
                width: 100%;
            }

            .cart-item {
                flex-direction: column;
            }

            .item-image {
                width: 100%;
                height: 200px;
            }
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            animation: slideIn 0.5s ease-out;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            background-color: #28a745;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .notification.hide {
            animation: slideOut 0.5s ease-out forwards;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Shopping Cart</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="/unimarket/customer/products/browse.php" class="btn">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item card">
                            <div class="item-image">
                                <?php if ($item['image_path']): ?>
                                    <img src="/unimarket/assets/images/products/<?php echo $item['image_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                <p class="item-price">₱<?php echo number_format($item['price'], 2); ?></p>
                                
                                <?php if ($item['category'] === 'clothes' && isset($item['size'])): ?>
                                    <p class="item-size">Size: <?php echo htmlspecialchars($item['size']); ?></p>
                                <?php endif; ?>
                                
                                <form method="POST" action="../ajax/update_cart.php" class="quantity-form">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                    <div class="quantity-controls">
                                        <button type="button" class="quantity-decrement">-</button>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['stock_quantity']; ?>" 
                                               class="quantity-input">
                                        <button type="button" class="quantity-increment">+</button>
                                    </div>
                                </form>
                                
                                <p class="item-total">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                
                                <form method="POST" action="../ajax/remove_from_cart.php" class="remove-form">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                    <button type="button" class="remove-item">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary card">
                    <h2>Order Summary</h2>
                    <div class="cart-total">
                        <span>Total:</span>
                        <span class="total-amount">₱<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <div class="cart-actions">
                        <a href="/unimarket/customer/products/browse.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                        <form method="POST" id="checkoutForm">
                            <input type="hidden" name="checkout" value="1">
                            <button type="button" onclick="openPurchaseModal()" class="btn btn-primary">
                                <i class="fas fa-shopping-bag"></i> Proceed to Checkout
                            </button>
                        </form>
                    </div>

                    <!-- Purchase confirmation modal HTML -->
                    <div class="purchase-modal" id="purchaseModal">
                        <div class="purchase-modal-content">
                            <h2>Confirm Purchase</h2>
                            <p>Are you sure you want to proceed with this purchase?</p>
                            <div class="payment-info">
                                <i class="fas fa-info-circle"></i>
                                <p>Payment will be collected when you pick up your items. The seller will contact you to schedule the pickup time.</p>
                            </div>
                            <div class="purchase-modal-buttons">
                                <button class="btn-cancel" onclick="closePurchaseModal()">Cancel</button>
                                <button class="btn-confirm" onclick="confirmPurchase()">Confirm Purchase</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Purchase modal functions
        function openPurchaseModal() {
            document.getElementById('purchaseModal').style.display = 'flex';
        }

        function closePurchaseModal() {
            document.getElementById('purchaseModal').style.display = 'none';
        }

        function confirmPurchase() {
            document.getElementById('checkoutForm').submit();
        }

        // Close modal when clicking outside
        document.getElementById('purchaseModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePurchaseModal();
            }
        });

        // Quantity update handling
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const form = this.closest('form');
                const formData = new FormData(form);
                
                fetch('../ajax/update_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update total price
                        const totalElement = document.querySelector('.cart-total .total-amount');
                        if (totalElement) {
                            totalElement.textContent = '₱' + data.total.toFixed(2);
                        }
                        // Update item total
                        const itemTotal = this.closest('.cart-item').querySelector('.item-total');
                        if (itemTotal) {
                            itemTotal.textContent = '₱' + data.item_total.toFixed(2);
                        }
                    } else {
                        alert(data.message || 'Failed to update quantity');
                        // Reset to previous value
                        this.value = this.defaultValue;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating quantity');
                    // Reset to previous value
                    this.value = this.defaultValue;
                });
            });
        });

        // Remove item handling
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove this item from your cart?')) {
                    const form = this.closest('form');
                    const formData = new FormData(form);
                    
                    fetch('../ajax/remove_from_cart.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove item from DOM
                            const cartItem = this.closest('.cart-item');
                            cartItem.remove();
                            
                            // Update total price
                            const totalElement = document.querySelector('.cart-total .total-amount');
                            if (totalElement) {
                                totalElement.textContent = '₱' + data.total.toFixed(2);
                            }
                            
                            // Update cart count in header
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount) {
                                cartCount.textContent = data.cart_count;
                            }
                            
                            // Show success message
                            showToast('Item removed from cart', 'success');
                            
                            // If cart is empty, reload page to show empty cart message
                            if (data.cart_count === 0) {
                                location.reload();
                            }
                        } else {
                            showToast(data.message || 'Failed to remove item', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('An error occurred while removing item', 'error');
                    });
                }
            });
        });

        // Auto-hide notifications after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                setTimeout(() => {
                    notification.classList.add('hide');
                    setTimeout(() => {
                        notification.remove();
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>