<?php
require_once 'includes/connection.php';
require_once 'includes/header.php';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    }
}

// Fetch Featured Products (last 8) with avg rating
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sql = "SELECT p.*, c.name as category_name,
        COALESCE(AVG(r.rating),0) as avg_rating,
        COUNT(r.id) as rating_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN product_ratings r ON p.id = r.product_id";

if ($search) {
    $sql .= " WHERE p.name LIKE '%$search%'";
    $sql .= " GROUP BY p.id";
} else {
    $sql .= " GROUP BY p.id ORDER BY p.created_at DESC LIMIT 8";
}

$result = $conn->query($sql);
?>

<style>
    /* Modern CSS Reset & Global Styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Banner Styles */
    .modern-banner {
        position: relative;
        background: url('images/home.jpg') no-repeat center center/cover;
        padding: 6rem 2rem;
        border-radius: 24px;
        margin-bottom: 3rem;
        overflow: hidden;
        width: 100%;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        transition: transform 0.3s ease;
    }

    /* smooth hover effect */
    .modern-banner:hover {
        transform: translateY(-6px);
    }

    /* DARK OVERLAY for readability */
    .modern-banner::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg,
                rgba(0, 0, 0, 0.55),
                rgba(0, 0, 0, 0.2));
        z-index: 1;
    }

    /* content should be above overlay */
    .modern-banner>* {
        position: relative;
        z-index: 2;
    }

    .modern-banner::after {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top left, rgba(255, 255, 255, 0.15), transparent 60%);
        z-index: 1;
        animation: glowMove 6s infinite alternate;
    }

    @keyframes glowMove {
        0% {
            transform: scale(1);
        }

        100% {
            transform: scale(1.1);
        }
    }

    .banner-content {
        position: relative;
        z-index: 2;
        text-align: center;
        color: #fff;

    }

    .banner-title {
        font-size: 4rem;
        font-weight: 800;
        margin-bottom: 1rem;
        letter-spacing: -1px;

        /* Premium gradient text */
        background: linear-gradient(135deg, #ffffff, #ffd89b);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;

        text-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);

        animation: fadeInUp 0.8s ease forwards;
    }

    .banner-subtitle {
        font-size: 1.3rem;
        margin-bottom: 2rem;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        opacity: 0.95;
        animation: fadeInUp 0.8s ease 0.2s both;
    }

    .btn-shop {
        display: inline-block;
        padding: 1rem 2.8rem;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;

        /* Premium gradient */
        background: linear-gradient(135deg, #ff6a00, #ee0979);

        color: #fff;
        text-decoration: none;

        transition: all 0.3s ease;
        box-shadow: 0 10px 25px rgba(255, 106, 0, 0.3);

        animation: fadeInUp 0.8s ease 0.4s both;
        position: relative;
        overflow: hidden;
    }

    .btn-shop::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(120deg,
                transparent,
                rgba(255, 255, 255, 0.4),
                transparent);
        transition: 0.5s;
    }

    .btn-shop:hover::before {
        left: 100%;
    }

    /* Marquee Styles */
    .marquee-wrapper {
        margin-top: 2.5rem;
        padding: 0.8rem;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border-radius: 50px;
        overflow: hidden;
        animation: fadeInUp 0.8s ease 0.6s both;
    }

    .modern-marquee {
        font-size: 1rem;
        font-weight: 500;
        letter-spacing: 1px;
        color: #fff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .modern-marquee span {
        display: inline-block;
        padding: 0 2rem;
        animation: marqueeScroll 20s linear infinite;
    }

    /* Page Title */
    .page-title {
        text-align: center;
        font-size: 2.5rem;
        font-weight: 700;
        margin: 3rem 0 2rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        position: relative;
        display: inline-block;
        width: 100%;
    }

    .page-title::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 3px;
    }

    /* Products Grid */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 2rem;
        padding: 1rem 0 3rem;
    }

    /* Product Card */
    .product-card {
        background: #fff;
        border-radius: 20px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        position: relative;
    }

    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }

    .product-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .product-card:hover::before {
        transform: scaleX(1);
    }

    .product-card img {
        width: 100%;
        height: 250px;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .product-card:hover img {
        transform: scale(1.05);
    }

    .product-info {
        padding: 1.5rem;
        text-align: center;
    }

    .product-info h3 {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: #2d3748;
        transition: color 0.3s ease;
    }

    .product-card:hover .product-info h3 {
        color: #667eea;
    }

    .price {
        font-size: 1.3rem;
        font-weight: 700;
        color: #764ba2;
        margin-bottom: 0.5rem;
    }

    /* Stock badge */
    .stock-badge {
        display: inline-block;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        margin-bottom: 0.7rem;
    }
    .stock-badge.in-stock  { background:#d1fae5; color:#065f46; }
    .stock-badge.low-stock { background:#fef3c7; color:#92400e; }
    .stock-badge.out-stock { background:#fee2e2; color:#991b1b; }

    /* Star rating */
    .star-rating {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 2px;
        margin-bottom: 0.6rem;
        font-size: 1rem;
    }
    .star-rating .star       { color: #d1d5db; }
    .star-rating .star.filled{ color: #f59e0b; }
    .star-rating .rating-count{ font-size:0.78rem; color:#718096; margin-left:4px; }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0.85rem 1.8rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: #fff;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1rem;
        letter-spacing: 0.5px;
        border: none;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: all 0.4s ease;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.25);
        transform: translateY(0);

    }

    /* Shine effect */
    .btn::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 40%;
        height: 200%;
        background: rgba(255, 255, 255, 0.25);
        transform: rotate(25deg);
        transition: all 2.6s ease;
        opacity: 0;
    }

    .btn:hover::before {
        left: 120%;
        opacity: 4;
    }

    /* Hover state */
    .btn:hover {
        transform: translateY(-4px) scale(1.03);
        box-shadow: 0 15px 30px rgba(148, 153, 155, 0.4);
    }

    /* Active click effect */
    .btn:active {
        transform: translateY(-1px) scale(0.98);
        box-shadow: 0 8px 15px rgba(102, 126, 234, 0.3);
    }

    /* Focus accessibility */
    .btn:focus {
        outline: none;
        box-shadow: 2 0 0 4px rgba(118, 75, 162, 0.3);
    }

    /* No Results Message */
    .no-products {
        text-align: center;
        padding: 3rem;
        font-size: 1.2rem;
        color: #718096;
        background: #f7fafc;
        border-radius: 20px;
        grid-column: 1 / -1;
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes marqueeScroll {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(-50%);
        }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .banner-title {
            font-size: 2.5rem;
        }

        .banner-subtitle {
            font-size: 1rem;
        }

        .modern-banner {
            padding: 4rem 1.5rem;
        }

        .products-grid {
            gap: 1.5rem;
            padding: 1rem;
        }

        .page-title {
            font-size: 2rem;
        }

        .btn-shop {
            padding: 0.8rem 2rem;
            font-size: 1rem;
        }

        .marquee-wrapper {
            margin-top: 1.5rem;
        }
    }

    @media (max-width: 480px) {
        .banner-title {
            font-size: 1.8rem;
        }

        .product-card img {
            height: 200px;
        }

        .product-info {
            padding: 1rem;
        }

        .price {
            font-size: 1.1rem;
        }
    }

    /* Optional: Add loading animation */
    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }

        100% {
            background-position: 1000px 0;
        }
    }

    .product-card.loading {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 1000px 100%;
        animation: shimmer 2s infinite;
    }

    /* Smooth Scroll */
    html {
        scroll-behavior: smooth;
    }
</style>


<?php if (!$search): ?>

    <div class="modern-banner">
        <div class="banner-content">
            <h1 class="banner-title">Design Your Dream Space</h1>
            <p class="banner-subtitle">Modern furniture & decor at unbeatable prices</p>
            <a href="products.php" class="btn-shop">Visit Now →</a>

            <div class="marquee-wrapper">
                <div class="modern-marquee">
                    <span>✨ Limited Time Offer: Free Shipping on Orders Over $50 ✨ | </span>
                    <span>🎨 New Arrivals Every Week 🎨 | </span>
                    <span>🏠 Transform Your Home Today 🏠 | </span>
                    <span>✨ Limited Time Offer: Free Shipping on Orders Over $50 ✨ | </span>
                    <span>🎨 New Arrivals Every Week 🎨 | </span>
                    <span>🏠 Transform Your Home Today 🏠</span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<h2 class="page-title"><?php echo $search ? "✨ Search Results for '$search' ✨" : "✨ Featured Products ✨"; ?></h2>

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
            // Star rating
            $avg   = round((float)$row['avg_rating']);
            $count = (int)$row['rating_count'];
            $stars = '';
            for ($s = 1; $s <= 5; $s++) {
                $stars .= '<span class="star' . ($s <= $avg ? ' filled' : '') . '">★</span>';
            }
            echo '<div class="star-rating">' . $stars . '<span class="rating-count">(' . $count . ')</span></div>';
            // Price
            echo '<p class="price">रु ' . number_format($row['price']) . '</p>';
            // Stock badge
            $qty = (int)$row['stock_quantity'];
            if ($qty <= 0) {
                echo '<span class="stock-badge out-stock">Out of Stock</span>';
            } elseif ($qty <= 10) {
                echo '<span class="stock-badge low-stock">Only ' . $qty . ' left!</span>';
            } else {
                echo '<span class="stock-badge in-stock">In Stock (' . $qty . ')</span>';
            }
            echo '<a href="product_details.php?id=' . $row['id'] . '" class="btn">View Details →</a>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<div class="no-products">';
        echo '<p>🔍 No products found matching your search.</p>';
        echo '<a href="index.php" style="display: inline-block; margin-top: 1rem; color: #667eea; text-decoration: none;">← Back to Home</a>';
        echo '</div>';
    }
    ?>
</div>

<?php require_once 'includes/footer.php'; ?>