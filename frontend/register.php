<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
require_once '../includes/header.php';

$error = '';
$success = '';

if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $raw_name = trim($_POST['name']);
    $name = $conn->real_escape_string($raw_name);
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'customer'; // All new registrations are regular customers (the users.role column allows customer or admin)

    // Check if email exists
    $check = "SELECT id FROM users WHERE email = '$email'";
    if ($conn->query($check)->num_rows > 0) {
        $error = "Email already registered.";
    } else {
        $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
        if ($conn->query($sql) === TRUE) {
            // Auto login
            $user_id = $conn->insert_id;
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $raw_name;
            $_SESSION['user_role'] = $role;

            // Redirect based on role
            if ($role === 'admin') {
                header("Location: ../admin/index.php");
            } else {
                header("Location: preferences.php");
            }
            exit();
        } else {
            $error = "Something went wrong creating your account. Please try again.";
        }
    }
}
?>

<div class="k-authpage">
    <?php k_auth_art($conn, 4, true); ?>

    <div class="k-authpage__main">
        <form method="POST" class="k-authpage__form">
            <h1 class="k-h1">Create Account</h1>

            <?php if ($error): ?>
                <div class="k-alert k-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="k-field">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="k-input" required autocomplete="name"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
            <div class="k-field">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="k-input" required autocomplete="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="k-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="k-input" required
                    autocomplete="new-password">
            </div>
            <button type="submit" class="k-btn k-btn--primary k-btn--block">Register</button>

            <p class="k-authpage__switch">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
