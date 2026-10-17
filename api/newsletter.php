<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
$respond = static function (bool $ok, string $msg, int $code = 200) use ($wantsJson): void {
    if ($wantsJson) {
        json_response(['ok' => $ok, 'message' => $msg], $code);
    }
    flash($ok ? 'success' : 'error', $msg);
    redirect(safe_return($_POST['return'] ?? '', url('')));
};
if (!is_post()) {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}
csrf_check();
$email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
if (!is_valid_email($email)) {
    $respond(false, t('newsletter.invalid'), 422);
}
if (empty($_POST['consent'])) {
    $respond(false, t('newsletter.consent_required'), 422);
}
$row = db_one('SELECT * FROM newsletter_subscribers WHERE email = ?', [$email]);
if ($row && $row['status'] === 'active') {
    $respond(true, t('newsletter.duplicate'));
}
if ($row) {
    db_exec("UPDATE newsletter_subscribers SET status = 'active', consent_at = NOW(), lang = ? WHERE id = ?", [lang(), (int) $row['id']]);
    $respond(true, t('newsletter.resubscribed'));
}
db_exec('INSERT INTO newsletter_subscribers (email, lang, consent_at, token, user_id) VALUES (?, ?, NOW(), ?, ?)',
    [$email, lang(), bin2hex(random_bytes(16)), is_logged_in() ? user_id() : null]);
if (is_logged_in() && current_user()['email'] === $email) {
    db_exec('UPDATE users SET newsletter = 1 WHERE id = ?', [user_id()]);
}
$respond(true, t('newsletter.success'));
