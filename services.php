<?php
/** Visitor services offered by the festival. */
require __DIR__ . '/includes/bootstrap.php';

$p = page_text('services');
render_header(['title' => $p['title'], 'description' => $p['meta']]);
echo page_hero($p['eyebrow'], $p['title'], $p['lead'], [[t('nav.about'), url('about.php')], [t('nav.services'), null]]);
?>
<section class="section">
  <div class="container">
    <div class="service-list">
      <?php foreach ($p['items'] as $item): ?>
        <article class="service" id="<?= e($item['id']) ?>">
          <span class="service__icon"><?= icon($item['icon']) ?></span>
          <div><h2><?= e($item['title']) ?></h2></div>
          <div>
            <p><?= e($item['text']) ?></p>
            <?php if (!empty($item['points'])): ?><ul><?php foreach ($item['points'] as $pt): ?><li><?= e($pt) ?></li><?php endforeach; ?></ul><?php endif; ?>
            <a class="link-arrow" href="<?= e(nav_url($item['link'])) ?>"><?= e($item['cta']) ?><?= icon('arrow-right') ?></a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php render_footer();
