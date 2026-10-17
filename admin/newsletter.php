<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require APP_ROOT . '/includes/admin.php';
require_admin();

if (is_post()) {
    csrf_check();
    db_exec("UPDATE newsletter_subscribers SET status = IF(status = 'active', 'unsubscribed', 'active') WHERE id = ?", [input_int('id')]);
    flash('success', t('admin.updated'));
    redirect(url('admin/newsletter.php'));
}
$status = in_array($_GET['status'] ?? '', ['active', 'unsubscribed'], true) ? $_GET['status'] : '';
$rows = db_all('SELECT n.*, u.first_name, u.last_name FROM newsletter_subscribers n LEFT JOIN users u ON u.id = n.user_id'
    . ($status ? ' WHERE n.status = ?' : '') . ' ORDER BY n.created_at DESC', $status ? [$status] : []);
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter-subscribers.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['email', 'name', 'language', 'status', 'consent_at', 'created_at']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['email'], $r['name'] ?? '', $r['lang'], $r['status'], $r['consent_at'], $r['created_at']]);
    }
    exit;
}
$byLang = db_all("SELECT lang, COUNT(*) AS n FROM newsletter_subscribers WHERE status = 'active' GROUP BY lang ORDER BY n DESC");

admin_start('newsletter', t('admin.newsletter'));
?>
<div class="admin-toolbar">
  <nav class="filter-pills" style="margin:0">
    <a href="<?= e(url('admin/newsletter.php')) ?>"<?= $status === '' ? ' aria-current="page"' : '' ?>><?= e(t('admin.all')) ?></a>
    <a href="<?= e(url('admin/newsletter.php', ['status' => 'active'])) ?>"<?= $status === 'active' ? ' aria-current="page"' : '' ?>><?= e(t('status.active')) ?></a>
    <a href="<?= e(url('admin/newsletter.php', ['status' => 'unsubscribed'])) ?>"<?= $status === 'unsubscribed' ? ' aria-current="page"' : '' ?>><?= e(t('status.unsubscribed')) ?></a>
  </nav>
  <a class="btn btn--primary" href="<?= e(current_url_with(['export' => 'csv'])) ?>"><?= icon('download') ?><?= e(t('admin.export_csv')) ?></a>
</div>
<p class="muted"><?= e(t('admin.records', ['n' => count($rows)])) ?> — <?= e(implode(', ', array_map(static fn($l) => strtoupper($l['lang']) . ': ' . $l['n'], $byLang))) ?></p>
<div class="table-wrap panel" style="padding:0">
  <table class="data-table">
    <thead><tr><th><?= e(t('form.email')) ?></th><th><?= e(t('form.name')) ?></th><th><?= e(t('account.language')) ?></th><th><?= e(t('admin.col_consent')) ?></th><th><?= e(t('ticket.status')) ?></th><th><?= e(t('admin.actions')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= e($r['email']) ?></td><td><?= e($r['name'] ?? trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: '—') ?></td><td><?= e(strtoupper($r['lang'])) ?></td>
        <td><?= e(date('Y-m-d H:i', strtotime($r['consent_at']))) ?></td><td><?= status_badge($r['status']) ?></td>
        <td><form method="post" action="<?= e(url('admin/newsletter.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <button class="btn btn--ghost btn--sm" type="submit"><?= e(t($r['status'] === 'active' ? 'admin.unsubscribe' : 'admin.resubscribe')) ?></button></form></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php admin_end();
