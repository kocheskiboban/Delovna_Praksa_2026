<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$order = get_order(input_int('order'), is_admin() ? null : user_id());
if (!$order || !$order['event']) {
    not_found();
}
$ev = $order['event'];
$kind = $order['status'] === 'paid' ? 'success' : 'pending';

render_header(['title' => t('booking.step_confirmation'), 'noindex' => true, 'body_class' => 'page-booking']);
echo page_hero(t('booking.eyebrow'), $ev['title'], fmt_date($ev['starts_at'], 'weekday') . ', ' . fmt_time($ev['starts_at']) . ' — ' . $ev['venue_name'],
    [[t('nav.account'), url('account/dashboard.php')], [t('booking.step_confirmation'), null]]);
?>
<section class="section section--tight">
  <div class="container">
    <?= booking_steps(5, $ev['mode'] === 'seated') ?>
    <div class="booking-layout">
      <div>
        <div class="panel result result--<?= $kind ?>">
          <span class="result__icon"><?= icon($kind === 'success' ? 'check-circle' : 'clock') ?></span>
          <h2><?= e(t('confirm.title_' . $order['status'])) ?></h2>
          <p class="lead" style="margin-inline:auto"><?= e(t('confirm.text_' . $order['status'], ['order' => $order['order_number'], 'date' => $order['expires_at'] ? fmt_datetime($order['expires_at']) : '', 'total' => money($order['total'])])) ?></p>
          <?php if ($order['status'] === 'pending'): ?><?= alert('info', e(t('payment.transfer_info', ['iban' => config('festival.iban'), 'ref' => $order['order_number'], 'total' => money($order['total'])]))) ?><?php endif; ?>
          <p><strong><?= e(t('ticket.order')) ?>:</strong> <code><?= e($order['order_number']) ?></code></p>
        </div>
        <div class="panel">
          <div class="panel__head"><h2><?= e(t('confirm.your_tickets', ['n' => count($order['tickets'])])) ?></h2>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('api/ics.php', ['event' => $ev['slug']])) ?>"><?= icon('calendar') ?><?= e(t('confirm.add_calendar')) ?></a></div>
          <div class="ticket-list"><?php foreach ($order['tickets'] as $t): ?><?= ticket_card($t) ?><?php endforeach; ?></div>
        </div>
        <div class="form-actions">
          <a class="btn btn--primary" href="<?= e(url('account/tickets.php')) ?>"><?= icon('ticket') ?><?= e(t('nav.my_tickets')) ?></a>
          <a class="btn btn--ghost" href="<?= e(url('programme.php', ['when' => 'upcoming'])) ?>"><?= e(t('confirm.more_events')) ?></a>
        </div>
      </div>
      <aside class="summary">
        <h2 style="font-size:var(--step-1)"><?= e(t('confirm.summary')) ?></h2>
        <dl class="profile__facts" style="margin:0">
          <dt><?= e(t('orders.payment')) ?></dt><dd><?= e(t('payment.method_' . $order['payment_method'])) ?><?= $order['card_last4'] ? ' •••• ' . e($order['card_last4']) : '' ?></dd>
          <dt><?= e(t('ticket.status')) ?></dt><dd><?= status_badge($order['status']) ?></dd>
          <dt><?= e(t('orders.subtotal')) ?></dt><dd><?= e(money($order['subtotal'])) ?></dd>
          <dt><?= e(t('orders.fee')) ?></dt><dd><?= e(money($order['service_fee'])) ?></dd>
          <dt><?= e(t('orders.total')) ?></dt><dd><strong><?= e(money($order['total'])) ?></strong></dd>
        </dl>
      </aside>
    </div>
  </div>
</section>
<?php render_footer();
