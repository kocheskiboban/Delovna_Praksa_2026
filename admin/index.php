<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require APP_ROOT . '/includes/admin.php';
require_admin();
booking_cleanup();

$s = db_one("SELECT
    (SELECT COUNT(*) FROM users) AS users,
    (SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 30 DAY) AS users_new,
    (SELECT COUNT(*) FROM events WHERE status = 'published' AND starts_at >= NOW()) AS upcoming,
    (SELECT COUNT(*) FROM events) AS events,
    (SELECT COUNT(*) FROM tickets WHERE status IN ('valid','used')) AS sold,
    (SELECT COUNT(*) FROM orders WHERE status IN ('reserved','pending')) AS reservations,
    (SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'paid') AS revenue,
    (SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'paid' AND created_at >= NOW() - INTERVAL 30 DAY) AS revenue_30,
    (SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active') AS subscribers,
    (SELECT COUNT(*) FROM news WHERE status = 'published') AS news,
    (SELECT COUNT(*) FROM contact_messages WHERE status = 'new') AS messages,
    (SELECT COUNT(*) FROM orders WHERE status = 'failed') AS failed,
    (SELECT COUNT(*) FROM page_views WHERE viewed_at >= NOW() - INTERVAL 14 DAY) AS views");
[$upcoming] = get_events(['when' => 'upcoming', 'limit' => 8]);
[$tSel, $tJoin] = tr_sql('event_translations', 'event_id', 'e', ['title' => 'event_title'], 'et');
$recent = db_all("SELECT o.*, $tSel FROM orders o JOIN events e ON e.id = o.event_id $tJoin ORDER BY o.created_at DESC LIMIT 8");
$messages = db_all('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 4');
$views = db_all("SELECT DATE(viewed_at) AS d, COUNT(*) AS n FROM page_views WHERE viewed_at >= CURDATE() - INTERVAL 13 DAY GROUP BY DATE(viewed_at) ORDER BY d");
$byMethod = db_all("SELECT payment_method, COUNT(*) AS n, SUM(total) AS total FROM orders WHERE status = 'paid' GROUP BY payment_method ORDER BY n DESC");
$maxViews = max(1, ...array_map('intval', array_column($views, 'n') ?: [1]));

admin_start('dashboard', t('admin.dashboard'));
?>
<div class="dash-cards">
  <a class="dash-card" href="<?= e(url('admin/users.php')) ?>"><?= icon('users') ?><strong><?= (int) $s['users'] ?></strong><span><?= e(t('admin.stat_users')) ?> (+<?= (int) $s['users_new'] ?> / 30d)</span></a>
  <a class="dash-card" href="<?= e(url('admin/manage.php', ['e' => 'events', 'when' => 'upcoming'])) ?>"><?= icon('calendar') ?><strong><?= (int) $s['upcoming'] ?></strong><span><?= e(t('admin.stat_upcoming')) ?></span></a>
  <a class="dash-card" href="<?= e(url('admin/manage.php', ['e' => 'events'])) ?>"><?= icon('archive') ?><strong><?= (int) $s['events'] ?></strong><span><?= e(t('admin.stat_events')) ?></span></a>
  <a class="dash-card" href="<?= e(url('admin/orders.php', ['status' => 'paid'])) ?>"><?= icon('ticket') ?><strong><?= number_format((int) $s['sold'], 0, ',', '.') ?></strong><span><?= e(t('admin.stat_sold')) ?></span></a>
  <a class="dash-card" href="<?= e(url('admin/orders.php', ['status' => 'open'])) ?>"><?= icon('clock') ?><strong><?= (int) $s['reservations'] ?></strong><span><?= e(t('admin.stat_reservations')) ?></span></a>
  <a class="dash-card" href="<?= e(url('admin/orders.php')) ?>"><?= icon('cash') ?><strong><?= e(money($s['revenue'])) ?></strong><span><?= e(t('admin.stat_revenue')) ?> (<?= e(money($s['revenue_30'])) ?> / 30d)</span></a>
  <a class="dash-card" href="<?= e(url('admin/newsletter.php')) ?>"><?= icon('mail') ?><strong><?= (int) $s['subscribers'] ?></strong><span><?= e(t('admin.stat_subscribers')) ?></span></a>
  <a class="dash-card" href="<?= e(url('admin/manage.php', ['e' => 'news'])) ?>"><?= icon('news') ?><strong><?= (int) $s['news'] ?></strong><span><?= e(t('admin.stat_news')) ?></span></a>
  <a class="dash-card" href="<?= e(url('admin/messages.php')) ?>"><?= icon('mail') ?><strong><?= (int) $s['messages'] ?></strong><span><?= e(t('admin.stat_messages')) ?></span></a>
  <div class="dash-card"><?= icon('eye') ?><strong><?= (int) $s['views'] ?></strong><span><?= e(t('admin.stat_views')) ?></span></div>
</div>

<div class="admin-grid">
  <section class="panel">
    <div class="panel__head"><h2><?= e(t('admin.sales_by_event')) ?></h2><a class="link-arrow" href="<?= e(url('admin/manage.php', ['e' => 'events', 'when' => 'upcoming'])) ?>"><?= e(t('common.view_all')) ?><?= icon('arrow-right') ?></a></div>
    <ul class="bar-list">
      <?php foreach ($upcoming as $ev): $pct = $ev['capacity_total'] ? round(100 * $ev['sold_count'] / $ev['capacity_total']) : 0; ?>
        <li><a href="<?= e(url('admin/seats.php', ['id' => $ev['id']])) ?>"><?= e($ev['title']) ?></a>
          <span class="bar" role="img" aria-label="<?= $pct ?>%"><span style="width:<?= $pct ?>%"></span></span>
          <small><?= (int) $ev['sold_count'] ?> / <?= (int) $ev['capacity_total'] ?> (<?= $pct ?>%)</small></li>
      <?php endforeach; ?>
    </ul>
  </section>
  <section class="panel">
    <h2><?= e(t('admin.views_14')) ?></h2>
    <div class="col-chart" role="img" aria-label="<?= e(t('admin.views_14')) ?>">
      <?php foreach ($views as $v): ?><span style="height:<?= round(100 * $v['n'] / $maxViews) ?>%" title="<?= e($v['d']) ?>: <?= (int) $v['n'] ?>"><em><?= (int) $v['n'] ?></em></span><?php endforeach; ?>
    </div>
    <p class="muted" style="font-size:var(--step--1)"><?= e(t('admin.views_note')) ?></p>
    <h2 style="margin-top:var(--s-5)"><?= e(t('admin.by_method')) ?></h2>
    <table class="data-table" style="min-width:0"><tbody>
      <?php foreach ($byMethod as $m): ?><tr><td><?= e(t('payment.method_' . $m['payment_method'])) ?></td><td><?= (int) $m['n'] ?></td><td><?= e(money($m['total'])) ?></td></tr><?php endforeach; ?>
      <tr><td><?= e(t('status.failed')) ?></td><td><?= (int) $s['failed'] ?></td><td>—</td></tr>
    </tbody></table>
  </section>
</div>

<section class="panel">
  <div class="panel__head"><h2><?= e(t('admin.recent_orders')) ?></h2><a class="link-arrow" href="<?= e(url('admin/orders.php')) ?>"><?= e(t('common.view_all')) ?><?= icon('arrow-right') ?></a></div>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th><?= e(t('ticket.order')) ?></th><th><?= e(t('admin.col_customer')) ?></th><th><?= e(t('event.title')) ?></th><th><?= e(t('orders.payment')) ?></th><th><?= e(t('orders.total')) ?></th><th><?= e(t('ticket.status')) ?></th><th><?= e(t('admin.col_date')) ?></th></tr></thead>
    <tbody><?php foreach ($recent as $o): ?>
      <tr><td><a href="<?= e(url('admin/order.php', ['id' => $o['id']])) ?>"><code><?= e($o['order_number']) ?></code></a></td><td><?= e($o['customer_name']) ?></td><td><?= e(str_limit($o['event_title'], 40)) ?></td>
        <td><?= e(t('payment.method_' . $o['payment_method'])) ?></td><td><?= e(money($o['total'])) ?></td><td><?= status_badge($o['status']) ?></td><td><?= e(date('Y-m-d H:i', strtotime($o['created_at']))) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
</section>

<section class="panel">
  <div class="panel__head"><h2><?= e(t('admin.messages')) ?></h2><a class="link-arrow" href="<?= e(url('admin/messages.php')) ?>"><?= e(t('common.view_all')) ?><?= icon('arrow-right') ?></a></div>
  <?php foreach ($messages as $m): ?>
    <p><?= status_badge($m['status']) ?> <strong><?= e($m['subject']) ?></strong> — <?= e($m['name']) ?> <span class="muted">(<?= e(date('Y-m-d H:i', strtotime($m['created_at']))) ?>)</span></p>
  <?php endforeach; ?>
</section>
<?php admin_end();
