<?php
/**
 * Генерално класиране — OpenF1 championship_drivers / championship_teams (beta).
 * Налично за сесии от тип „Race“; при други сесии се търси последното „Race“ за сезона.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

const OPENF1_BASE = 'https://api.openf1.org/v1';

/**
 * Надеждно GET към OpenF1: cURL (ако има), иначе file_get_contents.
 * Празно тяло при успешен отговор се третира като [] (някои стекове връщат "" вместо "[]").
 */
function openf1_get(string $path, array $query = []): ?array
{
    $qs = http_build_query($query);
    $url = OPENF1_BASE . $path . ($qs !== '' ? '?' . $qs : '');

    $raw = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: F1LiveBoard/1',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false) {
            $raw = null;
        } elseif ($code >= 400) {
            $raw = null;
        }
    }

    if ($raw === null) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 30,
                'header' => "Accept: application/json\r\nUser-Agent: F1LiveBoard/1\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $got = @file_get_contents($url, false, $ctx);
        $raw = $got !== false ? $got : null;
    }

    if ($raw === null) {
        return null;
    }

    $trim = trim((string) $raw);
    if ($trim === '') {
        return [];
    }

    $data = json_decode($trim, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    return is_array($data) ? $data : [];
}

/**
 * Редове с реален driver_number (OpenF1 понякога връща празни обекти).
 *
 * @param array<int, mixed> $rows
 * @return array<int, array<string, mixed>>
 */
function filter_valid_championship_driver_rows(array $rows): array
{
    $out = [];
    foreach ($rows as $r) {
        if (!is_array($r)) {
            continue;
        }
        $dn = (int) ($r['driver_number'] ?? 0);
        if ($dn <= 0) {
            continue;
        }
        $out[] = $r;
    }

    return $out;
}

/**
 * Всички главни „Race“ сесии за текущата и миналогодишната кампания, най-новата първа.
 *
 * @return list<int>
 */
function list_race_session_keys_newest_first(): array
{
    $keys = [];
    $year = (int) gmdate('Y');
    for ($dy = 0; $dy <= 1; $dy++) {
        $y = $year - $dy;
        $sessions = openf1_get('/sessions', ['year' => $y]);
        if (!is_array($sessions)) {
            continue;
        }
        foreach ($sessions as $s) {
            if (($s['session_name'] ?? '') !== 'Race') {
                continue;
            }
            if (!empty($s['is_cancelled'])) {
                continue;
            }
            $sk = (int) ($s['session_key'] ?? 0);
            if ($sk > 0) {
                $keys[] = ['sk' => $sk, 'end' => (string) ($s['date_end'] ?? '')];
            }
        }
    }
    usort($keys, static function ($a, $b) {
        return strcmp($b['end'], $a['end']);
    });
    $out = [];
    foreach ($keys as $k) {
        $out[] = $k['sk'];
    }

    return array_values(array_unique($out));
}

/**
 * Имена и отбори: за „Race“ session OpenF1 често връща празен drivers — допълваме от latest и meeting.
 *
 * @return array<int, array<string, mixed>>
 */
function build_driver_lookup(int $sessionKey, ?int $meetingKey): array
{
    $byNumber = [];
    $queries = [
        ['session_key' => (string) $sessionKey],
        ['session_key' => 'latest'],
    ];
    if ($meetingKey !== null && $meetingKey > 0) {
        $queries[] = ['meeting_key' => (string) $meetingKey];
    }
    foreach ($queries as $q) {
        $list = openf1_get('/drivers', $q);
        if (!is_array($list)) {
            continue;
        }
        foreach ($list as $d) {
            if (!is_array($d) || !isset($d['driver_number'])) {
                continue;
            }
            $n = (int) $d['driver_number'];
            if ($n <= 0) {
                continue;
            }
            if (!isset($byNumber[$n])) {
                $byNumber[$n] = $d;
            }
        }
    }

    return $byNumber;
}

/**
 * Ако класирането за избраната сесия е празно или фрагментирано, търси по-ново пълно от последните GP.
 *
 * @param array<int, array<string, mixed>> $driverRows
 * @return array{rows: array<int, array<string, mixed>>, session_key: int}
 */
function upgrade_championship_if_sparse(int $sessionKey, array $driverRows): array
{
    $minFullGrid = 12;
    if (count($driverRows) >= $minFullGrid) {
        return ['rows' => $driverRows, 'session_key' => $sessionKey];
    }

    $bestRows = $driverRows;
    $bestSk = $sessionKey;
    $bestCount = count($driverRows);

    $tried = 0;
    foreach (list_race_session_keys_newest_first() as $sk) {
        if (++$tried > 28) {
            break;
        }
        $raw = openf1_get('/championship_drivers', ['session_key' => (string) $sk]);
        if (!is_array($raw)) {
            continue;
        }
        $valid = filter_valid_championship_driver_rows($raw);
        $n = count($valid);
        if ($n > $bestCount) {
            $bestCount = $n;
            $bestRows = $valid;
            $bestSk = $sk;
        }
        if ($bestCount >= 18) {
            break;
        }
    }

    return ['rows' => $bestRows, 'session_key' => $bestSk];
}

function find_latest_main_race_session_key(): ?int
{
    $year = (int) gmdate('Y');
    for ($dy = 0; $dy <= 1; $dy++) {
        $y = $year - $dy;
        $sessions = openf1_get('/sessions', ['year' => $y]);
        if ($sessions === null) {
            continue;
        }
        $races = [];
        foreach ($sessions as $s) {
            if (($s['session_name'] ?? '') !== 'Race') {
                continue;
            }
            if (!empty($s['is_cancelled'])) {
                continue;
            }
            $races[] = $s;
        }
        usort($races, static function ($a, $b) {
            return strcmp((string) ($b['date_end'] ?? ''), (string) ($a['date_end'] ?? ''));
        });
        if ($races !== []) {
            $sk = (int) ($races[0]['session_key'] ?? 0);

            return $sk > 0 ? $sk : null;
        }
    }

    return null;
}

function resolve_championship_session_key(): ?int
{
    $latest = openf1_get('/championship_drivers', ['session_key' => 'latest']);
    if ($latest === null) {
        return find_latest_main_race_session_key();
    }
    if ($latest !== []) {
        $sk = (int) ($latest[0]['session_key'] ?? 0);

        return $sk > 0 ? $sk : find_latest_main_race_session_key();
    }

    return find_latest_main_race_session_key();
}

$sessionKey = resolve_championship_session_key();

if ($sessionKey === null) {
    echo json_encode([
        'ok' => true,
        'drivers' => [],
        'teams' => [],
        'session' => null,
        'message' => 'Не е открита подходяща състезателна сесия за класирането.',
        'source' => 'OpenF1 — https://openf1.org/',
        'fetched_at' => gmdate('c'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// session_key като низ — някои стекове с PHP http_build_query се държат по-добре с изричен string
$skParam = (string) $sessionKey;

$driverRows = openf1_get('/championship_drivers', ['session_key' => $skParam]);
if ($driverRows === null) {
    $driverRows = openf1_get('/championship_drivers', ['session_key' => 'latest']);
}
if ($driverRows === null) {
    $driverRows = [];
}

if ($driverRows === []) {
    $fallbackKey = find_latest_main_race_session_key();
    if ($fallbackKey !== null && $fallbackKey !== $sessionKey) {
        $sessionKey = $fallbackKey;
        $skParam = (string) $sessionKey;
        $retry = openf1_get('/championship_drivers', ['session_key' => $skParam]);
        $driverRows = is_array($retry) ? $retry : [];
    }
}

$driverRows = filter_valid_championship_driver_rows(is_array($driverRows) ? $driverRows : []);

$up = upgrade_championship_if_sparse($sessionKey, $driverRows);
$driverRows = $up['rows'];
$sessionKey = $up['session_key'];

if ($driverRows !== [] && isset($driverRows[0]['session_key'])) {
    $sessionKey = (int) $driverRows[0]['session_key'];
}

$teamRows = openf1_get('/championship_teams', ['session_key' => (string) $sessionKey]);
if ($teamRows === null) {
    $teamRows = openf1_get('/championship_teams', ['session_key' => 'latest']);
}
$teamRows = is_array($teamRows) ? $teamRows : [];

$sessions = openf1_get('/sessions', ['session_key' => (string) $sessionKey]);
$sessionInfo = (is_array($sessions) && isset($sessions[0])) ? $sessions[0] : null;

usort($driverRows, static function ($a, $b) {
    return ((int) ($a['position_current'] ?? 999)) <=> ((int) ($b['position_current'] ?? 999));
});

usort($teamRows, static function ($a, $b) {
    return ((int) ($a['position_current'] ?? 999)) <=> ((int) ($b['position_current'] ?? 999));
});

$meetingKey = isset($sessionInfo['meeting_key']) ? (int) $sessionInfo['meeting_key'] : null;
$byNumber = build_driver_lookup($sessionKey, $meetingKey > 0 ? $meetingKey : null);

$driversOut = [];
foreach ($driverRows as $row) {
    $dn = (int) ($row['driver_number'] ?? 0);
    $meta = $byNumber[$dn] ?? null;
    if ($meta === null && $dn > 0) {
        $meta = [
            'driver_number' => $dn,
            'full_name' => 'Пилот № ' . $dn,
            'broadcast_name' => '',
            'team_name' => '',
            'team_colour' => '666666',
        ];
    }
    $driversOut[] = [
        'championship' => $row,
        'driver' => $meta,
    ];
}

$message = null;
if ($driversOut === [] && $teamRows === []) {
    $message = 'Няма публикувани данни за генералното класиране (офсийзън или ограничение на API).';
}

echo json_encode([
    'ok' => true,
    'session_key' => $sessionKey,
    'session' => $sessionInfo ? [
        'session_name' => $sessionInfo['session_name'] ?? null,
        'circuit_short_name' => $sessionInfo['circuit_short_name'] ?? null,
        'country_name' => $sessionInfo['country_name'] ?? null,
        'year' => $sessionInfo['year'] ?? null,
        'date_end' => $sessionInfo['date_end'] ?? null,
        'meeting_key' => $sessionInfo['meeting_key'] ?? null,
    ] : null,
    'drivers' => $driversOut,
    'teams' => $teamRows,
    'message' => $message,
    'source' => 'OpenF1 — https://openf1.org/',
    'note' => 'Бета крайни точки championship_*; точността зависи от OpenF1.',
    'fetched_at' => gmdate('c'),
], JSON_UNESCAPED_UNICODE);
