<?php
// Hands out a picture that is kept in the database (UPLOAD_STORE=db, see k_upload_store() in includes/helpers.php).
// A request for uploads/<folder>/<name> that matches no file on disk is sent here by .htaccess (and by router.php on
// PHP's built-in server) as serve_upload.php?path=uploads/<folder>/<name>.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$path = (string) ($_GET['path'] ?? '');
$valid = preg_match('#^uploads/[A-Za-z0-9._-]+(/[A-Za-z0-9._-]+)*$#', $path) === 1 && strpos($path, '..') === false;

$row = null;
if ($valid && k_upload_store() === 'db') {
    $stmt = k_upload_table()->prepare("SELECT mime, data FROM kitab_uploads WHERE path = ?");
    $stmt->bind_param("s", $path);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
}

if (!$row) {
    // Not here either: show the normal 404 page
    $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/404.php';
    require __DIR__ . '/404.php';
    exit;
}

// The names carry a time and random digits, so a picture never changes: browsers may keep it for good
header('Content-Type: ' . $row['mime']);
header('Content-Length: ' . strlen($row['data']));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=31536000, immutable');
echo $row['data'];
