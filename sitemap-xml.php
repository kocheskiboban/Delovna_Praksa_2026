<?php
/** XML sitemap for search engines (served as /sitemap.xml via .htaccess). Lists every language version. */
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
$paths = [['', 1.0], ['programme.php', 0.9], ['calendar.php', 0.8], ['artists.php', 0.8], ['venues.php', 0.7], ['map.php', 0.6],
    ['news.php', 0.8], ['gallery.php', 0.6], ['about.php', 0.7], ['history.php', 0.6], ['editions.php', 0.6], ['team.php', 0.5],
    ['services.php', 0.5], ['tickets.php', 0.8], ['sponsors.php', 0.5], ['faq.php', 0.6], ['contact.php', 0.7], ['verify.php', 0.3],
    ['privacy.php', 0.2], ['cookies.php', 0.2], ['terms.php', 0.2], ['sitemap.php', 0.2], ['search.php', 0.3]];
[$events] = get_events(['when' => 'all']);
foreach ($events as $ev) { $paths[] = ['event.php?slug=' . $ev['slug'], $ev['is_past'] ? 0.4 : 0.8, $ev['updated_at']]; }
[$artists] = get_artists();
foreach ($artists as $a) { $paths[] = ['artist.php?slug=' . $a['slug'], 0.6]; }
foreach (get_venues() as $v) { $paths[] = ['venue.php?slug=' . $v['slug'], 0.6]; }
[$news] = get_news(['limit' => 100]);
foreach ($news as $n) { $paths[] = ['article.php?slug=' . $n['slug'], 0.6, $n['published_at']]; }
foreach (get_albums() as $al) { $paths[] = ['album.php?slug=' . $al['slug'], 0.4]; }
foreach (get_editions() as $ed) { $paths[] = ['edition.php?year=' . $ed['year'], 0.4]; }

$abs = static function (string $p, ?string $lang): string {
    [$path, $qs] = array_pad(explode('?', $p, 2), 2, '');
    parse_str($qs, $params);
    if ($lang && $lang !== config('app.default_lang')) { $params = ['lang' => $lang] + $params; }
    return origin() . base_path() . '/' . $path . ($params ? '?' . http_build_query($params) : '');
};
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
foreach ($paths as $row) {
    [$p, $prio] = $row;
    foreach (array_keys(LANGUAGES) as $code) {
        echo '<url><loc>' . e($abs($p, $code)) . '</loc>';
        if (!empty($row[2])) { echo '<lastmod>' . date('Y-m-d', strtotime($row[2])) . '</lastmod>'; }
        echo '<priority>' . number_format($code === 'mk' ? $prio : $prio * 0.8, 1) . '</priority>';
        foreach (array_keys(LANGUAGES) as $alt) {
            echo '<xhtml:link rel="alternate" hreflang="' . $alt . '" href="' . e($abs($p, $alt)) . '"/>';
        }
        echo "</url>\n";
    }
}
echo '</urlset>';
