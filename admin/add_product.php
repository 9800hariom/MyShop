<?php
require_once '../includes/connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST['name']);
    $price = sanitize_input($_POST['price']);
    $description = sanitize_input($_POST['description']);
    $category_id = intval($_POST['category_id']);
    
    // Handle Image Upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $filename = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $filename;
        
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        
        if (in_array($file_type, $allowed)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $filename;
            } else {
                $error = "File upload failed.";
            }
        } else {
            $error = "Only JPG, JPEG, PNG, GIF, & WEBP files are allowed.";
        }
    }
    
    if (!isset($error)) {
        $stmt = $conn->prepare("INSERT INTO products (name, price, image, description, category_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sdssi", $name, $price, $image, $description, $category_id);
        
        if ($stmt->execute()) {
            $success = "Product added successfully.";
        } else {
            $error = "Failed to add product: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
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
                <li><a href="add_product.php" style="color: var(--primary);">Add Product</a></li>
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="manage_users.php">Users</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <div class="form-container" style="max-width: 600px;">
            <h2>Add New Product</h2>
            <?php if (isset($error)) echo "<div class='alert alert-error'>$error</div>"; ?>
            <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
                
                <div class="form-group">
                    <label>Category</label>
                    <div style="display: flex; gap: 1rem;">
                        <select name="category_id" required style="flex: 1;">
                            <option value="">Select Category</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <!-- In a real scenario, you'd have a separate manage_categories.php to add categories -->
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" accept="image/*" required style="padding: 0.5rem; background: var(--background);">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="5"></textarea>
                </div>
                
                <button type="submit" class="btn-submit">Add Product</button>
            </form>
        </div>
    </main>
</body>
</html>
