<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Flat page; only the buttons are glass: is-gbtn switches them on (see assets/css/glass-buttons.css)
$body_class = 'is-gbtn';
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/auth-flat.css'];
require_once '../includes/header.php';

$error = '';
$success = '';
$errors = []; // problems with one field, shown under it (field name => message)

if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $field = fn(string $key): string => is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
    $raw_name = trim($field('name'));
    $raw_email = trim($field('email'));
    $raw_password = $field('password');

    if ($raw_name === '') {
        $errors['name'] = "Enter your full name.";
    } elseif (mb_strlen($raw_name) > 255) {
        $errors['name'] = "Please use 255 characters or fewer.";
    }
    if ($raw_email === '') {
        $errors['email'] = "Enter your email address.";
    } elseif (!filter_var($raw_email, FILTER_VALIDATE_EMAIL) || strlen($raw_email) > 255) {
        $errors['email'] = "Enter a valid email address, like name@example.com.";
    }
    if ($raw_password === '') {
        $errors['password'] = "Choose a password.";
    }

    $name = $conn->real_escape_string($raw_name);
    $email = $conn->real_escape_string($raw_email);
    $password = password_hash($raw_password, PASSWORD_DEFAULT);
    $role = 'customer'; // All new registrations are regular customers (the users.role column allows customer or admin)

    // Check if email exists
    $check = "SELECT id FROM users WHERE email = '$email'";
    if ($errors) {
        // nothing to save yet: the messages are shown under the fields
    } elseif ($conn->query($check)->num_rows > 0) {
        $errors['email'] = "This email is already registered. Try logging in instead.";
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
        <form method="POST" class="k-authpage__form" data-validate>
            <h1 class="k-h1">Create Account</h1>

            <?php if ($error): ?>
                <div class="k-alert k-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="k-field">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="k-input" required autocomplete="name"
                    data-msg-missing="Enter your full name."
                    value="<?php echo htmlspecialchars(is_string($_POST['name'] ?? null) ? $_POST['name'] : ''); ?>"
                    <?php k_field_invalid($errors, 'name'); ?>>
                <?php k_field_error($errors, 'name'); ?>
            </div>
            <div class="k-field">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="k-input" required autocomplete="email"
                    data-msg-missing="Enter your email address."
                    value="<?php echo htmlspecialchars(is_string($_POST['email'] ?? null) ? $_POST['email'] : ''); ?>"
                    <?php k_field_invalid($errors, 'email'); ?>>
                <?php k_field_error($errors, 'email'); ?>
            </div>
            <div class="k-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="k-input" required
                    autocomplete="new-password" data-msg-missing="Choose a password."
                    <?php k_field_invalid($errors, 'password'); ?>>
                <?php k_field_error($errors, 'password'); ?>
            </div>
            <button type="submit" class="k-btn k-btn--primary k-btn--block">Register</button>

            <p class="k-authpage__switch">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
