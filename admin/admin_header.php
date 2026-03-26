<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/connection.php';
require_once '../includes/auth.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Panel'; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --sidebar-width: 260px;
            --admin-primary: #6366f1;
            --admin-bg: #f8fafc;
            --sidebar-bg: #1e293b;
        }

        body {
            background-color: var(--admin-bg);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            padding-top: 1.5rem;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-brand {
            padding: 0 1.5rem 2rem;
            font-size: 1.4rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .sidebar-nav {
            flex: 1;
            list-style: none;
        }

        .sidebar-link {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar-link.active {
            color: white;
            background: var(--admin-primary);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .sidebar-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Main Content Container */
        .admin-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 2rem;
            max-width: calc(100vw - var(--sidebar-width));
        }

        .admin-top-bar {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: #e0e7ff;
            color: var(--admin-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Dashboard Components */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-content h3 {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 5px;
        }

        .stat-content .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }

        /* Chart Styling */
        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        /* Filter Form */
        .filter-bar {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-bar input, .filter-bar select {
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
            min-width: 180px;
        }

        .filter-bar input:focus {
            border-color: var(--admin-primary);
        }

        /* Better Tables */
        .premium-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-collapse: collapse;
        }

        .premium-table th {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            text-align: left;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
        }

        .premium-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }

        .premium-table tr:last-child td {
            border-bottom: none;
        }

        .premium-table tr:hover {
            background: #f8fafc;
        }

        /* Status Badges */
        .badge {
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-completed { background: #dcfce7; color: #15803d; }
        .badge-cancelled { background: #fee2e2; color: #b91c1c; }
        .badge-shipped { background: #e0e7ff; color: #4338ca; }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            border-radius: 6px;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
        }

        .btn-action:hover {
            color: var(--admin-primary);
            border-color: var(--admin-primary);
            background: #f5f3ff;
        }

        .btn-action.delete:hover {
            color: #ef4444;
            border-color: #ef4444;
            background: #fef2f2;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-toolbox"></i> AdminPanel
        </a>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="manage_products.php" class="sidebar-link <?php echo ($current_page == 'manage_products.php' || $current_page == 'add_product.php' || $current_page == 'edit_product.php') ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="manage_orders.php" class="sidebar-link <?php echo $current_page == 'manage_orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="manage_users.php" class="sidebar-link <?php echo $current_page == 'manage_users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Customers
            </a>
            <a href="../index.php" class="sidebar-link">
                <i class="fas fa-external-link-alt"></i> Storefront
            </a>
        </nav>
        <div style="padding: 1.5rem;">
            <a href="../logout.php" class="sidebar-link" style="color: #ef4444; background: rgba(239, 68, 68, 0.1); border-radius: 8px;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <div class="admin-main">
        <div class="admin-top-bar">
            <h2 style="font-size: 1.25rem; font-weight: 600; color: #1e293b;"><?php echo $page_title ?? 'Dashboard'; ?></h2>
            <div class="user-info">
                <span style="font-weight: 500; font-size: 0.9rem;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                </div>
            </div>
        </div>
