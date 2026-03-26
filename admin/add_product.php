<?php
$page_title = 'Add New Product';
require_once 'admin_header.php';
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

<div style="margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">Create New Product</h2>
        <p style="color: #64748b; font-size: 0.9rem;">Fill in the details below to add a new product to your catalog.</p>
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
                <input type="text" name="name" required placeholder="e.g. Modern Sofa">
            </div>
            
            <div class="form-group">
                <label>Price ($)</label>
                <input type="number" step="0.01" name="price" required placeholder="0.00">
            </div>
            
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group" style="grid-column: span 2;">
                <label>Product Image</label>
                <input type="file" name="image" accept="image/*" required style="padding: 0.5rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 5px;">Supported formats: JPG, PNG, WEBP, GIF</p>
            </div>
            
            <div class="form-group" style="grid-column: span 2;">
                <label>Description</label>
                <textarea name="description" rows="5" placeholder="Describe your product..."></textarea>
            </div>
        </div>
        
        <div style="margin-top: 1rem; text-align: right;">
            <button type="submit" class="btn-fill" style="padding: 0.75rem 2rem; border-radius: 8px; font-weight: 600;">Add Product</button>
        </div>
    </form>
</div>

<?php require_once 'admin_footer.php'; ?>
