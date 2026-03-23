<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // Assuming empty password for XAMPP
$db = 'ecommerce';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
