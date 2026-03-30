<?php
require_once 'includes/connection.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* ─────────────────────────────────────────────────────────────
   USER CANCELS THEIR OWN ORDER  →  Restore stock automatically
───────────────────────────────────────────────────────────── */
if (isset($_GET['cancel_order'])) {
    $order_id = intval($_GET['cancel_order']);

    // Verify the order belongs to this user AND is still pending
    $check = $conn->query(
        "SELECT id, status FROM orders
         WHERE id = $order_id AND user_id = $user_id AND status = 'pending'"
    );

    if ($check && $check->num_rows > 0) {

        // 1. Fetch items first (for the restore message)
        $items = $conn->query(
            "SELECT oi.quantity, p.name as product_name
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = $order_id"
        );
        $restored_items = [];
        while ($item = $items->fetch_assoc()) {
            $restored_items[] = $item;
        }

        // 2. Restore stock via single JOIN UPDATE
        $conn->query(
            "UPDATE products p
             INNER JOIN order_items oi ON oi.product_id = p.id
             SET p.stock_quantity = p.stock_quantity + oi.quantity
             WHERE oi.order_id = $order_id"
        );

        // 3. Update order status
        $conn->query("UPDATE orders SET status = 'cancelled' WHERE id = $order_id");

        // 4. Build restore summary for display
        $restore_msg = implode(', ', array_map(function ($it) {
            return htmlspecialchars($it['product_name']) . ' (+' . $it['quantity'] . ')';
        }, $restored_items));

        header("Location: user_orders.php?cancelled=1&restored=" . urlencode($restore_msg));
        exit;
    } else {
        header("Location: user_orders.php?error=1");
        exit;
    }
}

// Fetch Orders
$sql    = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC";
$result = $conn->query($sql);

require_once 'includes/header.php';
?>

<style>
    .order-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        margin-bottom: 2rem;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        transition: box-shadow 0.3s ease;
    }

    .order-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.09);
    }

    .order-header {
        background: #f3f4f6;
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
        border-bottom: 1px solid var(--border);
    }

    .order-meta-group {
        display: flex;
        flex-direction: column;
    }

    .order-meta-label {
        font-size: 0.75rem;
        color: var(--text-secondary);
        text-transform: uppercase;
        font-weight: 600;
    }

    .order-meta-value {
        font-weight: 600;
        font-size: 0.95rem;
    }

    /* Status badges */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.8rem;
        font-weight: 700;
        padding: 4px 14px;
        border-radius: 50px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-pill.pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-pill.completed {
        background: #d1fae5;
        color: #065f46;
    }

    .status-pill.cancelled {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Cancel button */
    .btn-cancel-order {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.45rem 1.1rem;
        background: #fff;
        color: #dc2626;
        border: 1.5px solid #dc2626;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-cancel-order:hover {
        background: #dc2626;
        color: #fff;
    }

    /* Restored badge */
    .restored-badge {
        display: inline-block;
        font-size: 0.72rem;
        background: #ecfdf5;
        color: #059669;
        padding: 2px 8px;
        border-radius: 20px;
        font-weight: 600;
        margin-left: 6px;
    }
</style>

<div style="max-width: 1000px; margin: 0 auto;">
    <h2 class="page-title">My Orders</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">🎉 Order placed successfully! We will process it shortly.</div>
    <?php endif; ?>

    <?php if (isset($_GET['cancelled'])): ?>
        <div class="alert alert-success">
            ✅ <strong>Order cancelled successfully!</strong><br>
            <?php if (!empty($_GET['restored'])): ?>
                <span style="font-size:0.9rem;">
                    🔄 Stock restored to inventory:<br>
                    <strong><?php echo htmlspecialchars($_GET['restored']); ?></strong>
                </span>
            <?php else: ?>
                <span style="font-size:0.9rem;">Stock quantities have been automatically restored to inventory.</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">❌ Could not cancel order. It may already be processed.</div>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($order = $result->fetch_assoc()): ?>
            <div class="order-card">

                <!-- Header row -->
                <div class="order-header">
                    <div class="order-meta-group">
                        <span class="order-meta-label">Order Placed</span>
                        <span class="order-meta-value"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="order-meta-group">
                        <span class="order-meta-label">Total</span>
                        <span class="order-meta-value">रु <?php echo number_format($order['total_price'], 0); ?></span>
                    </div>
                    <div class="order-meta-group">
                        <span class="order-meta-label">Status</span>
                        <span class="status-pill <?php echo $order['status']; ?>">
                            <?php
                            $icons = ['pending' => '⏳', 'completed' => '✅', 'cancelled' => '❌'];
                            echo ($icons[$order['status']] ?? '') . ' ' . ucfirst($order['status']);
                            if ($order['status'] === 'cancelled') {
                                echo '<span class="restored-badge">Stock Restored</span>';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="order-meta-group">
                        <!-- <span class="order-meta-label">Order #</span>
                        <span class="order-meta-value"><?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span> -->
                    </div>

                    <!-- Cancel button (only for pending orders) -->
                    <?php if ($order['status'] === 'pending'): ?>
                        <div>
                            <a href="user_orders.php?cancel_order=<?php echo $order['id']; ?>"
                                class="btn-cancel-order"
                                onclick="return confirm('Cancel this order? Your stock will be restored automatically.')">
                                ✕ Cancel Order
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Items -->
                <div style="padding: 1.5rem;">
                    <h4 style="margin-bottom: 1rem; font-size:1rem; color:#374151;">🛍️ Items in this Order</h4>
                    <table style="width: 100%; border-collapse: collapse;">
                        <?php
                        $oid = $order['id'];
                        $items_sql = "SELECT oi.quantity, oi.price, p.name, p.image
                                  FROM order_items oi
                                  JOIN products p ON oi.product_id = p.id
                                  WHERE oi.order_id = $oid";
                        $items_result = $conn->query($items_sql);
                        if ($items_result && $items_result->num_rows > 0) {
                            while ($item = $items_result->fetch_assoc()) {
                                $imgName = htmlspecialchars($item['image']);
                                if (!empty($imgName)) {
                                    if (file_exists($imgName))                       $imagePath = $imgName;
                                    elseif (file_exists('uploads/' . $imgName))          $imagePath = 'uploads/' . $imgName;
                                    elseif (file_exists('images/' . $imgName))           $imagePath = 'images/' . $imgName;
                                    elseif (file_exists('images/products/' . $imgName))  $imagePath = 'images/products/' . $imgName;
                                    else    $imagePath = 'https://via.placeholder.com/60?text=Img';
                                } else {
                                    $imagePath = 'https://via.placeholder.com/60?text=Img';
                                }

                                echo '<tr style="border-bottom: 1px solid var(--border);">';
                                echo '<td style="padding: 0.85rem 0; width: 80px;"><img src="' . $imagePath . '" alt="Product" style="width:60px;height:60px;object-fit:cover;border-radius:8px;"></td>';
                                echo '<td style="padding: 0.85rem 1rem; font-weight: 500;">' . htmlspecialchars($item['name']) . '</td>';
                                echo '<td style="padding: 0.85rem; text-align: center; color: var(--text-secondary);">Qty: ' . $item['quantity'] . '</td>';
                                echo '<td style="padding: 0.85rem; text-align: right; font-weight: 600;">रु ' . number_format($item['price'], 0) . '</td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </table>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem; background: var(--surface); border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <p style="font-size: 3rem; margin-bottom: 0.5rem;">🛒</p>
            <p style="font-size: 1.2rem; margin-bottom: 1rem; font-weight: 600;">You haven't placed any orders yet.</p>
            <a href="products.php" class="btn-fill" style="text-decoration:none; padding:0.8rem 2rem; border-radius:8px;">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>