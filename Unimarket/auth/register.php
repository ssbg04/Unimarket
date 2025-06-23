<?php
require_once '../includes/auth_functions.php';

if (isLoggedIn()) {
    header("Location: /unimarket/index.php");
    exit();
}

require_once '../config/database.php';

$errors = [];
$success = false;

// Form fields
$fields = [
    'username' => '',
    'email' => '',
    'first_name' => '',
    'last_name' => '',
    'university_id' => '',
    'phone' => '',
    'role' => 'customer' // Default role
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $fields['username'] = trim($_POST['username']);
    $fields['email'] = trim($_POST['email']);
    $fields['first_name'] = trim($_POST['first_name']);
    $fields['last_name'] = trim($_POST['last_name']);
    $fields['university_id'] = trim($_POST['university_id']);
    $fields['phone'] = trim($_POST['phone']);
    $fields['role'] = $_POST['role'] === 'owner' ? 'owner' : 'customer';
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate inputs
    if (empty($fields['username'])) {
        $errors['username'] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $fields['username'])) {
        $errors['username'] = 'Username must be 4-20 characters (letters, numbers, underscores).';
    }

    if (empty($fields['email'])) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (empty($fields['first_name'])) {
        $errors['first_name'] = 'First name is required.';
    }

    if (empty($fields['last_name'])) {
        $errors['last_name'] = 'Last name is required.';
    }

    if (empty($fields['university_id'])) {
        $errors['university_id'] = 'University ID is required.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // Check if username or email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$fields['username'], $fields['email']]);
        if ($stmt->fetch()) {
            $errors['general'] = 'Username or email already exists.';
        }
    }

    // If no errors, create user
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users 
                                 (username, password, email, first_name, last_name, university_id, phone, role) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $fields['username'],
                $hashed_password,
                $fields['email'],
                $fields['first_name'],
                $fields['last_name'],
                $fields['university_id'],
                $fields['phone'],
                $fields['role']
            ]);
            
            // Create a cart for customers
            if ($fields['role'] === 'customer') {
                $user_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
                $stmt->execute([$user_id]);
            }
            
            $success = true;
        } catch (PDOException $e) {
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UniMarket</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <style>
        .role-selector {
            display: flex;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .role-option {
            flex: 1;
            text-align: center;
            padding: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .role-option input {
            display: none;
        }
        
        .role-option label {
            display: block;
            cursor: pointer;
        }
        
        .role-option:hover {
            background-color: #f5f5f5;
        }
        
        .role-option.active {
            background-color: var(--primary-color);
            color: white;
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
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="card" style="max-width: 600px; margin: 50px auto;">
            <h1 style="text-align: center; margin-bottom: 20px;">Create Your Account</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Registration successful! You can now <a href="/unimarket/auth/login.php">login</a>.
                </div>
            <?php else: ?>
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-error"><?php echo $errors['general']; ?></div>
                <?php endif; ?>
                
                <form action="" method="POST" id="registrationForm">
                    <div class="role-selector">
                        <div class="role-option <?php echo $fields['role'] === 'customer' ? 'active' : ''; ?>">
                            <input type="radio" id="role_customer" name="role" value="customer" <?php echo $fields['role'] === 'customer' ? 'checked' : ''; ?>>
                            <label for="role_customer">I'm a Student</label>
                        </div>
                        <div class="role-option <?php echo $fields['role'] === 'owner' ? 'active' : ''; ?>">
                            <input type="radio" id="role_owner" name="role" value="owner" <?php echo $fields['role'] === 'owner' ? 'checked' : ''; ?>>
                            <label for="role_owner">I'm a Vendor</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username*</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($fields['username']); ?>" required>
                        <?php if (isset($errors['username'])): ?>
                            <small style="color: var(--error-color);"><?php echo $errors['username']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email*</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($fields['email']); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <small style="color: var(--error-color);"><?php echo $errors['email']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">First Name*</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($fields['first_name']); ?>" required>
                        <?php if (isset($errors['first_name'])): ?>
                            <small style="color: var(--error-color);"><?php echo $errors['first_name']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name*</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($fields['last_name']); ?>" required>
                        <?php if (isset($errors['last_name'])): ?>
                            <small style="color: var(--error-color);"><?php echo $errors['last_name']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="university_id">University ID*</label>
                        <input type="text" id="university_id" name="university_id" value="<?php echo htmlspecialchars($fields['university_id']); ?>" required>
                        <?php if (isset($errors['university_id'])): ?>
                            <small style="color: var(--error-color);"><?php echo $errors['university_id']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($fields['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password*</label>
                        <input type="password" id="password" name="password" required>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <small style="color: var(--error-color);"><?php echo $errors['password']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password*</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <small style="color: var(--error-color);"><?php echo $errors['confirm_password']; ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%;">Register</button>
                    
                    <p style="text-align: center; margin-top: 20px;">
                        Already have an account? <a href="/unimarket/auth/login.php">Login here</a>
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Role selector styling
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.role-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                this.classList.add('active');
                this.querySelector('input').checked = true;
            });
        });
        
        // Password strength indicator
        const passwordInput = document.getElementById('password');
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
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>