<?php
require_once __DIR__ . '/auth_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>UniMarket - University Marketplace</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .logo{
            text-decoration: none;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <a href="/unimarket/index.php" class="logo">
                <i class="fas fa-flip fa-store" style="margin-right: 10px; --fa-animation-duration: 10s;" ></i>UniMarket
            </a>
            
            <nav>
                <ul>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isCustomer()): ?>
                            <li><a href="/unimarket/customer/products/browse.php"><i class="fas fa-search"></i> Browse</a></li>
                            <li><a href="/unimarket/customer/cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                            <li><a href="/unimarket/customer/profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <?php else: ?>
                            <li><a href="/unimarket/owner/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="/unimarket/owner/products/list.php"><i class="fas fa-box-open"></i> Products</a></li>
                        <?php endif; ?>
                        <li><a href="/unimarket/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="/unimarket/index.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="/unimarket/auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="/unimarket/auth/register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <?php if (isLoggedIn()): ?>
                <div class="mobile-menu-toggle" style="display: none;">
                    <i class="fas fa-bars"></i>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <?php if (isLoggedIn()): ?>
    <style>
        .mobile-menu {
            display: none;
            background-color: var(--primary-dark);
            padding: 15px 0;
        }
        
        .mobile-menu ul {
            list-style: none;
            text-align: center;
        }
        
        .mobile-menu ul li {
            margin-bottom: 10px;
        }
        
        .mobile-menu ul li a {
            color: var(--light-text);
            text-decoration: none;
            display: block;
            padding: 8px 0;
        }
        
        .mobile-menu-toggle {
            color: var(--light-text);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .header-content nav ul {
                display: none;
            }
            
            .mobile-menu-toggle {
                display: block !important;
            }
        }
    </style>
    
    <div class="mobile-menu" id="mobileMenu">
        <div class="container">
            <ul>
                <?php if (isCustomer()): ?>
                    <li><a href="/unimarket/customer/products/browse.php"><i class="fas fa-search"></i> Browse</a></li>
                    <li><a href="/unimarket/customer/cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                    <li><a href="/unimarket/customer/profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <?php else: ?>
                    <li><a href="/unimarket/owner/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="/unimarket/owner/products/list.php"><i class="fas fa-box-open"></i> Products</a></li>
                <?php endif; ?>
                <li><a href="/unimarket/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>
    
    <script>
        document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
            const menu = document.getElementById('mobileMenu');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
    </script>
    <?php endif; ?>
    
    <main class="container">