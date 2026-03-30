<?php
$page_title = 'Edit Product';
require_once 'admin_header.php';
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
    $stock_quantity = intval($_POST['stock_quantity']);
    
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
        $stmt = $conn->prepare("UPDATE products SET name=?, price=?, image=?, description=?, category_id=?, stock_quantity=? WHERE id=?");
        $stmt->bind_param("sdssiii", $name, $price, $image, $description, $category_id, $stock_quantity, $id);
        
        if ($stmt->execute()) {
            $success = "Product updated successfully.";
            // Refresh product data
            $product['name'] = $name;
            $product['price'] = $price;
            $product['description'] = $description;
            $product['category_id'] = $category_id;
            $product['image'] = $image;
            $product['stock_quantity'] = $stock_quantity;
        } else {
            $error = "Failed to update product: " . $stmt->error;
        }
    }
}
?>

<div style="margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">Edit Product Information</h2>
        <p style="color: #64748b; font-size: 0.9rem;">Modify the product details for <strong><?php echo htmlspecialchars($product['name']); ?></strong>.</p>
    </div>
    <a href="manage_products.php" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
</div>

<div class="chart-container" style="max-width: 800px; margin: 0 auto; padding: 2rem;">
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
            <div class="form-group" style="grid-column: span 2;">
                <label>Product Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            
            <div class="form-group" style="grid-column: span 2;">
                <label>Current Image</label>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; display: inline-block;">
                    <?php 
                        $imgName = $product['image'];
                        $imagePath = 'https://via.placeholder.com/150?text=No+Img';
                        if (!empty($imgName)) {
                            $possiblePaths = [
                                $imgName,
                                '../uploads/' . $imgName,
                                '../images/' . $imgName,
                                '../' . $imgName
                            ];
                            foreach ($possiblePaths as $path) {
                                if (file_exists($path)) {
                                    $imagePath = $path;
                                    break;
                                }
                            }
                        }
                    ?>
                    <img src="<?php echo $imagePath; ?>" alt="Current Product Image" style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px;">
                </div>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label>Update Image (Optional)</label>
                <input type="file" name="image" accept="image/*" style="padding: 0.5rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 5px;">Leave blank to keep the current image.</p>
            </div>
            
            <div class="form-group">
                <label>Price ($)</label>
                <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Stock Quantity</label>
                <input type="number" name="stock_quantity" required min="0"
                    value="<?php echo isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : 100; ?>">
            </div>
            
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php 
                    mysqli_data_seek($categories, 0);
                    while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group" style="grid-column: span 2;">
                <label>Description</label>
                <textarea name="description" rows="5"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
        </div>
        
        <div style="margin-top: 1rem; text-align: right;">
            <button type="submit" class="btn-fill" style="padding: 0.75rem 2rem; border-radius: 8px; font-weight: 600;">Save Changes</button>
        </div>
    </form>
</div>

<?php require_once 'admin_footer.php'; ?>
