<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

// Check if user is logged in and is an admin
if (!isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <div class="content">
        <div class="header">
            <h1>Manage Products</h1>
        </div>

        <a href="../dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php
        // Get all products with owner information
        $stmt = $pdo->query("
            SELECT p.*, u.username as owner_name 
            FROM products p 
            JOIN users u ON p.owner_id = u.user_id 
            ORDER BY u.username, p.created_at DESC
        ");
        $products = $stmt->fetchAll();

        // Debug information
        echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 4px;'>";
        echo "<h3>Debug Information:</h3>";
        echo "<p>Total products found: " . count($products) . "</p>";
        
        // Group products by owner
        $grouped_products = [];
        foreach ($products as $product) {
            $owner_name = $product['owner_name'];
            if (!isset($grouped_products[$owner_name])) {
                $grouped_products[$owner_name] = [];
            }
            $grouped_products[$owner_name][] = $product;
        }
        
        echo "<p>Number of sellers: " . count($grouped_products) . "</p>";
        echo "<p>Sellers found: " . implode(", ", array_keys($grouped_products)) . "</p>";
        echo "</div>";
        ?>

        <?php foreach ($grouped_products as $owner_name => $owner_products): ?>
            <div class="seller-section">
                <h2 class="seller-name"><?php echo htmlspecialchars($owner_name); ?>'s Products</h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($owner_products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if (isset($product['image']) && !empty($product['image'])): ?>
                                            <img src="../../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumbnail">
                                        <?php else: ?>
                                            <div class="no-image">No Image</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td><?php echo isset($product['subcategory']) ? htmlspecialchars($product['subcategory']) : ''; ?></td>
                                    <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo isset($product['stock']) ? $product['stock'] : '0'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo isset($product['status']) ? $product['status'] : 'pending'; ?>">
                                            <?php echo isset($product['status']) ? ucfirst($product['status']) : 'Pending'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit.php?id=<?php echo $product['product_id']; ?>" class="btn btn-edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="delete.php?id=<?php echo $product['product_id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <style>
        .seller-section {
            margin-bottom: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }

        .seller-name {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #4CAF50;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .product-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }

        .no-image {
            width: 50px;
            height: 50px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            font-size: 0.8rem;
            color: #666;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-badge.active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-badge.inactive {
            background-color: #ffebee;
            color: #c62828;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn i {
            font-size: 14px;
        }

        .btn-primary {
            background-color: #28a745;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }

        .btn-edit {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            background-color: #218838;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background-color: #c82333;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
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
</body>
</html> 