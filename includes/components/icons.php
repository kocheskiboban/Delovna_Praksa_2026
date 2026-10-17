<?php

declare(strict_types=1);

const ICONS = [
    'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
    'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
    'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.5 3-5.5 6.5-5.5s6.5 2 6.5 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.8c2.2.6 3.5 2.4 3.5 5.2"/>',
    'ticket'   => '<path d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-2a2 2 0 0 0 0-4Z"/><path d="M14 6v12" stroke-dasharray="2 2"/>',
    'heart'    => '<path d="M12 20s-7.5-4.6-9.2-9.3C1.6 7.3 3.9 4 7.2 4c2 0 3.6 1.2 4.8 3 1.2-1.8 2.8-3 4.8-3 3.3 0 5.6 3.3 4.4 6.7C19.5 15.4 12 20 12 20Z"/>',
    'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
    'clock'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
    'pin'      => '<path d="M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.4"/>',
    'music'    => '<path d="M9 18V5.5l11-2V16"/><circle cx="6.5" cy="18" r="2.5"/><circle cx="17.5" cy="16" r="2.5"/>',
    'mask'     => '<path d="M4 5c5 1.5 11 1.5 16 0v6c0 5-3.6 9-8 9s-8-4-8-9Z"/><path d="M8.5 10.5h2M13.5 10.5h2M9 15c1.8 1.4 4.2 1.4 6 0"/>',
    'ballet'   => '<circle cx="12" cy="4.5" r="2"/><path d="M12 7v6M12 9.5 7 7M12 9.5l5-3.5M7.5 15h9L12 13Z"/><path d="M12 15l-3 6M12 15l3 6"/>',
    'opera'    => '<path d="M3 21V8a9 6 0 0 1 18 0v13"/><path d="M3 9c3 1 3 5 1 12M21 9c-3 1-3 5-1 12"/><path d="M8 21h8"/>',
    'book'     => '<path d="M3 5.5C5.5 4 9 4 12 6c3-2 6.5-2 9-.5V19c-2.5-1.5-6-1.5-9 .5-3-2-6.5-2-9-.5Z"/><path d="M12 6v13.5"/>',
    'dance'    => '<circle cx="14" cy="4.5" r="2"/><path d="m13 7.5-3 5 4 2-2 6.5M10 12.5 5.5 11M13.5 9.5l5 1"/>',
    'film'     => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 4v16M17 4v16M3 9h4M3 15h4M17 9h4M17 15h4"/>',
    'spark'    => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M5.6 18.4l2.8-2.8M15.6 8.4l2.8-2.8"/>',
    'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'arrow-left'  => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
    'chevron-down'=> '<path d="m6 9 6 6 6-6"/>',
    'chevron-left'=> '<path d="m15 6-6 6 6 6"/>',
    'chevron-right'=> '<path d="m9 6 6 6-6 6"/>',
    'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3Z"/>',
    'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
    'phone'    => '<path d="M5 3.5h3.5l1.5 4.5-2 1.5a12 12 0 0 0 6.5 6.5l1.5-2 4.5 1.5V19a2 2 0 0 1-2 2A16.5 16.5 0 0 1 3 5.5a2 2 0 0 1 2-2Z"/>',
    'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8.5a.5.5 0 0 1 .5-.5Z"/>',
    'instagram'=> '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r=".6" fill="currentColor"/>',
    'youtube'  => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="m10 9 5 3-5 3Z" fill="currentColor"/>',
    'x'        => '<path d="M4 4l16 16M20 4 4 20" />',
    'share'    => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.2 10.8 7.6-4.4M8.2 13.2l7.6 4.4"/>',
    'link'     => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
    'print'    => '<path d="M7 9V3.5h10V9"/><rect x="3.5" y="9" width="17" height="8" rx="2"/><path d="M7 14h10v6.5H7Z"/>',
    'check'    => '<path d="m4.5 12.5 4.5 4.5 10.5-11"/>',
    'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 3 3 5.5-6"/>',
    'alert'    => '<path d="M12 3.5 2.5 20h19Z"/><path d="M12 10v4.5M12 17.2v.3"/>',
    'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.8v.3"/>',
    'lock'     => '<rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>',
    'logout'   => '<path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/><path d="M10 16l-4-4 4-4M6 12h10"/>',
    'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M5.3 18.7l2.1-2.1M16.6 7.4l2.1-2.1"/>',
    'star'     => '<path d="m12 3.5 2.6 5.4 5.9.8-4.3 4.1 1 5.8-5.2-2.8-5.2 2.8 1-5.8-4.3-4.1 5.9-.8Z"/>',
    'filter'   => '<path d="M3.5 5h17l-6.5 8v6l-4 1.5V13Z"/>',
    'grid'     => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
    'list'     => '<path d="M9 6h11M9 12h11M9 18h11M4.5 6h.5M4.5 12h.5M4.5 18h.5"/>',
    'eye'      => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>',
    'accessible' => '<circle cx="12" cy="4.5" r="1.8"/><path d="M12 7.5v6h5l2 5M12 10h5"/><path d="M8.5 11.5a5 5 0 1 0 7 6"/>',
    'cash'     => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.8"/><path d="M6 9.5v.5M18 14v.5"/>',
    'card'     => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19M6.5 15h4"/>',
    'bank'     => '<path d="M3 9.5 12 4l9 5.5ZM5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 20.5h18"/>',
    'download' => '<path d="M12 4v11M7 10.5l5 5 5-5M4.5 20h15"/>',
    'edit'     => '<path d="M4 20h4L19 9l-4-4L4 16Z"/><path d="m13.5 6.5 4 4"/>',
    'trash'    => '<path d="M4 7h16M9.5 7V4.5h5V7M6 7l1 13h10l1-13"/>',
    'plus'     => '<path d="M12 5v14M5 12h14"/>',
    'home'     => '<path d="M3.5 11 12 4l8.5 7"/><path d="M6 9.5V20h12V9.5"/>',
    'image'    => '<rect x="3" y="4.5" width="18" height="15" rx="2"/><circle cx="8.5" cy="9.5" r="1.8"/><path d="m4 18 5.5-5 4 3.5 2.5-2 4 3.5"/>',
    'chart'    => '<path d="M4 20V4M4 20h16"/><path d="M8 16v-4M12 16V8M16 16v-6"/>',
    'news'     => '<rect x="3.5" y="4" width="14" height="16" rx="1.5"/><path d="M17.5 8h3v10a2 2 0 0 1-2 2"/><path d="M7 8h7M7 12h7M7 16h4"/>',
    'map'      => '<path d="M3 6.5 9 4l6 2.5L21 4v13.5L15 20l-6-2.5L3 20Z"/><path d="M9 4v13.5M15 6.5V20"/>',
    'theatre'  => '<path d="M3 21h18M5 21V10M19 21V10M9 21v-7M15 21v-7M3 10l9-6 9 6Z"/>',
    'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4"/>',
    'question' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.7.4-1 1-1 1.7v.5M12 17v.3"/>',
    'handshake'=> '<path d="m3 11 4-4 4 2 3-2 4 1 3 3-6 6-2-1-2 2-2-1-2 1Z"/><path d="m10 9 3 3"/>',
    'award'    => '<circle cx="12" cy="9" r="5.5"/><path d="m8.5 13.5-1.5 7.5 5-2.5 5 2.5-1.5-7.5"/>',
    'archive'  => '<rect x="3" y="4" width="18" height="5" rx="1"/><path d="M5 9v10.5h14V9M10 13h4"/>',
    'qr'       => '<rect x="4" y="4" width="6" height="6"/><rect x="14" y="4" width="6" height="6"/><rect x="4" y="14" width="6" height="6"/><path d="M14 14h2v2h-2zM18 18h2v2h-2zM14 18h2M18 14h2"/>',
    'cookie'   => '<path d="M20.5 12.5A8.5 8.5 0 1 1 11.5 3.5a3 3 0 0 0 4 3.5 3 3 0 0 0 5 5.5Z"/><circle cx="8.5" cy="10" r=".8" fill="currentColor"/><circle cx="12" cy="15" r=".8" fill="currentColor"/><circle cx="15.5" cy="13" r=".6" fill="currentColor"/>',
    'refresh'  => '<path d="M20 5v5h-5M4 19v-5h5"/><path d="M5.5 9a7 7 0 0 1 12.5-2l2 3M18.5 15a7 7 0 0 1-12.5 2l-2-3"/>',
    'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
];

function category_icon(?string $slug): string
{
    return [
        'music' => 'music', 'theatre' => 'mask', 'ballet' => 'ballet', 'opera' => 'opera',
        'literature' => 'book', 'dance' => 'dance', 'film' => 'film',
    ][$slug ?? ''] ?? 'spark';
}

function icon(string $name, string $class = '', ?string $label = null): string
{
    $aria = $label !== null ? 'role="img" aria-label="' . e($label) . '"' : 'aria-hidden="true" focusable="false"';
    return '<svg class="icon ' . e($class) . '" ' . $aria . '><use href="#i-' . e($name) . '"/></svg>';
}

function icon_sprite(): string
{
    $out = '<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true"><defs>';
    foreach (ICONS as $name => $paths) {
        $out .= '<symbol id="i-' . $name . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</symbol>';
    }
    return $out . '</defs></svg>';
}
