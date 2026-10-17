<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
booking_cleanup();

if (is_post()) {
    csrf_check();
    $id = input_int('order_id');
    $o = db_one("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status IN ('reserved','pending')", [$id, user_id()]);
    if ($o) {
        set_order_status($id, 'cancelled');
        flash('success', t('orders.cancelled', ['order' => $o['order_number']]));
    } else {
        flash('error', t('orders.cancel_error'));
    }
    redirect(url('account/orders.php'));
}

$filter = ($_GET['status'] ?? '') === 'open' ? 'open' : '';
$orders = get_user_orders(user_id());
if ($filter === 'open') {
    $orders = array_values(array_filter($orders, static fn($o) => in_array($o['status'], ['reserved', 'pending'], true)));
}

account_start('orders', t('account.orders'));
?>
<nav class="tabs">
  <a href="<?= e(url('account/orders.php')) ?>"<?= $filter === '' ? ' aria-current="page"' : '' ?>><?= e(t('orders.all')) ?></a>
  <a href="<?= e(url('account/orders.php', ['status' => 'open'])) ?>"<?= $filter === 'open' ? ' aria-current="page"' : '' ?>><?= e(t('account.reservations')) ?></a>
</nav>
<?php if (!$orders): ?>
  <?= empty_state('list', t('orders.empty'), t('orders.empty_text'), url('programme.php', ['when' => 'upcoming']), t('nav.programme')) ?>
<?php else: ?>
  <?php foreach ($orders as $o): $tickets = get_tickets(['order_id' => (int) $o['id']]); ?>
    <details class="panel" id="order-<?= (int) $o['id'] ?>" <?= in_array($o['status'], ['reserved', 'pending'], true) ? 'open' : '' ?>>
      <summary class="panel__head" style="cursor:pointer;list-style:none;margin:0">
        <div>
          <h2 style="font-size:var(--step-1);margin:0"><?= e($o['event_title']) ?></h2>
          <p class="muted" style="margin:0"><?= e($o['order_number']) ?> — <?= e(fmt_datetime($o['created_at'])) ?></p>
        </div>
        <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap"><?= status_badge($o['status']) ?><strong><?= e(money($o['total'])) ?></strong><?= icon('chevron-down') ?></div>
      </summary>
      <div style="margin-top:var(--s-4)">
        <dl class="profile__facts" style="margin-top:0">
          <dt><?= e(t('orders.event_date')) ?></dt><dd><?= e(fmt_datetime($o['starts_at'])) ?></dd>
          <dt><?= e(t('orders.payment')) ?></dt><dd><?= e(t('payment.method_' . $o['payment_method'])) ?><?= $o['card_last4'] ? ' •••• ' . e($o['card_last4']) : '' ?> — <?= status_badge($o['payment_status']) ?></dd>
          <dt><?= e(t('orders.subtotal')) ?></dt><dd><?= e(money($o['subtotal'])) ?></dd>
          <dt><?= e(t('orders.fee')) ?></dt><dd><?= e(money($o['service_fee'])) ?></dd>
          <dt><?= e(t('orders.total')) ?></dt><dd><strong><?= e(money($o['total'])) ?></strong></dd>
          <?php if ($o['expires_at'] && in_array($o['status'], ['reserved', 'pending'], true)): ?><dt><?= e(t('orders.deadline')) ?></dt><dd><?= e(fmt_datetime($o['expires_at'])) ?></dd><?php endif; ?>
        </dl>
        <?php if ($o['status'] === 'pending'): ?><?= alert('info', e(t('payment.transfer_info', ['iban' => config('festival.iban'), 'ref' => $o['order_number'], 'total' => money($o['total'])]))) ?><?php endif; ?>
        <?php if ($o['status'] === 'failed'): ?><?= alert('error', e(t('orders.failed_note'))) ?><?php endif; ?>
        <?php if ($tickets): ?>
        <div class="table-wrap"><table class="data-table">
          <thead><tr><th><?= e(t('ticket.code')) ?></th><th><?= e(t('ticket.seat')) ?></th><th><?= e(t('ticket.category')) ?></th><th><?= e(t('ticket.price')) ?></th><th><?= e(t('ticket.status')) ?></th><th></th></tr></thead>
          <tbody><?php foreach ($tickets as $t): ?>
            <tr><td><code><?= e($t['ticket_code']) ?></code></td><td><?= e(ticket_seat_text($t)) ?></td><td><?= e($t['type_name']) ?></td><td><?= e(money($t['unit_price'])) ?></td><td><?= status_badge($t['status']) ?></td>
            <td><a href="<?= e(url('account/ticket.php', ['code' => $t['ticket_code']])) ?>"><?= e(t('tickets.open')) ?></a></td></tr>
          <?php endforeach; ?></tbody>
        </table></div>
        <?php endif; ?>
        <?php if (in_array($o['status'], ['reserved', 'pending'], true)): ?>
          <form method="post" action="<?= e(url('account/orders.php')) ?>" data-confirm="<?= e(t('orders.cancel_confirm')) ?>" style="margin-top:var(--s-4)">
            <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
            <button class="btn btn--ghost btn--sm" type="submit"><?= icon('close') ?><?= e(t('orders.cancel')) ?></button>
          </form>
        <?php endif; ?>
      </div>
    </details>
  <?php endforeach; ?>
<?php endif; ?>
<?php account_end();
