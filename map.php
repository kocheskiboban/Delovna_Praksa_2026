<?php
/** Interactive festival map (self-contained SVG map, no external map service required). */
require __DIR__ . '/includes/bootstrap.php';

$venues = get_venues();
$activeSlug = input('venue', '', 'get');
$active = null;
foreach ($venues as $v) {
    if ($v['slug'] === $activeSlug) {
        $active = $v;
    }
}
$active = $active ?? $venues[0] ?? null;

// data for the client-side panel
$data = [];
foreach ($venues as $v) {
    [$evs] = get_events(['venue_id' => (int) $v['id'], 'when' => 'upcoming', 'limit' => 3]);
    $data[$v['slug']] = [
        'name' => $v['name'], 'description' => $v['short_description'], 'address' => venue_address($v),
        'capacity' => $v['seating_type'] === 'general' ? t('venue.capacity_general', ['n' => $v['capacity']]) : t('venue.capacity_seated', ['n' => $v['capacity']]),
        'image' => img($v['image']), 'url' => url('venue.php', ['slug' => $v['slug']]),
        'events' => array_map(static fn($e) => ['title' => $e['title'], 'date' => fmt_date($e['starts_at']) . ', ' . fmt_time($e['starts_at']), 'url' => url('event.php', ['slug' => $e['slug']])], $evs),
    ];
}

render_header(['title' => t('map.title'), 'description' => t('map.meta'), 'body_class' => 'page-map', 'scripts' => ['assets/js/map.js']]);
echo page_hero(t('map.eyebrow'), t('map.title'), t('map.lead'), [[t('nav.programme'), url('programme.php')], [t('nav.map'), null]]);
?>
<section class="section section--tight">
  <div class="container fmap-layout">
    <div>
      <div class="fmap" data-fmap>
        <svg class="fmap__art" viewBox="0 0 400 300" preserveAspectRatio="none" aria-hidden="true" focusable="false">
          <defs>
            <linearGradient id="lake" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#7fb7bd"/><stop offset="1" stop-color="#3f8f99"/></linearGradient>
            <pattern id="waves" width="24" height="10" patternUnits="userSpaceOnUse"><path d="M0 5c3-3 9-3 12 0s9 3 12 0" fill="none" stroke="#e6f2f1" stroke-width=".8" opacity=".6"/></pattern>
          </defs>
          <rect width="400" height="300" fill="#e9ede6"/>
          <path d="M0 214 C40 206 70 212 92 210 C120 208 150 226 170 232 C220 248 280 262 330 266 C360 268 385 272 400 276 L400 300 L0 300 Z" fill="url(#lake)"/>
          <path d="M0 214 C40 206 70 212 92 210 C120 208 150 226 170 232 C220 248 280 262 330 266 C360 268 385 272 400 276 L400 300 L0 300 Z" fill="url(#waves)"/>
          <path d="M18 60 C60 30 150 26 205 52 C215 90 200 150 180 205 C140 222 100 214 60 204 C30 170 20 110 18 60 Z" fill="#dcd5c3" stroke="#b9ab8d" stroke-width="1.2" stroke-dasharray="4 3"/>
          <path d="M70 52 L118 48 L130 76 L96 92 L68 80 Z" fill="#c8bb9c" stroke="#8e7f5f" stroke-width="1.4"/>
          <g stroke="#fbfaf6" stroke-width="3.2" fill="none" stroke-linecap="round">
            <path d="M205 52 C240 70 280 72 330 66 L400 60"/><path d="M180 205 C210 160 240 120 260 70"/><path d="M60 204 C90 170 130 150 165 120"/>
            <path d="M260 70 L268 0"/><path d="M330 66 L340 160 L360 260"/><path d="M250 130 L400 140"/>
          </g>
          <path d="M92 222 C130 230 160 238 200 250 C260 266 330 270 400 282" fill="none" stroke="#f3efe2" stroke-width="2" stroke-dasharray="2 4"/>
          <g font-family="Georgia, serif" fill="#56686f" font-style="italic">
            <text x="210" y="290" font-size="13"><?= e(t('map.lake')) ?></text>
            <text x="34" y="98" font-size="11"><?= e(t('map.old_town')) ?></text>
            <text x="290" y="110" font-size="11"><?= e(t('map.centre')) ?></text>
            <text x="70" y="44" font-size="9"><?= e(t('map.fortress')) ?></text>
          </g>
          <g transform="translate(370 30)" fill="#56686f"><path d="M0 -14 L4 0 L0 -3 L-4 0 Z"/><text y="12" font-size="9" text-anchor="middle" font-family="Georgia, serif">N</text></g>
        </svg>
        <?php foreach ($venues as $v): ?>
          <a class="fmap__pin" href="<?= e(url('map.php', ['venue' => $v['slug']])) ?>#venue-panel" data-venue="<?= e($v['slug']) ?>"
             style="left:<?= (float) $v['map_x'] ?>%;top:<?= (float) $v['map_y'] ?>%" aria-pressed="<?= $active && $active['slug'] === $v['slug'] ? 'true' : 'false' ?>"
             aria-label="<?= e($v['name']) ?>">
            <span class="pin"><?= icon($v['seating_type'] === 'general' ? 'sun' : 'theatre') ?></span><span class="label"><?= e($v['name']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <ul class="fmap-list" aria-label="<?= e(t('map.venue_list')) ?>">
        <?php foreach ($venues as $v): ?>
          <li><a class="btn btn--ghost btn--block" style="justify-content:flex-start" href="<?= e(url('map.php', ['venue' => $v['slug']])) ?>#venue-panel" data-venue="<?= e($v['slug']) ?>"><?= icon('pin') ?><?= e($v['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <p class="muted" style="margin-top:var(--s-4);font-size:var(--step--1)"><?= e(t('map.note')) ?></p>
    </div>
    <?php if ($active): $d = $data[$active['slug']]; ?>
    <aside class="fmap-panel" id="venue-panel" aria-live="polite">
      <img src="<?= e($d['image']) ?>" alt="" data-p="image" width="600" height="400">
      <div class="fmap-panel__body">
        <h2 data-p="name"><?= e($d['name']) ?></h2>
        <p data-p="description"><?= e($d['description']) ?></p>
        <p><?= icon('pin') ?> <span data-p="address"><?= e($d['address']) ?></span></p>
        <p><?= icon('users') ?> <span data-p="capacity"><?= e($d['capacity']) ?></span></p>
        <h3><?= e(t('map.upcoming')) ?></h3>
        <ul data-p="events">
          <?php foreach ($d['events'] as $ev): ?><li><a href="<?= e($ev['url']) ?>"><?= e($ev['title']) ?></a><br><span class="muted"><?= e($ev['date']) ?></span></li><?php endforeach; ?>
          <?php if (!$d['events']): ?><li class="muted"><?= e(t('venue.no_upcoming')) ?></li><?php endif; ?>
        </ul>
        <a class="btn btn--primary btn--block" data-p="url" href="<?= e($d['url']) ?>"><?= e(t('map.venue_details')) ?></a>
      </div>
    </aside>
    <?php endif; ?>
  </div>
</section>
<script type="application/json" id="fmap-data"><?= json_encode(['venues' => $data, 'empty' => t('venue.no_upcoming')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php render_footer(['scripts' => ['assets/js/map.js']]);
