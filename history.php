<?php
/** History of the festival with timeline. */
require __DIR__ . '/includes/bootstrap.php';

$p = page_text('history');
$editions = get_editions();
$albums = get_albums(['type' => 'festival']);
render_header(['title' => $p['title'], 'description' => $p['meta'], 'body_class' => 'page-history']);
echo page_hero($p['eyebrow'], $p['title'], $p['lead'], [[t('nav.about'), url('about.php')], [t('nav.history'), null]], 'assets/img/venues/st-sophia.jpg');
?>
<section class="section">
  <div class="container layout-sidebar">
    <div>
      <div class="prose"><?= rich_text($p['intro']) ?></div>
      <h2 style="margin-top:var(--s-6)"><?= e($p['timeline_h']) ?></h2>
      <ol class="timeline">
        <?php foreach ($p['timeline'] as [$year, $title, $text]): ?>
          <li><time><?= e($year) ?></time><div><h3><?= e($title) ?></h3><p><?= e($text) ?></p></div></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <aside>
      <div class="info-panel">
        <h3><?= icon('star') ?><?= e($p['artists_h']) ?></h3>
        <p><?= e($p['artists_text']) ?></p>
        <ul class="names-cloud">
          <?php foreach (['Sviatoslav Richter', 'Mstislav Rostropovich', 'Leonid Kogan', 'Gidon Kremer', 'Martina Arroyo', 'José Carreras', 'Zubin Mehta', 'Ennio Morricone'] as $nm): ?><li><?= e($nm) ?></li><?php endforeach; ?>
        </ul>
        <p class="muted" style="font-size:var(--step--1);margin-top:var(--s-4)"><?= e($p['artists_note']) ?></p>
      </div>
    </aside>
  </div>
</section>
<section class="section section--paper" aria-labelledby="prev-editions">
  <div class="container">
    <?= section_head($p['archive_eyebrow'], $p['archive_h'], url('editions.php'), t('nav.editions'), 'prev-editions') ?>
    <p class="lead"><?= e($p['archive_text']) ?></p>
    <?php foreach ($editions as $ed): if (strtotime($ed['end_date']) > time()) continue; ?>
      <article class="edition-row">
        <p class="edition-row__year"><?= (int) $ed['year'] ?><small><?= e(t('editions.number', ['n' => $ed['edition_number']])) ?></small></p>
        <div><h3 style="font-size:var(--step-2)"><?= e($ed['theme']) ?></h3><p><?= e($ed['summary']) ?></p></div>
        <a class="btn btn--ghost" href="<?= e(url('edition.php', ['year' => $ed['year']])) ?>"><?= e(t('editions.view')) ?></a>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php if ($albums): ?>
<section class="section" aria-labelledby="hist-photos">
  <div class="container">
    <?= section_head($p['photos_eyebrow'], $p['photos_h'], url('gallery.php'), t('home.open_gallery'), 'hist-photos') ?>
    <div class="album-grid">
      <?php foreach (array_slice($albums, 0, 3) as $al): ?>
        <a class="album" href="<?= e(url('album.php', ['slug' => $al['slug']])) ?>"><div class="album__cover"><img src="<?= e(img($al['cover_image'])) ?>" alt="" loading="lazy" width="600" height="400"><span class="album__count"><?= icon('image') ?><?= (int) $al['image_count'] ?></span></div><h3><?= e($al['title']) ?></h3></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php render_footer();
