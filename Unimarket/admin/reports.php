<?php
require_once '../includes/auth_functions.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

require_once '../config/database.php';

// Get total users by role
$stmt = $pdo->prepare("
    SELECT role, COUNT(*) as count 
    FROM users 
    GROUP BY role
");
$stmt->execute();
$users_by_role = $stmt->fetchAll();

// Get total products by status
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM products 
    GROUP BY status
");
$stmt->execute();
$products_by_status = $stmt->fetchAll();

// Get total orders by status
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM orders 
    GROUP BY status
");
$stmt->execute();
$orders_by_status = $stmt->fetchAll();

// Get total revenue
$stmt = $pdo->prepare("
    SELECT SUM(total_amount) as total 
    FROM orders 
    WHERE status = 'completed'
");
$stmt->execute();
$total_revenue = $stmt->fetchColumn();

// Get recent sales (last 7 days)
$stmt = $pdo->prepare("
    SELECT DATE(order_date) as date, SUM(total_amount) as total 
    FROM orders 
    WHERE status = 'completed' 
    AND order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(order_date)
    ORDER BY date DESC
");
$stmt->execute();
$recent_sales = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - UniMarket Admin</title>
    <link rel="stylesheet" href="/unimarket/assets/css/style.css">
    <link rel="stylesheet" href="/unimarket/assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .reports {
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
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .chart-container h2 {
            margin: 0 0 20px 0;
            color: var(--primary-dark);
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container reports">
        <h1>System Reports</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="number">$<?php echo number_format($total_revenue, 2); ?></div>
            </div>
            
            <?php foreach ($users_by_role as $role): ?>
            <div class="stat-card">
                <h3>Total <?php echo ucfirst($role['role']); ?>s</h3>
                <div class="number"><?php echo $role['count']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="chart-grid">
            <div class="chart-container">
                <h2>Products by Status</h2>
                <canvas id="productsChart"></canvas>
            </div>
            
            <div class="chart-container">
                <h2>Orders by Status</h2>
                <canvas id="ordersChart"></canvas>
            </div>
        </div>
        
        <div class="chart-container">
            <h2>Recent Sales (Last 7 Days)</h2>
            <canvas id="salesChart"></canvas>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Products by Status Chart
        new Chart(document.getElementById('productsChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($products_by_status, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($products_by_status, 'count')); ?>,
                    backgroundColor: ['#4CAF50', '#FFC107', '#F44336']
                }]
            }
        });
        
        // Orders by Status Chart
        new Chart(document.getElementById('ordersChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($orders_by_status, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($orders_by_status, 'count')); ?>,
                    backgroundColor: ['#2196F3', '#FFC107', '#4CAF50', '#F44336']
                }]
            }
        });
        
        // Recent Sales Chart
        new Chart(document.getElementById('salesChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($recent_sales, 'date')); ?>,
                datasets: [{
                    label: 'Daily Sales',
                    data: <?php echo json_encode(array_column($recent_sales, 'total')); ?>,
                    borderColor: '#2196F3',
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html> 