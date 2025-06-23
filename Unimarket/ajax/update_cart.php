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
    echo json_encode(['success' => false, 'message' => 'Please log in to update cart']);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['cart_item_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$cart_item_id = (int)$_POST['cart_item_id'];
$quantity = (int)$_POST['quantity'];

// Log the received data
error_log("Received update request - Cart Item ID: $cart_item_id, Quantity: $quantity");

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
    exit();
}

try {
    // Get current cart item and product details
    $stmt = $pdo->prepare("
        SELECT ci.*, p.stock_quantity, p.price, p.product_id
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        JOIN cart c ON ci.cart_id = c.cart_id
        WHERE ci.cart_item_id = ? AND c.user_id = ?
    ");
    $stmt->execute([$cart_item_id, $_SESSION['user_id']]);
    $cart_item = $stmt->fetch();

    if (!$cart_item) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit();
    }

    // Log the cart item details
    error_log("Cart item found: " . print_r($cart_item, true));

    // Get current stock quantity
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
    $stmt->execute([$cart_item['product_id']]);
    $current_stock = $stmt->fetch()['stock_quantity'];

    // Check stock availability
    if ($quantity > $current_stock) {
        echo json_encode([
            'success' => false, 
            'message' => "Only {$current_stock} available in stock",
            'previous_quantity' => $cart_item['quantity'],
            'current_stock' => $current_stock
        ]);
        exit();
    }

    // Update quantity
    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
    $result = $stmt->execute([$quantity, $cart_item_id]);

    if (!$result) {
        throw new PDOException("Failed to update cart item");
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

    // Log success
    error_log("Cart updated successfully - New quantity: $quantity, Total: $cart_total");

    echo json_encode([
        'success' => true,
        'message' => 'Quantity updated successfully',
        'cart_total' => $cart_total,
        'current_stock' => $current_stock
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update quantity. Please try again.',
        'previous_quantity' => $cart_item['quantity'] ?? 1
    ]);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected error occurred. Please try again.',
        'previous_quantity' => $cart_item['quantity'] ?? 1
    ]);
} 