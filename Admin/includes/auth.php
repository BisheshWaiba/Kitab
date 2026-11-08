<?php
// Shared by every admin page (through header.php) and by admin actions that output nothing (delete_book.php):
// starts the session, connects to the database and keeps everyone but admins out.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(dirname(__DIR__)) . '/config/db.php';

// Security Check: must be logged in AND hold the admin role (a session with no role is not an admin)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: " . BASE_URL . "frontend/login.php");
    exit();
}
