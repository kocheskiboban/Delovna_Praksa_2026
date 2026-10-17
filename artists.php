<?php
require __DIR__ . '/includes/bootstrap.php';

$f = [
    'q'        => mb_substr(input('q', '', 'get'), 0, 80),
    'category' => input('category', '', 'get'),
    'country'  => preg_replace('/[^A-Za-z]/', '', input('country', '', 'get')),
    'type'     => in_array($_GET['type'] ?? '', ['solo', 'ensemble'], true) ? $_GET['type'] : '',
    'sort'     => ($_GET['sort'] ?? '') === 'upcoming' ? 'upcoming' : 'name',
];
$perPage = 12;
$page = current_page();
[$artists, $total] = get_artists($f + ['limit' => $perPage, 'offset' => ($page - 1) * $perPage]);
$countries = get_artist_countries();
$countryOptions = ['' => t('artists.all_countries')];
foreach ($countries as $c) {
    $countryOptions[$c['country_code']] = t('country.' . $c['country_code']) . ' (' . $c['n'] . ')';
}

render_header(['title' => t('artists.title'), 'description' => t('artists.meta'), 'body_class' => 'page-artists']);
echo page_hero(t('artists.eyebrow'), t('artists.title'), t('artists.lead'), [[t('nav.artists'), null]]);
?>
<section class="section section--tight">
  <div class="container">
    <nav class="filter-pills" aria-label="<?= e(t('artists.by_discipline')) ?>">
      <a href="<?= e(current_url_with(['category' => null, 'page' => null])) ?>"<?= $f['category'] === '' ? ' aria-current="page"' : '' ?>><?= e(t('artists.all')) ?></a>
      <?php foreach (get_categories() as $c): ?>
        <a href="<?= e(current_url_with(['category' => $c['slug'], 'page' => null])) ?>"<?= $f['category'] === $c['slug'] ? ' aria-current="page"' : '' ?>><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </nav>
    <form class="filters" method="get" action="<?= e(url('artists.php')) ?>" role="search" aria-label="<?= e(t('artists.filters')) ?>">
      <?php if (lang() !== config('app.default_lang')): ?><input type="hidden" name="lang" value="<?= e(lang()) ?>"><?php endif; ?>
      <?php if ($f['category']): ?><input type="hidden" name="category" value="<?= e($f['category']) ?>"><?php endif; ?>
      <div class="filters__grid" style="grid-template-columns:2fr 1fr 1fr 1fr">
        <?= field(['name' => 'q', 'label' => t('artists.search'), 'type' => 'search', 'value' => $f['q'], 'attrs' => 'placeholder="' . e(t('artists.search_ph')) . '"']) ?>
        <?= field(['name' => 'country', 'label' => t('artists.country'), 'type' => 'select', 'value' => $f['country'], 'options' => $countryOptions]) ?>
        <?= field(['name' => 'type', 'label' => t('artists.type'), 'type' => 'select', 'value' => $f['type'],
            'options' => ['' => t('artists.type_all'), 'solo' => t('artists.type_solo'), 'ensemble' => t('artists.type_ensemble')]]) ?>
        <?= field(['name' => 'sort', 'label' => t('programme.sort'), 'type' => 'select', 'value' => $f['sort'],
            'options' => ['name' => t('artists.sort_name'), 'upcoming' => t('artists.sort_upcoming')]]) ?>
      </div>
      <div class="filters__actions">
        <p class="muted" style="margin:0" aria-live="polite"><strong><?= e(t('artists.results', ['n' => $total])) ?></strong></p>
        <div class="form-actions" style="margin:0">
          <a class="btn btn--ghost" href="<?= e(url('artists.php')) ?>"><?= e(t('programme.reset')) ?></a>
          <button class="btn btn--primary" type="submit"><?= icon('search') ?><?= e(t('programme.apply')) ?></button>
        </div>
      </div>
    </form>
    <?php if (!$artists): ?>
      <?= empty_state('users', t('artists.empty_title'), t('artists.empty_text'), url('artists.php'), t('artists.all')) ?>
    <?php else: ?>
      <div class="grid grid--4"><?php foreach ($artists as $a): ?><?= artist_card($a) ?><?php endforeach; ?></div>
    <?php endif; ?>
    <?= pagination($total, $perPage, $page) ?>
  </div>
</section>
<?php render_footer();
