<?php
require_once '../includes/auth_functions.php';
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper content type
header('Content-Type: application/json');

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to remove items from cart']);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['cart_item_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$cart_item_id = (int)$_POST['cart_item_id'];

try {
    // Verify the cart item belongs to the user
    $stmt = $pdo->prepare("
        SELECT ci.* 
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.cart_id
        WHERE ci.cart_item_id = ? AND c.user_id = ?
    ");
    $stmt->execute([$cart_item_id, $_SESSION['user_id']]);
    $cart_item = $stmt->fetch();

    if (!$cart_item) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit();
    }

    // Delete the cart item
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
    $result = $stmt->execute([$cart_item_id]);

    if (!$result) {
        throw new PDOException("Failed to remove cart item");
    }

    // Get updated cart total
    $stmt = $pdo->prepare("
        SELECT SUM(ci.quantity * p.price) as total
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        JOIN cart c ON ci.cart_id = c.cart_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_total = $stmt->fetch()['total'] ?? 0;

    // Get updated cart count
    $stmt = $pdo->prepare("
        SELECT SUM(quantity) as count 
        FROM cart_items ci 
        JOIN cart c ON ci.cart_id = c.cart_id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetch()['count'] ?? 0;

    echo json_encode([
        'success' => true,
        'message' => 'Item removed from cart',
        'total' => $cart_total,
        'cart_count' => $cart_count
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to remove item from cart. Please try again.'
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred. Please try again.'
    ]);
} 