<?php
// Keeps PHP sessions (who is logged in, the cart, the wishlist hearts) in the database instead of in files.
//
// An online host such as Vercel runs the site on several short-lived servers that do not share a disk, so file sessions
// would log people out at random. The Docker image switches this on (SESSION_STORE=db) and loads this file before every
// page through php.ini's auto_prepend_file (see docker/kitab.ini). On your own computer SESSION_STORE is not set, so this
// file does nothing and PHP keeps using its normal session files.

if (getenv('SESSION_STORE') !== 'db') {
    return;
}

require_once dirname(__DIR__) . '/config/connect.php';

final class KitabSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    private bool $tableChecked = false;

    // Runs one prepared statement. The table is created the first time it turns out to be missing (error 1146).
    private function run(string $sql, string $types = '', array $args = []): ?mysqli_stmt
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $conn = kitab_db_connect();
            try {
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    throw new mysqli_sql_exception($conn->error, $conn->errno);
                }
                if ($types !== '') {
                    $stmt->bind_param($types, ...$args);
                }
                if (!$stmt->execute()) {
                    throw new mysqli_sql_exception($stmt->error, $stmt->errno);
                }
                return $stmt;
            } catch (Throwable $e) {
                if ($e->getCode() === 1146 && !$this->tableChecked) {
                    $this->tableChecked = true;
                    $conn->query("CREATE TABLE IF NOT EXISTS php_sessions (
                        id VARCHAR(128) NOT NULL PRIMARY KEY,
                        data MEDIUMBLOB NOT NULL,
                        updated_at INT UNSIGNED NOT NULL,
                        KEY updated_at (updated_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    continue;
                }
                error_log('KITAB: session store error: ' . $e->getMessage());
                return null;
            }
        }
        return null;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->run("SELECT data FROM php_sessions WHERE id = ?", 's', [$id]);
        if ($stmt === null) {
            return '';
        }
        // Buffer and close the result, so the page's own queries on this connection are never blocked by it
        $stmt->store_result();
        $stmt->bind_result($data);
        $found = $stmt->fetch();
        $stmt->close();
        return $found ? (string) $data : '';
    }

    public function write(string $id, string $data): bool
    {
        return $this->run("REPLACE INTO php_sessions (id, data, updated_at) VALUES (?, ?, ?)", 'ssi', [$id, $data, time()]) !== null;
    }

    public function destroy(string $id): bool
    {
        $this->run("DELETE FROM php_sessions WHERE id = ?", 's', [$id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->run("DELETE FROM php_sessions WHERE updated_at < ?", 'i', [time() - $max_lifetime]);
        return $stmt === null ? 0 : $stmt->affected_rows;
    }

    // With session.use_strict_mode on, PHP asks whether an id it was sent really exists, and never adopts one that does not
    public function validateId(string $id): bool
    {
        $stmt = $this->run("SELECT 1 FROM php_sessions WHERE id = ?", 's', [$id]);
        if ($stmt === null) {
            return false;
        }
        $stmt->store_result();
        $found = $stmt->num_rows > 0;
        $stmt->close();
        return $found;
    }

    // Called instead of write() when the session did not change (session.lazy_write): saves a heavy write per page
    public function updateTimestamp(string $id, string $data): bool
    {
        return $this->run("UPDATE php_sessions SET updated_at = ? WHERE id = ?", 'is', [time(), $id]) !== null;
    }
}

$online_https = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if ($online_https) {
    ini_set('session.cookie_secure', '1');
}

session_set_save_handler(new KitabSessionHandler(), true);
