<?php
// Handle Status Update FIRST (before any HTML)
if (isset($_GET['update_status']) && isset($_GET['id'])) {
    require_once '../includes/connection.php';

    $id        = intval($_GET['id']);
    $newStatus = $_GET['update_status'];

    if (in_array($newStatus, ['pending', 'completed', 'cancelled'])) {

        // Get current status
        $cur      = $conn->query("SELECT status FROM orders WHERE id = $id");
        $oldRow   = $cur->fetch_assoc();
        $oldStatus = $oldRow ? $oldRow['status'] : '';

        // ── RESTORE STOCK when cancelling ────────────────────────────────
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            $items = $conn->query(
                "SELECT product_id, quantity FROM order_items WHERE order_id = $id"
            );
            while ($item = $items->fetch_assoc()) {
                $conn->query(
                    "UPDATE products
                     SET stock_quantity = stock_quantity + {$item['quantity']}
                     WHERE id = {$item['product_id']}"
                );
            }
        }

        // ── DEDUCT STOCK when un-cancelling ──────────────────────────────
        if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
            $items = $conn->query(
                "SELECT product_id, quantity FROM order_items WHERE order_id = $id"
            );
            while ($item = $items->fetch_assoc()) {
                $conn->query(
                    "UPDATE products
                     SET stock_quantity = GREATEST(0, stock_quantity - {$item['quantity']})
                     WHERE id = {$item['product_id']}"
                );
            }
        }

        $conn->query("UPDATE orders SET status = '$newStatus' WHERE id = $id");
        header("Location: manage_orders.php?success=Status updated successfully.");
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

<style>
    /* ── Page Header ─────────────────────────────── */
    .orders-header {
        margin-bottom: 1.5rem;
    }

    .orders-header h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
    }

    .orders-header p {
        color: #94a3b8;
        font-size: 0.88rem;
        margin-top: 3px;
    }

    /* ── Details Accordion Row ───────────────────── */
    .detail-row {
        display: none;
    }

    .detail-row.open {
        display: table-row;
    }

    .detail-panel {
        background: linear-gradient(135deg, #f0f9ff, #e8f5ff);
        border-top: 2px solid #bfdbfe;
        padding: 0 !important;
    }

    .detail-inner {
        padding: 1.5rem 2rem;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem 2.5rem;
    }

    @media (max-width: 768px) {
        .detail-inner {
            grid-template-columns: 1fr;
        }
    }

    /* ── Info section inside panel ───────────────── */
    .detail-section h4 {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .detail-section h4::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #bfdbfe;
    }

    .info-row {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 0.4rem;
        font-size: 0.88rem;
    }

    .info-label {
        font-weight: 600;
        color: #374151;
        min-width: 80px;
    }

    .info-value {
        color: #4b5563;
    }

    /* ── Items Table inside panel ─────────────────── */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
        margin-top: 0.5rem;
    }

    .items-table thead tr {
        background: #dbeafe;
    }

    .items-table thead th {
        padding: 0.55rem 0.75rem;
        text-align: left;
        font-weight: 700;
        color: #1e40af;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .items-table tbody tr:nth-child(even) {
        background: rgba(219, 234, 254, 0.3);
    }

    .items-table tbody td {
        padding: 0.6rem 0.75rem;
        color: #374151;
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
    }

    .items-table tfoot td {
        padding: 0.6rem 0.75rem;
        font-weight: 700;
        color: #1e40af;
        border-top: 2px solid #bfdbfe;
    }

    /* ── Stock Restored Notice ───────────────────── */
    .stock-restored-notice {
        margin: 0 2rem 1rem;
        padding: 0.65rem 1rem;
        background: #d1fae5;
        border-left: 4px solid #059669;
        border-radius: 6px;
        font-size: 0.83rem;
        color: #065f46;
        font-weight: 600;
    }

    /* ── Eye button active state ─────────────────── */
    .btn-action.view-active {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
    }

    .btn-action.view-active i {
        color: #fff !important;
    }

    /* ── Status change dropdown in actions ───────── */
    .status-select {
        font-size: 0.82rem;
        padding: 0.35rem 0.6rem;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
        cursor: pointer;
    }
</style>

<div class="orders-header">
    <h2>📋 Order Management</h2>
    <p>Track and manage customer orders and fulfillment status.</p>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem;">
        ✅ <?php echo htmlspecialchars($_GET['success']); ?>
    </div>
<?php endif; ?>

<!-- Filter Bar -->
<form class="filter-bar" method="GET" action="" style="margin-bottom:1.5rem;">
    <select name="status">
        <option value="">All Statuses</option>
        <option value="pending" <?php echo $status_filter == 'pending'   ? 'selected' : ''; ?>>⏳ Pending</option>
        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>✅ Completed</option>
        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
    </select>
    <button type="submit" class="btn-fill" style="padding: 0.6rem 1.5rem; border-radius: 8px;">Filter</button>
    <?php if ($status_filter): ?>
        <a href="manage_orders.php" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">✕ Clear</a>
    <?php endif; ?>
</form>

<div style="overflow-x: auto;">
    <table class="premium-table" id="ordersTable">
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
                <?php while ($row = $result->fetch_assoc()):
                    $oid = $row['id'];
                    // Fetch order items for this row
                    $items_q = $conn->query(
                        "SELECT oi.quantity, oi.price, p.name as product_name, p.stock_quantity
                     FROM order_items oi
                     JOIN products p ON oi.product_id = p.id
                     WHERE oi.order_id = $oid"
                    );
                    $order_items_arr = [];
                    while ($it = $items_q->fetch_assoc()) {
                        $order_items_arr[] = $it;
                    }
                ?>

                    <!-- ══ MAIN ORDER ROW ══ -->
                    <tr id="row-<?php echo $oid; ?>">
                        <td>
                            <span style="font-weight: 700; color: #1e293b;">
                                #<?php echo str_pad($oid, 6, '0', STR_PAD_LEFT); ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($row['user_name']); ?></div>
                            <div style="font-size: 0.75rem; color: #94a3b8;"><?php echo htmlspecialchars($row['user_email']); ?></div>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem; color: #64748b;">
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?><br>
                                <span style="font-size:0.75rem;"><?php echo date('H:i', strtotime($row['created_at'])); ?></span>
                            </span>
                        </td>
                        <td>
                            <span style="font-weight: 700; color: var(--admin-primary);">
                                रु <?php echo number_format($row['total_price'], 0); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $row['status']; ?>">
                                <?php
                                $sicons = ['pending' => '⏳', 'completed' => '✅', 'cancelled' => '❌'];
                                echo ($sicons[$row['status']] ?? '') . ' ' . ucfirst($row['status']);
                                ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.4rem; justify-content: flex-end; align-items: center;">

                                <!-- 👁 TOGGLE VIEW BUTTON -->
                                <button type="button"
                                    class="btn-action"
                                    id="view-btn-<?php echo $oid; ?>"
                                    onclick="toggleDetail(<?php echo $oid; ?>)"
                                    title="View / Hide Details">
                                    <i class="fas fa-eye"></i>
                                </button>

                                <?php if ($row['status'] === 'pending'): ?>
                                    <!-- ✓ Mark Completed -->
                                    <a href="manage_orders.php?update_status=completed&id=<?php echo $oid; ?>"
                                        class="btn-action"
                                        title="Mark as Completed"
                                        style="color:#059669;border-color:#059669;"
                                        onclick="return confirm('Mark order #<?php echo str_pad($oid, 6, '0', STR_PAD_LEFT); ?> as Completed?')">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <!-- ✕ Cancel Order -->
                                    <a href="manage_orders.php?update_status=cancelled&id=<?php echo $oid; ?>"
                                        class="btn-action delete"
                                        title="Cancel Order & Restore Stock"
                                        onclick="return confirm('Cancel order #<?php echo str_pad($oid, 6, '0', STR_PAD_LEFT); ?>? Stock will be restored automatically.')">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php elseif ($row['status'] === 'cancelled'): ?>
                                    <!-- Revert to pending -->
                                    <a href="manage_orders.php?update_status=pending&id=<?php echo $oid; ?>"
                                        class="btn-action"
                                        title="Revert to Pending"
                                        style="color:#d97706;border-color:#d97706;font-size:0.7rem;padding:0.3rem 0.55rem;"
                                        onclick="return confirm('Revert this order back to Pending? Stock will be re-deducted.')">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <!-- ══ DETAILS ACCORDION ROW (hidden by default) ══ -->
                    <tr class="detail-row" id="detail-<?php echo $oid; ?>">
                        <td colspan="6" class="detail-panel">

                            <?php if ($row['status'] === 'cancelled'): ?>
                                <div class="stock-restored-notice">
                                    ♻️ This order was cancelled — stock quantities have been automatically restored to inventory.
                                </div>
                            <?php endif; ?>

                            <div class="detail-inner">

                                <!-- LEFT: Customer Info -->
                                <div class="detail-section">
                                    <h4>👤 Customer Info</h4>
                                    <div class="info-row"><span class="info-label">Name:</span><span class="info-value"><?php echo htmlspecialchars($row['user_name']); ?></span></div>
                                    <div class="info-row"><span class="info-label">Email:</span><span class="info-value"><?php echo htmlspecialchars($row['user_email']); ?></span></div>
                                    <div class="info-row"><span class="info-label">Contact:</span><span class="info-value"><?php echo htmlspecialchars($row['contact_number'] ?? 'N/A'); ?></span></div>
                                    <div class="info-row"><span class="info-label">Address:</span><span class="info-value"><?php echo nl2br(htmlspecialchars($row['address'])); ?></span></div>
                                    <div class="info-row"><span class="info-label">Date:</span><span class="info-value"><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></span></div>
                                    <div class="info-row">
                                        <span class="info-label">Status:</span>
                                        <span class="info-value">
                                            <span class="badge badge-<?php echo $row['status']; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </span>
                                    </div>
                                </div>

                                <!-- RIGHT: Order Items -->
                                <div class="detail-section">
                                    <h4>🛍️ Order Items</h4>
                                    <?php if (!empty($order_items_arr)): ?>
                                        <table class="items-table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Product</th>
                                                    <th>Qty</th>
                                                    <th>Unit Price</th>
                                                    <th>Subtotal</th>
                                                    <?php if ($row['status'] === 'cancelled'): ?>
                                                        <th style="color:#059669;">Restored</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $idx = 1;
                                                foreach ($order_items_arr as $it): ?>
                                                    <tr>
                                                        <td><?php echo $idx++; ?></td>
                                                        <td><?php echo htmlspecialchars($it['product_name']); ?></td>
                                                        <td style="text-align:center;"><?php echo $it['quantity']; ?></td>
                                                        <td>रु <?php echo number_format($it['price'], 0); ?></td>
                                                        <td>रु <?php echo number_format($it['price'] * $it['quantity'], 0); ?></td>
                                                        <?php if ($row['status'] === 'cancelled'): ?>
                                                            <td style="color:#059669;font-weight:700;text-align:center;">
                                                                +<?php echo $it['quantity']; ?>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="<?php echo $row['status'] === 'cancelled' ? 4 : 4; ?>">
                                                        Order Total
                                                    </td>
                                                    <td>रु <?php echo number_format($row['total_price'], 0); ?></td>
                                                    <?php if ($row['status'] === 'cancelled'): ?><td></td><?php endif; ?>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    <?php else: ?>
                                        <p style="color:#94a3b8;font-style:italic;">No items found.</p>
                                    <?php endif; ?>
                                </div>

                            </div><!-- /.detail-inner -->
                        </td>
                    </tr>

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

<script>
    // Toggle detail accordion — one click opens, second click closes
    function toggleDetail(orderId) {
        const detailRow = document.getElementById('detail-' + orderId);
        const viewBtn = document.getElementById('view-btn-' + orderId);

        // Close all other open detail rows first
        document.querySelectorAll('.detail-row.open').forEach(function(row) {
            if (row.id !== 'detail-' + orderId) {
                row.classList.remove('open');
                const btnId = row.id.replace('detail-', 'view-btn-');
                const btn = document.getElementById(btnId);
                if (btn) btn.classList.remove('view-active');
            }
        });

        // Toggle current row
        const isOpen = detailRow.classList.contains('open');
        detailRow.classList.toggle('open', !isOpen);
        viewBtn.classList.toggle('view-active', !isOpen);

        // Smooth scroll into view when opening
        if (!isOpen) {
            setTimeout(() => {
                detailRow.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }, 50);
        }
    }
</script>

<?php require_once 'admin_footer.php'; ?>