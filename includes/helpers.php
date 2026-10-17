<?php

declare(strict_types=1);

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $configured = config('app.base_url');
    if ($configured) {
        $base = rtrim((string) parse_url($configured, PHP_URL_PATH), '/');
        return $base;
    }
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $appRoot = realpath(APP_ROOT) ?: APP_ROOT;
    $docRoot = str_replace('\\', '/', $docRoot);
    $appRoot = str_replace('\\', '/', $appRoot);
    if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
        $base = rtrim(substr($appRoot, strlen($docRoot)), '/');
    } else {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $base = preg_replace('#/(admin|account|booking|api)(/[^/]*)?$|/[^/]*$#', '', $script) ?? '';
    }
    if ($base !== '' && $base[0] !== '/') {
        $base = '/' . $base;
    }
    return $base;
}

function origin(): string
{
    $configured = config('app.base_url');
    if ($configured) {
        $p = parse_url($configured);
        return ($p['scheme'] ?? 'http') . '://' . ($p['host'] ?? 'localhost') . (isset($p['port']) ? ':' . $p['port'] : '');
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host)) {
        $host = 'localhost';
    }
    return ($https ? 'https' : 'http') . '://' . $host;
}


function url(string $path = '', array $params = [], bool $absolute = false): string
{
    $path = ltrim($path, '/');
    if ($path === 'index.php') {
        $path = '';
    }
    if (function_exists('lang') && !array_key_exists('lang', $params) && lang() !== config('app.default_lang')) {
        $params = ['lang' => lang()] + $params;
    }
    $params = array_filter($params, static fn($v) => $v !== null && $v !== '' && $v !== []);
    $qs = $params ? '?' . http_build_query($params) : '';
    $u = base_path() . '/' . $path . $qs;
    return $absolute ? origin() . $u : $u;
}

function asset(string $path): string
{
    $file = APP_ROOT . '/' . ltrim($path, '/');
    $v = is_file($file) ? substr((string) filemtime($file), -6) : '1';
    return base_path() . '/' . ltrim($path, '/') . (str_ends_with($path, '.svg') ? '' : '?v=' . $v);
}

function img(?string $path, string $fallback = 'assets/img/og-image.jpg'): string
{
    $path = $path ?: $fallback;
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return base_path() . '/' . ltrim($path, '/');
}

function redirect(string $to, int $code = 302): void
{
    header('Location: ' . $to, true, $code);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_api_request(): bool
{
    return str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/');
}

function input(string $key, string $default = '', string $source = 'request'): string
{
    $src = $source === 'get' ? $_GET : ($source === 'post' ? $_POST : array_merge($_GET, $_POST));
    $v = $src[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function input_int(string $key, int $default = 0): int
{
    $v = $_GET[$key] ?? $_POST[$key] ?? null;
    return is_numeric($v) ? (int) $v : $default;
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function remember_input(array $data): void
{
    unset($data['password'], $data['password_confirm'], $data['current_password'], $data['card_number'], $data['card_cvc'], $data['_csrf']);
    $_SESSION['_old'] = $data;
}

function old(string $key, string $default = ''): string
{
    return isset($_SESSION['_old'][$key]) && is_string($_SESSION['_old'][$key]) ? $_SESSION['_old'][$key] : $default;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function money($amount, bool $withCurrency = true): string
{
    $n = number_format((float) $amount, 0, ',', '.');
    if (!$withCurrency) {
        return $n;
    }
    return lang() === 'mk' ? $n . ' ден.' : $n . ' MKD';
}

function to_ts($dt): int
{
    if ($dt instanceof DateTimeInterface) {
        return $dt->getTimestamp();
    }
    return is_numeric($dt) ? (int) $dt : (int) strtotime((string) $dt);
}

function fmt_date($dt, string $style = 'long'): string
{
    $ts = to_ts($dt);
    $d = (int) date('j', $ts);
    $m = (int) date('n', $ts);
    $y = date('Y', $ts);
    $months = t_array('months');
    $monthsShort = t_array('months_short');
    $days = t_array('weekdays');
    $w = (int) date('N', $ts);
    switch ($style) {
        case 'short':
            return sprintf('%02d.%02d.%s', $d, $m, $y);
        case 'day':
            return $d . ' ' . ($monthsShort[$m - 1] ?? $m);
        case 'month':
            return ($months[$m - 1] ?? $m) . ' ' . $y;
        case 'weekday':
            return ($days[$w - 1] ?? '') . ', ' . fmt_date($ts, 'long');
        default:
            if (lang() === 'en') {
                return ($months[$m - 1] ?? $m) . ' ' . $d . ', ' . $y;
            }
            if (lang() === 'de') {
                return $d . '. ' . ($months[$m - 1] ?? $m) . ' ' . $y;
            }
            return $d . ' ' . ($months[$m - 1] ?? $m) . ' ' . $y;
    }
}

function fmt_time($dt): string
{
    return date('H:i', to_ts($dt));
}

function fmt_datetime($dt): string
{
    return fmt_date($dt) . ', ' . fmt_time($dt);
}

function str_limit(?string $s, int $len = 160): string
{
    $s = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $s)) ?? '');
    return mb_strlen($s) > $len ? rtrim(mb_substr($s, 0, $len - 1)) . '…' : $s;
}


function rich_text(?string $text): string
{
    $text = str_replace("\r\n", "\n", trim((string) $text));
    if ($text === '') {
        return '';
    }
    $html = '';
    foreach (preg_split("/\n{2,}/", $text) as $block) {
        $block = trim($block);
        if (str_starts_with($block, '## ')) {
            $lines = explode("\n", $block, 2);
            $html .= '<h2>' . e(substr($lines[0], 3)) . '</h2>';
            if (isset($lines[1]) && trim($lines[1]) !== '') {
                $html .= '<p>' . nl2br(e(trim($lines[1]))) . '</p>';
            }
        } elseif (preg_match('/^- /m', $block)) {
            $items = array_filter(array_map('trim', explode("\n", $block)));
            $html .= '<ul>' . implode('', array_map(static fn($i) => '<li>' . e(ltrim($i, '- ')) . '</li>', $items)) . '</ul>';
        } else {
            $html .= '<p>' . nl2br(e($block)) . '</p>';
        }
    }
    return $html;
}

function slugify(string $s): string
{
    $map = ['а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'ѓ' => 'gj', 'е' => 'e', 'ж' => 'zh', 'з' => 'z', 'ѕ' => 'dz',
        'и' => 'i', 'ј' => 'j', 'к' => 'k', 'л' => 'l', 'љ' => 'lj', 'м' => 'm', 'н' => 'n', 'њ' => 'nj', 'о' => 'o', 'п' => 'p',
        'р' => 'r', 'с' => 's', 'т' => 't', 'ќ' => 'kj', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'џ' => 'dzh', 'ш' => 'sh'];
    $s = mb_strtolower($s);
    $s = strtr($s, $map);
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = preg_replace('/[^a-z0-9]+/', '-', strtolower($s)) ?? '';
    return trim($s, '-') ?: 'item-' . bin2hex(random_bytes(3));
}

function client_ip_hash(): string
{
    return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . '|ohrid-salt');
}

function current_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return (string) parse_url($uri, PHP_URL_PATH);
}

function current_url_with(array $params, bool $keepLang = true): string
{
    $q = $_GET;
    foreach ($params as $k => $v) {
        if ($v === null) {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }
    if (!$keepLang) {
        unset($q['lang']);
    }
    $q = array_filter($q, static fn($v) => $v !== '' && $v !== null);
    return current_path() . ($q ? '?' . http_build_query($q) : '');
}

function is_valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL) && mb_strlen($email) <= 190;
}

function country_flag(string $code): string
{
    $code = strtoupper($code);
    if (!preg_match('/^[A-Z]{2}$/', $code)) {
        return '';
    }
    return mb_chr(0x1F1E6 + ord($code[0]) - 65) . mb_chr(0x1F1E6 + ord($code[1]) - 65);
}

function cookie_consent(): ?array
{
    static $c = false;
    if ($c !== false) {
        return $c;
    }
    $raw = $_COOKIE['os_consent'] ?? '';
    $data = json_decode($raw, true);
    $c = is_array($data) && isset($data['v']) ? [
        'necessary'   => true,
        'preferences' => !empty($data['p']),
        'analytics'   => !empty($data['a']),
        'id'          => preg_replace('/[^a-f0-9]/', '', (string) ($data['id'] ?? '')),
    ] : null;
    return $c;
}

function consent_allows(string $category): bool
{
    $c = cookie_consent();
    return $category === 'necessary' || ($c !== null && !empty($c[$category]));
}

function track_page_view(): void
{
    if (!consent_allows('analytics') || is_post() || is_api_request() || str_contains(current_path(), '/admin')) {
        return;
    }
    try {
        db_exec('INSERT INTO page_views (path, lang) VALUES (?, ?)', [mb_substr(current_path(), 0, 250), lang()]);
    } catch (Throwable $e) {
    }
}
