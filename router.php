<?php
// Router for PHP's built-in server, so a missing address shows the 404.php page instead of the server's plain one:
//
//     php -S 127.0.0.1:8000 router.php
//
// Files and folders that exist are served as usual. Anything else (and anything that starts with a dot, such as
// .git) gets the 404 page. Apache does the same job through .htaccess.

$root = realpath(__DIR__);
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$target = strpos($path, "\0") === false ? realpath($root . $path) : false;

$exists = $path === '/' || (
    strpos($path, "\0") === false
    && $target !== false
    && (DIRECTORY_SEPARATOR === '\\' ? strncasecmp($target, $root, strlen($root)) : strncmp($target, $root, strlen($root))) === 0
    && !preg_match('#(^|/)\.#', $path)
    && (!is_dir($target) || is_file($target . '/index.php') || is_file($target . '/index.html'))
);

if ($exists) {
    return false; // serve the file (or the folder's index page) as it is
}

// Pretend the request was for 404.php, so the page finds its own address right
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/404.php';
require __DIR__ . '/404.php';
