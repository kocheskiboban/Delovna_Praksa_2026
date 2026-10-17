<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

$event = booking_event_or_fail();
$b = booking_get((int) $event['id']);
$need = booking_qty_total($b);
if ($need < 1) {
    redirect(url('booking/tickets.php', ['event' => $event['slug']]));
}
if (empty($b['terms'])) {
    redirect(url('booking/review.php', ['event' => $event['slug']]));
}
$seats = booking_seats($event);
if ($event['mode'] === 'seated' && count($seats) !== $need) {
    flash('error', t('booking.hold_expired'));
    redirect(url('booking/seats.php', ['event' => $event['slug']]));
}
$types = get_ticket_types();
$method = in_array($_POST['method'] ?? '', PAYMENT_METHODS, true) ? $_POST['method'] : 'visa';
$errors = [];
$failed = false;

if (is_post()) {
    csrf_check();
    $pricing = price_lines($event, $b['qty'], $seats, $types, $method);
    $card = [];
    if (in_array($method, CARD_METHODS, true)) {
        [$errors, $card] = validate_card($_POST, $method);
    }
    if (!$errors) {
        if (in_array($method, CARD_METHODS, true) && !demo_gateway_approves($card['number'])) {
            record_failed_payment(current_user(), $event, $pricing, $method, $card);
            $failed = true;
        } else {
            try {
                $orderId = place_order(current_user(), $event, $pricing, $method, $card);
                booking_clear();
                $_SESSION['last_order'] = $orderId;
                redirect(url('booking/confirmation.php', ['order' => $orderId]));
            } catch (RuntimeException $e) {
                flash('error', t($e->getMessage() === 'sold_out' ? 'booking.err_soldout' : 'booking.hold_expired'));
                redirect(url($event['mode'] === 'seated' ? 'booking/seats.php' : 'booking/tickets.php', ['event' => $event['slug']]));
            }
        }
    }
}
$pricing = price_lines($event, $b['qty'], $seats, $types, $method);
$methods = [
    'visa' => ['card', t('payment.method_visa'), t('payment.instant')],
    'mastercard' => ['card', t('payment.method_mastercard'), t('payment.instant')],
    'maestro' => ['card', t('payment.method_maestro'), t('payment.instant')],
    'bank_transfer' => ['bank', t('payment.method_bank_transfer'), t('payment.transfer_short', ['n' => config('app.transfer_days')])],
    'box_office' => ['cash', t('payment.method_box_office'), t('payment.boxoffice_short', ['n' => config('app.reservation_hours')])],
];
$v = static fn($k) => e((string) ($_POST[$k] ?? ''));

render_header(['title' => t('booking.step_payment') . ' — ' . $event['title'], 'noindex' => true, 'body_class' => 'page-booking', 'scripts' => ['assets/js/booking.js']]);
echo page_hero(t('booking.eyebrow'), $event['title'], fmt_date($event['starts_at'], 'weekday') . ', ' . fmt_time($event['starts_at']) . ' — ' . $event['venue_name'],
    [[t('nav.programme'), url('programme.php')], [$event['title'], url('event.php', ['slug' => $event['slug']])], [t('booking.step_payment'), null]]);
?>
<section class="section section--tight">
  <div class="container">
    <?= booking_steps(4, $event['mode'] === 'seated') ?>
    <div class="booking-layout">
      <div>
        <?php if ($failed): ?>
          <div class="panel result result--failed" role="alert">
            <span class="result__icon"><?= icon('alert') ?></span>
            <h2><?= e(t('payment.failed_title')) ?></h2>
            <p><?= e(t('payment.failed_text')) ?></p>
            <p class="muted"><?= e(t('payment.failed_hint')) ?></p>
          </div>
        <?php endif; ?>
        <form class="panel" method="post" action="<?= e(url('booking/payment.php', ['event' => $event['slug']])) ?>" novalidate data-payment-form data-validate
              data-processing="<?= e(t('payment.processing')) ?>" data-processing-sub="<?= e(t('payment.processing_sub')) ?>">
          <?= csrf_field() ?>
          <h2><?= e(t('payment.title')) ?></h2>
          <div class="demo-banner" role="note"><?= icon('info') ?><div><strong><?= e(t('payment.demo_title')) ?></strong> <?= e(t('payment.demo_text')) ?><br>
            <?= e(t('payment.demo_cards')) ?> Visa <code>4242 4242 4242 4242</code>, Mastercard <code>5555 5555 5555 4444</code>, Maestro <code>6759 6498 2643 8453</code>. <?= e(t('payment.demo_decline')) ?> <code>4000 0000 0000 0002</code>.</div></div>
          <?php if ($errors): ?><?= alert('error', e(t('validation.form_errors'))) ?><?php endif; ?>
          <fieldset class="pay-methods">
            <legend><?= e(t('payment.choose')) ?></legend>
            <?php foreach ($methods as $key => [$ic, $label, $sub]): ?>
              <div class="pay-option">
                <input type="radio" name="method" id="pm-<?= e($key) ?>" value="<?= e($key) ?>" <?= $method === $key ? 'checked' : '' ?>>
                <label for="pm-<?= e($key) ?>"><?= icon($ic) ?><?= e($label) ?><small><?= e($sub) ?></small></label>
              </div>
            <?php endforeach; ?>
          </fieldset>

          <div data-pay-panel="card" <?= in_array($method, CARD_METHODS, true) ? '' : 'hidden' ?>>
            <div class="card-preview" aria-hidden="true">
              <span><?= e(t('payment.demo_card')) ?></span>
              <span class="card-preview__num" data-card-preview>•••• •••• •••• ••••</span>
              <div class="card-preview__row"><span data-name-preview><?= e(t('payment.card_name')) ?></span><span data-exp-preview>MM/YY</span></div>
            </div>
            <div class="form-grid">
              <div class="span-2"><?= field(['name' => 'card_name', 'label' => t('payment.card_name'), 'value' => $_POST['card_name'] ?? (current_user()['first_name'] . ' ' . current_user()['last_name']), 'required' => true, 'errors' => $errors, 'autocomplete' => 'cc-name']) ?></div>
              <div class="span-2"><?= field(['name' => 'card_number', 'label' => t('payment.card_number'), 'value' => '', 'required' => true, 'errors' => $errors, 'autocomplete' => 'cc-number', 'attrs' => 'inputmode="numeric" placeholder="1234 5678 9012 3456" maxlength="23" data-card-number']) ?></div>
              <?= field(['name' => 'card_expiry', 'label' => t('payment.card_expiry'), 'value' => $_POST['card_expiry'] ?? '', 'required' => true, 'errors' => $errors, 'autocomplete' => 'cc-exp', 'attrs' => 'placeholder="MM/YY" inputmode="numeric" maxlength="7" data-card-expiry']) ?>
              <?= field(['name' => 'card_cvc', 'label' => 'CVC', 'value' => '', 'required' => true, 'errors' => $errors, 'autocomplete' => 'cc-csc', 'attrs' => 'placeholder="123" inputmode="numeric" maxlength="4"', 'hint' => t('payment.cvc_hint')]) ?>
            </div>
          </div>
          <div data-pay-panel="bank_transfer" <?= $method === 'bank_transfer' ? '' : 'hidden' ?>>
            <?= alert('info', e(t('payment.bank_explain', ['iban' => config('festival.iban'), 'n' => config('app.transfer_days')]))) ?>
          </div>
          <div data-pay-panel="box_office" <?= $method === 'box_office' ? '' : 'hidden' ?>>
            <?= alert('info', e(t('payment.box_explain', ['n' => config('app.reservation_hours')]))) ?>
          </div>
          <div class="form-actions">
            <a class="btn btn--ghost" href="<?= e(url('booking/review.php', ['event' => $event['slug']])) ?>"><?= icon('arrow-left') ?><?= e(t('common.back')) ?></a>
            <button class="btn btn--primary btn--lg" type="submit" data-pay-button><?= icon('lock') ?><span data-pay-label><?= e(in_array($method, CARD_METHODS, true) ? t('payment.pay', ['total' => money($pricing['total'])]) : t('payment.reserve')) ?></span></button>
          </div>
          <template data-pay-labels><span data-card="<?= e(t('payment.pay', ['total' => money(price_lines($event, $b['qty'], $seats, $types, 'visa')['total'])])) ?>" data-other="<?= e(t('payment.reserve')) ?>"></span></template>
        </form>
      </div>
      <?= booking_summary($event, $pricing, $seats[0]['expires_at'] ?? null) ?>
    </div>
  </div>
</section>
<?php render_footer(['scripts' => ['assets/js/booking.js']]);
