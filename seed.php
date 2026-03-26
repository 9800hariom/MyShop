<?php
$host = 'localhost';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read the SQL file
$sqlFile = file_get_contents(__DIR__ . '/database/ecommerce.sql');
if ($conn->multi_query($sqlFile)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Database schema imported successfully.\n";
} else {
    echo "Error importing schema: " . $conn->error . "\n";
}

$conn->select_db('ecommerce');

// Create admin user
$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT IGNORE INTO users (id, name, email, password, role) VALUES (1, 'Admin', 'admin@ecommerce.com', ?, 'admin')");
if ($stmt) {
    $stmt->bind_param("s", $admin_pass);
    $stmt->execute();
    echo "Admin user ready: admin@ecommerce.com / admin123\n";
}

// Create user
$user_pass = password_hash('user123', PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT IGNORE INTO users (id, name, email, password, role) VALUES (2, 'John Doe', 'john@example.com', ?, 'user')");
if ($stmt) {
    $stmt->bind_param("s", $user_pass);
    $stmt->execute();
}

// Create some default categories
$categories = ['Electronics', 'Clothing', 'Home Appliances', 'Furniture', 'Toys'];
foreach($categories as $cat) {
    $conn->query("INSERT INTO categories (name) SELECT '$cat' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = '$cat')");
}
echo "Categories ready.\n";

// Add some sample products
$products = [
    ['Elegant Sofa', 899.99, 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc', 'Comfortable 3-seater modern sofa.', 4],
    ['Desk Lamp', 45.50, 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c', 'LED touch-sensitive lamp.', 1],
    ['Dining Table', 450.00, 'https://images.unsplash.com/photo-1544207617-059bc3203c94', 'Minimalist wooden dining table.', 4],
    ['Coffee Maker', 120.00, 'https://images.unsplash.com/photo-1520970014086-2208d157c9e2', 'Professional espresso machine.', 3]
];

foreach ($products as $p) {
    $stmt = $conn->prepare("INSERT IGNORE INTO products (name, price, image, description, category_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sdssi", $p[0], $p[1], $p[2], $p[3], $p[4]);
    $stmt->execute();
}
echo "Products ready.\n";

// Add some orders for the last 7 days to test charts
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d H:i:s', strtotime("-$i days"));
    $revenue = rand(100, 1000) + (rand(0, 99) / 100);
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status, address, created_at) VALUES (2, ?, 'completed', '123 Test St, NY', ?)");
    $stmt->bind_param("ds", $revenue, $date);
    $stmt->execute();
}
echo "Sample orders created for charts.\n";

$conn->close();
echo "Seed completed successfully.\n";
?>
