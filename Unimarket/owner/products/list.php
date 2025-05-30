<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotOwner();

require_once '../../config/database.php';

// Get all products for this owner
$stmt = $pdo->prepare("SELECT * FROM products WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - UniMarket</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .products-container {
            margin-top: 30px;
        }
        
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .products-table th, .products-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .products-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .product-cell {
            display: flex;
            align-items: center;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            margin-right: 15px;
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-image img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .stock-low {
            color: #e53935;
        }
        
        .action-link {
            color: var(--primary-color);
            text-decoration: none;
            margin-right: 10px;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .empty-products {
            text-align: center;
            padding: 50px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .products-table {
                display: block;
                overflow-x: auto;
            }
            
            .product-cell {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .product-image {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container products-container">
        <div class="products-header">
            <h1>My Products</h1>
            <a href="/unimarket/owner/products/add.php" class="btn">
                <i class="fas fa-plus"></i> Add Product
            </a>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="empty-products card">
                <i class="fas fa-box-open" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
                <h3>You don't have any products yet</h3>
                <p>Add your first product to start selling</p>
                <a href="/unimarket/owner/products/add.php" class="btn">Add Product</a>
            </div>
        <?php else: ?>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <div class="product-image">
                                        <?php if ($product['image_path']): ?>
                                            <img src="/unimarket/assets/images/products/<?php echo $product['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-box-open" style="color: #ccc;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                        <p><?php echo substr(htmlspecialchars($product['description']), 0, 50); ?>...</p>
                                    </div>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($product['price'], 2); ?></td>
                            <td class="<?php echo $product['stock_quantity'] < 5 ? 'stock-low' : ''; ?>">
                                <?php echo $product['stock_quantity']; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td>
                                <a href="/unimarket/owner/products/edit.php?id=<?php echo $product['product_id']; ?>" class="action-link">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="/unimarket/owner/products/delete.php?id=<?php echo $product['product_id']; ?>" class="action-link" onclick="return confirm('Are you sure you want to delete this product?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>