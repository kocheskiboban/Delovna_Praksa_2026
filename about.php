<?php
require __DIR__ . '/includes/bootstrap.php';

$p = page_text('about');
$stats = festival_stats();
$edition = get_current_edition();
render_header(['title' => $p['title'], 'description' => $p['meta'], 'body_class' => 'page-about']);
echo page_hero($p['eyebrow'], $p['title'], $p['lead'], [[t('nav.about'), null]], 'assets/img/venues/antique-theatre.jpg');
?>
<section class="section">
  <div class="container layout-sidebar">
    <div class="prose">
      <h2 id="overview"><?= e($p['overview_h']) ?></h2>
      <?= rich_text($p['overview']) ?>
      <h2 id="mission"><?= e($p['mission_h']) ?></h2>
      <?= rich_text($p['mission']) ?>
      <blockquote><?= e($p['quote']) ?></blockquote>
      <h2 id="significance"><?= e($p['significance_h']) ?></h2>
      <?= rich_text($p['significance']) ?>
      <h2 id="description"><?= e($p['description_h']) ?></h2>
      <?= rich_text($p['description']) ?>
    </div>
    <aside class="toc" aria-label="<?= e(t('a11y.on_this_page')) ?>">
      <p class="eyebrow"><?= e(t('a11y.on_this_page')) ?></p>
      <ul>
        <li><a href="#overview"><?= e($p['overview_h']) ?></a></li>
        <li><a href="#mission"><?= e($p['mission_h']) ?></a></li>
        <li><a href="#significance"><?= e($p['significance_h']) ?></a></li>
        <li><a href="#description"><?= e($p['description_h']) ?></a></li>
        <li><a href="#facts"><?= e($p['facts_h']) ?></a></li>
        <li><a href="#organisation"><?= e($p['org_h']) ?></a></li>
      </ul>
    </aside>
  </div>
</section>

<section class="section section--ink" aria-labelledby="facts">
  <div class="container">
    <?= section_head($p['stats_eyebrow'], $p['facts_h'], null, null, 'facts') ?>
    <div class="stats" style="margin-bottom:var(--s-6)">
      <?= stat_item('1961', t('stats.since')) ?>
      <?= stat_item((string) $stats['editions'], t('stats.editions')) ?>
      <?= stat_item((string) $stats['venues'], t('stats.venues')) ?>
      <?= $stats['visitors'] > 0 ? stat_item(number_format($stats['visitors'], 0, ',', '.'), t('stats.visitors')) : stat_item((string) $stats['last_events'], t('stats.last_events')) ?>
      <?= stat_item((string) $stats['countries'], t('stats.countries')) ?>
      <?= stat_item('1994', t('stats.efa')) ?>
    </div>
    <div class="grid grid--2">
      <?php foreach ($p['facts'] as [$h, $txt]): ?>
        <div><h3><?= e($h) ?></h3><p><?= e($txt) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" aria-labelledby="organisation">
  <div class="container layout-sidebar">
    <div class="prose">
      <h2 id="organisation"><?= e($p['org_h']) ?></h2>
      <?= rich_text($p['org']) ?>
    </div>
    <div class="info-panel">
      <h3><?= icon('award') ?><?= e(t('about.explore')) ?></h3>
      <ul style="list-style:none;padding:0;display:grid;gap:.6rem">
        <li><a class="link-arrow" href="<?= e(url('history.php')) ?>"><?= e(t('nav.history')) ?><?= icon('arrow-right') ?></a></li>
        <li><a class="link-arrow" href="<?= e(url('editions.php')) ?>"><?= e(t('nav.editions')) ?><?= icon('arrow-right') ?></a></li>
        <li><a class="link-arrow" href="<?= e(url('team.php')) ?>"><?= e(t('nav.team')) ?><?= icon('arrow-right') ?></a></li>
        <li><a class="link-arrow" href="<?= e(url('services.php')) ?>"><?= e(t('nav.services')) ?><?= icon('arrow-right') ?></a></li>
        <li><a class="link-arrow" href="<?= e(url('sponsors.php')) ?>"><?= e(t('nav.sponsors')) ?><?= icon('arrow-right') ?></a></li>
        <li><a class="link-arrow" href="<?= e(url('faq.php')) ?>"><?= e(t('nav.faq')) ?><?= icon('arrow-right') ?></a></li>
      </ul>
      <?php if ($edition): ?><p class="muted"><?= e(t('about.next_edition', ['n' => $edition['edition_number'], 'from' => fmt_date($edition['start_date']), 'to' => fmt_date($edition['end_date'])])) ?></p><?php endif; ?>
    </div>
  </div>
</section>
<?php render_footer();
