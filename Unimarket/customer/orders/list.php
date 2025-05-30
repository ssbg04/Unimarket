<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotCustomer();

require_once '../../config/database.php';

// Pagination variables
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($current_page - 1) * $per_page;

// Get total orders count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
$count_stmt->execute([$_SESSION['user_id']]);
$total_orders = $count_stmt->fetchColumn();

// Get orders with pagination
$stmt = $pdo->prepare("
    SELECT o.order_id, o.order_date, o.total_amount, o.status, o.pickup_schedule
    FROM orders o
    WHERE o.customer_id = ?
    ORDER BY o.order_date DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

// Calculate total pages
$total_pages = ceil($total_orders / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - UniMarket</title>
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
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: var(--text-color);
        }
        
        .pagination a:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .pagination .current {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
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
    
    <div class="container orders-container">
        <h1>My Orders</h1>
        
        <?php if (empty($orders)): ?>
            <div class="empty-orders card">
                <i class="fas fa-box-open" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
                <h3>You haven't placed any orders yet</h3>
                <p>Start shopping to see your orders here</p>
                <a href="/unimarket/customer/products/browse.php" class="btn">Browse Products</a>
            </div>
        <?php else: ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Pickup Schedule</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <a href="/unimarket/customer/orders/view.php?order_id=<?php echo $order['order_id']; ?>">
                                    #<?php echo $order['order_id']; ?>
                                </a>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
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
                                    <?php echo date('M j, Y g:i A', strtotime($order['pickup_schedule'])); ?>
                                <?php else: ?>
                                    Not scheduled
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=1">First</a>
                        <a href="?page=<?php echo $current_page - 1; ?>">Prev</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $current_page - 2); $i <= min($current_page + 2, $total_pages); $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?>">Next</a>
                        <a href="?page=<?php echo $total_pages; ?>">Last</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>