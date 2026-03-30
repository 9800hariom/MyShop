<?php
require_once 'includes/connection.php';
require_once 'includes/header.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    echo "<p>Invalid Product.</p>";
    require_once 'includes/footer.php';
    exit;
}

// Fetch product with avg rating
$stmt = $conn->prepare(
    "SELECT p.*, c.name as category_name,
     COALESCE(AVG(r.rating),0) as avg_rating,
     COUNT(r.id) as rating_count
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN product_ratings r ON p.id = r.product_id
     WHERE p.id = ?
     GROUP BY p.id"
);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Product not found.</p>";
    require_once 'includes/footer.php';
    exit;
}

$product = $result->fetch_assoc();

// Handle Add to Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $user_id  = $_SESSION['user_id'];
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    // Stock check
    if ($quantity > $product['stock_quantity']) {
        $cart_error = "Only " . (int)$product['stock_quantity'] . " unit(s) available.";
    } else {
        // Check if product already in cart
        $check = $conn->query("SELECT id, quantity FROM cart WHERE user_id = $user_id AND product_id = $product_id");
        if ($check->num_rows > 0) {
            $cart_item  = $check->fetch_assoc();
            $new_qty    = $cart_item['quantity'] + $quantity;
            if ($new_qty > $product['stock_quantity']) {
                $cart_error = "Cannot add more than available stock (" . (int)$product['stock_quantity'] . ").";
            } else {
                $conn->query("UPDATE cart SET quantity = $new_qty WHERE id = " . $cart_item['id']);
                $success_msg = "Cart updated successfully.";
            }
        } else {
            $stmt2 = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt2->bind_param("iii", $user_id, $product_id, $quantity);
            $stmt2->execute();
            $success_msg = "Product added to cart successfully.";
        }
    }
}

// Handle Rating Submission
$rating_msg = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_rating'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    $user_id    = (int)$_SESSION['user_id'];
    $rating_val = intval($_POST['rating']);
    $review_txt = $conn->real_escape_string(trim($_POST['review']));

    if ($rating_val < 1 || $rating_val > 5) {
        $rating_msg = '<div class="rating-alert error">Please select a star rating (1–5).</div>';
    } else {
        // Upsert: insert or update existing rating
        $chk = $conn->query("SELECT id FROM product_ratings WHERE user_id = $user_id AND product_id = $product_id");
        if ($chk->num_rows > 0) {
            $conn->query("UPDATE product_ratings SET rating = $rating_val, review = '$review_txt', created_at = NOW()
                          WHERE user_id = $user_id AND product_id = $product_id");
            $rating_msg = '<div class="rating-alert success">✅ Your review has been updated!</div>';
        } else {
            $conn->query("INSERT INTO product_ratings (product_id, user_id, rating, review)
                          VALUES ($product_id, $user_id, $rating_val, '$review_txt')");
            $rating_msg = '<div class="rating-alert success">✅ Thank you for your review!</div>';
        }
        // Refresh avg rating
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result2 = $stmt->get_result();
        if ($result2->num_rows > 0) {
            $refreshed = $result2->fetch_assoc();
            $product['avg_rating']   = $refreshed['avg_rating'];
            $product['rating_count'] = $refreshed['rating_count'];
        }
    }
}

// Fetch all reviews for this product
$reviews_sql = "SELECT r.*, u.name as user_name FROM product_ratings r
                JOIN users u ON r.user_id = u.id
                WHERE r.product_id = $product_id
                ORDER BY r.created_at DESC";
$reviews_result = $conn->query($reviews_sql);

// Check if current user already rated
$user_existing_rating = null;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $ur  = $conn->query("SELECT * FROM product_ratings WHERE user_id=$uid AND product_id=$product_id");
    if ($ur && $ur->num_rows > 0) $user_existing_rating = $ur->fetch_assoc();
}

// Image resolution
$imgName = htmlspecialchars($product['image']);
if (!empty($imgName)) {
    if (file_exists($imgName))                      $imagePath = $imgName;
    elseif (file_exists('uploads/' . $imgName))     $imagePath = 'uploads/' . $imgName;
    elseif (file_exists('images/' . $imgName))      $imagePath = 'images/' . $imgName;
    elseif (file_exists('images/products/' . $imgName)) $imagePath = 'images/products/' . $imgName;
    else $imagePath = 'https://via.placeholder.com/500x400?text=No+Image';
} else {
    $imagePath = 'https://via.placeholder.com/500x400?text=No+Image';
}

$avg_rating   = (float)$product['avg_rating'];
$rating_count = (int)$product['rating_count'];
$stock_qty    = (int)$product['stock_quantity'];
?>

<style>
    /* ── Product Detail Layout ─────────────────── */
    .detail-wrap {
        display: flex;
        gap: 3rem;
        margin-top: 1.5rem;
        background: var(--surface);
        padding: 2rem;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        flex-wrap: wrap;
    }

    .detail-img {
        flex: 1;
        min-width: 280px;
    }

    .detail-img img {
        width: 100%;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid var(--border);
    }

    .detail-info {
        flex: 1;
        min-width: 280px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .detail-info h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.4rem;
        color: #1a202c;
    }

    .detail-category {
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-bottom: 0.8rem;
    }

    .detail-price {
        font-size: 2.2rem;
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 1rem;
    }

    .detail-desc {
        line-height: 1.8;
        color: var(--text-primary);
        margin-bottom: 1.5rem;
    }

    /* ── Stock Badge ───────────────────────────── */
    .stock-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
        font-weight: 700;
        padding: 6px 16px;
        border-radius: 50px;
        margin-bottom: 1.2rem;
    }

    .stock-badge.in-stock {
        background: #d1fae5;
        color: #065f46;
    }

    .stock-badge.low-stock {
        background: #fef3c7;
        color: #92400e;
    }

    .stock-badge.out-stock {
        background: #fee2e2;
        color: #991b1b;
    }

    /* ── Star Rating Display ───────────────────── */
    .stars-display {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-bottom: 1.2rem;
    }

    .stars-display .s {
        font-size: 1.4rem;
    }

    .stars-display .avg-text {
        font-size: 0.95rem;
        color: #718096;
        margin-left: 6px;
    }

    /* ── Rating Section ────────────────────────── */
    .ratings-section {
        margin-top: 3rem;
        padding: 2rem;
        background: var(--surface);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    }

    .ratings-section h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #1a202c;
    }

    /* ── Interactive Star Input ────────────────── */
    .star-input {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 4px;
        margin-bottom: 1rem;
    }

    .star-input input[type="radio"] {
        display: none;
    }

    .star-input label {
        font-size: 2rem;
        color: #d1d5db;
        cursor: pointer;
        transition: color 0.2s ease, transform 0.15s ease;
    }

    .star-input label:hover,
    .star-input label:hover~label,
    .star-input input[type="radio"]:checked~label {
        color: #f59e0b;
    }

    .star-input label:hover {
        transform: scale(1.2);
    }

    /* ── Review Cards ──────────────────────────── */
    .review-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .review-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.2rem 1.5rem;
        border-left: 4px solid #667eea;
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .review-username {
        font-weight: 700;
        color: #2d3748;
    }

    .review-date {
        font-size: 0.8rem;
        color: #a0aec0;
    }

    .review-stars {
        color: #f59e0b;
        font-size: 1rem;
        margin-bottom: 0.4rem;
    }

    .review-text {
        color: #4a5568;
        font-size: 0.95rem;
        line-height: 1.6;
    }

    .no-reviews {
        text-align: center;
        color: #a0aec0;
        padding: 2rem;
        font-size: 0.95rem;
    }

    /* ── Rating Alert ──────────────────────────── */
    .rating-alert {
        padding: 0.8rem 1.2rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .rating-alert.success {
        background: #d1fae5;
        color: #065f46;
    }

    .rating-alert.error {
        background: #fee2e2;
        color: #991b1b;
    }

    /* ── Cart Form ─────────────────────────────── */
    .add-cart-form {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .qty-group {
        display: flex;
        flex-direction: column;
    }

    .qty-group label {
        margin-bottom: 0.3rem;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .qty-group input {
        width: 90px;
        padding: 0.55rem;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 1rem;
        text-align: center;
    }
</style>

<!-- Back button -->
<button type="button" class="btn-fill"
    style="padding:0.75rem 1.8rem; font-size:1rem; border-radius:8px; margin-bottom:1rem;"
    onclick="window.location.href='products.php'">
    ← Back to Products
</button>

<!-- ═══════════════════ PRODUCT HERO ═══════════════════ -->
<div class="detail-wrap">
    <!-- Image -->
    <div class="detail-img">
        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
    </div>

    <!-- Info -->
    <div class="detail-info">
        <?php if (isset($success_msg)) echo "<div class='alert alert-success'>$success_msg <a href='cart.php'>View Cart</a></div>"; ?>
        <?php if (isset($cart_error))  echo "<div class='alert alert-error'>$cart_error</div>"; ?>

        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        <p class="detail-category">Category: <?php echo htmlspecialchars($product['category_name']); ?></p>

        <!-- Star Rating Display -->
        <div class="stars-display">
            <?php for ($s = 1; $s <= 5; $s++): ?>
                <span class="s" style="color:<?php echo $s <= round($avg_rating) ? '#f59e0b' : '#d1d5db'; ?>">★</span>
            <?php endfor; ?>
            <span class="avg-text">
                <?php echo $rating_count > 0 ? number_format($avg_rating, 1) . ' / 5 (' . $rating_count . ' review' . ($rating_count > 1 ? 's' : '') . ')' : 'No reviews yet'; ?>
            </span>
        </div>

        <!-- Price -->
        <p class="detail-price">रु <?php echo number_format($product['price']); ?></p>

        <!-- Stock Badge -->
        <?php if ($stock_qty <= 0): ?>
            <span class="stock-badge out-stock">🚫 Out of Stock</span>
        <?php elseif ($stock_qty <= 10): ?>
            <span class="stock-badge low-stock">⚡ Only <?php echo $stock_qty; ?> left – Hurry!</span>
        <?php else: ?>
            <span class="stock-badge in-stock">In Stock (<?php echo $stock_qty; ?> available)</span>
        <?php endif; ?>

        <!-- Description -->
        <div class="detail-desc">
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        </div>

        <!-- Add to Cart Form -->
        <?php if ($stock_qty > 0): ?>
            <form method="POST" action="">
                <div class="add-cart-form">
                    <div class="qty-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1"
                            max="<?php echo $stock_qty; ?>">
                    </div>
                    <button type="submit" name="add_to_cart" class="btn-fill"
                        style="padding: 0.75rem 2rem; font-size: 1.05rem; border-radius: 8px;">
                        Add to Cart 🛒
                    </button>
                </div>
            </form>
        <?php else: ?>
            <p style="color:#e53e3e;font-weight:700;font-size:1rem;">This product is currently out of stock.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════ RATINGS & REVIEWS ═══════════════════ -->
<div class="ratings-section">
    <h2>⭐ Customer Ratings &amp; Reviews</h2>

    <?php echo $rating_msg; ?>

    <!-- Submit Rating Form (logged-in users only) -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <div style="background:#f0f4ff;border-radius:12px;padding:1.5rem;margin-bottom:2rem;border:1px solid #c7d2fe;">
            <h3 style="font-size:1.1rem;margin-bottom:1rem;color:#3730a3;">
                <?php echo $user_existing_rating ? '✏️ Update Your Review' : '📝 Write a Review'; ?>
            </h3>
            <form method="POST" action="">
                <div style="margin-bottom:1rem;">
                    <label style="font-weight:600;display:block;margin-bottom:0.4rem;">Your Rating</label>
                    <div class="star-input">
                        <?php for ($s = 5; $s >= 1; $s--): ?>
                            <?php $checked = ($user_existing_rating && $user_existing_rating['rating'] == $s) ? 'checked' : ''; ?>
                            <input type="radio" id="star<?php echo $s; ?>" name="rating" value="<?php echo $s; ?>" <?php echo $checked; ?>>
                            <label for="star<?php echo $s; ?>" title="<?php echo $s; ?> star<?php echo $s > 1 ? 's' : ''; ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="font-weight:600;display:block;margin-bottom:0.4rem;">Your Review (optional)</label>
                    <textarea name="review" rows="3" placeholder="Share your experience with this product..."
                        style="width:100%;padding:0.75rem;border:1px solid #c7d2fe;border-radius:8px;font-size:0.95rem;resize:vertical;"><?php echo $user_existing_rating ? htmlspecialchars($user_existing_rating['review']) : ''; ?></textarea>
                </div>
                <button type="submit" name="submit_rating" class="btn-fill"
                    style="padding:0.7rem 2rem;border-radius:8px;font-size:0.95rem;">
                    <?php echo $user_existing_rating ? 'Update Review' : 'Submit Review'; ?>
                </button>
            </form>
        </div>
    <?php else: ?>
        <div style="background:#fef3c7;border-radius:10px;padding:1rem 1.5rem;margin-bottom:2rem;color:#92400e;font-weight:600;">
            🔒 <a href="login.php" style="color:#92400e;">Login</a> to leave a review for this product.
        </div>
    <?php endif; ?>

    <!-- All Reviews List -->
    <?php if ($reviews_result && $reviews_result->num_rows > 0): ?>
        <div class="review-list">
            <?php while ($rev = $reviews_result->fetch_assoc()): ?>
                <div class="review-card">
                    <div class="review-header">
                        <span class="review-username">👤 <?php echo htmlspecialchars($rev['user_name']); ?></span>
                        <span class="review-date"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                    </div>
                    <div class="review-stars">
                        <?php for ($s = 1; $s <= 5; $s++) echo $s <= $rev['rating'] ? '★' : '☆'; ?>
                        <span style="font-size:0.85rem;color:#718096;margin-left:4px;"><?php echo $rev['rating']; ?>/5</span>
                    </div>
                    <?php if (!empty(trim($rev['review']))): ?>
                        <p class="review-text">"<?php echo nl2br(htmlspecialchars($rev['review'])); ?>"</p>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-reviews">
            💬 No reviews yet. Be the first to share your thoughts!
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>