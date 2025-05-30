<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotOwner();

require_once '../../config/database.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $category = trim($_POST['category']);
    $stock_quantity = (int)$_POST['stock_quantity'];
    
    // Validate inputs
    if (empty($name) || empty($description) || empty($price) || empty($stock_quantity)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!is_numeric($price) || $price <= 0) {
        $error_message = 'Please enter a valid price.';
    } elseif ($stock_quantity < 0) {
        $error_message = 'Stock quantity cannot be negative.';
    } else {
        try {
            // Handle file upload
            $image_path = null;
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
                } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $image_path = $filename;
                } else {
                    $error_message = 'Failed to upload image.';
                }
            }
            
            if (empty($error_message)) {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("
                    INSERT INTO products 
                    (owner_id, name, description, price, category, stock_quantity, image_path)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $name,
                    $description,
                    $price,
                    $category,
                    $stock_quantity,
                    $image_path
                ]);
                
                $pdo->commit();
                $success_message = 'Product added successfully!';
                
                // Clear form
                $name = $description = $price = $category = '';
                $stock_quantity = 0;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = 'Failed to add product. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - UniMarket</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .add-product-container {
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
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container add-product-container">
        <h1>Add New Product</h1>
        
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
                    <div class="default-text">No image selected</div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="name">Product Name*</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description*</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="price">Price*</label>
                <input type="number" id="price" name="price" step="0.01" min="0.01" value="<?php echo htmlspecialchars($price ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($category ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="stock_quantity">Stock Quantity*</label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo htmlspecialchars($stock_quantity ?? 0); ?>" required>
            </div>
            
            <button type="submit" class="btn-submit">Add Product</button>
        </form>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
        // Image preview functionality
        const productImage = document.getElementById('productImage');
        const imagePreview = document.getElementById('imagePreview');
        const defaultText = imagePreview.querySelector('.default-text');
        
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
                imagePreview.innerHTML = '<div class="default-text">No image selected</div>';
                imagePreview.classList.remove('has-image');
            }
        });
    </script>
</body>
</html>