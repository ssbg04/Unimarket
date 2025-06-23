<?php
require_once '../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

require_once '../config/database.php';

// Get total users count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$total_users = $stmt->fetchColumn();

// Get total products count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products");
$stmt->execute();
$total_products = $stmt->fetchColumn();

// Get total orders count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders");
$stmt->execute();
$total_orders = $stmt->fetchColumn();

// Get recent users
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY user_id DESC LIMIT 5");
$stmt->execute();
$recent_users = $stmt->fetchAll();

// Get recent orders
$stmt = $pdo->prepare("
    SELECT o.*, u.username as customer_name 
    FROM orders o 
    JOIN users u ON o.customer_id = u.user_id 
    ORDER BY o.order_date DESC 
    LIMIT 5
");
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// Get admin's products
$stmt = $pdo->prepare("SELECT * FROM products WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$admin_products = $stmt->fetchAll();

// Get admin information
$admin_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'admin'");
$admin_stmt->execute([$_SESSION['user_id']]);
$admin = $admin_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UniMarket</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .admin-dashboard {
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: var(--primary-dark);
        }
        
        .stat-card .number {
            font-size: 2em;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .recent-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .recent-section h2 {
            margin: 0 0 20px 0;
            color: var(--primary-dark);
        }
        
        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .admin-action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        
        .admin-action-btn:hover {
            background: var(--primary-dark);
        }
        
        .admin-action-btn i {
            margin-right: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .btn-small {
            display: inline-block;
            padding: 6px 12px;
            margin: 0 4px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-size: 0.9em;
        }
        
        .btn-edit {
            background-color: #4CAF50;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
        }
        
        .btn-edit:hover {
            background-color: #45a049;
        }
        
        .btn-edit i {
            font-size: 0.9em;
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-left: 8px;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }
        
        .btn-delete i {
            font-size: 0.9em;
        }
        
        .edit-product {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 150px;
            resize: vertical;
        }
        
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-submit:hover {
            background-color: #45a049;
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
        
        .image-preview {
            max-width: 200px;
            margin-top: 10px;
            display: none;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .btn-add {
            background-color: #4CAF50;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-add:hover {
            background-color: #45a049;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 16px;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 16px;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary:hover {
            background-color: #45a049;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #dff0d8;
            color: #3c763d;
        }
        
        .status-inactive {
            background-color: #f2dede;
            color: #a94442;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container admin-dashboard">
        <h1>Admin Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?php echo $total_users; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="number"><?php echo $total_products; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="number"><?php echo $total_orders; ?></div>
            </div>
        </div>
        
        <div class="admin-actions">
            <a href="/admin/users/manage.php" class="admin-action-btn">
                <i class="fas fa-users"></i>
                <span>Manage Users</span>
            </a>
            <a href="/admin/products/index.php" class="admin-action-btn">
                <i class="fas fa-box"></i>
                <span>Manage Products</span>
            </a>
            <a href="/admin/orders/manage.php" class="admin-action-btn">
                <i class="fas fa-shopping-cart"></i> Manage Orders
            </a>
            <a href="/admin/products/add.php" class="admin-action-btn">
                <i class="fas fa-plus"></i> Add My Product
            </a>
        </div>
        
        <div class="recent-section">
            <div class="section-header">
                <h2>My Products</h2>
                <a href="/admin/products/add.php" class="btn-add">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            </div>
            <?php if (empty($admin_products)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>You haven't added any products yet.</p>
                    <a href="/admin/products/add.php" class="btn-primary">Add Your First Product</a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admin_products as $product): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($product['image_path'])): ?>
                                        <img src="/uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>" alt="Product" class="product-image">
                                    <?php else: ?>
                                        <img src="/assets/images/no-image.png" alt="No Image" class="product-image">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock_quantity']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $product['stock_quantity'] > 0 ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $product['stock_quantity'] > 0 ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="/admin/products/delete.php?id=<?php echo $product['product_id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="recent-section">
            <h2>Recent Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td>
                            <a href="/admin/users/edit.php?id=<?php echo $user['user_id']; ?>" class="btn-small btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="recent-section">
            <h2>Recent Orders</h2>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['order_id']; ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><?php echo ucfirst($order['status']); ?></td>
                        <td>
                            <a href="/admin/orders/view.php?id=<?php echo $order['order_id']; ?>" class="btn-small btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html> 
