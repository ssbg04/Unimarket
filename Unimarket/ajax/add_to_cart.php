<?php
require_once '../includes/auth_functions.php';
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isCustomer()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart']);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];
$size = isset($_POST['size']) ? $_POST['size'] : null;

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
    exit();
}

try {
    // Check product availability
    $stmt = $pdo->prepare("SELECT stock_quantity, category, size_data FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    // Check if size is required for clothing items
    if ($product['category'] === 'clothes' && empty($size)) {
        echo json_encode(['success' => false, 'message' => 'Please select a size']);
        exit();
    }

    // Validate size availability for clothing items
    if ($product['category'] === 'clothes' && $product['size_data']) {
        $size_data = json_decode($product['size_data'], true);
        if (!isset($size_data[$size]) || $size_data[$size] < $quantity) {
            echo json_encode(['success' => false, 'message' => "Selected size is not available in the requested quantity"]);
            exit();
        }
    } else if ($quantity > $product['stock_quantity']) {
        echo json_encode(['success' => false, 'message' => "Only {$product['stock_quantity']} available in stock"]);
        exit();
    }

    // Get or create cart for user
    $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart = $stmt->fetch();

    if (!$cart) {
        $stmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_id = $pdo->lastInsertId();
    } else {
        $cart_id = $cart['cart_id'];
    }

    // Check if product already in cart
    $stmt = $pdo->prepare("SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$cart_id, $product_id]);
    $existing_item = $stmt->fetch();

    if ($existing_item) {
        // Update quantity if already in cart
        $new_quantity = $existing_item['quantity'] + $quantity;
        if ($product['category'] === 'clothes' && $product['size_data']) {
            $size_data = json_decode($product['size_data'], true);
            if ($new_quantity > $size_data[$size]) {
                echo json_encode(['success' => false, 'message' => "Cannot add more than available stock for selected size"]);
                exit();
            }
        } else if ($new_quantity > $product['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => "Cannot add more than available stock"]);
            exit();
        }
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
        $stmt->execute([$new_quantity, $existing_item['cart_item_id']]);
    } else {
        // Add new item to cart
        try {
            $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, size) VALUES (?, ?, ?, ?)");
            $stmt->execute([$cart_id, $product_id, $quantity, $size]);
        } catch (PDOException $e) {
            // If the size column doesn't exist, try without it
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$cart_id, $product_id, $quantity]);
            } else {
                throw $e;
            }
        }
    }

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
        'message' => 'Product added to cart!',
        'cart_count' => $cart_count
    ]);

} catch (PDOException $e) {
    error_log("Add to cart error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to add product to cart. Please try again.',
        'debug' => $e->getMessage()
    ]);
} 