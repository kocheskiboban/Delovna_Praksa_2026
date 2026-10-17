<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

$event = booking_event_or_fail();
$types = get_ticket_types();
$b = booking_get((int) $event['id']);
$errors = [];

if (is_post()) {
    csrf_check();
    [$qty, $total, $errs] = validate_quantities((array) ($_POST['qty'] ?? []), $types, $event);
    if ($errs) {
        $errors = $errs;
        $b['qty'] = $qty;
    } else {
        if ($qty !== ($b['qty'] ?? [])) {
            release_holds((int) $event['id'], user_id());
        }
        booking_put(['event_id' => (int) $event['id'], 'qty' => $qty, 'terms' => false]);
        redirect(url($event['mode'] === 'seated' ? 'booking/seats.php' : 'booking/review.php', ['event' => $event['slug']]));
    }
}
$max = min((int) config('app.max_tickets', 10), (int) $event['available']);

render_header(['title' => t('booking.step_tickets') . ' — ' . $event['title'], 'noindex' => true, 'body_class' => 'page-booking', 'scripts' => ['assets/js/booking.js']]);
echo page_hero(t('booking.eyebrow'), $event['title'], fmt_date($event['starts_at'], 'weekday') . ', ' . fmt_time($event['starts_at']) . ' — ' . $event['venue_name'],
    [[t('nav.programme'), url('programme.php')], [$event['title'], url('event.php', ['slug' => $event['slug']])], [t('booking.step_tickets'), null]]);
?>
<section class="section section--tight">
  <div class="container">
    <?= booking_steps(1, $event['mode'] === 'seated') ?>
    <div class="booking-layout">
      <form class="panel" method="post" action="<?= e(url('booking/tickets.php', ['event' => $event['slug']])) ?>" data-qty-form
            data-price="<?= e((string) $event['price_standard']) ?>" data-max="<?= $max ?>" data-currency="<?= e(lang() === 'mk' ? ' ден.' : ' MKD') ?>">
        <?= csrf_field() ?>
        <h2><?= e(t('booking.choose_tickets')) ?></h2>
        <p class="muted"><?= e(t('booking.choose_lead', ['max' => $max])) ?></p>
        <?php foreach ($errors as $err): ?><?= alert('error', e(t($err, ['max' => $max, 'n' => $event['available']]))) ?><?php endforeach; ?>
        <table class="qty-table">
          <thead><tr><th scope="col"><?= e(t('ticket.category')) ?></th><th scope="col"><?= e(t('ticket.price')) ?></th><th scope="col"><?= e(t('booking.quantity')) ?></th></tr></thead>
          <tbody>
          <?php foreach ($types as $tt):
              $price = round($event['price_standard'] * (100 - $tt['discount_percent']) / 100);
              $vip = $event['price_vip'] ? round($event['price_vip'] * (100 - $tt['discount_percent']) / 100) : null;
              $val = (int) ($b['qty'][$tt['code']] ?? 0); ?>
            <tr>
              <td><strong><?= e($tt['name']) ?></strong><small><?= e($tt['description']) ?></small></td>
              <td><?= e(money($price)) ?><?php if ($vip && $event['mode'] === 'seated'): ?><br><small><?= e(t('booking.vip_price', ['price' => money($vip)])) ?></small><?php endif; ?></td>
              <td>
                <div class="stepper" data-stepper>
                  <button type="button" data-step="-1" aria-label="<?= e(t('booking.less', ['type' => $tt['name']])) ?>">−</button>
                  <label class="visually-hidden" for="qty-<?= e($tt['code']) ?>"><?= e(t('booking.quantity')) ?>: <?= e($tt['name']) ?></label>
                  <input id="qty-<?= e($tt['code']) ?>" type="number" name="qty[<?= e($tt['code']) ?>]" value="<?= $val ?>" min="0" max="<?= $max ?>" inputmode="numeric" data-unit="<?= e((string) $price) ?>">
                  <button type="button" data-step="1" aria-label="<?= e(t('booking.more', ['type' => $tt['name']])) ?>">+</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <p style="margin-top:var(--s-4)"><strong><?= e(t('booking.selected')) ?>:</strong> <span data-qty-total>0</span> · <strong><?= e(t('booking.estimate')) ?>:</strong> <span data-qty-sum><?= e(money(0)) ?></span></p>
        <p class="muted" style="font-size:var(--step--1)"><?= e($event['mode'] === 'seated' ? t('booking.next_seats_note') : t('booking.next_ga_note', ['n' => $event['available']])) ?></p>
        <div class="form-actions">
          <a class="btn btn--ghost" href="<?= e(url('event.php', ['slug' => $event['slug']])) ?>"><?= icon('arrow-left') ?><?= e(t('common.back')) ?></a>
          <button class="btn btn--primary btn--lg" type="submit"><?= e($event['mode'] === 'seated' ? t('booking.to_seats') : t('booking.to_review')) ?><?= icon('arrow-right') ?></button>
        </div>
      </form>
      <?= booking_summary($event) ?>
    </div>
  </div>
</section>
<?php render_footer(['scripts' => ['assets/js/booking.js']]);
