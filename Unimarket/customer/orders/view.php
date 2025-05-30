<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotCustomer();

require_once '../../config/database.php';

// Get order ID from URL
if (!isset($_GET['order_id'])) {
    header("Location: /unimarket/customer/orders/list.php");
    exit();
}

$order_id = (int)$_GET['order_id'];

// Verify order belongs to current user
$stmt = $pdo->prepare("SELECT customer_id FROM orders WHERE order_id = ?");
$stmt->execute([$order_id]);
$order_customer_id = $stmt->fetchColumn();

if ($order_customer_id != $_SESSION['user_id']) {
    header("Location: /unimarket/customer/orders/list.php");
    exit();
}

// Get order details
$stmt = $pdo->prepare("
    SELECT o.order_id, o.order_date, o.total_amount, o.status, o.pickup_schedule
    FROM orders o
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image_path, p.price
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
    <title>Order Details - UniMarket</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-details-container {
            margin-top: 30px;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .order-info {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .info-item {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1.1rem;
        }
        
        .order-status {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 4px;
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
        
        .order-items {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .items-table th, .items-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .items-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .product-cell {
            display: flex;
            align-items: center;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            margin-right: 15px;
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-image img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .order-total {
            text-align: right;
            margin-top: 20px;
            font-size: 1.2rem;
            font-weight: 600;
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
        
        @media (max-width: 768px) {
            .items-table {
                display: block;
                overflow-x: auto;
            }
            
            .product-cell {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .product-image {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container order-details-container">
        <a href="/unimarket/customer/orders/list.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
        
        <div class="order-header">
            <h1>Order #<?php echo $order['order_id']; ?></h1>
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
        </div>
        
        <div class="order-info card">
            <h2>Order Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Order Date</div>
                    <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Order Status</div>
                    <div class="info-value">
                        <span class="order-status <?php echo $status_class; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                        </span>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Pickup Schedule</div>
                    <div class="info-value">
                        <?php if ($order['pickup_schedule']): ?>
                            <?php echo date('F j, Y g:i A', strtotime($order['pickup_schedule'])); ?>
                        <?php else: ?>
                            Not scheduled yet
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value">Pay on Pickup</div>
                </div>
            </div>
        </div>
        
        <div class="order-items card">
            <h2>Order Items</h2>
            <table class="items-table">
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
                            <td>
                                <div class="product-cell">
                                    <div class="product-image">
                                        <?php if ($item['image_path']): ?>
                                            <img src="/unimarket/assets/images/products/<?php echo $item['image_path']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-box-open" style="color: #ccc;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    </div>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="order-total">
                <div style="margin-bottom: 10px;">Subtotal: ₱<?php echo number_format($order['total_amount'], 2); ?></div>
                <div style="margin-bottom: 10px;">Shipping: Free</div>
                <div style="font-size: 1.4rem;">Total: ₱<?php echo number_format($order['total_amount'], 2); ?></div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>