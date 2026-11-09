<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Flat page; only the buttons are glass: is-gbtn switches them on (see assets/css/glass-buttons.css)
$body_class = 'is-gbtn';
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/auth-flat.css'];
require_once '../includes/password_reset.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();
}

$error = '';
$errors = []; // problems with one field, shown under it
$sent = false;
$dev_link = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';

    if (!k_csrf_valid($_POST['csrf'] ?? '')) {
        $error = "Your session expired. Please try again.";
    } elseif ($email === '') {
        $errors['email'] = "Enter the email address of your account.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Enter a valid email address, like name@example.com.";
    } else {
        $request = k_request_reset($conn, $email);
        // Same answer whether or not the email has an account. The one exception: on your own computer, where
        // the server usually cannot send mail, the link is shown so you can still finish the flow.
        if ($request && !$request['sent'] && k_is_loopback()) {
            $dev_link = $request['link'];
        }
        $sent = true;
    }
}

$page_title = 'Forgot password';
require_once '../includes/header.php';
?>

<div class="k-authpage">
    <?php k_auth_art($conn, 8); ?>

    <div class="k-authpage__main">
        <?php if ($sent): ?>
            <div class="k-authpage__form">
                <h1 class="k-h1">Check your email</h1>
                <p class="k-authpage__text">
                    If there is an account for <strong><?php echo htmlspecialchars($email); ?></strong>, we have sent it a
                    link to choose a new password. The link works for <?php echo RESET_MINUTES; ?> minutes.
                </p>
                <p class="k-authpage__text">
                    Nothing there? Look in your spam folder, or wait a minute and ask for a new link.
                </p>

                <?php if ($dev_link !== ''): ?>
                    <div class="k-devnote">
                        <strong>Only you see this.</strong> This computer cannot send email, so here is the link
                        (it is shown only on localhost):
                        <a href="<?php echo htmlspecialchars($dev_link); ?>"><?php echo htmlspecialchars($dev_link); ?></a>
                    </div>
                <?php endif; ?>

                <a href="login.php" class="k-btn k-btn--outline k-btn--block">Back to Login</a>
            </div>
        <?php else: ?>
            <form method="POST" class="k-authpage__form" data-validate>
                <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                <h1 class="k-h1">Forgot Password</h1>
                <p class="k-authpage__text">Enter the email address of your account and we will send you a link to choose a new password.</p>

                <?php if ($error): ?>
                    <div class="k-alert k-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="k-field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="k-input" required autocomplete="email"
                        data-msg-missing="Enter the email address of your account."
                        value="<?php echo htmlspecialchars($email); ?>" <?php k_field_invalid($errors, 'email'); ?>>
                    <?php k_field_error($errors, 'email'); ?>
                </div>
                <button type="submit" class="k-btn k-btn--primary k-btn--block">Send Reset Link</button>

                <p class="k-authpage__switch">
                    Remembered it? <a href="login.php">Back to login</a>
                </p>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
