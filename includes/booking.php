<?php

declare(strict_types=1);

const PAYMENT_METHODS = ['visa', 'mastercard', 'maestro', 'bank_transfer', 'box_office'];
const CARD_METHODS = ['visa', 'mastercard', 'maestro'];

function booking_cleanup(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    db_exec('DELETE FROM reservations WHERE expires_at < NOW()');
    $expired = db_all("SELECT id FROM orders WHERE status IN ('reserved','pending') AND expires_at IS NOT NULL AND expires_at < NOW()");
    foreach ($expired as $o) {
        set_order_status((int) $o['id'], 'cancelled');
    }
}

function booking_get(int $eventId): array
{
    $b = $_SESSION['booking'] ?? [];
    return (($b['event_id'] ?? 0) === $eventId) ? $b : ['event_id' => $eventId, 'qty' => [], 'seats' => []];
}

function booking_put(array $b): void
{
    $_SESSION['booking'] = $b;
}

function booking_clear(): void
{
    unset($_SESSION['booking']);
}

function booking_qty_total(array $b): int
{
    return array_sum(array_map('intval', $b['qty'] ?? []));
}


function event_seat_map(array $event, int $userId = 0): array
{
    $seats = db_all('SELECT id, section, row_label, seat_number, seat_type, is_active, pos_x, pos_y
        FROM venue_seats WHERE venue_id = ? ORDER BY section, CAST(row_label AS UNSIGNED), seat_number', [(int) $event['venue_id']]);
    $sold = array_flip(array_map('intval', array_column(db_all("SELECT seat_id FROM tickets WHERE event_id = ? AND status <> 'cancelled' AND seat_id IS NOT NULL", [(int) $event['id']]), 'seat_id')));
    $blocked = array_flip(array_map('intval', array_column(db_all('SELECT seat_id FROM event_seat_blocks WHERE event_id = ?', [(int) $event['id']]), 'seat_id')));
    $holds = db_all('SELECT seat_id, user_id FROM reservations WHERE event_id = ? AND expires_at >= NOW()', [(int) $event['id']]);
    $held = [];
    foreach ($holds as $h) {
        $held[(int) $h['seat_id']] = (int) $h['user_id'];
    }
    $out = [];
    foreach ($seats as $s) {
        $id = (int) $s['id'];
        if (!$s['is_active'] || isset($blocked[$id])) {
            $st = 'unavailable';
        } elseif (isset($sold[$id])) {
            $st = 'occupied';
        } elseif (isset($held[$id])) {
            $st = $held[$id] === $userId ? 'mine' : 'occupied';
        } else {
            $st = 'available';
        }
        $out[] = ['id' => $id, 's' => $s['section'], 'r' => $s['row_label'], 'n' => (int) $s['seat_number'],
            't' => $s['seat_type'], 'x' => (int) $s['pos_x'], 'y' => (int) $s['pos_y'], 'st' => $st];
    }
    return $out;
}

function seat_label(array $seat): string
{
    $sec = $seat['section'] ?? $seat['s'] ?? '';
    $row = $seat['row_label'] ?? $seat['r'] ?? '';
    $num = $seat['seat_number'] ?? $seat['n'] ?? '';
    return t('seat.label', ['section' => t('seat.section_' . $sec), 'row' => $row, 'seat' => $num]);
}


function hold_seats(array $event, int $userId, array $seatIds): array
{
    $seatIds = array_values(array_unique(array_map('intval', $seatIds)));
    $minutes = (int) config('app.hold_minutes', 15);
    return db_transaction(function () use ($event, $userId, $seatIds, $minutes) {
        db_exec('DELETE FROM reservations WHERE expires_at < NOW()');
        db_exec('DELETE FROM reservations WHERE event_id = ? AND user_id = ?', [(int) $event['id'], $userId]);
        $valid = db_all('SELECT id FROM venue_seats WHERE venue_id = ? AND is_active = 1 AND id IN (' . db_placeholders($seatIds) . ')
            AND id NOT IN (SELECT seat_id FROM event_seat_blocks WHERE event_id = ?)
            AND id NOT IN (SELECT seat_id FROM tickets WHERE event_id = ? AND status <> \'cancelled\' AND seat_id IS NOT NULL)
            AND id NOT IN (SELECT seat_id FROM reservations WHERE event_id = ? AND expires_at >= NOW())
            FOR UPDATE', array_merge([(int) $event['venue_id']], $seatIds, [(int) $event['id'], (int) $event['id'], (int) $event['id']]));
        $validIds = array_map('intval', array_column($valid, 'id'));
        $taken = array_values(array_diff($seatIds, $validIds));
        if ($taken) {
            return ['ok' => false, 'taken' => $taken];
        }
        foreach ($seatIds as $sid) {
            try {
                db_exec('INSERT INTO reservations (user_id, event_id, seat_id, expires_at) VALUES (?, ?, ?, NOW() + INTERVAL ' . $minutes . ' MINUTE)',
                    [$userId, (int) $event['id'], $sid]);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') { // someone else holds it
                    throw new RuntimeException('seat_taken:' . $sid);
                }
                throw $e;
            }
        }
        return ['ok' => true, 'taken' => []];
    });
}

function user_holds(int $eventId, int $userId): array
{
    return db_all('SELECT r.seat_id, r.expires_at, s.section, s.row_label, s.seat_number, s.seat_type
        FROM reservations r JOIN venue_seats s ON s.id = r.seat_id
        WHERE r.event_id = ? AND r.user_id = ? AND r.expires_at >= NOW()
        ORDER BY s.section, CAST(s.row_label AS UNSIGNED), s.seat_number', [$eventId, $userId]);
}

function release_holds(int $eventId, int $userId): void
{
    db_exec('DELETE FROM reservations WHERE event_id = ? AND user_id = ?', [$eventId, $userId]);
}


function validate_quantities(array $raw, array $types, array $event): array
{
    $qty = [];
    $total = 0;
    foreach ($types as $tt) {
        $n = (int) ($raw[$tt['code']] ?? 0);
        if ($n < 0 || $n > 20) {
            $n = 0;
        }
        if ($n > 0) {
            $qty[$tt['code']] = $n;
            $total += $n;
        }
    }
    $errors = [];
    if ($total < 1) {
        $errors[] = 'booking.err_none';
    } elseif ($total > (int) config('app.max_tickets', 10)) {
        $errors[] = 'booking.err_max';
    } elseif ($total > (int) $event['available']) {
        $errors[] = 'booking.err_available';
    }
    return [$qty, $total, $errors];
}


function price_lines(array $event, array $qty, array $seats, array $types, string $method = 'visa'): array
{
    $byCode = [];
    foreach ($types as $tt) {
        $byCode[$tt['code']] = $tt;
    }
    $codes = [];
    foreach ($byCode as $code => $tt) {
        for ($i = 0; $i < (int) ($qty[$code] ?? 0); $i++) {
            $codes[] = $code;
        }
    }
    $lines = [];
    foreach ($codes as $i => $code) {
        $seat = $seats[$i] ?? null;
        $isVip = $seat && $seat['seat_type'] === 'vip' && $event['price_vip'] !== null;
        $base = (float) ($isVip ? $event['price_vip'] : $event['price_standard']);
        $tt = $byCode[$code];
        $price = round($base * (100 - (int) $tt['discount_percent']) / 100);
        $lines[] = [
            'type_id' => (int) $tt['id'], 'type_code' => $code, 'type_name' => $tt['name'],
            'discount' => (int) $tt['discount_percent'], 'seat' => $seat, 'seat_id' => $seat ? (int) $seat['seat_id'] : null,
            'zone' => $isVip ? 'vip' : 'standard', 'base' => $base, 'price' => $price,
        ];
    }
    $subtotal = array_sum(array_column($lines, 'price'));
    $fee = ($method === 'box_office' || $subtotal <= 0) ? 0.0 : (float) config('app.service_fee', 50);
    return ['lines' => $lines, 'subtotal' => $subtotal, 'fee' => $fee, 'total' => $subtotal + $fee];
}

function luhn_valid(string $number): bool
{
    $sum = 0;
    $alt = false;
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $n = (int) $number[$i];
        if ($alt) {
            $n *= 2;
            if ($n > 9) {
                $n -= 9;
            }
        }
        $sum += $n;
        $alt = !$alt;
    }
    return $sum % 10 === 0;
}

function detect_card_brand(string $number): ?string
{
    if (preg_match('/^4\d{12}(\d{3}){0,2}$/', $number)) {
        return 'visa';
    }
    if (preg_match('/^(5[1-5]\d{14}|2(2[2-9]\d|[3-6]\d\d|7[01]\d|720)\d{12})$/', $number)) {
        return 'mastercard';
    }
    if (preg_match('/^(5018|5020|5038|5893|6304|6759|676[1-3]|6799)\d{8,15}$/', $number)) {
        return 'maestro';
    }
    return null;
}


function validate_card(array $post, string $method): array
{
    $errors = [];
    $number = preg_replace('/\D/', '', (string) ($post['card_number'] ?? '')) ?? '';
    $name = trim((string) ($post['card_name'] ?? ''));
    $exp = trim((string) ($post['card_expiry'] ?? ''));
    $cvc = preg_replace('/\D/', '', (string) ($post['card_cvc'] ?? '')) ?? '';
    if (mb_strlen($name) < 3 || !preg_match('/^[\p{L} .\'-]+$/u', $name)) {
        $errors['card_name'] = 'payment.err_name';
    }
    $brand = detect_card_brand($number);
    if ($number === '' || strlen($number) < 12 || strlen($number) > 19 || !luhn_valid($number)) {
        $errors['card_number'] = 'payment.err_number';
    } elseif ($brand !== $method) {
        $errors['card_number'] = 'payment.err_brand';
    }
    if (!preg_match('#^(0[1-9]|1[0-2])\s*/\s*(\d{2})$#', $exp, $m)) {
        $errors['card_expiry'] = 'payment.err_expiry';
    } else {
        $expTs = mktime(23, 59, 59, (int) $m[1] + 1, 0, 2000 + (int) $m[2]);
        if ($expTs < time()) {
            $errors['card_expiry'] = 'payment.err_expired';
        }
    }
    if (!preg_match('/^\d{3,4}$/', $cvc)) {
        $errors['card_cvc'] = 'payment.err_cvc';
    }
    return [$errors, ['brand' => $brand, 'last4' => substr($number, -4), 'name' => $name, 'number' => $number]];
}

function demo_gateway_approves(string $number): bool
{
    return !str_ends_with($number, '0002');
}

function new_order_number(array $event): string
{
    $yy = date('y', strtotime($event['starts_at']));
    do {
        $n = 'OL' . $yy . '-' . str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    } while (db_value('SELECT 1 FROM orders WHERE order_number = ?', [$n]));
    return $n;
}

function new_ticket_code(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $s = '';
        for ($i = 0; $i < 10; $i++) {
            $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $code = 'OL-' . substr($s, 0, 5) . '-' . substr($s, 5);
    } while (db_value('SELECT 1 FROM tickets WHERE ticket_code = ?', [$code]));
    return $code;
}


function place_order(array $user, array $event, array $pricing, string $method, array $card = []): int
{
    return db_transaction(function () use ($user, $event, $pricing, $method, $card) {
        db_one('SELECT id FROM events WHERE id = ? FOR UPDATE', [(int) $event['id']]);
        $seatIds = array_values(array_filter(array_column($pricing['lines'], 'seat_id')));
        if ($event['mode'] === 'seated') {
            $held = db_all('SELECT seat_id FROM reservations WHERE event_id = ? AND user_id = ? AND expires_at >= NOW() FOR UPDATE',
                [(int) $event['id'], (int) $user['id']]);
            $heldIds = array_map('intval', array_column($held, 'seat_id'));
            if (!$seatIds || array_diff($seatIds, $heldIds)) {
                throw new RuntimeException('seats_expired');
            }
        } else {
            $sold = (int) db_value("SELECT COUNT(*) FROM tickets WHERE event_id = ? AND status <> 'cancelled'", [(int) $event['id']]);
            if ($sold + count($pricing['lines']) > (int) $event['capacity_total']) {
                throw new RuntimeException('sold_out');
            }
        }
        $isCard = in_array($method, CARD_METHODS, true);
        $status = $isCard ? 'paid' : ($method === 'bank_transfer' ? 'pending' : 'reserved');
        $ticketStatus = $isCard ? 'valid' : 'reserved';
        $expires = null;
        if ($method === 'box_office') {
            $expires = date('Y-m-d H:i:s', min(strtotime($event['starts_at']) - 3600, time() + 3600 * (int) config('app.reservation_hours', 72)));
        } elseif ($method === 'bank_transfer') {
            $expires = date('Y-m-d H:i:s', min(strtotime($event['starts_at']) - 3600, time() + 86400 * (int) config('app.transfer_days', 5)));
        }
        db_exec('INSERT INTO orders (order_number, user_id, event_id, customer_name, customer_email, status, payment_method, payment_status,
            card_brand, card_last4, subtotal, service_fee, total, currency, paid_at, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            new_order_number($event), (int) $user['id'], (int) $event['id'],
            trim($user['first_name'] . ' ' . $user['last_name']), $user['email'],
            $status, $method, $isCard ? 'paid' : 'unpaid',
            $isCard ? ucfirst($card['brand'] ?? $method) : null, $isCard ? ($card['last4'] ?? null) : null,
            $pricing['subtotal'], $pricing['fee'], $pricing['total'], config('app.currency', 'MKD'),
            $isCard ? date('Y-m-d H:i:s') : null, $expires,
        ]);
        $orderId = db_insert_id();
        foreach ($pricing['lines'] as $line) {
            db_exec('INSERT INTO order_items (order_id, ticket_type_id, seat_id, zone, unit_price) VALUES (?, ?, ?, ?, ?)',
                [$orderId, $line['type_id'], $line['seat_id'], $line['zone'], $line['price']]);
            $itemId = db_insert_id();
            try {
                db_exec('INSERT INTO tickets (order_id, order_item_id, event_id, seat_id, ticket_code, status) VALUES (?, ?, ?, ?, ?, ?)',
                    [$orderId, $itemId, (int) $event['id'], $line['seat_id'], new_ticket_code(), $ticketStatus]);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    throw new RuntimeException('seats_expired');
                }
                throw $e;
            }
        }
        db_exec('DELETE FROM reservations WHERE event_id = ? AND user_id = ?', [(int) $event['id'], (int) $user['id']]);
        return $orderId;
    });
}

/** Keeps a record of a declined payment (no tickets issued). */
function record_failed_payment(array $user, array $event, array $pricing, string $method, array $card): void
{
    db_exec('INSERT INTO orders (order_number, user_id, event_id, customer_name, customer_email, status, payment_method, payment_status,
        card_brand, card_last4, subtotal, service_fee, total) VALUES (?, ?, ?, ?, ?, \'failed\', ?, \'failed\', ?, ?, ?, ?, ?)', [
        new_order_number($event), (int) $user['id'], (int) $event['id'], trim($user['first_name'] . ' ' . $user['last_name']),
        $user['email'], $method, ucfirst($card['brand'] ?? $method), $card['last4'] ?? null,
        $pricing['subtotal'], $pricing['fee'], $pricing['total'],
    ]);
}

function set_order_status(int $orderId, string $status): void
{
    $map = [
        'paid'      => ['payment' => 'paid', 'ticket' => 'valid'],
        'reserved'  => ['payment' => 'unpaid', 'ticket' => 'reserved'],
        'pending'   => ['payment' => 'unpaid', 'ticket' => 'reserved'],
        'cancelled' => ['payment' => null, 'ticket' => 'cancelled'],
        'refunded'  => ['payment' => 'refunded', 'ticket' => 'cancelled'],
    ];
    if (!isset($map[$status])) {
        throw new InvalidArgumentException('Unknown status');
    }
    db_transaction(function () use ($orderId, $status, $map) {
        $o = db_one('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
        if (!$o || $o['status'] === 'failed') {
            return;
        }
        // re-activating a cancelled order is only possible if its seats are still free
        if (in_array($o['status'], ['cancelled', 'refunded'], true) && $map[$status]['ticket'] !== 'cancelled') {
            $conflict = db_value("SELECT COUNT(*) FROM tickets t JOIN tickets t2 ON t2.event_id = t.event_id AND t2.seat_id = t.seat_id
                AND t2.id <> t.id AND t2.status <> 'cancelled' WHERE t.order_id = ?", [$orderId]);
            if ($conflict) {
                throw new RuntimeException('seat_conflict');
            }
        }
        $pay = $map[$status]['payment'] ?? $o['payment_status'];
        db_exec('UPDATE orders SET status = ?, payment_status = ?, paid_at = IF(? = \'paid\' AND paid_at IS NULL, NOW(), paid_at),
            expires_at = IF(? IN (\'paid\',\'cancelled\',\'refunded\'), NULL, expires_at) WHERE id = ?',
            [$status, $pay, $status, $status, $orderId]);
        $ticket = $map[$status]['ticket'];
        // "used" tickets stay used when an order is marked paid again
        db_exec("UPDATE tickets SET status = ? WHERE order_id = ? AND NOT (status = 'used' AND ? = 'valid')", [$ticket, $orderId, $ticket]);
    });
}

function get_order(int $orderId, ?int $userId = null): ?array
{
    $sql = 'SELECT * FROM orders WHERE id = ?' . ($userId !== null ? ' AND user_id = ?' : '');
    $o = db_one($sql, $userId !== null ? [$orderId, $userId] : [$orderId]);
    if (!$o) {
        return null;
    }
    $o['event'] = get_event_by_id((int) $o['event_id'], true);
    $o['tickets'] = get_tickets(['order_id' => $orderId]);
    return $o;
}

function get_tickets(array $f): array
{
    [$tSel, $tJoin] = tr_sql('event_translations', 'event_id', 'e', ['title' => 'event_title'], 'et');
    [$vSel, $vJoin] = tr_sql('venue_translations', 'venue_id', 'v', ['name' => 'venue_name'], 'vt');
    [$ttSel, $ttJoin] = tr_sql('ticket_type_translations', 'ticket_type_id', 'tt', ['name' => 'type_name'], 'ttt');
    $where = [];
    $p = [];
    if (!empty($f['order_id'])) {
        $where[] = 't.order_id = ?';
        $p[] = (int) $f['order_id'];
    }
    if (!empty($f['user_id'])) {
        $where[] = 'o.user_id = ?';
        $p[] = (int) $f['user_id'];
    }
    if (!empty($f['code'])) {
        $where[] = 't.ticket_code = ?';
        $p[] = $f['code'];
    }
    if (($f['when'] ?? '') === 'upcoming') {
        $where[] = 'e.starts_at >= NOW()';
    } elseif (($f['when'] ?? '') === 'past') {
        $where[] = 'e.starts_at < NOW()';
    }
    if (!empty($f['active'])) {
        $where[] = "t.status <> 'cancelled'";
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $order = ($f['when'] ?? '') === 'past' ? 'e.starts_at DESC' : 'e.starts_at ASC';
    return db_all("SELECT t.*, o.order_number, o.status AS order_status, o.payment_method, o.user_id, o.customer_name,
            oi.unit_price, oi.zone, e.slug AS event_slug, e.starts_at, e.duration_min, e.image AS event_image, e.status AS event_status,
            v.slug AS venue_slug, s.section, s.row_label, s.seat_number, s.seat_type, tt.code AS type_code,
            $tSel, $vSel, $ttSel
        FROM tickets t
        JOIN orders o ON o.id = t.order_id
        JOIN order_items oi ON oi.id = t.order_item_id
        JOIN ticket_types tt ON tt.id = oi.ticket_type_id
        JOIN events e ON e.id = t.event_id
        JOIN venues v ON v.id = e.venue_id
        LEFT JOIN venue_seats s ON s.id = t.seat_id
        $tJoin $vJoin $ttJoin
        $whereSql ORDER BY $order, t.id", $p);
}

function ticket_seat_text(array $t): string
{
    return $t['seat_id'] ? seat_label($t) : t('seat.general');
}


function qr_svg(string $code, int $size = 168): string
{
    $n = 25;
    $bits = '';
    $h = $code;
    while (strlen($bits) < $n * $n) {
        $h = hash('sha256', $h . 'qr', true);
        foreach (str_split($h) as $ch) {
            $bits .= str_pad(decbin(ord($ch)), 8, '0', STR_PAD_LEFT);
        }
    }
    $finder = static function (int $x, int $y) use ($n): ?bool {
        foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$fx, $fy]) {
            if ($x >= $fx - 1 && $x <= $fx + 7 && $y >= $fy - 1 && $y <= $fy + 7) {
                $dx = $x - $fx;
                $dy = $y - $fy;
                if ($dx < 0 || $dy < 0 || $dx > 6 || $dy > 6) {
                    return false;
                }
                $edge = $dx === 0 || $dy === 0 || $dx === 6 || $dy === 6;
                $core = $dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4;
                return $edge || $core;
            }
        }
        return null;
    };
    $rects = '';
    for ($y = 0; $y < $n; $y++) {
        for ($x = 0; $x < $n; $x++) {
            $f = $finder($x, $y);
            $on = $f ?? ($y === 6 || $x === 6 ? ($x + $y) % 2 === 0 : $bits[$y * $n + $x] === '1');
            if ($on) {
                $rects .= "<rect x=\"$x\" y=\"$y\" width=\"1\" height=\"1\"/>";
            }
        }
    }
    return '<svg class="qr" viewBox="-2 -2 29 29" width="' . $size . '" height="' . $size . '" role="img" aria-label="'
        . e(t('ticket.qr_alt', ['code' => $code])) . '" xmlns="http://www.w3.org/2000/svg"><rect x="-2" y="-2" width="29" height="29" fill="#fff"/><g fill="#0c2433">'
        . $rects . '</g></svg>';
}

function release_all_holds(int $userId): void
{
    db_exec('DELETE FROM reservations WHERE user_id = ?', [$userId]);
}

function get_user_orders(int $userId): array
{
    [$tSel, $tJoin] = tr_sql('event_translations', 'event_id', 'e', ['title' => 'event_title'], 'et');
    return db_all("SELECT o.*, e.slug AS event_slug, e.starts_at, $tSel,
        (SELECT COUNT(*) FROM tickets t WHERE t.order_id = o.id) AS ticket_count
        FROM orders o JOIN events e ON e.id = o.event_id $tJoin WHERE o.user_id = ? ORDER BY o.created_at DESC", [$userId]);
}


function best_available_seats(array $event, int $n, int $userId): array
{
    $map = array_values(array_filter(event_seat_map($event, $userId), static fn($s) => in_array($s['st'], ['available', 'mine'], true) && $s['t'] !== 'accessible'));
    if (count($map) < $n) {
        return [];
    }
    $secRank = ['C' => 0, 'P' => 0, 'T' => 0, 'A' => 1, 'B' => 1, 'L' => 2, 'R' => 2];
    $rows = [];
    foreach ($map as $s) {
        $rows[$s['s'] . '|' . $s['r']][] = $s;
    }
    $best = null;
    $bestScore = PHP_INT_MAX;
    foreach ($rows as $key => $seats) {
        usort($seats, static fn($a, $b) => $a['n'] <=> $b['n']);
        [$sec, $row] = explode('|', $key);
        for ($i = 0; $i + $n <= count($seats); $i++) {
            $run = array_slice($seats, $i, $n);
            if ($run[$n - 1]['n'] - $run[0]['n'] !== $n - 1) {
                continue;
            }
            $cx = array_sum(array_column($run, 'x')) / $n;
            $score = (int) $row * 100 + ($secRank[$sec] ?? 3) * 400 + (int) abs($cx - 500) / 10;
            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $run;
            }
        }
    }
    if (!$best) {
        usort($map, static fn($a, $b) => [(int) $a['r'], abs($a['x'] - 500)] <=> [(int) $b['r'], abs($b['x'] - 500)]);
        $best = array_slice($map, 0, $n);
    }
    return array_column($best, 'id');
}

function booking_event_or_fail(): array
{
    $slug = input('event');
    $event = $slug !== '' ? get_event_by_slug($slug) : null;
    if (!$event) {
        render_error_page(404, 'notfound');
    }
    require_login();
    booking_cleanup();
    $event = get_event_by_slug($slug); // fresh availability after cleanup
    if (!$event['can_buy']) {
        $msg = $event['is_sold_out'] ? 'booking.err_soldout' : ($event['is_past'] ? 'booking.err_past' : 'booking.err_closed');
        flash('error', t($msg));
        redirect(url('event.php', ['slug' => $event['slug']]));
    }
    return $event;
}

function booking_seats(array $event): array
{
    return $event['mode'] === 'seated' ? user_holds((int) $event['id'], user_id()) : [];
}
