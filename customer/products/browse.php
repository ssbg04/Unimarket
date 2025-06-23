<?php
require_once '../../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotCustomer();

require_once '../../config/database.php';

// Pagination variables
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($current_page - 1) * $per_page;

// Search and filter variables
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Base query
$query = "SELECT * FROM products WHERE stock_quantity > 0";
$count_query = "SELECT COUNT(*) FROM products WHERE stock_quantity > 0";
$params = [];

// Add search condition
if (!empty($search_query)) {
    $query .= " AND (name LIKE ? OR description LIKE ?)";
    $count_query .= " AND (name LIKE ? OR description LIKE ?)";
    $search_param = "%$search_query%";
    array_push($params, $search_param, $search_param);
}

// Add category filter
if (!empty($category_filter)) {
    $query .= " AND category = ?";
    $count_query .= " AND category = ?";
    array_push($params, $category_filter);
}

// Add sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'name':
        $query .= " ORDER BY name ASC";
        break;
    default:
        $query .= " ORDER BY created_at DESC";
        break;
}

// Add pagination
$query .= " LIMIT ? OFFSET ?";
array_push($params, $per_page, $offset);

// Prepare and execute the query
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key + 1, $value, $paramType);
}
$stmt->execute();
$products = $stmt->fetchAll();

// Get total count for pagination
$count_stmt = $pdo->prepare($count_query);
foreach (array_slice($params, 0, count($params) - 2) as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $count_stmt->bindValue($key + 1, $value, $paramType);
}
$count_stmt->execute();
$total_products = $count_stmt->fetchColumn();

// Get all categories for filter dropdown
$categories = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll();

// Calculate total pages
$total_pages = ceil($total_products / $per_page);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products - UniMarket</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        .browse-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 15px;
            padding-right: 40px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-box button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--primary-color);
        }
        
        .filter-dropdown {
            min-width: 200px;
        }
        
        .filter-dropdown select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .sort-dropdown {
            min-width: 150px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: var(--text-color);
        }
        
        .pagination a:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .pagination .current {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .no-products {
            text-align: center;
            padding: 40px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        
        .product-card .stock-info {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .product-card .stock-low {
            color: #e53935;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="browse-header">
            <h1>Browse Products</h1>
            <div class="search-filter-container">
                <form method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                
                <div class="filter-dropdown">
                    <select name="category" onchange="this.form.submit()" form="filter-form">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['category']); ?>" <?php echo $category_filter === $category['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="sort-dropdown">
                    <select name="sort" onchange="this.form.submit()" form="filter-form">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name: A-Z</option>
                    </select>
                </div>
                
                <form id="filter-form" method="GET" style="display: none;">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                </form>
            </div>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="no-products card">
                <h3>No products found</h3>
                <p>Try adjusting your search or filter criteria</p>
                <a href="/customer/products/browse.php" class="btn">Reset Filters</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card card">
                        <a style="color: black; text-decoration: none;" href="/customer/products/view.php?id=<?php echo $product['product_id']; ?>">
                            <div class="product-image">
                                <?php if ($product['image_path']): ?>
                                    <img src="/uploads/products/<?php echo $product['image_path']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #999;">
                                        <i class="fas fa-box-open" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                                <p class="stock-info <?php echo $product['stock_quantity'] < 5 ? 'stock-low' : ''; ?>">
                                    <?php echo $product['stock_quantity'] < 5 ? 'Only ' . $product['stock_quantity'] . ' left!' : 'In stock'; ?>
                                </p>
                                <a href="/customer/products/view.php?id=<?php echo $product['product_id']; ?>" class="btn">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?<?php echo buildQueryString(['page' => 1]); ?>">First</a>
                        <a href="?<?php echo buildQueryString(['page' => $current_page - 1]); ?>">Prev</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $current_page - 2); $i <= min($current_page + 2, $total_pages); $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php echo buildQueryString(['page' => $i]); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?<?php echo buildQueryString(['page' => $current_page + 1]); ?>">Next</a>
                        <a href="?<?php echo buildQueryString(['page' => $total_pages]); ?>">Last</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
        // Add to cart with quantity validation
        document.querySelectorAll('form[action="/customer/cart.php"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                const quantityInput = this.querySelector('input[name="quantity"]');
                const max = parseInt(quantityInput.getAttribute('max'));
                const value = parseInt(quantityInput.value);
                
                if (value < 1) {
                    e.preventDefault();
                    alert('Quantity must be at least 1');
                    quantityInput.focus();
                } else if (value > max) {
                    e.preventDefault();
                    alert(`Only ${max} available in stock`);
                    quantityInput.focus();
                }
            });
        });
        
        // Helper function to build query string from current URL parameters
        function buildQueryString(params) {
            const searchParams = new URLSearchParams(window.location.search);
            for (const key in params) {
                if (params[key] !== undefined) {
                    searchParams.set(key, params[key]);
                }
            }
            return searchParams.toString();
        }
    </script>
</body>
</html>

<?php
/**
 * Helper function to build query string while preserving existing parameters
 */
function buildQueryString($new_params) {
    $params = array_merge($_GET, $new_params);
    return http_build_query($params);
}
?>
