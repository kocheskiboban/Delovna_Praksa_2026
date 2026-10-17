<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require APP_ROOT . '/includes/admin.php';
require_admin();
booking_cleanup();

$q = mb_substr(input('q', '', 'get'), 0, 80);
$status = input('status', '', 'get');
$method = input('method', '', 'get');
$eventId = input_int('event');
$where = [];
$p = [];
if ($q !== '') {
    $where[] = '(o.order_number LIKE ? OR o.customer_email LIKE ? OR o.customer_name LIKE ? OR EXISTS (SELECT 1 FROM tickets tq WHERE tq.order_id = o.id AND tq.ticket_code LIKE ?))';
    array_push($p, "%$q%", "%$q%", "%$q%", "%$q%");
}
if ($status === 'open') {
    $where[] = "o.status IN ('reserved','pending')";
} elseif (in_array($status, ['pending', 'reserved', 'paid', 'cancelled', 'failed', 'refunded'], true)) {
    $where[] = 'o.status = ?';
    $p[] = $status;
}
if (in_array($method, PAYMENT_METHODS, true)) {
    $where[] = 'o.payment_method = ?';
    $p[] = $method;
}
if ($eventId) {
    $where[] = 'o.event_id = ?';
    $p[] = $eventId;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
[$tSel, $tJoin] = tr_sql('event_translations', 'event_id', 'e', ['title' => 'event_title'], 'et');
$per = 25;
$page = current_page();
$total = (int) db_value("SELECT COUNT(*) FROM orders o $whereSql", $p);
$sum = (float) db_value("SELECT COALESCE(SUM(total),0) FROM orders o $whereSql", $p);
$rows = db_all("SELECT o.*, e.starts_at, $tSel, (SELECT COUNT(*) FROM tickets t WHERE t.order_id = o.id) AS n
    FROM orders o JOIN events e ON e.id = o.event_id $tJoin $whereSql ORDER BY o.created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['order', 'customer', 'email', 'event', 'status', 'method', 'total', 'created']);
    foreach (db_all("SELECT o.*, $tSel FROM orders o JOIN events e ON e.id = o.event_id $tJoin $whereSql ORDER BY o.created_at DESC", $p) as $o) {
        fputcsv($out, [$o['order_number'], $o['customer_name'], $o['customer_email'], $o['event_title'], $o['status'], $o['payment_method'], $o['total'], $o['created_at']]);
    }
    exit;
}

admin_start('orders', t('admin.orders'));
?>
<form method="get" class="admin-filters admin-toolbar" action="<?= e(url('admin/orders.php')) ?>">
  <?php if (lang() !== config('app.default_lang')): ?><input type="hidden" name="lang" value="<?= e(lang()) ?>"><?php endif; ?>
  <?php if ($eventId): ?><input type="hidden" name="event" value="<?= $eventId ?>"><?php endif; ?>
  <label class="visually-hidden" for="oq"><?= e(t('admin.search')) ?></label>
  <input class="search-input" id="oq" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('admin.search_orders')) ?>">
  <div class="select"><select name="status" class="search-input" aria-label="<?= e(t('ticket.status')) ?>"><option value=""><?= e(t('admin.all')) ?></option>
    <option value="open"<?= $status === 'open' ? ' selected' : '' ?>><?= e(t('account.reservations')) ?></option>
    <?php foreach (['paid', 'reserved', 'pending', 'cancelled', 'failed', 'refunded'] as $st): ?><option value="<?= $st ?>"<?= $status === $st ? ' selected' : '' ?>><?= e(t('status.' . $st)) ?></option><?php endforeach; ?>
  </select><?= icon('chevron-down') ?></div>
  <div class="select"><select name="method" class="search-input" aria-label="<?= e(t('orders.payment')) ?>"><option value=""><?= e(t('admin.all')) ?></option>
    <?php foreach (PAYMENT_METHODS as $m): ?><option value="<?= $m ?>"<?= $method === $m ? ' selected' : '' ?>><?= e(t('payment.method_' . $m)) ?></option><?php endforeach; ?>
  </select><?= icon('chevron-down') ?></div>
  <button class="btn btn--ghost" type="submit"><?= icon('filter') ?><?= e(t('programme.apply')) ?></button>
  <a class="btn btn--ghost" href="<?= e(current_url_with(['export' => 'csv', 'page' => null])) ?>"><?= icon('download') ?>CSV</a>
</form>
<p class="muted"><?= e(t('admin.records', ['n' => $total])) ?> — <?= e(t('orders.total')) ?>: <strong><?= e(money($sum)) ?></strong><?= $eventId ? ' — ' . e(t('admin.filtered_event')) . ' <a href="' . e(url('admin/orders.php')) . '">' . e(t('programme.reset')) . '</a>' : '' ?></p>
<div class="table-wrap panel" style="padding:0">
  <table class="data-table">
    <thead><tr><th><?= e(t('ticket.order')) ?></th><th><?= e(t('admin.col_customer')) ?></th><th><?= e(t('admin.col_event')) ?></th><th><?= e(t('admin.col_tickets')) ?></th><th><?= e(t('orders.payment')) ?></th><th><?= e(t('orders.total')) ?></th><th><?= e(t('ticket.status')) ?></th><th><?= e(t('admin.col_date')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $o): ?>
      <tr>
        <td><a href="<?= e(url('admin/order.php', ['id' => $o['id']])) ?>"><code><?= e($o['order_number']) ?></code></a></td>
        <td><?= e($o['customer_name']) ?><br><span class="muted"><?= e($o['customer_email']) ?></span></td>
        <td><?= e(str_limit($o['event_title'], 36)) ?><br><span class="muted"><?= e(date('Y-m-d', strtotime($o['starts_at']))) ?></span></td>
        <td><?= (int) $o['n'] ?></td>
        <td><?= e(t('payment.method_' . $o['payment_method'])) ?><br><?= status_badge($o['payment_status']) ?></td>
        <td><?= e(money($o['total'])) ?></td>
        <td><?= status_badge($o['status']) ?></td>
        <td><?= e(date('Y-m-d H:i', strtotime($o['created_at']))) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="8"><?= e(t('orders.empty')) ?></td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?= pagination($total, $per, $page) ?>
<?php admin_end();
