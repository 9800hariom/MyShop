<?php
require_once '../includes/connection.php';
require_once '../includes/auth.php';

// Fetch statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_sales = $conn->query("SELECT IFNULL(SUM(total_price), 0) as total FROM orders WHERE status = 'completed'")->fetch_assoc()['total'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="glass-header">
        <div class="logo">
            <a href="dashboard.php">🛠️ Admin Panel</a>
        </div>
        <nav>
            <ul>
                <li><a href="../index.php">Storefront</a></li>
                <li><a href="manage_products.php">Products</a></li>
                <li><a href="add_product.php">Add Product</a></li>
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="manage_users.php">Users</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <h2 class="page-title">Dashboard Statistics</h2>
        
        <div class="products-grid">
            <div class="product-card" style="padding: 2rem; background: linear-gradient(135deg, var(--primary), #a855f7); color: white;">
                <h3>Total Users</h3>
                <p style="font-size: 2.5rem; font-weight: bold; margin-top: 1rem; color: white !important;"><?php echo $total_users; ?></p>
            </div>
            
            <div class="product-card" style="padding: 2rem; background: linear-gradient(135deg, #10B981, #059669); color: white;">
                <h3>Total Products</h3>
                <p style="font-size: 2.5rem; font-weight: bold; margin-top: 1rem; color: white !important;"><?php echo $total_products; ?></p>
            </div>
            
            <div class="product-card" style="padding: 2rem; background: linear-gradient(135deg, #F59E0B, #D97706); color: white;">
                <h3>Total Orders</h3>
                <p style="font-size: 2.5rem; font-weight: bold; margin-top: 1rem; color: white !important;"><?php echo $total_orders; ?></p>
            </div>
            
            <div class="product-card" style="padding: 2rem; background: linear-gradient(135deg, #EF4444, #DC2626); color: white;">
                <h3>Total Sales</h3>
                <p style="font-size: 2.5rem; font-weight: bold; margin-top: 1rem; color: white !important;">$<?php echo number_format($total_sales, 2); ?></p>
            </div>
        </div>
    </main>
</body>
</html>
