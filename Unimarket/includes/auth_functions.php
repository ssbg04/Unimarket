<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Check if a user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the logged in user is an owner
 * @return bool True if user is an owner, false otherwise
 */
function isOwner() {
    return isLoggedIn() && $_SESSION['role'] === 'owner';
}

/**
 * Check if the logged in user is a customer
 * @return bool True if user is a customer, false otherwise
 */
function isCustomer() {
    return isLoggedIn() && $_SESSION['role'] === 'customer';
}

/**
 * Redirect to login page if not logged in
 */
function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: /unimarket/auth/login.php");
        exit();
    }
}

/**
 * Redirect to homepage if not an owner
 */
function redirectIfNotOwner() {
    if (!isOwner()) {
        header("Location: /unimarket/index.php");
        exit();
    }
}

/**
 * Redirect to homepage if not a customer
 */
function redirectIfNotCustomer() {
    if (!isCustomer()) {
        header("Location: /unimarket/index.php");
        exit();
    }
}

/**
 * Get the current user's ID
 * @return int|null User ID if logged in, null otherwise
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the current user's role
 * @return string|null User role if logged in, null otherwise
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get the current user's username
 * @return string|null Username if logged in, null otherwise
 */
function getUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Verify if the provided password matches the user's password
 * @param int $user_id User ID to verify
 * @param string $password Password to verify
 * @return bool True if password matches, false otherwise
 */
function verifyPassword($user_id, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        return $user && password_verify($password, $user['password']);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Update user's password
 * @param int $user_id User ID
 * @param string $new_password New password
 * @return bool True if update was successful, false otherwise
 */
function updatePassword($user_id, $new_password) {
    global $pdo;
    
    try {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        return $stmt->execute([$hashed_password, $user_id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Generate a CSRF token and store it in session
 * @return string Generated CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token
 * @param string $token Token to verify
 * @return bool True if token is valid, false otherwise
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if the current user owns a specific product
 * @param int $product_id Product ID to check
 * @return bool True if user owns the product, false otherwise
 */
function ownsProduct($product_id) {
    if (!isOwner()) return false;
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ? AND owner_id = ?");
        $stmt->execute([$product_id, getUserId()]);
        return (bool)$stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check if the current user placed a specific order
 * @param int $order_id Order ID to check
 * @return bool True if user placed the order, false otherwise
 */
function placedOrder($order_id) {
    if (!isCustomer()) return false;
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT order_id FROM orders WHERE order_id = ? AND customer_id = ?");
        $stmt->execute([$order_id, getUserId()]);
        return (bool)$stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Flash a message to display on the next page load
 * @param string $key Message key
 * @param string $message Message content
 */
function flashMessage($key, $message) {
    $_SESSION['flash_messages'][$key] = $message;
}

/**
 * Get and clear a flashed message
 * @param string $key Message key
 * @return string|null Message content if exists, null otherwise
 */
function getFlashMessage($key) {
    if (isset($_SESSION['flash_messages'][$key])) {
        $message = $_SESSION['flash_messages'][$key];
        unset($_SESSION['flash_messages'][$key]);
        return $message;
    }
    return null;
}

/**
 * Initialize a user session after login
 * @param array $user User data array (must contain user_id, username, and role)
 */
function initUserSession($user) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    
    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);
}

/**
 * Destroy the current user session
 */
function destroySession() {
    // Unset all session variables
    $_SESSION = array();

    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();
}