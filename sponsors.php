<?php
/** Sponsors and partners. */
require __DIR__ . '/includes/bootstrap.php';

$p = page_text('sponsors');
$sponsors = get_sponsors();
$partners = get_partners();
$tiers = [];
foreach ($sponsors as $s) {
    $tiers[$s['level']][] = $s;
}
render_header(['title' => $p['title'], 'description' => $p['meta']]);
echo page_hero($p['eyebrow'], $p['title'], $p['lead'], [[t('nav.about'), url('about.php')], [t('nav.sponsors'), null]]);
?>
<section class="section">
  <div class="container">
    <h2><?= e(t('sponsors.sponsors')) ?></h2>
    <?php foreach (['general', 'gold', 'silver', 'media'] as $level): if (empty($tiers[$level])) continue; ?>
      <div class="sponsor-tier">
        <h3 class="eyebrow" style="font-size:var(--step-1)"><?= e(t('sponsors.level_' . $level)) ?></h3>
        <div class="sponsor-list">
          <?php foreach ($tiers[$level] as $s): ?>
            <article class="sponsor" id="sponsor-<?= (int) $s['id'] ?>">
              <img src="<?= e(img($s['logo'])) ?>" alt="<?= e(t('sponsors.logo_alt', ['name' => $s['name']])) ?>" loading="lazy" width="240" height="80">
              <div>
                <h4 style="margin:0 0 .25rem;font-size:var(--step-1)"><?= e($s['name']) ?></h4>
                <p><?= e($s['description']) ?></p>
                <span class="badge"><?= e(t('sponsors.level_' . $s['level'])) ?></span>
                <?php if ($s['website']): ?> <a href="<?= e($s['website']) ?>" target="_blank" rel="noopener noreferrer"><?= e(t('sponsors.website')) ?></a><?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<section class="section section--paper" aria-labelledby="partners-title">
  <div class="container">
    <h2 id="partners-title"><?= e(t('sponsors.partners')) ?></h2>
    <div class="sponsor-list">
      <?php foreach ($partners as $s): ?>
        <article class="sponsor" id="partner-<?= (int) $s['id'] ?>">
          <img src="<?= e(img($s['logo'])) ?>" alt="<?= e(t('sponsors.logo_alt', ['name' => $s['name']])) ?>" loading="lazy" width="240" height="80">
          <div>
            <h3><?= e($s['name']) ?></h3>
            <p><?= e($s['description']) ?></p>
            <span class="badge"><?= e(t('sponsors.type_' . $s['partner_type'])) ?></span>
            <?php if ($s['website']): ?> <a href="<?= e($s['website']) ?>" target="_blank" rel="noopener noreferrer"><?= e(t('sponsors.website')) ?></a><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="muted" style="margin-top:var(--s-6)"><?= e($p['note']) ?></p>
  </div>
</section>
<section class="section section--tight">
  <div class="container">
    <div class="cta-band">
      <div><h2><?= e($p['become_h']) ?></h2><p><?= e($p['become_text']) ?></p></div>
      <a class="btn btn--gold btn--lg" href="<?= e(url('contact.php', ['topic' => 'partnership'])) ?>"><?= icon('handshake') ?><?= e($p['become_cta']) ?></a>
    </div>
  </div>
</section>
<?php render_footer();
