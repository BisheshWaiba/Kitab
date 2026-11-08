<?php
// The "forgot password" flow (frontend/forgot_password.php and frontend/reset_password.php). Needs config/db.php.
//
// How it works: asking for a reset stores a random token (only its SHA-256 hash is kept) and emails a link that holds
// the token. The link works once, for RESET_MINUTES. Using it sets a new password and removes the token.

const RESET_MINUTES = 60;        // how long an emailed link keeps working
const RESET_COOLDOWN = 60;       // seconds before another link can be requested for the same account
const RESET_MIN_PASSWORD = 8;    // shortest new password
const RESET_MAX_PASSWORD = 72;   // longest password that bcrypt really uses (it ignores anything after that)

// Created on first use, so an existing database needs no manual step
function k_reset_table(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY token_hash (token_hash),
            KEY user_id (user_id),
            CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

// True for a request that comes from this very computer (never for a visitor over the network)
function k_is_loopback(): bool
{
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
}

function k_reset_link(string $token): string
{
    $base = SITE_URL !== '' ? rtrim(SITE_URL, '/') . '/' : BASE_URL;
    return $base . 'frontend/reset_password.php?token=' . $token;
}

// Plain-text email through PHP's mail(). Returns false when the server cannot send mail.
function k_send_mail(string $to, string $subject, string $body): bool
{
    $to = str_replace(["\r", "\n"], '', $to);
    $headers = "From: " . MAIL_FROM . "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8";
    return @mail($to, $subject, $body, $headers);
}

// Starts a reset for the account with this email, if there is one.
// Returns null when nothing was done (no such account, or a link was requested a moment ago), otherwise
// ['sent' => whether the email went out, 'link' => the reset link]. The caller must answer the same way
// in every case, so the form does not reveal which emails have an account.
function k_request_reset(mysqli $conn, string $email): ?array
{
    k_reset_table($conn);
    $conn->query("DELETE FROM password_resets WHERE expires_at < NOW()");

    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) {
        return null;
    }

    $cooldown = RESET_COOLDOWN;
    $stmt = $conn->prepare("SELECT 1 FROM password_resets WHERE user_id = ? AND created_at > NOW() - INTERVAL ? SECOND");
    $stmt->bind_param("ii", $user['id'], $cooldown);
    $stmt->execute();
    if ($stmt->get_result()->fetch_row()) {
        return null;
    }

    // One live link per account: a new request replaces the old one
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $minutes = RESET_MINUTES;
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL ? MINUTE)");
    $stmt->bind_param("isi", $user['id'], $hash, $minutes);
    $stmt->execute();

    $link = k_reset_link($token);
    $body = "Hello " . str_replace(["\r", "\n"], ' ', $user['name']) . ",\r\n\r\n"
        . "Someone asked to reset the password of your KITAB account. To choose a new password, open this link within "
        . RESET_MINUTES . " minutes:\r\n\r\n" . $link . "\r\n\r\n"
        . "If you did not ask for this, you can ignore this email. Your password stays as it is.\r\n\r\nKITAB\r\n";
    $sent = k_send_mail($user['email'], 'Reset your KITAB password', $body);
    if (!$sent) {
        error_log('KITAB: could not send a password reset email (is mail set up on this server?) for user ' . $user['id']);
    }
    return ['sent' => $sent, 'link' => $link];
}

// The pending reset a link from an email belongs to, or null when it is unknown, used or expired
function k_find_reset(mysqli $conn, string $token): ?array
{
    k_reset_table($conn);
    $hash = hash('sha256', $token);
    $stmt = $conn->prepare("SELECT id, user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

// Sets the new password and uses the link up. All or nothing, and only one of two simultaneous tries can win.
function k_finish_reset(mysqli $conn, int $reset_id, int $user_id, string $password): bool
{
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE id = ? AND expires_at > NOW()");
        $stmt->bind_param("i", $reset_id);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) {
            $conn->rollback();
            return false;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $user_id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        return false;
    }
}
