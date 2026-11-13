<?php
// The one place that knows how to reach the database.
//
// On your own computer (XAMPP) nothing needs to be set: it connects to localhost as root with the database below.
// An online host sets environment variables instead (Vercel: Project > Settings > Environment Variables):
//
//     DB_HOST   DB_PORT (default 3306)   DB_USER   DB_PASS   DB_NAME
//     DB_SSL=1       when the database host wants an encrypted connection (most hosted MySQL services do)
//     DB_SSL_CA      optional: path to the host's CA certificate file, to also check the server's identity

// An environment variable, or $default when it is not set (or empty)
function kitab_env(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
}

// The database connection, made once per request (the session store and the pages share it)
function kitab_db_connect(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    $host = kitab_env('DB_HOST', 'localhost');
    $user = kitab_env('DB_USER', 'root');
    $pass = kitab_env('DB_PASS', kitab_env('DB_PASSWORD', ''));
    $name = kitab_env('DB_NAME', '25123794');
    $port = (int) kitab_env('DB_PORT', '3306');

    try {
        $link = mysqli_init();
        $link->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
        $flags = 0;
        if (kitab_env('DB_SSL') !== '') {
            $link->ssl_set(null, null, kitab_env('DB_SSL_CA') ?: null, null, null);
            $flags = MYSQLI_CLIENT_SSL;
        }
        $link->real_connect($host, $user, $pass, $name, $port, null, $flags);
    } catch (Throwable $e) {
        // The reason goes to the server log, never to the visitor (it can name the host and the user)
        error_log('KITAB: could not connect to the database: ' . $e->getMessage());
        http_response_code(503);
        header('Retry-After: 30');
        exit('The shop is temporarily unavailable. Please try again in a moment.');
    }

    $conn = $link;
    return $conn;
}
