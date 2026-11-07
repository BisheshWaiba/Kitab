<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
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

    // One message for both cases, so the form does not reveal which emails have accounts.
    $error = "Invalid email or password.";

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_role'] = $row['role'] ?? 'user';

            if ($_SESSION['user_role'] === 'admin') {
                header("Location: ../admin/index.php");
            } else {
                header("Location: ../index.php");
            }
            exit();
        }
    }
}
?>

<div class="k-authpage">
    <?php k_auth_art($conn, 0); ?>

    <div class="k-authpage__main">
        <form method="POST" class="k-authpage__form">
            <h1 class="k-h1">Welcome Back</h1>

            <?php if ($error): ?>
                <div class="k-alert k-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="k-field">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="k-input" required autocomplete="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="k-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="k-input" required
                    autocomplete="current-password">
            </div>
            <button type="submit" class="k-btn k-btn--primary k-btn--block">Login</button>

            <p class="k-authpage__switch">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
