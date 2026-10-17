<?php

declare(strict_types=1);

function get_categories(): array
{
    static $rows = null;
    if ($rows === null) {
        [$sel, $join] = tr_sql('category_translations', 'category_id', 'c', ['name']);
        $rows = db_all("SELECT c.*, $sel FROM categories c $join ORDER BY c.sort_order");
    }
    return $rows;
}

function category_by_slug(string $slug): ?array
{
    foreach (get_categories() as $c) {
        if ($c['slug'] === $slug) {
            return $c;
        }
    }
    return null;
}

function get_ticket_types(): array
{
    [$sel, $join] = tr_sql('ticket_type_translations', 'ticket_type_id', 'tt', ['name', 'description']);
    return db_all("SELECT tt.*, $sel FROM ticket_types tt $join WHERE tt.is_active = 1 ORDER BY tt.sort_order");
}

function event_base_sql(): array
{
    [$tSel, $tJoin] = tr_sql('event_translations', 'event_id', 'e', ['title', 'subtitle', 'description', 'programme', 'director'], 'et');
    [$vSel, $vJoin] = tr_sql('venue_translations', 'venue_id', 'v', ['name' => 'venue_name'], 'vt');
    [$cSel, $cJoin] = tr_sql('category_translations', 'category_id', 'c', ['name' => 'category_name'], 'ct');
    $select = "e.*, v.slug AS venue_slug, v.seating_type AS venue_seating, v.layout_type, v.capacity AS venue_capacity,
        v.image AS venue_image, c.slug AS category_slug, c.color AS category_color, c.icon AS category_icon,
        fe.year AS edition_year, fe.edition_number,
        (SELECT COUNT(*) FROM tickets tk WHERE tk.event_id = e.id AND tk.status <> 'cancelled') AS sold_count,
        (SELECT COUNT(*) FROM venue_seats s WHERE s.venue_id = e.venue_id AND s.is_active = 1) AS seat_total,
        (SELECT COUNT(*) FROM event_seat_blocks b WHERE b.event_id = e.id) AS blocked_count,
        $tSel, $vSel, $cSel";
    $from = "FROM events e
        JOIN venues v ON v.id = e.venue_id
        JOIN categories c ON c.id = e.category_id
        LEFT JOIN festival_editions fe ON fe.id = e.edition_id
        $tJoin $vJoin $cJoin";
    return [$select, $from];
}


function get_events(array $f = []): array
{
    [$select, $from] = event_base_sql();
    $where = [];
    $p = [];
    if (empty($f['any_status'])) {
        $where[] = "e.status IN ('published','cancelled')";
    }
    if (!empty($f['q'])) {
        $like = '%' . $f['q'] . '%';
        $where[] = "(EXISTS (SELECT 1 FROM event_translations x WHERE x.event_id = e.id AND (x.title LIKE ? OR x.subtitle LIKE ? OR x.description LIKE ? OR x.programme LIKE ?))
            OR EXISTS (SELECT 1 FROM event_artists ea JOIN artist_translations at2 ON at2.artist_id = ea.artist_id WHERE ea.event_id = e.id AND at2.name LIKE ?)
            OR EXISTS (SELECT 1 FROM venue_translations vx WHERE vx.venue_id = e.venue_id AND vx.name LIKE ?))";
        array_push($p, $like, $like, $like, $like, $like, $like);
    }
    if (!empty($f['category'])) {
        $where[] = 'c.slug = ?';
        $p[] = $f['category'];
    }
    if (!empty($f['venue'])) {
        $where[] = 'v.slug = ?';
        $p[] = $f['venue'];
    }
    if (!empty($f['date_from']) && strtotime($f['date_from'])) {
        $where[] = 'e.starts_at >= ?';
        $p[] = date('Y-m-d 00:00:00', strtotime($f['date_from']));
    }
    if (!empty($f['date_to']) && strtotime($f['date_to'])) {
        $where[] = 'e.starts_at <= ?';
        $p[] = date('Y-m-d 23:59:59', strtotime($f['date_to']));
    }
    if (isset($f['price_max']) && $f['price_max'] !== '' && is_numeric($f['price_max'])) {
        $where[] = 'e.price_standard <= ?';
        $p[] = (float) $f['price_max'];
    }
    $when = $f['when'] ?? 'all';
    if ($when === 'upcoming') {
        $where[] = 'e.starts_at >= NOW()';
    } elseif ($when === 'past') {
        $where[] = 'e.starts_at < NOW()';
    }
    if (!empty($f['edition'])) {
        $where[] = 'fe.year = ?';
        $p[] = (int) $f['edition'];
    }
    if (!empty($f['featured'])) {
        $where[] = 'e.is_featured = 1';
    }
    if (!empty($f['artist'])) {
        $where[] = 'EXISTS (SELECT 1 FROM event_artists ea2 WHERE ea2.event_id = e.id AND ea2.artist_id = ?)';
        $p[] = (int) $f['artist'];
    }
    if (!empty($f['venue_id'])) {
        $where[] = 'e.venue_id = ?';
        $p[] = (int) $f['venue_id'];
    }
    if (!empty($f['exclude'])) {
        $where[] = 'e.id <> ?';
        $p[] = (int) $f['exclude'];
    }
    if (!empty($f['ids'])) {
        $ids = array_map('intval', (array) $f['ids']);
        $where[] = 'e.id IN (' . db_placeholders($ids) . ')';
        array_push($p, ...$ids);
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $order = match ($f['sort'] ?? ($when === 'past' ? 'date_desc' : 'date_asc')) {
        'date_desc'  => 'e.starts_at DESC',
        'price_asc'  => 'e.price_standard ASC, e.starts_at ASC',
        'price_desc' => 'e.price_standard DESC, e.starts_at ASC',
        'title_asc'  => 'title ASC',
        default      => 'e.starts_at ASC',
    };
    $limit = max(1, min(500, (int) ($f['limit'] ?? 500)));
    $offset = max(0, (int) ($f['offset'] ?? 0));
    $rows = db_all("SELECT $select $from $whereSql ORDER BY $order LIMIT $limit OFFSET $offset", $p);
    $total = (int) db_value("SELECT COUNT(*) $from $whereSql", $p);
    return [array_map('event_decorate', $rows), $total];
}

function event_decorate(array $e): array
{
    $mode = $e['seating_mode'] ?: $e['venue_seating'];
    $e['mode'] = $mode;
    $capacity = $mode === 'general'
        ? (int) ($e['capacity'] ?: $e['venue_capacity'])
        : max(0, (int) $e['seat_total'] - (int) $e['blocked_count']);
    $e['capacity_total'] = $capacity;
    $e['available'] = max(0, $capacity - (int) $e['sold_count']);
    $e['is_past'] = strtotime($e['starts_at']) < time();
    $e['is_sold_out'] = !$e['is_past'] && $e['available'] <= 0;
    $e['is_cancelled'] = $e['status'] === 'cancelled';
    $e['can_buy'] = !$e['is_past'] && !$e['is_sold_out'] && !$e['is_cancelled'] && $e['sales_status'] === 'open' && $e['status'] === 'published';
    $ratio = $capacity > 0 ? $e['available'] / $capacity : 0;
    $e['availability'] = $e['is_past'] ? 'past' : ($e['is_cancelled'] ? 'cancelled' : ($e['is_sold_out'] ? 'soldout'
        : ($e['sales_status'] !== 'open' ? 'closed' : ($ratio < 0.2 ? 'low' : 'good'))));
    return $e;
}

function get_event_by_slug(string $slug, bool $includeDrafts = false): ?array
{
    [$select, $from] = event_base_sql();
    $row = db_one("SELECT $select $from WHERE e.slug = ?" . ($includeDrafts ? '' : " AND e.status IN ('published','cancelled')"), [$slug]);
    return $row ? event_decorate($row) : null;
}

function get_event_by_id(int $id, bool $includeDrafts = false): ?array
{
    [$select, $from] = event_base_sql();
    $row = db_one("SELECT $select $from WHERE e.id = ?" . ($includeDrafts ? '' : " AND e.status IN ('published','cancelled')"), [$id]);
    return $row ? event_decorate($row) : null;
}

function get_event_artists(int $eventId): array
{
    [$sel, $join] = tr_sql('artist_translations', 'artist_id', 'a', ['name', 'role', 'biography']);
    return db_all("SELECT a.*, ea.role AS event_role, $sel FROM event_artists ea JOIN artists a ON a.id = ea.artist_id $join
        WHERE ea.event_id = ? AND a.is_published = 1 ORDER BY ea.sort_order", [$eventId]);
}

function get_related_events(array $event, int $limit = 3): array
{
    [$rows] = get_events(['category' => $event['category_slug'], 'when' => 'upcoming', 'exclude' => $event['id'], 'limit' => $limit]);
    if (count($rows) < $limit) {
        [$more] = get_events(['when' => 'upcoming', 'exclude' => $event['id'], 'limit' => $limit * 2]);
        foreach ($more as $m) {
            if (count($rows) >= $limit) {
                break;
            }
            if (!in_array($m['id'], array_column($rows, 'id'), true)) {
                $rows[] = $m;
            }
        }
    }
    return $rows;
}

function get_events_by_day(int $year, int $month): array
{
    $from = sprintf('%04d-%02d-01', $year, $month);
    $to = date('Y-m-t', strtotime($from));
    [$rows] = get_events(['date_from' => $from, 'date_to' => $to, 'sort' => 'date_asc']);
    $days = [];
    foreach ($rows as $r) {
        $days[substr($r['starts_at'], 0, 10)][] = $r;
    }
    return $days;
}

function event_month_range(): array
{
    $r = db_one("SELECT MIN(starts_at) AS mn, MAX(starts_at) AS mx FROM events WHERE status IN ('published','cancelled')");
    return [$r['mn'] ?? date('Y-m-d'), $r['mx'] ?? date('Y-m-d')];
}

function artist_base_sql(): array
{
    [$sel, $join] = tr_sql('artist_translations', 'artist_id', 'a', ['name', 'role', 'biography']);
    [$cSel, $cJoin] = tr_sql('category_translations', 'category_id', 'c', ['name' => 'category_name'], 'ct');
    return ["a.*, c.slug AS category_slug, $sel, $cSel,
        (SELECT COUNT(*) FROM event_artists ea JOIN events ev ON ev.id = ea.event_id WHERE ea.artist_id = a.id AND ev.status = 'published' AND ev.starts_at >= NOW()) AS upcoming_count",
        "FROM artists a JOIN categories c ON c.id = a.category_id $join $cJoin"];
}

function get_artists(array $f = []): array
{
    [$select, $from] = artist_base_sql();
    $where = ['a.is_published = 1'];
    $p = [];
    if (!empty($f['q'])) {
        $like = '%' . $f['q'] . '%';
        $where[] = 'EXISTS (SELECT 1 FROM artist_translations x WHERE x.artist_id = a.id AND (x.name LIKE ? OR x.role LIKE ? OR x.biography LIKE ?))';
        array_push($p, $like, $like, $like);
    }
    if (!empty($f['category'])) {
        $where[] = 'c.slug = ?';
        $p[] = $f['category'];
    }
    if (!empty($f['country'])) {
        $where[] = 'a.country_code = ?';
        $p[] = strtoupper($f['country']);
    }
    if (!empty($f['featured'])) {
        $where[] = 'a.is_featured = 1';
    }
    if (!empty($f['type'])) {
        $where[] = 'a.is_ensemble = ?';
        $p[] = $f['type'] === 'ensemble' ? 1 : 0;
    }
    if (!empty($f['ids'])) {
        $ids = array_map('intval', (array) $f['ids']);
        $where[] = 'a.id IN (' . db_placeholders($ids) . ')';
        array_push($p, ...$ids);
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $order = ($f['sort'] ?? '') === 'upcoming' ? 'upcoming_count DESC, name ASC' : 'a.is_featured DESC, name ASC';
    $limit = max(1, min(500, (int) ($f['limit'] ?? 500)));
    $offset = max(0, (int) ($f['offset'] ?? 0));
    $rows = db_all("SELECT $select $from $whereSql ORDER BY $order LIMIT $limit OFFSET $offset", $p);
    $total = (int) db_value("SELECT COUNT(*) $from $whereSql", $p);
    return [$rows, $total];
}

function get_artist_by_slug(string $slug): ?array
{
    [$select, $from] = artist_base_sql();
    return db_one("SELECT $select $from WHERE a.slug = ? AND a.is_published = 1", [$slug]);
}

function get_artist_countries(): array
{
    return db_all('SELECT country_code, COUNT(*) AS n FROM artists WHERE is_published = 1 GROUP BY country_code ORDER BY country_code');
}

function venue_base_sql(): array
{
    [$sel, $join] = tr_sql('venue_translations', 'venue_id', 'v', ['name', 'short_description', 'description', 'accessibility', 'seating_info']);
    return ["v.*, $sel,
        (SELECT COUNT(*) FROM events ev WHERE ev.venue_id = v.id AND ev.status = 'published' AND ev.starts_at >= NOW()) AS upcoming_count",
        "FROM venues v $join"];
}

function get_venues(): array
{
    [$select, $from] = venue_base_sql();
    return db_all("SELECT $select $from WHERE v.is_active = 1 ORDER BY v.sort_order");
}

function get_venue_by_slug(string $slug): ?array
{
    [$select, $from] = venue_base_sql();
    return db_one("SELECT $select $from WHERE v.slug = ? AND v.is_active = 1", [$slug]);
}

function venue_address(array $v): string
{
    return lang() === 'mk' ? $v['address'] : ($v['address_en'] ?: $v['address']);
}

function news_base_sql(): array
{
    [$sel, $join] = tr_sql('news_translations', 'news_id', 'n', ['title', 'excerpt', 'body']);
    [$cSel, $cJoin] = tr_sql('news_category_translations', 'category_id', 'nc', ['name' => 'category_name'], 'nct');
    return ["n.*, nc.slug AS category_slug, $sel, $cSel",
        "FROM news n JOIN news_categories nc ON nc.id = n.category_id $join $cJoin"];
}

function get_news(array $f = []): array
{
    [$select, $from] = news_base_sql();
    $where = ["n.status = 'published'", 'n.published_at <= NOW()'];
    $p = [];
    if (!empty($f['q'])) {
        $like = '%' . $f['q'] . '%';
        $where[] = 'EXISTS (SELECT 1 FROM news_translations x WHERE x.news_id = n.id AND (x.title LIKE ? OR x.excerpt LIKE ? OR x.body LIKE ?))';
        array_push($p, $like, $like, $like);
    }
    if (!empty($f['category'])) {
        $where[] = 'nc.slug = ?';
        $p[] = $f['category'];
    }
    if (!empty($f['exclude'])) {
        $where[] = 'n.id <> ?';
        $p[] = (int) $f['exclude'];
    }
    if (!empty($f['event'])) {
        $where[] = 'n.related_event_id = ?';
        $p[] = (int) $f['event'];
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $limit = max(1, min(100, (int) ($f['limit'] ?? 9)));
    $offset = max(0, (int) ($f['offset'] ?? 0));
    $order = !empty($f['category_first']) ? 'FIELD(nc.slug, ' . db()->quote((string) $f['category_first']) . ') DESC, n.published_at DESC' : 'n.published_at DESC';
    $rows = db_all("SELECT $select $from $whereSql ORDER BY $order LIMIT $limit OFFSET $offset", $p);
    $total = (int) db_value("SELECT COUNT(*) $from $whereSql", $p);
    return [$rows, $total];
}

function get_news_by_slug(string $slug): ?array
{
    [$select, $from] = news_base_sql();
    return db_one("SELECT $select $from WHERE n.slug = ? AND n.status = 'published' AND n.published_at <= NOW()", [$slug]);
}

function get_news_categories(): array
{
    [$sel, $join] = tr_sql('news_category_translations', 'category_id', 'nc', ['name']);
    return db_all("SELECT nc.*, $sel, (SELECT COUNT(*) FROM news n WHERE n.category_id = nc.id AND n.status = 'published') AS n
        FROM news_categories nc $join ORDER BY nc.id");
}

function get_albums(array $f = []): array
{
    [$sel, $join] = tr_sql('gallery_album_translations', 'album_id', 'ga', ['title', 'description']);
    $where = ['ga.is_published = 1'];
    $p = [];
    if (!empty($f['type'])) {
        $where[] = 'ga.album_type = ?';
        $p[] = $f['type'];
    }
    if (!empty($f['venue_id'])) {
        $where[] = 'ga.venue_id = ?';
        $p[] = (int) $f['venue_id'];
    }
    if (!empty($f['event_id'])) {
        $where[] = 'ga.event_id = ?';
        $p[] = (int) $f['event_id'];
    }
    if (!empty($f['edition_id'])) {
        $where[] = 'ga.edition_id = ?';
        $p[] = (int) $f['edition_id'];
    }
    return db_all("SELECT ga.*, $sel, fe.year AS edition_year, (SELECT COUNT(*) FROM gallery_images gi WHERE gi.album_id = ga.id) AS image_count
        FROM gallery_albums ga $join LEFT JOIN festival_editions fe ON fe.id = ga.edition_id
        WHERE " . implode(' AND ', $where) . ' ORDER BY ga.sort_order, ga.id', $p);
}

function get_album_by_slug(string $slug): ?array
{
    [$sel, $join] = tr_sql('gallery_album_translations', 'album_id', 'ga', ['title', 'description']);
    return db_one("SELECT ga.*, $sel FROM gallery_albums ga $join WHERE ga.slug = ? AND ga.is_published = 1", [$slug]);
}

function get_gallery_images(array $f = []): array
{
    [$sel, $join] = tr_sql('gallery_image_translations', 'image_id', 'gi', ['caption']);
    [$aSel, $aJoin] = tr_sql('gallery_album_translations', 'album_id', 'ga', ['title' => 'album_title'], 'gat');
    $where = ['ga.is_published = 1'];
    $p = [];
    if (!empty($f['album_id'])) {
        $where[] = 'gi.album_id = ?';
        $p[] = (int) $f['album_id'];
    }
    if (!empty($f['type'])) {
        $where[] = 'ga.album_type = ?';
        $p[] = $f['type'];
    }
    $limit = max(1, min(500, (int) ($f['limit'] ?? 500)));
    return db_all("SELECT gi.*, ga.slug AS album_slug, ga.album_type, $sel, $aSel
        FROM gallery_images gi JOIN gallery_albums ga ON ga.id = gi.album_id $join $aJoin
        WHERE " . implode(' AND ', $where) . " ORDER BY ga.sort_order, gi.sort_order LIMIT $limit", $p);
}

function get_sponsors(): array
{
    [$sel, $join] = tr_sql('sponsor_translations', 'sponsor_id', 's', ['description']);
    return db_all("SELECT s.*, $sel FROM sponsors s $join WHERE s.is_active = 1
        ORDER BY FIELD(s.level, 'general','gold','silver','media'), s.sort_order");
}

function get_partners(): array
{
    [$sel, $join] = tr_sql('partner_translations', 'partner_id', 'p', ['description']);
    return db_all("SELECT p.*, $sel FROM partners p $join WHERE p.is_active = 1 ORDER BY p.sort_order");
}

function get_team(): array
{
    [$sel, $join] = tr_sql('team_member_translations', 'member_id', 'tm', ['name', 'position', 'bio']);
    return db_all("SELECT tm.*, $sel FROM team_members tm $join WHERE tm.is_active = 1 ORDER BY tm.sort_order");
}

function get_faqs(): array
{
    [$sel, $join] = tr_sql('faq_translations', 'faq_id', 'f', ['question', 'answer']);
    return db_all("SELECT f.*, $sel FROM faqs f $join WHERE f.is_active = 1 ORDER BY f.sort_order");
}

function get_editions(): array
{
    [$sel, $join] = tr_sql('edition_translations', 'edition_id', 'fe', ['theme', 'summary']);
    return db_all("SELECT fe.*, $sel,
        (SELECT COUNT(*) FROM events e WHERE e.edition_id = fe.id AND e.status = 'published') AS event_count,
        (SELECT COUNT(DISTINCT ea.artist_id) FROM event_artists ea JOIN events e ON e.id = ea.event_id WHERE e.edition_id = fe.id) AS artist_count,
        (SELECT COUNT(DISTINCT e.venue_id) FROM events e WHERE e.edition_id = fe.id) AS venue_count,
        (SELECT COUNT(*) FROM tickets t JOIN events e ON e.id = t.event_id WHERE e.edition_id = fe.id AND t.status <> 'cancelled') AS tickets_sold
        FROM festival_editions fe $join ORDER BY fe.year DESC");
}

function get_edition_by_year(int $year): ?array
{
    foreach (get_editions() as $ed) {
        if ((int) $ed['year'] === $year) {
            return $ed;
        }
    }
    return null;
}

function get_current_edition(): ?array
{
    $eds = get_editions();
    $current = null;
    foreach ($eds as $ed) {
        if (strtotime($ed['end_date'] . ' 23:59:59') >= time()) {
            $current = $ed; // editions are DESC, keep the earliest future one
        }
    }
    return $current ?? ($eds[0] ?? null);
}

function search_all(string $q, string $type = 'all', int $limit = 12): array
{
    $out = ['events' => [], 'artists' => [], 'news' => [], 'venues' => []];
    if (mb_strlen($q) < 2) {
        return $out;
    }
    if (in_array($type, ['all', 'events'], true)) {
        [$out['events']] = get_events(['q' => $q, 'limit' => $limit, 'sort' => 'date_desc']);
        // upcoming first
        usort($out['events'], static fn($a, $b) => [$a['is_past'], $a['is_past'] ? -strtotime($a['starts_at']) : strtotime($a['starts_at'])]
            <=> [$b['is_past'], $b['is_past'] ? -strtotime($b['starts_at']) : strtotime($b['starts_at'])]);
    }
    if (in_array($type, ['all', 'artists'], true)) {
        [$out['artists']] = get_artists(['q' => $q, 'limit' => $limit]);
    }
    if (in_array($type, ['all', 'news'], true)) {
        [$out['news']] = get_news(['q' => $q, 'limit' => $limit]);
    }
    if (in_array($type, ['all', 'venues'], true)) {
        $like = '%' . $q . '%';
        [$select, $from] = venue_base_sql();
        $out['venues'] = db_all("SELECT $select $from WHERE v.is_active = 1 AND EXISTS (SELECT 1 FROM venue_translations x
            WHERE x.venue_id = v.id AND (x.name LIKE ? OR x.description LIKE ? OR x.short_description LIKE ?)) ORDER BY v.sort_order LIMIT $limit",
            [$like, $like, $like]);
    }
    return $out;
}

function user_favorite_ids(string $type): array
{
    static $cache = [];
    if (!is_logged_in()) {
        return [];
    }
    if (!isset($cache[$type])) {
        $cache[$type] = array_map('intval', array_column(
            db_all('SELECT entity_id FROM favorites WHERE user_id = ? AND entity_type = ?', [user_id(), $type]), 'entity_id'));
    }
    return $cache[$type];
}

function is_favorite(string $type, int $id): bool
{
    return in_array($id, user_favorite_ids($type), true);
}

function festival_stats(): array
{
    $current = get_current_edition();
    return [
        'since'    => 1961,
        'editions' => $current ? (int) $current['edition_number'] : (int) date('Y') - 1960,
        'venues'   => (int) db_value('SELECT COUNT(*) FROM venues WHERE is_active = 1'),
        'artists'  => (int) db_value('SELECT COUNT(*) FROM artists WHERE is_published = 1'),
        'events'   => (int) db_value("SELECT COUNT(*) FROM events WHERE status = 'published' AND starts_at >= NOW()"),
        'visitors' => (int) db_value('SELECT visitors FROM festival_editions WHERE end_date < CURDATE() ORDER BY year DESC LIMIT 1'),
        'countries'=> (int) db_value('SELECT countries FROM festival_editions WHERE end_date < CURDATE() ORDER BY year DESC LIMIT 1'),
        // events of the last finished edition (shown when no visitor figure is published)
        'last_events' => (int) db_value('SELECT COUNT(*) FROM events e JOIN festival_editions f ON f.id = e.edition_id
            WHERE f.id = (SELECT id FROM festival_editions WHERE end_date < CURDATE() ORDER BY year DESC LIMIT 1)'),
    ];
}
