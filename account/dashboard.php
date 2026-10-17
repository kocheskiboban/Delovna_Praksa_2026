<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
booking_cleanup();

$u = current_user();
$uid = (int) $u['id'];
$upcomingTickets = get_tickets(['user_id' => $uid, 'when' => 'upcoming', 'active' => 1]);
$orders = get_user_orders($uid);
$reservations = array_values(array_filter($orders, static fn($o) => in_array($o['status'], ['reserved', 'pending'], true)));
$favCount = (int) db_value('SELECT COUNT(*) FROM favorites WHERE user_id = ?', [$uid]);
$subscribed = (bool) db_value("SELECT 1 FROM newsletter_subscribers WHERE email = ? AND status = 'active'", [$u['email']]);
[$recommended] = get_events(['when' => 'upcoming', 'featured' => 1, 'limit' => 3]);

account_start('dashboard', t('account.hello', ['name' => $u['first_name']]), [[t('account.dashboard'), null]]);
?>
<div class="dash-cards">
  <a class="dash-card" href="<?= e(url('account/tickets.php')) ?>"><?= icon('ticket') ?><strong><?= count($upcomingTickets) ?></strong><span><?= e(t('account.upcoming_tickets')) ?></span></a>
  <a class="dash-card" href="<?= e(url('account/orders.php')) ?>"><?= icon('list') ?><strong><?= count($orders) ?></strong><span><?= e(t('account.orders')) ?></span></a>
  <a class="dash-card" href="<?= e(url('account/orders.php', ['status' => 'open'])) ?>"><?= icon('clock') ?><strong><?= count($reservations) ?></strong><span><?= e(t('account.reservations')) ?></span></a>
  <a class="dash-card" href="<?= e(url('account/favorites.php')) ?>"><?= icon('heart') ?><strong><?= $favCount ?></strong><span><?= e(t('nav.favorites')) ?></span></a>
</div>

<?php if ($reservations): ?>
<div class="panel">
  <div class="panel__head"><h2><?= e(t('account.reservations')) ?></h2></div>
  <?php foreach ($reservations as $o): ?>
    <?= alert('warning', '<strong>' . e($o['event_title']) . '</strong> — ' . e(t('account.reservation_note_' . $o['payment_method'], ['date' => fmt_datetime($o['expires_at']), 'total' => money($o['total']), 'order' => $o['order_number']])) . ' <a href="' . e(url('account/orders.php')) . '#order-' . (int) $o['id'] . '">' . e(t('common.details')) . '</a>') ?>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="panel">
  <div class="panel__head"><h2><?= e(t('account.next_events')) ?></h2><a class="link-arrow" href="<?= e(url('account/tickets.php')) ?>"><?= e(t('nav.my_tickets')) ?><?= icon('arrow-right') ?></a></div>
  <?php if ($upcomingTickets): ?>
    <div class="ticket-list"><?php foreach (array_slice($upcomingTickets, 0, 4) as $t): ?><?= ticket_card($t) ?><?php endforeach; ?></div>
  <?php else: ?>
    <?= empty_state('ticket', t('tickets.empty_title'), t('tickets.empty_text'), url('programme.php', ['when' => 'upcoming']), t('nav.buy_tickets')) ?>
  <?php endif; ?>
</div>

<div class="grid grid--2" style="gap:var(--s-5)">
  <div class="panel">
    <h2><?= e(t('account.profile')) ?></h2>
    <dl class="profile__facts" style="margin:0 0 var(--s-4)">
      <dt><?= e(t('form.name')) ?></dt><dd><?= e($u['first_name'] . ' ' . $u['last_name']) ?></dd>
      <dt><?= e(t('form.email')) ?></dt><dd><?= e($u['email']) ?></dd>
      <dt><?= e(t('form.phone')) ?></dt><dd><?= e($u['phone'] ?: '—') ?></dd>
      <dt><?= e(t('account.language')) ?></dt><dd><?= e(LANGUAGES[$u['preferred_lang']]['name'] ?? $u['preferred_lang']) ?></dd>
      <dt><?= e(t('account.member_since')) ?></dt><dd><?= e(fmt_date($u['created_at'])) ?></dd>
    </dl>
    <a class="btn btn--ghost btn--sm" href="<?= e(url('account/settings.php')) ?>"><?= icon('edit') ?><?= e(t('account.edit_profile')) ?></a>
  </div>
  <div class="panel">
    <h2><?= e(t('account.newsletter')) ?></h2>
    <p><?= $subscribed ? status_badge('active') . ' ' . e(t('account.newsletter_on')) : status_badge('unsubscribed') . ' ' . e(t('account.newsletter_off')) ?></p>
    <a class="btn btn--ghost btn--sm" href="<?= e(url('account/settings.php')) ?>#newsletter"><?= icon('mail') ?><?= e(t('account.newsletter_manage')) ?></a>
  </div>
</div>

<?php if ($recommended): ?>
<div class="panel">
  <div class="panel__head"><h2><?= e(t('account.recommended')) ?></h2></div>
  <div class="grid grid--3"><?php foreach ($recommended as $ev): ?><?= event_card($ev) ?><?php endforeach; ?></div>
</div>
<?php endif; ?>
<?php account_end();
