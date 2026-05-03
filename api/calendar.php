<?php
/**
 * F1 календар по сезон — OpenF1 meetings + sessions.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

const OPENF1_BASE = 'https://api.openf1.org/v1';

function openf1_get(string $path, array $query = []): ?array
{
    $qs = http_build_query($query);
    $url = OPENF1_BASE . $path . ($qs !== '' ? '?' . $qs : '');
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 25,
            'header' => "Accept: application/json\r\nUser-Agent: F1LiveBoard/1\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || $raw === '') {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

$y = isset($_GET['year']) ? (int) $_GET['year'] : (int) gmdate('Y');
if ($y < 2023 || $y > 2035) {
    $y = (int) gmdate('Y');
}

$meetings = openf1_get('/meetings', ['year' => $y]);
if ($meetings === null) {
    echo json_encode([
        'ok' => false,
        'year' => $y,
        'error' => 'Не може да се зареди календарът от OpenF1.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sessions = openf1_get('/sessions', ['year' => $y]) ?? [];

$sessionsByMeeting = [];
foreach ($sessions as $s) {
    $mk = $s['meeting_key'] ?? null;
    if ($mk === null) {
        continue;
    }
    if (!isset($sessionsByMeeting[$mk])) {
        $sessionsByMeeting[$mk] = [];
    }
    $sessionsByMeeting[$mk][] = $s;
}

foreach ($sessionsByMeeting as $mk => &$list) {
    usort($list, static function ($a, $b) {
        return strcmp((string) ($a['date_start'] ?? ''), (string) ($b['date_start'] ?? ''));
    });
}
unset($list);

usort($meetings, static function ($a, $b) {
    return strcmp((string) ($a['date_start'] ?? ''), (string) ($b['date_start'] ?? ''));
});

$rounds = [];
foreach ($meetings as $m) {
    $mk = $m['meeting_key'] ?? null;
    $rounds[] = [
        'meeting' => $m,
        'sessions' => $mk !== null ? ($sessionsByMeeting[$mk] ?? []) : [],
    ];
}

echo json_encode([
    'ok' => true,
    'year' => $y,
    'source' => 'OpenF1 — https://openf1.org/',
    'rounds' => $rounds,
    'fetched_at' => gmdate('c'),
], JSON_UNESCAPED_UNICODE);
