<?php
require __DIR__ . '/includes/bootstrap.php';

$n = get_news_by_slug(input('slug', '', 'get'));
if (!$n) {
    not_found();
}
[$related] = get_news(['category_first' => $n['category_slug'], 'exclude' => (int) $n['id'], 'limit' => 3]);
$event = $n['related_event_id'] ? get_event_by_id((int) $n['related_event_id']) : null;
$relatedEvents = $event ? array_merge([$event], get_related_events($event, 2)) : (get_events(['when' => 'upcoming', 'featured' => 1, 'limit' => 3])[0]);
$absUrl = url('article.php', ['slug' => $n['slug']], true);
$words = str_word_count(strip_tags((string) $n['body'])) ?: (int) (mb_strlen((string) $n['body']) / 6);

render_header([
    'title' => $n['title'],
    'description' => $n['excerpt'],
    'image' => $n['image'],
    'og_type' => 'article',
    'jsonld' => ['@context' => 'https://schema.org', '@type' => 'NewsArticle', 'headline' => $n['title'], 'description' => $n['excerpt'],
        'datePublished' => date('c', strtotime($n['published_at'])), 'image' => origin() . img($n['image']),
        'author' => ['@type' => 'Organization', 'name' => $n['author_name']], 'publisher' => ['@type' => 'Organization', 'name' => t('site.name')]],
]);
?>
<header class="page-hero">
  <div class="container">
    <?= breadcrumbs([[t('nav.news'), url('news.php')], [$n['category_name'], url('news.php', ['category' => $n['category_slug']])], [$n['title'], null]]) ?>
    <p class="eyebrow"><a href="<?= e(url('news.php', ['category' => $n['category_slug']])) ?>" style="color:inherit"><?= e($n['category_name']) ?></a></p>
    <h1><?= e($n['title']) ?></h1>
    <p class="lead"><?= e($n['excerpt']) ?></p>
    <p class="muted" style="color:#a9c0c4;display:flex;flex-wrap:wrap;gap:.4rem 1.2rem">
      <span><?= icon('user') ?> <?= e($n['author_name']) ?></span>
      <span><?= icon('calendar') ?> <time datetime="<?= e(date('c', strtotime($n['published_at']))) ?>"><?= e(fmt_date($n['published_at'])) ?></time></span>
      <span><?= icon('clock') ?> <?= e(t('news.read_time', ['n' => max(1, (int) round($words / 200))])) ?></span>
    </p>
  </div>
</header>
<section class="section">
  <div class="container layout-sidebar">
    <article>
      <figure style="margin:0 0 var(--s-6)">
        <img src="<?= e(img($n['image'])) ?>" alt="<?= e(t('news.image_alt', ['title' => $n['title']])) ?>" width="1200" height="800" style="border-radius:var(--radius-l);width:100%">
      </figure>
      <?php if (content_is_fallback('news_translations', 'news_id', (int) $n['id'])): ?><p class="notice-fallback"><?= icon('info') ?><?= e(t('content.fallback')) ?></p><?php endif; ?>
      <div class="prose"><?= rich_text($n['body']) ?></div>
      <div class="detail-actions"><?= share_buttons($absUrl, $n['title']) ?><a class="link-arrow" href="<?= e(url('news.php')) ?>"><?= icon('arrow-left') ?><?= e(t('news.back')) ?></a></div>
    </article>
    <aside>
      <h2 style="font-size:var(--step-2)"><?= e(t('news.related_events')) ?></h2>
      <div class="grid" style="gap:var(--s-5)">
        <?php foreach (array_slice($relatedEvents, 0, 3) as $ev): ?><?= event_card($ev) ?><?php endforeach; ?>
      </div>
    </aside>
  </div>
</section>
<?php if ($related): ?>
<section class="section section--paper" aria-labelledby="rel-news">
  <div class="container">
    <?= section_head(t('news.related_eyebrow'), t('news.related'), url('news.php'), t('home.all_news'), 'rel-news') ?>
    <div class="grid grid--3"><?php foreach ($related as $r): ?><?= news_card($r) ?><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>
<?php render_footer();
