<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

require_once '../../config/database.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$product_id = $_GET['id'];

// Get product details to check ownership and get image path
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? AND owner_id = ?");
$stmt->execute([$product_id, $_SESSION['user_id']]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: /admin/dashboard.php');
    exit;
}

try {
    // Delete product image if exists
    if (!empty($product['image_path'])) {
        $image_path = '../../assets/images/products/' . $product['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Delete product from database
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ? AND owner_id = ?");
    $stmt->execute([$product_id, $_SESSION['user_id']]);
    
    // Redirect back to dashboard with success message
    header('Location: /admin/dashboard.php?message=Product deleted successfully');
    exit;
} catch (PDOException $e) {
    // Redirect back to dashboard with error message
    header('Location: /admin/dashboard.php?error=Failed to delete product');
    exit;
} 
