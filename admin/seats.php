<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require APP_ROOT . '/includes/admin.php';
require_admin();
booking_cleanup();

$event = get_event_by_id(input_int('id'), true);
if (!$event) {
    not_found();
}
$eid = (int) $event['id'];
if (is_post()) {
    csrf_check();
    $sales = in_array($_POST['sales_status'] ?? '', ['open', 'closed'], true) ? $_POST['sales_status'] : $event['sales_status'];
    db_exec('UPDATE events SET sales_status = ? WHERE id = ?', [$sales, $eid]);
    if ($event['mode'] === 'general') {
        $cap = max(1, input_int('capacity', (int) $event['capacity_total']));
        $sold = (int) $event['sold_count'];
        if ($cap < $sold) {
            flash('error', t('admin.err_capacity_sold', ['n' => $sold]));
            redirect(url('admin/seats.php', ['id' => $eid]));
        }
        db_exec('UPDATE events SET capacity = ? WHERE id = ?', [$cap, $eid]);
    } else {
        $blocked = array_map('intval', (array) ($_POST['blocked'] ?? []));
        $sold = array_map('intval', array_column(db_all("SELECT seat_id FROM tickets WHERE event_id = ? AND status <> 'cancelled' AND seat_id IS NOT NULL", [$eid]), 'seat_id'));
        $blocked = array_values(array_diff($blocked, $sold));
        db_transaction(function () use ($eid, $blocked, $event) {
            db_exec('DELETE FROM event_seat_blocks WHERE event_id = ?', [$eid]);
            if ($blocked) {
                $valid = array_map('intval', array_column(db_all('SELECT id FROM venue_seats WHERE venue_id = ? AND id IN (' . db_placeholders($blocked) . ')',
                    array_merge([(int) $event['venue_id']], $blocked)), 'id'));
                foreach ($valid as $sid) {
                    db_exec('INSERT INTO event_seat_blocks (event_id, seat_id, reason) VALUES (?, ?, ?)', [$eid, $sid, 'Admin']);
                }
            }
        });
    }
    flash('success', t('admin.updated'));
    redirect(url('admin/seats.php', ['id' => $eid]));
}
$reserved = (int) db_value("SELECT COUNT(*) FROM tickets WHERE event_id = ? AND status = 'reserved'", [$eid]);
$held = (int) db_value('SELECT COUNT(*) FROM reservations WHERE event_id = ? AND expires_at >= NOW()', [$eid]);
$revenue = (float) db_value("SELECT COALESCE(SUM(total),0) FROM orders WHERE event_id = ? AND status = 'paid'", [$eid]);
$seats = $event['mode'] === 'seated' ? event_seat_map($event, 0) : [];
$blockedIds = array_map('intval', array_column(db_all('SELECT seat_id FROM event_seat_blocks WHERE event_id = ?', [$eid]), 'seat_id'));
$inactive = $seats ? array_map('intval', array_column(db_all('SELECT id FROM venue_seats WHERE venue_id = ? AND is_active = 0', [(int) $event['venue_id']]), 'id')) : [];
foreach ($seats as &$s) {
    $s['blocked'] = in_array($s['id'], $blockedIds, true);
    $s['inactive'] = in_array($s['id'], $inactive, true);
}
unset($s);

admin_start('events', t('admin.availability') . ': ' . $event['title']);
?>
<p><a class="link-arrow" href="<?= e(url('admin/manage.php', ['e' => 'events'])) ?>"><?= icon('arrow-left') ?><?= e(t('admin.back_list')) ?></a></p>
<div class="dash-cards">
  <div class="dash-card"><?= icon('users') ?><strong><?= (int) $event['capacity_total'] ?></strong><span><?= e(t('admin.capacity')) ?></span></div>
  <div class="dash-card"><?= icon('ticket') ?><strong><?= (int) $event['sold_count'] ?></strong><span><?= e(t('admin.tickets_issued')) ?></span></div>
  <div class="dash-card"><?= icon('clock') ?><strong><?= $reserved ?> / <?= $held ?></strong><span><?= e(t('admin.reserved_held')) ?></span></div>
  <div class="dash-card"><?= icon('check-circle') ?><strong><?= (int) $event['available'] ?></strong><span><?= e(t('event.availability')) ?></span></div>
  <div class="dash-card"><?= icon('cash') ?><strong><?= e(money($revenue)) ?></strong><span><?= e(t('admin.revenue')) ?></span></div>
</div>
<form method="post" class="panel" action="<?= e(url('admin/seats.php', ['id' => $eid])) ?>">
  <?= csrf_field() ?>
  <div class="form-grid">
    <?= field(['name' => 'sales_status', 'label' => t('admin.f_sales'), 'type' => 'select', 'value' => $event['sales_status'], 'options' => ['open' => t('status.open'), 'closed' => t('status.closed')]]) ?>
    <?php if ($event['mode'] === 'general'): ?>
      <?= field(['name' => 'capacity', 'label' => t('admin.f_capacity'), 'type' => 'number', 'value' => (string) $event['capacity_total'], 'attrs' => 'min="1"']) ?>
    <?php endif; ?>
  </div>
  <?php if ($seats): ?>
    <h2><?= e(t('admin.block_seats')) ?></h2>
    <p class="muted"><?= e(t('admin.block_help')) ?></p>
    <div class="seatmap-wrap"><div class="seatmap-scroll"><div data-admin-seatmap data-layout="<?= e($event['layout_type']) ?>" data-stage="<?= e(t('seat.stage')) ?>"></div></div>
      <ul class="seat-legend">
        <li><i class="lg-available"></i><?= e(t('seat.available')) ?></li><li><i class="lg-occupied"></i><?= e(t('admin.sold')) ?></li>
        <li><i class="lg-unavailable"></i><?= e(t('admin.blocked')) ?></li><li><i class="lg-vip"></i>VIP</li>
      </ul>
    </div>
    <div data-blocked-inputs><?php foreach ($blockedIds as $b): ?><input type="hidden" name="blocked[]" value="<?= $b ?>"><?php endforeach; ?></div>
    <script type="application/json" id="admin-seat-data"><?= json_encode(array_map(static fn($s) => ['id' => $s['id'], 'x' => $s['x'], 'y' => $s['y'], 't' => $s['t'],
        'st' => $s['inactive'] ? 'inactive' : ($s['blocked'] ? 'blocked' : ($s['st'] === 'occupied' ? 'sold' : 'available')), 'l' => seat_label($s)], $seats), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
  <?php endif; ?>
  <div class="form-actions"><button class="btn btn--primary" type="submit"><?= icon('check') ?><?= e(t('admin.save')) ?></button>
    <a class="btn btn--ghost" href="<?= e(url('event.php', ['slug' => $event['slug']])) ?>"><?= icon('eye') ?><?= e(t('admin.view')) ?></a>
    <a class="btn btn--ghost" href="<?= e(url('admin/orders.php', ['event' => $eid])) ?>"><?= icon('list') ?><?= e(t('admin.orders')) ?></a></div>
</form>
<?php admin_end(['scripts' => ['assets/js/admin.js']]);
