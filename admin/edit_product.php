<?php
require_once '../includes/connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$product = null;

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $res = $conn->query("SELECT * FROM products WHERE id = $id");
    if ($res->num_rows > 0) {
        $product = $res->fetch_assoc();
    } else {
        header("Location: manage_products.php?error=Product not found");
        exit;
    }
} else {
    header("Location: manage_products.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST['name']);
    $price = sanitize_input($_POST['price']);
    $description = sanitize_input($_POST['description']);
    $category_id = intval($_POST['category_id']);
    
    // Handle Image Upload
    $image = $product['image']; // Keep old image by default
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
                // Delete old image if it exists
                if ($product['image'] && file_exists("../uploads/" . $product['image'])) {
                    unlink("../uploads/" . $product['image']);
                }
            } else {
                $error = "File upload failed.";
            }
        } else {
            $error = "Only JPG, JPEG, PNG, GIF, & WEBP files are allowed.";
        }
    }
    
    if (!isset($error)) {
        $stmt = $conn->prepare("UPDATE products SET name=?, price=?, image=?, description=?, category_id=? WHERE id=?");
        $stmt->bind_param("sdssii", $name, $price, $image, $description, $category_id, $id);
        
        if ($stmt->execute()) {
            $success = "Product updated successfully.";
            // Refresh product data
            $product['name'] = $name;
            $product['price'] = $price;
            $product['description'] = $description;
            $product['category_id'] = $category_id;
            $product['image'] = $image;
        } else {
            $error = "Failed to update product: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
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
                <li><a href="manage_products.php" style="color: var(--primary);">Products</a></li>
                <li><a href="add_product.php">Add Product</a></li>
                <li><a href="manage_orders.php">Orders</a></li>
                <li><a href="manage_users.php">Users</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <div class="form-container" style="max-width: 600px;">
            <h2>Edit Product</h2>
            <?php if (isset($error)) echo "<div class='alert alert-error'>$error</div>"; ?>
            <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Category</label>
                    <div style="display: flex; gap: 1rem;">
                        <select name="category_id" required style="flex: 1;">
                            <option value="">Select Category</option>
                            <?php 
                            // Reset categories pointer since we might loop multiple times if we had a bug, but here we only loop once
                            mysqli_data_seek($categories, 0);
                            while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Product Image (Leave blank to keep current)</label>
                    <?php if ($product['image']): ?>
                        <div style="margin-bottom: 0.5rem;">
                            <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="Current Image" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;" onerror="this.src='https://via.placeholder.com/100?text=No+Img';">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*" style="padding: 0.5rem; background: var(--background);">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="5"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                
                <button type="submit" class="btn-submit">Update Product</button>
            </form>
        </div>
    </main>
</body>
</html>
