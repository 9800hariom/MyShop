<?php
require_once 'includes/connection.php';
require_once 'includes/header.php';

// Pagination and Filtering
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$sort = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : '';

// Build Query with avg rating
$sql = "SELECT p.*, c.name as category_name,
        COALESCE(AVG(r.rating),0) as avg_rating,
        COUNT(r.id) as rating_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN product_ratings r ON p.id = r.product_id
        WHERE 1=1";

if ($search) {
    $sql .= " AND p.name LIKE '%$search%'";
}
if ($category > 0) {
    $sql .= " AND p.category_id = $category";
}

$sql .= " GROUP BY p.id";

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

<div style="display: flex; gap: 2rem; margin-top: 2rem; ">
    <!-- Filters Sidebar -->
    <aside style="width: 250px; flex-shrink: 0; background: var(--surface); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);background-color:orange;">
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
                    // Star rating
                    $avg   = round((float)$row['avg_rating']);
                    $count = (int)$row['rating_count'];
                    $stars = '';
                    for ($s = 1; $s <= 5; $s++) {
                        $filled = $s <= $avg ? ' filled' : '';
                        $stars .= '<span style="color:' . ($s <= $avg ? '#f59e0b' : '#d1d5db') . ';font-size:1rem;">★</span>';
                    }
                    echo '<div style="display:flex;justify-content:center;align-items:center;gap:2px;margin-bottom:0.5rem;">' . $stars . '<span style="font-size:0.78rem;color:#718096;margin-left:4px;">(' . $count . ')</span></div>';
                    // Price
                    echo '<p class="price">रु ' . number_format($row['price']) . '</p>';
                    // Stock badge
                    $qty = (int)$row['stock_quantity'];
                    if ($qty <= 0) {
                        echo '<div style="display:inline-block;font-size:0.78rem;font-weight:600;padding:3px 10px;border-radius:20px;background:#fee2e2;color:#991b1b;margin-bottom:0.6rem;">Out of Stock</div>';
                    } elseif ($qty <= 10) {
                        echo '<div style="display:inline-block;font-size:0.78rem;font-weight:600;padding:3px 10px;border-radius:20px;background:#fef3c7;color:#92400e;margin-bottom:0.6rem;">Only ' . $qty . ' left!</div>';
                    } else {
                        echo '<div style="display:inline-block;font-size:0.78rem;font-weight:600;padding:3px 10px;border-radius:20px;background:#d1fae5;color:#065f46;margin-bottom:0.6rem;">In Stock (' . $qty . ')</div>';
                    }
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