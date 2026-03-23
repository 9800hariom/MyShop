<?php
require_once 'includes/connection.php';
require_once 'includes/header.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch Cart Items for Summary
$sql = "SELECT c.quantity, p.id as product_id, p.price 
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

// Handle Order Placement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $address = sanitize_input($_POST['address']);
    $payment_method = sanitize_input($_POST['payment_method']);
    
    if (empty($address)) {
        $error = "Shipping address is required.";
    } elseif ($payment_method !== 'COD') {
        $error = "Currently only Cash on Delivery (COD) is supported.";
    } else {
        // Create Order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status, address) VALUES (?, ?, 'pending', ?)");
        $stmt->bind_param("ids", $user_id, $total_price, $address);
        
        if ($stmt->execute()) {
            $order_id = $stmt->insert_id;
            
            // Insert Order Items
            $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cart_items as $item) {
                $pid = $item['product_id'];
                $qty = $item['quantity'];
                $price = $item['price'];
                $stmt_items->bind_param("iiid", $order_id, $pid, $qty, $price);
                $stmt_items->execute();
            }
            
            // Clear cart
            $conn->query("DELETE FROM cart WHERE user_id = $user_id");
            
            header("Location: user_orders.php?success=1");
            exit;
        } else {
            $error = "Failed to place order.";
        }
    }
}
?>

<div style="display: flex; gap: 2rem; max-width: 1000px; margin: 0 auto;">
    <div style="flex: 2;">
        <h2 class="page-title">Checkout</h2>
        <div class="form-container" style="max-width: 100%; margin: 0;">
            <?php if (isset($error)) echo "<div class='alert alert-error'>$error</div>"; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Shipping Address</label>
                    <textarea name="address" required rows="4" placeholder="Enter your full shipping address"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" required>
                        <option value="COD">Cash on Delivery (COD)</option>
                    </select>
                </div>
                
                <button type="submit" name="place_order" class="btn-submit" style="font-size: 1.1rem; padding: 1rem;">Confirm & Place Order</button>
            </form>
        </div>
    </div>
    
    <div style="flex: 1;">
        <div class="cart-summary" style="position: sticky; top: 100px;">
            <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1rem; text-align: left;">Order Summary</h3>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 1.1rem;">
                <span>Total Items:</span>
                <span style="font-weight: 500;"><?php echo count($cart_items); ?></span>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 1.5rem; color: var(--primary); font-weight: bold;">
                <span>Total:</span>
                <span>$<?php echo number_format($total_price, 2); ?></span>
            </div>
            
            <p style="font-size: 0.9rem; color: var(--text-secondary); text-align: left; margin-top: 1rem;">
                Shipping and taxes calculated during checkout. Please review your order details before placing the order.
            </p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
