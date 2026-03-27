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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        /* ===== NAVBAR ===== */
        nav {
            background: #ffffff;
            padding: 15px 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-radius: 50px;
        }

        /* NAV MENU */
        nav ul {
            list-style: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        /* NAV LINKS */
        nav ul li a {
            text-decoration: none;
            font-weight: 600;
            color: #333;
            padding: 8px 14px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        /* HOVER EFFECT */
        nav ul li a:hover {
            background: linear-gradient(135deg, #ff6a00, #ee0979);
            color: #fff;
            transform: translateY(-2px);
        }

        /* ===== SEARCH BAR ===== */
        .search-bar {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }

        .search-bar form {
            display: flex;
            width: 100%;
            max-width: 500px;
            background: #fff;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        /* INPUT */
        .search-bar input {
            flex: 1;
            border: none;
            outline: none;
            padding: 14px 18px;
            font-size: 15px;
        }

        /* BUTTON */
        .search-bar button {
            border: none;
            padding: 14px 22px;
            background: linear-gradient(135deg, #ff6a00, #ee0979);
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .search-bar button:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }

        /* ===== LOGIN / REGISTER BUTTONS ===== */
        .btn-outline {
            border: 2px solid #ff6a00;
            color: #ff6a00;
            padding: 6px 14px;
            border-radius: 25px;
            transition: 0.3s;
        }

        .btn-outline:hover {
            background: #ff6a00;
            color: #fff;
        }

        .btn-fill {
            background: linear-gradient(135deg, #ff6a00, #ee0979);
            color: #fff;
            padding: 6px 14px;
            border-radius: 25px;
            transition: 0.3s;
        }

        .btn-fill:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(25, 0, 255, 0.3);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            nav ul {
                flex-direction: column;
                gap: 10px;
            }

            .search-bar form {
                width: 90%;
            }
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