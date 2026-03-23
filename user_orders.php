<?php
require_once 'includes/connection.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch Orders
$sql = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<div style="max-width: 1000px; margin: 0 auto;">
    <h2 class="page-title">My Orders</h2>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Order placed successfully! We will process it shortly.</div>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($order = $result->fetch_assoc()): ?>
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 2rem; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <div style="background: #F3F4F6; padding: 1rem 1.5rem; display: flex; justify-content: space-between; border-bottom: 1px solid var(--border);">
                    <div>
                        <p style="font-size: 0.9rem; color: var(--text-secondary);">ORDER PLACED</p>
                        <p style="font-weight: 500;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.9rem; color: var(--text-secondary);">TOTAL</p>
                        <p style="font-weight: 500;">$<?php echo number_format($order['total_price'], 2); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.9rem; color: var(--text-secondary);">STATUS</p>
                        <span style="font-weight: 600; text-transform: uppercase; color: <?php echo $order['status'] == 'completed' ? '#059669' : ($order['status'] == 'cancelled' ? 'var(--danger)' : 'var(--primary)'); ?>;">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </div>
                    <div>
                        <p style="font-size: 0.9rem; color: var(--text-secondary);">ORDER #</p>
                        <p style="font-weight: 500;"><?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
                    </div>
                </div>
                
                <div style="padding: 1.5rem;">
                    <h4 style="margin-bottom: 1rem;">Items in this Order</h4>
                    <table style="width: 100%; border-collapse: collapse;">
                        <?php
                            $order_id = $order['id'];
                            $items_sql = "SELECT oi.quantity, oi.price, p.name, p.image 
                                          FROM order_items oi 
                                          JOIN products p ON oi.product_id = p.id 
                                          WHERE oi.order_id = $order_id";
                            $items_result = $conn->query($items_sql);
                            if ($items_result && $items_result->num_rows > 0) {
                                while ($item = $items_result->fetch_assoc()) {
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
                                    
                                    echo '<tr style="border-bottom: 1px solid var(--border);">';
                                    echo '<td style="padding: 1rem 0; width: 80px;"><img src="' . $imagePath . '" alt="Product" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;"></td>';
                                    echo '<td style="padding: 1rem; font-weight: 500;">' . htmlspecialchars($item['name']) . '</td>';
                                    echo '<td style="padding: 1rem; text-align: center; color: var(--text-secondary);">Qty: ' . $item['quantity'] . '</td>';
                                    echo '<td style="padding: 1rem; text-align: right; font-weight: 500;">$' . number_format($item['price'], 2) . '</td>';
                                    echo '</tr>';
                                }
                            }
                        ?>
                    </table>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem; background: var(--surface); border-radius: 12px;">
            <p style="font-size: 1.2rem; margin-bottom: 1rem;">You haven't placed any orders yet.</p>
            <a href="products.php" class="btn-fill" style="text-decoration:none;">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
