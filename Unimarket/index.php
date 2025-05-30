<?php
require_once 'includes/auth_functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniMarket - University Marketplace</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <style>
        .hero {
            background-color: var(--primary-color);
            color: var(--light-text);
            padding: 80px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 30px;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }
        
        .feature-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            margin-bottom: 15px;
            color: var(--primary-dark);
        }
        
        .cta-section {
            background-color: #f5f5f5;
            padding: 60px 0;
            text-align: center;
            margin-top: 40px;
        }
        
        .trending-products {
            margin: 60px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 40px;
            color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="hero">
        <div class="container">
            <h1>Welcome to UniMarket</h1>
            <p>Your university's premier marketplace for buying and selling products within the campus community.</p>
            <?php if (!isLoggedIn()): ?>
                <div>
                    <a href="/unimarket/auth/register.php" class="btn" style="margin-right: 10px;">Join Now</a>
                    <a href="/unimarket/auth/login.php" class="btn btn-secondary">Sign In</a>
                </div>
            <?php else: ?>
                <div>
                    <?php if (isCustomer()): ?>
                        <a href="/unimarket/customer/products/browse.php" class="btn">Browse Products</a>
                    <?php else: ?>
                        <a href="/unimarket/owner/dashboard.php" class="btn">Go to Dashboard</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container">
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">🛍️</div>
                <h3>Campus Marketplace</h3>
                <p>Buy and sell products exclusively within your university community.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🔄</div>
                <h3>Easy Pickup</h3>
                <p>Schedule convenient on-campus pickup times for your orders.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🛒</div>
                <h3>Secure Transactions</h3>
                <p>Safe and reliable transactions between students and campus vendors.</p>
            </div>
        </div>
        
        <?php
        // Display trending products (only for customers or guests)
        if (!isLoggedIn() || isCustomer()) {
            require_once 'config/database.php';
            
            $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 6");
            $trending_products = $stmt->fetchAll();
            
            if (!empty($trending_products)) {
                echo '<div class="trending-products">';
                echo '<h2 class="section-title">Trending Products</h2>';
                echo '<div class="product-grid">';
                
                foreach ($trending_products as $product) {
                    echo '<div class="product-card"">';
                    echo '<div class="product-image">';
                    if ($product['image_path']) {
                        echo '<img src="/unimarket/assets/images/products/'.$product['image_path'].'" alt="'.htmlspecialchars($product['name']).'">';
                    } else {
                        echo '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #999;">No Image</div>';
                    }
                    echo '</div>';
                    echo '<div class="product-info">';
                    echo '<h3 class="product-title">'.htmlspecialchars($product['name']).'</h3>';
                    echo '<p class="product-price">$'.number_format($product['price'], 2).'</p>';
                    
                    if (isLoggedIn()) {
                        echo '<form action="/unimarket/customer/cart.php" method="POST" style="display: flex;">';
                        echo '<input type="hidden" name="product_id" value="'.$product['product_id'].'">';
                        echo '<a style="display: flex;" href="/unimarket/customer/products/browse.php"><input type="number" name="quantity" value="1" min="1" style="width: 60px; margin-right: 10px;"></a>';
                        echo '<button type="submit" name="add_to_cart" class="btn" style="padding: 8px 15px;">Add to Cart</button>';
                        echo '</form>';
                    } else {
                        echo '<a href="/unimarket/auth/login.php" class="btn" style="display: block; text-align: center;">Login to Purchase</a>';
                    }
                    
                    echo '</div>';
                    echo '</div>';
                }
                
                echo '</div>';
                echo '<div style="text-align: center; margin-top: 30px;">';
                echo '<a href="/unimarket/customer/products/browse.php" class="btn">View All Products</a>';
                echo '</div>';
                echo '</div>';
            }
        }
        ?>
    </div>
    
    <div class="cta-section">
        <div class="container">
            <h2>Ready to join the campus marketplace?</h2>
            <p style="max-width: 600px; margin: 0 auto 30px;">Whether you're looking to buy or sell, UniMarket connects you with your university community.</p>
            <?php if (!isLoggedIn()): ?>
                <a href="/unimarket/auth/register.php" class="btn">Get Started</a>
            <?php else: ?>
                <?php if (isCustomer()): ?>
                    <a href="/unimarket/customer/products/browse.php" class="btn">Browse Products</a>
                <?php else: ?>
                    <a href="/unimarket/owner/products/add.php" class="btn">Add New Product</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>