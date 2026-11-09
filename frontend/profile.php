<?php
require_once '../config/db.php';
require_once '../includes/helpers.php';
// The account page is flat (white and light-grey surfaces, like the Shop); only its buttons are glass.
// is-gbtn switches the glass buttons on, and these stylesheets are loaded by the redesigned pages only
$body_class = 'is-gbtn';
$extra_css = ['assets/css/glass-buttons.css', 'assets/css/flat.css', 'assets/css/profile-flat.css'];
$page_title = 'My account';
require_once '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// A message left by the page that changed something (shown once)
$notice = (string) ($_SESSION['account_notice'] ?? '');
unset($_SESSION['account_notice']);

// The account details, read fresh so a new photo or genre choice shows at once
$stmt = $conn->prepare("SELECT name, email, phone, address, profile_picture, favorite_genres, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc() ?: [];

$name = trim((string) ($user['name'] ?? $_SESSION['user_name'] ?? ''));
$email = trim((string) ($user['email'] ?? ''));
$phone = trim((string) ($user['phone'] ?? ''));
$address = trim((string) ($user['address'] ?? ''));
$initial = strtoupper(mb_substr($name, 0, 1));
$member_since = !empty($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : '';

$avatar_path = trim((string) ($user['profile_picture'] ?? ''));
$avatar_url = $avatar_path === '' ? '' : (strpos($avatar_path, 'http') === 0 ? $avatar_path : BASE_URL . $avatar_path);

// Favorite genres, as the labels the Preferences page shows
$genre_labels = k_genre_options();
$genres = [];
foreach (array_filter(explode(',', (string) ($user['favorite_genres'] ?? ''))) as $value) {
    if (isset($genre_labels[$value])) {
        $genres[] = $genre_labels[$value];
    }
}
?>

<main class="fl">
    <div class="fl-wrap pf">
        <h1 class="fl-h1">Account details</h1>

        <?php if ($notice !== ''): ?>
            <div class="k-alert k-alert--success pf-notice" role="status"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>

        <section class="pf-card fl-panel" aria-label="Account details">
            <div class="pf-id">
                <?php if ($avatar_url !== ''): ?>
                    <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="" class="pf-avatar">
                <?php else: ?>
                    <div class="pf-avatar" aria-hidden="true"><?php echo htmlspecialchars($initial); ?></div>
                <?php endif; ?>
                <div class="pf-id__text">
                    <p class="pf-name"><?php echo htmlspecialchars($name); ?></p>
                    <?php if ($member_since !== ''): ?>
                        <p class="pf-meta">Member since <?php echo htmlspecialchars($member_since); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <dl class="pf-list">
                <div class="pf-row">
                    <dt>Email</dt>
                    <dd><?php echo htmlspecialchars($email); ?></dd>
                </div>
                <div class="pf-row">
                    <dt>Phone</dt>
                    <dd><?php echo $phone !== '' ? htmlspecialchars($phone) : '<span class="pf-empty">Not added yet</span>'; ?></dd>
                </div>
                <div class="pf-row">
                    <dt>Address</dt>
                    <dd><?php echo $address !== '' ? nl2br(htmlspecialchars($address)) : '<span class="pf-empty">Not added yet</span>'; ?></dd>
                </div>
                <div class="pf-row">
                    <dt>Favorite genres</dt>
                    <dd>
                        <?php if ($genres): ?>
                            <ul class="pf-tags">
                                <?php foreach ($genres as $label): ?>
                                    <li class="k-tag"><?php echo htmlspecialchars($label); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <span class="pf-empty">None chosen yet</span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div class="pf-row">
                    <dt>Password</dt>
                    <dd>
                        <span class="pf-secret" aria-hidden="true">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</span><span class="k-sr">Hidden</span>
                        <div class="pf-row__actions">
                            <a href="change_password.php" class="k-btn k-btn--outline k-btn--sm">Change password</a>
                            <form method="POST" action="change_password.php">
                                <input type="hidden" name="csrf" value="<?php echo k_csrf_token(); ?>">
                                <input type="hidden" name="do" value="reset">
                                <button type="submit" class="k-btn k-btn--ghost k-btn--sm">Email me a reset link</button>
                            </form>
                        </div>
                    </dd>
                </div>
            </dl>
        </section>

        <div class="pf-actions">
            <a href="preferences.php" class="k-btn k-btn--outline">Edit Profile</a>
            <a href="logout.php" class="k-btn k-btn--danger-light">Logout</a>
        </div>
    </div>
</main>

<?php
$hide_footer = true; // the account page ends with its own content; the site script still loads
require_once '../includes/footer.php';
?>
