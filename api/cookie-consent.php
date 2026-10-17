<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

if (!is_post()) {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}
csrf_check();
$choice = input('choice', 'custom', 'post');
$prefs = $choice === 'all' ? true : ($choice === 'necessary' ? false : !empty($_POST['preferences']));
$analytics = $choice === 'all' ? true : ($choice === 'necessary' ? false : !empty($_POST['analytics']));
$existing = cookie_consent();
$id = $existing['id'] ?? '';
if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
    $id = bin2hex(random_bytes(16));
}
$value = json_encode(['v' => 1, 'p' => $prefs ? 1 : 0, 'a' => $analytics ? 1 : 0, 'id' => $id, 't' => time()]);
setcookie('os_consent', $value, ['expires' => time() + 180 * 86400, 'path' => base_path() . '/', 'samesite' => 'Lax', 'httponly' => false,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
if (!$prefs) {
    setcookie('os_lang', '', ['expires' => time() - 3600, 'path' => base_path() . '/']);
} else {
    setcookie('os_lang', lang(), ['expires' => time() + 31536000, 'path' => base_path() . '/', 'samesite' => 'Lax']);
}
db_exec('INSERT INTO cookie_preferences (consent_id, user_id, preferences, analytics, ip_hash, user_agent) VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE preferences = VALUES(preferences), analytics = VALUES(analytics), user_id = COALESCE(VALUES(user_id), user_id)',
    [$id, is_logged_in() ? user_id() : null, $prefs ? 1 : 0, $analytics ? 1 : 0, client_ip_hash(), mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250)]);
if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
    json_response(['ok' => true, 'preferences' => $prefs, 'analytics' => $analytics, 'message' => t('cookie.saved')]);
}
flash('success', t('cookie.saved'));
redirect(safe_return($_POST['return'] ?? '', url('')));
