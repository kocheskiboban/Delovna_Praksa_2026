<?php
/** Event detail page. */
require __DIR__ . '/includes/bootstrap.php';

$slug = input('slug', '', 'get');
$ev = $slug !== '' ? get_event_by_slug($slug) : null;
if (!$ev) {
    not_found();
}
$artists = get_event_artists((int) $ev['id']);
$related = get_related_events($ev, 3);
[$news] = get_news(['event' => (int) $ev['id'], 'limit' => 2]);
$types = get_ticket_types();
$venue = get_venue_by_slug($ev['venue_slug']);
$fallback = content_is_fallback('event_translations', 'event_id', (int) $ev['id']);
$end = strtotime($ev['starts_at']) + 60 * (int) $ev['duration_min'];
$absUrl = url('event.php', ['slug' => $ev['slug']], true);
$ratio = $ev['capacity_total'] > 0 ? round(100 * $ev['available'] / $ev['capacity_total']) : 0;

render_header([
    'title' => $ev['title'],
    'description' => str_limit($ev['subtitle'] . '. ' . $ev['description'], 160),
    'image' => $ev['image'],
    'og_type' => 'article',
    'body_class' => 'page-event',
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => $ev['title'],
        'description' => str_limit($ev['description'], 300),
        'startDate' => date('c', strtotime($ev['starts_at'])),
        'endDate' => date('c', $end),
        'eventStatus' => $ev['is_cancelled'] ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'image' => origin() . img($ev['image']),
        'location' => ['@type' => 'Place', 'name' => $ev['venue_name'], 'address' => $venue ? venue_address($venue) : 'Ohrid'],
        'performer' => array_map(static fn($a) => ['@type' => $a['is_ensemble'] ? 'PerformingGroup' : 'Person', 'name' => $a['name']], $artists),
        'offers' => ['@type' => 'Offer', 'price' => (float) $ev['price_standard'], 'priceCurrency' => 'MKD', 'url' => $absUrl,
            'availability' => $ev['is_sold_out'] ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock'],
        'organizer' => ['@type' => 'Organization', 'name' => t('site.name'), 'url' => url('', [], true)],
    ],
]);
?>
<header class="event-hero">
  <div class="event-hero__bg"><img src="<?= e(img($ev['image'])) ?>" alt="" width="1200" height="800"></div>
  <div class="container event-hero__inner">
    <?= breadcrumbs([[t('nav.programme'), url('programme.php')], [$ev['category_name'], url('programme.php', ['category' => $ev['category_slug']])], [$ev['title'], null]]) ?>
    <div class="event-hero__chips">
      <?= category_chip($ev) ?><?= availability_badge($ev) ?>
      <?php if ($ev['edition_number']): ?><span class="badge"><?= e(t('event.edition', ['n' => $ev['edition_number']])) ?></span><?php endif; ?>
    </div>
    <h1><?= e($ev['title']) ?></h1>
    <?php if ($ev['subtitle']): ?><p class="subtitle"><?= e($ev['subtitle']) ?></p><?php endif; ?>
    <dl class="event-facts">
      <div><dt><?= icon('calendar') ?><?= e(t('event.date')) ?></dt><dd><time datetime="<?= e(date('c', strtotime($ev['starts_at']))) ?>"><?= e(fmt_date($ev['starts_at'], 'weekday')) ?></time></dd></div>
      <div><dt><?= icon('clock') ?><?= e(t('event.time')) ?></dt><dd><?= e(fmt_time($ev['starts_at'])) ?> – <?= e(date('H:i', $end)) ?></dd></div>
      <div><dt><?= icon('clock') ?><?= e(t('event.duration')) ?></dt><dd><?= e(t('event.minutes', ['n' => $ev['duration_min']])) ?></dd></div>
      <div><dt><?= icon('pin') ?><?= e(t('event.venue')) ?></dt><dd><a href="<?= e(url('venue.php', ['slug' => $ev['venue_slug']])) ?>" style="color:#fff"><?= e($ev['venue_name']) ?></a></dd></div>
      <?php if ($ev['country']): ?><div><dt><?= icon('globe') ?><?= e(t('event.country')) ?></dt><dd><?= e($ev['country']) ?></dd></div><?php endif; ?>
      <?php if ($ev['director']): ?><div><dt><?= icon('user') ?><?= e(t($ev['category_slug'] === 'music' || $ev['category_slug'] === 'opera' ? 'event.conductor' : 'event.director')) ?></dt><dd><?= e($ev['director']) ?></dd></div><?php endif; ?>
    </dl>
  </div>
</header>

<section class="section">
  <div class="container layout-sidebar">
    <div>
      <?php if ($ev['is_cancelled']): ?><?= alert('error', e(t('event.cancelled_notice'))) ?><?php endif; ?>
      <?php if ($fallback): ?><p class="notice-fallback"><?= icon('info') ?><?= e(t('content.fallback')) ?></p><?php endif; ?>
      <div class="prose">
        <h2><?= e(t('event.about')) ?></h2>
        <?= rich_text($ev['description']) ?>
      </div>

      <?php if ($ev['programme']): ?>
      <h2><?= e(t('event.programme')) ?></h2>
      <ul class="programme-list">
        <?php foreach (preg_split('/\R/u', trim($ev['programme'])) as $line): if (trim($line) === '') continue; ?>
          <li><?= e($line) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>

      <?php if ($artists): ?>
      <h2 style="margin-top:var(--s-6)"><?= e(t('event.performers')) ?></h2>
      <div class="performers">
        <?php foreach ($artists as $a): ?>
          <a class="performer" href="<?= e(url('artist.php', ['slug' => $a['slug']])) ?>">
            <img src="<?= e(img($a['image'])) ?>" alt="" loading="lazy" width="64" height="64">
            <div><strong><?= e($a['name']) ?></strong><span><?= e(t('role.' . $a['event_role'])) ?><?= mb_strtolower((string) $a['role']) !== mb_strtolower(t('role.' . $a['event_role'])) ? ' — ' . e($a['role']) : '' ?></span></div>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="detail-actions">
        <?= favorite_button('event', (int) $ev['id']) ?>
        <?= share_buttons($absUrl, $ev['title']) ?>
      </div>

      <?php if ($venue): ?>
      <h2><?= e(t('event.venue_info')) ?></h2>
      <div class="info-panels">
        <div class="info-panel">
          <h3><?= icon('pin') ?><?= e($venue['name']) ?></h3>
          <p><?= e(venue_address($venue)) ?></p>
          <p><?= e($venue['short_description']) ?></p>
          <a class="link-arrow" href="<?= e(url('venue.php', ['slug' => $venue['slug']])) ?>"><?= e(t('event.venue_link')) ?><?= icon('arrow-right') ?></a>
        </div>
        <div class="info-panel">
          <h3><?= icon('users') ?><?= e(t('event.seating')) ?></h3>
          <p><?= e($ev['mode'] === 'general' ? t('event.seating_general', ['n' => $ev['capacity_total']]) : t('event.seating_seated', ['n' => $ev['capacity_total']])) ?></p>
          <p class="muted"><?= e($venue['seating_info']) ?></p>
        </div>
        <div class="info-panel">
          <h3><?= icon('accessible') ?><?= e(t('venue.accessibility')) ?></h3>
          <p><?= e($venue['accessibility']) ?></p>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($news): ?>
      <h2 style="margin-top:var(--s-7)"><?= e(t('event.related_news')) ?></h2>
      <div class="grid grid--2"><?php foreach ($news as $n): ?><?= news_card($n) ?><?php endforeach; ?></div>
      <?php endif; ?>
    </div>

    <aside class="buy-box" aria-labelledby="buy-title">
      <h2 id="buy-title"><?= e(t('event.tickets')) ?></h2>
      <?php $isFree = (float) $ev['price_standard'] <= 0; ?>
      <?php if ($isFree): ?>
      <p class="buy-box__price"><?= e(t('event.free')) ?></p>
      <?php else: ?>
      <p class="muted"><?= e(t('event.price_from')) ?></p>
      <p class="buy-box__price"><?= e(money($ev['price_standard'])) ?></p>
      <?php endif; ?>
      <?php if ($ev['price_vip']): ?><p class="muted"><?= e(t('event.vip_price', ['price' => money($ev['price_vip'])])) ?></p><?php endif; ?>
      <?php if (!$isFree): ?>
      <ul>
        <?php foreach ($types as $tt): ?>
          <li><span><?= e($tt['name']) ?></span><strong><?= e(money(round($ev['price_standard'] * (100 - $tt['discount_percent']) / 100))) ?></strong></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <?php if (!$ev['is_past']): ?>
        <p><strong><?= e(t('event.availability')) ?>:</strong> <?= availability_badge($ev) ?></p>
        <div class="availability-meter<?= $ratio < 20 ? ' is-low' : '' ?><?= $ev['is_sold_out'] ? ' is-out' : '' ?>" role="img" aria-label="<?= e(t('event.seats_left', ['n' => $ev['available'], 't' => $ev['capacity_total']])) ?>"><span style="width:<?= 100 - $ratio ?>%"></span></div>
        <p class="muted"><?= e(t('event.seats_left', ['n' => $ev['available'], 't' => $ev['capacity_total']])) ?></p>
      <?php endif; ?>
      <?php if ($ev['can_buy']): ?>
        <a class="btn btn--primary btn--block btn--lg" href="<?= e(url('booking/tickets.php', ['event' => $ev['slug']])) ?>"><?= icon('ticket') ?><?= e(t('event.buy')) ?></a>
        <p class="muted" style="margin-top:.6rem;font-size:var(--step--1)"><?= e($ev['mode'] === 'seated' ? t('event.buy_hint_seated') : t('event.buy_hint_general')) ?></p>
      <?php elseif ($ev['is_sold_out']): ?>
        <?= alert('warning', '<strong>' . e(t('event.status_soldout')) . '</strong><br>' . e(t('event.soldout_text'))) ?>
        <a class="btn btn--ghost btn--block" href="<?= e(url('contact.php', ['topic' => 'tickets'])) ?>"><?= e(t('event.waitlist')) ?></a>
      <?php elseif ($ev['is_past']): ?>
        <?= alert('info', e(t('event.past_text'))) ?>
        <a class="btn btn--ghost btn--block" href="<?= e(url('gallery.php')) ?>"><?= icon('image') ?><?= e(t('nav.gallery')) ?></a>
      <?php elseif ($ev['is_cancelled']): ?>
        <?= alert('error', e(t('event.cancelled_notice'))) ?>
      <?php else: ?>
        <?= alert('info', e(t('event.closed_text'))) ?>
      <?php endif; ?>
      <p style="margin-top:var(--s-4)"><a class="link-arrow" href="<?= e(url('tickets.php')) ?>"><?= e(t('event.ticket_info')) ?><?= icon('arrow-right') ?></a></p>
    </aside>
  </div>
</section>

<?php if ($related): ?>
<section class="section section--paper" aria-labelledby="related-title">
  <div class="container">
    <?= section_head(t('event.related_eyebrow'), t('event.related'), url('programme.php', ['category' => $ev['category_slug']]), t('event.more_in', ['c' => $ev['category_name']]), 'related-title') ?>
    <div class="grid grid--3"><?php foreach ($related as $r): ?><?= event_card($r) ?><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>
<?php render_footer();
