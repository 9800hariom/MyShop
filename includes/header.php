<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Store</title>
    <!-- Modern aesthetic CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        .glass-header {
            background-color: orange;
        }

        .glass-header:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
    </style>
</head>

<body>
    <header class="glass-header">
        <div class="logo">
            <a href="index.php">🛍️ MyShop</a>
        </div>

        <div class="search-bar">
            <form action="index.php" method="GET">
                <input type="text" name="search" placeholder="Search products...">
                <button type="submit">Search</button>
            </form>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="cart.php">Cart 🛒</a></li>
                    <li><a href="user_orders.php">Orders</a></li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin/dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn-outline">Login</a></li>
                    <li><a href="register.php" class="btn-fill">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>