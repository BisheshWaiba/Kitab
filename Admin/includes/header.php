<?php
session_start();
require_once dirname(dirname(__DIR__)) . '/config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin')) {
    header("Location: " . BASE_URL . "frontend/login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KITAB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap"
        rel="stylesheet">
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-logo">KITAB Admin</div>
        <ul class="sidebar-menu">
            <li>
                <a href="<?php echo BASE_URL; ?>admin/index.php"
                    class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/books.php"
                    class="<?php echo $current_page == 'books.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i> Books
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/users.php"
                    class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/orders.php"
                    class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-bag"></i> Orders
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>admin/profile.php"
                    class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i> Profile
                </a>
            </li>
            <li class="nav-link-separator">
                <a href="<?php echo BASE_URL; ?>index.php">
                    <i class="fas fa-home"></i> View Website
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>frontend/logout.php" style="color: #ef4444;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </aside>