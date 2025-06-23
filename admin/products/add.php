<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

require_once '../../config/database.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $category = trim($_POST['category']);
    $subcategory = trim($_POST['subcategory']);
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
            // Handle image upload
            $image_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/products/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception('Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.');
                }
                
                $image_path = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $image_path;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    throw new Exception('Failed to upload image.');
                }
            }
            
            if (empty($error_message)) {
                $pdo->beginTransaction();
                
                // Get admin's user ID from session
                $owner_id = $_SESSION['user_id'];
                
                $stmt = $pdo->prepare("
                    INSERT INTO products 
                    (owner_id, name, description, price, category, Sub_Cat, stock_quantity, image_path, size_data)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $owner_id,
                    $name,
                    $description,
                    $price,
                    $category,
                    $subcategory,
                    $stock_quantity,
                    $image_path,
                    $size_data ? json_encode($size_data) : null
                ]);
                
                $pdo->commit();
                $success_message = 'Product added successfully!';
                
                // Clear form
                $name = $description = $price = $category = $subcategory = '';
                $stock_quantity = 0;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = $e->getMessage();
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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        .add-product {
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
    
    <div class="container add-product">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>

        <h1>Add New Product</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name *</label>
                <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="price">Price (₱) *</label>
                <input type="number" id="price" name="price" min="0" step="0.01" value="<?php echo isset($price) ? $price : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="stock_quantity">Stock Quantity *</label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo isset($stock_quantity) ? $stock_quantity : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category*</label>
                <select id="category" name="category" onchange="updateSubcategories()" required>
                    <option value="">Select a category</option>
                    <option value="clothes">Clothes</option>
                    <option value="school_supplies">School Supplies</option>
                    <option value="others">Others</option>
                </select>
            </div>
            
            <div class="form-group" id="subcategoryGroup" style="display: none;">
                <label for="subcategory">Subcategory*</label>
                <select id="subcategory" name="subcategory" onchange="handleSubcategoryChange()" required>
                    <option value="">Select a subcategory</option>
                </select>
                <input type="text" id="customSubcategory" name="custom_subcategory" style="display: none;" placeholder="Enter subcategory">
            </div>

            <div class="form-group" id="sizeGroup" style="display: none;">
                <label>Sizes and Quantities*</label>
                <div id="sizeContainer">
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
                </div>
                <button type="button" class="btn-add-size" onclick="addSizeRow()">Add Another Size</button>
            </div>
            
            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                <img id="imagePreview" class="image-preview" src="#" alt="Image Preview">
            </div>
            
            <button type="submit" class="btn-submit">Add Product</button>
        </form>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

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
            const customSubcategory = document.getElementById('customSubcategory');
            const sizeGroup = document.getElementById('sizeGroup');
            const stockQuantityInput = document.getElementById('stock_quantity');
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
                    stockQuantityInput.style.display = 'none';
                    stockQuantityInput.value = calculateTotalSizeQuantity();
                } else {
                    sizeGroup.style.display = 'none';
                    stockQuantityInput.style.display = 'block';
                }

                // Add new options
                subcategories[selectedCategory].forEach(subcategory => {
                    const option = document.createElement('option');
                    option.value = subcategory.toLowerCase().replace(/\s+/g, '_');
                    option.textContent = subcategory;
                    subcategorySelect.appendChild(option);
                });

                // Add custom option
                const customOption = document.createElement('option');
                customOption.value = 'custom';
                customOption.textContent = 'Other (Type your own)';
                subcategorySelect.appendChild(customOption);
            } else {
                // Hide subcategory group if no category selected
                subcategoryGroup.style.display = 'none';
                sizeGroup.style.display = 'none';
                stockQuantityInput.style.display = 'block';
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
                subcategorySelect.name = 'subcategory_placeholder';
                customSubcategory.name = 'subcategory';
            } else {
                subcategorySelect.style.display = 'block';
                customSubcategory.style.display = 'none';
                customSubcategory.required = false;
                subcategorySelect.name = 'subcategory';
                customSubcategory.name = 'custom_subcategory';
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
            const subcategorySelect = document.getElementById('subcategory');
            const customSubcategory = document.getElementById('customSubcategory');
            
            if (subcategorySelect.style.display === 'none' && !customSubcategory.value.trim()) {
                e.preventDefault();
                alert('Please enter a subcategory');
                return;
            }

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

        // Initialize subcategories on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSubcategories();
        });
    </script>
</body>
</html> 
