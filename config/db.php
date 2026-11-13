<?php
// Database settings and the public address of the site. The database is reached through config/connect.php:
// XAMPP defaults on your own computer, DB_* environment variables once the site is online.
require_once __DIR__ . '/connect.php';

$host = kitab_env('DB_HOST', 'localhost');
$user = kitab_env('DB_USER', 'root');
$pass = kitab_env('DB_PASS', kitab_env('DB_PASSWORD', '')); // empty = the default XAMPP password
$db_name = kitab_env('DB_NAME', '25123794');

// An online host ends the https connection before PHP sees it and says so in this header
$forwarded_https = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
$scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwarded_https) ? 'https' : 'http';
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

// Emails the shop sends (password reset links). Set MAIL_FROM online; the default is only a placeholder.
define('MAIL_FROM', kitab_env('MAIL_FROM', 'KITAB <no-reply@kitab.example>'));
// Public address of the site, for example 'https://kitab.example/'. Links in emails are built from it. Leave it empty
// while you work on your own computer; set the SITE_URL environment variable once the site is online, so a link never
// depends on the Host header of the request that asked for it.
define('SITE_URL', kitab_env('SITE_URL', ''));

$conn = kitab_db_connect();
?>
