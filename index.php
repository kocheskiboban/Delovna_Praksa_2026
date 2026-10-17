<?php
/** Home page. */
require __DIR__ . '/includes/bootstrap.php';

$edition = get_current_edition();
[$upcoming] = get_events(['when' => 'upcoming', 'limit' => 7]);
[$featuredList] = get_events(['when' => 'upcoming', 'featured' => 1, 'limit' => 1, 'edition' => $edition['year'] ?? null]);
$featured = $featuredList[0] ?? ($upcoming[0] ?? null);
$grid = array_values(array_filter($upcoming, static fn($e) => !$featured || $e['id'] !== $featured['id']));
$grid = array_slice($grid, 0, 6);
[$artists] = get_artists(['featured' => 1, 'limit' => 4]);
$venues = get_venues();
[$news] = get_news(['limit' => 3]);
$photos = get_gallery_images(['limit' => 5]);
$sponsors = get_sponsors();
$partners = get_partners();
$stats = festival_stats();

// opening night = first event of the current edition's festival period
$opening = null;
if ($edition) {
    $opening = db_value("SELECT MIN(starts_at) FROM events WHERE edition_id = ? AND status = 'published' AND starts_at >= ?", [(int) $edition['id'], $edition['start_date']]);
}
$opening = $opening ?: (($edition['start_date'] ?? date('Y-m-d')) . ' 21:00:00');
$openingTs = strtotime($opening);
$diff = max(0, $openingTs - time());

render_header([
    'title' => '',
    'description' => t('home.meta_description'),
    'body_class' => 'page-home',
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'Festival',
        'name' => t('site.name'),
        'alternateName' => 'Ohrid Summer Festival',
        'url' => url('', [], true),
        'foundingDate' => '1961-08-04',
        'startDate' => $edition['start_date'] ?? null,
        'endDate' => $edition['end_date'] ?? null,
        'location' => ['@type' => 'Place', 'name' => 'Ohrid', 'address' => 'Ohrid, North Macedonia'],
        'image' => origin() . img('assets/img/og-image.jpg'),
    ],
]);
?>
<section class="hero" aria-labelledby="hero-title">
  <div class="hero__inner">
    <div>
      <?php if ($edition): ?>
      <p class="hero__edition"><?= e(t('home.edition_line', ['n' => $edition['edition_number'], 'theme' => $edition['theme']])) ?></p>
      <?php endif; ?>
      <h1 class="hero__title" id="hero-title"><?= e(t('site.name')) ?><span><?= e(t('home.hero_sub')) ?></span></h1>
      <p class="hero__lead"><?= e(t('home.hero_lead')) ?></p>
      <div class="hero__actions">
        <a class="btn btn--gold btn--lg" href="<?= e(url('programme.php', ['when' => 'upcoming'])) ?>"><?= icon('ticket') ?><?= e(t('home.cta_programme')) ?></a>
        <a class="btn btn--ghost-light btn--lg" href="<?= e(url('calendar.php')) ?>"><?= icon('calendar') ?><?= e(t('nav.calendar')) ?></a>
      </div>
    </div>
    <?php if ($edition): ?>
    <aside class="hero__panel" aria-labelledby="countdown-title">
      <h2 id="countdown-title"><?= e(t('home.countdown_title')) ?></h2>
      <p class="hero__dates"><?= e(fmt_date($edition['start_date'])) ?> – <?= e(fmt_date($edition['end_date'])) ?></p>
      <dl class="countdown" data-countdown="<?= e(date('c', $openingTs)) ?>" aria-live="off">
        <div><dt><?= e(t('home.days')) ?></dt><dd data-unit="d"><?= intdiv($diff, 86400) ?></dd></div>
        <div><dt><?= e(t('home.hours')) ?></dt><dd data-unit="h"><?= intdiv($diff % 86400, 3600) ?></dd></div>
        <div><dt><?= e(t('home.minutes')) ?></dt><dd data-unit="m"><?= intdiv($diff % 3600, 60) ?></dd></div>
        <div><dt><?= e(t('home.seconds')) ?></dt><dd data-unit="s"><?= $diff % 60 ?></dd></div>
      </dl>
    </aside>
    <?php endif; ?>
  </div>
</section>

<section class="section section--paper" aria-labelledby="intro-title">
  <div class="container intro-band">
    <div>
      <p class="eyebrow"><?= e(t('home.intro_eyebrow')) ?></p>
      <h2 id="intro-title" class="visually-hidden"><?= e(t('home.intro_title')) ?></h2>
      <blockquote><?= e(t('home.intro_quote')) ?></blockquote>
      <p class="lead"><?= e(t('home.intro_text')) ?></p>
      <a class="link-arrow" href="<?= e(url('about.php')) ?>"><?= e(t('home.intro_link')) ?><?= icon('arrow-right') ?></a>
    </div>
    <div class="stats">
      <?= stat_item((string) $stats['since'], t('stats.since')) ?>
      <?= stat_item((string) $stats['editions'], t('stats.editions')) ?>
      <?= stat_item((string) $stats['venues'], t('stats.venues')) ?>
      <?= stat_item((string) $stats['events'], t('stats.upcoming')) ?>
      <?= $stats['visitors'] > 0 ? stat_item(number_format($stats['visitors'], 0, ',', '.'), t('stats.visitors')) : stat_item((string) $stats['last_events'], t('stats.last_events')) ?>
      <?= stat_item((string) $stats['countries'], t('stats.countries')) ?>
    </div>
  </div>
</section>

<section class="section" aria-labelledby="upcoming-title">
  <div class="container">
    <?= section_head(t('home.upcoming_eyebrow'), t('home.upcoming_title'), url('programme.php', ['when' => 'upcoming']), t('home.full_programme'), 'upcoming-title') ?>
    <nav class="cat-strip" aria-label="<?= e(t('programme.by_category')) ?>">
      <?php foreach (get_categories() as $c): ?>
        <a href="<?= e(url('programme.php', ['category' => $c['slug']])) ?>"><?= icon(category_icon($c['slug'])) ?><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php if ($featured): ?>
    <article class="featured-event">
      <a class="featured-event__media" href="<?= e(url('event.php', ['slug' => $featured['slug']])) ?>" tabindex="-1" aria-hidden="true"><img src="<?= e(img($featured['image'])) ?>" alt="" width="1200" height="750"></a>
      <div>
        <p class="eyebrow"><?= e(t('home.featured')) ?></p>
        <h3><a href="<?= e(url('event.php', ['slug' => $featured['slug']])) ?>" style="color:inherit;text-decoration:none"><?= e($featured['title']) ?></a></h3>
        <p class="lead"><?= e($featured['subtitle']) ?></p>
        <dl>
          <dt><?= e(t('event.date')) ?></dt><dd><?= e(fmt_date($featured['starts_at'], 'weekday')) ?>, <?= e(fmt_time($featured['starts_at'])) ?></dd>
          <dt><?= e(t('event.venue')) ?></dt><dd><?= e($featured['venue_name']) ?></dd>
          <dt><?= e(t('event.tickets')) ?></dt><dd><?= e(t('event.from_price', ['price' => money($featured['price_standard'])])) ?> <?= availability_badge($featured) ?></dd>
        </dl>
        <div class="hero__actions" style="margin-top:0">
          <?php if ($featured['can_buy']): ?><a class="btn btn--primary" href="<?= e(url('booking/tickets.php', ['event' => $featured['slug']])) ?>"><?= icon('ticket') ?><?= e(t('event.buy')) ?></a><?php endif; ?>
          <a class="btn btn--ghost" href="<?= e(url('event.php', ['slug' => $featured['slug']])) ?>"><?= e(t('common.details')) ?></a>
        </div>
      </div>
    </article>
    <?php endif; ?>
    <div class="grid grid--3">
      <?php foreach ($grid as $ev): ?><?= event_card($ev) ?><?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--ink" aria-labelledby="artists-title">
  <div class="container">
    <?= section_head(t('home.artists_eyebrow'), t('home.artists_title'), url('artists.php'), t('home.all_artists'), 'artists-title') ?>
    <div class="grid grid--4 artists-dark">
      <?php foreach ($artists as $a): ?><?= artist_card($a) ?><?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" aria-labelledby="venues-title">
  <div class="container">
    <?= section_head(t('home.venues_eyebrow'), t('home.venues_title'), url('map.php'), t('home.open_map'), 'venues-title') ?>
    <div class="venue-strip">
      <?php foreach ($venues as $v): ?>
        <a class="venue-tile" href="<?= e(url('venue.php', ['slug' => $v['slug']])) ?>">
          <img src="<?= e(img($v['image'])) ?>" alt="" loading="lazy" width="600" height="400">
          <span><?= e($v['name']) ?><small><?= e(t('venue.upcoming_count', ['n' => $v['upcoming_count']])) ?></small></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--paper" aria-labelledby="news-title">
  <div class="container">
    <?= section_head(t('home.news_eyebrow'), t('home.news_title'), url('news.php'), t('home.all_news'), 'news-title') ?>
    <div class="grid grid--3">
      <?php foreach ($news as $n): ?><?= news_card($n) ?><?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" aria-labelledby="gallery-title">
  <div class="container">
    <?= section_head(t('home.gallery_eyebrow'), t('home.gallery_title'), url('gallery.php'), t('home.open_gallery'), 'gallery-title') ?>
    <div class="gallery-preview">
      <?php foreach ($photos as $p): ?>
        <a href="<?= e(url('album.php', ['slug' => $p['album_slug']])) ?>"><img src="<?= e(img($p['image'])) ?>" alt="<?= e($p['caption']) ?>" loading="lazy" width="600" height="400"></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--paper" aria-labelledby="sponsors-title">
  <div class="container">
    <?= section_head(t('home.sponsors_eyebrow'), t('home.sponsors_title'), url('sponsors.php'), t('home.all_sponsors'), 'sponsors-title') ?>
    <ul class="logo-wall">
      <?php foreach (array_merge($sponsors, $partners) as $s): ?>
        <li><a href="<?= e(url('sponsors.php')) ?>" title="<?= e($s['name']) ?>"><img src="<?= e(img($s['logo'])) ?>" alt="<?= e($s['name']) ?>" loading="lazy" width="240" height="80"></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="section section--tight">
  <div class="container">
    <div class="cta-band">
      <div>
        <h2><?= e(t('home.cta_title')) ?></h2>
        <p><?= e(t('home.cta_text')) ?></p>
      </div>
      <a class="btn btn--gold btn--lg" href="<?= e(url('tickets.php')) ?>"><?= icon('ticket') ?><?= e(t('home.cta_button')) ?></a>
    </div>
  </div>
</section>
<?php render_footer(['scripts' => []]);
