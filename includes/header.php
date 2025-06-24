<?php
require_once __DIR__ . '/auth_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>UniMarket - University Marketplace</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="icon" type="image/x-icon" href="/assets/images/logo/tab-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .logo{
            text-decoration: none;
            color: var(--light-text);   
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo img {
            height: 30px;
            width: auto;
            filter: brightness(0) invert(1);
        }

        /* Sticky header styles */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(46, 125, 50, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            max-width: 100%;
            color: var(--light-text);
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        header .container {
            background: transparent;
            box-shadow: none;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            border: none;
            margin: 0 auto;
            padding: 0 30px;
        }

        body {
            padding-top: 60px !important;
        }

        /* Adjust mobile menu for fixed header */
        .mobile-menu {
            position: fixed;
            top: 60px;
            left: 0;
            right: 0;
            z-index: 999;
            background: rgba(46, 125, 50, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
            gap: 10px;
        }

        nav ul li {
            margin: 0;
        }

        nav ul li a {
            color: var(--light-text);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s ease;
            height: 40px;
            white-space: nowrap;
        }

        nav ul li a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        nav ul li a i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .mobile-menu-toggle {
            display: none;
            color: var(--light-text);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
        }

        @media (max-width: 768px) {
            .header-content nav ul {
                display: none;
            }
            
            .mobile-menu-toggle {
                display: block !important;
            }

            .mobile-menu {
                display: none;
                padding: 15px 0;
            }

            .mobile-menu ul {
                list-style: none;
                text-align: center;
                padding: 0;
                margin: 0;
            }

            .mobile-menu ul li {
                margin: 0;
                padding: 10px 0;
            }

            .mobile-menu ul li a {
                display: block;
                padding: 8px 15px;
                color: var(--light-text);
                text-decoration: none;
            }

            .mobile-menu ul li a:hover {
                background: none;
            }
        }

        /* Add logout modal styles */
        .logout-modal {
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

        .logout-modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .logout-modal h2 {
            margin-bottom: 20px;
            color: var(--primary-dark);
        }

        .logout-modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }

        .logout-modal-buttons button {
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .logout-modal-buttons .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }

        .logout-modal-buttons .btn-cancel:hover {
            background: #d0d0d0;
        }

        .logout-modal-buttons .btn-confirm {
            background: #ff4444;
            color: white;
        }

        .logout-modal-buttons .btn-confirm:hover {
            background: #ff0000;
        }

        /* Update notification styles */
        .notification-btn {
            position: relative;
            color: var(--light-text);
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            height: 40px;
            white-space: nowrap;
        }

        .notification-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .notification-btn i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            display: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            animation: pulse 2s infinite;
        }

        .notification-badge.show {
            display: block;
        }

        .notification-container {
            position: relative;
        }

        .notification-modal {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 350px;
            max-height: 80vh;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 2000;
            animation: slideDown 0.2s ease;
            overflow: hidden;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .notification-modal::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 20px;
            width: 16px;
            height: 16px;
            background: white;
            transform: rotate(45deg);
            box-shadow: -2px -2px 5px rgba(0, 0, 0, 0.05);
        }

        .notification-modal-content {
            background: white;
            padding: 0;
            border-radius: 8px;
            max-height: 80vh;
            overflow: hidden;
        }

        .notification-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--primary-color);
            color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .notification-modal-header h2 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notification-modal-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: white;
            opacity: 0.8;
            transition: opacity 0.3s;
            padding: 5px;
            line-height: 1;
        }

        .notification-modal-close:hover {
            opacity: 1;
        }

        .notification-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-list::-webkit-scrollbar {
            width: 4px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 2px;
        }

        .notification-list::-webkit-scrollbar-thumb:hover {
            background: #666;
        }

        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            transition: background-color 0.2s;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        
        .notification-item.unread {
            background-color: #f0f7ff;
        }
        
        .notification-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .notification-icon.ready {
            background-color: #d4edda;
            color: #155724;
        }
        
        .notification-icon.completed {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .notification-icon.cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .notification-content {
            flex: 1;
            min-width: 0;
        }
        
        .notification-title {
            font-weight: 500;
            margin-bottom: 4px;
            color: #333;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .notification-time {
            font-size: 0.8rem;
            color: #666;
        }
        
        .notification-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 4px;
        }
        
        .status-ready {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .no-notifications {
            text-align: center;
            padding: 30px 20px;
            color: #666;
            background: #f8f9fa;
        }

        .no-notifications i {
            font-size: 1.8rem;
            color: #ccc;
            margin-bottom: 8px;
            display: block;
        }

        .no-notifications p {
            margin: 0;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .notification-modal {
                position: fixed;
                top: 60px;
                right: 0;
                left: 0;
                width: 100%;
                max-height: calc(100vh - 60px);
                margin: 0;
                border-radius: 0;
            }

            .notification-modal::before {
                display: none;
            }
        }

        .logout-link {
            color: var(--light-text) !important;
            transition: all 0.3s ease !important;
            background: none !important;
            background-color: transparent !important;
            display: flex !important;
            align-items: center !important;
            height: 40px !important;
            white-space: nowrap !important;
        }
        
        .logout-link:hover {
            background-color: rgba(255, 68, 68, 0.1) !important;
            color: #ff4444 !important;
        }

        .logout-link i {
            width: 20px !important;
            text-align: center !important;
            font-size: 1rem !important;
        }
    </style>
</head>
<body>
    <!-- Add logout modal HTML -->
    <div class="logout-modal" id="logoutModal">
        <div class="logout-modal-content">
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to logout?</p>
            <div class="logout-modal-buttons">
                <button class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn-confirm" onclick="confirmLogout()">Logout</button>
            </div>
        </div>
    </div>

    <header>
        <div class="container header-content">
            <a href="/index.php" class="logo">
                <img src="/assets/images/logo/logo.png" alt="UniMarket Logo">
            </a>
            
            <nav>
                <ul>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isCustomer()): ?>
                            <li><a href="/customer/products/browse.php"><i class="fas fa-search"></i> Browse</a></li>
                            <li><a href="/customer/cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                            <li class="notification-container">
                                <a href="#" onclick="openNotificationModal(); return false;" class="notification-btn">
                                    <i class="fas fa-bell"></i> Notifications
                                    <span class="notification-badge" id="notificationBadge"></span>
                                </a>
                                <div class="notification-modal" id="notificationModal">
                                    <div class="notification-modal-content">
                                        <div class="notification-modal-header">
                                            <h2><i class="fas fa-bell"></i> Notifications</h2>
                                            <button class="notification-modal-close" onclick="closeNotificationModal()">&times;</button>
                                        </div>
                                        <div class="notification-list" id="notificationList">
                                            <div class="no-notifications">
                                                <i class="fas fa-bell-slash"></i>
                                                <p>No notifications yet</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li><a href="/customer/profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <?php elseif (isAdmin()): ?>
                            <li><a href="/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a></li>
                            <li><a href="/admin/users/manage.php"><i class="fas fa-users"></i> Users</a></li>
                            <li><a href="/admin/products/index.php"><i class="fas fa-box"></i> Products</a></li>
                            <li><a href="/admin/orders/manage.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                        <?php else: ?>
                            <li><a href="/owner/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="/owner/products/list.php"><i class="fas fa-box-open"></i> Products</a></li>
                        <?php endif; ?>
                        <li><a href="#" onclick="openLogoutModal(); return false;" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="/index.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="/auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="/auth/register.php"><i class="fas fa-user-plus"></i> Register</a></li>
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
                    <li><a href="/customer/products/browse.php"><i class="fas fa-search"></i> Browse</a></li>
                    <li><a href="/customer/cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                    <li class="notification-container">
                        <a href="#" onclick="openNotificationModal(); return false;" class="notification-btn">
                            <i class="fas fa-bell"></i> Notifications
                            <span class="notification-badge" id="notificationBadge"></span>
                        </a>
                        <div class="notification-modal" id="notificationModal">
                            <div class="notification-modal-content">
                                <div class="notification-modal-header">
                                    <h2><i class="fas fa-bell"></i> Notifications</h2>
                                    <button class="notification-modal-close" onclick="closeNotificationModal()">&times;</button>
                                </div>
                                <div class="notification-list" id="notificationList">
                                    <div class="no-notifications">
                                        <i class="fas fa-bell-slash"></i>
                                        <p>No notifications yet</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li><a href="/customer/profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <?php elseif (isAdmin()): ?>
                    <li><a href="/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a></li>
                    <li><a href="/admin/users/manage.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="/admin/products/index.php"><i class="fas fa-box"></i> Products</a></li>
                    <li><a href="/admin/orders/manage.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <?php else: ?>
                    <li><a href="/owner/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="/owner/products/list.php"><i class="fas fa-box-open"></i> Products</a></li>
                <?php endif; ?>
                <li><a href="#" onclick="openLogoutModal(); return false;" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>
    
    <script>
        document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
            const menu = document.getElementById('mobileMenu');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });

        // Add logout modal functions
        function openLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        function confirmLogout() {
            window.location.href = '/auth/logout.php';
        }

        // Close modal when clicking outside
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogoutModal();
            }
        });
    </script>
    <?php endif; ?>
    
    <main class="container">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Remove the scroll event listener that was causing the hover effect
        });
    </script>

    <script>
        // Notification Modal Functions
        function openNotificationModal() {
            const modal = document.getElementById('notificationModal');
            modal.style.display = 'block';
            loadNotifications();
            
            // Close modal when clicking outside
            document.addEventListener('click', function closeModal(e) {
                if (!modal.contains(e.target) && !e.target.closest('.notification-btn')) {
                    modal.style.display = 'none';
                    document.removeEventListener('click', closeModal);
                }
            });
        }

        function closeNotificationModal() {
            document.getElementById('notificationModal').style.display = 'none';
        }

        function loadNotifications() {
            const notificationList = document.getElementById('notificationList');
            const badge = document.getElementById('notificationBadge');
            
            fetch('/customer/notifications.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    if (data.notifications.length === 0) {
                        notificationList.innerHTML = `
                            <div class="no-notifications">
                                <i class="fas fa-bell-slash"></i>
                                <p>No notifications yet</p>
                            </div>
                        `;
                    } else {
                        notificationList.innerHTML = data.notifications.map(notification => `
                            <div class="notification-item ${notification.unread ? 'unread' : ''}">
                                <div class="notification-icon ${notification.status}">
                                    <i class="fas ${notification.status === 'ready' ? 'fa-check-circle' : 
                                                   notification.status === 'completed' ? 'fa-check-double' : 
                                                   'fa-times-circle'}"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title">${notification.title}</div>
                                    <div class="notification-time">${notification.time}</div>
                                    <span class="notification-status status-${notification.status}">
                                        ${notification.status.charAt(0).toUpperCase() + notification.status.slice(1)}
                                    </span>
                                </div>
                            </div>
                        `).join('');
                    }
                    
                    // Update badge
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count;
                        badge.classList.add('show');
                    } else {
                        badge.classList.remove('show');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    notificationList.innerHTML = `
                        <div class="no-notifications">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Error loading notifications: ${error.message}</p>
                            <p>Please try again later</p>
                        </div>
                    `;
                });
        }

        // Load notifications every 30 seconds if user is logged in
        <?php if (isLoggedIn()): ?>
        setInterval(loadNotifications, 30000);
        <?php endif; ?>
    </script>
