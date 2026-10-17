<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();

$code = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', input('code', '', 'get')) ?? '');
$rows = $code !== '' ? get_tickets(['code' => $code]) : [];
$t = $rows[0] ?? null;
if (!$t || ((int) $t['user_id'] !== user_id() && !is_admin())) {
    account_start('tickets', t('ticket.invalid_title'));
    echo empty_state('alert', t('ticket.invalid_title'), t('ticket.invalid_text'), url('account/tickets.php'), t('nav.my_tickets'));
    account_end();
    exit;
}
$verifyUrl = url('verify.php', ['code' => $t['ticket_code']], true);
$stamp = ['cancelled' => t('status.cancelled'), 'used' => t('status.used')][$t['status']] ?? '';

account_start('tickets', t('ticket.title'), [[t('nav.my_tickets'), url('account/tickets.php')], [$t['ticket_code'], null]]);
?>
<?php if ($t['status'] === 'reserved'): ?><?= alert('warning', e(t('ticket.reserved_note'))) ?><?php endif; ?>
<article class="ticket<?= $stamp ? ' ticket--cancelled' : '' ?>" aria-label="<?= e(t('ticket.title')) ?>">
  <div class="ticket__main" data-stamp="<?= e($stamp) ?>">
    <div class="ticket__brand"><?= festival_logo() ?><div><strong><?= e(t('site.name')) ?></strong><small><?= e(t('ticket.brand_sub')) ?></small></div></div>
    <p class="eyebrow"><?= e($t['type_name']) ?><?= $t['zone'] === 'vip' ? ' — VIP' : '' ?></p>
    <h1><?= e($t['event_title']) ?></h1>
    <dl class="ticket__grid">
      <div><dt><?= e(t('event.date')) ?></dt><dd><?= e(fmt_date($t['starts_at'], 'weekday')) ?></dd></div>
      <div><dt><?= e(t('event.time')) ?></dt><dd><?= e(fmt_time($t['starts_at'])) ?></dd></div>
      <div><dt><?= e(t('event.venue')) ?></dt><dd><?= e($t['venue_name']) ?></dd></div>
      <div><dt><?= e(t('ticket.seat')) ?></dt><dd><?= e(ticket_seat_text($t)) ?></dd></div>
      <div><dt><?= e(t('ticket.category')) ?></dt><dd><?= e($t['type_name']) ?></dd></div>
      <div><dt><?= e(t('ticket.price')) ?></dt><dd><?= e(money($t['unit_price'])) ?></dd></div>
      <div><dt><?= e(t('ticket.order')) ?></dt><dd><?= e($t['order_number']) ?></dd></div>
      <div><dt><?= e(t('ticket.holder')) ?></dt><dd><?= e($t['customer_name']) ?></dd></div>
      <div><dt><?= e(t('ticket.status')) ?></dt><dd><?= status_badge($t['status']) ?></dd></div>
    </dl>
  </div>
  <div class="ticket__stub">
    <?= qr_svg($t['ticket_code']) ?>
    <code><?= e($t['ticket_code']) ?></code>
    <small><?= e(t('ticket.scan')) ?></small>
  </div>
</article>
<div class="ticket-actions">
  <button type="button" class="btn btn--primary" data-print><?= icon('print') ?><?= e(t('ticket.print')) ?></button>
  <a class="btn btn--ghost" href="<?= e($verifyUrl) ?>"><?= icon('qr') ?><?= e(t('ticket.verify')) ?></a>
  <a class="btn btn--ghost" href="<?= e(url('event.php', ['slug' => $t['event_slug']])) ?>"><?= icon('calendar') ?><?= e(t('ticket.event_page')) ?></a>
  <a class="btn btn--ghost" href="<?= e(url('account/tickets.php')) ?>"><?= icon('arrow-left') ?><?= e(t('nav.my_tickets')) ?></a>
</div>
<p class="muted no-print" style="text-align:center;font-size:var(--step--1)"><?= e(t('ticket.demo_note')) ?></p>
<?php account_end();
