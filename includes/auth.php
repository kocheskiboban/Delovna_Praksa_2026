<?php

declare(strict_types=1);

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW_MIN = 15;

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id > 0) {
        try {
            $row = db_one('SELECT u.*, r.code AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?', [$id]);
        } catch (Throwable $e) {
            $row = null;
        }
        if ($row && (int) $row['is_active'] === 1) {
            unset($row['password_hash']);
            $user = $row;
        } else {
            unset($_SESSION['user_id']);
        }
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

function user_id(): int
{
    return (int) (current_user()['id'] ?? 0);
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['_created'] = time();
    db_exec('UPDATE users SET last_login_at = NOW() WHERE id = ?', [(int) $user['id']]);
    if (empty($_GET['lang']) && is_supported_lang($user['preferred_lang'] ?? null) && empty($_SESSION['lang_chosen'])) {
        $_SESSION['lang'] = $user['preferred_lang'];
    }
}

function logout_user(): void
{
    $lang = $_SESSION['lang'] ?? null;
    $_SESSION = [];
    session_regenerate_id(true);
    if ($lang) {
        $_SESSION['lang'] = $lang;
    }
}

function safe_return(?string $to, string $fallback): string
{
    $to = (string) $to;
    if ($to !== '' && $to[0] === '/' && !str_starts_with($to, '//') && !preg_match('/[\r\n\\\\]/', $to)) {
        return $to;
    }
    return $fallback;
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('info', t('auth.login_required'));
        redirect(url('account/login.php', ['return' => $_SERVER['REQUEST_URI'] ?? '']));
    }
}

function require_admin(): void
{
    if (!is_logged_in()) {
        flash('info', t('auth.login_required'));
        redirect(url('account/login.php', ['return' => $_SERVER['REQUEST_URI'] ?? '']));
    }
    if (!is_admin()) {
        render_error_page(403, 'forbidden');
    }
}

function too_many_attempts(string $email): bool
{
    $n = (int) db_value(
        'SELECT COUNT(*) FROM login_attempts WHERE (email = ? OR ip_hash = ?) AND attempted_at > (NOW() - INTERVAL ' . LOGIN_WINDOW_MIN . ' MINUTE)',
        [mb_strtolower($email), client_ip_hash()]
    );
    return $n >= LOGIN_MAX_ATTEMPTS * 2 || (int) db_value(
        'SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > (NOW() - INTERVAL ' . LOGIN_WINDOW_MIN . ' MINUTE)',
        [mb_strtolower($email)]
    ) >= LOGIN_MAX_ATTEMPTS;
}

function record_failed_login(string $email): void
{
    db_exec('INSERT INTO login_attempts (email, ip_hash) VALUES (?, ?)', [mb_strtolower($email), client_ip_hash()]);
}

function clear_failed_logins(string $email): void
{
    db_exec('DELETE FROM login_attempts WHERE email = ?', [mb_strtolower($email)]);
}

function password_problems(string $pw): array
{
    $errors = [];
    if (mb_strlen($pw) < 8) {
        $errors[] = 'validation.password_length';
    }
    if (!preg_match('/\p{L}/u', $pw) || !preg_match('/\d/', $pw)) {
        $errors[] = 'validation.password_mix';
    }
    return $errors;
}
