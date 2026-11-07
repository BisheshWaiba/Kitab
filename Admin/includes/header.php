<?php
session_start();
require_once dirname(dirname(__DIR__)) . '/config/db.php';

// Security Check: must be logged in AND hold the admin role (a session with no role is not an admin)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: " . BASE_URL . "frontend/login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);

// file => [label, files that keep it highlighted]
$admin_nav = [
    'index.php' => ['Dashboard', ['index.php']],
    'books.php' => ['Books', ['books.php', 'add_book.php']],
    'users.php' => ['Users', ['users.php']],
    'orders.php' => ['Orders', ['orders.php']],
    'profile.php' => ['Profile', ['profile.php']],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KITAB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Admin/assets/css/admin-style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/kitab.css">
</head>

<body class="k-adminbody">
    <aside class="k-adminside">
        <div class="k-adminside__brand">
            <span class="k-adminside__logo">KITAB<span>.</span></span>
            <span class="k-adminside__tag">Admin</span>
        </div>
        <nav aria-label="Admin">
            <ul class="k-adminside__nav">
                <?php foreach ($admin_nav as $file => [$label, $active_files]): ?>
                    <?php $is_active = in_array($current_page, $active_files, true); ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>admin/<?php echo $file; ?>"
                            class="k-adminside__link<?php echo $is_active ? ' is-active' : ''; ?>"
                            <?php echo $is_active ? 'aria-current="page"' : ''; ?>><?php echo $label; ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div class="k-adminside__foot">
            <a href="<?php echo BASE_URL; ?>index.php" class="k-adminside__link">View Website</a>
            <a href="<?php echo BASE_URL; ?>frontend/logout.php" class="k-adminside__link k-adminside__link--danger">Logout</a>
        </div>
    </aside>
