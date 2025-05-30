<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotCustomer();

require_once '../../config/database.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header("Location: /unimarket/customer/products/browse.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Get product details
$stmt = $pdo->prepare("SELECT p.*, u.username as seller_name 
                      FROM products p
                      JOIN users u ON p.owner_id = u.user_id
                      WHERE p.product_id = ? AND p.stock_quantity > 0");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: /unimarket/customer/products/browse.php");
    exit();
}

// Handle add to cart
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity < 1) {
        $error_message = 'Quantity must be at least 1.';
    } elseif ($quantity > $product['stock_quantity']) {
        $error_message = "Only {$product['stock_quantity']} available in stock.";
    } else {
        try {
            // Get or create cart for user
            $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $cart = $stmt->fetch();
            
            if (!$cart) {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
                $stmt->execute([$_SESSION['user_id']]);
                $cart_id = $pdo->lastInsertId();
            } else {
                $cart_id = $cart['cart_id'];
            }
            
            // Check if product already in cart
            $stmt = $pdo->prepare("SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?");
            $stmt->execute([$cart_id, $product_id]);
            $existing_item = $stmt->fetch();
            
            if ($existing_item) {
                // Update quantity if already in cart
                $new_quantity = $existing_item['quantity'] + $quantity;
                if ($new_quantity > $product['stock_quantity']) {
                    $error_message = "Cannot add more than available stock.";
                } else {
                    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
                    $stmt->execute([$new_quantity, $existing_item['cart_item_id']]);
                    $success_message = 'Product quantity updated in cart!';
                }
            } else {
                // Add new item to cart
                $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$cart_id, $product_id, $quantity]);
                $success_message = 'Product added to cart!';
            }
        } catch (PDOException $e) {
            $error_message = 'Failed to add product to cart. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - UniMarket</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .product-detail-container {
            margin-top: 30px;
        }
        
        .product-main {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        @media (min-width: 768px) {
            .product-main {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .product-gallery {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .product-image {
            max-width: 100%;
            max-height: 400px;
            margin-bottom: 20px;
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-title {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary-dark);
        }
        
        .product-seller {
            color: #666;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .product-price {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .product-stock {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .stock-high {
            color: #2e7d32;
        }
        
        .stock-low {
            color: #e53935;
        }
        
        .product-description {
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .add-to-cart-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .quantity-input {
            width: 80px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .product-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        
        .meta-item {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
        }
        
        .meta-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container product-detail-container">
        <a href="/unimarket/customer/products/browse.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="product-main card">
            <div class="product-gallery">
                <?php if ($product['image_path']): ?>
                    <img src="/unimarket/assets/images/products/<?php echo $product['image_path']; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="product-image">
                <?php else: ?>
                    <div style="height: 400px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-box-open" style="font-size: 5rem; color: #ccc;"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="product-info">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="product-seller">Sold by <?php echo htmlspecialchars($product['seller_name']); ?></p>
                
                <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                
                <p class="product-stock <?php echo $product['stock_quantity'] < 5 ? 'stock-low' : 'stock-high'; ?>">
                    <?php if ($product['stock_quantity'] < 5): ?>
                        Only <?php echo $product['stock_quantity']; ?> left in stock!
                    <?php else: ?>
                        In stock (<?php echo $product['stock_quantity']; ?> available)
                    <?php endif; ?>
                </p>
                
                <div class="product-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                
                <form method="POST" class="add-to-cart-form">
                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" 
                           class="quantity-input">
                    <button type="submit" name="add_to_cart" class="btn">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                </form>
            </div>
        </div>
        
        <div class="product-meta">
            <div class="meta-item card">
                <div class="meta-label">Category</div>
                <div><?php echo $product['category'] ? htmlspecialchars($product['category']) : 'No category specified'; ?></div>
            </div>
            
            <div class="meta-item card">
                <div class="meta-label">Pickup Information</div>
                <div>Available for on-campus pickup</div>
            </div>
            
            <div class="meta-item card">
                <div class="meta-label">Payment</div>
                <div>Pay when you pickup</div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
        // Quantity input validation
        const quantityInput = document.querySelector('.quantity-input');
        quantityInput.addEventListener('change', function() {
            const max = parseInt(this.getAttribute('max'));
            const value = parseInt(this.value);
            
            if (value < 1) {
                this.value = 1;
            } else if (value > max) {
                this.value = max;
                alert(`Only ${max} available in stock`);
            }
        });
    </script>
</body>
</html>