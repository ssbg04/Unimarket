<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotOwner();

require_once '../../config/database.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header("Location: /owner/products/list.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Verify product belongs to current owner
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? AND owner_id = ?");
$stmt->execute([$product_id, $_SESSION['user_id']]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: /owner/products/list.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $category = trim($_POST['category']);
    $stock_quantity = (int)$_POST['stock_quantity'];
    
    // Get size data if category is clothes
    $size_data = null;
    if ($category === 'clothes' && isset($_POST['sizes']) && isset($_POST['size_quantities'])) {
        $sizes = $_POST['sizes'];
        $quantities = $_POST['size_quantities'];
        $size_data = [];
        
        for ($i = 0; $i < count($sizes); $i++) {
            if (!empty($sizes[$i]) && isset($quantities[$i]) && $quantities[$i] > 0) {
                $size_data[$sizes[$i]] = (int)$quantities[$i];
            }
        }
        
        // Update stock quantity based on sizes
        $stock_quantity = array_sum($size_data);
    }
    
    // Validate inputs
    if (empty($name) || empty($description) || empty($price) || empty($stock_quantity)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!is_numeric($price) || $price <= 0) {
        $error_message = 'Please enter a valid price.';
    } elseif ($stock_quantity < 0) {
        $error_message = 'Stock quantity cannot be negative.';
    } elseif ($category === 'clothes' && empty($size_data)) {
        $error_message = 'Please add at least one size with quantity.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Handle file upload if new image is provided
            $image_path = $product['image_path'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/images/products/';
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $file_extension;
                $destination = $upload_dir . $filename;
                
                // Validate image
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array(strtolower($file_extension), $allowed_types)) {
                    $error_message = 'Only JPG, JPEG, PNG, and GIF files are allowed.';
                } elseif ($_FILES['image']['size'] > 5000000) { // 5MB max
                    $error_message = 'File size must be less than 5MB.';
                } else {
                    // Delete old image if it exists
                    if ($image_path && file_exists($upload_dir . $image_path)) {
                        unlink($upload_dir . $image_path);
                    }
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                        $image_path = $filename;
                    } else {
                        $error_message = 'Failed to upload image.';
                    }
                }
            }
            
            if (empty($error_message)) {
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET name = ?, description = ?, price = ?, category = ?, stock_quantity = ?, image_path = ?, size_data = ?
                    WHERE product_id = ? AND owner_id = ?
                ");
                $stmt->execute([
                    $name,
                    $description,
                    $price,
                    $category,
                    $stock_quantity,
                    $image_path,
                    $size_data ? json_encode($size_data) : null,
                    $product_id,
                    $_SESSION['user_id']
                ]);
                
                if ($stmt->rowCount() > 0) {
                    $pdo->commit();
                    $success_message = 'Product updated successfully!';
                    
                    // Refresh product data
                    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
                    $stmt->execute([$product_id]);
                    $product = $stmt->fetch();
                } else {
                    throw new Exception('No changes were made to the product.');
                }
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = 'Failed to update product: ' . $e->getMessage();
        }
    }
}

// Decode size data if exists
$size_data = null;
if (isset($product['size_data']) && $product['size_data']) {
    $size_data = json_decode($product['size_data'], true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - UniMarket</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .edit-product-container {
            margin-top: 30px;
        }
        
        .image-upload {
            margin-bottom: 20px;
        }
        
        .image-preview {
            width: 200px;
            height: 200px;
            background-color: #f5f5f5;
            border: 1px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .image-preview.has-image {
            border: none;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .default-text {
            color: #999;
            text-align: center;
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input, 
        .form-group textarea, 
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 100px;
        }
        
        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        
        .btn-submit:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
            margin-left: 10px;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
        }
        
        .button-group {
            display: flex;
            justify-content: space-between;
        }
        
        .size-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .size-select, .size-quantity {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .size-select {
            width: 100px;
        }
        
        .size-quantity {
            width: 100px;
        }
        
        .btn-remove-size {
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 4px;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-add-size {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .btn-add-size:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container edit-product-container">
        <h1>Edit Product</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="card">
            <div class="form-group image-upload">
                <label>Product Image</label>
                <input type="file" name="image" id="productImage" accept="image/*">
                <div class="image-preview" id="imagePreview">
                    <?php if ($product['image_path']): ?>
                        <img src="/assets/images/products/<?php echo $product['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <div class="default-text">No image selected</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="name">Product Name*</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description*</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="price">Price*</label>
                <input type="number" id="price" name="price" step="0.01" min="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" onchange="updateSubcategories()">
                    <option value="">Select a category</option>
                    <option value="clothes" <?php echo $product['category'] === 'clothes' ? 'selected' : ''; ?>>Clothes</option>
                    <option value="school_supplies" <?php echo $product['category'] === 'school_supplies' ? 'selected' : ''; ?>>School Supplies</option>
                    <option value="others" <?php echo $product['category'] === 'others' ? 'selected' : ''; ?>>Others</option>
                </select>
            </div>
            
            <div class="form-group" id="sizeGroup" style="display: <?php echo isset($product['category']) && $product['category'] === 'clothes' ? 'block' : 'none'; ?>;">
                <label>Sizes and Quantities*</label>
                <div id="sizeContainer">
                    <?php if ($size_data && is_array($size_data)): ?>
                        <?php foreach ($size_data as $size => $quantity): ?>
                            <div class="size-row">
                                <select name="sizes[]" class="size-select">
                                    <option value="">Select Size</option>
                                    <option value="XS" <?php echo $size === 'XS' ? 'selected' : ''; ?>>XS</option>
                                    <option value="S" <?php echo $size === 'S' ? 'selected' : ''; ?>>S</option>
                                    <option value="M" <?php echo $size === 'M' ? 'selected' : ''; ?>>M</option>
                                    <option value="L" <?php echo $size === 'L' ? 'selected' : ''; ?>>L</option>
                                    <option value="XL" <?php echo $size === 'XL' ? 'selected' : ''; ?>>XL</option>
                                    <option value="XXL" <?php echo $size === 'XXL' ? 'selected' : ''; ?>>XXL</option>
                                </select>
                                <input type="number" name="size_quantities[]" min="0" placeholder="Quantity" class="size-quantity" value="<?php echo $quantity; ?>">
                                <button type="button" class="btn-remove-size" onclick="removeSizeRow(this)">×</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="size-row">
                            <select name="sizes[]" class="size-select">
                                <option value="">Select Size</option>
                                <option value="XS">XS</option>
                                <option value="S">S</option>
                                <option value="M">M</option>
                                <option value="L">L</option>
                                <option value="XL">XL</option>
                                <option value="XXL">XXL</option>
                            </select>
                            <input type="number" name="size_quantities[]" min="0" placeholder="Quantity" class="size-quantity">
                            <button type="button" class="btn-remove-size" onclick="removeSizeRow(this)">×</button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-add-size" onclick="addSizeRow()">Add Another Size</button>
            </div>
            
            <div class="form-group">
                <label for="stock_quantity">Stock Quantity*</label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn-submit">Update Product</button>
                <a href="/owner/products/delete.php?id=<?php echo $product_id; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product?')">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </div>
        </form>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
        // Image preview functionality
        const productImage = document.getElementById('productImage');
        const imagePreview = document.getElementById('imagePreview');
        
        productImage.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    imagePreview.appendChild(img);
                    imagePreview.classList.add('has-image');
                }
                
                reader.readAsDataURL(file);
            } else {
                <?php if ($product['image_path']): ?>
                    imagePreview.innerHTML = '<img src="/assets/images/products/<?php echo $product['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">';
                <?php else: ?>
                    imagePreview.innerHTML = '<div class="default-text">No image selected</div>';
                    imagePreview.classList.remove('has-image');
                <?php endif; ?>
            }
        });

        function updateSubcategories() {
            const categorySelect = document.getElementById('category');
            const sizeGroup = document.getElementById('sizeGroup');
            const stockQuantityInput = document.getElementById('stock_quantity');
            const selectedCategory = categorySelect.value;

            if (selectedCategory === 'clothes') {
                sizeGroup.style.display = 'block';
                stockQuantityInput.style.display = 'none';
                stockQuantityInput.value = calculateTotalSizeQuantity();
            } else {
                sizeGroup.style.display = 'none';
                stockQuantityInput.style.display = 'block';
            }
        }

        function addSizeRow() {
            const container = document.getElementById('sizeContainer');
            const newRow = document.createElement('div');
            newRow.className = 'size-row';
            newRow.innerHTML = `
                <select name="sizes[]" class="size-select" onchange="updateTotalQuantity()">
                    <option value="">Select Size</option>
                    <option value="XS">XS</option>
                    <option value="S">S</option>
                    <option value="M">M</option>
                    <option value="L">L</option>
                    <option value="XL">XL</option>
                    <option value="XXL">XXL</option>
                </select>
                <input type="number" name="size_quantities[]" min="0" placeholder="Quantity" class="size-quantity" onchange="updateTotalQuantity()">
                <button type="button" class="btn-remove-size" onclick="removeSizeRow(this)">×</button>
            `;
            container.appendChild(newRow);
        }

        function removeSizeRow(button) {
            button.parentElement.remove();
            updateTotalQuantity();
        }

        function calculateTotalSizeQuantity() {
            const quantities = document.getElementsByName('size_quantities[]');
            let total = 0;
            for (let input of quantities) {
                total += parseInt(input.value) || 0;
            }
            return total;
        }

        function updateTotalQuantity() {
            const stockQuantityInput = document.getElementById('stock_quantity');
            stockQuantityInput.value = calculateTotalSizeQuantity();
        }

        // Form submission validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const category = document.getElementById('category').value;
            
            if (category === 'clothes') {
                const sizeSelects = document.getElementsByName('sizes[]');
                const sizeQuantities = document.getElementsByName('size_quantities[]');
                let hasValidSize = false;

                for (let i = 0; i < sizeSelects.length; i++) {
                    if (sizeSelects[i].value && sizeQuantities[i].value > 0) {
                        hasValidSize = true;
                        break;
                    }
                }

                if (!hasValidSize) {
                    e.preventDefault();
                    alert('Please add at least one size with quantity');
                    return;
                }
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSubcategories();
        });
    </script>
</body>
</html>
