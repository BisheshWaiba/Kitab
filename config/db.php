<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password
$db_name = '25123794';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$httpHost = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$publicRoot = '/';

$segments = array_values(array_filter(explode('/', trim($scriptName, '/')), function ($segment) {
    return $segment !== '';
}));

foreach ($segments as $index => $segment) {
    if (in_array(strtolower($segment), ['frontend', 'admin', 'assets', 'includes'], true)) {
        $prefixSegments = array_slice($segments, 0, $index);
        if (!empty($prefixSegments)) {
            $publicRoot = '/' . implode('/', $prefixSegments) . '/';
        }
        break;
    }
}

define('BASE_URL', $scheme . '://' . $httpHost . $publicRoot);

// Emails the shop sends (password reset links)
define('MAIL_FROM', 'KITAB <no-reply@kitab.example>'); // CHANGE: the "from" address of those emails
// Public address of the site, for example 'https://kitab.example/'. Links in emails are built from it. Leave it empty
// while you work on your own computer; set it once the site is online, so a link never depends on the Host header
// of the request that asked for it.
define('SITE_URL', '');

$conn = new mysqli($host, $user, $pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>