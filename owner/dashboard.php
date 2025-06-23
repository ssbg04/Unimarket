<?php
require_once '../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotOwner();

require_once '../config/database.php';

// Get owner's products count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE owner_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$products_count = $stmt->fetchColumn();

// Get pending orders count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders o 
                      JOIN order_items oi ON o.order_id = oi.order_id 
                      JOIN products p ON oi.product_id = p.product_id 
                      WHERE p.owner_id = ? AND o.status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$pending_orders_count = $stmt->fetchColumn();

// Get recent orders
$stmt = $pdo->prepare("SELECT o.order_id, o.order_date, o.total_amount, o.status, u.username 
                      FROM orders o 
                      JOIN order_items oi ON o.order_id = oi.order_id 
                      JOIN products p ON oi.product_id = p.product_id 
                      JOIN users u ON o.customer_id = u.user_id
                      WHERE p.owner_id = ? 
                      GROUP BY o.order_id 
                      ORDER BY o.order_date DESC 
                      LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_orders = $stmt->fetchAll();

// Get vendor information
$vendor_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'owner'");
$vendor_stmt->execute([$_SESSION['user_id']]);
$vendor = $vendor_stmt->fetch();

// Get total products count
$products_stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE owner_id = ?");
$products_stmt->execute([$_SESSION['user_id']]);
$total_products = $products_stmt->fetchColumn();

// Get total orders count
$orders_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE p.owner_id = ?
");
$orders_stmt->execute([$_SESSION['user_id']]);
$total_orders = $orders_stmt->fetchColumn();

// Get total revenue
$revenue_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE p.owner_id = ? AND o.status = 'completed'
");
$revenue_stmt->execute([$_SESSION['user_id']]);
$total_revenue = $revenue_stmt->fetchColumn();

// Get recent orders
$recent_orders_stmt = $pdo->prepare("
    SELECT o.order_id, o.order_date, o.status, o.total_amount,
           u.first_name, u.last_name
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    JOIN users u ON o.customer_id = u.user_id
    WHERE p.owner_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
    LIMIT 4
");
$recent_orders_stmt->execute([$_SESSION['user_id']]);
$recent_orders = $recent_orders_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - UniMarket</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <style>
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: #ffffff;
            color: #333;
            padding: 20px 0;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            color: #333;
            font-size: 1.5rem;
            margin: 0;
            padding: 0;
            font-weight: 600;
        }

        .sidebar-header p {
            color: #666;
            margin: 5px 0 0;
            font-size: 0.9rem;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin: 5px 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background: #e8f5e9;
            color: #2e7d32;
            border-left-color: #2e7d32;
        }

        .nav-link.active {
            background: #e8f5e9;
            color: #2e7d32;
            border-left-color: #2e7d32;
            font-weight: 500;
        }

        .nav-link i {
            width: 20px;
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .nav-link span {
            font-size: 0.95rem;
        }

        .logout-link {
            margin-top: auto;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .logout-link a {
            color: var(--light-text);
            display: flex;
            align-items: center;
            padding: 12px 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            height: 40px;
            white-space: nowrap;
            background-color: transparent !important;
        }

        .logout-link a:hover {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
        }

        .logout-link i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
            margin-right: 10px;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 40px;
            min-height: 100vh;
            background: #f5f6fa;
            width: calc(100% - 250px);
        }

        .dashboard-container {
            max-width: 1800px;
            margin: 0 auto;
            padding: 30px;
        }

        .dashboard-header {
            margin-bottom: 40px;
            padding: 20px 0;
            border-bottom: 2px solid #f0f0f0;
            position: relative;
        }

        .dashboard-header h1 {
            font-size: 2rem;
            color: #2e7d32;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dashboard-header h1::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 24px;
            background-color: #2e7d32;
            border-radius: 2px;
        }

        .dashboard-header p {
            margin: 8px 0 0 0;
            color: #666;
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 50px;
            max-width: 1200px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s ease;
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .stat-card h3 {
            margin: 0 0 15px 0;
            color: #666;
            font-size: 1.1rem;
        }

        .stat-card .value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2e7d32;
        }

        .recent-orders {
            background: white;
            padding: 25px 40px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            width: fit-content;
            margin: 0 auto;
        }

        .recent-orders h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 1.4rem;
            font-weight: 600;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }

        .view-all-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background-color: #2e7d32;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-top: 20px;
        }

        .view-all-btn:hover {
            background-color: #1b5e20;
            transform: translateY(-1px);
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
            min-width: 600px;
        }

        .table th,
        .table td {
            padding: 16px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.95rem;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
            border-top: 1px solid #f0f0f0;
            min-width: 100px;
        }

        .table th:first-child {
            border-top-left-radius: 8px;
            min-width: 80px;
        }

        .table th:nth-child(2) {
            min-width: 150px;
        }

        .table th:nth-child(3) {
            min-width: 100px;
        }

        .table th:last-child {
            border-top-right-radius: 8px;
            min-width: 120px;
        }

        .table td {
            white-space: nowrap;
            color: #444;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 6px;
            background-color: #e9ecef;
            color: #495057;
        }

        .status-badge.pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-badge.processing {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-badge.ready {
            background-color: #d4edda;
            color: #155724;
        }

        .status-badge.completed {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .status-badge.cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        #order-number {
            color: #2e7d32;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }

        #order-number:hover {
            color: #1b5e20;
            text-decoration: none;
        }

        @media (max-width: 1600px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .recent-orders {
                margin: 0 -20px;
                border-radius: 0;
                padding: 20px;
            }

            .table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container dashboard-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Vendor Dashboard</h2>
                <p>Welcome, <?php echo htmlspecialchars($vendor['first_name'] . ' ' . $vendor['last_name']); ?></p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="products/list.php" class="nav-link">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders/list.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
            </ul>
            
            <div class="logout-link">
                <a href="/unimarket/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="dashboard-container">
                <div class="dashboard-header">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome to your vendor dashboard</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Products</h3>
                        <div class="value"><?php echo $total_products; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Orders</h3>
                        <div class="value"><?php echo $total_orders; ?></div>
                    </div>
                </div>

                <div class="recent-orders">
                    <h2>Recent Orders</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="/unimarket/owner/orders/view.php?order_id=<?php echo $order['order_id']; ?>" id="order-number">
                                            #<?php echo $order['order_id']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower(str_replace('_', '', $order['status'])); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="/unimarket/owner/orders/list.php" class="view-all-btn">
                        <i class="fas fa-list"></i>
                        View All Orders
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Remove modal-related JavaScript
    </script>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>