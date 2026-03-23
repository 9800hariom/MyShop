<?php
require_once 'includes/connection.php';
require_once 'includes/header.php';

// Fetch Featured Products (last 8)
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";

if ($search) {
    $sql .= " WHERE p.name LIKE '%$search%'";
} else {
    $sql .= " ORDER BY p.created_at DESC LIMIT 8";
}

$result = $conn->query($sql);
?>

<?php if (!$search): ?>
<div class="banner" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('images/banner-drop.jpg') no-repeat center center/cover; padding: 8rem 2rem; color: #fff; text-align: center; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
    <h1 style="font-size: 3.5rem; margin-bottom: 1rem; font-weight: 700; letter-spacing: -1px; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">Design Your Dream Space</h1>
    <p style="font-size: 1.2rem; margin-bottom: 2rem; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">Modern furniture & decor at unbeatable prices.</p>
    <a href="products.php" class="btn-fill" style="padding: 1rem 2.5rem; font-size: 1.1rem; border-radius: 50px; background: #fff; color: #333; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">Shop Now</a>
</div>
<?php endif; ?>

<h2 class="page-title"><?php echo $search ? "Search Results for '$search'" : "Featured Products"; ?></h2>

<div class="products-grid">
    <?php
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<div class="product-card">';
            
            // Check if image exists, otherwise placeholder
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
        echo '<p>No products found.</p>';
    }
    ?>
</div>

<?php require_once 'includes/footer.php'; ?>
