<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotCustomer();

require_once '../../config/database.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header("Location: /customer/products/browse.php");
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
    header("Location: /customer/products/browse.php");
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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="/assets/js/notifications.js"></script>
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
        
        .product-sizes {
            margin-bottom: 20px;
        }
        
        .size-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .size-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
            text-align: center;
            background-color: #f9f9f9;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .size-item.in-stock {
            border-color: #2e7d32;
            background-color: #e8f5e9;
        }
        
        .size-item.in-stock:hover {
            background-color: #c8e6c9;
            transform: translateY(-2px);
        }
        
        .size-item.selected {
            background-color: #2e7d32;
            color: white;
        }
        
        .size-item.out-of-stock {
            border-color: #c62828;
            background-color: #ffebee;
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .size-label {
            display: block;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .size-quantity {
            display: block;
            font-size: 0.9em;
            color: #666;
        }
        
        .size-selection {
            margin-bottom: 15px;
        }
        
        .size-selection label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .size-selection select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            margin-bottom: 10px;
        }

        /* Add to cart modal styles */
        .cart-modal {
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

        .cart-modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .cart-modal h2 {
            margin-bottom: 20px;
            color: var(--primary-dark);
        }

        .cart-modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }

        .cart-modal-buttons button {
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .cart-modal-buttons .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }

        .cart-modal-buttons .btn-cancel:hover {
            background: #d0d0d0;
        }

        .cart-modal-buttons .btn-confirm {
            background: var(--primary-color);
            color: white;
        }

        .cart-modal-buttons .btn-confirm:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <!-- Add to cart modal HTML -->
    <div class="cart-modal" id="cartModal">
        <div class="cart-modal-content">
            <h2>Add to Cart</h2>
            <p>Are you sure you want to add this item to your cart?</p>
            <div class="cart-modal-buttons">
                <button class="btn-cancel" onclick="closeCartModal()">Cancel</button>
                <button class="btn-confirm" onclick="confirmAddToCart()">Add to Cart</button>
            </div>
        </div>
    </div>

    <?php include '../../includes/header.php'; ?>
    
    <div class="container product-detail-container">
        <a href="/customer/products/browse.php" class="back-link">
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
                    <img src="/uploads/products/<?php echo $product['image_path']; ?>" 
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
                
                <?php if ($product['category'] === 'clothes' && isset($product['size_data']) && $product['size_data']): ?>
                    <?php 
                    $size_data = json_decode($product['size_data'], true);
                    if ($size_data && is_array($size_data)): 
                    ?>
                        <div class="product-sizes">
                            <h3>Available Sizes</h3>
                            <div class="size-grid">
                                <?php foreach ($size_data as $size => $quantity): ?>
                                    <div class="size-item <?php echo $quantity > 0 ? 'in-stock' : 'out-of-stock'; ?>" 
                                         data-size="<?php echo htmlspecialchars($size); ?>"
                                         onclick="<?php echo $quantity > 0 ? 'selectSize(this)' : ''; ?>">
                                        <span class="size-label"><?php echo htmlspecialchars($size); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
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
                
                <form method="POST" action="../../ajax/add_to_cart.php" class="add-to-cart-form" id="addToCartForm">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <?php if ($product['category'] === 'clothes' && isset($product['size_data']) && $product['size_data']): ?>
                        <input type="hidden" name="size" id="selectedSize" required>
                    <?php endif; ?>
                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" 
                           class="quantity-input">
                    <button type="button" onclick="openCartModal()" class="btn">
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
        // Add to cart modal functions
        function openCartModal() {
            const form = document.getElementById('addToCartForm');
            const sizeInput = document.getElementById('selectedSize');
            
            if (sizeInput && !sizeInput.value) {
                showToast('Please select a size', 'error');
                return;
            }
            
            document.getElementById('cartModal').style.display = 'flex';
        }

        function closeCartModal() {
            document.getElementById('cartModal').style.display = 'none';
        }

        function confirmAddToCart() {
            const form = document.getElementById('addToCartForm');
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count in header
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    // Show success message
                    showToast('Item added to cart successfully!', 'success');
                    closeCartModal();
                } else {
                    showToast(data.message || 'Failed to add item to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while adding to cart', 'error');
            });
        }

        // Close modal when clicking outside
        document.getElementById('cartModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCartModal();
            }
        });

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

        function selectSize(element) {
            // Remove selected class from all size items
            document.querySelectorAll('.size-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Add selected class to clicked item
            element.classList.add('selected');
            
            // Update hidden input value
            document.getElementById('selectedSize').value = element.dataset.size;
        }
    </script>
</body>
</html>
