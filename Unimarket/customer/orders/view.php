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

// Get order details first
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.first_name, u.last_name, u.email
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

// Verify order belongs to current user
if (!$order || $order['customer_id'] != $_SESSION['user_id']) {
    header("Location: /unimarket/customer/orders/list.php");
    exit();
}

// Handle pickup confirmation and cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_pickup'])) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE order_id = ? AND customer_id = ? AND status = 'ready'");
            $stmt->execute([$order_id, $_SESSION['user_id']]);
            
            // Refresh order data
            $stmt = $pdo->prepare("
                SELECT o.order_id, o.order_date, o.total_amount, o.status, o.pickup_schedule
                FROM orders o
                WHERE o.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            
            $success_message = 'Order pickup confirmed! Thank you for your purchase.';
        } catch (PDOException $e) {
            $error_message = 'Failed to confirm pickup. Please try again.';
        }
    } elseif (isset($_POST['cancel_order'])) {
        try {
            // Only allow cancellation if order is not completed or cancelled
            if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled') {
                $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND customer_id = ?");
                $stmt->execute([$order_id, $_SESSION['user_id']]);
                
                // Refresh order data
                $stmt = $pdo->prepare("
                    SELECT o.order_id, o.order_date, o.total_amount, o.status, o.pickup_schedule
                    FROM orders o
                    WHERE o.order_id = ?
                ");
                $stmt->execute([$order_id]);
                $order = $stmt->fetch();
                
                $success_message = 'Order has been cancelled successfully.';
            } else {
                $error_message = 'This order cannot be cancelled.';
            }
        } catch (PDOException $e) {
            $error_message = 'Failed to cancel order. Please try again.';
        }
    } elseif (isset($_POST['delete_order'])) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update order status to deleted
            $stmt = $pdo->prepare("UPDATE orders SET status = 'deleted' WHERE order_id = ? AND customer_id = ?");
            $stmt->execute([$order_id, $_SESSION['user_id']]);
            
            // Log the deletion
            error_log("Deleting order ID: " . $order_id . " for user ID: " . $_SESSION['user_id']);
            
            // Commit transaction
            $pdo->commit();
            
            // Redirect to orders list
            header("Location: /unimarket/customer/orders/list.php");
            exit();
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            error_log("Error deleting order: " . $e->getMessage());
            $error_message = 'Failed to delete order. Please try again.';
        }
    }
}

// Get order items with seller information
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image_path, p.price, 
           u.username as seller_username, u.first_name as seller_first_name, u.last_name as seller_last_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN users u ON p.owner_id = u.user_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Calculate total items
$total_items = array_sum(array_column($order_items, 'quantity'));
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
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(46, 125, 50, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .order-details-container {
            margin-top: 100px; /* Add margin to prevent content from hiding under fixed header */
        }
        
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        
        .info-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1.1rem;
            font-weight: 500;
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
            gap: 15px;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            background-color: #f8f9fa;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-name {
            font-weight: 500;
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
        
        .pickup-confirmation-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .confirm-pickup-btn {
            background-color: #28a745;
            color: white;
            padding: 12px 24px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }
        
        .confirm-pickup-btn:hover {
            background-color: #218838;
        }
        
        .confirm-pickup-btn i {
            font-size: 1.2rem;
        }
        
        .order-actions {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .cancel-order-form {
            margin-top: 20px;
        }
        
        .cancel-order-btn {
            background-color: #dc3545;
            color: white;
            padding: 12px 24px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }
        
        .cancel-order-btn:hover {
            background-color: #c82333;
        }
        
        .cancel-order-btn i {
            font-size: 1.2rem;
        }
        
        .seller-username {
            font-size: 0.9rem;
            color: #666;
            margin-top: 3px;
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .items-table {
                display: block;
                overflow-x: auto;
            }
            
            .product-cell {
                min-width: 200px;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container order-details-container">
        <a href="/unimarket/customer/profile.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Profile
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
                case 'ready':
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
                    <div class="info-label">Order Number</div>
                    <div class="info-value">#<?php echo $order['order_id']; ?></div>
                </div>
                
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
                    <div class="info-label">Total Items</div>
                    <div class="info-value"><?php echo $total_items; ?> items</div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Total Amount</div>
                    <div class="info-value">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value">Pay on Pickup</div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Pickup Schedule</div>
                    <div class="info-value">
                        <?php if ($order['pickup_schedule']): ?>
                            <?php echo date('F j, Y g:i A', strtotime($order['pickup_schedule'])); ?>
                        <?php else: ?>
                            <span style="color: #666;">Waiting for seller to schedule pickup time</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="order-actions">
                <?php if ($order['status'] === 'ready'): ?>
                    <form method="POST" class="pickup-confirmation-form" onsubmit="return confirm('Have you received your items and completed the payment?');">
                        <button type="submit" name="confirm_pickup" class="btn confirm-pickup-btn">
                            <i class="fas fa-check-circle"></i> Confirm Pickup
                        </button>
                    </form>
                <?php endif; ?>
                
                <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                    <form method="POST" class="cancel-order-form" onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.');">
                        <button type="submit" name="cancel_order" class="btn cancel-order-btn">
                            <i class="fas fa-times-circle"></i> Cancel Order
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="order-items card">
            <h2>Order Items</h2>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td class="product-cell">
                                <?php if ($item['image_path']): ?>
                                    <div class="product-image">
                                        <img src="/unimarket/assets/images/products/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </div>
                                <?php else: ?>
                                    <div class="product-image">
                                        <i class="fas fa-box"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($item['seller_first_name'] . ' ' . $item['seller_last_name']); ?>
                                <div class="seller-username">@<?php echo htmlspecialchars($item['seller_username']); ?></div>
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

            <?php if ($order['status'] === 'completed' || $order['status'] === 'cancelled'): ?>
                <form method="POST" style="margin-top: 20px; text-align: right;">
                    <button type="submit" name="delete_order" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this order? This action cannot be undone.')">
                        <i class="fas fa-trash"></i> Delete Order
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>