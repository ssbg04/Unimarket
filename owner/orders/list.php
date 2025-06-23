<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotOwner();

require_once '../../config/database.php';

// Debug query to check order items with sizes
$debug_stmt = $pdo->prepare("
    SELECT oi.order_id, oi.product_id, oi.size, p.name, p.category
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE p.owner_id = ?
    ORDER BY oi.order_id DESC
    LIMIT 5
");
$debug_stmt->execute([$_SESSION['user_id']]);
$debug_items = $debug_stmt->fetchAll();
error_log("Recent order items with sizes: " . print_r($debug_items, true));

// Get all orders with customer info for products owned by this owner
$stmt = $pdo->prepare("
    SELECT o.order_id, o.order_date, o.total_amount, o.status, o.pickup_schedule, 
           u.username AS customer_username, u.first_name, u.last_name,
           GROUP_CONCAT(
               CONCAT(
                   p.name, 
                   CASE 
                       WHEN p.category = 'clothes' AND oi.size IS NOT NULL AND oi.size != ''
                       THEN CONCAT(' (Size: ', oi.size, ')')
                       ELSE ''
                   END,
                   ' x', oi.quantity
               ) ORDER BY p.name SEPARATOR ', '
           ) as order_items,
           COUNT(DISTINCT oi.product_id) as item_count
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    JOIN users u ON o.customer_id = u.user_id
    WHERE p.owner_id = ?
    GROUP BY o.order_id, o.order_date, o.total_amount, o.status, o.pickup_schedule, 
             u.username, u.first_name, u.last_name
    ORDER BY o.order_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// For debugging
error_log("Orders query result: " . print_r($orders, true));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - UniMarket</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .orders-container {
            margin-top: 30px;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .orders-table th, .orders-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .orders-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .order-items {
            max-width: 300px;
        }
        
        .order-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-name {
            font-weight: 500;
        }
        
        .item-size {
            color: #666;
            font-size: 0.9em;
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .item-quantity {
            color: #666;
            font-size: 0.9em;
        }
        
        .order-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
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
        
        .action-link {
            color: var(--primary-color);
            text-decoration: none;
            margin-right: 10px;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .empty-orders {
            text-align: center;
            padding: 50px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .orders-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Manage Orders</h1>
                    <a href="../dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
                <div class="card">
                    <div class="container orders-container">
                        <?php if (empty($orders)): ?>
                            <div class="empty-orders card">
                                <i class="fas fa-box-open" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
                                <h3>No orders yet</h3>
                                <p>Orders for your products will appear here</p>
                            </div>
                        <?php else: ?>
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Items</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Pickup</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['order_id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                            <td>
                                                <div class="order-items">
                                                    <?php 
                                                    $items = explode(', ', $order['order_items']);
                                                    foreach ($items as $item) {
                                                        echo '<div class="order-item">';
                                                        // Check if it's a clothing item with size
                                                        if (strpos($item, '(Size:') !== false) {
                                                            // Split the item into name and size+quantity
                                                            $parts = explode(' (Size:', $item);
                                                            $name = $parts[0];
                                                            $sizeAndQty = $parts[1];
                                                            
                                                            // Split size and quantity
                                                            $sizeQtyParts = explode(') x', $sizeAndQty);
                                                            $size = $sizeQtyParts[0];
                                                            $quantity = $sizeQtyParts[1];
                                                            
                                                            echo '<span class="item-name">' . htmlspecialchars($name) . '</span>';
                                                            echo '<span class="item-size">Size: ' . htmlspecialchars($size) . '</span>';
                                                            echo '<span class="item-quantity">x' . htmlspecialchars($quantity) . '</span>';
                                                        } else {
                                                            echo htmlspecialchars($item);
                                                        }
                                                        echo '</div>';
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <?php 
                                                $status_class = '';
                                                switch ($order['status']) {
                                                    case 'pending':
                                                        $status_class = 'status-pending';
                                                        break;
                                                    case 'processing':
                                                        $status_class = 'status-processing';
                                                        break;
                                                    case 'ready_for_pickup':
                                                        $status_class = 'status-ready';
                                                        break;
                                                    case 'completed':
                                                        $status_class = 'status-completed';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'status-cancelled';
                                                        break;
                                                }
                                                ?>
                                                <span class="order-status <?php echo $status_class; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($order['pickup_schedule']): ?>
                                                    <?php echo date('M j, Y', strtotime($order['pickup_schedule'])); ?>
                                                <?php else: ?>
                                                    Not scheduled
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                                                    <a href="/unimarket/owner/orders/schedule_pickup.php?order_id=<?php echo $order['order_id']; ?>" class="action-link">
                                                        <i class="fas fa-calendar-alt"></i> Schedule
                                                    </a>
                                                <?php endif; ?>
                                                <a href="/unimarket/owner/orders/view.php?order_id=<?php echo $order['order_id']; ?>" class="action-link">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>