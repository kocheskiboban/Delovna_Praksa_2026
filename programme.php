<?php
/** Programme: browse, search, filter and sort events. */
require __DIR__ . '/includes/bootstrap.php';

$when = in_array($_GET['when'] ?? '', ['upcoming', 'past', 'all'], true) ? $_GET['when']
    : ((input_int('edition') || input('date_from', '', 'get') !== '') ? 'all' : 'upcoming');
$sorts = ['date_asc' => t('sort.date_asc'), 'date_desc' => t('sort.date_desc'), 'price_asc' => t('sort.price_asc'), 'price_desc' => t('sort.price_desc'), 'title_asc' => t('sort.title_asc')];
$sort = array_key_exists($_GET['sort'] ?? '', $sorts) ? $_GET['sort'] : ($when === 'past' ? 'date_desc' : 'date_asc');
$view = ($_GET['view'] ?? '') === 'list' ? 'list' : 'grid';
$filters = [
    'q'         => mb_substr(input('q', '', 'get'), 0, 80),
    'category'  => input('category', '', 'get'),
    'venue'     => input('venue', '', 'get'),
    'date_from' => input('date_from', '', 'get'),
    'date_to'   => input('date_to', '', 'get'),
    'price_max' => input('price_max', '', 'get'),
    'edition'   => input_int('edition'),
];
if ($filters['edition']) {
    $when = 'all'; // an edition is a closed period: show all of its events
}
$perPage = 12;
$page = current_page();
[$events, $total] = get_events($filters + ['when' => $when, 'sort' => $sort, 'limit' => $perPage, 'offset' => ($page - 1) * $perPage]);
$categories = get_categories();
$venues = get_venues();
$editions = get_editions();
$priceOptions = ['' => t('programme.any_price'), '300' => t('programme.up_to', ['p' => money(300)]), '600' => t('programme.up_to', ['p' => money(600)]),
    '1000' => t('programme.up_to', ['p' => money(1000)]), '1500' => t('programme.up_to', ['p' => money(1500)])];

$active = [];
if ($filters['q'] !== '') { $active['q'] = '“' . $filters['q'] . '”'; }
if ($filters['category'] && ($c = category_by_slug($filters['category']))) { $active['category'] = $c['name']; }
foreach ($venues as $v) { if ($v['slug'] === $filters['venue']) { $active['venue'] = $v['name']; } }
if ($filters['date_from']) { $active['date_from'] = t('programme.from') . ' ' . fmt_date($filters['date_from'], 'short'); }
if ($filters['date_to']) { $active['date_to'] = t('programme.to') . ' ' . fmt_date($filters['date_to'], 'short'); }
if ($filters['price_max'] !== '' && isset($priceOptions[$filters['price_max']])) { $active['price_max'] = $priceOptions[$filters['price_max']]; }
if ($filters['edition']) { $active['edition'] = (string) $filters['edition']; }

$titleKey = $when === 'past' ? 'programme.title_past' : ($when === 'all' ? 'programme.title_all' : 'programme.title');
render_header(['title' => t($titleKey), 'description' => t('programme.meta'), 'body_class' => 'page-programme']);
echo page_hero(t('programme.eyebrow'), t($titleKey), t('programme.lead'), [[t('nav.programme'), null]]);
?>
<section class="section section--tight">
  <div class="container">
    <form class="filters" method="get" action="<?= e(url('programme.php')) ?>" role="search" aria-label="<?= e(t('programme.filters')) ?>">
      <?php if (lang() !== config('app.default_lang')): ?><input type="hidden" name="lang" value="<?= e(lang()) ?>"><?php endif; ?>
      <input type="hidden" name="when" value="<?= e($when) ?>">
      <div class="filters__grid">
        <?= field(['name' => 'q', 'label' => t('programme.search'), 'type' => 'search', 'value' => $filters['q'], 'attrs' => 'placeholder="' . e(t('programme.search_ph')) . '"']) ?>
        <?= field(['name' => 'category', 'label' => t('programme.category'), 'type' => 'select', 'value' => $filters['category'],
            'options' => ['' => t('programme.all_categories')] + array_column($categories, 'name', 'slug')]) ?>
        <?= field(['name' => 'venue', 'label' => t('programme.venue'), 'type' => 'select', 'value' => $filters['venue'],
            'options' => ['' => t('programme.all_venues')] + array_column($venues, 'name', 'slug')]) ?>
        <?= field(['name' => 'date_from', 'label' => t('programme.date_from'), 'type' => 'date', 'value' => $filters['date_from']]) ?>
        <?= field(['name' => 'date_to', 'label' => t('programme.date_to'), 'type' => 'date', 'value' => $filters['date_to']]) ?>
      </div>
      <div class="filters__grid" style="margin-top:var(--s-4)">
        <?= field(['name' => 'price_max', 'label' => t('programme.price'), 'type' => 'select', 'value' => $filters['price_max'], 'options' => $priceOptions]) ?>
        <?= field(['name' => 'edition', 'label' => t('programme.edition'), 'type' => 'select', 'value' => (string) ($filters['edition'] ?: ''),
            'options' => ['' => t('programme.all_editions')] + array_combine(array_column($editions, 'year'), array_map(static fn($e) => $e['year'] . ' — ' . $e['theme'], $editions))]) ?>
        <?= field(['name' => 'sort', 'label' => t('programme.sort'), 'type' => 'select', 'value' => $sort, 'options' => $sorts]) ?>
      </div>
      <div class="filters__actions">
        <nav class="seg" aria-label="<?= e(t('programme.when')) ?>">
          <?php foreach (['upcoming' => 'programme.upcoming', 'past' => 'programme.past', 'all' => 'programme.all'] as $w => $k): ?>
            <a href="<?= e(current_url_with(['when' => $w, 'page' => null, 'sort' => null])) ?>"<?= $when === $w ? ' aria-current="page"' : '' ?>><?= e(t($k)) ?></a>
          <?php endforeach; ?>
        </nav>
        <div class="form-actions" style="margin:0">
          <a class="btn btn--ghost" href="<?= e(url('programme.php', ['when' => $when])) ?>"><?= e(t('programme.reset')) ?></a>
          <button class="btn btn--primary" type="submit"><?= icon('filter') ?><?= e(t('programme.apply')) ?></button>
        </div>
      </div>
    </form>

    <div class="results-bar">
      <div>
        <p aria-live="polite"><strong><?= e(t('programme.results', ['n' => $total])) ?></strong></p>
        <?php if ($active): ?>
        <div class="active-filters" aria-label="<?= e(t('programme.active_filters')) ?>">
          <?php foreach ($active as $key => $label): ?>
            <a href="<?= e(current_url_with([$key => null, 'page' => null])) ?>" aria-label="<?= e(t('programme.remove_filter', ['f' => $label])) ?>"><?= e($label) ?><?= icon('close') ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <nav class="seg" aria-label="<?= e(t('programme.view')) ?>">
        <a href="<?= e(current_url_with(['view' => null])) ?>"<?= $view === 'grid' ? ' aria-current="page"' : '' ?>><?= icon('grid') ?><span class="visually-hidden"><?= e(t('programme.view_grid')) ?></span></a>
        <a href="<?= e(current_url_with(['view' => 'list'])) ?>"<?= $view === 'list' ? ' aria-current="page"' : '' ?>><?= icon('list') ?><span class="visually-hidden"><?= e(t('programme.view_list')) ?></span></a>
      </nav>
    </div>

    <?php if (!$events): ?>
      <?= empty_state('search', t('programme.empty_title'), t('programme.empty_text'), url('programme.php', ['when' => 'all']), t('programme.show_all')) ?>
    <?php elseif ($view === 'list'): ?>
      <div class="event-list"><?php foreach ($events as $ev): ?><?= event_card($ev, 'row') ?><?php endforeach; ?></div>
    <?php else: ?>
      <div class="grid grid--3"><?php foreach ($events as $ev): ?><?= event_card($ev) ?><?php endforeach; ?></div>
    <?php endif; ?>
    <?= pagination($total, $perPage, $page) ?>
  </div>
</section>
<?php render_footer();
