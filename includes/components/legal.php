<?php
declare(strict_types=1);

function render_legal_page(string $key, string $extraHtml = ''): void
{
    $p = page_text($key);
    render_header(['title' => $p['title'], 'description' => $p['meta'], 'body_class' => 'page-legal']);
    echo page_hero($p['eyebrow'], $p['title'], $p['lead'], [[$p['title'], null]]);
    echo '<section class="section"><div class="container layout-sidebar"><div class="prose">';
    echo '<p class="muted">' . e(t('legal.updated', ['date' => fmt_date($p['updated'])])) . '</p>';
    foreach ($p['sections'] as $i => [$h, $body]) {
        echo '<h2 id="s' . ($i + 1) . '">' . e($h) . '</h2>' . rich_text($body);
    }
    echo $extraHtml . '</div><aside class="toc" aria-label="' . e(t('a11y.on_this_page')) . '"><p class="eyebrow">' . e(t('a11y.on_this_page')) . '</p><ul>';
    foreach ($p['sections'] as $i => [$h]) {
        echo '<li><a href="#s' . ($i + 1) . '">' . e($h) . '</a></li>';
    }
    echo '</ul><div class="info-panel" style="margin-top:var(--s-5)"><h3>' . icon('info') . e(t('legal.related')) . '</h3><ul style="list-style:none;padding:0;display:grid;gap:.4rem">'
        . '<li><a href="' . e(url('privacy.php')) . '">' . e(t('footer.privacy')) . '</a></li>'
        . '<li><a href="' . e(url('cookies.php')) . '">' . e(t('footer.cookies')) . '</a></li>'
        . '<li><a href="' . e(url('terms.php')) . '">' . e(t('footer.terms')) . '</a></li></ul></div></aside></div></section>';
    render_footer();
}
