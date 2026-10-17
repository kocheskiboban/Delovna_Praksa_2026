<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

$q = mb_substr(input('q', '', 'get'), 0, 60);
if (mb_strlen($q) < 2) {
    json_response(['ok' => true, 'items' => []]);
}
$r = search_all($q, 'all', 4);
$items = [];
foreach ($r['events'] as $ev) {
    $items[] = ['type' => t('search.event'), 'title' => $ev['title'], 'meta' => fmt_date($ev['starts_at']) . ' — ' . $ev['venue_name'], 'url' => url('event.php', ['slug' => $ev['slug']]), 'image' => img($ev['image'])];
}
foreach ($r['artists'] as $a) {
    $items[] = ['type' => t('search.artist'), 'title' => $a['name'], 'meta' => $a['role'], 'url' => url('artist.php', ['slug' => $a['slug']]), 'image' => img($a['image'])];
}
foreach ($r['venues'] as $v) {
    $items[] = ['type' => t('search.venue'), 'title' => $v['name'], 'meta' => t('search.venue'), 'url' => url('venue.php', ['slug' => $v['slug']]), 'image' => img($v['image'])];
}
foreach ($r['news'] as $n) {
    $items[] = ['type' => t('search.article'), 'title' => $n['title'], 'meta' => fmt_date($n['published_at']), 'url' => url('article.php', ['slug' => $n['slug']]), 'image' => img($n['image'])];
}
json_response(['ok' => true, 'items' => array_slice($items, 0, 8), 'all' => ['label' => t('search.see_all', ['q' => $q]), 'url' => url('search.php', ['q' => $q])]]);
