<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotOwner();

require_once '../../config/database.php';

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header("Location: /owner/orders/list.php");
    exit();
}

$order_id = (int)$_GET['order_id'];

// Verify order contains owner's products
$stmt = $pdo->prepare("
    SELECT o.order_id 
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE o.order_id = ? AND p.owner_id = ?
    LIMIT 1
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$valid_order = $stmt->fetch();

if (!$valid_order) {
    header("Location: /owner/orders/list.php");
    exit();
}

// Get order details with proper null checks
$stmt = $pdo->prepare("
    SELECT o.*, 
           COALESCE(u.username, 'N/A') AS username,
           COALESCE(u.first_name, '') AS first_name,
           COALESCE(u.last_name, '') AS last_name,
           COALESCE(u.email, 'Not provided') AS email,
           COALESCE(u.phone, 'Not provided') AS phone
    FROM orders o
    LEFT JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

// Get order items for this owner's products
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.price, p.image_path, p.category, oi.size
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ? AND p.owner_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order_items = $stmt->fetchAll();

// Calculate subtotal for owner's products
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        // Refresh order data
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   COALESCE(u.username, 'N/A') AS username,
                   COALESCE(u.first_name, '') AS first_name,
                   COALESCE(u.last_name, '') AS last_name,
                   COALESCE(u.email, 'Not provided') AS email,
                   COALESCE(u.phone, 'Not provided') AS phone
            FROM orders o
            LEFT JOIN users u ON o.customer_id = u.user_id
            WHERE o.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        $success_message = 'Order status updated successfully!';
    } catch (PDOException $e) {
        $error_message = 'Failed to update order status. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> - UniMarket</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-detail-container {
            margin-top: 30px;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .order-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .customer-info {
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
            background-color: #d4edda;
            color: #155724;
            font-weight: 600;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
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
        
        .item-size {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
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
        
        .status-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 20px;
        }
        
        .status-form select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .status-form select:disabled,
        .status-form button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .status-note {
            display: inline-block;
            margin-left: 10px;
            color: #666;
            font-style: italic;
        }
        
        .completed-note {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #155724;
            font-weight: 500;
            padding: 8px 15px;
            background-color: #d4edda;
            border-radius: 4px;
        }
        
        .completed-note i {
            font-size: 1.1rem;
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
            
            .status-form {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container order-detail-container">
        <a href="/owner/orders/list.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
        
        <div class="order-header">
            <h1>Order #<?php echo $order_id; ?></h1>
            <?php 
            $status_class = '';
            switch ($order['status'] ?? '') {
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
                default:
                    $status_class = 'status-pending';
            }
            ?>
            <span class="order-status <?php echo $status_class; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $order['status'] ?? 'pending')); ?>
            </span>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="order-card card">
            <h2>Customer Information</h2>
            <div class="customer-info">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value">
                            <?php 
                            $first_name = $order['first_name'] ?? '';
                            $last_name = $order['last_name'] ?? '';
                            echo htmlspecialchars(trim("$first_name $last_name") ?: 'Not provided'); 
                            ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Username</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['username'] ?? 'N/A'); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['email'] ?? 'Not provided'); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Phone</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['phone'] ?? 'Not provided'); ?></div>
                    </div>
                </div>
            </div>
            
            <h2>Order Information</h2>
            <div class="order-info">
                <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></p>
                <p><strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
                <?php if ($order['status'] !== 'ready'): ?>
                <p><strong>Pickup Schedule:</strong> <?php echo date('F j, Y g:i A', strtotime($order['pickup_schedule'])); ?></p>
                <?php endif; ?>
            </div>
            
            <form method="POST" class="status-form">
                <?php if ($order['status'] !== 'completed'): ?>
                    <label for="status">Update Status:</label>
                    <select name="status" id="status">
                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="ready" <?php echo $order['status'] === 'ready' ? 'selected' : ''; ?>>Ready for Pickup</option>
                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" name="update_status" class="btn">
                        <i class="fas fa-save"></i> Update
                    </button>
                <?php else: ?>
                    <div class="completed-note">
                        <i class="fas fa-check-circle"></i> Order is completed and cannot be modified
                    </div>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="order-card card">
            <h2>Order Items (Your Products)</h2>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Size</th>
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
                                            <img src="/assets/images/products/<?php echo $item['image_path']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
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
                            <td>
                                <?php if ($item['category'] === 'clothes' && $item['size']): ?>
                                    <span class="item-size"><?php echo htmlspecialchars($item['size']); ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="order-total">
                <div style="margin-bottom: 10px;">Subtotal: $<?php echo number_format($subtotal, 2); ?></div>
                <div style="font-size: 1.4rem;">Total: $<?php echo number_format($subtotal, 2); ?></div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>
