<?php
declare(strict_types=1);
/**
 * Formula 2 live/резултати (безплатен източник: TheSportsDB).
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

const TSD_BASE = 'https://www.thesportsdb.com/api/v1/json/3';
const F2_LEAGUE_ID = '4486';
const FIA_F2_DRIVER_STANDINGS = 'https://www.fiaformula2.com/Standings/Driver';
const FIA_F2_TEAM_STANDINGS = 'https://www.fiaformula2.com/Standings/Team';

function tsd_get(string $path, array $query = []): ?array
{
    $url = TSD_BASE . $path;
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    $raw = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: F1LiveBoard/1'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $code >= 400) {
            $raw = null;
        }
    }

    if ($raw === null) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 20,
                'header' => "Accept: application/json\r\nUser-Agent: F1LiveBoard/1\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $got = @file_get_contents($url, false, $ctx);
        if ($got !== false) {
            $raw = $got;
        }
    }

    if ($raw === null || trim($raw) === '') {
        return null;
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function http_get_text(string $url): ?string
{
    $raw = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => ['User-Agent: F1LiveBoard/1', 'Accept: text/html,*/*'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $code >= 400) {
            $raw = null;
        }
    }

    if ($raw === null) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 25,
                'header' => "User-Agent: F1LiveBoard/1\r\nAccept: text/html,*/*\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $got = @file_get_contents($url, false, $ctx);
        if ($got !== false) {
            $raw = $got;
        }
    }

    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }

    return $raw;
}

function extract_plain_lines(string $html): array
{
    $txt = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $txt = preg_replace("/\r\n?/", "\n", $txt) ?? $txt;
    $rawLines = explode("\n", $txt);
    $lines = [];
    foreach ($rawLines as $line) {
        $line = trim(preg_replace('/\s+/u', ' ', $line) ?? '');
        if ($line !== '') {
            $lines[] = $line;
        }
    }

    return $lines;
}

function normalize_f2_name(string $v): string
{
    $v = trim($v);
    // маха дублирано "NameName" от някои рендери
    if (preg_match('/^(.{3,})\1$/u', $v, $m)) {
        $v = $m[1];
    }
    return trim($v);
}

/**
 * @param array<int, string> $lines
 * @return array<int, array<string, string|int>>
 */
function parse_driver_standings_lines(array $lines): array
{
    $out = [];
    $n = count($lines);
    for ($i = 0; $i < $n - 2; $i++) {
        if (!preg_match('/^\d{1,2}$/', $lines[$i])) {
            continue;
        }
        $pos = (int) $lines[$i];
        if ($pos <= 0 || $pos > 40) {
            continue;
        }
        $name = $lines[$i + 1] ?? '';
        $ptsLine = $lines[$i + 2] ?? '';
        if (!preg_match('/[A-Za-z]/', $name)) {
            continue;
        }
        if (!preg_match('/^\d{1,4}$/', $ptsLine)) {
            continue;
        }
        $name = normalize_f2_name($name);
        $points = (int) $ptsLine;
        $out[] = ['pos' => $pos, 'name' => $name, 'team' => '', 'points' => $points];
        $i += 2;
    }

    return $out;
}

/**
 * @param array<int, string> $lines
 * @return array<int, array<string, string|int>>
 */
function parse_team_standings_lines(array $lines): array
{
    $out = [];
    $n = count($lines);
    for ($i = 0; $i < $n - 2; $i++) {
        if (!preg_match('/^\d{1,2}$/', $lines[$i])) {
            continue;
        }
        $pos = (int) $lines[$i];
        if ($pos <= 0 || $pos > 40) {
            continue;
        }
        $team = $lines[$i + 1] ?? '';
        $ptsLine = $lines[$i + 2] ?? '';
        if (!preg_match('/[A-Za-z]/', $team)) {
            continue;
        }
        if (!preg_match('/^\d{1,4}$/', $ptsLine)) {
            continue;
        }
        $team = normalize_f2_name($team);
        $points = (int) $ptsLine;
        $out[] = ['pos' => $pos, 'name' => $team, 'team' => '', 'points' => $points];
        $i += 2;
    }

    return $out;
}

/**
 * @return array<int, array<string, string|int>>
 */
function scrape_fia_standings(string $url, string $mode): array
{
    $html = http_get_text($url);
    if ($html === null) {
        return [];
    }
    $lines = extract_plain_lines($html);
    $rows = $mode === 'drivers'
        ? parse_driver_standings_lines($lines)
        : parse_team_standings_lines($lines);

    // премахва дублирани позиции, запазва първата срещната
    $seen = [];
    $out = [];
    foreach ($rows as $r) {
        $p = (int) ($r['pos'] ?? 0);
        if ($p <= 0 || isset($seen[$p])) {
            continue;
        }
        $seen[$p] = true;
        $out[] = $r;
    }

    usort($out, static function ($a, $b) {
        return ((int) ($a['pos'] ?? 999)) <=> ((int) ($b['pos'] ?? 999));
    });

    return $out;
}

function wiki_api_get(string $page): ?array
{
    $url = 'https://en.wikipedia.org/w/api.php?' . http_build_query([
        'action' => 'parse',
        'page' => $page,
        'prop' => 'text',
        'format' => 'json',
        'formatversion' => '2',
        'origin' => '*',
    ]);
    $raw = http_get_text($url);
    if ($raw === null) {
        return null;
    }
    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}

/**
 * @return array{drivers: array<int, array<string, string|int>>, teams: array<int, array<string, string|int>>}
 */
function scrape_wikipedia_f2_standings(int $year): array
{
    $pageCandidates = [
        $year . '_Formula_2_Championship',
        $year . '_FIA_Formula_2_Championship',
    ];

    $html = null;
    foreach ($pageCandidates as $p) {
        $data = wiki_api_get($p);
        if (isset($data['parse']['text']) && is_string($data['parse']['text'])) {
            $html = $data['parse']['text'];
            break;
        }
    }
    if ($html === null || trim($html) === '') {
        return ['drivers' => [], 'teams' => []];
    }

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $xp = new DOMXPath($doc);

    $driverRows = [];
    $teamRows = [];

    $tables = $xp->query('//table[contains(@class,"wikitable")]');
    if (!$tables) {
        return ['drivers' => [], 'teams' => []];
    }

    foreach ($tables as $table) {
        $tblText = strtolower(trim(preg_replace('/\s+/u', ' ', $table->textContent ?? '') ?? ''));
        $isDriver = str_contains($tblText, 'driver') && str_contains($tblText, 'points');
        $isTeam = (str_contains($tblText, 'constructor') || str_contains($tblText, 'team')) && str_contains($tblText, 'points');

        if (!$isDriver && !$isTeam) {
            continue;
        }

        $rows = $xp->query('.//tr', $table);
        if (!$rows) {
            continue;
        }

        $parsed = [];
        foreach ($rows as $tr) {
            $cells = $xp->query('./th|./td', $tr);
            if (!$cells || $cells->length < 3) {
                continue;
            }
            $vals = [];
            foreach ($cells as $c) {
                $vals[] = trim(preg_replace('/\s+/u', ' ', $c->textContent ?? '') ?? '');
            }
            $pos = (int) preg_replace('/\D+/', '', $vals[0] ?? '0');
            if ($pos <= 0 || $pos > 40) {
                continue;
            }
            $points = 0;
            $name = '';
            $team = '';
            // points: последната цифрова клетка
            for ($i = count($vals) - 1; $i >= 1; $i--) {
                if (preg_match('/^\d+$/', $vals[$i])) {
                    $points = (int) $vals[$i];
                    break;
                }
            }
            // name/team: първата смислена текстова клетка след позицията
            for ($i = 1; $i < count($vals); $i++) {
                if ($vals[$i] === '' || preg_match('/^\d+$/', $vals[$i])) {
                    continue;
                }
                if (!preg_match('/[A-Za-z]/', $vals[$i])) {
                    continue;
                }
                $name = $vals[$i];
                if (isset($vals[$i + 1]) && preg_match('/[A-Za-z]/', $vals[$i + 1])) {
                    $team = $vals[$i + 1];
                }
                break;
            }
            if ($name === '') {
                continue;
            }
            $parsed[] = ['pos' => $pos, 'name' => $name, 'team' => $team, 'points' => $points];
        }

        usort($parsed, static function ($a, $b) {
            return ((int) ($a['pos'] ?? 999)) <=> ((int) ($b['pos'] ?? 999));
        });

        if ($isDriver && $driverRows === [] && $parsed !== []) {
            $driverRows = $parsed;
        }
        if ($isTeam && $teamRows === [] && $parsed !== []) {
            $teamRows = $parsed;
        }
    }

    return ['drivers' => $driverRows, 'teams' => $teamRows];
}

function parse_event_timestamp(array $e): ?int
{
    $ts = $e['strTimestamp'] ?? null;
    if (is_string($ts) && $ts !== '') {
        $t = strtotime($ts . ' UTC');
        if ($t !== false) {
            return $t;
        }
    }

    $date = $e['dateEvent'] ?? '';
    $time = $e['strTime'] ?? '00:00:00';
    if (is_string($date) && $date !== '') {
        $t = strtotime(trim($date . ' ' . $time) . ' UTC');
        if ($t !== false) {
            return $t;
        }
    }

    return null;
}

$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) gmdate('Y');
if ($year < 2017 || $year > ((int) gmdate('Y') + 2)) {
    $year = (int) gmdate('Y');
}

$payload = tsd_get('/eventsseason.php', ['id' => F2_LEAGUE_ID, 's' => (string) $year]);
$events = is_array($payload['events'] ?? null) ? $payload['events'] : [];

if ($events === []) {
    echo json_encode([
        'ok' => false,
        'year' => $year,
        'error' => 'Няма върнати събития за F2 от TheSportsDB.',
        'source' => 'TheSportsDB',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

usort($events, static function ($a, $b) {
    return (parse_event_timestamp($a) ?? 0) <=> (parse_event_timestamp($b) ?? 0);
});

$now = time();
$live = [];
$past = [];
$next = [];

foreach ($events as $e) {
    $t = parse_event_timestamp($e);
    $status = strtolower((string) ($e['strStatus'] ?? ''));

    $isLiveByStatus = str_contains($status, 'live') || str_contains($status, 'progress');
    $isLiveByTime = $t !== null && $t <= $now && $now <= ($t + 3 * 3600);

    if ($isLiveByStatus || $isLiveByTime) {
        $live[] = $e;
    } elseif ($t !== null && $t < $now) {
        $past[] = $e;
    } else {
        $next[] = $e;
    }
}

usort($past, static function ($a, $b) {
    return (parse_event_timestamp($b) ?? 0) <=> (parse_event_timestamp($a) ?? 0);
});

$past = array_slice($past, 0, 8);
$next = array_slice($next, 0, 8);

$official = [
    'drivers' => FIA_F2_DRIVER_STANDINGS,
    'teams' => FIA_F2_TEAM_STANDINGS,
    'live_timing' => 'https://www.fiaformula2.com/Live-Timing',
];

$standDrivers = scrape_fia_standings(FIA_F2_DRIVER_STANDINGS, 'drivers');
$standTeams = scrape_fia_standings(FIA_F2_TEAM_STANDINGS, 'teams');
if ($standDrivers === [] || $standTeams === []) {
    $wiki = scrape_wikipedia_f2_standings($year);
    if ($standDrivers === [] && $wiki['drivers'] !== []) {
        $standDrivers = $wiki['drivers'];
    }
    if ($standTeams === [] && $wiki['teams'] !== []) {
        $standTeams = $wiki['teams'];
    }
}

echo json_encode([
    'ok' => true,
    'year' => $year,
    'source' => 'TheSportsDB + FIA Formula 2',
    'live_events' => $live,
    'recent_results' => $past,
    'upcoming_events' => $next,
    'official_standings_links' => $official,
    'standings_drivers' => $standDrivers,
    'standings_teams' => $standTeams,
    'note' => 'F2 календар/събития са от TheSportsDB. Генералните класирания (пилоти/отбори) се взимат от официалните таблици на FIA Formula 2 (безплатно, чрез парсване).',
    'fetched_at' => gmdate('c'),
], JSON_UNESCAPED_UNICODE);
