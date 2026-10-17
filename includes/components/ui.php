<?php

declare(strict_types=1);

function breadcrumbs(array $items): string
{
    if (!$items) {
        return '';
    }
    $all = array_merge([[t('nav.home'), url('')]], $items);
    $html = '<nav class="breadcrumbs" aria-label="' . e(t('a11y.breadcrumbs')) . '"><ol itemscope itemtype="https://schema.org/BreadcrumbList">';
    foreach ($all as $i => [$label, $href]) {
        $last = $i === count($all) - 1;
        $html .= '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        if ($href && !$last) {
            $html .= '<a itemprop="item" href="' . e($href) . '"><span itemprop="name">' . e($label) . '</span></a>';
        } else {
            $html .= '<span itemprop="name" ' . ($last ? 'aria-current="page"' : '') . '>' . e($label) . '</span>';
        }
        $html .= '<meta itemprop="position" content="' . ($i + 1) . '"></li>';
    }
    return $html . '</ol></nav>';
}

function page_hero(string $eyebrow, string $title, string $lead = '', array $crumbs = [], ?string $image = null, string $extra = ''): string
{
    $bg = $image ? ' style="--hero-img:url(\'' . e(img($image)) . '\')"' : '';
    return '<header class="page-hero' . ($image ? ' page-hero--image' : '') . '"' . $bg . '><div class="container">'
        . breadcrumbs($crumbs)
        . ($eyebrow !== '' ? '<p class="eyebrow">' . e($eyebrow) . '</p>' : '')
        . '<h1>' . e($title) . '</h1>'
        . ($lead !== '' ? '<p class="lead">' . e($lead) . '</p>' : '')
        . $extra . '</div></header>';
}

function section_head(string $eyebrow, string $title, ?string $href = null, ?string $linkLabel = null, string $id = ''): string
{
    $idAttr = $id ? ' id="' . e($id) . '"' : '';
    return '<div class="section-head"><div><p class="eyebrow">' . e($eyebrow) . '</p><h2' . $idAttr . '>' . e($title) . '</h2></div>'
        . ($href ? '<a class="link-arrow" href="' . e($href) . '">' . e($linkLabel ?? t('common.view_all')) . icon('arrow-right') . '</a>' : '')
        . '</div>';
}

function availability_badge(array $ev): string
{
    $map = [
        'past'      => ['muted', 'event.status_past'],
        'cancelled' => ['danger', 'event.status_cancelled'],
        'soldout'   => ['danger', 'event.status_soldout'],
        'closed'    => ['muted', 'event.status_closed'],
        'low'       => ['warning', 'event.status_low'],
        'good'      => ['success', 'event.status_available'],
    ];
    [$cls, $key] = $map[$ev['availability']] ?? $map['good'];
    return '<span class="badge badge--' . $cls . '">' . e(t($key)) . '</span>';
}

function category_chip(array $row): string
{
    return '<span class="chip" style="--chip:' . e($row['category_color'] ?? '#134a66') . '">'
        . icon(category_icon($row['category_slug'] ?? '')) . e($row['category_name'] ?? '') . '</span>';
}

function status_badge(string $status): string
{
    $cls = match ($status) {
        'paid', 'valid', 'active', 'answered', 'published' => 'success',
        'pending', 'reserved', 'new', 'draft'             => 'warning',
        'cancelled', 'failed', 'refunded', 'unsubscribed' => 'danger',
        default                                           => 'muted',
    };
    return '<span class="badge badge--' . $cls . '">' . e(t('status.' . $status)) . '</span>';
}

function date_block(string $dt): string
{
    $ts = strtotime($dt);
    $mShort = t_array('months_short');
    $days = t_array('weekdays_short');
    return '<span class="date-block" aria-hidden="true"><span class="date-block__day">' . date('j', $ts) . '</span>'
        . '<span class="date-block__month">' . e($mShort[(int) date('n', $ts) - 1] ?? '') . '</span>'
        . '<span class="date-block__wd">' . e($days[(int) date('N', $ts) - 1] ?? '') . '</span></span>';
}

function event_card(array $ev, string $variant = 'card'): string
{
    $href = url('event.php', ['slug' => $ev['slug']]);
    $fav = favorite_button('event', (int) $ev['id'], false);
    $price = (float) $ev['price_standard'] > 0 ? t('event.from_price', ['price' => money($ev['price_standard'])]) : t('event.free');
    if ($variant === 'row') {
        return '<article class="event-row">'
            . date_block($ev['starts_at'])
            . '<div class="event-row__body"><div class="event-row__meta">' . category_chip($ev) . availability_badge($ev) . '</div>'
            . '<h3><a href="' . e($href) . '">' . e($ev['title']) . '</a></h3>'
            . '<p class="muted">' . icon('clock') . e(fmt_time($ev['starts_at'])) . ' · ' . icon('pin') . e($ev['venue_name']) . '</p></div>'
            . '<div class="event-row__aside"><span class="price">' . e($price) . '</span>'
            . ($ev['can_buy'] ? '<a class="btn btn--primary btn--sm" href="' . e(url('booking/tickets.php', ['event' => $ev['slug']])) . '">' . e(t('event.buy')) . '</a>'
                : '<a class="btn btn--ghost btn--sm" href="' . e($href) . '">' . e(t('common.details')) . '</a>')
            . '</div></article>';
    }
    return '<article class="card event-card' . ($ev['is_past'] ? ' is-past' : '') . '">'
        . '<a class="card__media" href="' . e($href) . '" tabindex="-1" aria-hidden="true"><img src="' . e(img($ev['image'])) . '" alt="" loading="lazy" width="600" height="400"></a>'
        . '<div class="card__overlay">' . category_chip($ev) . $fav . '</div>'
        . '<div class="card__body">'
        . '<p class="card__kicker">' . icon('calendar') . '<time datetime="' . e(date('c', strtotime($ev['starts_at']))) . '">' . e(fmt_date($ev['starts_at'], 'weekday')) . ', ' . e(fmt_time($ev['starts_at'])) . '</time></p>'
        . '<h3 class="card__title"><a href="' . e($href) . '">' . e($ev['title']) . '</a></h3>'
        . ($ev['subtitle'] ? '<p class="card__text">' . e(str_limit($ev['subtitle'], 90)) . '</p>' : '')
        . '<p class="card__meta">' . icon('pin') . e($ev['venue_name']) . '</p>'
        . '<div class="card__footer"><span class="price">' . e($price) . '</span>' . availability_badge($ev) . '</div>'
        . '</div></article>';
}

function artist_card(array $a): string
{
    $href = url('artist.php', ['slug' => $a['slug']]);
    return '<article class="card artist-card">'
        . '<a class="card__media card__media--portrait" href="' . e($href) . '" tabindex="-1" aria-hidden="true"><img src="' . e(img($a['image'])) . '" alt="" loading="lazy" width="400" height="500"></a>'
        . '<div class="card__overlay card__overlay--end">' . favorite_button('artist', (int) $a['id'], false) . '</div>'
        . '<div class="card__body">'
        . '<p class="card__kicker">' . e($a['role'] ?? '') . '</p>'
        . '<h3 class="card__title"><a href="' . e($href) . '">' . e($a['name']) . '</a></h3>'
        . '<p class="card__meta"><span aria-hidden="true">' . country_flag($a['country_code']) . '</span> ' . e(t('country.' . $a['country_code'])) . '</p>'
        . ((int) ($a['upcoming_count'] ?? 0) > 0 ? '<p class="card__meta">' . icon('calendar') . e(t('artist.upcoming_count', ['n' => $a['upcoming_count']])) . '</p>' : '')
        . '</div></article>';
}

function news_card(array $n, bool $featured = false): string
{
    $href = url('article.php', ['slug' => $n['slug']]);
    return '<article class="card news-card' . ($featured ? ' news-card--featured' : '') . '">'
        . '<a class="card__media" href="' . e($href) . '" tabindex="-1" aria-hidden="true"><img src="' . e(img($n['image'])) . '" alt="" loading="lazy" width="600" height="400"></a>'
        . '<div class="card__body">'
        . '<p class="card__kicker"><a class="tag" href="' . e(url('news.php', ['category' => $n['category_slug']])) . '">' . e($n['category_name']) . '</a>'
        . '<time datetime="' . e(date('Y-m-d', strtotime($n['published_at']))) . '">' . e(fmt_date($n['published_at'])) . '</time></p>'
        . '<h3 class="card__title"><a href="' . e($href) . '">' . e($n['title']) . '</a></h3>'
        . '<p class="card__text">' . e(str_limit($n['excerpt'], $featured ? 220 : 130)) . '</p>'
        . '<a class="link-arrow" href="' . e($href) . '" aria-label="' . e(t('news.read_more') . ': ' . $n['title']) . '">' . e(t('news.read_more')) . icon('arrow-right') . '</a>'
        . '</div></article>';
}

function venue_card(array $v): string
{
    $href = url('venue.php', ['slug' => $v['slug']]);
    $cap = $v['seating_type'] === 'general' ? t('venue.capacity_general', ['n' => $v['capacity']]) : t('venue.capacity_seated', ['n' => $v['capacity']]);
    return '<article class="card venue-card">'
        . '<a class="card__media" href="' . e($href) . '" tabindex="-1" aria-hidden="true"><img src="' . e(img($v['image'])) . '" alt="" loading="lazy" width="600" height="400"></a>'
        . '<div class="card__body">'
        . '<h3 class="card__title"><a href="' . e($href) . '">' . e($v['name']) . '</a></h3>'
        . '<p class="card__text">' . e(str_limit($v['short_description'], 120)) . '</p>'
        . '<p class="card__meta">' . icon('users') . e($cap) . '</p>'
        . '<p class="card__meta">' . icon('calendar') . e(t('venue.upcoming_count', ['n' => $v['upcoming_count']])) . '</p>'
        . '</div></article>';
}

function favorite_button(string $type, int $id, bool $withLabel = true): string
{
    $on = is_favorite($type, $id);
    $label = t($on ? 'fav.remove' : 'fav.add');
    if (!is_logged_in()) {
        return '<a class="fav-btn' . ($withLabel ? ' fav-btn--label' : '') . '" href="' . e(url('account/login.php', ['return' => $_SERVER['REQUEST_URI'] ?? ''])) . '" title="' . e(t('fav.login')) . '" aria-label="' . e(t('fav.login')) . '">'
            . icon('heart') . ($withLabel ? '<span>' . e(t('fav.add')) . '</span>' : '') . '</a>';
    }
    return '<form class="fav-form" method="post" action="' . e(url('api/favorite.php')) . '">' . csrf_field()
        . '<input type="hidden" name="type" value="' . e($type) . '"><input type="hidden" name="id" value="' . $id . '">'
        . '<input type="hidden" name="return" value="' . e($_SERVER['REQUEST_URI'] ?? '') . '">'
        . '<button type="submit" class="fav-btn' . ($on ? ' is-on' : '') . ($withLabel ? ' fav-btn--label' : '') . '" aria-pressed="' . ($on ? 'true' : 'false') . '"'
        . ' data-label-on="' . e(t('fav.remove')) . '" data-label-off="' . e(t('fav.add')) . '" aria-label="' . e($label) . '" title="' . e($label) . '">'
        . icon('heart') . ($withLabel ? '<span>' . e($label) . '</span>' : '') . '</button></form>';
}

function share_buttons(string $absUrl, string $title): string
{
    $u = rawurlencode($absUrl);
    $tt = rawurlencode($title);
    return '<div class="share" aria-label="' . e(t('share.title')) . '"><span class="share__label">' . e(t('share.title')) . '</span>'
        . '<a class="icon-btn" href="https://www.facebook.com/sharer/sharer.php?u=' . $u . '" target="_blank" rel="noopener noreferrer" aria-label="' . e(t('share.facebook')) . '">' . icon('facebook') . '</a>'
        . '<a class="icon-btn" href="https://x.com/intent/post?url=' . $u . '&amp;text=' . $tt . '" target="_blank" rel="noopener noreferrer" aria-label="' . e(t('share.x')) . '">' . icon('x') . '</a>'
        . '<a class="icon-btn" href="mailto:?subject=' . $tt . '&amp;body=' . $u . '" aria-label="' . e(t('share.email')) . '">' . icon('mail') . '</a>'
        . '<button type="button" class="icon-btn" data-copy="' . e($absUrl) . '" data-copied="' . e(t('share.copied')) . '" aria-label="' . e(t('share.copy')) . '">' . icon('link') . '</button>'
        . '<button type="button" class="icon-btn js-native-share" data-title="' . e($title) . '" data-url="' . e($absUrl) . '" aria-label="' . e(t('share.native')) . '" hidden>' . icon('share') . '</button>'
        . '</div>';
}

function pagination(int $total, int $perPage, int $page): string
{
    $pages = (int) ceil($total / max(1, $perPage));
    if ($pages <= 1) {
        return '';
    }
    $html = '<nav class="pagination" aria-label="' . e(t('a11y.pagination')) . '"><ul>';
    $html .= '<li>' . ($page > 1 ? '<a href="' . e(current_url_with(['page' => $page - 1])) . '" rel="prev" aria-label="' . e(t('pagination.prev')) . '">' . icon('chevron-left') . '</a>' : '<span class="is-disabled">' . icon('chevron-left') . '</span>') . '</li>';
    for ($i = 1; $i <= $pages; $i++) {
        if ($i !== 1 && $i !== $pages && abs($i - $page) > 2) {
            if (abs($i - $page) === 3) {
                $html .= '<li><span class="ellipsis">…</span></li>';
            }
            continue;
        }
        $html .= '<li>' . ($i === $page ? '<span aria-current="page">' . $i . '</span>'
            : '<a href="' . e(current_url_with(['page' => $i])) . '" aria-label="' . e(t('pagination.page', ['n' => $i])) . '">' . $i . '</a>') . '</li>';
    }
    $html .= '<li>' . ($page < $pages ? '<a href="' . e(current_url_with(['page' => $page + 1])) . '" rel="next" aria-label="' . e(t('pagination.next')) . '">' . icon('chevron-right') . '</a>' : '<span class="is-disabled">' . icon('chevron-right') . '</span>') . '</li>';
    return $html . '</ul></nav>';
}

function current_page(): int
{
    return max(1, input_int('page', 1));
}

function alert(string $type, string $message, bool $dismiss = false): string
{
    $ic = ['success' => 'check-circle', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'][$type] ?? 'info';
    $role = $type === 'error' ? 'alert' : 'status';
    return '<div class="alert alert--' . e($type) . '" role="' . $role . '">' . icon($ic) . '<div>' . $message . '</div>'
        . ($dismiss ? '<button type="button" class="alert__close" aria-label="' . e(t('common.close')) . '" data-dismiss>' . icon('close') . '</button>' : '')
        . '</div>';
}

function flash_messages(): string
{
    $html = '';
    foreach (take_flashes() as $f) {
        $html .= alert($f['type'], e($f['message']), true);
    }
    return $html ? '<div class="flash-stack container">' . $html . '</div>' : '';
}

function empty_state(string $ic, string $title, string $text = '', ?string $href = null, ?string $cta = null): string
{
    return '<div class="empty-state">' . '<span class="empty-state__icon">' . icon($ic) . '</span>'
        . '<h3>' . e($title) . '</h3>' . ($text ? '<p>' . e($text) . '</p>' : '')
        . ($href ? '<a class="btn btn--primary" href="' . e($href) . '">' . e($cta ?? t('common.back_home')) . '</a>' : '') . '</div>';
}


function field(array $o): string
{
    $name = $o['name'];
    $id = $o['id'] ?? 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', $name);
    $type = $o['type'] ?? 'text';
    $value = (string) ($o['value'] ?? '');
    $err = $o['errors'][$name] ?? null;
    $req = !empty($o['required']);
    $describedBy = [];
    if (!empty($o['hint'])) {
        $describedBy[] = $id . '-hint';
    }
    if ($err) {
        $describedBy[] = $id . '-err';
    }
    $attrs = 'id="' . e($id) . '" name="' . e($name) . '"' . ($req ? ' required aria-required="true"' : '')
        . ($err ? ' aria-invalid="true"' : '') . ($describedBy ? ' aria-describedby="' . e(implode(' ', $describedBy)) . '"' : '')
        . (!empty($o['autocomplete']) ? ' autocomplete="' . e($o['autocomplete']) . '"' : '')
        . (!empty($o['attrs']) ? ' ' . $o['attrs'] : '');
    if ($type === 'checkbox') {
        return '<div class="field field--check' . ($err ? ' has-error' : '') . '"><label class="check"><input type="checkbox" ' . $attrs
            . ' value="' . e($o['check_value'] ?? '1') . '"' . (!empty($o['checked']) ? ' checked' : '') . '><span>' . ($o['label_html'] ?? e($o['label'])) . '</span></label>'
            . ($err ? '<p class="field__error" id="' . e($id) . '-err">' . icon('alert') . e(t($err)) . '</p>' : '') . '</div>';
    }
    $html = '<div class="field' . ($err ? ' has-error' : '') . '"><label for="' . e($id) . '">' . e($o['label'])
        . ($req ? ' <span class="req" aria-hidden="true">*</span>' : '') . '</label>';
    if ($type === 'textarea') {
        $html .= '<textarea ' . $attrs . ' rows="' . (int) ($o['rows'] ?? 5) . '">' . e($value) . '</textarea>';
    } elseif ($type === 'select') {
        $html .= '<div class="select"><select ' . $attrs . '>';
        foreach ($o['options'] as $val => $label) {
            $html .= '<option value="' . e((string) $val) . '"' . ((string) $val === $value ? ' selected' : '') . '>' . e($label) . '</option>';
        }
        $html .= '</select>' . icon('chevron-down') . '</div>';
    } else {
        $html .= '<input type="' . e($type) . '" ' . $attrs . ' value="' . e($value) . '">';
    }
    if (!empty($o['hint'])) {
        $html .= '<p class="field__hint" id="' . e($id) . '-hint">' . e($o['hint']) . '</p>';
    }
    if ($err) {
        $html .= '<p class="field__error" id="' . e($id) . '-err">' . icon('alert') . e(t($err)) . '</p>';
    }
    return $html . '</div>';
}

function newsletter_form(string $variant = 'block'): string
{
    $id = 'nl-' . $variant;
    return '<form class="newsletter newsletter--' . e($variant) . ' js-ajax-form" method="post" action="' . e(url('api/newsletter.php')) . '" novalidate>'
        . csrf_field() . '<input type="hidden" name="return" value="' . e($_SERVER['REQUEST_URI'] ?? '/') . '">'
        . '<div class="newsletter__row"><label class="visually-hidden" for="' . $id . '-email">' . e(t('newsletter.email')) . '</label>'
        . '<input id="' . $id . '-email" type="email" name="email" required autocomplete="email" placeholder="' . e(t('newsletter.placeholder')) . '">'
        . '<button class="btn btn--gold" type="submit">' . e(t('newsletter.subscribe')) . '</button></div>'
        . '<label class="check check--small"><input type="checkbox" name="consent" value="1" required><span>'
        . t('newsletter.consent', ['privacy' => '<a href="' . e(url('privacy.php')) . '">' . e(t('footer.privacy')) . '</a>']) . '</span></label>'
        . '<div class="form-feedback" aria-live="polite"></div></form>';
}

function stat_item(string $value, string $label): string
{
    return '<div class="stat"><span class="stat__value">' . e($value) . '</span><span class="stat__label">' . e($label) . '</span></div>';
}

function ticket_card(array $t): string
{
    $href = url('account/ticket.php', ['code' => $t['ticket_code']]);
    return '<article class="ticket-mini ticket-mini--' . e($t['status']) . '">'
        . '<div class="ticket-mini__date">' . date_block($t['starts_at']) . '</div>'
        . '<div class="ticket-mini__body"><h3><a href="' . e($href) . '">' . e($t['event_title']) . '</a></h3>'
        . '<p class="muted">' . icon('pin') . e($t['venue_name']) . ' · ' . icon('clock') . e(fmt_time($t['starts_at'])) . '</p>'
        . '<p>' . icon('ticket') . e(ticket_seat_text($t)) . ' · ' . e($t['type_name']) . '</p></div>'
        . '<div class="ticket-mini__aside">' . status_badge($t['status']) . '<code>' . e($t['ticket_code']) . '</code>'
        . '<a class="btn btn--ghost btn--sm" href="' . e($href) . '">' . icon('qr') . e(t('tickets.open')) . '</a></div></article>';
}

function stage_svg(string $layout, int $width = 1000): string
{
    $label = e(t('seat.stage'));
    if ($layout === 'amphitheatre') {
        return '<path class="stage" d="M380,70 A120,120 0 0 0 620,70 Z"/><text class="stage-label" x="500" y="100" text-anchor="middle">' . $label . '</text>';
    }
    return '<rect class="stage" x="300" y="40" width="400" height="56" rx="8"/><text class="stage-label" x="500" y="74" text-anchor="middle">' . $label . '</text>';
}

function venue_plan_svg(array $seats, string $layout, string $name): string
{
    $maxY = max(array_map(static fn($s) => (int) $s['pos_y'], $seats)) + 40;
    $html = '<div class="seatmap-scroll"><svg class="seatmap" viewBox="0 0 1000 ' . $maxY . '" role="img" aria-label="' . e(t('venue.plan_alt', ['name' => $name])) . '">' . stage_svg($layout);
    foreach ($seats as $s) {
        $st = $s['is_active'] ? 'available' : 'unavailable';
        $html .= '<g class="seat" data-st="' . $st . '" data-t="' . e($s['seat_type']) . '"><circle cx="' . (int) $s['pos_x'] . '" cy="' . (int) $s['pos_y'] . '" r="9"/></g>';
    }
    return $html . '</svg></div>';
}

function booking_steps(int $current, bool $seated = true): string
{
    $steps = [1 => 'booking.step_tickets', 2 => 'booking.step_seats', 3 => 'booking.step_review', 4 => 'booking.step_payment', 5 => 'booking.step_confirmation'];
    if (!$seated) {
        $steps[2] = 'booking.step_seats_ga';
    }
    $html = '<ol class="steps" aria-label="' . e(t('booking.progress')) . '">';
    foreach ($steps as $i => $key) {
        $cls = $i < $current ? 'is-done' : ($i === $current ? 'is-current' : '');
        $html .= '<li class="' . $cls . '"' . ($i === $current ? ' aria-current="step"' : '') . '>' . e(t($key)) . '</li>';
    }
    return $html . '</ol>';
}

function booking_summary(array $event, ?array $pricing = null, ?string $holdExpires = null, bool $showFee = true): string
{
    $html = '<aside class="summary" aria-labelledby="sum-title"><div class="summary__event"><img src="' . e(img($event['image'])) . '" alt="" width="80" height="80">'
        . '<div><h2 id="sum-title">' . e($event['title']) . '</h2><p>' . e(fmt_date($event['starts_at'], 'weekday')) . ', ' . e(fmt_time($event['starts_at'])) . '</p><p>' . e($event['venue_name']) . '</p></div></div>';
    if ($holdExpires) {
        $html .= '<p class="hold-timer" data-hold-until="' . e(date('c', strtotime($holdExpires))) . '" data-expired-text="' . e(t('booking.hold_expired')) . '">' . icon('clock')
            . '<span>' . e(t('booking.hold_left')) . ' <strong data-hold-clock>' . gmdate('i:s', max(0, strtotime($holdExpires) - time())) . '</strong></span></p>';
    }
    if ($pricing && $pricing['lines']) {
        $html .= '<table><tbody>';
        foreach ($pricing['lines'] as $l) {
            $html .= '<tr><td>' . e($l['type_name']) . ($l['zone'] === 'vip' ? ' <span class="badge badge--warning">VIP</span>' : '')
                . ($l['seat'] ? '<br><span class="muted">' . e(seat_label($l['seat'])) . '</span>' : '') . '</td><td>' . e(money($l['price'])) . '</td></tr>';
        }
        $html .= '</tbody><tfoot><tr><td>' . e(t('orders.subtotal')) . '</td><td>' . e(money($pricing['subtotal'])) . '</td></tr>';
        if ($showFee) {
            $html .= '<tr><td>' . e(t('orders.fee')) . '</td><td>' . e(money($pricing['fee'])) . '</td></tr>';
        }
        $html .= '<tr class="total"><td>' . e(t('orders.total')) . '</td><td>' . e(money($showFee ? $pricing['total'] : $pricing['subtotal'])) . '</td></tr></tfoot></table>';
    }
    $html .= '<p class="muted" style="font-size:var(--step--1);margin:0">' . icon('lock') . ' ' . e(t('booking.secure_note')) . '</p>';
    return $html . '</aside>';
}
