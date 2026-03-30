<?php
require_once 'includes/connection.php';
require_once 'includes/functions.php';

session_start();

/* =========================
   AUTH CHECK (MUST BE FIRST)
========================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* =========================
   FETCH CART ITEMS
========================= */
$sql = "SELECT c.quantity, p.id as product_id, p.name as product_name, p.price, p.stock_quantity
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = $user_id";

$result = $conn->query($sql);

$total_price = 0;
$cart_items = [];

if ($result && $result->num_rows > 0) {
    while ($item = $result->fetch_assoc()) {
        $cart_items[] = $item;
        $total_price += ($item['price'] * $item['quantity']);
    }
} else {
    header("Location: cart.php");
    exit;
}

/* =========================
   PLACE ORDER LOGIC
========================= */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {

    $address = sanitize_input($_POST['address']);
    $contact_number = sanitize_input($_POST['contact_number']);
    $payment_method = sanitize_input($_POST['payment_method']);

    if (empty($address) || empty($contact_number)) {
        $error = "All fields are required.";
    } elseif ($payment_method !== 'COD') {
        $error = "Only Cash on Delivery is allowed.";
    } else {

        // =============================
        // STOCK VALIDATION BEFORE ORDER
        // =============================
        $stock_error = null;
        foreach ($cart_items as $item) {
            if ($item['quantity'] > $item['stock_quantity']) {
                $available = (int)$item['stock_quantity'];
                $stock_error = "Sorry, only <strong>$available</strong> unit(s) of <strong>" . htmlspecialchars($item['product_name']) . "</strong> are available.";
                break;
            }
        }

        if ($stock_error) {
            $error = $stock_error;
        } else {
            // INSERT ORDER
            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, total_price, status, address, contact_number)
                VALUES (?, ?, 'pending', ?, ?)
            ");

            $stmt->bind_param("idss", $user_id, $total_price, $address, $contact_number);

            if ($stmt->execute()) {

                $order_id = $stmt->insert_id;

                // INSERT ORDER ITEMS & DEDUCT STOCK
                $stmt_items = $conn->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt_stock = $conn->prepare("
                    UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?
                ");

                foreach ($cart_items as $item) {
                    // Insert order item
                    $stmt_items->bind_param(
                        "iiid",
                        $order_id,
                        $item['product_id'],
                        $item['quantity'],
                        $item['price']
                    );
                    $stmt_items->execute();

                    // Deduct stock
                    $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                    $stmt_stock->execute();
                }

                // CLEAR CART
                $conn->query("DELETE FROM cart WHERE user_id = $user_id");

                // REDIRECT (SAFE NOW)
                header("Location: user_orders.php?success=1");
                exit;
            } else {
                $error = "Failed to place order.";
            }
        }
    }
}
?>

<!-- =========================
     HEADER (AFTER LOGIC)
========================= -->
<?php require_once 'includes/header.php'; ?>

<!-- BACK BUTTON -->
<button type="button" class="btn-fill"
    style="align-self:flex-start; padding:0.8rem 2rem; font-size:1.1rem; border-radius:8px;"
    onclick="window.location.href='cart.php'">
    Back To Cart
</button>

<div style="display: flex; gap: 2rem; max-width: 1000px; margin: 0 auto;">

    <!-- LEFT SIDE FORM -->
    <div style="flex: 2;">
        <h2 class="page-title">Checkout</h2>

        <div class="form-container" style="max-width: 100%; margin: 0;">

            <?php if (isset($error)) : ?>
                <div class='alert alert-error'><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">

                <div class="form-group">
                    <label>Shipping Address</label>
                    <textarea name="address" required rows="4"
                        placeholder="Enter your full shipping address"></textarea>
                </div>

                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" required
                        placeholder="Enter your phone number">
                </div>

                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" required>
                        <option value="COD">Cash on Delivery (COD)</option>
                    </select>
                </div>

                <button type="submit" name="place_order" class="btn-submit"
                    style="font-size: 1.1rem; padding: 1rem;">
                    Confirm &amp; Place Order
                </button>

            </form>

        </div>
    </div>

    <!-- RIGHT SIDE SUMMARY -->
    <div style="flex: 1;">
        <div class="cart-summary" style="position: sticky; top: 100px;">

            <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                Order Summary
            </h3>

            <?php foreach ($cart_items as $item): ?>
                <div style="display:flex;justify-content:space-between;margin-bottom:0.6rem;font-size:0.9rem;color:#555;">
                    <span><?php echo htmlspecialchars($item['product_name']); ?> × <?php echo $item['quantity']; ?></span>
                    <span>रु <?php echo number_format($item['price'] * $item['quantity'], 0); ?></span>
                </div>
                <!-- Stock warning -->
                <?php if ($item['quantity'] > $item['stock_quantity']): ?>
                    <div style="color:#e53e3e;font-size:0.8rem;margin-bottom:0.5rem;">
                        ⚠️ Only <?php echo (int)$item['stock_quantity']; ?> in stock!
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <div style="display:flex;justify-content:space-between;margin-bottom:1rem;border-top:1px solid var(--border);padding-top:0.8rem;">
                <span>Total Items:</span>
                <span><?php echo count($cart_items); ?></span>
            </div>

            <div style="display:flex;justify-content:space-between;margin-bottom:1rem;font-size:1.3rem;color:var(--primary);">
                <span>Total:</span>
                <span>रु <?php echo number_format($total_price, 0); ?></span>
            </div>

        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>