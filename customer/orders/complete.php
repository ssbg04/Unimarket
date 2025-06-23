<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotCustomer();

require_once '../../config/database.php';

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header("Location: /customer/orders/list.php");
    exit();
}

$order_id = (int)$_GET['order_id'];

// Verify order belongs to current user and is ready for pickup
$stmt = $pdo->prepare("
    SELECT o.order_id, o.status, o.pickup_schedule
    FROM orders o
    WHERE o.order_id = ? AND o.customer_id = ? AND o.status = 'ready_for_pickup'
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: /customer/orders/list.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle order completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    try {
        $pdo->beginTransaction();
        
        // Update order status to completed
        $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        $pdo->commit();
        $success_message = 'Order has been marked as completed. Thank you for your purchase!';
        
        // Redirect to order view page after a short delay
        header("Refresh: 2; URL=/customer/orders/view.php?order_id=" . $order_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = 'An error occurred while completing the order. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Order - UniMarket</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .complete-order-container {
            margin-top: 30px;
        }
        
        .order-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .pickup-info {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .pickup-info h3 {
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .pickup-info p {
            margin: 5px 0;
        }
        
        .complete-form {
            margin-top: 20px;
        }
        
        .btn-complete {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        
        .btn-complete:hover {
            background-color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container complete-order-container">
        <a href="/customer/orders/view.php?order_id=<?php echo $order_id; ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Order Details
        </a>
        
        <h1>Complete Order</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="order-card">
            <div class="order-header">
                <h2>Order #<?php echo $order['order_id']; ?></h2>
                <span class="order-status status-ready">Ready for Pickup</span>
            </div>
            
            <div class="pickup-info">
                <h3><i class="fas fa-info-circle"></i> Pickup Information</h3>
                <p><strong>Scheduled Pickup:</strong> <?php echo date('F j, Y g:i A', strtotime($order['pickup_schedule'])); ?></p>
                <p><strong>Payment Method:</strong> Pay on Pickup</p>
                <p><strong>Instructions:</strong> Please bring your university ID for verification when picking up your order.</p>
            </div>
            
            <form method="POST" class="complete-form">
                <p>Click the button below to mark this order as completed after you have picked up your items and made the payment.</p>
                <button type="submit" name="complete_order" class="btn-complete">
                    <i class="fas fa-check-circle"></i> Mark Order as Completed
                </button>
            </form>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html> 
