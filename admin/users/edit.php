<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

require_once '../../config/database.php';

// Check if user ID is provided
if (!isset($_GET['id'])) {
    header("Location: /admin/users/manage.php");
    exit();
}

$user_id = (int)$_GET['id'];

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: /admin/users/manage.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    
    // Validate inputs
    if (empty($username) || empty($email)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            // Check if username or email already exists (excluding current user)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM users 
                WHERE (username = ? OR email = ?) 
                AND user_id != ?
            ");
            $stmt->execute([$username, $email, $user_id]);
            
            if ($stmt->fetchColumn() > 0) {
                $error_message = 'Username or email already exists.';
            } else {
                // Update user
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, role = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$username, $email, $role, $user_id]);
                
                if ($stmt->rowCount() > 0) {
                    $success_message = 'User updated successfully!';
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                } else {
                    $error_message = 'No changes were made.';
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Failed to update user. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - UniMarket</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .edit-user-container {
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-submit {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        
        .btn-submit:hover {
            background-color: #218838;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        
        .alert-error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #6c757d;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-button:hover {
            color: #495057;
        }

        .back-button i {
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container edit-user-container">
        <a href="/admin/users/manage.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
        
        <h1>Edit User</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="card">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                    <option value="owner" <?php echo $user['role'] === 'owner' ? 'selected' : ''; ?>>Owner</option>
                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            
            <button type="submit" class="btn-submit">Update User</button>
        </form>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html> 
