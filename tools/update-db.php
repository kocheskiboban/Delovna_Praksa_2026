<?php
/**
 * Makes sure the local database matches database/schema.sql.
 * Run from the project folder:  php tools/update-db.php   (start.command / start.bat do this automatically)
 *
 * - database missing                 -> imported from database/schema.sql
 * - database from an older version   -> dropped and re-imported (demo data is reset)
 * - database up to date              -> nothing happens
 */
declare(strict_types=1);
$root = dirname(__DIR__);
$cfg = (require $root . '/includes/config.php')['db'];
$schemaFile = $root . '/database/schema.sql';
$sql = (string) file_get_contents($schemaFile);
preg_match("/'schema_version', '([^']+)'/", $sql, $m);
$want = $m[1] ?? '';

mysqli_report(MYSQLI_REPORT_OFF);
$candidates = [[$cfg['port'], $cfg['pass']], [3306, ''], [8889, 'root'], [3306, 'root']];
$db = null;
foreach ($candidates as [$port, $pass]) {
    $c = @new mysqli($cfg['host'], $cfg['user'], $pass, '', (int) $port);
    if (!$c->connect_errno) { $db = $c; break; }
}
if (!$db) {
    fwrite(STDERR, "  Не може да се поврзе со MySQL (порти 3306/8889). Стартувајте MAMP/XAMPP.\n");
    exit(1);
}
$db->set_charset('utf8mb4');
$name = $cfg['name'];
$have = null;
$exists = $db->query("SHOW DATABASES LIKE '" . $db->real_escape_string($name) . "'")->num_rows > 0;
if ($exists) {
    $r = @$db->query("SELECT v FROM `$name`.app_meta WHERE k = 'schema_version'");
    $have = ($r && ($row = $r->fetch_row())) ? $row[0] : 'old';
}
if ($exists && $have === $want) {
    echo "  База: во ред ($want)\n";
    exit(0);
}
echo $exists ? "  База: стара верзија ($have) — се увезува новата ($want)...\n" : "  База: се креира ($want)...\n";
if ($exists) {
    $db->query("DROP DATABASE `$name`");
}
if (!$db->multi_query($sql)) {
    fwrite(STDERR, "  Грешка при увоз: " . $db->error . "\n");
    exit(1);
}
do {
    if ($res = $db->store_result()) { $res->free(); }
} while ($db->more_results() && $db->next_result());
if ($db->errno) {
    fwrite(STDERR, "  Грешка при увоз: " . $db->error . "\n");
    exit(1);
}
echo "  База: готово.\n";
