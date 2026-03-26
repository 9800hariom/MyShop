<?php
require_once 'includes/connection.php';
require_once 'includes/functions.php';




if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            session_start();
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['role'] = $row['role'];

            echo $_SESSION['role'];
            if ($_SESSION['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            }else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}
require_once 'includes/header.php';
?>

<div class="form-container">
    <h2>Login</h2>
    <?php if (isset($error)) echo "<div class='alert alert-error'>$error</div>"; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn-submit">Login</button>
    </form>
    <p style="margin-top: 1rem; text-align: center;">Don't have an account? <a href="register.php">Register here</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>