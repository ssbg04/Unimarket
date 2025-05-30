<?php
require_once '../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotCustomer();

require_once '../config/database.php';

// Initialize variables
$success_message = '';
$error_message = '';
$user = [];
$orders = [];

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get recent orders
$stmt = $pdo->prepare("
    SELECT o.order_id, o.order_date, o.total_amount, o.status, o.pickup_schedule 
    FROM orders o 
    WHERE o.customer_id = ? 
    ORDER BY o.order_date DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $university_id = trim($_POST['university_id']);

    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email) || empty($university_id)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            
            if ($stmt->fetch()) {
                $error_message = 'This email is already registered to another account.';
            } else {
                // Update profile
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, university_id = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $first_name,
                    $last_name,
                    $email,
                    $phone,
                    $university_id,
                    $_SESSION['user_id']
                ]);
                
                $success_message = 'Profile updated successfully!';
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error_message = 'An error occurred while updating your profile. Please try again.';
        }
    }
}

// Handle password changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password)) {
        $error_message = 'Please enter your current password.';
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error_message = 'Please enter and confirm your new password.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $error_message = 'Password must be at least 8 characters long.';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $db_password = $stmt->fetchColumn();
        
        if (password_verify($current_password, $db_password)) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            
            $success_message = 'Password changed successfully!';
        } else {
            $error_message = 'Current password is incorrect.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - UniMarket</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        @media (min-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .profile-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 10px;
        }
        
        .profile-info {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
        }
        
        .profile-details h3 {
            margin-bottom: 5px;
            font-size: 1.3rem;
        }
        
        .profile-details p {
            color: #666;
            margin-bottom: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .btn-update {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        
        .btn-update:hover {
            background-color: var(--primary-dark);
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .orders-table th, .orders-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .orders-table th {
            background-color: #f5f5f5;
        }
        
        .order-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        #orders-ids{
            text-decoration: none;
        }

        #orders-ids:hover{
            color: green;
            text-decoration: underline;
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
        
        .view-all {
            display: block;
            text-align: right;
            margin-top: 15px;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        .password-strength {
            height: 5px;
            background-color: #eee;
            margin-top: 5px;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            background-color: #ff0000;
            transition: width 0.3s, background-color 0.3s;
        }
        
        @media (max-width: 768px) {
            .profile-info {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .profile-avatar {
                margin: 0 auto;
            }
            
            .orders-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>My Profile</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="profile-container">
            <div class="profile-section">
                <h2 class="section-title">Profile Information</h2>
                
                <div class="profile-info">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-details">
                        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . htmlspecialchars($user['last_name'])); ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                        <p>Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="university_id">University ID</label>
                        <input type="text" id="university_id" name="university_id" value="<?php echo htmlspecialchars($user['university_id']); ?>" required>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn-update">Update Profile</button>
                </form>
            </div>
            
            <div class="profile-section">
                <h2 class="section-title">Change Password</h2>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn-update">Change Password</button>
                </form>
                
                <h2 class="section-title" style="margin-top: 40px;">Recent Orders</h2>
                
                <?php if (empty($orders)): ?>
                    <p>You haven't placed any orders yet.</p>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><a id="orders-ids" href="/unimarket/customer/orders/view.php?order_id=<?php echo $order['order_id']; ?>">#<?php echo $order['order_id']; ?></a></td>
                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        switch ($order['status']) {
                                            case 'pending':
                                                $status_class = 'status-pending';
                                                break;
                                            case 'processing':
                                                $status_class = 'status-processing';
                                                break;
                                            case 'ready_for_pickup':
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
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="/unimarket/customer/orders/list.php" class="view-all">View all orders →</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('new_password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            
            const width = strength * 20;
            let color;
            
            if (strength <= 1) color = '#ff0000';
            else if (strength <= 3) color = '#ff9900';
            else color = '#00aa00';
            
            strengthBar.style.width = width + '%';
            strengthBar.style.backgroundColor = color;
        });
        
        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = this.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('error');
                        
                        // Create or show error message
                        let errorMsg = field.nextElementSibling;
                        if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                            errorMsg = document.createElement('small');
                            errorMsg.className = 'error-message';
                            errorMsg.textContent = 'This field is required';
                            field.parentNode.insertBefore(errorMsg, field.nextSibling);
                        }
                    } else {
                        field.classList.remove('error');
                        const errorMsg = field.nextElementSibling;
                        if (errorMsg && errorMsg.classList.contains('error-message')) {
                            errorMsg.remove();
                        }
                    }
                });
                
                // Password confirmation check
                if (this.querySelector('#new_password') && this.querySelector('#confirm_password')) {
                    const newPassword = this.querySelector('#new_password').value;
                    const confirmPassword = this.querySelector('#confirm_password').value;
                    
                    if (newPassword !== confirmPassword) {
                        isValid = false;
                        const confirmField = this.querySelector('#confirm_password');
                        confirmField.classList.add('error');
                        
                        let errorMsg = confirmField.nextElementSibling;
                        if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                            errorMsg = document.createElement('small');
                            errorMsg.className = 'error-message';
                            errorMsg.textContent = 'Passwords do not match';
                            confirmField.parentNode.insertBefore(errorMsg, confirmField.nextSibling);
                        }
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    
                    // Scroll to first error
                    const firstError = this.querySelector('.error');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        });
    </script>
</body>
</html>