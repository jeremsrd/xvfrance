#!/usr/bin/env php
<?php
/**
 * Nettoie results_complete.csv pour ne garder que les tests internationaux
 * (sélections nationales uniquement, pas de clubs/provinces/A teams).
 */

$nationalTeams = [
    // Six Nations
    'France', 'England', 'Scotland', 'Wales', 'Ireland', 'Italy',
    // Southern Hemisphere
    'New Zealand', 'Australia', 'South Africa', 'Argentina',
    // Europe
    'Romania', 'Spain', 'Germany', 'Portugal', 'Georgia', 'Netherlands',
    'Belgium', 'Sweden', 'Czechoslovakia', 'Russia', 'Czech Republic',
    // Amériques
    'USA', 'Canada', 'Uruguay', 'Chile', 'Paraguay',
    // Pacifique
    'Fiji', 'Samoa', 'Tonga',
    // Afrique
    'Morocco', 'Tunisia', 'Namibia', 'Zimbabwe', 'Cote D\'Ivoire',
    'Ivory Coast', 'Kenya',
    // Asie
    'Japan', 'Kazakhstan', 'Hong Kong', 'South Korea', 'Korea',
    // Historiques / combinés
    'Great Britain', 'British & Irish Lions', 'British Isles',
    'Pacific Islanders', 'Rhodesia',
];

$nationalTeamsLower = array_map('strtolower', $nationalTeams);

$input = __DIR__ . '/results_complete.csv';
$output = __DIR__ . '/results_clean.csv';

$in = fopen($input, 'r');
$out = fopen($output, 'w');

$header = fgetcsv($in);
fputcsv($out, $header);

$kept = 0;
$skipped = 0;
$skippedTeams = [];

while (($row = fgetcsv($in)) !== false) {
    $data = array_combine($header, $row);
    $homeTeam = $data['home_team'];
    $awayTeam = $data['away_team'];

    // Déterminer l'adversaire de la France
    if ($homeTeam === 'France') {
        $opponent = $awayTeam;
    } else {
        $opponent = $homeTeam;
    }

    // Exclure les équipes A/B/XV/Development/Invitation
    if (preg_match('/\b(A|B|XV|Development|Invitation|Universities|Barbarians|Coloured|African XV|Jaguars|Presidents|Select|Country)\b/i', $opponent)) {
        $skippedTeams[$opponent] = ($skippedTeams[$opponent] ?? 0) + 1;
        $skipped++;
        continue;
    }

    // Vérifier si c'est une sélection nationale connue
    if (in_array(strtolower($opponent), $nationalTeamsLower)) {
        fputcsv($out, $row);
        $kept++;
    } else {
        $skippedTeams[$opponent] = ($skippedTeams[$opponent] ?? 0) + 1;
        $skipped++;
    }
}

fclose($in);
fclose($out);

echo "=== RÉSULTAT DU NETTOYAGE ===\n";
echo "Matches conservés : {$kept}\n";
echo "Matches exclus    : {$skipped}\n\n";

if (!empty($skippedTeams)) {
    arsort($skippedTeams);
    echo "Équipes exclues :\n";
    foreach ($skippedTeams as $team => $count) {
        echo "  {$team} ({$count} match" . ($count > 1 ? 'es' : '') . ")\n";
    }
}

// Vérification : lister les adversaires uniques gardés
$clean = fopen($output, 'r');
fgetcsv($clean); // skip header
$opponents = [];
while (($row = fgetcsv($clean)) !== false) {
    $data = array_combine($header, $row);
    $opp = $data['home_team'] === 'France' ? $data['away_team'] : $data['home_team'];
    $opponents[$opp] = ($opponents[$opp] ?? 0) + 1;
}
fclose($clean);

arsort($opponents);
echo "\n=== " . count($opponents) . " ADVERSAIRES CONSERVÉS ===\n";
foreach ($opponents as $team => $count) {
    echo "  {$team} : {$count} match" . ($count > 1 ? 'es' : '') . "\n";
}
