<?php
// Handle Status Update FIRST (before any HTML)
if (isset($_GET['update_status']) && isset($_GET['id'])) {
    require_once '../includes/connection.php'; // adjust path if needed

    $id = intval($_GET['id']);
    $status = $_GET['update_status'];

    if (in_array($status, ['pending', 'completed', 'cancelled'])) {
        $conn->query("UPDATE orders SET status = '$status' WHERE id = $id");
        header("Location: manage_orders.php?success=1");
        exit;
    }
}

$page_title = 'Manage Orders';
require_once 'admin_header.php';

// Filters
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$where = "WHERE 1=1";
if ($status_filter) {
    $where .= " AND o.status = '$status_filter'";
}

$sql = "SELECT o.*, u.name as user_name, u.email as user_email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        $where
        ORDER BY o.created_at DESC";
$result = $conn->query($sql);
?>

<div style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">Order Management</h2>
    <p style="color: #d9dce0; font-size: 0.9rem;">Track and manage customer orders and fulfillment.</p>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem;">Order status updated successfully.</div>
<?php endif; ?>

<form class="filter-bar" method="GET" action="">
    <select name="status">
        <option value="">All Statuses</option>
        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
    </select>

    <button type="submit" class="btn-fill" style="padding: 0.6rem 1.5rem; border-radius: 8px;">Filter Orders</button>
    <?php if ($status_filter): ?>
        <a href="manage_orders.php" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">Clear</a>
    <?php endif; ?>
</form>

<style>
    body {
        background-color: #0369a1;
    }
</style>

<div style="overflow-x: auto;">
    <table class="premium-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>

                    <!-- MAIN ORDER ROW -->
                    <tr>
                        <td>
                            <span style="font-weight: 700; color: #1e293b;">
                                #<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?>
                            </span>
                        </td>
                        <td>
                            <div>
                                <div style="font-weight: 600; color: #1e293b;">
                                    <?php echo htmlspecialchars($row['user_name']); ?>
                                </div>
                                <div style="font-size: 0.75rem; color: #94a3b8;">
                                    <?php echo htmlspecialchars($row['user_email']); ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem; color: #64748b;">
                                <?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-weight: 700; color: var(--admin-primary);">
                                $<?php echo number_format($row['total_price'], 2); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">

                                <!-- VIEW BUTTON -->
                                <a href="manage_orders.php?view=<?php echo $row['id']; ?>" class="btn-action" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <?php if ($row['status'] == 'pending'): ?>
                                    <a href="manage_orders.php?update_status=completed&id=<?php echo $row['id']; ?>" class="btn-action" title="Mark as Completed" style="color: #059669; border-color: #059669;">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="manage_orders.php?update_status=cancelled&id=<?php echo $row['id']; ?>" class="btn-action delete" title="Cancel Order">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php else: ?>
                                    <!-- <span style="font-size: 0.75rem; color: #94a3b8; font-style: italic;">No actions</span> -->
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <!-- ORDER DETAILS ROW -->
                    <?php if (isset($_GET['view']) && $_GET['view'] == $row['id']): ?>
                        <tr>
                            <td colspan="6" style="background: #f8fafc;">

                                <strong>Checkout Details:</strong><br>
                                <div style="margin-bottom:10px;">
                                    <b>Customer:</b> <?php echo htmlspecialchars($row['user_name']); ?><br>
                                    <b>Email:</b> <?php echo htmlspecialchars($row['user_email']); ?><br>
                                    <b>Contact:</b> <?php echo htmlspecialchars($row['contact_number']); ?><br>

                                    <b>Address:</b> <?php echo htmlspecialchars($row['address']); ?><br>
                                    <b>Order Date:</b> <?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?><br>
                                </div>
                                <strong>Order Items:</strong><br>

                                <?php
                                $order_id = $row['id'];
                                $items_sql = "SELECT oi.*, p.name 
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = $order_id";


                                $items_result = $conn->query($items_sql);

                                if ($items_result->num_rows > 0):
                                    while ($item = $items_result->fetch_assoc()):
                                ?>
                                        <div style="margin-bottom: 5px;">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                            — Qty: <?php echo $item['quantity']; ?>
                                            — Price: $<?php echo number_format($item['price'], 2); ?>
                                        </div>
                                    <?php endwhile;
                                else: ?>
                                    No items found.
                                <?php endif; ?>

                            </td>
                        </tr>
                    <?php endif; ?>

                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 3rem; color: #94a3b8;">
                        <i class="fas fa-receipt" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                        No orders found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'admin_footer.php'; ?>