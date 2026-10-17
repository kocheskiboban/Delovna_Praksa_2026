<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require APP_ROOT . '/includes/admin.php';
require_admin();

if (is_post()) {
    csrf_check();
    $st = input('status', '', 'post');
    if (input('op', '', 'post') === 'delete') {
        db_exec('DELETE FROM contact_messages WHERE id = ?', [input_int('id')]);
        flash('success', t('admin.deleted'));
    } elseif (in_array($st, ['new', 'read', 'answered'], true)) {
        db_exec('UPDATE contact_messages SET status = ? WHERE id = ?', [$st, input_int('id')]);
        flash('success', t('admin.updated'));
    }
    redirect(url('admin/messages.php'));
}
$rows = db_all("SELECT * FROM contact_messages ORDER BY FIELD(status, 'new', 'read', 'answered'), created_at DESC");
admin_start('messages', t('admin.messages'));
?>
<?php if (!$rows): ?><?= empty_state('mail', t('admin.no_messages')) ?><?php endif; ?>
<?php foreach ($rows as $m): ?>
  <details class="panel" <?= $m['status'] === 'new' ? 'open' : '' ?>>
    <summary class="panel__head" style="cursor:pointer;list-style:none;margin:0">
      <div><h2 style="font-size:var(--step-1);margin:0"><?= e($m['subject']) ?></h2>
        <p class="muted" style="margin:0"><?= e($m['name']) ?> &lt;<?= e($m['email']) ?>&gt; — <?= e(t('contact.topic_' . $m['topic'])) ?> — <?= e(date('Y-m-d H:i', strtotime($m['created_at']))) ?> — <?= e(strtoupper($m['lang'])) ?></p></div>
      <?= status_badge($m['status']) ?>
    </summary>
    <div style="margin-top:var(--s-4)">
      <p style="white-space:pre-line"><?= e($m['message']) ?></p>
      <?php if ($m['phone']): ?><p><?= icon('phone') ?> <?= e($m['phone']) ?></p><?php endif; ?>
      <div class="links-row">
        <a class="btn btn--primary btn--sm" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['subject']) ?>"><?= icon('mail') ?><?= e(t('admin.reply')) ?></a>
        <?php foreach (['read', 'answered', 'new'] as $st): if ($st === $m['status']) continue; ?>
          <form method="post" action="<?= e(url('admin/messages.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="status" value="<?= $st ?>">
            <button class="btn btn--ghost btn--sm" type="submit"><?= e(t('admin.mark_as', ['s' => t('status.' . $st)])) ?></button></form>
        <?php endforeach; ?>
        <form method="post" action="<?= e(url('admin/messages.php')) ?>" data-confirm="<?= e(t('admin.confirm_delete')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="op" value="delete">
          <button class="btn btn--ghost btn--sm" type="submit"><?= icon('trash') ?><?= e(t('admin.delete')) ?></button></form>
      </div>
    </div>
  </details>
<?php endforeach; ?>
<?php admin_end();
