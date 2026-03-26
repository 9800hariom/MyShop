<?php
$page_title = 'Manage Products';
require_once 'admin_header.php';

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

// Search and Filter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;

$where = "WHERE 1=1";
if ($search) {
    $where .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
}
if ($category_filter) {
    $where .= " AND p.category_id = $category_filter";
}

$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $where 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);

$categories = $conn->query("SELECT * FROM categories");
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">Product Inventory</h2>
        <p style="color: #64748b; font-size: 0.9rem;">Manage, add, and edit your store products.</p>
    </div>
    <a href="add_product.php" class="btn-fill" style="text-decoration:none; padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-plus"></i> Add New Product
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem;">Product deleted successfully.</div>
<?php endif; ?>

<form class="filter-bar" method="GET" action="">
    <div style="position: relative; flex: 1; min-width: 250px;">
        <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
        <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>" style="padding-left: 2.5rem; width: 100%;">
    </div>
    
    <select name="category">
        <option value="0">All Categories</option>
        <?php while($cat = $categories->fetch_assoc()): ?>
            <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['name']); ?>
            </option>
        <?php endwhile; ?>
    </select>
    
    <button type="submit" class="btn-fill" style="padding: 0.6rem 1.5rem; border-radius: 8px;">Filter</button>
    <?php if($search || $category_filter): ?>
        <a href="manage_products.php" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">Clear</a>
    <?php endif; ?>
</form>

<div style="overflow-x: auto;">
    <table class="premium-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock Status</th>
                <th>Added Date</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $imgName = $row['image'];
                    $imagePath = 'https://via.placeholder.com/50?text=Img';
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
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <img src="<?php echo $imagePath; ?>" alt="" style="width: 45px; height: 45px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <div>
                                <div style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($row['name']); ?></div>
                                <div style="font-size: 0.75rem; color: #94a3b8;">ID: #<?php echo $row['id']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['category_name']); ?></span></td>
                    <td><span style="font-weight: 600; color: #1e293b;">$<?php echo number_format($row['price'], 2); ?></span></td>
                    <td><span class="badge badge-completed">In Stock</span></td>
                    <td><span style="font-size: 0.85rem; color: #64748b;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span></td>
                    <td>
                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                            <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn-action" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="manage_products.php?delete=<?php echo $row['id']; ?>" class="btn-action delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: #94a3b8;">
                        <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                        No products found matching your criteria.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'admin_footer.php'; ?>
