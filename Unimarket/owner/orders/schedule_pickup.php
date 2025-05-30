<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotOwner();

require_once '../../config/database.php';

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header("Location: /unimarket/owner/orders/list.php");
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
    header("Location: /unimarket/owner/orders/list.php");
    exit();
}

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.first_name, u.last_name
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup_date = $_POST['pickup_date'];
    $pickup_time = $_POST['pickup_time'];
    
    if (empty($pickup_date) || empty($pickup_time)) {
        $error_message = 'Please select both date and time.';
    } else {
        $pickup_datetime = date('Y-m-d H:i:s', strtotime("$pickup_date $pickup_time"));
        
        try {
            $stmt = $pdo->prepare("UPDATE orders SET pickup_schedule = ?, status = 'ready_for_pickup' WHERE order_id = ?");
            $stmt->execute([$pickup_datetime, $order_id]);
            
            $success_message = 'Pickup scheduled successfully!';
            $order['pickup_schedule'] = $pickup_datetime;
            $order['status'] = 'ready_for_pickup';
        } catch (PDOException $e) {
            $error_message = 'Failed to schedule pickup. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Pickup - UniMarket</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .schedule-container {
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
        
        .customer-info {
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .datetime-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        
        .btn-submit:hover {
            background-color: var(--primary-dark);
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
            .datetime-fields {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container schedule-container">
        <a href="/unimarket/owner/orders/list.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
        
        <h1>Schedule Pickup</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="order-card card">
            <div class="order-header">
                <h2>Order #<?php echo $order['order_id']; ?></h2>
                <div>
                    <strong>Order Date:</strong> <?php echo date('M j, Y', strtotime($order['order_date'])); ?>
                </div>
            </div>
            
            <div class="customer-info">
                <h3>Customer Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Current Pickup Schedule</label>
                    <p>
                        <?php if ($order['pickup_schedule']): ?>
                            <?php echo date('F j, Y g:i A', strtotime($order['pickup_schedule'])); ?>
                        <?php else: ?>
                            Not scheduled yet
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="form-group">
                    <label for="pickup_date">Pickup Date*</label>
                    <input type="date" id="pickup_date" name="pickup_date" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="pickup_time">Pickup Time*</label>
                    <select id="pickup_time" name="pickup_time" required>
                        <option value="">Select a time</option>
                        <?php 
                        // Generate time slots from 9AM to 5PM in 30-minute increments
                        $start = strtotime('09:00');
                        $end = strtotime('17:00');
                        
                        for ($time = $start; $time <= $end; $time += 1800) {
                            $time_value = date('H:i', $time);
                            $time_display = date('g:i A', $time);
                            echo "<option value=\"$time_value\">$time_display</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-calendar-check"></i> Schedule Pickup
                </button>
            </form>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date picker
        flatpickr("#pickup_date", {
            minDate: "today",
            dateFormat: "Y-m-d",
            disable: [
                function(date) {
                    // Disable weekends
                    return (date.getDay() === 0 || date.getDay() === 6);
                }
            ]
        });
    </script>
</body>
</html>