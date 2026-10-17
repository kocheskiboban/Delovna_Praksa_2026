<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/components/legal.php';

$c = cookie_consent();
$state = static fn(bool $on) => $on ? '<span class="badge badge--success">' . e(t('cookie.on')) . '</span>' : '<span class="badge badge--muted">' . e(t('cookie.off')) . '</span>';
$table = '<h2 id="your-settings">' . e(t('cookie.your_settings')) . '</h2>'
    . ($c ? '<p>' . e(t('cookie.saved_choice')) . '</p>' : '<p>' . e(t('cookie.no_choice')) . '</p>')
    . '<div class="table-wrap"><table class="price-table"><thead><tr><th>' . e(t('cookie.category')) . '</th><th>' . e(t('cookie.cookies')) . '</th><th>' . e(t('cookie.status')) . '</th></tr></thead><tbody>'
    . '<tr><th scope="row">' . e(t('cookie.necessary')) . '</th><td><code>OHRIDSESS</code>, <code>os_consent</code></td><td>' . $state(true) . '</td></tr>'
    . '<tr><th scope="row">' . e(t('cookie.preferences')) . '</th><td><code>os_lang</code></td><td>' . $state((bool) ($c['preferences'] ?? false)) . '</td></tr>'
    . '<tr><th scope="row">' . e(t('cookie.analytics')) . '</th><td>' . e(t('cookie.analytics_tech')) . '</td><td>' . $state((bool) ($c['analytics'] ?? false)) . '</td></tr>'
    . '</tbody></table></div><p><button type="button" class="btn btn--primary" data-open-cookie-settings>' . icon('cookie') . e(t('cookie.change')) . '</button></p>';
render_legal_page('cookies', $table);
