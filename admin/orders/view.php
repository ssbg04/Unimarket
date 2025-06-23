<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

require_once '../../config/database.php';

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header("Location: /admin/orders/manage.php");
    exit();
}

$order_id = (int)$_GET['id'];

// Get order details with customer information
$stmt = $pdo->prepare("
    SELECT o.*, u.username as customer_name, u.email as customer_email
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: /admin/orders/manage.php");
    exit();
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.price as product_price
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order - UniMarket</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .order-details {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .order-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .order-info h2 {
            margin: 0 0 20px 0;
            color: var(--primary-dark);
        }
        
        .info-group {
            margin-bottom: 15px;
        }
        
        .info-group label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-group span {
            color: #666;
        }
        
        .order-items {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .order-items h2 {
            margin: 0 0 20px 0;
            color: var(--primary-dark);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .order-total {
            text-align: right;
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container order-details">
        <div class="order-info">
            <h2>Order #<?php echo $order['order_id']; ?></h2>
            
            <div class="info-group">
                <label>Customer</label>
                <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
            </div>
            
            <div class="info-group">
                <label>Email</label>
                <span><?php echo htmlspecialchars($order['customer_email']); ?></span>
            </div>
            
            <div class="info-group">
                <label>Order Date</label>
                <span><?php echo date('F d, Y H:i', strtotime($order['order_date'])); ?></span>
            </div>
            
            <div class="info-group">
                <label>Status</label>
                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="order-items">
            <h2>Order Items</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td>₱<?php echo number_format($item['product_price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>₱<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="order-total">
                Total: ₱<?php echo number_format($order['total_amount'], 2); ?>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html> 
