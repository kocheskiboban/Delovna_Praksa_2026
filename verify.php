<?php
/** Public ticket verification (what an usher's scanner would check). Shows no personal data. */
require __DIR__ . '/includes/bootstrap.php';

$code = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', input('code', '', 'get')) ?? '');
$ticket = null;
if ($code !== '') {
    $rows = get_tickets(['code' => $code]);
    $ticket = $rows[0] ?? null;
}
render_header(['title' => t('verify.title'), 'description' => t('verify.meta'), 'noindex' => $code !== '']);
echo page_hero(t('verify.eyebrow'), t('verify.title'), t('verify.lead'), [[t('nav.verify'), null]]);
?>
<section class="section">
  <div class="container--narrow">
    <form class="form-card" method="get" action="<?= e(url('verify.php')) ?>">
      <?php if (lang() !== config('app.default_lang')): ?><input type="hidden" name="lang" value="<?= e(lang()) ?>"><?php endif; ?>
      <?= field(['name' => 'code', 'label' => t('verify.code'), 'value' => $code, 'required' => true, 'hint' => t('verify.hint'), 'attrs' => 'placeholder="OL-XXXXX-XXXXX" pattern="[A-Za-z0-9-]{8,24}" autocapitalize="characters"']) ?>
      <button class="btn btn--primary" type="submit"><?= icon('qr') ?><?= e(t('verify.check')) ?></button>
    </form>
    <?php if ($code !== ''): ?>
      <?php if (!$ticket): ?>
        <div class="result result--failed"><span class="result__icon"><?= icon('alert') ?></span><h2><?= e(t('verify.invalid')) ?></h2><p><?= e(t('verify.invalid_text', ['code' => $code])) ?></p></div>
      <?php else:
          $kind = match ($ticket['status']) { 'valid' => 'success', 'reserved' => 'pending', default => 'failed' };
          $past = strtotime($ticket['starts_at']) + 6 * 3600 < time(); ?>
        <div class="result result--<?= $kind ?>">
          <span class="result__icon"><?= icon($kind === 'success' ? 'check-circle' : ($kind === 'pending' ? 'clock' : 'alert')) ?></span>
          <h2><?= e(t('verify.state_' . $ticket['status'])) ?></h2>
          <p><strong><?= e($ticket['event_title']) ?></strong><br><?= e(fmt_date($ticket['starts_at'], 'weekday')) ?>, <?= e(fmt_time($ticket['starts_at'])) ?> — <?= e($ticket['venue_name']) ?></p>
          <p><?= e(ticket_seat_text($ticket)) ?>, <?= e($ticket['type_name']) ?></p>
          <p><?= status_badge($ticket['status']) ?> <?php if ($past && $ticket['status'] === 'valid'): ?><span class="badge badge--muted"><?= e(t('verify.event_passed')) ?></span><?php endif; ?></p>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php render_footer();
