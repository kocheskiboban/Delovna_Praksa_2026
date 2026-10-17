<?php
/** Human-readable site map (also guarantees crawlability of every page). */
require __DIR__ . '/includes/bootstrap.php';

[$events] = get_events(['when' => 'all', 'sort' => 'date_desc']);
[$artists] = get_artists();
$venues = get_venues();
[$news] = get_news(['limit' => 100]);
$albums = get_albums();
$editions = get_editions();

render_header(['title' => t('sitemap.title'), 'description' => t('sitemap.meta')]);
echo page_hero(t('sitemap.eyebrow'), t('sitemap.title'), t('sitemap.lead'), [[t('footer.sitemap'), null]]);
$sections = [
    t('footer.festival') => [['', 'nav.home'], ['about.php', 'nav.about_festival'], ['history.php', 'nav.history'], ['editions.php', 'nav.editions'], ['team.php', 'nav.team'], ['services.php', 'nav.services'], ['sponsors.php', 'nav.sponsors'], ['gallery.php', 'nav.gallery']],
    t('footer.programme') => [['programme.php', 'nav.programme'], ['programme.php?when=past', 'nav.programme_past'], ['calendar.php', 'nav.calendar'], ['map.php', 'nav.map'], ['artists.php', 'nav.artists'], ['venues.php', 'nav.venues'], ['news.php', 'nav.news'], ['search.php', 'nav.search']],
    t('footer.visitors') => [['tickets.php', 'nav.tickets'], ['faq.php', 'nav.faq'], ['contact.php', 'nav.contact'], ['verify.php', 'nav.verify'], ['account/login.php', 'nav.login'], ['account/register.php', 'nav.register'], ['account/dashboard.php', 'nav.account'], ['account/tickets.php', 'nav.my_tickets'], ['account/favorites.php', 'nav.favorites']],
    t('footer.legal') => [['privacy.php', 'footer.privacy'], ['cookies.php', 'footer.cookies'], ['terms.php', 'footer.terms'], ['sitemap-xml.php', 'sitemap.xml']],
];
?>
<section class="section">
  <div class="container sitemap-cols">
    <?php foreach ($sections as $title => $links): ?>
      <section><h2><?= e($title) ?></h2><ul><?php foreach ($links as [$href, $k]): ?><li><a href="<?= e(nav_url($href)) ?>"><?= e(t($k)) ?></a></li><?php endforeach; ?></ul></section>
    <?php endforeach; ?>
    <section><h2><?= e(t('nav.editions')) ?></h2><ul><?php foreach ($editions as $ed): ?><li><a href="<?= e(url('edition.php', ['year' => $ed['year']])) ?>"><?= (int) $ed['year'] ?> — <?= e($ed['theme']) ?></a></li><?php endforeach; ?></ul></section>
    <section><h2><?= e(t('nav.venues')) ?></h2><ul><?php foreach ($venues as $v): ?><li><a href="<?= e(url('venue.php', ['slug' => $v['slug']])) ?>"><?= e($v['name']) ?></a></li><?php endforeach; ?></ul></section>
    <section><h2><?= e(t('nav.programme')) ?></h2><ul><?php foreach ($events as $ev): ?><li><a href="<?= e(url('event.php', ['slug' => $ev['slug']])) ?>"><?= e($ev['title']) ?></a> <span class="muted">(<?= e(fmt_date($ev['starts_at'], 'short')) ?>)</span></li><?php endforeach; ?></ul></section>
    <section><h2><?= e(t('nav.artists')) ?></h2><ul><?php foreach ($artists as $a): ?><li><a href="<?= e(url('artist.php', ['slug' => $a['slug']])) ?>"><?= e($a['name']) ?></a></li><?php endforeach; ?></ul></section>
    <section><h2><?= e(t('nav.news')) ?></h2><ul><?php foreach ($news as $n): ?><li><a href="<?= e(url('article.php', ['slug' => $n['slug']])) ?>"><?= e($n['title']) ?></a></li><?php endforeach; ?></ul></section>
    <section><h2><?= e(t('nav.gallery')) ?></h2><ul><?php foreach ($albums as $al): ?><li><a href="<?= e(url('album.php', ['slug' => $al['slug']])) ?>"><?= e($al['title']) ?></a></li><?php endforeach; ?></ul></section>
    <section><h2><?= e(t('footer.language')) ?></h2><?= lang_switcher('list') ?></section>
  </div>
</section>
<?php render_footer();
