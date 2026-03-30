<?php
$page_title = 'Dashboard';
require_once 'admin_header.php';

// Fetch statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_sales = $conn->query("SELECT IFNULL(SUM(total_price), 0) as total FROM orders WHERE status = 'completed'")->fetch_assoc()['total'];

// Data for Chart (last 7 days sales)
$sales_data = [];
$labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('M d', strtotime("-$i days"));
    $res = $conn->query("SELECT IFNULL(SUM(total_price), 0) as total FROM orders WHERE DATE(created_at) = '$date' AND status = 'completed'");
    $sales_data[] = $res->fetch_assoc()['total'];
}
?>

<style>
    body {
        background-color: #0369a1;
    }

    .chart-container {
        background: linear-gradient(135deg, #cfd146, #dfd665, #c8d145);
    }
</style>

<div class="stats-grid">
    <div class=" stat-card">
        <div class="stat-icon" style="background: #e0f2fe; color: #0369a1;">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <h3>Total Customers</h3>
            <div class="value"><?php echo number_format($total_users); ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #f0fdf4; color: #15803d;">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <h3>Total Products</h3>
            <div class="value"><?php echo number_format($total_products); ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #fef3c7; color: #b45309;">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-content">
            <h3>Total Orders</h3>
            <div class="value"><?php echo number_format($total_orders); ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #fdf2f8; color: #be185d;">
            <i class="fas fa-rupee-sign"></i> रु
        </div>
        <div class="stat-content">
            <h3>Total Sales</h3>
            <div class="value">रु <?php echo number_format($total_sales, 0); ?></div>
        </div>
    </div>
</div>

<div class="chart-container">
    <h3> Sales Revenue (Last 7 Days)</h3>
    <canvas id="salesChart"></canvas>
</div>

<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Revenue (रु)',
                data: <?php echo json_encode($sales_data); ?>,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(10, 10, 14, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5

            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
</script>

<?php require_once 'admin_footer.php'; ?>