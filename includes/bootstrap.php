<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_START', microtime(true));

$GLOBALS['config'] = require __DIR__ . '/config.php';

function config(string $path, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $path) as $key) {
        if (!is_array($value) || !array_key_exists($key, $value)) {
            return $default;
        }
        $value = $value[$key];
    }
    return $value;
}

date_default_timezone_set(config('app.timezone', 'Europe/Skopje'));
mb_internal_encoding('UTF-8');

$isDev = config('app.env') === 'development';
error_reporting(E_ALL);
ini_set('display_errors', $isDev ? '1' : '0');
ini_set('log_errors', '1');

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/i18n.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/repo/content.php';
require __DIR__ . '/booking.php';
require __DIR__ . '/components/icons.php';
require __DIR__ . '/components/ui.php';
require __DIR__ . '/components/account.php';
require __DIR__ . '/layout.php';

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('OHRIDSESS');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => base_path() . '/',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
if (!isset($_SESSION['_created'])) {
    $_SESSION['_created'] = time();
} elseif (time() - $_SESSION['_created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; "
        . "style-src 'self' 'unsafe-inline'; font-src 'self'; "
        . "script-src 'self'; frame-src https://www.openstreetmap.org; connect-src 'self'; "
        . "form-action 'self'; base-uri 'self'; frame-ancestors 'self'");
}

set_exception_handler(function (Throwable $e) {
    error_log('[ohrid-summer] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (ob_get_level()) {
        @ob_end_clean();
    }
    $isDb = $e instanceof PDOException;
    if (is_api_request()) {
        json_response(['ok' => false, 'error' => t($isDb ? 'error.db_title' : 'error.500_title')], 500);
    }
    render_error_page(500, $isDb ? 'db' : 'server', config('app.env') === 'development' ? $e->getMessage() : null);
});

i18n_init();
track_page_view();
