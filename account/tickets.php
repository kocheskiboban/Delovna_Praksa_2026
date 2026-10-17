<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
booking_cleanup();

$tab = in_array($_GET['tab'] ?? '', ['upcoming', 'past', 'cancelled'], true) ? $_GET['tab'] : 'upcoming';
$all = get_tickets(['user_id' => user_id(), 'when' => $tab === 'past' ? 'past' : ($tab === 'upcoming' ? 'upcoming' : '')]);
$tickets = array_values(array_filter($all, static fn($t) => $tab === 'cancelled' ? $t['status'] === 'cancelled' : $t['status'] !== 'cancelled'));

account_start('tickets', t('nav.my_tickets'));
?>
<nav class="tabs" aria-label="<?= e(t('tickets.filter')) ?>">
  <?php foreach (['upcoming' => 'tickets.tab_upcoming', 'past' => 'tickets.tab_past', 'cancelled' => 'tickets.tab_cancelled'] as $k => $label): ?>
    <a href="<?= e(url('account/tickets.php', ['tab' => $k === 'upcoming' ? null : $k])) ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= e(t($label)) ?></a>
  <?php endforeach; ?>
</nav>
<?php if (!$tickets): ?>
  <?= empty_state('ticket', t($tab === 'upcoming' ? 'tickets.empty_title' : 'tickets.empty_other'), $tab === 'upcoming' ? t('tickets.empty_text') : '', url('programme.php', ['when' => 'upcoming']), t('nav.buy_tickets')) ?>
<?php else: ?>
  <p class="muted"><?= e(t('tickets.count', ['n' => count($tickets)])) ?></p>
  <div class="ticket-list"><?php foreach ($tickets as $t): ?><?= ticket_card($t) ?><?php endforeach; ?></div>
<?php endif; ?>
<?php account_end();
