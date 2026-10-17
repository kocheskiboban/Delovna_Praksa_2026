<?php
require dirname(__DIR__) . '/includes/bootstrap.php';

$ev = get_event_by_slug(input('event', '', 'get'));
if (!$ev) {
    not_found();
}
$esc = static fn(string $s) => addcslashes(str_replace(["\r\n", "\n"], '\n', $s), ',;\\');
$start = strtotime($ev['starts_at']);
$end = $start + 60 * (int) $ev['duration_min'];
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $ev['slug'] . '.ics"');
echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Ohrid Summer Festival//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\n";
echo 'UID:event-' . (int) $ev['id'] . '@ohridsummer' . "\r\n";
echo 'DTSTAMP:' . gmdate('Ymd\THis\Z') . "\r\n";
echo 'DTSTART:' . gmdate('Ymd\THis\Z', $start) . "\r\nDTEND:" . gmdate('Ymd\THis\Z', $end) . "\r\n";
echo 'SUMMARY:' . $esc($ev['title']) . "\r\nLOCATION:" . $esc($ev['venue_name'] . ', Ohrid') . "\r\n";
echo 'DESCRIPTION:' . $esc(str_limit($ev['description'], 300) . ' ' . url('event.php', ['slug' => $ev['slug']], true)) . "\r\n";
echo "END:VEVENT\r\nEND:VCALENDAR\r\n";
