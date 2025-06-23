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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
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
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-update::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .btn-update:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
        }

        .btn-update:hover::after {
            transform: translateX(100%);
        }

        .btn-secondary {
            background-color: #757575;
        }

        .btn-secondary:hover {
            background-color: #616161;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .profile-field {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }

        .profile-field:hover {
            background: rgba(255, 255, 255, 0.8);
        }

        .field-label {
            font-weight: 500;
            min-width: 120px;
            color: #666;
        }

        .field-value {
            flex: 1;
        }

        .edit-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .edit-btn:hover {
            background: rgba(46, 125, 50, 0.1);
            transform: scale(1.1);
        }

        .edit-btn i {
            font-size: 1rem;
        }

        .field-input {
            display: none;
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .field-input.active {
            display: block;
        }

        .field-value.hidden {
            display: none;
        }

        .save-btn {
            display: none;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .save-btn.active {
            display: inline-block;
        }

        .save-btn:hover {
            background: var(--primary-dark);
        }

        .view-all-orders {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .view-all-orders::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .view-all-orders:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
        }

        .view-all-orders:hover::after {
            transform: translateX(100%);
        }

        /* Add update profile modal styles */
        .update-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .update-modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .update-modal h2 {
            margin-bottom: 20px;
            color: var(--primary-dark);
        }

        .update-modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }

        .update-modal-buttons button {
            padding: 10px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .update-modal-buttons .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }

        .update-modal-buttons .btn-cancel:hover {
            background: #d0d0d0;
        }

        .update-modal-buttons .btn-confirm {
            background: var(--primary-color);
            color: white;
        }

        .update-modal-buttons .btn-confirm:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <!-- Add update profile modal HTML -->
    <div class="update-modal" id="updateModal">
        <div class="update-modal-content">
            <h2>Confirm Profile Update</h2>
            <p>Are you sure you want to update your profile information?</p>
            <div class="update-modal-buttons">
                <button class="btn-cancel" onclick="closeUpdateModal()">Cancel</button>
                <button class="btn-confirm" onclick="confirmUpdate()">Update Profile</button>
            </div>
        </div>
    </div>

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
                
                <form method="POST" id="profileForm">
                    <div class="profile-field">
                        <span class="field-label">First Name</span>
                        <span class="field-value"><?php echo htmlspecialchars($user['first_name']); ?></span>
                        <input type="text" name="first_name" class="field-input" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        <button type="button" class="edit-btn" onclick="toggleEdit(this)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="save-btn" onclick="saveField(this)">Save</button>
                    </div>
                    
                    <div class="profile-field">
                        <span class="field-label">Last Name</span>
                        <span class="field-value"><?php echo htmlspecialchars($user['last_name']); ?></span>
                        <input type="text" name="last_name" class="field-input" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        <button type="button" class="edit-btn" onclick="toggleEdit(this)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="save-btn" onclick="saveField(this)">Save</button>
                    </div>
                    
                    <div class="profile-field">
                        <span class="field-label">Email</span>
                        <span class="field-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        <input type="email" name="email" class="field-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        <button type="button" class="edit-btn" onclick="toggleEdit(this)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="save-btn" onclick="saveField(this)">Save</button>
                    </div>
                    
                    <div class="profile-field">
                        <span class="field-label">Phone Number</span>
                        <span class="field-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                        <input type="tel" name="phone" class="field-input" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        <button type="button" class="edit-btn" onclick="toggleEdit(this)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="save-btn" onclick="saveField(this)">Save</button>
                    </div>
                    
                    <div class="profile-field">
                        <span class="field-label">University ID</span>
                        <span class="field-value"><?php echo htmlspecialchars($user['university_id']); ?></span>
                        <input type="text" name="university_id" class="field-input" value="<?php echo htmlspecialchars($user['university_id']); ?>" required>
                        <button type="button" class="edit-btn" onclick="toggleEdit(this)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="save-btn" onclick="saveField(this)">Save</button>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn-update" onclick="openUpdateModal()">Update Profile</button>
                        <button type="button" class="btn-update btn-secondary" onclick="openPasswordModal()">Change Password</button>
                    </div>
                </form>
            </div>
            
            <div class="profile-section">
                <h2 class="section-title">Recent Orders</h2>
                
                <?php if (empty($orders)): ?>
                    <p>You haven't placed any orders yet.</p>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Pickup Schedule</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><a href="order_details.php?id=<?php echo $order['order_id']; ?>" id="orders-ids">#<?php echo $order['order_id']; ?></a></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($order['pickup_schedule'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="/customer/orders/list.php" class="view-all-orders">
                        <i class="fas fa-list"></i> View All Orders
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Change Password</h2>
            <form method="POST" id="passwordForm">
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
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('passwordModal');
        const closeBtn = document.getElementsByClassName('close')[0];

        function openPasswordModal() {
            modal.style.display = "block";
        }

        closeBtn.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Profile field editing functionality
        function toggleEdit(button) {
            const field = button.closest('.profile-field');
            const value = field.querySelector('.field-value');
            const input = field.querySelector('.field-input');
            const saveBtn = field.querySelector('.save-btn');
            
            value.classList.add('hidden');
            input.classList.add('active');
            saveBtn.classList.add('active');
            button.style.display = 'none';
        }

        function saveField(button) {
            const field = button.closest('.profile-field');
            const value = field.querySelector('.field-value');
            const input = field.querySelector('.field-input');
            const editBtn = field.querySelector('.edit-btn');
            
            // Update the displayed value
            value.textContent = input.value;
            
            // Hide input and save button, show value and edit button
            value.classList.remove('hidden');
            input.classList.remove('active');
            button.classList.remove('active');
            editBtn.style.display = 'flex';
        }

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
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });

        // Add update profile modal functions
        function openUpdateModal() {
            document.getElementById('updateModal').style.display = 'flex';
        }

        function closeUpdateModal() {
            document.getElementById('updateModal').style.display = 'none';
        }

        function confirmUpdate() {
            document.getElementById('profileForm').submit();
        }

        // Close modal when clicking outside
        document.getElementById('updateModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUpdateModal();
            }
        });
    </script>
</body>
</html>
