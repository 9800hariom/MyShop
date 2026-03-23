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
$stmt = $conn->prepare("INSERT IGNORE INTO users (name, email, password, role) VALUES ('Admin', 'admin@ecommerce.com', ?, 'admin')");
if ($stmt) {
    $stmt->bind_param("s", $admin_pass);
    if ($stmt->execute()) {
        echo "Admin user created: admin@ecommerce.com / admin123\n";
    } else {
        echo "Error creating admin: " . $stmt->error . "\n";
    }
}

// Create some default categories
$categories = ['Electronics', 'Clothing', 'Home Appliances', 'Books', 'Toys'];
foreach($categories as $cat) {
    $conn->query("INSERT INTO categories (name) SELECT '$cat' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = '$cat')");
}
echo "Categories inserted.\n";

$conn->close();
?>
