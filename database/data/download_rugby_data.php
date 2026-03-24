<?php
/**
 * Download France international rugby test matches from octonion/rugby GitHub repository
 * and combine them into a single clean CSV.
 *
 * CSV columns (no header row):
 * [0] matchId, [1] description, [2] venueId, [3] venueName, [4] city, [5] country,
 * [6] time, [7] timeUtc, [8] date, [9] attendance, [10] homeTeamId, [11] homeTeamName,
 * [12] homeTeamAbbr, [13] awayTeamId, [14] awayTeamName, [15] awayTeamAbbr,
 * [16] homeScore, [17] awayScore, [18] status, [19] outcome, [20] eventId,
 * [21] eventName, [22] eventAbbr, ...
 */

$outputFile = __DIR__ . '/results_complete.csv';
$baseUrl = 'https://raw.githubusercontent.com/octonion/rugby/master/world_rugby/csv/matches_%d.csv';

// Column indices
define('COL_DATE', 8);
define('COL_ATTENDANCE', 9);
define('COL_HOME_TEAM', 11);
define('COL_HOME_ABBR', 12);
define('COL_AWAY_TEAM', 14);
define('COL_AWAY_ABBR', 15);
define('COL_HOME_SCORE', 16);
define('COL_AWAY_SCORE', 17);
define('COL_STATUS', 18);
define('COL_EVENT_NAME', 21);
define('COL_VENUE_NAME', 3);
define('COL_CITY', 4);
define('COL_COUNTRY', 5);

/**
 * Parse various date formats into YYYY-MM-DD
 */
function parseDate(string $raw): ?string
{
    $raw = trim($raw);

    // Already YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
        return $raw;
    }

    // Format: "Fri 1 Apr 2005, 13:15 GMT" or "Fri 1 Aug 2014, 20:45 GMT+02:00"
    // Extract date part before the comma or parse the whole thing
    $ts = strtotime($raw);
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }

    // Try removing timezone info
    $cleaned = preg_replace('/,?\s*\d{2}:\d{2}\s*(GMT[^\s]*)?\s*$/', '', $raw);
    $ts = strtotime($cleaned);
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }

    return null;
}

/**
 * Check if a competition name indicates a non-senior team (women, U20, U19, 7s, etc.)
 */
function isNonSeniorCompetition(string $eventName): bool
{
    return (bool) preg_match('/Women|Under\s*\d|U\d{2}\b|Junior|7s|Sevens|Youth|Girls/i', $eventName);
}

$allMatches = [];
$skippedYears = [];
$downloadedYears = [];
$filteredNonSenior = 0;
$badDateCount = 0;

echo "Downloading France rugby match data (1906-2025)...\n";

for ($year = 1906; $year <= 2025; $year++) {
    $url = sprintf($baseUrl, $year);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || empty($content)) {
        $skippedYears[] = $year;
        continue;
    }

    $lines = explode("\n", $content);
    $yearCount = 0;

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        $fields = str_getcsv($line);

        // Need at least 22 columns
        if (count($fields) < 22) continue;

        $homeTeam = trim($fields[COL_HOME_TEAM]);
        $awayTeam = trim($fields[COL_AWAY_TEAM]);

        // Filter: exactly "France" (not "France 7s", "France U20", "France A", "France XV", etc.)
        if ($homeTeam !== 'France' && $awayTeam !== 'France') {
            continue;
        }

        // Skip non-completed matches
        $status = trim($fields[COL_STATUS]);
        if ($status !== 'C') continue;

        $eventName = trim($fields[COL_EVENT_NAME]);

        // Filter out non-senior competitions
        if (isNonSeniorCompetition($eventName)) {
            $filteredNonSenior++;
            continue;
        }

        // Parse date
        $rawDate = trim($fields[COL_DATE]);
        $date = parseDate($rawDate);
        if ($date === null) {
            $badDateCount++;
            // Try to reconstruct from the year
            echo "  WARNING: Unparseable date '$rawDate' for $homeTeam vs $awayTeam ($eventName)\n";
            continue;
        }

        $worldCup = (stripos($eventName, 'World Cup') !== false) ? 'True' : 'False';

        $allMatches[] = [
            'date' => $date,
            'home_team' => $homeTeam,
            'away_team' => $awayTeam,
            'home_score' => trim($fields[COL_HOME_SCORE]) ?: '0',
            'away_score' => trim($fields[COL_AWAY_SCORE]) ?: '0',
            'competition' => $eventName,
            'stadium' => trim($fields[COL_VENUE_NAME]),
            'city' => trim($fields[COL_CITY]),
            'country' => trim($fields[COL_COUNTRY]),
            'neutral' => 'False',
            'world_cup' => $worldCup,
        ];

        $yearCount++;
    }

    if ($yearCount > 0) {
        $downloadedYears[] = $year;
        echo "  $year: $yearCount matches\n";
    } else {
        $skippedYears[] = $year;
    }
}

// Sort by date
usort($allMatches, function ($a, $b) {
    return strcmp($a['date'], $b['date']);
});

// Remove duplicates (same date + same teams + same score)
$unique = [];
$duplicateCount = 0;
foreach ($allMatches as $match) {
    $key = $match['date'] . '|' . $match['home_team'] . '|' . $match['away_team'] . '|' . $match['home_score'] . '|' . $match['away_score'];
    if (!isset($unique[$key])) {
        $unique[$key] = $match;
    } else {
        $duplicateCount++;
    }
}
$allMatches = array_values($unique);

// Write CSV
$fp = fopen($outputFile, 'w');
fputcsv($fp, ['date', 'home_team', 'away_team', 'home_score', 'away_score', 'competition', 'stadium', 'city', 'country', 'neutral', 'world_cup']);

foreach ($allMatches as $match) {
    fputcsv($fp, array_values($match));
}

fclose($fp);

// Analysis
echo "\n=== RESULTS ===\n";
echo "Total France senior matches: " . count($allMatches) . "\n";
echo "Non-senior matches filtered out: $filteredNonSenior\n";
echo "Duplicates removed: $duplicateCount\n";
echo "Bad dates skipped: $badDateCount\n";
echo "Years with data: " . count($downloadedYears) . "\n";
echo "Years skipped (no data/404): " . count($skippedYears) . "\n";

if (count($allMatches) > 0) {
    echo "Date range: " . $allMatches[0]['date'] . " to " . end($allMatches)['date'] . "\n";
}
echo "Output file: $outputFile\n";

// List all unique opponents
$opponents = [];
foreach ($allMatches as $match) {
    $opp = ($match['home_team'] === 'France') ? $match['away_team'] : $match['home_team'];
    $opponents[$opp] = ($opponents[$opp] ?? 0) + 1;
}
arsort($opponents);

echo "\n=== ALL UNIQUE OPPONENTS (" . count($opponents) . " teams) ===\n";
foreach ($opponents as $team => $count) {
    printf("  %-30s %d matches\n", $team, $count);
}

// Separate national teams from provincial/club teams
$national = [];
$provincial = [];
$provincialPatterns = [
    '/Province|District|Province/i',
    '/^(ACT|Queensland|New South Wales|Victoria|Transvaal|Natal|Border|Otago|Wellington|Canterbury|Waikato|Southland|Taranaki|Manawatu|Counties|Hawke|Marlborough|Northland|Bay of Plenty|North (Auckland|Harbour)|King Country|Wairarapa|Nelson|South Canterbury|Griqualand|Free State|Western Province|Eastern|Northern|Seddon)/i',
    '/(Barbarians|Universities|Invitation|Selection|Select XV|Presidents|Clubs XV|Jaguars)/i',
    '/(Buenos Aires|Tucum|Cuyo|Rosario|San (Isidro|Juan)|Cordoba|Santa F|Interior|Western Union|Eastern Union)/i',
    '/^(Sydney|Glasgow|Edinburgh|Rhodesia|South West Africa|Combined|Kyushu|Western Japan|New York|British Columbia|Queensland Country)/i',
    '/(XV$|Blues$)/i',
    '/Junior Springboks|Montpellier/i',
];

foreach ($opponents as $team => $count) {
    $isProvincial = false;
    foreach ($provincialPatterns as $pattern) {
        if (preg_match($pattern, $team)) {
            $isProvincial = true;
            break;
        }
    }
    if ($isProvincial) {
        $provincial[$team] = $count;
    } else {
        $national[$team] = $count;
    }
}

echo "\n=== NATIONAL TEAMS ONLY (" . count($national) . " teams) ===\n";
foreach ($national as $team => $count) {
    printf("  %-30s %d matches\n", $team, $count);
}

echo "\n=== PROVINCIAL/CLUB/SELECTION TEAMS (" . count($provincial) . " teams) ===\n";
foreach ($provincial as $team => $count) {
    printf("  %-30s %d matches\n", $team, $count);
}

// List top competitions
$competitions = [];
foreach ($allMatches as $match) {
    $comp = $match['competition'] ?: '(empty)';
    $competitions[$comp] = ($competitions[$comp] ?? 0) + 1;
}
arsort($competitions);

echo "\n=== TOP 30 COMPETITIONS ===\n";
$i = 0;
foreach ($competitions as $comp => $count) {
    printf("  %-65s %d\n", $comp, $count);
    if (++$i >= 30) {
        echo "  ... and " . (count($competitions) - 30) . " more\n";
        break;
    }
}

// Compare with existing results.csv
$existingFile = __DIR__ . '/results.csv';
if (file_exists($existingFile)) {
    $existingLines = file($existingFile);
    $existingOpponents = [];
    $existingFranceCount = 0;
    foreach ($existingLines as $j => $line) {
        if ($j === 0) continue;
        $f = str_getcsv($line);
        if (count($f) < 3) continue;
        $home = trim($f[1]);
        $away = trim($f[2]);
        if ($home === 'France' || $away === 'France') {
            $existingFranceCount++;
            $opp = ($home === 'France') ? $away : $home;
            $existingOpponents[$opp] = true;
        }
    }

    echo "\n=== COMPARISON WITH EXISTING results.csv ===\n";
    echo "Existing results.csv: " . (count($existingLines) - 1) . " total rows, $existingFranceCount France matches\n";
    echo "New results_complete.csv: " . count($allMatches) . " France matches\n";
    echo "Existing unique opponents: " . count($existingOpponents) . " (" . implode(', ', array_keys($existingOpponents)) . ")\n";
    echo "New unique opponents: " . count($opponents) . "\n";

    $newOpponents = array_diff(array_keys($opponents), array_keys($existingOpponents));
    if (!empty($newOpponents)) {
        echo "\nNew opponents not in existing file (" . count($newOpponents) . "):\n";
        foreach ($newOpponents as $opp) {
            echo "  + $opp ({$opponents[$opp]} matches)\n";
        }
    }
}

echo "\nDone!\n";
