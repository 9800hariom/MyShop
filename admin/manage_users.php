<?php
require_once '../includes/connection.php';
require_once '../includes/auth.php';

// Handle User Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Ensure we don't delete the currently logged in admin or other admins loosely
    // For safety, let's just make it simple but secure enough.
    if ($id !== $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id = $id AND role != 'admin'");
        header("Location: manage_users.php?success=1");
        exit;
    } else {
        header("Location: manage_users.php?error=1");
        exit;
    }
}

$sql = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: var(--surface); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .admin-table th, .admin-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border); }
        .admin-table th { background: #f3f4f6; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        .role-badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .role-admin { background: var(--primary); color: white; }
        .role-user { background: #E5E7EB; color: var(--text-primary); }
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
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="manage_users.php" style="color: var(--primary);">Users</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <h2 class="page-title">Manage Users</h2>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">User deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">Action not permitted.</div>
        <?php endif; ?>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Date Joined</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td style="font-weight: 500;"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <span class="role-badge role-<?php echo $row['role']; ?>">
                            <?php echo htmlspecialchars($row['role']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <?php if ($row['role'] !== 'admin'): ?>
                            <a href="manage_users.php?delete=<?php echo $row['id']; ?>" style="color: var(--danger); text-decoration: none;" onclick="return confirm('Delete this user?');">Delete</a>
                        <?php else: ?>
                            <span style="font-size: 0.8rem; color: var(--text-secondary);">N/A</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
