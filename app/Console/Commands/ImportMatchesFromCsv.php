<?php

namespace App\Console\Commands;

use App\Enums\Continent;
use App\Models\Competition;
use App\Models\CompetitionEdition;
use App\Models\Country;
use App\Models\RugbyMatch;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ImportMatchesFromCsv extends Command
{
    protected $signature = 'import:matches
        {file : Chemin vers le fichier CSV}
        {--dry-run : Affiche ce qui serait importé sans écrire en BDD}
        {--from= : Année de début (ex: 1987)}
        {--to= : Année de fin (ex: 2024)}';

    protected $description = 'Importe les matches de la France depuis un CSV de résultats internationaux';

    private array $competitionCache = [];
    private array $editionCache = [];
    private array $countryCache = [];
    private array $venueCache = [];

    private int $imported = 0;
    private int $duplicates = 0;
    private array $countriesCreated = [];
    private array $venuesCreated = [];

    private array $countryNameMapping = [
        // Noms anglais → noms français (correspondant à la BDD)
        'England' => 'Angleterre',
        'Scotland' => 'Écosse',
        'Wales' => 'Pays de Galles',
        'Ireland' => 'Irlande',
        'Italy' => 'Italie',
        'Australia' => 'Australie',
        'Argentina' => 'Argentine',
        'New Zealand' => 'Nouvelle-Zélande',
        'South Africa' => 'Afrique du Sud',
        'United States' => 'États-Unis',
        'USA' => 'États-Unis',
        'Fiji' => 'Fidji',
        'Samoa' => 'Samoa',
        'Tonga' => 'Tonga',
        'Georgia' => 'Géorgie',
        'Romania' => 'Roumanie',
        'Japan' => 'Japon',
        'Canada' => 'Canada',
        'Namibia' => 'Namibie',
        'Uruguay' => 'Uruguay',
        'Spain' => 'Espagne',
        'Portugal' => 'Portugal',
        'Russia' => 'Russie',
        'Germany' => 'Allemagne',
        'Zimbabwe' => 'Zimbabwe',
        'British Isles' => 'Lions Britanniques',
        'British & Irish Lions' => 'Lions Britanniques',
        'Great Britain' => 'Grande-Bretagne',
        'USSR' => 'Union Soviétique',
        'Czechoslovakia' => 'Tchécoslovaquie',
        'Ivory Coast' => 'Côte d\'Ivoire',
        'Cote D\'Ivoire' => 'Côte d\'Ivoire',
        'Korea' => 'Corée du Sud',
        'South Korea' => 'Corée du Sud',
        'Hong Kong' => 'Hong Kong',
        'Chinese Taipei' => 'Taipei Chinois',
        'Sri Lanka' => 'Sri Lanka',
        'Cook Islands' => 'Îles Cook',
        'Papua New Guinea' => 'Papouasie-Nouvelle-Guinée',
        'Trinidad & Tobago' => 'Trinité-et-Tobago',
        'Northern Ireland' => 'Irlande',
        'United States' => 'États-Unis',
        'Morocco' => 'Maroc',
        'Tunisia' => 'Tunisie',
        'Netherlands' => 'Pays-Bas',
        'Belgium' => 'Belgique',
        'Sweden' => 'Suède',
        'Chile' => 'Chili',
        'Paraguay' => 'Paraguay',
        'Kazakhstan' => 'Kazakhstan',
        'Rhodesia' => 'Rhodésie',
        'Pacific Islanders' => 'Pacific Islanders',
        'Kenya' => 'Kenya',
        'Czech Republic' => 'République Tchèque',
    ];

    private array $countryCodeMapping = [
        'Nouvelle-Zélande' => 'NZL',
        'Afrique du Sud' => 'RSA',
        'États-Unis' => 'USA',
        'Lions Britanniques' => 'BIL',
        'Grande-Bretagne' => 'GBR',
        'Union Soviétique' => 'URS',
        'Tchécoslovaquie' => 'TCH',
        'Côte d\'Ivoire' => 'CIV',
        'Maroc' => 'MAR',
        'Tunisie' => 'TUN',
        'Pays-Bas' => 'NED',
        'Belgique' => 'BEL',
        'Suède' => 'SWE',
        'Chili' => 'CHI',
        'Rhodésie' => 'RHO',
        'République Tchèque' => 'CZE',
        'England' => 'ENG',
        'Scotland' => 'SCO',
        'Wales' => 'WAL',
        'Ireland' => 'IRL',
        'Italy' => 'ITA',
        'Australia' => 'AUS',
        'Argentina' => 'ARG',
        'Fiji' => 'FIJ',
        'Samoa' => 'SAM',
        'Tonga' => 'TGA',
        'Georgia' => 'GEO',
        'Romania' => 'ROU',
        'Japan' => 'JPN',
        'Canada' => 'CAN',
        'Namibia' => 'NAM',
        'Uruguay' => 'URU',
        'Spain' => 'ESP',
        'Portugal' => 'POR',
        'Russia' => 'RUS',
        'Germany' => 'GER',
        'Zimbabwe' => 'ZIM',
        'Corée du Sud' => 'KOR',
        'Hong Kong' => 'HKG',
        'Chile' => 'CHI',
        'Kenya' => 'KEN',
        'Madagascar' => 'MAD',
        'Morocco' => 'MAR',
        'Netherlands' => 'NED',
        'Belgium' => 'BEL',
        'Czech Republic' => 'CZE',
        'Paraguay' => 'PAR',
        'Peru' => 'PER',
        'Brazil' => 'BRA',
        'Tunisia' => 'TUN',
        'Ukraine' => 'UKR',
        'Croatia' => 'CRO',
        'Serbia' => 'SRB',
        'Kazakhstan' => 'KAZ',
        'Sweden' => 'SWE',
        'Pacific Islanders' => 'PAC',
    ];

    private array $continentMapping = [
        'ENG' => Continent::EUROPE,
        'SCO' => Continent::EUROPE,
        'WAL' => Continent::EUROPE,
        'IRL' => Continent::EUROPE,
        'ITA' => Continent::EUROPE,
        'GEO' => Continent::EUROPE,
        'ROU' => Continent::EUROPE,
        'RUS' => Continent::EUROPE,
        'ESP' => Continent::EUROPE,
        'POR' => Continent::EUROPE,
        'GER' => Continent::EUROPE,
        'BEL' => Continent::EUROPE,
        'NED' => Continent::EUROPE,
        'CZE' => Continent::EUROPE,
        'URS' => Continent::EUROPE,
        'TCH' => Continent::EUROPE,
        'UKR' => Continent::EUROPE,
        'CRO' => Continent::EUROPE,
        'SRB' => Continent::EUROPE,
        'NZL' => Continent::OCEANIE,
        'AUS' => Continent::OCEANIE,
        'FIJ' => Continent::OCEANIE,
        'SAM' => Continent::OCEANIE,
        'TGA' => Continent::OCEANIE,
        'RSA' => Continent::AFRIQUE,
        'NAM' => Continent::AFRIQUE,
        'ZIM' => Continent::AFRIQUE,
        'CIV' => Continent::AFRIQUE,
        'KEN' => Continent::AFRIQUE,
        'MAD' => Continent::AFRIQUE,
        'MAR' => Continent::AFRIQUE,
        'TUN' => Continent::AFRIQUE,
        'ARG' => Continent::AMERIQUE_SUD,
        'URU' => Continent::AMERIQUE_SUD,
        'CHI' => Continent::AMERIQUE_SUD,
        'BRA' => Continent::AMERIQUE_SUD,
        'PAR' => Continent::AMERIQUE_SUD,
        'PER' => Continent::AMERIQUE_SUD,
        'USA' => Continent::AMERIQUE_NORD,
        'CAN' => Continent::AMERIQUE_NORD,
        'JPN' => Continent::ASIE,
        'KOR' => Continent::ASIE,
        'HKG' => Continent::ASIE,
        'BIL' => Continent::EUROPE,
        'GBR' => Continent::EUROPE,
        'MAR' => Continent::AFRIQUE,
        'SWE' => Continent::EUROPE,
        'RHO' => Continent::AFRIQUE,
        'PAC' => Continent::OCEANIE,
        'KAZ' => Continent::ASIE,
        'PAR' => Continent::AMERIQUE_SUD,
    ];

    public function handle(): int
    {
        $file = $this->argument('file');
        $dryRun = $this->option('dry-run');
        $fromYear = $this->option('from') ? (int) $this->option('from') : null;
        $toYear = $this->option('to') ? (int) $this->option('to') : null;

        if (!file_exists($file)) {
            $this->error("Fichier introuvable : {$file}");
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('🔍 MODE DRY-RUN — Aucune écriture en base de données');
            $this->newLine();
        }

        $this->preloadCaches();

        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);

        $this->info("Colonnes détectées : " . implode(', ', $headers));
        $this->newLine();

        $bar = $this->output->createProgressBar();
        $bar->start();

        $lineNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            $data = array_combine($headers, $row);

            if ($data['home_team'] !== 'France' && $data['away_team'] !== 'France') {
                continue;
            }

            $matchDate = Carbon::parse($data['date']);
            $year = $matchDate->year;

            if ($fromYear && $year < $fromYear) {
                continue;
            }
            if ($toYear && $year > $toYear) {
                continue;
            }

            $isFranceHome = $data['home_team'] === 'France';
            $opponentName = $isFranceHome ? $data['away_team'] : $data['home_team'];
            $franceScore = $isFranceHome ? (int) $data['home_score'] : (int) $data['away_score'];
            $opponentScore = $isFranceHome ? (int) $data['away_score'] : (int) $data['home_score'];
            $isNeutral = strtolower($data['neutral'] ?? 'false') === 'true';
            $isHome = $isFranceHome && !$isNeutral;

            $opponent = $this->findOrCreateCountry($opponentName, $dryRun);
            if (!$opponent) {
                $this->warn("Ligne {$lineNumber}: Impossible de gérer le pays '{$opponentName}', ignoré.");
                continue;
            }

            // Vérifier doublon
            if (!$dryRun) {
                $exists = RugbyMatch::whereDate('match_date', $matchDate->format('Y-m-d'))
                    ->where('opponent_id', $opponent->id)
                    ->exists();
                if ($exists) {
                    $this->duplicates++;
                    $bar->advance();
                    continue;
                }
            }

            $venue = $this->findOrCreateVenue(
                $data['stadium'] ?? null,
                $data['city'] ?? null,
                $data['country'] ?? null,
                $dryRun
            );

            $edition = $this->findOrCreateEdition($data['competition'] ?? '', $year, $dryRun);

            $stage = $this->mapWorldCupStage($data['competition'] ?? '');

            if (!$dryRun) {
                RugbyMatch::create([
                    'match_date' => $matchDate,
                    'venue_id' => $venue?->id,
                    'opponent_id' => $opponent->id,
                    'edition_id' => $edition?->id,
                    'france_score' => $franceScore,
                    'opponent_score' => $opponentScore,
                    'is_home' => $isHome,
                    'is_neutral' => $isNeutral,
                    'stage' => $stage,
                ]);
            }

            $this->imported++;
            $bar->advance();
        }

        fclose($handle);
        $bar->finish();
        $this->newLine(2);

        $this->displaySummary($dryRun);

        return self::SUCCESS;
    }

    private function preloadCaches(): void
    {
        Country::all()->each(function (Country $c) {
            $this->countryCache[strtolower($c->name)] = $c;
        });

        Competition::all()->each(function (Competition $c) {
            $this->competitionCache[$c->short_name] = $c;
        });

        Venue::all()->each(function (Venue $v) {
            $this->venueCache[strtolower($v->name)] = $v;
        });
    }

    private function findOrCreateCountry(string $name, bool $dryRun): ?Country
    {
        $mappedName = $this->countryNameMapping[$name] ?? $name;
        $key = strtolower($mappedName);

        if (isset($this->countryCache[$key])) {
            return $this->countryCache[$key];
        }

        // Chercher aussi par le nom anglais original
        $keyOriginal = strtolower($name);
        if (isset($this->countryCache[$keyOriginal])) {
            return $this->countryCache[$keyOriginal];
        }

        $code = $this->countryCodeMapping[$mappedName]
            ?? $this->countryCodeMapping[$name]
            ?? strtoupper(substr($name, 0, 3));

        $continent = $this->continentMapping[$code] ?? Continent::EUROPE;

        if ($dryRun) {
            $placeholder = new Country([
                'id' => 0,
                'name' => $mappedName,
                'code' => $code,
                'continent' => $continent,
            ]);
            $this->countryCache[$key] = $placeholder;
            $this->countriesCreated[] = "{$mappedName} ({$code})";
            return $placeholder;
        }

        // Vérifier si le code existe déjà pour éviter un conflit d'unicité
        $existing = Country::where('code', $code)->first();
        if ($existing) {
            $this->countryCache[$key] = $existing;
            return $existing;
        }

        $country = Country::create([
            'name' => $mappedName,
            'code' => $code,
            'continent' => $continent,
            'flag_emoji' => '',
        ]);

        $this->countryCache[$key] = $country;
        $this->countriesCreated[] = "{$mappedName} ({$code})";

        return $country;
    }

    private function findOrCreateVenue(?string $stadium, ?string $city, ?string $hostCountry, bool $dryRun): ?Venue
    {
        if (empty($stadium)) {
            return null;
        }

        $key = strtolower($stadium);

        if (isset($this->venueCache[$key])) {
            return $this->venueCache[$key];
        }

        // Chercher aussi par correspondance partielle pour les stades connus
        foreach ($this->venueCache as $cachedKey => $cachedVenue) {
            if (str_contains($key, $cachedKey) || str_contains($cachedKey, $key)) {
                $this->venueCache[$key] = $cachedVenue;
                return $cachedVenue;
            }
        }

        if ($dryRun) {
            $placeholder = new Venue(['id' => 0, 'name' => $stadium, 'city' => $city]);
            $this->venueCache[$key] = $placeholder;
            $this->venuesCreated[] = "{$stadium}" . ($city ? ", {$city}" : '');
            return $placeholder;
        }

        $countryId = null;
        if ($hostCountry) {
            $mappedHost = $this->countryNameMapping[$hostCountry] ?? $hostCountry;
            $hostKey = strtolower($mappedHost);
            $country = $this->countryCache[$hostKey] ?? null;
            if ($country && $country->id) {
                $countryId = $country->id;
            }
        }

        $venue = Venue::create([
            'name' => $stadium,
            'city' => $city ?: null,
            'country_id' => $countryId,
        ]);

        $this->venueCache[$key] = $venue;
        $this->venuesCreated[] = "{$stadium}" . ($city ? ", {$city}" : '');

        return $venue;
    }

    private function mapCompetition(string $csvCompetition): ?string
    {
        if (empty($csvCompetition)) {
            return null;
        }

        $lower = strtolower($csvCompetition);

        if (str_contains($lower, 'six nations') || str_contains($lower, 'five nations')
            || str_contains($lower, 'home nations') || str_contains($lower, '4 nations')) {
            return '5/6 Nations';
        }

        if (str_contains($lower, 'world cup')) {
            return 'Coupe du Monde';
        }

        if (str_contains($lower, 'autumn') || str_contains($lower, 'november')
            || str_contains($lower, 'end of year') || str_contains($lower, 'end-of-year')
            || str_contains($lower, 'end-of year')) {
            return "Tests d'automne";
        }

        if (str_contains($lower, 'summer') || str_contains($lower, 'june')
            || str_contains($lower, 'mid-year') || str_contains($lower, 'mid year')
            || str_contains($lower, 'tour')) {
            return "Tournée d'été";
        }

        if (str_contains($lower, 'test match') || str_contains($lower, 'test')) {
            return null;
        }

        if (str_contains($lower, 'latin') || str_contains($lower, 'fira')
            || str_contains($lower, 'european cup')) {
            return null;
        }

        return null;
    }

    private function mapWorldCupStage(string $csvCompetition): ?string
    {
        $lower = strtolower($csvCompetition);

        if (!str_contains($lower, 'world cup')) {
            return null;
        }

        if (str_contains($lower, 'final') && !str_contains($lower, 'semi') && !str_contains($lower, 'quarter') && !str_contains($lower, 'bronze')) {
            return 'finale';
        }
        if (str_contains($lower, 'semi')) {
            return 'demi';
        }
        if (str_contains($lower, 'quarter')) {
            return 'quart';
        }
        if (str_contains($lower, 'bronze') || str_contains($lower, 'third-place') || str_contains($lower, 'petite finale')) {
            return 'petite_finale';
        }
        if (str_contains($lower, 'pool') || str_contains($lower, 'group')) {
            return 'poule';
        }

        return null;
    }

    private function findOrCreateEdition(string $csvCompetition, int $year, bool $dryRun): ?CompetitionEdition
    {
        $shortName = $this->mapCompetition($csvCompetition);
        if (!$shortName) {
            return null;
        }

        $cacheKey = "{$shortName}_{$year}";
        if (isset($this->editionCache[$cacheKey])) {
            return $this->editionCache[$cacheKey];
        }

        $competition = $this->competitionCache[$shortName] ?? null;
        if (!$competition) {
            return null;
        }

        if ($dryRun) {
            $placeholder = new CompetitionEdition([
                'id' => 0,
                'competition_id' => $competition->id,
                'year' => $year,
                'label' => "{$competition->name} {$year}",
            ]);
            $this->editionCache[$cacheKey] = $placeholder;
            return $placeholder;
        }

        $edition = CompetitionEdition::firstOrCreate(
            ['competition_id' => $competition->id, 'year' => $year],
            ['label' => "{$competition->name} {$year}"]
        );

        $this->editionCache[$cacheKey] = $edition;
        return $edition;
    }

    private function displaySummary(bool $dryRun): void
    {
        $prefix = $dryRun ? '[DRY-RUN] ' : '';

        $this->info("{$prefix}=== RÉSUMÉ DE L'IMPORT ===");
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Matches importés', $this->imported],
                ['Doublons ignorés', $this->duplicates],
                ['Pays créés', count($this->countriesCreated)],
                ['Stades créés', count($this->venuesCreated)],
            ]
        );

        if (!empty($this->countriesCreated)) {
            $this->newLine();
            $this->warn("{$prefix}Pays créés automatiquement (à vérifier) :");
            foreach ($this->countriesCreated as $c) {
                $this->line("  → {$c}");
            }
        }

        if (!empty($this->venuesCreated)) {
            $this->newLine();
            $this->warn("{$prefix}Stades créés automatiquement :");
            foreach (array_unique($this->venuesCreated) as $v) {
                $this->line("  → {$v}");
            }
        }
    }
}
