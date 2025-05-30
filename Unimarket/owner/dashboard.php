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
        #order-number{
            text-decoration: none;
        }

        #order-number:hover{
            color: green;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container dashboard-container">
        <div class="sidebar">
            <h3>Owner Menu</h3>
            <ul>
                <li><a href="/unimarket/owner/dashboard.php" class="active">Dashboard</a></li>
                <li><a href="/unimarket/owner/products/list.php">Products</a></li>
                <li><a href="/unimarket/owner/products/add.php">Add Product</a></li>
                <li><a href="/unimarket/owner/orders/list.php">Orders</a></li>
                <li><a href="/unimarket/auth/logout.php">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <h1>Dashboard</h1>
            
            <div class="card-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <div class="card">
                    <h3>Products</h3>
                    <p style="font-size: 24px; font-weight: bold; color: var(--primary-color);"><?php echo $products_count; ?></p>
                </div>
                
                <div class="card">
                    <h3>Pending Orders</h3>
                    <p style="font-size: 24px; font-weight: bold; color: var(--primary-color);"><?php echo $pending_orders_count; ?></p>
                </div>
            </div>
            
            <h2>Recent Orders</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><a id="order-number" href="/unimarket/owner/orders/list.php?order_id=<?php echo $order['order_id']; ?>">#<?php echo $order['order_id']; ?></a></td>
                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><span class="status-badge"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>