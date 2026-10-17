<?php
require __DIR__ . '/includes/bootstrap.php';

$ed = get_edition_by_year(input_int('year'));
if (!$ed) {
    not_found();
}
[$events] = get_events(['edition' => (int) $ed['year'], 'sort' => 'date_asc']);
$artistIds = $events ? array_map('intval', array_column(db_all('SELECT DISTINCT artist_id FROM event_artists WHERE event_id IN (' . db_placeholders($events) . ')', array_column($events, 'id')), 'artist_id')) : [];
[$artists] = $artistIds ? get_artists(['ids' => $artistIds]) : [[], 0];
$venueNames = array_unique(array_column($events, 'venue_name'));
$albums = get_albums(['edition_id' => (int) $ed['id']]);
$byCat = [];
foreach ($events as $e) {
    $byCat[$e['category_name']] = ($byCat[$e['category_name']] ?? 0) + 1;
}

render_header(['title' => t('editions.edition_title', ['year' => $ed['year'], 'theme' => $ed['theme']]), 'description' => $ed['summary']]);
echo page_hero(t('editions.number', ['n' => $ed['edition_number']]), $ed['year'] . ' — ' . $ed['theme'], (string) $ed['summary'],
    [[t('nav.editions'), url('editions.php')], [(string) $ed['year'], null]]);
?>
<section class="section section--tight">
  <div class="container">
    <div class="stats">
      <?= stat_item(fmt_date($ed['start_date'], 'day') . ' – ' . fmt_date($ed['end_date'], 'day'), t('editions.dates')) ?>
      <?= stat_item((string) count($events), t('editions.events')) ?>
      <?= stat_item((string) count($artists), t('editions.artists')) ?>
      <?= stat_item((string) count($venueNames), t('editions.venues')) ?>
      <?php if ($ed['visitors']): ?><?= stat_item(number_format((int) $ed['visitors'], 0, ',', '.'), t('stats.visitors')) ?><?php endif; ?>
      <?php if ($ed['countries']): ?><?= stat_item((string) $ed['countries'], t('stats.countries')) ?><?php endif; ?>
      <?= stat_item(number_format((int) $ed['tickets_sold'], 0, ',', '.'), t('editions.tickets')) ?>
    </div>
    <?php if ($byCat): ?>
      <p class="muted" style="margin-top:var(--s-4)"><?= e(t('editions.by_category')) ?>:
        <?= e(implode(', ', array_map(static fn($k, $v) => "$k ($v)", array_keys($byCat), $byCat))) ?></p>
    <?php endif; ?>
  </div>
</section>
<section class="section section--paper" aria-labelledby="ed-prog">
  <div class="container">
    <?= section_head(t('editions.programme_eyebrow'), t('editions.programme'), url('programme.php', ['edition' => $ed['year']]), t('editions.filter_programme'), 'ed-prog') ?>
    <?php if ($events): ?>
      <div class="event-list"><?php foreach ($events as $ev): ?><?= event_card($ev, 'row') ?><?php endforeach; ?></div>
    <?php else: ?>
      <?= empty_state('calendar', t('editions.no_events'), '', url('editions.php'), t('nav.editions')) ?>
    <?php endif; ?>
  </div>
</section>
<?php if ($artists): ?>
<section class="section" aria-labelledby="ed-art">
  <div class="container">
    <?= section_head(t('editions.artists_eyebrow'), t('editions.artists'), null, null, 'ed-art') ?>
    <div class="grid grid--4"><?php foreach ($artists as $a): ?><?= artist_card($a) ?><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>
<section class="section section--paper" aria-labelledby="ed-ven">
  <div class="container">
    <h2 id="ed-ven"><?= e(t('editions.venues')) ?></h2>
    <ul class="names-cloud"><?php foreach ($venueNames as $vn): ?><li><?= e($vn) ?></li><?php endforeach; ?></ul>
    <?php if ($albums): ?>
      <h2 style="margin-top:var(--s-6)"><?= e(t('nav.gallery')) ?></h2>
      <div class="album-grid">
        <?php foreach ($albums as $al): ?><a class="album" href="<?= e(url('album.php', ['slug' => $al['slug']])) ?>"><div class="album__cover"><img src="<?= e(img($al['cover_image'])) ?>" alt="" loading="lazy" width="600" height="400"></div><h3><?= e($al['title']) ?></h3></a><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php render_footer();
