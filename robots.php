<?php
/** robots.txt with an absolute sitemap URL (served as /robots.txt via .htaccess). */
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
$b = base_path();
echo "User-agent: *\nAllow: /\n";
foreach (['admin', 'account', 'booking', 'api', 'includes', 'database', 'tools'] as $d) {
    echo "Disallow: {$b}/{$d}/\n";
}
echo "\nSitemap: " . origin() . $b . "/sitemap.xml\n";
