<?php
/** Venue directory. */
require __DIR__ . '/includes/bootstrap.php';

$venues = get_venues();
render_header(['title' => t('venues.title'), 'description' => t('venues.meta'), 'body_class' => 'page-venues']);
echo page_hero(t('venues.eyebrow'), t('venues.title'), t('venues.lead'), [[t('nav.venues'), null]]);
?>
<section class="section">
  <div class="container">
    <div class="results-bar">
      <p><strong><?= e(t('venues.count', ['n' => count($venues)])) ?></strong></p>
      <a class="btn btn--ghost" href="<?= e(url('map.php')) ?>"><?= icon('map') ?><?= e(t('venues.open_map')) ?></a>
    </div>
    <div class="grid grid--3"><?php foreach ($venues as $v): ?><?= venue_card($v) ?><?php endforeach; ?></div>
  </div>
</section>
<?php render_footer();
