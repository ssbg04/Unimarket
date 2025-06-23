<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

require_once '../../config/database.php';

// Handle user role updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];
    
    // Prevent changing own role
    if ($user_id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->execute([$new_role, $user_id]);
        flashMessage('success', 'User role updated successfully.');
    } else {
        flashMessage('error', 'You cannot change your own role.');
    }
    
    header("Location: manage.php");
    exit();
}

// Get all users
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY user_id DESC");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - UniMarket Admin</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .manage-users {
            padding: 20px;
        }
        
        .users-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .users-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th,
        .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .users-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .users-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .role-select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .btn-small {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            margin-right: 5px;
        }
        
        .btn-update {
            background: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
        }
        
        .btn-update:hover {
            background: var(--primary-dark);
        }
        
        .btn-edit {
            background-color: #28a745;
            color: white;
            border: none;
        }
        
        .btn-edit:hover {
            background-color: #218838;
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .role-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .role-customer {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .role-owner {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .role-admin {
            background-color: #d4edda;
            color: #155724;
        }
        
        .btn-edit.disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.65;
        }
        
        .btn-edit.disabled:hover {
            background-color: #6c757d;
            transform: none;
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
    
    <div class="container manage-users">
        <a href="/unimarket/admin/dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <h1>Manage Users</h1>
        
        <?php if ($message = getFlashMessage('success')): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($message = getFlashMessage('error')): ?>
            <div class="alert alert-error"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php
                            $role_class = 'role-' . $user['role'];
                            $role_display = ucfirst($user['role']);
                            ?>
                            <span class="role-badge <?php echo $role_class; ?>">
                                <?php echo $role_display; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="btn-small btn-edit disabled">
                                    <i class="fas fa-edit"></i> Edit
                                </span>
                            <?php else: ?>
                                <a href="edit.php?id=<?php echo $user['user_id']; ?>" class="btn-small btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            <?php endif; ?>
                            <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                <a href="delete.php?id=<?php echo $user['user_id']; ?>" class="btn-small btn-delete" onclick="return confirm('Are you sure you want to delete this user?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html> 