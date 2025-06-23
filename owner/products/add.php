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
            // Handle file upload
            $image_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/products/';
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
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
                
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO products 
                        (owner_id, name, description, price, category, Sub_Cat, stock_quantity, image_path, size_data)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'],
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
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error_message = 'Failed to add product: ' . $e->getMessage();
                }
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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
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
        
        .subcategory-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .subcategory-divider {
            color: #666;
            font-size: 0.9rem;
        }

        #subcategory, #customSubcategory {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        #customSubcategory {
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
                <label for="category">Category*</label>
                <select id="category" name="category" required onchange="updateSubcategories()">
                    <option value="">Select a category</option>
                    <option value="clothes" <?php echo (isset($category) && $category === 'clothes') ? 'selected' : ''; ?>>Clothes</option>
                    <option value="school_supplies" <?php echo (isset($category) && $category === 'school_supplies') ? 'selected' : ''; ?>>School Supplies</option>
                    <option value="others" <?php echo (isset($category) && $category === 'others') ? 'selected' : ''; ?>>Others</option>
                </select>
            </div>
            
            <div class="form-group" id="subcategoryGroup" style="display: none;">
                <label for="subcategory">Subcategory*</label>
                <div class="subcategory-container">
                    <select id="subcategory" name="subcategory" onchange="handleSubcategoryChange()">
                        <option value="">Select a subcategory</option>
                    </select>
                    <div class="subcategory-divider">or</div>
                    <input type="text" id="customSubcategory" name="customSubcategory" placeholder="Type your own subcategory">
                </div>
            </div>
            
            <div class="form-group">
                <label for="stock_quantity">Stock Quantity*</label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo htmlspecialchars($stock_quantity ?? 0); ?>" required>
            </div>
            
            <div class="form-group" id="sizeGroup" style="display: none;">
                <label>Sizes and Quantities*</label>
                <div id="sizeContainer">
                    <!-- Size rows will be added here dynamically -->
                </div>
                <button type="button" class="btn-add-size" onclick="addSizeRow()">
                    <i class="fas fa-plus"></i> Add Size
                </button>
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

        const subcategories = {
            clothes: [
                'T-Shirts',
                'Pants',
                'PE Uniform',
                'University Uniform',
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

                // Show/hide size group based on category
                if (selectedCategory === 'clothes') {
                    sizeGroup.style.display = 'block';
                    stockQuantityInput.style.display = 'none';
                    stockQuantityInput.value = calculateTotalSizeQuantity();
                } else {
                    sizeGroup.style.display = 'none';
                    stockQuantityInput.style.display = 'block';
                }
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
            } else {
                subcategorySelect.style.display = 'block';
                customSubcategory.style.display = 'none';
                customSubcategory.required = false;
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
