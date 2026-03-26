<?php
require_once 'includes/connection.php';
require_once 'includes/header.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    echo "<p>Invalid Product.</p>";
    require_once 'includes/footer.php';
    exit;
}

$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
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

    $user_id = $_SESSION['user_id'];
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    // Check if product already in cart
    $check = $conn->query("SELECT id, quantity FROM cart WHERE user_id = $user_id AND product_id = $product_id");

    if ($check->num_rows > 0) {
        $cart_item = $check->fetch_assoc();
        $new_qty = $cart_item['quantity'] + $quantity;
        $conn->query("UPDATE cart SET quantity = $new_qty WHERE id = " . $cart_item['id']);
    } else {
        $stmt2 = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt2->bind_param("iii", $user_id, $product_id, $quantity);
        $stmt2->execute();
    }

    $success_msg = "Product added to cart successfully.";
}

$imgName = htmlspecialchars($product['image']);
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
        $imagePath = 'https://via.placeholder.com/500x400?text=No+Image';
    }
} else {
    $imagePath = 'https://via.placeholder.com/500x400?text=No+Image';
}
?>

<div style="display: flex; gap: 3rem; margin-top: 2rem; background: var(--surface); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
    <button type="button" class="btn-fill" style="align-self:flex-end; padding:0.8rem 2rem; font-size:1.1rem; border-radius:8px;" onclick="window.location.href='products.php'"> Back To Products </button>
    <div style="flex: 1;">
        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; border-radius: 8px; object-fit: cover; border: 1px solid var(--border);">
    </div>

    <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
        <?php if (isset($success_msg)) echo "<div class='alert alert-success'>$success_msg <a href=\"cart.php\">View Cart</a></div>"; ?>

        <h1 style="font-size: 2rem; font-weight: 600; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($product['name']); ?></h1>
        <p style="color: var(--text-secondary); margin-bottom: 1rem;">Category: <?php echo htmlspecialchars($product['category_name']); ?></p>

        <p style="font-size: 2rem; color: var(--primary); font-weight: bold; margin-bottom: 1.5rem;">$<?php echo number_format($product['price'], 2); ?></p>

        <div style="margin-bottom: 2rem;">
            <p style="line-height: 1.8; color: var(--text-primary);"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        </div>

        <form method="POST" action="" style="display: flex; gap: 1rem; align-items: stretch;">
            <div style="display: flex; flex-direction: column;">
                <label for="quantity" style="margin-bottom: 0.3rem; font-size: 0.9rem; font-weight: 500;">Quantity</label>
                <input type="number" id="quantity" name="quantity" value="1" min="1" style="width: 80px; padding: 0.5rem; border: 1px solid var(--border); border-radius: 8px; font-size: 1rem; text-align: center;">
            </div>

            <button type="submit" name="add_to_cart" class="btn-fill" style="align-self: flex-end; padding: 0.8rem 2rem; font-size: 1.1rem; border-radius: 8px;">Add to Cart 🛒</button>
        </form>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>