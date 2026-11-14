<?php
require_once __DIR__ . '/auth.php'; // session, database and the admin check

$current_page = basename($_SERVER['PHP_SELF']);

// file => [label, files that keep it highlighted, icon (the drawing inside a 24x24 box)]
$admin_nav = [
    'index.php' => ['Dashboard', ['index.php'], '<rect x="3" y="3" width="7" height="9" rx="1.5" /><rect x="14" y="3" width="7" height="5" rx="1.5" /><rect x="14" y="12" width="7" height="9" rx="1.5" /><rect x="3" y="16" width="7" height="5" rx="1.5" />'],
    'books.php' => ['Books', ['books.php', 'add_book.php', 'edit_book.php'], '<path d="M5 4h10a3 3 0 0 1 3 3v13H8a3 3 0 0 1-3-3V4z" /><path d="M5 17a3 3 0 0 1 3-3h10" />'],
    'users.php' => ['Users', ['users.php'], '<circle cx="9" cy="8" r="3.5" /><path d="M2.5 20a6.5 6.5 0 0 1 13 0" /><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.3a6.5 6.5 0 0 1 3.5 5.7" />'],
    'orders.php' => ['Orders', ['orders.php'], '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z" /><path d="M9 8h6M9 12h6" />'],
    'messages.php' => ['Messages', ['messages.php'], '<rect x="3" y="5" width="18" height="14" rx="2" /><path d="m4 7 8 6 8-6" />'],
    'profile.php' => ['Profile', ['profile.php'], '<circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" />'],
];

// Unread contact messages, shown next to "Messages" (the table is created by the first message, so it may not exist yet)
$unread_messages = 0;
try {
    $unread_messages = (int) $conn->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetch_row()[0];
} catch (Throwable $e) {
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require __DIR__ . '/../../includes/theme.php'; // dark mode, the same switch as the shop ?>
    <script>document.documentElement.classList.add('has-js');</script>
    <title><?php echo htmlspecialchars(($admin_nav[$current_page][0] ?? ['add_book.php' => 'Add Book', 'edit_book.php' => 'Edit Book'][$current_page] ?? 'Admin') . ' - Admin - KITAB'); ?></title>
<?php require __DIR__ . '/../../includes/favicon.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/kitab.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/glass-buttons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin-flat.css">
    <noscript><style>.k-adminside__theme { display: none; }</style></noscript>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js" defer></script>
    <script src="<?php echo BASE_URL; ?>assets/js/admin.js" defer></script>
</head>

<body class="k-adminbody is-gbtn">
    <?php // Phones and tablets: a slim bar with the name and a menu button; the menu below slides open under it (assets/js/admin.js) ?>
    <div class="k-adminbar">
        <a href="<?php echo BASE_URL; ?>admin/index.php" class="k-adminbar__brand">KITAB<span>.</span> <small>Admin</small></a>
        <button type="button" class="k-cart k-navbtn k-adminbar__btn" data-admin-menu aria-expanded="false" aria-controls="k-adminside" aria-label="Menu">
            <svg class="k-navbtn__open" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
            <svg class="k-navbtn__close" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
        </button>
    </div>

    <aside class="k-adminside" id="k-adminside">
        <div class="k-adminside__brand">
            <span class="k-adminside__logo">KITAB<span>.</span></span>
            <span class="k-adminside__tag">Admin</span>
        </div>
        <nav aria-label="Admin">
            <ul class="k-adminside__nav">
                <?php foreach ($admin_nav as $file => [$label, $active_files, $icon]): ?>
                    <?php $is_active = in_array($current_page, $active_files, true); ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/<?php echo $file; ?>"
                            class="k-adminside__link<?php echo $is_active ? ' is-active' : ''; ?>"
                            <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $icon; ?></svg>
                            <?php echo $label; ?>
                            <?php if ($file === 'messages.php' && $unread_messages > 0): ?>
                                <span class="k-adminside__badge" aria-label="<?php echo $unread_messages; ?> unread"><?php echo $unread_messages; ?></span>
                            <?php endif; ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div class="k-adminside__foot">
            <a href="<?php echo BASE_URL; ?>index.php" class="k-adminside__link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" /></svg>
                View Website</a>
            <button type="button" class="k-adminside__link k-adminside__theme" data-theme-toggle aria-pressed="false" aria-label="Dark mode">
                <svg class="k-adminside__moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 14.6A8.4 8.4 0 0 1 9.4 4a8.4 8.4 0 1 0 10.6 10.6Z" /></svg>
                <svg class="k-adminside__sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4" /><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4" /></svg>
                <span class="k-adminside__to-dark">Dark mode</span><span class="k-adminside__to-light">Light mode</span>
            </button>
            <a href="<?php echo BASE_URL; ?>frontend/logout.php" class="k-adminside__link k-adminside__link--danger">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h4M16 8l4 4-4 4M20 12H9" /></svg>
                Logout</a>
        </div>
    </aside>
