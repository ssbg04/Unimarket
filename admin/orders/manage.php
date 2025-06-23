<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

require_once '../../config/database.php';

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$new_status, $order_id]);
    flashMessage('success', 'Order status updated successfully.');
    
    header("Location: manage.php");
    exit();
}

// Get all orders with customer information
$stmt = $pdo->prepare("
    SELECT o.*, u.username as customer_name 
    FROM orders o 
    JOIN users u ON o.customer_id = u.user_id 
    ORDER BY o.order_date DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - UniMarket Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .manage-orders {
            padding: 20px;
        }
        
        .orders-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .orders-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .orders-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .orders-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .btn-small {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            margin-right: 5px;
        }
        
        .btn-update {
            background: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
        }
        
        .btn-update:hover {
            background: var(--primary-dark);
        }
        
        .btn-view {
            background-color: #28a745;
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }
        
        .btn-view:hover {
            background-color: #218838;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
            font-weight: 600;
        }
        
        .completed-note {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #155724;
            font-weight: 500;
            padding: 5px 10px;
            background-color: #d4edda;
            border-radius: 15px;
        }
        
        .completed-note i {
            font-size: 0.9rem;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
            font-weight: 600;
        }
        
        .cancelled-note {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #721c24;
            font-weight: 500;
            padding: 5px 10px;
            background-color: #f8d7da;
            border-radius: 15px;
        }
        
        .cancelled-note i {
            font-size: 0.9rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #6c757d;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-button:hover {
            color: #495057;
        }

        .back-button i {
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container manage-orders">
        <a href="/admin/dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <h1>Manage Orders</h1>
        
        <?php if ($message = getFlashMessage('success')): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <?php if ($order['status'] === 'completed'): ?>
                                <div class="completed-note">
                                    <i class="fas fa-check-circle"></i> Completed
                                </div>
                            <?php elseif ($order['status'] === 'cancelled'): ?>
                                <div class="cancelled-note">
                                    <i class="fas fa-times-circle"></i> Cancelled
                                </div>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <select name="status" class="status-select">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn-small btn-update">Update</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="view.php?id=<?php echo $order['order_id']; ?>" class="btn-small btn-view">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html> 
