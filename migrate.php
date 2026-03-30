<?php
require_once 'includes/connection.php';

$results = [];

// 1. Add stock_quantity column to products (if not exists)
$check = $conn->query("SHOW COLUMNS FROM products LIKE 'stock_quantity'");
if ($check->num_rows === 0) {
    if ($conn->query("ALTER TABLE products ADD COLUMN stock_quantity INT NOT NULL DEFAULT 100")) {
        $results[] = "✅ Added stock_quantity column to products table.";
    } else {
        $results[] = "❌ Failed: " . $conn->error;
    }
} else {
    $results[] = "ℹ️ stock_quantity column already exists.";
}

// 2. Create product_ratings table (if not exists)
$sql_ratings = "CREATE TABLE IF NOT EXISTS product_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL COMMENT '1 to 5 stars',
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product (user_id, product_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql_ratings)) {
    $results[] = "✅ product_ratings table ready.";
} else {
    $results[] = "❌ Failed: " . $conn->error;
}

// 3. Add contact_number column to orders if not exists
$check2 = $conn->query("SHOW COLUMNS FROM orders LIKE 'contact_number'");
if ($check2->num_rows === 0) {
    if ($conn->query("ALTER TABLE orders ADD COLUMN contact_number VARCHAR(20) DEFAULT NULL AFTER address")) {
        $results[] = "✅ Added contact_number column to orders table.";
    } else {
        $results[] = "❌ Failed to add contact_number: " . $conn->error;
    }
} else {
    $results[] = "ℹ️ contact_number column already exists in orders.";
}

echo "<h2>Migration Results</h2><ul>";
foreach ($results as $r) echo "<li>$r</li>";
echo "</ul>";
echo "<p><a href='index.php'>→ Go to Homepage</a></p>";
?>
