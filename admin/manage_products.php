<?php
require_once '../includes/connection.php';
require_once '../includes/auth.php';

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Fetch image to delete from directory
    $res = $conn->query("SELECT image FROM products WHERE id = $id");
    if ($res->num_rows > 0) {
        $img = $res->fetch_assoc()['image'];
        if ($img && file_exists("../uploads/" . $img)) {
            unlink("../uploads/" . $img);
        }
    }
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: manage_products.php?success=1");
    exit;
}

$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: var(--surface); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .admin-table th, .admin-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border); }
        .admin-table th { background: #f3f4f6; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
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
                <li><a href="manage_products.php" style="color: var(--primary);">Products</a></li>
                <li><a href="add_product.php">Add Product</a></li>
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="manage_users.php">Users</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 class="page-title" style="margin: 0;">Manage Products</h2>
            <a href="add_product.php" class="btn-fill" style="text-decoration:none;">+ Add New</a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Product deleted successfully.</div>
        <?php endif; ?>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Date Added</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): 
                    $imgName = htmlspecialchars($row['image']);
                    if (!empty($imgName)) {
                        if (file_exists($imgName)) {
                            $imagePath = $imgName;
                        } elseif (file_exists('../uploads/' . $imgName)) {
                            $imagePath = '../uploads/' . $imgName;
                        } elseif (file_exists('../images/' . $imgName)) {
                            $imagePath = '../images/' . $imgName;
                        } elseif (file_exists('../images/products/' . $imgName)) {
                            $imagePath = '../images/products/' . $imgName;
                        } elseif (file_exists('../' . $imgName)) {
                            $imagePath = '../' . $imgName;
                        } else {
                            $imagePath = 'https://via.placeholder.com/50?text=Img';
                        }
                    } else {
                        $imagePath = 'https://via.placeholder.com/50?text=Img';
                    }
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><img src="<?php echo $imagePath; ?>" alt="Product" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;"></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                    <td>$<?php echo number_format($row['price'], 2); ?></td>
                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <a href="edit_product.php?id=<?php echo $row['id']; ?>" style="color: var(--primary); text-decoration: none; margin-right: 0.5rem;">Edit</a>
                        <a href="manage_products.php?delete=<?php echo $row['id']; ?>" style="color: var(--danger); text-decoration: none;" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
