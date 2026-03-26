<?php
$page_title = 'Manage Customers';
require_once 'admin_header.php';

// Handle User Deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id !== $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id = $id AND role != 'admin'");
        header("Location: manage_users.php?success=1");
        exit;
    } else {
        header("Location: manage_users.php?error=1");
        exit;
    }
}

// Search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where = "WHERE 1=1";
if ($search) {
    $where .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')";
}

$sql = "SELECT * FROM users $where ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<style>
    body {
        background-color: #0369a1;
    }
</style>

<div style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">Customer Management</h2>
    <p style="color: #d5d7db; font-size: 0.9rem;">View and manage registered users and their roles.</p>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem;">User removed successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error" style="margin-bottom: 1.5rem;">Action not permitted.</div>
<?php endif; ?>

<form class="filter-bar" method="GET" action="">
    <div style="position: relative; flex: 1; min-width: 300px;">
        <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
        <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>" style="padding-left: 2.5rem; width: 100%;">
    </div>

    <button type="submit" class="btn-fill" style="padding: 0.6rem 1.5rem; border-radius: 8px;">Find User</button>
    <?php if ($search): ?>
        <a href="manage_users.php" style="color: #64748b; text-decoration: none; font-size: 0.9rem;">Clear</a>
    <?php endif; ?>
</form>

<div style="overflow-x: auto;">
    <table class="premium-table">
        <thead>
            <tr>
                <th>Customer Name</th>
                <th>Email Address</th>
                <th>Access Level</th>
                <th>Joined Date</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                </div>
                                <span style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($row['name']); ?></span>
                            </div>
                        </td>
                        <td><span style="font-size: 0.9rem; color: #64748b;"><?php echo htmlspecialchars($row['email']); ?></span></td>
                        <td>
                            <span class="badge" style="background: <?php echo $row['role'] == 'admin' ? '#e0e7ff' : '#f1f5f9'; ?>; color: <?php echo $row['role'] == 'admin' ? '#4338ca' : '#475569'; ?>;">
                                <?php echo ucfirst($row['role']); ?>
                            </span>
                        </td>
                        <td><span style="font-size: 0.85rem; color: #64748b;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <?php if ($row['role'] !== 'admin'): ?>
                                    <a href="manage_users.php?delete=<?php echo $row['id']; ?>" class="btn-action delete" title="Remove User">
                                        <i class="fas fa-user-slash"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 0.75rem; color: #94a3b8; font-style: italic;">Protected</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem; color: #94a3b8;">
                        <i class="fas fa-users-slash" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                        No users found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'admin_footer.php'; ?>