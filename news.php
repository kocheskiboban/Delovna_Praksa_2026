<?php
/** News listing with categories, search and pagination. */
require __DIR__ . '/includes/bootstrap.php';

$q = mb_substr(input('q', '', 'get'), 0, 80);
$category = input('category', '', 'get');
$perPage = 7;
$page = current_page();
[$items, $total] = get_news(['q' => $q, 'category' => $category, 'limit' => $perPage, 'offset' => ($page - 1) * $perPage]);
$cats = get_news_categories();
$catName = '';
foreach ($cats as $c) {
    if ($c['slug'] === $category) {
        $catName = $c['name'];
    }
}

render_header(['title' => $catName ? $catName . ' — ' . t('news.title') : t('news.title'), 'description' => t('news.meta'), 'body_class' => 'page-news']);
echo page_hero(t('news.eyebrow'), t('news.title'), t('news.lead'), $catName ? [[t('nav.news'), url('news.php')], [$catName, null]] : [[t('nav.news'), null]]);
?>
<section class="section section--tight">
  <div class="container">
    <div class="results-bar">
      <nav class="filter-pills" aria-label="<?= e(t('news.categories')) ?>" style="margin:0">
        <a href="<?= e(url('news.php', ['q' => $q])) ?>"<?= $category === '' ? ' aria-current="page"' : '' ?>><?= e(t('news.all')) ?></a>
        <?php foreach ($cats as $c): ?>
          <a href="<?= e(url('news.php', ['category' => $c['slug'], 'q' => $q])) ?>"<?= $category === $c['slug'] ? ' aria-current="page"' : '' ?>><?= e($c['name']) ?> <span class="muted">(<?= (int) $c['n'] ?>)</span></a>
        <?php endforeach; ?>
      </nav>
      <form method="get" action="<?= e(url('news.php')) ?>" role="search" class="newsletter__row" style="min-width:min(100%,360px)">
        <?php if (lang() !== config('app.default_lang')): ?><input type="hidden" name="lang" value="<?= e(lang()) ?>"><?php endif; ?>
        <?php if ($category): ?><input type="hidden" name="category" value="<?= e($category) ?>"><?php endif; ?>
        <label class="visually-hidden" for="news-q"><?= e(t('news.search')) ?></label>
        <input class="search-input" id="news-q" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('news.search_ph')) ?>">
        <button class="btn btn--primary" type="submit"><?= icon('search') ?><span class="visually-hidden"><?= e(t('search.submit')) ?></span></button>
      </form>
    </div>
    <p class="muted" aria-live="polite"><?= e(t('news.results', ['n' => $total])) ?><?= $q !== '' ? ' — “' . e($q) . '”' : '' ?></p>
    <?php if (!$items): ?>
      <?= empty_state('news', t('news.empty_title'), t('news.empty_text'), url('news.php'), t('news.all')) ?>
    <?php else: ?>
      <div class="grid grid--3">
        <?php foreach ($items as $i => $n): ?><?= news_card($n, $i === 0 && $page === 1 && $q === '') ?><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?= pagination($total, $perPage, $page) ?>
  </div>
</section>
<?php render_footer();
