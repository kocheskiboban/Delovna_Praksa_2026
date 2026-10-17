<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

$event = booking_event_or_fail();
$b = booking_get((int) $event['id']);
$need = booking_qty_total($b);
if ($need < 1) {
    redirect(url('booking/tickets.php', ['event' => $event['slug']]));
}
$seats = booking_seats($event);
if ($event['mode'] === 'seated' && count($seats) !== $need) {
    flash('error', t('booking.hold_expired'));
    redirect(url('booking/seats.php', ['event' => $event['slug']]));
}
$types = get_ticket_types();
$pricing = price_lines($event, $b['qty'], $seats, $types);
$errors = [];
if (is_post()) {
    csrf_check();
    if (empty($_POST['terms'])) {
        $errors['terms'] = 'validation.terms';
    } else {
        $b['terms'] = true;
        booking_put($b);
        redirect(url('booking/payment.php', ['event' => $event['slug']]));
    }
}

render_header(['title' => t('booking.step_review') . ' — ' . $event['title'], 'noindex' => true, 'body_class' => 'page-booking', 'scripts' => ['assets/js/booking.js']]);
echo page_hero(t('booking.eyebrow'), $event['title'], fmt_date($event['starts_at'], 'weekday') . ', ' . fmt_time($event['starts_at']) . ' — ' . $event['venue_name'],
    [[t('nav.programme'), url('programme.php')], [$event['title'], url('event.php', ['slug' => $event['slug']])], [t('booking.step_review'), null]]);
?>
<section class="section section--tight">
  <div class="container">
    <?= booking_steps(3, $event['mode'] === 'seated') ?>
    <div class="booking-layout">
      <form class="panel" method="post" action="<?= e(url('booking/review.php', ['event' => $event['slug']])) ?>" novalidate data-validate>
        <?= csrf_field() ?>
        <h2><?= e(t('booking.review_title')) ?></h2>
        <p class="muted"><?= e(t('booking.review_lead')) ?></p>
        <div class="table-wrap">
          <table class="data-table" style="min-width:520px">
            <thead><tr><th>#</th><th><?= e(t('ticket.category')) ?></th><th><?= e(t('ticket.seat')) ?></th><th><?= e(t('booking.zone')) ?></th><th style="text-align:right"><?= e(t('ticket.price')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($pricing['lines'] as $i => $l): ?>
              <tr><td><?= $i + 1 ?></td><td><?= e($l['type_name']) ?><?= $l['discount'] ? ' <span class="muted">(−' . (int) $l['discount'] . '%)</span>' : '' ?></td>
                <td><?= e($l['seat'] ? seat_label($l['seat']) : t('seat.general')) ?></td>
                <td><?= $l['zone'] === 'vip' ? '<span class="badge badge--warning">VIP</span>' : e(t('seat.standard')) ?></td>
                <td style="text-align:right"><?= e(money($l['price'])) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr><td colspan="4"><?= e(t('orders.subtotal')) ?></td><td style="text-align:right"><?= e(money($pricing['subtotal'])) ?></td></tr>
              <tr><td colspan="4"><?= e(t('orders.fee')) ?> <span class="muted">(<?= e(t('booking.fee_note')) ?>)</span></td><td style="text-align:right"><?= e(money($pricing['fee'])) ?></td></tr>
              <tr><td colspan="4"><strong><?= e(t('orders.total')) ?></strong></td><td style="text-align:right"><strong><?= e(money($pricing['total'])) ?></strong></td></tr>
            </tfoot>
          </table>
        </div>
        <dl class="profile__facts">
          <dt><?= e(t('booking.buyer')) ?></dt><dd><?= e(current_user()['first_name'] . ' ' . current_user()['last_name']) ?> (<?= e(current_user()['email']) ?>)</dd>
          <dt><?= e(t('event.venue')) ?></dt><dd><?= e($event['venue_name']) ?></dd>
          <dt><?= e(t('event.date')) ?></dt><dd><?= e(fmt_date($event['starts_at'], 'weekday')) ?>, <?= e(fmt_time($event['starts_at'])) ?></dd>
        </dl>
        <?= field(['name' => 'terms', 'type' => 'checkbox', 'label' => '', 'required' => true, 'errors' => $errors, 'checked' => !empty($b['terms']),
            'label_html' => t('booking.accept', ['terms' => '<a href="' . e(url('terms.php')) . '" target="_blank">' . e(t('footer.terms')) . '</a>'])]) ?>
        <div class="form-actions">
          <a class="btn btn--ghost" href="<?= e(url($event['mode'] === 'seated' ? 'booking/seats.php' : 'booking/tickets.php', ['event' => $event['slug']])) ?>"><?= icon('arrow-left') ?><?= e(t($event['mode'] === 'seated' ? 'booking.change_seats' : 'booking.change_qty')) ?></a>
          <button class="btn btn--primary btn--lg" type="submit"><?= icon('card') ?><?= e(t('booking.to_payment')) ?></button>
        </div>
      </form>
      <?= booking_summary($event, $pricing, $seats[0]['expires_at'] ?? null) ?>
    </div>
  </div>
</section>
<?php render_footer(['scripts' => ['assets/js/booking.js']]);
