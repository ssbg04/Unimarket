<?php
require_once '../includes/auth_functions.php';
require_once '../config/database.php';

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get user ID
$user_id = $_SESSION['user_id'];

try {
    // Get notifications for the user's orders
    $stmt = $pdo->prepare("
        SELECT 
            o.order_id,
            o.status,
            o.order_date,
            CASE 
                WHEN o.status = 'ready' THEN CONCAT('Your order #', o.order_id, ' is ready for pickup!')
                WHEN o.status = 'completed' THEN CONCAT('Your order #', o.order_id, ' has been completed.')
                WHEN o.status = 'cancelled' THEN CONCAT('Your order #', o.order_id, ' has been cancelled.')
                ELSE NULL
            END as title,
            o.order_date as notification_time,
            CASE 
                WHEN o.status = 'ready' AND o.notification_read = 0 THEN 1
                ELSE 0
            END as unread,
            o.pickup_schedule
        FROM orders o
        WHERE o.customer_id = ? 
        AND o.status IN ('ready', 'completed', 'cancelled')
        AND o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY o.order_date DESC
    ");
    
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format notifications
    $formatted_notifications = array_map(function($notification) {
        $message = $notification['title'];
        if ($notification['status'] === 'ready' && $notification['pickup_schedule']) {
            $message .= ' Scheduled for ' . date('M d, Y h:i A', strtotime($notification['pickup_schedule']));
        }
        return [
            'id' => $notification['order_id'],
            'title' => $message,
            'time' => date('M d, Y h:i A', strtotime($notification['notification_time'])),
            'unread' => (bool)$notification['unread'],
            'status' => $notification['status']
        ];
    }, $notifications);
    
    // Count unread notifications
    $unread_count = array_reduce($notifications, function($carry, $notification) {
        return $carry + ($notification['unread'] ? 1 : 0);
    }, 0);
    
    // Mark notifications as read
    if ($unread_count > 0) {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET notification_read = 1 
            WHERE customer_id = ? 
            AND status = 'ready' 
            AND notification_read = 0
        ");
        $stmt->execute([$user_id]);
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'notifications' => $formatted_notifications,
        'unread_count' => $unread_count
    ]);
    
} catch (PDOException $e) {
    error_log("Notification Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Notification Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} 