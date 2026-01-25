<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$error = '';
$success = '';

if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email exists
    $check = "SELECT id FROM users WHERE email = '$email'";
    if ($conn->query($check)->num_rows > 0) {
        $error = "Email already registered.";
    } else {
        $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
        if ($conn->query($sql) === TRUE) {
            // Auto login
            $user_id = $conn->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;

            // Redirect to preferences
            header("Location: preferences.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-box">
        <h2 style="text-align:center; margin-bottom:1.5rem;">Create Account</h2>
        <?php if ($error): ?>
            <div
                style="background:#fee2e2; color:#b91c1c; padding:0.75rem; border-radius:8px; margin-bottom:1.5rem; text-align:center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        <p style="text-align:center; margin-top:1.5rem; font-size:0.9rem;">
            Already have an account? <a href="login.php" style="color:var(--accent); font-weight:600;">Login here</a>
        </p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>