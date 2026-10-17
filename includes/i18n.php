<?php

declare(strict_types=1);

const LANGUAGES = [
    'mk' => ['name' => 'Македонски', 'short' => 'МК', 'locale' => 'mk_MK', 'hreflang' => 'mk'],
    'en' => ['name' => 'English',    'short' => 'EN', 'locale' => 'en_GB', 'hreflang' => 'en'],
    'sq' => ['name' => 'Shqip',      'short' => 'SQ', 'locale' => 'sq_AL', 'hreflang' => 'sq'],
    'de' => ['name' => 'Deutsch',    'short' => 'DE', 'locale' => 'de_DE', 'hreflang' => 'de'],
    'fr' => ['name' => 'Français',   'short' => 'FR', 'locale' => 'fr_FR', 'hreflang' => 'fr'],
    'it' => ['name' => 'Italiano',   'short' => 'IT', 'locale' => 'it_IT', 'hreflang' => 'it'],
];

function lang(): string
{
    return $GLOBALS['_lang'] ?? config('app.default_lang', 'mk');
}

function is_supported_lang(?string $code): bool
{
    return is_string($code) && isset(LANGUAGES[$code]);
}


function i18n_init(): void
{
    $chosen = null;
    $fromUrl = $_GET['lang'] ?? null;
    if (is_supported_lang($fromUrl)) {
        $chosen = $fromUrl;
        $_SESSION['lang'] = $chosen;
        $_SESSION['lang_chosen'] = true;
        if (consent_allows('preferences') && !headers_sent()) {
            setcookie('os_lang', $chosen, [
                'expires' => time() + 31536000, 'path' => base_path() . '/', 'samesite' => 'Lax', 'httponly' => false,
            ]);
        }
    } elseif (is_supported_lang($_SESSION['lang'] ?? null)) {
        $chosen = $_SESSION['lang'];
    } elseif (consent_allows('preferences') && is_supported_lang($_COOKIE['os_lang'] ?? null)) {
        $chosen = $_COOKIE['os_lang'];
    } else {
        $accept = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        foreach (explode(',', $accept) as $part) {
            $code = substr(trim($part), 0, 2);
            if (is_supported_lang($code)) {
                $chosen = $code;
                break;
            }
        }
    }
    $GLOBALS['_lang'] = $chosen ?: config('app.default_lang', 'mk');
    load_translations($GLOBALS['_lang']);
}

function load_translations(string $code): void
{
    $base = require APP_ROOT . '/lang/en.php';
    $mine = $code === 'en' ? [] : (is_file(APP_ROOT . "/lang/{$code}.php") ? require APP_ROOT . "/lang/{$code}.php" : []);
    $GLOBALS['_t'] = array_replace($base, $mine);
}

function t(string $key, array $repl = []): string
{
    $s = $GLOBALS['_t'][$key] ?? null;
    if (!is_string($s)) {
        return $key;
    }
    foreach ($repl as $k => $v) {
        $s = str_replace(':' . $k, (string) $v, $s);
    }
    return $s;
}

function t_array(string $key): array
{
    $v = $GLOBALS['_t'][$key] ?? [];
    return is_array($v) ? $v : [];
}

function page_text(string $page): array
{
    static $cache = [];
    $code = lang();
    if (!isset($cache[$code])) {
        $file = APP_ROOT . "/lang/pages/{$code}.php";
        $cache[$code] = is_file($file) ? require $file : [];
        if ($code !== 'en') {
            $cache[$code] += require APP_ROOT . '/lang/pages/en.php';
        }
    }
    return $cache[$code][$page] ?? [];
}

function lang_chain(): array
{
    return array_values(array_unique([lang(), 'en', 'mk']));
}


function tr_sql(string $table, string $fk, string $alias, array $cols, string $p = 't'): array
{
    $chain = lang_chain();
    $joins = [];
    foreach ($chain as $i => $code) {
        if (!is_supported_lang($code)) {
            continue;
        }
        $joins[] = "LEFT JOIN `{$table}` {$p}{$i} ON {$p}{$i}.`{$fk}` = {$alias}.id AND {$p}{$i}.lang = '{$code}'";
    }
    $select = [];
    foreach ($cols as $col => $as) {
        if (is_int($col)) {
            $col = $as;
        }
        $parts = [];
        foreach (array_keys($chain) as $i) {
            $parts[] = "NULLIF({$p}{$i}.`{$col}`, '')";
        }
        $select[] = 'COALESCE(' . implode(', ', $parts) . ") AS `{$as}`";
    }
    return [implode(', ', $select), implode(' ', $joins)];
}

function content_is_fallback(string $table, string $fk, int $id): bool
{
    static $cache = [];
    $k = "$table.$id." . lang();
    if (!isset($cache[$k])) {
        $cache[$k] = !db_value("SELECT 1 FROM `{$table}` WHERE `{$fk}` = ? AND lang = ? LIMIT 1", [$id, lang()]);
    }
    return $cache[$k];
}

function lang_url(string $code): string
{
    return current_url_with(['lang' => $code]);
}
