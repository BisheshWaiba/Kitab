<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Flat page; only the buttons are glass: is-gbtn switches them on (see assets/css/glass-buttons.css)
$body_class = 'is-gbtn';
$hide_footer = true; // this page has no footer (the footer stays on Home, Shop, the Book page and Wishlist; see includes/footer.php)
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/auth-flat.css'];
require_once '../includes/password_reset.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// The address of this page holds the secret token, so keep it out of the Referer header sent to other sites
header('Referrer-Policy: no-referrer');

$token_in = $_POST['token'] ?? $_GET['token'] ?? '';
$token = (is_string($token_in) && preg_match('/^[a-f0-9]{64}$/', $token_in) === 1) ? $token_in : '';
$reset = $token !== '' ? k_find_reset($conn, $token) : null;
$error = '';
$errors = []; // problems with one field, shown under it

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm = is_string($_POST['confirm'] ?? null) ? $_POST['confirm'] : '';

    if (!k_csrf_valid($_POST['csrf'] ?? '')) {
        $error = "Your session expired. Please try again.";
    } elseif (strlen($password) < RESET_MIN_PASSWORD) {
        $errors['password'] = "Use at least " . RESET_MIN_PASSWORD . " characters (you typed " . strlen($password) . ").";
    } elseif (strlen($password) > RESET_MAX_PASSWORD) {
        $errors['password'] = "Use " . RESET_MAX_PASSWORD . " characters or fewer.";
    } elseif (!hash_equals($password, $confirm)) {
        $errors['confirm'] = "The two passwords do not match.";
    } elseif (k_finish_reset($conn, (int) $reset['id'], (int) $reset['user_id'], $password)) {
        header("Location: login.php?reset=1");
        exit();
    } else {
        $reset = null; // the link was used up or ran out while the form was open
    }
}

if (!$reset) {
    http_response_code(400);
}
$page_title = $reset ? 'Choose a new password' : 'Link expired';
require_once '../includes/header.php';
?>

<div class="k-authpage">
    <?php k_auth_art($conn, 8, true); ?>

    <div class="k-authpage__main">
        <?php if ($reset): ?>
            <form method="POST" class="k-authpage__form" data-validate>
                <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <h1 class="k-h1">New Password</h1>
                <p class="k-authpage__text">Choose a new password for your account.</p>

                <?php if ($error): ?>
                    <div class="k-alert k-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="k-field">
                    <label for="password">New Password <span class="k-field__hint">(at least <?php echo RESET_MIN_PASSWORD; ?> characters)</span></label>
                    <input type="password" id="password" name="password" class="k-input" required
                        minlength="<?php echo RESET_MIN_PASSWORD; ?>" maxlength="<?php echo RESET_MAX_PASSWORD; ?>"
                        autocomplete="new-password" data-msg-missing="Choose a new password."
                        <?php k_field_invalid($errors, 'password'); ?>>
                    <?php k_field_error($errors, 'password'); ?>
                </div>
                <div class="k-field">
                    <label for="confirm">Confirm Password</label>
                    <input type="password" id="confirm" name="confirm" class="k-input" required
                        minlength="<?php echo RESET_MIN_PASSWORD; ?>" maxlength="<?php echo RESET_MAX_PASSWORD; ?>"
                        autocomplete="new-password" data-match="password" data-msg-match="The two passwords do not match."
                        data-msg-missing="Type the new password again to confirm it."
                        <?php k_field_invalid($errors, 'confirm'); ?>>
                    <?php k_field_error($errors, 'confirm'); ?>
                </div>
                <button type="submit" class="k-btn k-btn--primary k-btn--block">Update Password</button>
            </form>
        <?php else: ?>
            <div class="k-authpage__form">
                <h1 class="k-h1">Link Expired</h1>
                <p class="k-authpage__text">
                    This password reset link is not valid any more. A link works only once and only for
                    <?php echo RESET_MINUTES; ?> minutes.
                </p>
                <a href="forgot_password.php" class="k-btn k-btn--primary k-btn--block">Request a New Link</a>
                <p class="k-authpage__switch"><a href="login.php">Back to login</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
