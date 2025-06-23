<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

// Check if user is logged in and is an admin
if (!isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$product_id = $_GET['id'];

// Get product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category = trim($_POST['category']);
    $subcategory = isset($_POST['subcategory']) ? trim($_POST['subcategory']) : '';
    $size = isset($_POST['size']) ? trim($_POST['size']) : null;
    
    // Validate input
    if (empty($name) || empty($description) || $price <= 0 || $stock < 0) {
        $error_message = "Please fill in all required fields correctly.";
    } else {
        // Handle image upload
        $image = $product['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                // Delete old image if exists
                if (!empty($product['image'])) {
                    $old_image_path = $upload_dir . $product['image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                
                $image = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $image;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $error_message = "Failed to upload image.";
                }
            } else {
                $error_message = "Invalid image format. Allowed formats: JPG, JPEG, PNG, GIF";
            }
        }
        
        if (empty($error_message)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET name = ?, description = ?, price = ?, stock = ?, 
                        category = ?, subcategory = ?, size = ?, image = ?
                    WHERE product_id = ?
                ");
                
                $stmt->execute([
                    $name,
                    $description,
                    $price,
                    $stock,
                    $category,
                    $subcategory,
                    $size,
                    $image,
                    $product_id
                ]);
                
                $success_message = "Product updated successfully!";
                
                // Refresh product data
                $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
            } catch (PDOException $e) {
                $error_message = "Error updating product: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="content">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>

        <div class="header">
            <h1>Edit Product</h1>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="form-container">
            <div class="form-group">
                <label for="name">Product Name*</label>
                <input type="text" id="name" name="name" value="<?php echo isset($product['name']) ? htmlspecialchars($product['name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description*</label>
                <textarea id="description" name="description" required><?php echo isset($product['description']) ? htmlspecialchars($product['description']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="price">Price (₱)*</label>
                <input type="number" id="price" name="price" min="0" step="0.01" value="<?php echo isset($product['price']) ? $product['price'] : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="stock">Stock*</label>
                <input type="number" id="stock" name="stock" min="0" value="<?php echo isset($product['stock']) ? $product['stock'] : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category*</label>
                <select id="category" name="category" required onchange="updateSubcategories()">
                    <option value="">Select a category</option>
                    <option value="clothes" <?php echo (isset($product['category']) && $product['category'] === 'clothes') ? 'selected' : ''; ?>>Clothes</option>
                    <option value="school_supplies" <?php echo (isset($product['category']) && $product['category'] === 'school_supplies') ? 'selected' : ''; ?>>School Supplies</option>
                    <option value="others" <?php echo (isset($product['category']) && $product['category'] === 'others') ? 'selected' : ''; ?>>Others</option>
                </select>
            </div>
            
            <div class="form-group" id="subcategoryGroup" style="display: none;">
                <label for="subcategory">Subcategory*</label>
                <div class="subcategory-container">
                    <select id="subcategory" name="subcategory" onchange="handleSubcategoryChange()">
                        <option value="">Select a subcategory</option>
                    </select>
                    <div class="subcategory-divider">or</div>
                    <input type="text" id="customSubcategory" name="customSubcategory" placeholder="Type your own subcategory" value="<?php echo isset($product['subcategory']) ? htmlspecialchars($product['subcategory']) : ''; ?>">
                </div>
            </div>

            <div class="form-group" id="sizeGroup" style="display: none;">
                <label for="size">Size*</label>
                <select id="size" name="size">
                    <option value="">Select a size</option>
                    <option value="XS" <?php echo (isset($product['size']) && $product['size'] === 'XS') ? 'selected' : ''; ?>>Extra Small (XS)</option>
                    <option value="S" <?php echo (isset($product['size']) && $product['size'] === 'S') ? 'selected' : ''; ?>>Small (S)</option>
                    <option value="M" <?php echo (isset($product['size']) && $product['size'] === 'M') ? 'selected' : ''; ?>>Medium (M)</option>
                    <option value="L" <?php echo (isset($product['size']) && $product['size'] === 'L') ? 'selected' : ''; ?>>Large (L)</option>
                    <option value="XL" <?php echo (isset($product['size']) && $product['size'] === 'XL') ? 'selected' : ''; ?>>Extra Large (XL)</option>
                    <option value="XXL" <?php echo (isset($product['size']) && $product['size'] === 'XXL') ? 'selected' : ''; ?>>Double Extra Large (XXL)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="image">Product Image</label>
                <?php if (isset($product['image']) && $product['image']): ?>
                    <div class="current-image">
                        <img src="../../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="Current product image" style="max-width: 200px;">
                    </div>
                <?php endif; ?>
                <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                <img id="imagePreview" class="image-preview" src="#" alt="Image preview">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Product
                </button>
            </div>
        </form>
    </div>

    <script>
        const subcategories = {
            clothes: [
                'T-Shirts',
                'Pants',
                'PE Uniform',
                'University Uniform'
            ],
            school_supplies: [
                'Notebooks',
                'Pens',
                'Pencils',
                'Highlighters',
                'Markers',
                'Art Supplies',
                'Paper',
                'Folders',
                'Binders',
                'Staplers'
            ],
            others: [
                'University Lace',
                'Hygiene Products',
                'Seals',
                'Calculator',
                'ID Holders'
            ]
        };

        function updateSubcategories() {
            const categorySelect = document.getElementById('category');
            const subcategorySelect = document.getElementById('subcategory');
            const subcategoryGroup = document.getElementById('subcategoryGroup');
            const sizeGroup = document.getElementById('sizeGroup');
            const customSubcategory = document.getElementById('customSubcategory');
            const selectedCategory = categorySelect.value;

            // Clear current options
            subcategorySelect.innerHTML = '<option value="">Select a subcategory</option>';

            if (selectedCategory && subcategories[selectedCategory]) {
                // Show subcategory group
                subcategoryGroup.style.display = 'block';
                subcategorySelect.style.display = 'block';
                customSubcategory.style.display = 'none';

                // Show/hide size group based on category
                if (selectedCategory === 'clothes') {
                    sizeGroup.style.display = 'block';
                    document.getElementById('size').required = true;
                } else {
                    sizeGroup.style.display = 'none';
                    document.getElementById('size').required = false;
                }

                // Add new options
                subcategories[selectedCategory].forEach(subcategory => {
                    const option = document.createElement('option');
                    option.value = subcategory.toLowerCase().replace(/\s+/g, '_');
                    option.textContent = subcategory;
                    // If this is the current subcategory, select it
                    if (option.value === '<?php echo isset($product['subcategory']) ? $product['subcategory'] : ''; ?>') {
                        option.selected = true;
                    }
                    subcategorySelect.appendChild(option);
                });

                // Add custom option
                const customOption = document.createElement('option');
                customOption.value = 'custom';
                customOption.textContent = 'Other (Type your own)';
                subcategorySelect.appendChild(customOption);

                // If current subcategory is not in the list, show custom input
                const currentSubcategory = '<?php echo isset($product['subcategory']) ? $product['subcategory'] : ''; ?>';
                if (currentSubcategory && !subcategories[selectedCategory].some(sub => sub.toLowerCase().replace(/\s+/g, '_') === currentSubcategory)) {
                    subcategorySelect.value = 'custom';
                    handleSubcategoryChange();
                }
            } else {
                // Hide subcategory group if no category selected
                subcategoryGroup.style.display = 'none';
                sizeGroup.style.display = 'none';
            }
        }

        function handleSubcategoryChange() {
            const subcategorySelect = document.getElementById('subcategory');
            const customSubcategory = document.getElementById('customSubcategory');
            
            if (subcategorySelect.value === 'custom') {
                subcategorySelect.style.display = 'none';
                customSubcategory.style.display = 'block';
                customSubcategory.focus();
                customSubcategory.required = true;
            } else {
                subcategorySelect.style.display = 'block';
                customSubcategory.style.display = 'none';
                customSubcategory.required = false;
            }
        }

        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Initialize subcategories on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSubcategories();
        });
    </script>

    <style>
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

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

        .subcategory-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .subcategory-divider {
            color: #666;
            font-size: 14px;
        }

        .current-image {
            margin: 10px 0;
        }

        .image-preview {
            max-width: 200px;
            margin-top: 10px;
            display: none;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
    </style>
</body>
</html> 
