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
            } else {
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

<div class="form-container"
    style="max-width:380px; margin:60px auto; padding:2rem; 
background: rgba(255,255,255,0.15); 
backdrop-filter: blur(15px);
border-radius:20px; 
box-shadow:0 15px 40px rgba(0,0,0,0.2);
border:1px solid rgba(255,255,255,0.2);
background-color:#667eea;
font-family:Arial, sans-serif;">

    <h2 style="text-align:center; margin-bottom:1.5rem; color:#333; font-size:1.8rem;">
        Welcome Back
    </h2>

    <?php if (isset($error))
        echo "<div style='background:#ff4d4d; color:white; padding:10px; border-radius:8px; margin-bottom:1rem; text-align:center;'>$error</div>";
    ?>

    <form method="POST" action="">

        <div style="margin-bottom:1rem;">
            <label style="display:block; margin-bottom:5px; font-weight:600;">Email</label>
            <input type="email" name="email" required
                style="width:100%; padding:10px; border-radius:10px; border:1px solid #ccc; outline:none; transition:0.3s;">
        </div>

        <div style="margin-bottom:1.5rem;">
            <label style="display:block; margin-bottom:5px; font-weight:600;">Password</label>
            <input type="password" name="password" required
                style="width:100%; padding:10px; border-radius:10px; border:1px solid #ccc; outline:none;">
        </div>

        <button type="submit"
            style="width:100%; padding:12px; border:none; border-radius:10px;
        background:linear-gradient(135deg,#667eea,#764ba2);
        color:white; font-size:1rem; cursor:pointer;
        transition:0.3s;">
            Login
        </button>

    </form>

    <p style="margin-top:1rem; text-align:center; font-size:0.9rem;">
        Don't have an account?
        <a href="register.php" style="color:white; font-weight:600; text-decoration:none;">
            Register here
        </a>
    </p>

</div>

<?php require_once 'includes/footer.php'; ?>