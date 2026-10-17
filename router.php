<?php
/**
 * Router for PHP's built-in web server (used by start.command / start.bat):
 *     php -S localhost:8000 router.php
 * Mirrors the rules in .htaccess. Not used under Apache.
 */
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if ($path === '/sitemap.xml') {
    require __DIR__ . '/sitemap-xml.php';
    return true;
}
if ($path === '/robots.txt') {
    require __DIR__ . '/robots.php';
    return true;
}
// never serve internal folders or source/data files
if (preg_match('#^/(includes|database|tools|lang|docs)(/|$)#', $path)
    || preg_match('#\.(sql|py|md|log|ini|command|bat)$#i', $path)
    || $path === '/router.php') {
    require __DIR__ . '/404.php';
    return true;
}
$file = __DIR__ . $path;
if (is_dir($file)) {
    $file = rtrim($file, '/') . '/index.php';
}
if (!is_file($file)) {
    require __DIR__ . '/404.php';
    return true;
}
return false; // let the built-in server handle the existing file
