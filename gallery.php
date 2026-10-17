<?php
/** Photo gallery: albums + filterable photo wall with lightbox. */
require __DIR__ . '/includes/bootstrap.php';

$types = ['event' => t('gallery.type_event'), 'venue' => t('gallery.type_venue'), 'festival' => t('gallery.type_festival'), 'backstage' => t('gallery.type_backstage')];
$type = array_key_exists($_GET['type'] ?? '', $types) ? $_GET['type'] : '';
$albums = get_albums(['type' => $type]);
$photos = get_gallery_images(['type' => $type, 'limit' => 24]);

render_header(['title' => t('gallery.title'), 'description' => t('gallery.meta'), 'body_class' => 'page-gallery', 'scripts' => ['assets/js/gallery.js']]);
echo page_hero(t('gallery.eyebrow'), t('gallery.title'), t('gallery.lead'), [[t('nav.gallery'), null]]);
?>
<section class="section section--tight">
  <div class="container">
    <nav class="filter-pills" aria-label="<?= e(t('gallery.filter')) ?>">
      <a href="<?= e(url('gallery.php')) ?>"<?= $type === '' ? ' aria-current="page"' : '' ?>><?= e(t('gallery.all')) ?></a>
      <?php foreach ($types as $k => $label): ?>
        <a href="<?= e(url('gallery.php', ['type' => $k])) ?>"<?= $type === $k ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <p class="muted photo-note"><?= icon('info') ?> <?= e(t('gallery.photo_note')) ?></p>
    <h2><?= e(t('gallery.albums')) ?></h2>
    <?php if (!$albums): ?>
      <?= empty_state('image', t('gallery.empty'), '', url('gallery.php'), t('gallery.all')) ?>
    <?php else: ?>
    <div class="album-grid">
      <?php foreach ($albums as $al): ?>
        <a class="album" href="<?= e(url('album.php', ['slug' => $al['slug']])) ?>">
          <div class="album__cover"><img src="<?= e(img($al['cover_image'])) ?>" alt="" loading="lazy" width="600" height="400"><span class="album__count"><?= icon('image') ?><?= (int) $al['image_count'] ?></span></div>
          <h3><?= e($al['title']) ?></h3>
          <p><?= e($types[$al['album_type']] ?? '') ?><?= $al['edition_year'] ? ', ' . (int) $al['edition_year'] : '' ?> — <?= e(str_limit($al['description'], 80)) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php if ($photos): ?>
<section class="section section--paper" aria-labelledby="photos-title">
  <div class="container">
    <h2 id="photos-title"><?= e(t('gallery.latest')) ?></h2>
    <p class="muted"><?= e(t('gallery.lightbox_hint')) ?></p>
    <div class="photo-grid" data-lightbox-group>
      <?php foreach ($photos as $p): ?>
        <figure>
          <a href="<?= e(img($p['image'])) ?>" data-lightbox data-caption="<?= e($p['caption'] . ' — ' . $p['album_title']) ?>">
            <img src="<?= e(img($p['image'])) ?>" alt="<?= e($p['caption']) ?>" loading="lazy" width="600" height="400">
          </a>
          <figcaption><?= e($p['caption']) ?> · <a href="<?= e(url('album.php', ['slug' => $p['album_slug']])) ?>"><?= e($p['album_title']) ?></a></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php render_footer(['scripts' => ['assets/js/gallery.js']]);
