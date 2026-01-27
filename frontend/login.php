<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT id, name, password, role FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_role'] = $row['role'] ?? 'user';

            if ($_SESSION['user_role'] === 'admin') {
                header("Location: ../admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No user found with this email.";
    }
}
?>

<div class="auth-container">
    <div class="auth-box">
        <h2 style="text-align:center; margin-bottom:1.5rem;">Welcome Back</h2>
        <?php if ($error): ?>
            <div style="background:#fee2e2; color:#b91c1c; padding:0.75rem; border-radius:8px; margin-bottom:1.5rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <p style="text-align:center; margin-top:1.5rem; font-size:0.9rem;">
            Don't have an account? <a href="register.php" style="color:var(--accent); font-weight:600;">Register
                here</a>
        </p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>