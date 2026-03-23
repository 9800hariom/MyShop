<?php
require_once '../includes/connection.php';
require_once '../includes/auth.php';

// Handle Status Update
if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $status = $conn->real_escape_string($_GET['update_status']);
    if (in_array($status, ['pending', 'completed', 'cancelled'])) {
        $conn->query("UPDATE orders SET status = '$status' WHERE id = $id");
        header("Location: manage_orders.php?success=1");
        exit;
    }
}

$sql = "SELECT o.*, u.name as user_name, u.email as user_email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Orders</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: var(--surface); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .admin-table th, .admin-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border); }
        .admin-table th { background: #f3f4f6; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        
        .status-badge { padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #FEF3C7; color: #D97706; }
        .status-completed { background: #D1FAE5; color: #059669; }
        .status-cancelled { background: #FEE2E2; color: #DC2626; }
    </style>
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
                <li><a href="manage_orders.php" style="color: var(--primary);">Orders</a></li>
                <li><a href="manage_users.php">Users</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <h2 class="page-title">Manage Orders</h2>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Order status updated.</div>
        <?php endif; ?>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight: 600;"><?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['user_email']); ?></td>
                    <td style="font-weight: 500;">$<?php echo number_format($row['total_price'], 2); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $row['status']; ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <!-- Only show necessary status update options -->
                            <?php if ($row['status'] == 'pending'): ?>
                                <a href="manage_orders.php?update_status=completed&id=<?php echo $row['id']; ?>" class="status-badge" style="background:#059669;color:white;text-decoration:none;">Complete</a>
                                <a href="manage_orders.php?update_status=cancelled&id=<?php echo $row['id']; ?>" class="status-badge" style="background:#DC2626;color:white;text-decoration:none;">Cancel</a>
                            <?php elseif ($row['status'] == 'completed'): ?>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">No actions</span>
                            <?php elseif ($row['status'] == 'cancelled'): ?>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">No actions</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
