<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

$event = booking_event_or_fail();
$b = booking_get((int) $event['id']);
$need = booking_qty_total($b);
if ($event['mode'] !== 'seated') {
    redirect(url('booking/review.php', ['event' => $event['slug']]));
}
if ($need < 1) {
    flash('info', t('booking.choose_first'));
    redirect(url('booking/tickets.php', ['event' => $event['slug']]));
}
$errors = [];
if (is_post()) {
    csrf_check();
    $ids = !empty($_POST['best'])
        ? best_available_seats($event, $need, user_id())
        : array_map('intval', (array) ($_POST['seats'] ?? []));
    $ids = array_values(array_unique(array_filter($ids)));
    if (count($ids) !== $need) {
        $errors[] = t(empty($_POST['best']) ? 'booking.err_seat_count' : 'booking.err_no_block', ['n' => $need]);
    } else {
        try {
            $res = hold_seats($event, user_id(), $ids);
        } catch (RuntimeException $e) {
            $res = ['ok' => false, 'taken' => []];
        }
        if ($res['ok']) {
            redirect(url('booking/review.php', ['event' => $event['slug']]));
        }
        $errors[] = t('booking.err_taken');
    }
}
$seats = event_seat_map($event, user_id());
$mine = array_column(array_filter($seats, static fn($s) => $s['st'] === 'mine'), 'id');
$holds = user_holds((int) $event['id'], user_id());
$maxY = $seats ? max(array_column($seats, 'y')) + 40 : 600;
$sectionNames = [];
foreach (array_unique(array_column($seats, 's')) as $sec) {
    $sectionNames[$sec] = t('seat.section_' . $sec);
}
$payload = [
    'need' => $need, 'layout' => $event['layout_type'], 'height' => $maxY, 'seats' => $seats, 'mine' => array_values($mine),
    'price' => (float) $event['price_standard'], 'vip' => $event['price_vip'] !== null ? (float) $event['price_vip'] : null,
    'sections' => $sectionNames,
    'i18n' => ['stage' => t('seat.stage'), 'row' => t('seat.row'), 'seat' => t('seat.seat'), 'vip' => t('seat.vip'), 'accessible' => t('seat.accessible'),
        'occupied' => t('seat.occupied'), 'unavailable' => t('seat.unavailable'), 'available' => t('seat.available'), 'selected' => t('seat.selected'),
        'limit' => t('booking.err_limit', ['n' => $need]), 'remaining' => t('booking.remaining'), 'label' => t('seat.label')],
];

render_header(['title' => t('booking.step_seats') . ' — ' . $event['title'], 'noindex' => true, 'body_class' => 'page-booking', 'scripts' => ['assets/js/seatmap.js', 'assets/js/booking.js']]);
echo page_hero(t('booking.eyebrow'), $event['title'], fmt_date($event['starts_at'], 'weekday') . ', ' . fmt_time($event['starts_at']) . ' — ' . $event['venue_name'],
    [[t('nav.programme'), url('programme.php')], [$event['title'], url('event.php', ['slug' => $event['slug']])], [t('booking.step_seats'), null]]);
?>
<section class="section section--tight">
  <div class="container">
    <?= booking_steps(2) ?>
    <div class="booking-layout">
      <div>
        <?php foreach ($errors as $err): ?><?= alert('error', e($err)) ?><?php endforeach; ?>
        <form method="post" action="<?= e(url('booking/seats.php', ['event' => $event['slug']])) ?>" data-seat-form>
          <?= csrf_field() ?>
          <div class="seatmap-wrap">
            <div class="seatmap-toolbar">
              <div>
                <h2 style="margin:0;font-size:var(--step-2)"><?= e(t('booking.pick_seats', ['n' => $need])) ?></h2>
                <p class="muted" style="margin:0"><span class="seat-counter" data-seat-counter>0 / <?= $need ?></span> <?= e(t('booking.selected_seats')) ?></p>
              </div>
              <div class="seatmap-zoom" role="group" aria-label="<?= e(t('booking.zoom')) ?>">
                <button type="button" data-zoom="-1" aria-label="<?= e(t('booking.zoom_out')) ?>">−</button>
                <button type="button" data-zoom="1" aria-label="<?= e(t('booking.zoom_in')) ?>">+</button>
              </div>
            </div>
            <div class="seatmap-scroll" data-seatmap-scroll>
              <div data-seatmap aria-describedby="seatmap-help"><noscript><p class="alert alert--info"><?= e(t('booking.noscript')) ?></p></noscript></div>
            </div>
            <p id="seatmap-help" class="muted" style="font-size:var(--step--1);margin:.6rem 0 0"><?= e(t('booking.keyboard_help')) ?></p>
            <ul class="seat-legend">
              <li><i class="lg-available"></i><?= e(t('seat.available')) ?></li>
              <li><i class="lg-selected"></i><?= e(t('seat.selected')) ?></li>
              <li><i class="lg-occupied"></i><?= e(t('seat.occupied')) ?></li>
              <li><i class="lg-vip"></i><?= e(t('seat.vip')) ?><?= $event['price_vip'] ? ' (' . e(money($event['price_vip'])) . ')' : '' ?></li>
              <li><i class="lg-accessible"></i><?= e(t('seat.accessible')) ?></li>
              <li><i class="lg-unavailable"></i><?= e(t('seat.unavailable')) ?></li>
            </ul>
          </div>
          <div class="panel" style="margin-top:var(--s-4)">
            <h3><?= e(t('booking.your_seats')) ?></h3>
            <ul class="selected-seats" data-selected-list aria-live="polite"><?php foreach ($holds as $h): ?><li><?= e(seat_label($h)) ?></li><?php endforeach; ?></ul>
            <div data-seat-inputs><?php foreach ($mine as $id): ?><input type="hidden" name="seats[]" value="<?= (int) $id ?>"><?php endforeach; ?></div>
            <div class="form-actions">
              <a class="btn btn--ghost" href="<?= e(url('booking/tickets.php', ['event' => $event['slug']])) ?>"><?= icon('arrow-left') ?><?= e(t('booking.change_qty')) ?></a>
              <button class="btn btn--ghost" type="submit" name="best" value="1"><?= icon('star') ?><?= e(t('booking.best_available')) ?></button>
              <button class="btn btn--primary btn--lg" type="submit" data-seat-submit <?= count($mine) === $need ? '' : 'disabled' ?>><?= e(t('booking.confirm_seats')) ?><?= icon('arrow-right') ?></button>
            </div>
            <p class="muted" style="font-size:var(--step--1);margin:0"><?= e(t('booking.hold_note', ['n' => config('app.hold_minutes')])) ?></p>
          </div>
        </form>
      </div>
      <?= booking_summary($event, null, $holds[0]['expires_at'] ?? null) ?>
    </div>
  </div>
</section>
<script type="application/json" id="seatmap-data"><?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php render_footer(['scripts' => ['assets/js/seatmap.js', 'assets/js/booking.js']]);
