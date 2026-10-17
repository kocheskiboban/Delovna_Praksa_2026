<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require APP_ROOT . '/includes/admin.php';
require_admin();

if (is_post()) {
    csrf_check();
    $uid = input_int('user_id');
    $op = input('op', '', 'post');
    if ($uid === user_id()) {
        flash('error', t('admin.err_self'));
    } elseif ($op === 'toggle_active') {
        db_exec('UPDATE users SET is_active = 1 - is_active WHERE id = ?', [$uid]);
        flash('success', t('admin.updated'));
    } elseif ($op === 'role') {
        $role = input('role', '', 'post') === 'admin' ? 1 : 2;
        db_exec('UPDATE users SET role_id = ? WHERE id = ?', [$role, $uid]);
        flash('success', t('admin.updated'));
    }
    redirect(safe_return($_POST['return'] ?? '', url('admin/users.php')));
}
$q = mb_substr(input('q', '', 'get'), 0, 80);
$role = input('role', '', 'get');
$where = [];
$p = [];
if ($q !== '') {
    $where[] = '(u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)';
    array_push($p, "%$q%", "%$q%", "%$q%");
}
if (in_array($role, ['admin', 'user'], true)) {
    $where[] = 'r.code = ?';
    $p[] = $role;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$users = db_all("SELECT u.*, r.code AS role, r.name AS role_name,
    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders,
    (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.user_id = u.id AND o.status = 'paid') AS spent
    FROM users u JOIN roles r ON r.id = u.role_id $whereSql ORDER BY u.created_at DESC LIMIT 200", $p);
$roles = db_all('SELECT * FROM roles ORDER BY id');

admin_start('users', t('admin.users'));
?>
<form method="get" class="admin-filters admin-toolbar" action="<?= e(url('admin/users.php')) ?>">
  <?php if (lang() !== config('app.default_lang')): ?><input type="hidden" name="lang" value="<?= e(lang()) ?>"><?php endif; ?>
  <label class="visually-hidden" for="uq"><?= e(t('admin.search')) ?></label>
  <input class="search-input" id="uq" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('admin.search_users')) ?>">
  <div class="select"><select class="search-input" name="role" aria-label="<?= e(t('admin.col_role')) ?>"><option value=""><?= e(t('admin.all')) ?></option>
    <?php foreach ($roles as $r): ?><option value="<?= e($r['code']) ?>"<?= $role === $r['code'] ? ' selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?></select><?= icon('chevron-down') ?></div>
  <button class="btn btn--ghost" type="submit"><?= icon('filter') ?><?= e(t('programme.apply')) ?></button>
</form>
<p class="muted"><?= e(t('admin.roles_note')) ?>: <?= e(implode(', ', array_map(static fn($r) => $r['code'] . ' (' . $r['name'] . ')', $roles))) ?></p>
<div class="table-wrap panel" style="padding:0">
  <table class="data-table">
    <thead><tr><th><?= e(t('form.name')) ?></th><th><?= e(t('form.email')) ?></th><th><?= e(t('admin.col_role')) ?></th><th><?= e(t('account.language')) ?></th><th><?= e(t('admin.col_orders')) ?></th><th><?= e(t('admin.col_registered')) ?></th><th><?= e(t('admin.col_last_login')) ?></th><th><?= e(t('ticket.status')) ?></th><th><?= e(t('admin.actions')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): $self = (int) $u['id'] === user_id(); ?>
      <tr>
        <td><strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong></td>
        <td><?= e($u['email']) ?></td>
        <td>
          <form method="post" action="<?= e(url('admin/users.php')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="op" value="role"><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
            <label class="visually-hidden" for="role-<?= (int) $u['id'] ?>"><?= e(t('admin.col_role')) ?></label>
            <select id="role-<?= (int) $u['id'] ?>" name="role" <?= $self ? 'disabled' : '' ?> data-autosubmit><option value="user"<?= $u['role'] === 'user' ? ' selected' : '' ?>>user</option><option value="admin"<?= $u['role'] === 'admin' ? ' selected' : '' ?>>admin</option></select>
            <noscript><button class="btn btn--ghost btn--sm" type="submit"><?= e(t('admin.save')) ?></button></noscript>
          </form>
        </td>
        <td><?= e(strtoupper($u['preferred_lang'])) ?></td>
        <td><?= (int) $u['orders'] ?> / <?= e(money($u['spent'])) ?></td>
        <td><?= e(date('Y-m-d', strtotime($u['created_at']))) ?></td>
        <td><?= e($u['last_login_at'] ? date('Y-m-d H:i', strtotime($u['last_login_at'])) : '—') ?></td>
        <td><?= status_badge($u['is_active'] ? 'active' : 'inactive') ?></td>
        <td>
          <?php if (!$self): ?>
          <form method="post" action="<?= e(url('admin/users.php')) ?>" data-confirm="<?= e(t($u['is_active'] ? 'admin.confirm_deactivate' : 'admin.confirm_activate')) ?>"><?= csrf_field() ?>
            <input type="hidden" name="op" value="toggle_active"><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
            <button class="btn btn--ghost btn--sm" type="submit"><?= e(t($u['is_active'] ? 'admin.deactivate' : 'admin.activate')) ?></button></form>
          <?php else: ?><span class="muted"><?= e(t('admin.you')) ?></span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php admin_end(['scripts' => ['assets/js/admin.js']]);
