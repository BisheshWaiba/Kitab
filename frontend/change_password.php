<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// Flat page; only the buttons are glass: is-gbtn switches them on (see assets/css/glass-buttons.css)
$body_class = 'is-gbtn';
$hide_footer = true; // this page has no footer (the footer stays on Home, Shop, the Book page and Wishlist; see includes/footer.php)
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/profile-flat.css'];
require_once '../includes/password_reset.php'; // the password rules, and the emailed reset link
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

const PW_TRIES = 5;      // wrong "current password" tries allowed ...
const PW_WINDOW = 600;   // ... in this many seconds

$user_id = (int) $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT email, password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
if (!$account) {
    header("Location: login.php");
    exit();
}

$error = '';
$errors = [];          // problems with one field, shown under it
$reset_sent = false;   // the reset link was asked for
$cooldown = false;     // ... but one went out a moment ago
$dev_link = '';

// Wrong tries are counted per visit, so a stolen session cannot guess the current password all day
$now = time();
$fails = array_values(array_filter((array) ($_SESSION['pw_fails'] ?? []), fn($t) => is_int($t) && $t > $now - PW_WINDOW));
$wait_minutes = count($fails) >= PW_TRIES ? (int) ceil(($fails[0] + PW_WINDOW - $now) / 60) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!k_csrf_valid($_POST['csrf'] ?? '')) {
        $error = "Your session expired. Please try again.";
    } elseif (($_POST['do'] ?? '') === 'reset') {
        // "I forgot my current password": email the link to the account's own address
        $request = k_request_reset($conn, (string) $account['email']);
        if ($request === null) {
            $cooldown = true;
        } elseif (!$request['sent'] && k_is_loopback()) {
            $dev_link = $request['link']; // on your own computer, where mail usually cannot be sent
        }
        $reset_sent = true;
    } else {
        $field = fn(string $key): string => is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
        $current = $field('current');
        $new = $field('password');
        $confirm = $field('confirm');

        if ($wait_minutes > 0) {
            $error = "Too many tries. Please wait " . $wait_minutes . ($wait_minutes === 1 ? " minute" : " minutes") . ", or email yourself a reset link below.";
        } else {
            if ($current === '') {
                $errors['current'] = "Enter your current password.";
            }
            if ($new === '') {
                $errors['password'] = "Choose a new password.";
            } elseif (strlen($new) < RESET_MIN_PASSWORD) {
                $errors['password'] = "Use at least " . RESET_MIN_PASSWORD . " characters (you typed " . strlen($new) . ").";
            } elseif (strlen($new) > RESET_MAX_PASSWORD) {
                $errors['password'] = "Use " . RESET_MAX_PASSWORD . " characters or fewer.";
            }
            if (!isset($errors['password']) && !hash_equals($new, $confirm)) {
                $errors['confirm'] = "The two passwords do not match.";
            }

            if (!$errors) {
                if (!password_verify($current, (string) $account['password'])) {
                    $errors['current'] = "That is not your current password.";
                    $fails[] = $now;
                    $_SESSION['pw_fails'] = $fails;
                } elseif (password_verify($new, (string) $account['password'])) {
                    $errors['password'] = "Choose a password that is different from your current one.";
                } else {
                    $hash = password_hash($new, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->bind_param("si", $hash, $user_id);
                    $stmt->execute();
                    // Any reset link emailed earlier must stop working now
                    k_reset_table($conn);
                    $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    unset($_SESSION['pw_fails']);
                    session_regenerate_id(true);
                    $_SESSION['account_notice'] = "Your password was updated.";
                    header("Location: profile.php");
                    exit();
                }
            }
        }
    }
}

$page_title = $reset_sent ? 'Check your email' : 'Change password';
require_once '../includes/header.php';
?>

<main class="fl">
    <div class="fl-wrap pf pf--narrow">

        <?php if ($reset_sent): ?>
            <h1 class="fl-h1">Check your email</h1>
            <div class="pf-form">
                <?php if ($cooldown): ?>
                    <p class="pf-text">We sent a reset link to <strong><?php echo htmlspecialchars((string) $account['email']); ?></strong> a moment ago. Look for it there (and in your spam folder), or wait a minute and ask for another.</p>
                <?php else: ?>
                    <p class="pf-text">We sent a link to <strong><?php echo htmlspecialchars((string) $account['email']); ?></strong>. Open it to choose a new password. The link works for <?php echo RESET_MINUTES; ?> minutes.</p>
                    <p class="pf-text">Nothing there? Look in your spam folder, or wait a minute and ask for a new link.</p>
                <?php endif; ?>

                <?php if ($dev_link !== ''): ?>
                    <div class="k-devnote">
                        <strong>Only you see this.</strong> This computer cannot send email, so here is the link
                        (it is shown only on localhost):
                        <a href="<?php echo htmlspecialchars($dev_link); ?>"><?php echo htmlspecialchars($dev_link); ?></a>
                    </div>
                <?php endif; ?>

                <div class="pf-actions">
                    <a href="profile.php" class="k-btn k-btn--outline">Back to Account</a>
                </div>
            </div>
        <?php else: ?>
            <h1 class="fl-h1">Change password</h1>

            <form method="POST" class="pf-form fl-panel" data-validate>
                <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">

                <?php if ($error): ?>
                    <div class="k-alert k-alert--error" role="alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="k-field">
                    <label for="current">Current password</label>
                    <input type="password" id="current" name="current" class="k-input" required autocomplete="current-password"
                        maxlength="<?php echo RESET_MAX_PASSWORD; ?>" data-msg-missing="Enter your current password."
                        <?php k_field_invalid($errors, 'current'); ?>>
                    <?php k_field_error($errors, 'current'); ?>
                </div>
                <div class="k-field">
                    <label for="password">New password <span class="k-field__hint">(at least <?php echo RESET_MIN_PASSWORD; ?> characters)</span></label>
                    <input type="password" id="password" name="password" class="k-input" required
                        minlength="<?php echo RESET_MIN_PASSWORD; ?>" maxlength="<?php echo RESET_MAX_PASSWORD; ?>"
                        autocomplete="new-password" data-msg-missing="Choose a new password."
                        <?php k_field_invalid($errors, 'password'); ?>>
                    <?php k_field_error($errors, 'password'); ?>
                </div>
                <div class="k-field">
                    <label for="confirm">Confirm new password</label>
                    <input type="password" id="confirm" name="confirm" class="k-input" required
                        minlength="<?php echo RESET_MIN_PASSWORD; ?>" maxlength="<?php echo RESET_MAX_PASSWORD; ?>"
                        autocomplete="new-password" data-match="password" data-msg-match="The two passwords do not match."
                        data-msg-missing="Type the new password again to confirm it."
                        <?php k_field_invalid($errors, 'confirm'); ?>>
                    <?php k_field_error($errors, 'confirm'); ?>
                </div>

                <div class="pf-form__actions">
                    <button type="submit" class="k-btn k-btn--primary">Update Password</button>
                    <a href="profile.php" class="k-btn k-btn--outline">Cancel</a>
                </div>
            </form>

            <section class="pf-forgot" aria-labelledby="pf-forgot">
                <h2 id="pf-forgot">Forgot your current password?</h2>
                <p class="pf-text">We can email a link to <strong><?php echo htmlspecialchars((string) $account['email']); ?></strong> that lets you choose a new one.</p>
                <form method="POST">
                    <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                    <input type="hidden" name="do" value="reset">
                    <button type="submit" class="k-btn k-btn--outline">Email me a reset link</button>
                </form>
            </section>
        <?php endif; ?>

    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
