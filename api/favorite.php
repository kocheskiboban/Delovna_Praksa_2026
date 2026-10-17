<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
if (!is_post()) {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}
csrf_check();
if (!is_logged_in()) {
    $wantsJson ? json_response(['ok' => false, 'login' => url('account/login.php'), 'error' => t('fav.login')], 401) : redirect(url('account/login.php'));
}
$type = input('type', '', 'post');
$id = input_int('id');
$table = ['event' => 'events', 'artist' => 'artists'][$type] ?? null;
if (!$table || $id < 1 || !db_value("SELECT 1 FROM `$table` WHERE id = ?", [$id])) {
    json_response(['ok' => false, 'error' => t('error.404_title')], 404);
}
$exists = db_value('SELECT id FROM favorites WHERE user_id = ? AND entity_type = ? AND entity_id = ?', [user_id(), $type, $id]);
if ($exists) {
    db_exec('DELETE FROM favorites WHERE id = ?', [(int) $exists]);
    $on = false;
} else {
    db_exec('INSERT IGNORE INTO favorites (user_id, entity_type, entity_id) VALUES (?, ?, ?)', [user_id(), $type, $id]);
    $on = true;
}
$msg = t($on ? 'fav.added' : 'fav.removed');
if ($wantsJson) {
    json_response(['ok' => true, 'on' => $on, 'message' => $msg, 'count' => (int) db_value('SELECT COUNT(*) FROM favorites WHERE user_id = ?', [user_id()])]);
}
flash('success', $msg);
redirect(safe_return($_POST['return'] ?? '', url('account/favorites.php')));
