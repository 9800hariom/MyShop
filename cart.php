<?php
require_once 'includes/connection.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ── Handle Remove Item ────────────────────────────────────────
if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    $conn->query("DELETE FROM cart WHERE id = $cart_id AND user_id = $user_id");
    header("Location: cart.php?updated=1");
    exit;
}

// ── Handle Update Quantity (POST) ─────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['quantity'])) {
    foreach ($_POST['quantity'] as $cart_id => $qty) {
        $cart_id = (int)$cart_id;
        $qty     = (int)$qty;
        if ($qty > 0) {
            // Clamp to available stock
            $stock_r = $conn->query(
                "SELECT p.stock_quantity FROM cart c
                 JOIN products p ON c.product_id = p.id
                 WHERE c.id = $cart_id AND c.user_id = $user_id"
            );
            if ($stock_r && $stock_r->num_rows > 0) {
                $stock = (int)$stock_r->fetch_assoc()['stock_quantity'];
                $qty   = min($qty, $stock);
            }
            $conn->query("UPDATE cart SET quantity = $qty WHERE id = $cart_id AND user_id = $user_id");
        } else {
            $conn->query("DELETE FROM cart WHERE id = $cart_id AND user_id = $user_id");
        }
    }
    header("Location: cart.php?updated=1");
    exit;
}

// ── Fetch Cart Items ──────────────────────────────────────────
$sql = "SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name,
               p.price, p.image, p.stock_quantity
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = $user_id";
$result = $conn->query($sql);

$total_price = 0;
$cart_rows   = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cart_rows[] = $row;
        $total_price += $row['price'] * $row['quantity'];
    }
}
?>

<style>
    /* ── Cart Table ──────────────────────────────── */
    .cart-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    }

    .cart-table thead tr {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: #fff;
    }

    .cart-table thead th {
        padding: 1rem 1.2rem;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        font-weight: 700;
    }

    .cart-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s ease;
    }

    .cart-table tbody tr:hover {
        background: #f8faff;
    }

    .cart-table tbody td {
        padding: 1rem 1.2rem;
        vertical-align: middle;
        color: #374151;
    }

    .cart-table tbody img {
        width: 64px;
        height: 64px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }

    /* ── Quantity Controls ───────────────────────── */
    .qty-wrap {
        display: flex;
        align-items: center;
        gap: 0;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        width: fit-content;
    }

    .qty-btn {
        width: 30px;
        height: 34px;
        background: #f1f5f9;
        border: none;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        color: #374151;
        transition: background 0.15s;
        line-height: 1;
    }

    .qty-btn:hover {
        background: #667eea;
        color: #fff;
    }

    .qty-input {
        width: 46px;
        height: 34px;
        text-align: center;
        border: none;
        border-left: 1.5px solid #e2e8f0;
        border-right: 1.5px solid #e2e8f0;
        font-size: 0.95rem;
        font-weight: 600;
        color: #1a202c;
        outline: none;
        background: #fff;
    }

    /* Hide arrows on number input */
    .qty-input::-webkit-outer-spin-button,
    .qty-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .qty-input[type=number] {
        -moz-appearance: textfield;
    }

    /* ── Subtotal cell ───────────────────────────── */
    .subtotal-cell {
        font-weight: 700;
        color: #764ba2;
        font-size: 0.95rem;
    }

    /* ── Remove link ─────────────────────────────── */
    .remove-btn {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 6px;
        color: #ef4444;
        border: 1.5px solid #ef4444;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .remove-btn:hover {
        background: #ef4444;
        color: #fff;
    }

    /* ── Update Cart button ──────────────────────── */
    .btn-update {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.75rem 1.8rem;
        background: #fff;
        color: #667eea;
        border: 2px solid #667eea;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-update:hover {
        background: #667eea;
        color: #fff;
    }

    /* ── Cart Summary ────────────────────────────── */
    .cart-summary-box {
        background: #fff;
        border-radius: 14px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        min-width: 260px;
    }

    .cart-summary-box h3 {
        font-size: 1.05rem;
        color: #374151;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.6rem;
        font-size: 0.93rem;
        color: #4b5563;
    }

    .summary-total {
        display: flex;
        justify-content: space-between;
        font-size: 1.2rem;
        font-weight: 800;
        color: #764ba2;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 2px solid #e2e8f0;
    }

    /* ── Updated toast ───────────────────────────── */
    .cart-toast {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #d1fae5;
        color: #065f46;
        border-left: 4px solid #059669;
        padding: 0.75rem 1.2rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-weight: 600;
        font-size: 0.9rem;
        animation: fadeInDown 0.4s ease;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<h2 class="page-title">🛒 Your Cart</h2>

<?php if (isset($_GET['updated'])): ?>
    <div class="cart-toast">✅ Cart updated successfully!</div>
<?php endif; ?>

<?php if (!empty($cart_rows)): ?>
    <form action="" method="POST" id="cartForm">
        <table class="cart-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Remove</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_rows as $item):
                    $subtotal = $item['price'] * $item['quantity'];
                    $imgName  = htmlspecialchars($item['image']);
                    if (!empty($imgName)) {
                        if (file_exists($imgName))                       $imagePath = $imgName;
                        elseif (file_exists('uploads/' . $imgName))          $imagePath = 'uploads/' . $imgName;
                        elseif (file_exists('images/' . $imgName))           $imagePath = 'images/' . $imgName;
                        elseif (file_exists('images/products/' . $imgName))  $imagePath = 'images/products/' . $imgName;
                        else    $imagePath = 'https://via.placeholder.com/64?text=Img';
                    } else {
                        $imagePath = 'https://via.placeholder.com/64?text=Img';
                    }
                    $maxQty = (int)$item['stock_quantity'];
                ?>
                    <tr id="cart-row-<?php echo $item['cart_id']; ?>">
                        <td><img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>"></td>
                        <td>
                            <a href="product_details.php?id=<?php echo $item['product_id']; ?>"
                                style="font-weight:600;color:#374151;text-decoration:none;">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                            <?php if ($maxQty < 10 && $maxQty > 0): ?>
                                <div style="font-size:0.75rem;color:#d97706;margin-top:3px;">
                                    ⚡ Only <?php echo $maxQty; ?> in stock
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:600;">रु <?php echo number_format($item['price']); ?></td>
                        <td>
                            <!-- ± Quantity stepper -->
                            <div class="qty-wrap">
                                <button type="button" class="qty-btn"
                                    onclick="changeQty(<?php echo $item['cart_id']; ?>, -1, <?php echo $maxQty; ?>)">−</button>
                                <input type="number"
                                    class="qty-input"
                                    id="qty-<?php echo $item['cart_id']; ?>"
                                    name="quantity[<?php echo $item['cart_id']; ?>]"
                                    value="<?php echo $item['quantity']; ?>"
                                    min="1"
                                    max="<?php echo $maxQty; ?>"
                                    oninput="recalcRow(<?php echo $item['cart_id']; ?>, <?php echo $item['price']; ?>, <?php echo $maxQty; ?>)">
                                <button type="button" class="qty-btn"
                                    onclick="changeQty(<?php echo $item['cart_id']; ?>, 1, <?php echo $maxQty; ?>)">+</button>
                            </div>
                        </td>
                        <td class="subtotal-cell" id="sub-<?php echo $item['cart_id']; ?>">
                            रु <?php echo number_format($subtotal); ?>
                        </td>
                        </td>
                        <td>
                            <a href="cart.php?remove=<?php echo $item['cart_id']; ?>"
                                class="remove-btn"
                                title="Remove item"
                                onclick="return confirm('Remove this item from cart?')">
                                Remove
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Bottom bar -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-top:1.5rem; flex-wrap:wrap; gap:1rem;">

            <!-- Left: Update Cart button -->
            <button type="submit" class="btn-update" id="updateCartBtn">
                <i class="fas fa-sync-alt"></i> Update Cart
            </button>

            <!-- Right: Summary box -->
            <div class="cart-summary-box">
                <h3>🧾 Order Summary</h3>
                <div class="summary-row">
                    <span>Items (<?php echo count($cart_rows); ?>)</span>
                    <span id="item-count"><?php echo count($cart_rows); ?></span>
                </div>
                <div class="summary-total">
                    <span>Total</span>
                    <span id="cart-total">रु <?php echo number_format($total_price); ?></span>
                </div>
                <a href="checkout.php" class="btn-fill"
                    style="display:block;text-align:center;text-decoration:none;margin-top:1rem;border-radius:8px;padding:0.9rem;">
                    Proceed to Checkout →
                </a>
            </div>
        </div>
    </form>

<?php else: ?>
    <div style="text-align:center;padding:5rem 2rem;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,0.06);">
        <p style="font-size:3.5rem;margin-bottom:0.5rem;">🛒</p>
        <p style="font-size:1.2rem;font-weight:600;margin-bottom:1rem;color:#374151;">Your cart is empty.</p>
        <a href="products.php" class="btn-fill" style="text-decoration:none;border-radius:8px;padding:0.85rem 2rem;">
            Start Shopping →
        </a>
    </div>
<?php endif; ?>

<script>
    // Price map keyed by cart_id for live recalc
    const prices = {
        <?php foreach ($cart_rows as $item): ?>
            <?php echo $item['cart_id']; ?>: <?php echo $item['price']; ?>,
        <?php endforeach; ?>
    };

    function changeQty(cartId, delta, maxStock) {
        const input = document.getElementById('qty-' + cartId);
        let val = parseInt(input.value) + delta;
        val = Math.max(1, Math.min(val, maxStock));
        input.value = val;
        recalcRow(cartId, prices[cartId], maxStock);
    }

    function recalcRow(cartId, price, maxStock) {
        const input = document.getElementById('qty-' + cartId);
        let qty = parseInt(input.value) || 1;
        qty = Math.max(1, Math.min(qty, maxStock));
        input.value = qty;

        const subtotal = (price * qty).toFixed(2);
        document.getElementById('sub-' + cartId).textContent = '$' + subtotal;

        // Recalculate grand total
        let total = 0;
        Object.keys(prices).forEach(function(id) {
            const inp = document.getElementById('qty-' + id);
            if (inp) total += prices[id] * parseInt(inp.value || 1);
        });
        document.getElementById('cart-total').textContent = '$' + total.toFixed(2);

        // Highlight the Update Cart button
        const btn = document.getElementById('updateCartBtn');
        btn.style.background = '#667eea';
        btn.style.color = '#fff';
        btn.innerHTML = '<i class="fas fa-sync-alt"></i> Update Cart ●';
    }
</script>

<?php require_once 'includes/footer.php'; ?>