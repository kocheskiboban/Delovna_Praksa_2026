<?php
/** Festival team. */
require __DIR__ . '/includes/bootstrap.php';

$p = page_text('team');
$team = get_team();
render_header(['title' => $p['title'], 'description' => $p['meta']]);
echo page_hero($p['eyebrow'], $p['title'], $p['lead'], [[t('nav.about'), url('about.php')], [t('nav.team'), null]]);
?>
<section class="section">
  <div class="container">
    <div class="prose" style="margin-bottom:var(--s-6)"><?= rich_text($p['intro']) ?></div>
    <div class="team-grid">
      <?php foreach ($team as $m): ?>
        <article class="team-member">
          <img src="<?= e(img($m['image'])) ?>" alt="<?= e(t('team.photo_alt', ['name' => $m['name']])) ?>" loading="lazy" width="400" height="500">
          <h2 style="font-size:var(--step-1);margin:0"><?= e($m['name']) ?></h2>
          <p class="role"><?= e($m['position']) ?></p>
          <p><?= e($m['bio']) ?></p>
          <p><span class="badge"><?= e(t('team.dept_' . $m['department'])) ?></span></p>
          <?php if ($m['email']): ?><a class="link-arrow" href="mailto:<?= e($m['email']) ?>"><?= icon('mail') ?><?= e(t('team.write')) ?></a><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="muted" style="margin-top:var(--s-6)"><?= e($p['note']) ?></p>
  </div>
</section>
<section class="section section--tight">
  <div class="container">
    <div class="cta-band">
      <div><h2><?= e($p['join_h']) ?></h2><p><?= e($p['join_text']) ?></p></div>
      <a class="btn btn--gold btn--lg" href="<?= e(url('contact.php', ['topic' => 'volunteering'])) ?>"><?= icon('handshake') ?><?= e($p['join_cta']) ?></a>
    </div>
  </div>
</section>
<?php render_footer();
