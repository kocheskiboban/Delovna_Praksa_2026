<?php
/** Global search across events, artists, news and venues. */
require __DIR__ . '/includes/bootstrap.php';

$q = mb_substr(input('q', '', 'get'), 0, 80);
$types = ['all' => t('search.all'), 'events' => t('search.events'), 'artists' => t('search.artists'), 'news' => t('search.news'), 'venues' => t('search.venues')];
$type = array_key_exists($_GET['type'] ?? '', $types) ? $_GET['type'] : 'all';
$results = search_all($q, $type, $type === 'all' ? 6 : 30);
$counts = $q !== '' ? search_all($q, 'all', 100) : $results;
$total = array_sum(array_map('count', $counts));
$tooShort = $q !== '' && mb_strlen($q) < 2;

render_header(['title' => $q !== '' ? t('search.results_for', ['q' => $q]) : t('search.title'), 'description' => t('search.meta'), 'noindex' => $q !== '']);
$form = '<form class="search-big" method="get" action="' . e(url('search.php')) . '" role="search" data-autocomplete="' . e(url('api/search-suggest.php')) . '" style="position:relative">'
    . (lang() !== config('app.default_lang') ? '<input type="hidden" name="lang" value="' . e(lang()) . '">' : '')
    . '<label class="visually-hidden" for="search-page-q">' . e(t('search.label')) . '</label>'
    . '<input id="search-page-q" type="search" name="q" value="' . e($q) . '" placeholder="' . e(t('search.placeholder')) . '" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="search-page-list" aria-autocomplete="list">'
    . '<button class="btn btn--gold btn--lg" type="submit">' . icon('search') . e(t('search.submit')) . '</button>'
    . '<ul class="autocomplete" id="search-page-list" role="listbox" hidden></ul></form>';
echo page_hero(t('search.eyebrow'), t('search.title'), t('search.lead'), [[t('nav.search'), null]], null, $form);
?>
<section class="section section--tight">
  <div class="container">
    <?php if ($q === ''): ?>
      <?= empty_state('search', t('search.start_title'), t('search.start_text')) ?>
    <?php elseif ($tooShort): ?>
      <?= alert('warning', e(t('search.too_short'))) ?>
    <?php else: ?>
      <div class="results-bar">
        <p aria-live="polite"><strong><?= e(t('search.found', ['n' => $total, 'q' => $q])) ?></strong></p>
        <nav class="filter-pills" style="margin:0" aria-label="<?= e(t('search.filter')) ?>">
          <?php foreach ($types as $k => $label): $n = $k === 'all' ? $total : count($counts[$k]); ?>
            <a href="<?= e(url('search.php', ['q' => $q, 'type' => $k === 'all' ? null : $k])) ?>"<?= $type === $k ? ' aria-current="page"' : '' ?>><?= e($label) ?> (<?= $n ?>)</a>
          <?php endforeach; ?>
        </nav>
      </div>
      <?php if ($total === 0): ?>
        <?= empty_state('search', t('search.none_title', ['q' => $q]), t('search.none_text'), url('programme.php'), t('nav.programme')) ?>
      <?php endif; ?>

      <?php if ($results['events']): ?>
      <section class="search-section" aria-labelledby="sr-events">
        <h2 id="sr-events"><?= icon('calendar') ?><?= e(t('search.events')) ?> <span class="badge"><?= count($counts['events']) ?></span></h2>
        <ul class="result-list">
          <?php foreach ($results['events'] as $ev): ?>
            <li><img src="<?= e(img($ev['image'])) ?>" alt="" loading="lazy" width="96" height="72"><div>
              <h3><a href="<?= e(url('event.php', ['slug' => $ev['slug']])) ?>"><?= e($ev['title']) ?></a></h3>
              <p><?= e(fmt_date($ev['starts_at'])) ?>, <?= e(fmt_time($ev['starts_at'])) ?> — <?= e($ev['venue_name']) ?> <?= availability_badge($ev) ?></p></div></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($type === 'all' && count($counts['events']) > count($results['events'])): ?><p><a class="link-arrow" href="<?= e(url('search.php', ['q' => $q, 'type' => 'events'])) ?>"><?= e(t('search.more')) ?><?= icon('arrow-right') ?></a></p><?php endif; ?>
      </section>
      <?php endif; ?>

      <?php if ($results['artists']): ?>
      <section class="search-section" aria-labelledby="sr-artists">
        <h2 id="sr-artists"><?= icon('users') ?><?= e(t('search.artists')) ?> <span class="badge"><?= count($counts['artists']) ?></span></h2>
        <ul class="result-list">
          <?php foreach ($results['artists'] as $a): ?>
            <li><img src="<?= e(img($a['image'])) ?>" alt="" loading="lazy" width="96" height="72"><div>
              <h3><a href="<?= e(url('artist.php', ['slug' => $a['slug']])) ?>"><?= e($a['name']) ?></a></h3>
              <p><?= e($a['role']) ?> — <?= e(t('country.' . $a['country_code'])) ?></p></div></li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>

      <?php if ($results['news']): ?>
      <section class="search-section" aria-labelledby="sr-news">
        <h2 id="sr-news"><?= icon('news') ?><?= e(t('search.news')) ?> <span class="badge"><?= count($counts['news']) ?></span></h2>
        <ul class="result-list">
          <?php foreach ($results['news'] as $n): ?>
            <li><img src="<?= e(img($n['image'])) ?>" alt="" loading="lazy" width="96" height="72"><div>
              <h3><a href="<?= e(url('article.php', ['slug' => $n['slug']])) ?>"><?= e($n['title']) ?></a></h3>
              <p><?= e(fmt_date($n['published_at'])) ?> — <?= e(str_limit($n['excerpt'], 120)) ?></p></div></li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>

      <?php if ($results['venues']): ?>
      <section class="search-section" aria-labelledby="sr-venues">
        <h2 id="sr-venues"><?= icon('pin') ?><?= e(t('search.venues')) ?> <span class="badge"><?= count($counts['venues']) ?></span></h2>
        <ul class="result-list">
          <?php foreach ($results['venues'] as $v): ?>
            <li><img src="<?= e(img($v['image'])) ?>" alt="" loading="lazy" width="96" height="72"><div>
              <h3><a href="<?= e(url('venue.php', ['slug' => $v['slug']])) ?>"><?= e($v['name']) ?></a></h3>
              <p><?= e(str_limit($v['short_description'], 120)) ?></p></div></li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
<?php render_footer();
