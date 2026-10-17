<?php

declare(strict_types=1);

function admin_start(string $active, string $title): void
{
    require_admin();
    $u = current_user();
    $newMsgs = (int) db_value("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
    $openOrders = (int) db_value("SELECT COUNT(*) FROM orders WHERE status IN ('reserved','pending')");
    render_header(['title' => $title . ' — ' . t('admin.title'), 'noindex' => true, 'body_class' => 'page-admin', 'styles' => ['assets/css/admin.css']]);
    $nav = [
        [t('admin.overview'), [['dashboard', 'admin/index.php', 'chart', 'admin.dashboard', 0]]],
        [t('admin.content'), [
            ['events', 'admin/manage.php?e=events', 'calendar', 'admin.events', 0],
            ['artists', 'admin/manage.php?e=artists', 'users', 'admin.artists', 0],
            ['venues', 'admin/manage.php?e=venues', 'pin', 'admin.venues', 0],
            ['news', 'admin/manage.php?e=news', 'news', 'admin.news', 0],
            ['albums', 'admin/manage.php?e=albums', 'image', 'admin.albums', 0],
            ['images', 'admin/manage.php?e=images', 'image', 'admin.images', 0],
            ['editions', 'admin/manage.php?e=editions', 'archive', 'admin.editions', 0],
            ['categories', 'admin/manage.php?e=categories', 'grid', 'admin.categories', 0],
        ]],
        [t('admin.organisation'), [
            ['team', 'admin/manage.php?e=team', 'users', 'admin.team', 0],
            ['sponsors', 'admin/manage.php?e=sponsors', 'award', 'admin.sponsors', 0],
            ['partners', 'admin/manage.php?e=partners', 'handshake', 'admin.partners', 0],
            ['faqs', 'admin/manage.php?e=faqs', 'question', 'admin.faqs', 0],
        ]],
        [t('admin.sales'), [
            ['orders', 'admin/orders.php', 'ticket', 'admin.orders', $openOrders],
            ['ticket_types', 'admin/manage.php?e=ticket_types', 'card', 'admin.ticket_types', 0],
        ]],
        [t('admin.people'), [
            ['users', 'admin/users.php', 'user', 'admin.users', 0],
            ['newsletter', 'admin/newsletter.php', 'mail', 'admin.newsletter', 0],
            ['messages', 'admin/messages.php', 'mail', 'admin.messages', $newMsgs],
        ]],
    ];
    echo '<div class="admin"><aside class="admin__side" aria-label="' . e(t('admin.menu')) . '"><p class="admin__brand">' . icon('settings') . e(t('admin.title')) . '</p>';
    foreach ($nav as [$group, $items]) {
        echo '<p class="admin__group">' . e($group) . '</p><ul>';
        foreach ($items as [$key, $href, $ic, $label, $badge]) {
            echo '<li><a href="' . e(nav_url($href)) . '"' . ($key === $active ? ' aria-current="page"' : '') . '>' . icon($ic) . '<span>' . e(t($label)) . '</span>'
                . ($badge ? '<span class="admin__badge">' . $badge . '</span>' : '') . '</a></li>';
        }
        echo '</ul>';
    }
    echo '<p class="admin__group">' . e(t('admin.site')) . '</p><ul><li><a href="' . e(url('')) . '">' . icon('home') . '<span>' . e(t('admin.view_site')) . '</span></a></li>'
        . '<li><a href="' . e(url('account/dashboard.php')) . '">' . icon('user') . '<span>' . e($u['first_name']) . '</span></a></li></ul></aside>';
    echo '<div class="admin__main"><div class="admin__head"><h1>' . e($title) . '</h1></div>';
}

function admin_end(array $meta = []): void
{
    echo '</div></div>';
    render_footer($meta);
}

function admin_options(string $what): array
{
    static $cache = [];
    if (isset($cache[$what])) {
        return $cache[$what];
    }
    switch ($what) {
        case 'categories':
            $o = array_column(get_categories(), 'name', 'id');
            break;
        case 'venues':
            $o = array_column(get_venues(), 'name', 'id');
            break;
        case 'editions':
            $o = ['' => '—'];
            foreach (get_editions() as $ed) {
                $o[$ed['id']] = $ed['year'] . ' — ' . $ed['theme'];
            }
            break;
        case 'news_categories':
            $o = array_column(get_news_categories(), 'name', 'id');
            break;
        case 'events':
            $o = ['' => '—'];
            [$evs] = get_events(['when' => 'all', 'sort' => 'date_desc', 'any_status' => 1]);
            foreach ($evs as $ev) {
                $o[$ev['id']] = date('Y-m-d', strtotime($ev['starts_at'])) . ' — ' . $ev['title'];
            }
            break;
        case 'albums':
            [$sel, $join] = tr_sql('gallery_album_translations', 'album_id', 'ga', ['title']);
            $o = array_column(db_all("SELECT ga.id, $sel FROM gallery_albums ga $join ORDER BY ga.sort_order"), 'title', 'id');
            break;
        case 'venues_opt':
            $o = ['' => '—'] + array_column(get_venues(), 'name', 'id');
            break;
        case 'artists':
            [$rows] = get_artists();
            $o = array_column($rows, 'name', 'id');
            break;
        default:
            $o = [];
    }
    return $cache[$what] = $o;
}


function admin_entities(): array
{
    $statusOpts = ['published' => t('status.published'), 'draft' => t('status.draft'), 'cancelled' => t('status.cancelled')];
    return [
        'events' => [
            'table' => 'events', 'trans' => 'event_translations', 'fk' => 'event_id', 'title' => 'title', 'label' => 'admin.events', 'icon' => 'calendar',
            'order' => 'x.starts_at DESC', 'public' => 'event.php', 'image_dir' => 'events',
            'list' => [['image', 'image'], ['title', 'title'], ['starts_at', 'datetime'], ['venue_id', 'venues'], ['category_id', 'categories'], ['price_standard', 'money'], ['status', 'status'], ['sales_status', 'status']],
            'filters' => ['status' => $statusOpts, 'when' => ['upcoming' => t('programme.upcoming'), 'past' => t('programme.past')]],
            'fields' => [
                ['name' => 'title', 'label' => 'admin.f_title', 'type' => 'text', 'trans' => true, 'required' => true],
                ['name' => 'subtitle', 'label' => 'admin.f_subtitle', 'type' => 'text', 'trans' => true],
                ['name' => 'description', 'label' => 'admin.f_description', 'type' => 'textarea', 'trans' => true, 'help' => 'admin.h_richtext'],
                ['name' => 'programme', 'label' => 'admin.f_programme', 'type' => 'textarea', 'trans' => true, 'help' => 'admin.h_lines'],
                ['name' => 'director', 'label' => 'admin.f_director', 'type' => 'text', 'trans' => true],
                ['name' => 'slug', 'label' => 'admin.f_slug', 'type' => 'slug', 'required' => true],
                ['name' => 'starts_at', 'label' => 'admin.f_starts_at', 'type' => 'datetime', 'required' => true],
                ['name' => 'duration_min', 'label' => 'admin.f_duration', 'type' => 'number', 'required' => true, 'min' => 10, 'max' => 600],
                ['name' => 'venue_id', 'label' => 'admin.f_venue', 'type' => 'select', 'options' => 'venues', 'required' => true],
                ['name' => 'category_id', 'label' => 'admin.f_category', 'type' => 'select', 'options' => 'categories', 'required' => true],
                ['name' => 'edition_id', 'label' => 'admin.f_edition', 'type' => 'select', 'options' => 'editions'],
                ['name' => 'price_standard', 'label' => 'admin.f_price', 'type' => 'decimal', 'required' => true, 'min' => 0],
                ['name' => 'price_vip', 'label' => 'admin.f_price_vip', 'type' => 'decimal', 'min' => 0, 'help' => 'admin.h_vip'],
                ['name' => 'seating_mode', 'label' => 'admin.f_seating', 'type' => 'select', 'options' => ['' => t('admin.seating_venue'), 'seated' => t('admin.seating_seated'), 'general' => t('admin.seating_general')]],
                ['name' => 'capacity', 'label' => 'admin.f_capacity', 'type' => 'number', 'min' => 1, 'help' => 'admin.h_capacity'],
                ['name' => 'country', 'label' => 'admin.f_country', 'type' => 'text'],
                ['name' => 'image', 'label' => 'admin.f_image', 'type' => 'image'],
                ['name' => 'status', 'label' => 'admin.f_status', 'type' => 'select', 'options' => $statusOpts, 'required' => true],
                ['name' => 'sales_status', 'label' => 'admin.f_sales', 'type' => 'select', 'options' => ['open' => t('status.open'), 'closed' => t('status.closed')], 'required' => true],
                ['name' => 'is_featured', 'label' => 'admin.f_featured', 'type' => 'checkbox'],
                ['name' => 'performers', 'label' => 'admin.f_performers', 'type' => 'performers'],
            ],
        ],
        'artists' => [
            'table' => 'artists', 'trans' => 'artist_translations', 'fk' => 'artist_id', 'title' => 'name', 'label' => 'admin.artists', 'icon' => 'users',
            'order' => 'name ASC', 'public' => 'artist.php', 'image_dir' => 'artists',
            'list' => [['image', 'image'], ['name', 'title'], ['role', 'text'], ['country_code', 'text'], ['category_id', 'categories'], ['is_featured', 'bool'], ['is_published', 'bool']],
            'fields' => [
                ['name' => 'name', 'label' => 'admin.f_name', 'type' => 'text', 'trans' => true, 'required' => true],
                ['name' => 'role', 'label' => 'admin.f_role', 'type' => 'text', 'trans' => true],
                ['name' => 'biography', 'label' => 'admin.f_biography', 'type' => 'textarea', 'trans' => true, 'help' => 'admin.h_richtext'],
                ['name' => 'slug', 'label' => 'admin.f_slug', 'type' => 'slug', 'required' => true],
                ['name' => 'category_id', 'label' => 'admin.f_discipline', 'type' => 'select', 'options' => 'categories', 'required' => true],
                ['name' => 'country_code', 'label' => 'admin.f_country_code', 'type' => 'text', 'required' => true, 'pattern' => '[A-Za-z]{2}', 'help' => 'admin.h_country'],
                ['name' => 'is_ensemble', 'label' => 'admin.f_ensemble', 'type' => 'checkbox'],
                ['name' => 'website', 'label' => 'admin.f_website', 'type' => 'url'],
                ['name' => 'instagram', 'label' => 'admin.f_instagram', 'type' => 'text'],
                ['name' => 'facebook', 'label' => 'admin.f_facebook', 'type' => 'text'],
                ['name' => 'youtube', 'label' => 'admin.f_youtube', 'type' => 'text'],
                ['name' => 'image', 'label' => 'admin.f_photo', 'type' => 'image'],
                ['name' => 'is_featured', 'label' => 'admin.f_featured', 'type' => 'checkbox'],
                ['name' => 'is_published', 'label' => 'admin.f_published', 'type' => 'checkbox', 'default' => 1],
            ],
        ],
        'venues' => [
            'table' => 'venues', 'trans' => 'venue_translations', 'fk' => 'venue_id', 'title' => 'name', 'label' => 'admin.venues', 'icon' => 'pin',
            'order' => 'x.sort_order', 'public' => 'venue.php', 'image_dir' => 'venues',
            'list' => [['image', 'image'], ['name', 'title'], ['seating_type', 'text'], ['capacity', 'text'], ['is_active', 'bool']],
            'fields' => [
                ['name' => 'name', 'label' => 'admin.f_name', 'type' => 'text', 'trans' => true, 'required' => true],
                ['name' => 'short_description', 'label' => 'admin.f_short', 'type' => 'textarea', 'trans' => true, 'rows' => 2],
                ['name' => 'description', 'label' => 'admin.f_description', 'type' => 'textarea', 'trans' => true],
                ['name' => 'accessibility', 'label' => 'admin.f_accessibility', 'type' => 'textarea', 'trans' => true, 'rows' => 3],
                ['name' => 'seating_info', 'label' => 'admin.f_seating_info', 'type' => 'textarea', 'trans' => true, 'rows' => 3],
                ['name' => 'slug', 'label' => 'admin.f_slug', 'type' => 'slug', 'required' => true],
                ['name' => 'address', 'label' => 'admin.f_address', 'type' => 'text', 'required' => true],
                ['name' => 'address_en', 'label' => 'admin.f_address_en', 'type' => 'text'],
                ['name' => 'seating_type', 'label' => 'admin.f_seating', 'type' => 'select', 'options' => ['seated' => t('admin.seating_seated'), 'general' => t('admin.seating_general')], 'required' => true],
                ['name' => 'layout_type', 'label' => 'admin.f_layout', 'type' => 'select', 'options' => ['amphitheatre' => 'Amphitheatre', 'nave' => 'Nave', 'courtyard' => 'Courtyard', 'terrace' => 'Terrace', 'hall' => 'Hall', 'open' => 'Open air'], 'required' => true],
                ['name' => 'capacity', 'label' => 'admin.f_capacity', 'type' => 'number', 'min' => 0, 'help' => 'admin.h_venue_capacity'],
                ['name' => 'latitude', 'label' => 'admin.f_lat', 'type' => 'decimal'],
                ['name' => 'longitude', 'label' => 'admin.f_lng', 'type' => 'decimal'],
                ['name' => 'map_x', 'label' => 'admin.f_map_x', 'type' => 'decimal', 'min' => 0, 'max' => 100],
                ['name' => 'map_y', 'label' => 'admin.f_map_y', 'type' => 'decimal', 'min' => 0, 'max' => 100],
                ['name' => 'image', 'label' => 'admin.f_image', 'type' => 'image'],
                ['name' => 'sort_order', 'label' => 'admin.f_sort', 'type' => 'number'],
                ['name' => 'is_active', 'label' => 'admin.f_active', 'type' => 'checkbox', 'default' => 1],
            ],
        ],
        'news' => [
            'table' => 'news', 'trans' => 'news_translations', 'fk' => 'news_id', 'title' => 'title', 'label' => 'admin.news', 'icon' => 'news',
            'order' => 'x.published_at DESC', 'public' => 'article.php', 'image_dir' => 'news',
            'list' => [['image', 'image'], ['title', 'title'], ['published_at', 'datetime'], ['category_id', 'news_categories'], ['author_name', 'text'], ['status', 'status']],
            'fields' => [
                ['name' => 'title', 'label' => 'admin.f_title', 'type' => 'text', 'trans' => true, 'required' => true],
                ['name' => 'excerpt', 'label' => 'admin.f_excerpt', 'type' => 'textarea', 'trans' => true, 'rows' => 2],
                ['name' => 'body', 'label' => 'admin.f_body', 'type' => 'textarea', 'trans' => true, 'rows' => 12, 'help' => 'admin.h_richtext'],
                ['name' => 'slug', 'label' => 'admin.f_slug', 'type' => 'slug', 'required' => true],
                ['name' => 'category_id', 'label' => 'admin.f_category', 'type' => 'select', 'options' => 'news_categories', 'required' => true],
                ['name' => 'related_event_id', 'label' => 'admin.f_related_event', 'type' => 'select', 'options' => 'events'],
                ['name' => 'author_name', 'label' => 'admin.f_author', 'type' => 'text', 'required' => true],
                ['name' => 'published_at', 'label' => 'admin.f_published_at', 'type' => 'datetime', 'required' => true],
                ['name' => 'image', 'label' => 'admin.f_image', 'type' => 'image'],
                ['name' => 'status', 'label' => 'admin.f_status', 'type' => 'select', 'options' => ['published' => t('status.published'), 'draft' => t('status.draft')], 'required' => true],
                ['name' => 'is_featured', 'label' => 'admin.f_featured', 'type' => 'checkbox'],
            ],
        ],
        'albums' => [
            'table' => 'gallery_albums', 'trans' => 'gallery_album_translations', 'fk' => 'album_id', 'title' => 'title', 'label' => 'admin.albums', 'icon' => 'image',
            'order' => 'x.sort_order', 'public' => 'album.php', 'image_dir' => 'gallery',
            'list' => [['cover_image', 'image'], ['title', 'title'], ['album_type', 'text'], ['sort_order', 'text'], ['is_published', 'bool']],
            'fields' => [
                ['name' => 'title', 'label' => 'admin.f_title', 'type' => 'text', 'trans' => true, 'required' => true],
                ['name' => 'description', 'label' => 'admin.f_description', 'type' => 'textarea', 'trans' => true, 'rows' => 3],
                ['name' => 'slug', 'label' => 'admin.f_slug', 'type' => 'slug', 'required' => true],
                ['name' => 'album_type', 'label' => 'admin.f_album_type', 'type' => 'select', 'options' => ['event' => t('gallery.type_event'), 'venue' => t('gallery.type_venue'), 'festival' => t('gallery.type_festival'), 'backstage' => t('gallery.type_backstage')], 'required' => true],
                ['name' => 'edition_id', 'label' => 'admin.f_edition', 'type' => 'select', 'options' => 'editions'],
                ['name' => 'event_id', 'label' => 'admin.f_related_event', 'type' => 'select', 'options' => 'events'],
                ['name' => 'venue_id', 'label' => 'admin.f_venue', 'type' => 'select', 'options' => 'venues_opt'],
                ['name' => 'cover_image', 'label' => 'admin.f_cover', 'type' => 'image'],
                ['name' => 'sort_order', 'label' => 'admin.f_sort', 'type' => 'number'],
                ['name' => 'is_published', 'label' => 'admin.f_published', 'type' => 'checkbox', 'default' => 1],
            ],
        ],
        'images' => [
            'table' => 'gallery_images', 'trans' => 'gallery_image_translations', 'fk' => 'image_id', 'title' => 'caption', 'label' => 'admin.images', 'icon' => 'image',
            'order' => 'x.album_id, x.sort_order', 'image_dir' => 'gallery',
            'list' => [['image', 'image'], ['caption', 'title'], ['album_id', 'albums'], ['sort_order', 'text']],
            'filters' => ['album_id' => 'albums'],
            'fields' => [
                ['name' => 'caption', 'label' => 'admin.f_caption', 'type' => 'text', 'trans' => true, 'required' => true],
                ['name' => 'album_id', 'label' => 'admin.f_album', 'type' => 'select', 'options' => 'albums', 'required' => true],
                ['name' => 'image', 'label' => 'admin.f_image', 'type' => 'image', 'required' => true],
                ['name' => 'sort_order', 'label' => 'admin.f_sort', 'type' => 'number'],
            ],
        ],
        'editions' => [
            'table' => 'festival_editions', 'trans' => 'edition_translations', 'fk' => 'edition_id', 'title' => 'theme', 'label' => 'admin.editions', 'icon' => 'archive',
            'order' => 'x.year DESC', 'public_param' => ['edition.php', 'year'],
            'list' => [['year', 'text'], ['theme', 'title'], ['edition_number', 'text'], ['start_date', 'date'], ['end_date', 'date'], ['visitors', 'text']],
            'fields' => [
                ['name' => 'theme', 'label' => 'admin.f_theme', 'type' => 'text', 'trans' => true, 'required' => true],
                ['name' => 'summary', 'label' => 'admin.f_summary', 'type' => 'textarea', 'trans' => true, 'rows' => 3],
                ['name' => 'year', 'label' => 'admin.f_year', 'type' => 'number', 'required' => true, 'min' => 1961, 'max' => 2100],
                ['name' => 'edition_number', 'label' => 'admin.f_edition_number', 'type' => 'number', 'required' => true, 'min' => 1],
                ['name' => 'start_date', 'label' => 'admin.f_start_date', 'type' => 'date', 'required' => true],
                ['name' => 'end_date', 'label' => 'admin.f_end_date', 'type' => 'date', 'required' => true],
                ['name' => 'visitors', 'label' => 'admin.f_visitors', 'type' => 'number', 'min' => 0],
                ['name' => 'countries', 'label' => 'admin.f_countries', 'type' => 'number', 'min' => 0],
            ],
        ],
        'categories' => [
            'table' => 'categories', 'trans' => 'category_translations', 'fk' => 'category_id', 'title' => 'name', 'label' => 'admin.categories', 'icon' => 'grid',
            'order' => 'x.sort_order',
            'list' => [['name', 'title'], ['slug', 'text'], ['color', 'color'], ['sort_order', 'text']],
            'fields' => [
                ['name' => 'name', 'label' => 'admin.f_name', 'type' => 'text', 'trans' => true, 'required' => true],
                ['name' => 'slug', 'label' => 'admin.f_slug', 'type' => 'slug', 'required' => true],
                ['name' => 'icon', 'label' => 'admin.f_icon', 'type' => 'select', 'options' => array_combine(['music', 'mask', 'ballet', 'opera', 'book', 'dance', 'film', 'spark'], ['music', 'mask', 'ballet', 'opera', 'book', 'dance', 'film', 'spark']), 'required' => true],
                ['name' => 'color', 'label' => 'admin.f_color', 'type' => 'color', 'required' => true],
                ['name' => 'sort_order', 'label' => 'admin.f_sort', 'type' => 'number'],
            ],
        ],
        'team' => [
            'table' => 'team_members', 'trans' => 'team_member_translations', 'fk' => 'member_id', 'title' => 'name', 'label' => 'admin.team', 'icon' => 'users',
            'order' => 'x.sort_order', 'image_dir' => 'team',
            'list' => [['image', 'image'], ['name', 'title'], ['position', 'text'], ['department', 'text'], ['is_active', 'bool']],
            'fields' => [
                ['name' => 'name', 'label' => 'admin.f_name', 'type' => 'text', 'trans' => true, 'required' => true],
                ['name' => 'position', 'label' => 'admin.f_position', 'type' => 'text', 'trans' => true, 'required' => true],
                ['name' => 'bio', 'label' => 'admin.f_bio', 'type' => 'textarea', 'trans' => true, 'rows' => 3],
                ['name' => 'slug', 'label' => 'admin.f_slug', 'type' => 'slug', 'required' => true],
                ['name' => 'department', 'label' => 'admin.f_department', 'type' => 'select', 'required' => true,
                    'options' => array_combine(['management', 'programme', 'production', 'communication', 'visitors', 'international'], array_map(static fn($d) => t('team.dept_' . $d), ['management', 'programme', 'production', 'communication', 'visitors', 'international']))],
                ['name' => 'email', 'label' => 'admin.f_email', 'type' => 'email'],
                ['name' => 'image', 'label' => 'admin.f_photo', 'type' => 'image'],
                ['name' => 'sort_order', 'label' => 'admin.f_sort', 'type' => 'number'],
                ['name' => 'is_active', 'label' => 'admin.f_active', 'type' => 'checkbox', 'default' => 1],
            ],
        ],
        'sponsors' => [
            'table' => 'sponsors', 'trans' => 'sponsor_translations', 'fk' => 'sponsor_id', 'title' => 'name', 'label' => 'admin.sponsors', 'icon' => 'award',
            'order' => 'x.sort_order', 'image_dir' => 'logos',
            'list' => [['logo', 'image'], ['name', 'base'], ['level', 'text'], ['website', 'text'], ['is_active', 'bool']],
            'fields' => [
                ['name' => 'name', 'label' => 'admin.f_name', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'admin.f_description', 'type' => 'textarea', 'trans' => true, 'rows' => 3],
                ['name' => 'level', 'label' => 'admin.f_level', 'type' => 'select', 'required' => true,
                    'options' => ['general' => t('sponsors.level_general'), 'gold' => t('sponsors.level_gold'), 'silver' => t('sponsors.level_silver'), 'media' => t('sponsors.level_media')]],
                ['name' => 'website', 'label' => 'admin.f_website', 'type' => 'url'],
                ['name' => 'logo', 'label' => 'admin.f_logo', 'type' => 'image'],
                ['name' => 'sort_order', 'label' => 'admin.f_sort', 'type' => 'number'],
                ['name' => 'is_active', 'label' => 'admin.f_active', 'type' => 'checkbox', 'default' => 1],
            ],
        ],
        'partners' => [
            'table' => 'partners', 'trans' => 'partner_translations', 'fk' => 'partner_id', 'title' => 'name', 'label' => 'admin.partners', 'icon' => 'handshake',
            'order' => 'x.sort_order', 'image_dir' => 'logos',
            'list' => [['logo', 'image'], ['name', 'base'], ['partner_type', 'text'], ['is_active', 'bool']],
            'fields' => [
                ['name' => 'name', 'label' => 'admin.f_name', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'admin.f_description', 'type' => 'textarea', 'trans' => true, 'rows' => 3],
                ['name' => 'partner_type', 'label' => 'admin.f_type', 'type' => 'select', 'required' => true,
                    'options' => array_combine(['institutional', 'network', 'cultural', 'hospitality', 'media'], array_map(static fn($x) => t('sponsors.type_' . $x), ['institutional', 'network', 'cultural', 'hospitality', 'media']))],
                ['name' => 'website', 'label' => 'admin.f_website', 'type' => 'url'],
                ['name' => 'logo', 'label' => 'admin.f_logo', 'type' => 'image'],
                ['name' => 'sort_order', 'label' => 'admin.f_sort', 'type' => 'number'],
                ['name' => 'is_active', 'label' => 'admin.f_active', 'type' => 'checkbox', 'default' => 1],
            ],
        ],
        'faqs' => [
            'table' => 'faqs', 'trans' => 'faq_translations', 'fk' => 'faq_id', 'title' => 'question', 'label' => 'admin.faqs', 'icon' => 'question',
            'order' => 'x.sort_order',
            'list' => [['question', 'title'], ['topic', 'text'], ['sort_order', 'text'], ['is_active', 'bool']],
            'fields' => [
                ['name' => 'question', 'label' => 'admin.f_question', 'type' => 'text', 'trans' => true, 'required' => true],
                ['name' => 'answer', 'label' => 'admin.f_answer', 'type' => 'textarea', 'trans' => true, 'required' => true, 'rows' => 4],
                ['name' => 'topic', 'label' => 'admin.f_topic', 'type' => 'select', 'required' => true,
                    'options' => array_combine(['tickets', 'reservations', 'payment', 'venues', 'refunds', 'accessibility', 'programme', 'festival'], array_map(static fn($x) => t('faq.topic_' . $x), ['tickets', 'reservations', 'payment', 'venues', 'refunds', 'accessibility', 'programme', 'festival']))],
                ['name' => 'sort_order', 'label' => 'admin.f_sort', 'type' => 'number'],
                ['name' => 'is_active', 'label' => 'admin.f_active', 'type' => 'checkbox', 'default' => 1],
            ],
        ],
        'ticket_types' => [
            'table' => 'ticket_types', 'trans' => 'ticket_type_translations', 'fk' => 'ticket_type_id', 'title' => 'name', 'label' => 'admin.ticket_types', 'icon' => 'card',
            'order' => 'x.sort_order',
            'list' => [['name', 'title'], ['code', 'text'], ['discount_percent', 'percent'], ['is_active', 'bool']],
            'fields' => [
                ['name' => 'name', 'label' => 'admin.f_name', 'type' => 'text', 'trans' => true, 'required' => true],
                ['name' => 'description', 'label' => 'admin.f_description', 'type' => 'text', 'trans' => true],
                ['name' => 'code', 'label' => 'admin.f_code', 'type' => 'slug', 'required' => true],
                ['name' => 'discount_percent', 'label' => 'admin.f_discount', 'type' => 'number', 'required' => true, 'min' => 0, 'max' => 100],
                ['name' => 'sort_order', 'label' => 'admin.f_sort', 'type' => 'number'],
                ['name' => 'is_active', 'label' => 'admin.f_active', 'type' => 'checkbox', 'default' => 1],
            ],
        ],
    ];
}

function admin_handle_upload(string $input, string $dir): ?string
{
    $f = $_FILES[$input] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 3 * 1024 * 1024) {
        throw new InvalidArgumentException('admin.err_upload_size');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime] ?? null;
    if (!$ext || @getimagesize($f['tmp_name']) === false) {
        throw new InvalidArgumentException('admin.err_upload_type');
    }
    $dirPath = APP_ROOT . '/uploads/' . preg_replace('/[^a-z]/', '', $dir);
    if (!is_dir($dirPath) && !mkdir($dirPath, 0775, true) && !is_dir($dirPath)) {
        throw new RuntimeException('Cannot create upload folder');
    }
    $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dirPath . '/' . $name)) {
        throw new RuntimeException('Upload failed');
    }
    return 'uploads/' . basename($dirPath) . '/' . $name;
}
