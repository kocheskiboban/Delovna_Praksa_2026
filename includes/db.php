<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = config('db');
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    
    $candidates = [[$c['port'], $c['pass']]];
    if (getenv('OS_DB_PORT') === false && getenv('OS_DB_PASS') === false) {
        foreach ([[3306, ''], [8889, 'root'], [3306, 'root']] as $alt) {
            if (!in_array($alt, $candidates, true)) {
                $candidates[] = $alt;
            }
        }
    }

    $lastError = null;
    foreach ($candidates as [$port, $pass]) {
        $server = sprintf('mysql:host=%s;port=%d;charset=%s', $c['host'], $port, $c['charset']);
        try {
            $pdo = new PDO($server . ';dbname=' . $c['name'], $c['user'], $pass, $options);
        } catch (PDOException $e) {
            $lastError = $e;
            if ((int) ($e->errorInfo[1] ?? 0) === 1049) {
                // Server reachable, database missing: create it from database/schema.sql (first run).
                db_install($c['host'], (int) $port, $c['user'], $pass);
                $pdo = new PDO($server . ';dbname=' . $c['name'], $c['user'], $pass, $options);
            } else {
                continue;
            }
        }
        if (db_is_outdated($pdo)) {
            $pdo = null;
            $tmp = new PDO($server, $c['user'], $pass, $options);
            $tmp->exec('DROP DATABASE `' . str_replace('`', '', $c['name']) . '`');
            $tmp = null;
            db_install($c['host'], (int) $port, $c['user'], $pass);
            $pdo = new PDO($server . ';dbname=' . $c['name'], $c['user'], $pass, $options);
        }
        $pdo->exec("SET time_zone = '" . date('P') . "'");
        return $pdo;
    }
    throw $lastError ?? new RuntimeException('Database connection failed.');
}

function db_is_outdated(PDO $pdo): bool
{
    $head = (string) @file_get_contents(APP_ROOT . '/database/schema.sql', false, null, 0, 16384);
    if (!preg_match("/'schema_version', '([^']+)'/", $head, $m)) {
        return false;
    }
    try {
        $have = $pdo->query("SELECT v FROM app_meta WHERE k = 'schema_version'")->fetchColumn();
    } catch (PDOException $e) {
        $have = false; 
    }
    return $have !== $m[1];
}


function db_install(string $host, int $port, string $user, string $pass): void
{
    $file = APP_ROOT . '/database/schema.sql';
    if (!is_file($file) || !function_exists('mysqli_connect')) {
        throw new RuntimeException('Database "' . config('db')['name'] . '" not found. Import database/schema.sql.');
    }
    @set_time_limit(300);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $m = new mysqli($host, $user, $pass, '', $port);
    $m->set_charset('utf8mb4');
    $m->multi_query((string) file_get_contents($file));
    do {
        if ($r = $m->store_result()) {
            $r->free();
        }
    } while ($m->more_results() && $m->next_result());
    $m->close();
}

function db_query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    foreach (array_values(array_keys($params)) as $i => $key) {
        $value = $params[$key];
        $name = is_int($key) ? $key + 1 : (str_starts_with((string) $key, ':') ? $key : ':' . $key);
        $type = is_int($value) ? PDO::PARAM_INT : (is_bool($value) ? PDO::PARAM_BOOL : ($value === null ? PDO::PARAM_NULL : PDO::PARAM_STR));
        $stmt->bindValue($name, $value, $type);
    }
    $stmt->execute();
    return $stmt;
}

function db_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $row = db_query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function db_value(string $sql, array $params = [])
{
    $v = db_query($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

function db_exec(string $sql, array $params = []): int
{
    return db_query($sql, $params)->rowCount();
}

function db_insert_id(): int
{
    return (int) db()->lastInsertId();
}

function db_transaction(callable $fn)
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $result = $fn($pdo);
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function db_placeholders(array $items): string
{
    return implode(',', array_fill(0, max(1, count($items)), '?'));
}
