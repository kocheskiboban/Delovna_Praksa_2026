<?php

declare(strict_types=1);

function main_nav(): array
{
    return [
        ['programme.php', 'nav.programme', [
            ['programme.php', 'nav.programme_all'],
            ['programme.php?when=past', 'nav.programme_past'],
            ['calendar.php', 'nav.calendar'],
            ['map.php', 'nav.map'],
            ['editions.php', 'nav.editions'],
        ]],
        ['calendar.php', 'nav.calendar', []],
        ['artists.php', 'nav.artists', []],
        ['venues.php', 'nav.venues', []],
        ['news.php', 'nav.news', []],
        ['about.php', 'nav.about', [
            ['about.php', 'nav.about_festival'],
            ['history.php', 'nav.history'],
            ['editions.php', 'nav.editions'],
            ['team.php', 'nav.team'],
            ['services.php', 'nav.services'],
            ['sponsors.php', 'nav.sponsors'],
            ['faq.php', 'nav.faq'],
        ]],
        ['gallery.php', 'nav.gallery', []],
        ['tickets.php', 'nav.tickets', []],
        ['contact.php', 'nav.contact', []],
    ];
}

function nav_url(string $target): string
{
    [$path, $qs] = array_pad(explode('?', $target, 2), 2, '');
    parse_str($qs, $params);
    return url($path, $params);
}

function nav_is_active(string $target, array $children = []): bool
{
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/account/')) {
        return false;
    }
    foreach (main_nav() as [$top]) {
        if (basename($top) === $script && basename($target) !== $script) {
            return false;
        }
    }
    foreach (array_merge([[$target]], $children) as $c) {
        if (basename(explode('?', $c[0])[0]) === $script) {
            return true;
        }
    }
    $aliases = ['event.php' => 'programme.php', 'artist.php' => 'artists.php', 'venue.php' => 'venues.php',
        'article.php' => 'news.php', 'album.php' => 'gallery.php', 'edition.php' => 'about.php'];
    return isset($aliases[$script]) && $aliases[$script] === basename($target);
}


function festival_logo(string $class = '', bool $light = false): string
{
    $file = 'assets/img/' . ($light ? 'logo-light.png' : 'logo.png');
    return '<img class="logo-mark logo-mark--image ' . e($class) . '" src="' . e(asset($file)) . '" alt="" width="46" height="46">';
}

function lang_switcher(string $variant = 'dropdown'): string
{
    if ($variant === 'list') {
        $html = '<ul class="lang-list" aria-label="' . e(t('a11y.language')) . '">';
        foreach (LANGUAGES as $code => $l) {
            $html .= '<li><a href="' . e(lang_url($code)) . '" hreflang="' . $code . '" lang="' . $code . '"'
                . ($code === lang() ? ' aria-current="true" class="is-active"' : '') . '>' . e($l['name']) . '</a></li>';
        }
        return $html . '</ul>';
    }
    $html = '<div class="lang-switch" data-dropdown><button type="button" class="lang-switch__btn" aria-expanded="false" aria-haspopup="true" aria-controls="lang-menu">'
        . icon('globe') . '<span>' . e(LANGUAGES[lang()]['short']) . '</span><span class="visually-hidden">' . e(t('a11y.language')) . ': ' . e(LANGUAGES[lang()]['name']) . '</span>' . icon('chevron-down') . '</button>'
        . '<ul class="lang-switch__menu" id="lang-menu">';
    foreach (LANGUAGES as $code => $l) {
        $html .= '<li><a href="' . e(lang_url($code)) . '" hreflang="' . $code . '" lang="' . $code . '"' . ($code === lang() ? ' aria-current="true"' : '') . '>'
            . '<span class="lang-code">' . e($l['short']) . '</span>' . e($l['name']) . '</a></li>';
    }
    return $html . '</ul></div>';
}

function render_header(array $meta = []): void
{
    $siteName = t('site.name');
    $title = isset($meta['title']) && $meta['title'] !== '' ? $meta['title'] . ' — ' . $siteName : $siteName . ' — ' . t('site.tagline');
    $desc = $meta['description'] ?? t('site.description');
    $canonical = $meta['canonical'] ?? (origin() . current_url_with(['page' => input_int('page', 1) > 1 ? input_int('page') : null, 'lang' => lang() === config('app.default_lang') ? null : lang()]));
    $image = $meta['image'] ?? 'assets/img/og-image.jpg';
    $imageAbs = preg_match('#^https?://#', $image) ? $image : origin() . img($image);
    $user = current_user();
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!doctype html>
<html lang="<?= e(lang()) ?>" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e(str_limit($desc, 160)) ?>">
<?php if (!empty($meta['noindex'])): ?><meta name="robots" content="noindex, nofollow">
<?php else: ?><link rel="canonical" href="<?= e($canonical) ?>">
<?php foreach (LANGUAGES as $code => $l): ?><link rel="alternate" hreflang="<?= e($l['hreflang']) ?>" href="<?= e(origin() . current_url_with(['lang' => $code, 'page' => null])) ?>">
<?php endforeach; ?><link rel="alternate" hreflang="x-default" href="<?= e(origin() . current_url_with(['lang' => null, 'page' => null])) ?>">
<?php endif; ?>
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($meta['title'] ?? $siteName) ?>">
<meta property="og:description" content="<?= e(str_limit($desc, 200)) ?>">
<meta property="og:type" content="<?= e($meta['og_type'] ?? 'website') ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($imageAbs) ?>">
<meta property="og:locale" content="<?= e(LANGUAGES[lang()]['locale']) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#0c2433">
<link rel="icon" href="<?= e(asset('assets/img/favicon-32.png')) ?>" type="image/png" sizes="32x32">
<link rel="icon" href="<?= e(asset('assets/img/favicon-512.png')) ?>" type="image/png" sizes="512x512">
<link rel="apple-touch-icon" href="<?= e(asset('assets/img/apple-touch-icon.png')) ?>">
<link rel="preload" href="<?= e(base_path()) ?>/assets/fonts/alegreya-cyrillic-700-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('assets/css/fonts.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
<?php foreach ($meta['styles'] ?? [] as $css): ?><link rel="stylesheet" href="<?= e(asset($css)) ?>">
<?php endforeach; ?>
<?php if (!empty($meta['jsonld'])): ?><script type="application/ld+json"><?= json_encode($meta['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endif; ?>
</head>
<body class="<?= e($meta['body_class'] ?? '') ?>">
<a class="skip-link" href="#main"><?= e(t('a11y.skip')) ?></a>
<?= icon_sprite() ?>
<div class="topbar">
  <div class="container topbar__inner">
    <p class="topbar__note"><?= icon('sun') ?><?= e(t('topbar.note')) ?></p>
    <div class="topbar__actions">
      <a href="<?= e(url('search.php')) ?>" class="topbar__link"><?= icon('search') ?><?= e(t('nav.search')) ?></a>
      <?php if ($user): ?>
        <a href="<?= e(url('account/dashboard.php')) ?>" class="topbar__link"><?= icon('user') ?><?= e($user['first_name']) ?></a>
        <?php if ($user['role'] === 'admin'): ?><a href="<?= e(url('admin/index.php')) ?>" class="topbar__link"><?= icon('settings') ?><?= e(t('nav.admin')) ?></a><?php endif; ?>
      <?php else: ?>
        <a href="<?= e(url('account/login.php')) ?>" class="topbar__link"><?= icon('user') ?><?= e(t('nav.login')) ?></a>
        <a href="<?= e(url('account/register.php')) ?>" class="topbar__link topbar__link--hide-sm"><?= e(t('nav.register')) ?></a>
      <?php endif; ?>
      <?= lang_switcher() ?>
    </div>
  </div>
</div>
<header class="site-header" data-header>
  <div class="container site-header__inner">
    <a class="brand" href="<?= e(url('')) ?>" aria-label="<?= e($siteName . ' — ' . t('nav.home')) ?>">
      <?= festival_logo() ?>
      <span class="brand__text"><span class="brand__name"><?= e($siteName) ?></span><span class="brand__sub"><?= e(t('site.since')) ?></span></span>
    </a>
    <nav class="main-nav" id="main-nav" aria-label="<?= e(t('a11y.main_nav')) ?>">
      <ul class="main-nav__list">
        <li class="main-nav__item main-nav__item--home"><a href="<?= e(url('')) ?>" <?= basename($_SERVER['SCRIPT_NAME'] ?? '') === 'index.php' && !str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? 'aria-current="page"' : '' ?>><?= e(t('nav.home')) ?></a></li>
        <?php foreach (main_nav() as [$target, $label, $children]): $active = nav_is_active($target, $children); ?>
        <li class="main-nav__item<?= $children ? ' has-sub' : '' ?><?= $active ? ' is-active' : '' ?>">
          <a href="<?= e(nav_url($target)) ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= e(t($label)) ?></a>
          <?php if ($children): ?>
          <button type="button" class="sub-toggle" aria-expanded="false" aria-label="<?= e(t('a11y.submenu', ['name' => t($label)])) ?>"><?= icon('chevron-down') ?></button>
          <ul class="sub-menu">
            <?php foreach ($children as [$cTarget, $cLabel]): ?><li><a href="<?= e(nav_url($cTarget)) ?>"><?= e(t($cLabel)) ?></a></li><?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <div class="main-nav__mobile-extra">
        <?php if ($user): ?>
          <a class="btn btn--ghost btn--block" href="<?= e(url('account/dashboard.php')) ?>"><?= icon('user') ?><?= e(t('nav.account')) ?></a>
        <?php else: ?>
          <a class="btn btn--ghost btn--block" href="<?= e(url('account/login.php')) ?>"><?= icon('user') ?><?= e(t('nav.login')) ?></a>
        <?php endif; ?>
        <?= lang_switcher('list') ?>
      </div>
    </nav>
    <div class="site-header__actions">
      <button type="button" class="icon-btn" data-open-search aria-label="<?= e(t('nav.search')) ?>" aria-controls="search-dialog"><?= icon('search') ?></button>
      <a class="icon-btn" href="<?= e(url($user ? 'account/favorites.php' : 'account/login.php', $user ? [] : ['return' => url('account/favorites.php')])) ?>" aria-label="<?= e(t('nav.favorites')) ?>"><?= icon('heart') ?></a>
      <a class="btn btn--gold btn--sm site-header__cta" href="<?= e(url('programme.php', ['when' => 'upcoming'])) ?>"><?= icon('ticket') ?><span><?= e(t('nav.buy_tickets')) ?></span></a>
      <button type="button" class="icon-btn nav-toggle" aria-expanded="false" aria-controls="main-nav" data-nav-toggle>
        <?= icon('menu', 'nav-toggle__open') ?><?= icon('close', 'nav-toggle__close') ?><span class="visually-hidden"><?= e(t('a11y.menu')) ?></span>
      </button>
    </div>
  </div>
</header>
<dialog class="search-dialog" id="search-dialog" aria-labelledby="search-dialog-title">
  <form method="get" action="<?= e(url('search.php')) ?>" class="search-dialog__form" role="search" data-autocomplete="<?= e(url('api/search-suggest.php')) ?>">
    <h2 id="search-dialog-title" class="visually-hidden"><?= e(t('search.title')) ?></h2>
    <?php if (lang() !== config('app.default_lang')): ?><input type="hidden" name="lang" value="<?= e(lang()) ?>"><?php endif; ?>
    <?= icon('search') ?>
    <label class="visually-hidden" for="global-search"><?= e(t('search.label')) ?></label>
    <input id="global-search" type="search" name="q" placeholder="<?= e(t('search.placeholder')) ?>" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="global-search-list" aria-autocomplete="list" minlength="2" required>
    <button type="button" class="icon-btn" data-close-search aria-label="<?= e(t('common.close')) ?>"><?= icon('close') ?></button>
    <ul class="autocomplete" id="global-search-list" role="listbox" hidden></ul>
  </form>
  <p class="search-dialog__hint"><?= e(t('search.hint')) ?></p>
</dialog>
<main id="main" tabindex="-1">
<?= flash_messages() ?>
<?php
}

function render_footer(array $meta = []): void
{
    $social = config('festival.social');
    try {
        $sponsors = get_sponsors();
    } catch (Throwable $e) {
        $sponsors = [];
    }
    ?>
</main>
<section class="newsletter-band" aria-labelledby="nl-band-title">
  <div class="container newsletter-band__inner">
    <div>
      <p class="eyebrow eyebrow--light"><?= e(t('newsletter.eyebrow')) ?></p>
      <h2 id="nl-band-title"><?= e(t('newsletter.title')) ?></h2>
      <p><?= e(t('newsletter.text')) ?></p>
    </div>
    <?= newsletter_form('band') ?>
  </div>
</section>
<footer class="site-footer">
  <?php if ($sponsors): ?>
  <div class="container footer-sponsors">
    <p class="eyebrow"><?= e(t('footer.supported_by')) ?></p>
    <ul>
      <?php foreach ($sponsors as $s): ?><li><a href="<?= e(url('sponsors.php')) ?>#sponsor-<?= (int) $s['id'] ?>" title="<?= e($s['name']) ?>"><img src="<?= e(img($s['logo'])) ?>" alt="<?= e($s['name']) ?>" loading="lazy" width="150" height="50"></a></li><?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>
  <div class="container footer-grid">
    <div class="footer-brand">
      <a class="brand brand--light" href="<?= e(url('')) ?>"><?= festival_logo('', true) ?><span class="brand__text"><span class="brand__name"><?= e(t('site.name')) ?></span><span class="brand__sub"><?= e(t('site.since')) ?></span></span></a>
      <p><?= e(t('footer.about')) ?></p>
      <ul class="social" aria-label="<?= e(t('footer.social')) ?>">
        <li><a href="<?= e($social['facebook']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><?= icon('facebook') ?></a></li>
        <li><a href="<?= e($social['instagram']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><?= icon('instagram') ?></a></li>
        <li><a href="<?= e($social['youtube']) ?>" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><?= icon('youtube') ?></a></li>
        <li><a href="<?= e($social['x']) ?>" target="_blank" rel="noopener noreferrer" aria-label="X"><?= icon('x') ?></a></li>
      </ul>
    </div>
    <nav class="footer-col" aria-labelledby="fc-festival">
      <h2 id="fc-festival"><?= e(t('footer.festival')) ?></h2>
      <ul>
        <li><a href="<?= e(url('')) ?>"><?= e(t('nav.home')) ?></a></li>
        <li><a href="<?= e(url('about.php')) ?>"><?= e(t('nav.about_festival')) ?></a></li>
        <li><a href="<?= e(url('history.php')) ?>"><?= e(t('nav.history')) ?></a></li>
        <li><a href="<?= e(url('editions.php')) ?>"><?= e(t('nav.editions')) ?></a></li>
        <li><a href="<?= e(url('team.php')) ?>"><?= e(t('nav.team')) ?></a></li>
        <li><a href="<?= e(url('services.php')) ?>"><?= e(t('nav.services')) ?></a></li>
        <li><a href="<?= e(url('sponsors.php')) ?>"><?= e(t('nav.sponsors')) ?></a></li>
        <li><a href="<?= e(url('gallery.php')) ?>"><?= e(t('nav.gallery')) ?></a></li>
      </ul>
    </nav>
    <nav class="footer-col" aria-labelledby="fc-programme">
      <h2 id="fc-programme"><?= e(t('footer.programme')) ?></h2>
      <ul>
        <li><a href="<?= e(url('programme.php')) ?>"><?= e(t('nav.programme')) ?></a></li>
        <li><a href="<?= e(url('calendar.php')) ?>"><?= e(t('nav.calendar')) ?></a></li>
        <li><a href="<?= e(url('artists.php')) ?>"><?= e(t('nav.artists')) ?></a></li>
        <li><a href="<?= e(url('venues.php')) ?>"><?= e(t('nav.venues')) ?></a></li>
        <li><a href="<?= e(url('map.php')) ?>"><?= e(t('nav.map')) ?></a></li>
        <li><a href="<?= e(url('news.php')) ?>"><?= e(t('nav.news')) ?></a></li>
        <li><a href="<?= e(url('search.php')) ?>"><?= e(t('nav.search')) ?></a></li>
      </ul>
    </nav>
    <nav class="footer-col" aria-labelledby="fc-visitors">
      <h2 id="fc-visitors"><?= e(t('footer.visitors')) ?></h2>
      <ul>
        <li><a href="<?= e(url('tickets.php')) ?>"><?= e(t('nav.tickets')) ?></a></li>
        <li><a href="<?= e(url('faq.php')) ?>"><?= e(t('nav.faq')) ?></a></li>
        <li><a href="<?= e(url('contact.php')) ?>"><?= e(t('nav.contact')) ?></a></li>
        <li><a href="<?= e(url('account/dashboard.php')) ?>"><?= e(t('nav.account')) ?></a></li>
        <li><a href="<?= e(url('account/tickets.php')) ?>"><?= e(t('nav.my_tickets')) ?></a></li>
        <li><a href="<?= e(url('verify.php')) ?>"><?= e(t('nav.verify')) ?></a></li>
        <li><a href="<?= e(url('account/register.php')) ?>"><?= e(t('nav.register')) ?></a></li>
      </ul>
    </nav>
    <div class="footer-col footer-contact">
      <h2><?= e(t('footer.contact')) ?></h2>
      <address>
        <p><?= icon('pin') ?><?= e(lang() === 'mk' ? config('festival.address_mk') : config('festival.address_en')) ?></p>
        <p><?= icon('phone') ?><a href="tel:<?= e(preg_replace('/\s+/u', '', config('festival.phone'))) ?>"><?= e(config('festival.phone')) ?></a></p>
        <p><?= icon('mail') ?><a href="mailto:<?= e(config('festival.email')) ?>"><?= e(config('festival.email')) ?></a></p>
        <p><?= icon('clock') ?><?= e(t('contact.hours_short')) ?></p>
      </address>
      <h2 class="footer-lang-title"><?= e(t('footer.language')) ?></h2>
      <?= lang_switcher('list') ?>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container footer-bottom__inner">
      <p>© <?= date('Y') ?> <?= e(t('site.name')) ?>. <?= e(t('footer.disclaimer')) ?></p>
      <ul>
        <li><a href="<?= e(url('privacy.php')) ?>"><?= e(t('footer.privacy')) ?></a></li>
        <li><a href="<?= e(url('cookies.php')) ?>"><?= e(t('footer.cookies')) ?></a></li>
        <li><a href="<?= e(url('terms.php')) ?>"><?= e(t('footer.terms')) ?></a></li>
        <li><a href="<?= e(url('sitemap.php')) ?>"><?= e(t('footer.sitemap')) ?></a></li>
        <li><button type="button" class="linklike" data-open-cookie-settings><?= e(t('cookie.settings')) ?></button></li>
      </ul>
    </div>
  </div>
</footer>
<?php render_cookie_banner(); ?>
<script type="application/json" id="js-i18n"><?= json_encode(['required' => t('validation.required'), 'email' => t('validation.email'), 'minlength' => t('validation.minlength'),
    'match' => t('validation.password_match'), 'pattern' => t('validation.pattern'), 'check' => t('validation.check'), 'consent' => t('newsletter.consent_required'),
    'error' => t('error.generic')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
<?php foreach ($meta['scripts'] ?? [] as $js): ?><script src="<?= e(asset($js)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
<?php
}

function render_cookie_banner(): void
{
    $c = cookie_consent();
    ?>
<section class="cookie-banner" id="cookie-banner" role="region" aria-labelledby="cookie-title" <?= $c ? 'hidden' : '' ?>>
  <div class="cookie-banner__inner">
    <div class="cookie-banner__text">
      <h2 id="cookie-title"><?= icon('cookie') ?><?= e(t('cookie.title')) ?></h2>
      <p><?= e(t('cookie.text')) ?> <a href="<?= e(url('cookies.php')) ?>"><?= e(t('cookie.learn_more')) ?></a></p>
    </div>
    <form class="cookie-banner__actions" method="post" action="<?= e(url('api/cookie-consent.php')) ?>" data-cookie-form>
      <?= csrf_field() ?>
      <input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
      <button class="btn btn--gold" type="submit" name="choice" value="all"><?= e(t('cookie.accept_all')) ?></button>
      <button class="btn btn--ghost-light" type="submit" name="choice" value="necessary"><?= e(t('cookie.reject')) ?></button>
      <button class="btn btn--link-light" type="button" data-open-cookie-settings><?= e(t('cookie.manage')) ?></button>
    </form>
  </div>
</section>
<dialog class="modal" id="cookie-dialog" aria-labelledby="cookie-dialog-title">
  <form method="post" action="<?= e(url('api/cookie-consent.php')) ?>" class="modal__inner" data-cookie-form>
    <?= csrf_field() ?>
    <input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
    <input type="hidden" name="choice" value="custom">
    <header class="modal__header">
      <h2 id="cookie-dialog-title"><?= e(t('cookie.title')) ?></h2>
      <button type="button" class="icon-btn" data-close-dialog aria-label="<?= e(t('common.close')) ?>"><?= icon('close') ?></button>
    </header>
    <p><?= e(t('cookie.dialog_text')) ?></p>
    <div class="cookie-cat">
      <label class="switch"><input type="checkbox" checked disabled><span class="switch__ui" aria-hidden="true"></span><span class="switch__label"><strong><?= e(t('cookie.necessary')) ?></strong> — <?= e(t('cookie.necessary_text')) ?></span></label>
    </div>
    <div class="cookie-cat">
      <label class="switch"><input type="checkbox" name="preferences" value="1" <?= ($c['preferences'] ?? false) ? 'checked' : '' ?>><span class="switch__ui" aria-hidden="true"></span><span class="switch__label"><strong><?= e(t('cookie.preferences')) ?></strong> — <?= e(t('cookie.preferences_text')) ?></span></label>
    </div>
    <div class="cookie-cat">
      <label class="switch"><input type="checkbox" name="analytics" value="1" <?= ($c['analytics'] ?? false) ? 'checked' : '' ?>><span class="switch__ui" aria-hidden="true"></span><span class="switch__label"><strong><?= e(t('cookie.analytics')) ?></strong> — <?= e(t('cookie.analytics_text')) ?></span></label>
    </div>
    <footer class="modal__footer">
      <button class="btn btn--ghost" type="submit" name="choice" value="necessary"><?= e(t('cookie.reject')) ?></button>
      <button class="btn btn--primary" type="submit"><?= e(t('cookie.save')) ?></button>
    </footer>
  </form>
</dialog>
<?php
}

/**
 * Renders a full error page and stops. $kind: notfound|forbidden|csrf|server|db|ticket
 */
function render_error_page(int $code, string $kind = 'notfound', ?string $detail = null): void
{
    if (!headers_sent()) {
        http_response_code($code);
    }
    $map = [
        'notfound'  => ['icon' => 'map', 'title' => 'error.404_title', 'text' => 'error.404_text'],
        'forbidden' => ['icon' => 'lock', 'title' => 'error.403_title', 'text' => 'error.403_text'],
        'csrf'      => ['icon' => 'refresh', 'title' => 'error.csrf_title', 'text' => 'error.csrf_text'],
        'server'    => ['icon' => 'alert', 'title' => 'error.500_title', 'text' => 'error.500_text'],
        'db'        => ['icon' => 'archive', 'title' => 'error.db_title', 'text' => 'error.db_text'],
    ];
    $m = $map[$kind] ?? $map['notfound'];
    render_header(['title' => t($m['title']), 'noindex' => true, 'body_class' => 'page-error']);
    ?>
<section class="error-page">
  <div class="container error-page__inner">
    <p class="error-page__code" aria-hidden="true"><?= (int) $code ?></p>
    <span class="error-page__icon"><?= icon($m['icon']) ?></span>
    <h1><?= e(t($m['title'])) ?></h1>
    <p class="lead"><?= e(t($m['text'])) ?></p>
    <?php if ($detail): ?><pre class="error-page__detail"><?= e($detail) ?></pre><?php endif; ?>
    <?php if ($kind === 'notfound'): ?>
    <form class="error-page__search" method="get" action="<?= e(url('search.php')) ?>" role="search">
      <?php if (lang() !== config('app.default_lang')): ?><input type="hidden" name="lang" value="<?= e(lang()) ?>"><?php endif; ?>
      <label class="visually-hidden" for="err-q"><?= e(t('search.label')) ?></label>
      <input id="err-q" type="search" name="q" placeholder="<?= e(t('search.placeholder')) ?>" minlength="2" required>
      <button class="btn btn--primary" type="submit"><?= icon('search') ?><?= e(t('search.submit')) ?></button>
    </form>
    <?php endif; ?>
    <div class="error-page__links">
      <a class="btn btn--primary" href="<?= e(url('')) ?>"><?= icon('home') ?><?= e(t('common.back_home')) ?></a>
      <?php if ($kind === 'forbidden' && !is_logged_in()): ?>
        <a class="btn btn--ghost" href="<?= e(url('account/login.php')) ?>"><?= e(t('nav.login')) ?></a>
      <?php else: ?>
        <a class="btn btn--ghost" href="<?= e(url('programme.php')) ?>"><?= e(t('nav.programme')) ?></a>
      <?php endif; ?>
      <a class="btn btn--ghost" href="<?= e(url('contact.php')) ?>"><?= e(t('nav.contact')) ?></a>
    </div>
  </div>
</section>
<?php
    render_footer();
    exit;
}

function not_found(): void
{
    render_error_page(404, 'notfound');
}
