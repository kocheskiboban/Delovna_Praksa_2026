<?php
require __DIR__ . '/includes/bootstrap.php';

$a = get_artist_by_slug(input('slug', '', 'get'));
if (!$a) {
    not_found();
}
[$upcoming] = get_events(['artist' => (int) $a['id'], 'when' => 'upcoming', 'limit' => 20]);
[$past] = get_events(['artist' => (int) $a['id'], 'when' => 'past', 'sort' => 'date_desc', 'limit' => 20]);
$fallback = content_is_fallback('artist_translations', 'artist_id', (int) $a['id']);
$absUrl = url('artist.php', ['slug' => $a['slug']], true);

render_header([
    'title' => $a['name'],
    'description' => str_limit($a['role'] . '. ' . $a['biography'], 160),
    'image' => $a['image'],
    'og_type' => 'profile',
    'jsonld' => ['@context' => 'https://schema.org', '@type' => $a['is_ensemble'] ? 'PerformingGroup' : 'Person', 'name' => $a['name'],
        'description' => str_limit($a['biography'], 300), 'image' => origin() . img($a['image']), 'url' => $absUrl],
]);
echo page_hero($a['role'], $a['name'], '', [[t('nav.artists'), url('artists.php')], [$a['name'], null]]);
?>
<section class="section">
  <div class="container profile">
    <div>
      <div class="profile__portrait"><img src="<?= e(img($a['image'])) ?>" alt="<?= e(t('artist.portrait_alt', ['name' => $a['name']])) ?>" width="800" height="1000"></div>
      <div class="detail-actions" style="margin:var(--s-4) 0 0">
        <?= favorite_button('artist', (int) $a['id']) ?>
      </div>
    </div>
    <div>
      <?php if ($fallback): ?><p class="notice-fallback"><?= icon('info') ?><?= e(t('content.fallback')) ?></p><?php endif; ?>
      <h2><?= e(t('artist.biography')) ?></h2>
      <div class="prose"><?= rich_text($a['biography']) ?></div>
      <dl class="profile__facts">
        <dt><?= e(t('artist.discipline')) ?></dt><dd><a href="<?= e(url('artists.php', ['category' => $a['category_slug']])) ?>"><?= e($a['category_name']) ?></a></dd>
        <dt><?= e(t('artist.country')) ?></dt><dd><span aria-hidden="true"><?= country_flag($a['country_code']) ?></span> <?= e(t('country.' . $a['country_code'])) ?></dd>
        <dt><?= e(t('artist.type')) ?></dt><dd><?= e(t($a['is_ensemble'] ? 'artists.type_ensemble' : 'artists.type_solo')) ?></dd>
        <dt><?= e(t('artist.appearances')) ?></dt><dd><?= e(t('artist.appearances_n', ['u' => count($upcoming), 'p' => count($past)])) ?></dd>
      </dl>
      <div class="links-row">
        <?php if ($a['website']): ?><a class="btn btn--ghost btn--sm" href="<?= e($a['website']) ?>" target="_blank" rel="noopener noreferrer"><?= icon('external') ?><?= e(t('artist.website')) ?></a><?php endif; ?>
        <?php if ($a['instagram']): ?><a class="btn btn--ghost btn--sm" href="https://www.instagram.com/<?= e(rawurlencode($a['instagram'])) ?>/" target="_blank" rel="noopener noreferrer"><?= icon('instagram') ?>Instagram</a><?php endif; ?>
        <?php if ($a['facebook']): ?><a class="btn btn--ghost btn--sm" href="https://www.facebook.com/<?= e(rawurlencode($a['facebook'])) ?>" target="_blank" rel="noopener noreferrer"><?= icon('facebook') ?>Facebook</a><?php endif; ?>
        <?php if ($a['youtube']): ?><a class="btn btn--ghost btn--sm" href="https://www.youtube.com/@<?= e(rawurlencode($a['youtube'])) ?>" target="_blank" rel="noopener noreferrer"><?= icon('youtube') ?>YouTube</a><?php endif; ?>
      </div>
      <div style="margin-top:var(--s-5)"><?= share_buttons($absUrl, $a['name']) ?></div>
    </div>
  </div>
</section>

<section class="section section--paper" aria-labelledby="up-title">
  <div class="container">
    <?= section_head(t('artist.upcoming_eyebrow'), t('artist.upcoming'), null, null, 'up-title') ?>
    <?php if ($upcoming): ?>
      <div class="event-list"><?php foreach ($upcoming as $ev): ?><?= event_card($ev, 'row') ?><?php endforeach; ?></div>
    <?php else: ?>
      <?= empty_state('calendar', t('artist.no_upcoming'), t('artist.no_upcoming_text'), url('programme.php'), t('nav.programme')) ?>
    <?php endif; ?>
  </div>
</section>

<?php if ($past): ?>
<section class="section" aria-labelledby="past-title">
  <div class="container">
    <?= section_head(t('artist.past_eyebrow'), t('artist.past'), null, null, 'past-title') ?>
    <div class="event-list"><?php foreach ($past as $ev): ?><?= event_card($ev, 'row') ?><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>
<?php render_footer();
