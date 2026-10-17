<?php
declare(strict_types=1);

function account_start(string $active, string $title, array $crumbs = []): void
{
    $u = current_user();
    render_header(['title' => $title, 'noindex' => true, 'body_class' => 'page-account']);
    echo page_hero(t('account.eyebrow'), $title, '', array_merge([[t('nav.account'), url('account/dashboard.php')]], $crumbs ?: [[$title, null]]));
    $items = [
        'dashboard' => ['account/dashboard.php', 'home', 'account.dashboard'],
        'tickets'   => ['account/tickets.php', 'ticket', 'nav.my_tickets'],
        'orders'    => ['account/orders.php', 'list', 'account.orders'],
        'favorites' => ['account/favorites.php', 'heart', 'nav.favorites'],
        'settings'  => ['account/settings.php', 'settings', 'account.settings'],
    ];
    echo '<div class="container account-layout"><nav class="account-nav" aria-label="' . e(t('account.menu')) . '">'
        . '<div class="account-nav__user"><strong>' . e($u['first_name'] . ' ' . $u['last_name']) . '</strong><span>' . e($u['email']) . '</span></div><ul>';
    foreach ($items as $key => [$href, $ic, $label]) {
        echo '<li><a href="' . e(url($href)) . '"' . ($key === $active ? ' aria-current="page"' : '') . '>' . icon($ic) . e(t($label)) . '</a></li>';
    }
    if ($u['role'] === 'admin') {
        echo '<li><a href="' . e(url('admin/index.php')) . '">' . icon('chart') . e(t('nav.admin')) . '</a></li>';
    }
    echo '<li><form method="post" action="' . e(url('account/logout.php')) . '">' . csrf_field() . '<button type="submit">' . icon('logout') . e(t('nav.logout')) . '</button></form></li>';
    echo '</ul></nav><div class="account-main">';
}

function account_end(array $meta = []): void
{
    echo '</div></div>';
    render_footer($meta);
}
