<?php
require __DIR__ . '/includes/bootstrap.php';

$al = get_album_by_slug(input('slug', '', 'get'));
if (!$al) {
    not_found();
}
$photos = get_gallery_images(['album_id' => (int) $al['id']]);
$event = $al['event_id'] ? get_event_by_id((int) $al['event_id']) : null;
$venue = null;
if ($al['venue_id']) {
    foreach (get_venues() as $v) {
        if ((int) $v['id'] === (int) $al['venue_id']) {
            $venue = $v;
        }
    }
}

render_header(['title' => $al['title'], 'description' => $al['description'], 'image' => $al['cover_image'], 'scripts' => ['assets/js/gallery.js']]);
echo page_hero(t('gallery.album'), $al['title'], (string) $al['description'], [[t('nav.gallery'), url('gallery.php')], [$al['title'], null]]);
?>
<section class="section section--tight">
  <div class="container">
    <p class="muted photo-note"><?= icon('info') ?> <?= e(t('gallery.photo_note')) ?></p>
    <div class="results-bar">
      <p><?= e(t('gallery.photos_n', ['n' => count($photos)])) ?></p>
      <div class="links-row">
        <?php if ($event): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('event.php', ['slug' => $event['slug']])) ?>"><?= icon('calendar') ?><?= e($event['title']) ?></a><?php endif; ?>
        <?php if ($venue): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('venue.php', ['slug' => $venue['slug']])) ?>"><?= icon('pin') ?><?= e($venue['name']) ?></a><?php endif; ?>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('gallery.php')) ?>"><?= icon('arrow-left') ?><?= e(t('gallery.back')) ?></a>
      </div>
    </div>
    <?php if (!$photos): ?>
      <?= empty_state('image', t('gallery.empty'), '', url('gallery.php'), t('gallery.back')) ?>
    <?php else: ?>
    <div class="photo-grid" data-lightbox-group>
      <?php foreach ($photos as $i => $p): ?>
        <figure>
          <a href="<?= e(img($p['image'])) ?>" data-lightbox data-caption="<?= e($p['caption']) ?>">
            <img src="<?= e(img($p['image'])) ?>" alt="<?= e($p['caption']) ?>" loading="<?= $i < 3 ? 'eager' : 'lazy' ?>" width="600" height="400">
          </a>
          <figcaption><?= e($p['caption']) ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php render_footer(['scripts' => ['assets/js/gallery.js']]);
