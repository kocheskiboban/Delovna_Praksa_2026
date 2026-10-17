<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require APP_ROOT . '/includes/admin.php';
require_admin();

$id = input_int('id');
if (is_post()) {
    csrf_check();
    $status = input('status', '', 'post');
    try {
        set_order_status($id, $status);
        flash('success', t('admin.updated'));
    } catch (Throwable $e) {
        flash('error', t($e->getMessage() === 'seat_conflict' ? 'admin.err_seat_conflict' : 'admin.err_generic'));
    }
    redirect(url('admin/order.php', ['id' => $id]));
}
$o = get_order($id);
if (!$o) {
    not_found();
}
$transitions = [
    'pending'   => ['paid', 'cancelled'],
    'reserved'  => ['paid', 'cancelled'],
    'paid'      => ['refunded', 'cancelled'],
    'cancelled' => ['reserved'],
    'refunded'  => [],
    'failed'    => [],
];
admin_start('orders', t('ticket.order') . ' ' . $o['order_number']);
?>
<p><a class="link-arrow" href="<?= e(url('admin/orders.php')) ?>"><?= icon('arrow-left') ?><?= e(t('admin.back_list')) ?></a></p>
<div class="admin-grid">
  <section class="panel">
    <h2><?= e(t('admin.order_details')) ?></h2>
    <dl class="profile__facts">
      <dt><?= e(t('ticket.order')) ?></dt><dd><code><?= e($o['order_number']) ?></code></dd>
      <dt><?= e(t('ticket.status')) ?></dt><dd><?= status_badge($o['status']) ?></dd>
      <dt><?= e(t('admin.col_customer')) ?></dt><dd><?= e($o['customer_name']) ?> — <a href="mailto:<?= e($o['customer_email']) ?>"><?= e($o['customer_email']) ?></a></dd>
      <dt><?= e(t('admin.col_event')) ?></dt><dd><?= $o['event'] ? '<a href="' . e(url('event.php', ['slug' => $o['event']['slug']])) . '">' . e($o['event']['title']) . '</a> (' . e(fmt_datetime($o['event']['starts_at'])) . ')' : '—' ?></dd>
      <dt><?= e(t('orders.payment')) ?></dt><dd><?= e(t('payment.method_' . $o['payment_method'])) ?><?= $o['card_last4'] ? ' •••• ' . e($o['card_last4']) : '' ?> — <?= status_badge($o['payment_status']) ?></dd>
      <dt><?= e(t('orders.subtotal')) ?></dt><dd><?= e(money($o['subtotal'])) ?></dd>
      <dt><?= e(t('orders.fee')) ?></dt><dd><?= e(money($o['service_fee'])) ?></dd>
      <dt><?= e(t('orders.total')) ?></dt><dd><strong><?= e(money($o['total'])) ?></strong></dd>
      <dt><?= e(t('admin.col_date')) ?></dt><dd><?= e($o['created_at']) ?></dd>
      <dt><?= e(t('admin.paid_at')) ?></dt><dd><?= e($o['paid_at'] ?? '—') ?></dd>
      <dt><?= e(t('orders.deadline')) ?></dt><dd><?= e($o['expires_at'] ?? '—') ?></dd>
    </dl>
  </section>
  <section class="panel">
    <h2><?= e(t('admin.change_status')) ?></h2>
    <?php if ($transitions[$o['status']] ?? []): ?>
      <p class="muted"><?= e(t('admin.status_help')) ?></p>
      <div class="links-row">
        <?php foreach ($transitions[$o['status']] as $to): ?>
          <form method="post" action="<?= e(url('admin/order.php', ['id' => $id])) ?>" data-confirm="<?= e(t('admin.confirm_status', ['s' => t('status.' . $to)])) ?>">
            <?= csrf_field() ?><input type="hidden" name="status" value="<?= e($to) ?>">
            <button class="btn <?= $to === 'paid' ? 'btn--primary' : 'btn--ghost' ?>" type="submit"><?= e(t('admin.mark_' . $to)) ?></button>
          </form>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="muted"><?= e(t('admin.no_transitions')) ?></p>
    <?php endif; ?>
  </section>
</div>
<section class="panel">
  <h2><?= e(t('admin.col_tickets')) ?> (<?= count($o['tickets']) ?>)</h2>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th><?= e(t('ticket.code')) ?></th><th><?= e(t('ticket.seat')) ?></th><th><?= e(t('ticket.category')) ?></th><th><?= e(t('booking.zone')) ?></th><th><?= e(t('ticket.price')) ?></th><th><?= e(t('ticket.status')) ?></th><th></th></tr></thead>
    <tbody><?php foreach ($o['tickets'] as $t): ?>
      <tr><td><code><?= e($t['ticket_code']) ?></code></td><td><?= e(ticket_seat_text($t)) ?></td><td><?= e($t['type_name']) ?></td><td><?= e($t['zone']) ?></td><td><?= e(money($t['unit_price'])) ?></td><td><?= status_badge($t['status']) ?></td>
        <td><a href="<?= e(url('account/ticket.php', ['code' => $t['ticket_code']])) ?>"><?= e(t('tickets.open')) ?></a> · <a href="<?= e(url('verify.php', ['code' => $t['ticket_code']])) ?>"><?= e(t('nav.verify')) ?></a></td></tr>
    <?php endforeach; ?>
    <?php if (!$o['tickets']): ?><tr><td colspan="7"><?= e(t('admin.no_tickets')) ?></td></tr><?php endif; ?></tbody>
  </table></div>
</section>
<?php admin_end();
