<?php
/**
 * OpenF1 aggregate endpoint — JSON за живо / последна сесия.
 * Документация: https://openf1.org/docs/
 *
 * Историческите данни са безплатни. За „най-живо“ време OpenF1 може да изисква абонамент — виж openf1.org.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const OPENF1_BASE = 'https://api.openf1.org/v1';

function openf1_get(string $path, array $query = []): ?array
{
    $qs = http_build_query($query);
    $url = OPENF1_BASE . $path . ($qs !== '' ? '?' . $qs : '');
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 20,
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

function latest_per_driver(array $rows, string $driverKey = 'driver_number', string $dateKey = 'date'): array
{
    $best = [];
    foreach ($rows as $row) {
        if (!isset($row[$driverKey], $row[$dateKey])) {
            continue;
        }
        $dn = (int) $row[$driverKey];
        $t = strtotime($row[$dateKey]) ?: 0;
        if (!isset($best[$dn]) || $t >= strtotime($best[$dn][$dateKey] ?? '') ?: 0) {
            $best[$dn] = $row;
        }
    }
    return array_values($best);
}

function session_is_race_like(?string $sessionName, ?string $sessionType): bool
{
    $n = strtolower((string) $sessionName . ' ' . (string) $sessionType);
    return str_contains($n, 'race') || str_contains($n, 'sprint');
}

$sessions = openf1_get('/sessions', ['session_key' => 'latest']);
if (!$sessions || !isset($sessions[0])) {
    echo json_encode([
        'ok' => false,
        'error' => 'Не може да се зареди информация за сесията от OpenF1.',
        'source' => OPENF1_BASE,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$session = $sessions[0];
$sessionKey = $session['session_key'] ?? null;
$meetingKey = $session['meeting_key'] ?? null;

$drivers = openf1_get('/drivers', ['session_key' => 'latest']) ?? [];
$driverByNum = [];
foreach ($drivers as $d) {
    if (isset($d['driver_number'])) {
        $driverByNum[(int) $d['driver_number']] = $d;
    }
}

$raceLike = session_is_race_like($session['session_name'] ?? null, $session['session_type'] ?? null);

$standings = [];
$mode = 'position';

if ($raceLike) {
    $intervals = openf1_get('/intervals', ['session_key' => 'latest']) ?? [];
    if ($intervals !== []) {
        $mode = 'intervals';
        $latestIv = latest_per_driver($intervals, 'driver_number', 'date');
        usort($latestIv, static function ($a, $b) {
            $ga = $a['gap_to_leader'] ?? null;
            $gb = $b['gap_to_leader'] ?? null;
            if ($ga === null && $gb === null) {
                return 0;
            }
            if ($ga === null) {
                return -1;
            }
            if ($gb === null) {
                return 1;
            }
            if ($ga === '+1 LAP' && $gb === '+1 LAP') {
                return 0;
            }
            if ($ga === '+1 LAP') {
                return 1;
            }
            if ($gb === '+1 LAP') {
                return -1;
            }
            return (float) $ga <=> (float) $gb;
        });
        $pos = 1;
        foreach ($latestIv as $row) {
            $dn = (int) $row['driver_number'];
            $standings[] = [
                'position' => $pos++,
                'driver_number' => $dn,
                'gap_to_leader' => $row['gap_to_leader'] ?? null,
                'interval' => $row['interval'] ?? null,
                'updated_at' => $row['date'] ?? null,
                'driver' => $driverByNum[$dn] ?? null,
            ];
        }
    }
}

if ($standings === []) {
    $positions = openf1_get('/position', ['session_key' => 'latest']) ?? [];
    $latestPos = latest_per_driver($positions, 'driver_number', 'date');
    usort($latestPos, static function ($a, $b) {
        return ((int) ($a['position'] ?? 999)) <=> ((int) ($b['position'] ?? 999));
    });
    $mode = 'position';
    foreach ($latestPos as $row) {
        $dn = (int) $row['driver_number'];
        $standings[] = [
            'position' => (int) ($row['position'] ?? 0),
            'driver_number' => $dn,
            'gap_to_leader' => null,
            'interval' => null,
            'updated_at' => $row['date'] ?? null,
            'driver' => $driverByNum[$dn] ?? null,
        ];
    }
}

// Последни съобщения от race control (ако има)
$raceControl = openf1_get('/race_control', ['session_key' => 'latest']);
if (is_array($raceControl)) {
    usort($raceControl, static function ($a, $b) {
        return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
    });
    $raceControl = array_slice($raceControl, 0, 12);
} else {
    $raceControl = [];
}

echo json_encode([
    'ok' => true,
    'source' => 'OpenF1 — https://openf1.org/',
    'session' => [
        'session_key' => $sessionKey,
        'meeting_key' => $meetingKey,
        'session_name' => $session['session_name'] ?? null,
        'session_type' => $session['session_type'] ?? null,
        'circuit_short_name' => $session['circuit_short_name'] ?? null,
        'location' => $session['location'] ?? null,
        'country_name' => $session['country_name'] ?? null,
        'date_start' => $session['date_start'] ?? null,
        'date_end' => $session['date_end'] ?? null,
        'year' => $session['year'] ?? null,
    ],
    'data_mode' => $mode,
    'standings' => $standings,
    'race_control' => $raceControl,
    'fetched_at' => gmdate('c'),
], JSON_UNESCAPED_UNICODE);
