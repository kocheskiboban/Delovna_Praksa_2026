<?php
require __DIR__ . '/includes/bootstrap.php';

[$minDt, $maxDt] = event_month_range();
$minMonth = date('Y-m', strtotime($minDt));
$maxMonth = date('Y-m', strtotime($maxDt));

$month = input('month', '', 'get');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    // default: the current month, or the next month that has events if the current one is empty
    $month = date('Y-m');
    $next = db_value("SELECT MIN(starts_at) FROM events WHERE status = 'published' AND starts_at >= ?", [date('Y-m-01')]);
    if ($next && !db_value("SELECT 1 FROM events WHERE status = 'published' AND DATE_FORMAT(starts_at, '%Y-%m') = ? LIMIT 1", [$month])) {
        $month = date('Y-m', strtotime($next));
    }
}
$month = max($minMonth, min($maxMonth, $month));
[$y, $m] = array_map('intval', explode('-', $month));
$first = mktime(0, 0, 0, $m, 1, $y);
$days = get_events_by_day($y, $m);

$date = input('date', '', 'get');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || substr($date, 0, 7) !== $month) {
    $today = date('Y-m-d');
    $date = isset($days[$today]) ? $today : (array_key_first($days) ?? (substr($today, 0, 7) === $month ? $today : date('Y-m-d', $first)));
}
$selected = $days[$date] ?? [];

$prev = date('Y-m', strtotime('-1 month', $first));
$next = date('Y-m', strtotime('+1 month', $first));
$monthsWithEvents = array_column(db_all("SELECT DISTINCT DATE_FORMAT(starts_at, '%Y-%m') AS ym FROM events WHERE status = 'published' ORDER BY ym"), 'ym');
$weekdays = t_array('weekdays_short');
$startOffset = (int) date('N', $first) - 1;
$daysInMonth = (int) date('t', $first);
$cells = (int) ceil(($startOffset + $daysInMonth) / 7) * 7;

render_header(['title' => t('calendar.title') . ' — ' . fmt_date($first, 'month'), 'description' => t('calendar.meta'), 'body_class' => 'page-calendar']);
echo page_hero(t('calendar.eyebrow'), t('calendar.title'), t('calendar.lead'), [[t('nav.programme'), url('programme.php')], [t('nav.calendar'), null]]);
?>
<section class="section section--tight">
  <div class="container">
    <nav class="cal-months" aria-label="<?= e(t('calendar.months_with_events')) ?>">
      <?php foreach ($monthsWithEvents as $ym): ?>
        <a href="<?= e(url('calendar.php', ['month' => $ym])) ?>"<?= $ym === $month ? ' aria-current="page"' : '' ?>><?= e(fmt_date(strtotime($ym . '-01'), 'month')) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="cal-layout">
      <div class="cal">
        <div class="cal__head">
          <h2 id="cal-title"><?= e(fmt_date($first, 'month')) ?></h2>
          <div class="cal__nav">
            <?php if ($prev >= $minMonth): ?><a href="<?= e(url('calendar.php', ['month' => $prev])) ?>" aria-label="<?= e(t('calendar.prev')) ?>"><?= icon('chevron-left') ?></a><?php else: ?><span aria-hidden="true"><?= icon('chevron-left') ?></span><?php endif; ?>
            <?php if ($next <= $maxMonth): ?><a href="<?= e(url('calendar.php', ['month' => $next])) ?>" aria-label="<?= e(t('calendar.next')) ?>"><?= icon('chevron-right') ?></a><?php else: ?><span aria-hidden="true"><?= icon('chevron-right') ?></span><?php endif; ?>
          </div>
        </div>
        <table aria-labelledby="cal-title">
          <thead><tr><?php foreach ($weekdays as $wd): ?><th scope="col"><?= e($wd) ?></th><?php endforeach; ?></tr></thead>
          <tbody>
          <?php for ($i = 0; $i < $cells; $i++):
              if ($i % 7 === 0) echo '<tr>';
              $dayNum = $i - $startOffset + 1;
              $inMonth = $dayNum >= 1 && $dayNum <= $daysInMonth;
              echo '<td>';
              if ($inMonth) {
                  $d = sprintf('%04d-%02d-%02d', $y, $m, $dayNum);
                  $evs = $days[$d] ?? [];
                  $cls = 'cal__day' . ($evs ? ' has-events' : '') . ($d === date('Y-m-d') ? ' is-today' : '') . ($d === $date ? ' is-selected' : '');
                  $label = fmt_date($d) . ($evs ? ' — ' . t('calendar.n_events', ['n' => count($evs)]) : '');
                  echo '<a class="' . $cls . '" href="' . e(url('calendar.php', ['month' => $month, 'date' => $d])) . '#day-panel" aria-label="' . e($label) . '"' . ($d === $date ? ' aria-current="date"' : '') . '>';
                  echo '<span class="cal__num">' . $dayNum . '</span>';
                  if ($evs) {
                      echo '<span class="cal__dots">';
                      foreach ($evs as $ev) {
                          echo '<span class="cal__dot" style="--dot:' . e($ev['category_color']) . '"></span>';
                      }
                      echo '</span><span class="cal__label">' . e($evs[0]['title']) . '</span>';
                  }
                  echo '</a>';
              }
              echo '</td>';
              if ($i % 7 === 6) echo '</tr>';
          endfor; ?>
          </tbody>
        </table>
        <div class="cal-legend">
          <?php foreach (get_categories() as $c): ?><span><i class="cal__dot" style="--dot:<?= e($c['color']) ?>"></i><?= e($c['name']) ?></span><?php endforeach; ?>
        </div>
      </div>

      <aside class="day-panel" id="day-panel" aria-live="polite" aria-labelledby="day-title">
        <p class="eyebrow"><?= e(t('calendar.selected_day')) ?></p>
        <h2 id="day-title"><?= e(fmt_date($date, 'weekday')) ?></h2>
        <?php if (!$selected): ?>
          <p class="muted"><?= e(t('calendar.no_events')) ?></p>
          <?php if ($days): ?><p><?= e(t('calendar.pick_day')) ?></p><?php endif; ?>
        <?php else: ?>
          <?php foreach ($selected as $ev): ?>
            <article class="day-event">
              <img src="<?= e(img($ev['image'])) ?>" alt="" width="70" height="70" loading="lazy">
              <div>
                <h3><a href="<?= e(url('event.php', ['slug' => $ev['slug']])) ?>"><?= e($ev['title']) ?></a></h3>
                <p><?= icon('clock') ?> <?= e(fmt_time($ev['starts_at'])) ?> — <?= e($ev['venue_name']) ?></p>
                <p><?= availability_badge($ev) ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
        <hr>
        <p class="muted"><?= e(t('calendar.month_summary', ['n' => array_sum(array_map('count', $days))])) ?></p>
        <a class="btn btn--ghost btn--block" href="<?= e(url('programme.php', ['date_from' => date('Y-m-01', $first), 'date_to' => date('Y-m-t', $first), 'when' => 'all'])) ?>"><?= icon('list') ?><?= e(t('calendar.as_list')) ?></a>
      </aside>
    </div>
  </div>
</section>
<?php render_footer();
