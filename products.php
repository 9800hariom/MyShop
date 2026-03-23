<?php
require_once 'includes/connection.php';
require_once 'includes/header.php';

// Pagination and Filtering
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$sort = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : '';

// Build Query
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";

if ($search) {
    $sql .= " AND p.name LIKE '%$search%'";
}
if ($category > 0) {
    $sql .= " AND p.category_id = $category";
}

// Sorting logic
if ($sort === 'price_asc') {
    $sql .= " ORDER BY p.price ASC";
} elseif ($sort === 'price_desc') {
    $sql .= " ORDER BY p.price DESC";
} else {
    $sql .= " ORDER BY p.created_at DESC";
}

$result = $conn->query($sql);

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>

<div style="display: flex; gap: 2rem; margin-top: 2rem;">
    <!-- Filters Sidebar -->
    <aside style="width: 250px; flex-shrink: 0; background: var(--surface); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3>Filters</h3>
        <form action="" method="GET" style="margin-top: 1rem;">
            <?php if ($search): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Category</label>
                <select name="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php if ($categories): ?>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sort By</label>
                <select name="sort" onchange="this.form.submit()">
                    <option value="">Newest</option>
                    <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                    <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                </select>
            </div>
        </form>
    </aside>

    <!-- Products List -->
    <div style="flex: 1;">
        <h2 class="page-title">Products</h2>
        
        <div class="products-grid">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="product-card">';
                    
                    $imgName = htmlspecialchars($row['image']);
                    if (!empty($imgName)) {
                        if (file_exists($imgName)) {
                            $imagePath = $imgName;
                        } elseif (file_exists('uploads/' . $imgName)) {
                            $imagePath = 'uploads/' . $imgName;
                        } elseif (file_exists('images/' . $imgName)) {
                            $imagePath = 'images/' . $imgName;
                        } elseif (file_exists('images/products/' . $imgName)) {
                            $imagePath = 'images/products/' . $imgName;
                        } else {
                            $imagePath = 'https://via.placeholder.com/250x200?text=No+Image';
                        }
                    } else {
                        $imagePath = 'https://via.placeholder.com/250x200?text=No+Image';
                    }
                    
                    echo '<img src="' . $imagePath . '" alt="' . htmlspecialchars($row['name']) . '">';
                    echo '<div class="product-info">';
                    echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
                    echo '<p class="price">$' . number_format($row['price'], 2) . '</p>';
                    echo '<a href="product_details.php?id=' . $row['id'] . '" class="btn">View Details</a>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p>No products found matching your criteria.</p>';
            }
            ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
