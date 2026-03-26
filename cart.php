<?php
require_once 'includes/connection.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Remove Item
if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    $conn->query("DELETE FROM cart WHERE id = $cart_id AND user_id = $user_id");
    header("Location: cart.php");
    exit;
}

// Handle Update Quantity
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $cart_id => $qty) {
        $cart_id = intval($cart_id);
        $qty = intval($qty);
        if ($qty > 0) {
            $conn->query("UPDATE cart SET quantity = $qty WHERE id = $cart_id AND user_id = $user_id");
        } else {
            $conn->query("DELETE FROM cart WHERE id = $cart_id AND user_id = $user_id");
        }
    }
    header("Location: cart.php");
    exit;
}

// Fetch Cart Items
$sql = "SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.image 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = $user_id";
$result = $conn->query($sql);

$total_price = 0;
?>

<h2 class="page-title">Your Cart</h2>

<?php if ($result && $result->num_rows > 0): ?>
    <form action="" method="POST">
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $result->fetch_assoc()): 
                    $subtotal = $item['price'] * $item['quantity'];
                    $total_price += $subtotal;
                    $imgName = htmlspecialchars($item['image']);
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
                            $imagePath = 'https://via.placeholder.com/60?text=Img';
                        }
                    } else {
                        $imagePath = 'https://via.placeholder.com/60?text=Img';
                    }
                ?>
                <tr>
                    <td><img src="<?php echo $imagePath; ?>" alt="Product"></td>
                    <td><a href="product_details.php?id=<?php echo $item['product_id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a></td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td>
                        <input type="number" name="quantity[<?php echo $item['cart_id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" style="width: 60px; padding: 0.3rem;">
                    </td>
                    <td>$<?php echo number_format($subtotal, 2); ?></td>
                    <td>
                        <a href="cart.php?remove=<?php echo $item['cart_id']; ?>" style="color: var(--danger); text-decoration:none;">Remove</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <button type="submit" name="update_cart" class="btn-outline">Update Cart</button>
            
            <div class="cart-summary">
                <h3>Total: $<?php echo number_format($total_price, 2); ?></h3>
                <a href="checkout.php" class="btn-fill" style="display:inline-block; text-decoration:none; margin-top: 1rem;">Proceed to Checkout</a>
            </div>
        </div>
    </form>
<?php else: ?>
    <div style="text-align: center; padding: 4rem;">
        <p style="font-size: 1.2rem; margin-bottom: 1rem;">Your cart is empty.</p>
        <a href="products.php" class="btn-fill" style="text-decoration:none;">Continue Shopping</a>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
