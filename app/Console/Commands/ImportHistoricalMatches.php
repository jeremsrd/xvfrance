<?php

namespace App\Console\Commands;

use App\Enums\Continent;
use App\Enums\CompetitionType;
use App\Models\Competition;
use App\Models\CompetitionEdition;
use App\Models\Country;
use App\Models\RugbyMatch;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class ImportHistoricalMatches extends Command
{
    protected $signature = 'import:historical
        {--year= : Importer une seule année (ex: 1906)}
        {--dry-run : Affiche ce qui serait importé sans écrire en BDD}
        {--delay=2 : Délai en secondes entre les requêtes}';

    protected $description = 'Importe les matches historiques 1906-1949 depuis equipe-france.fr';

    private const BASE_URL = 'https://www.equipe-france.fr/rugby/masculin/';
    private const USER_AGENT = 'xvfrance.fr Historical Import Bot / 1.0';

    // Années sans matches internationaux
    private const SKIP_YEARS = [
        1915, 1916, 1917, 1918, 1919, // WWI
        1940, 1941, 1942, 1943, 1944, // WWII
    ];

    private array $countryCache = [];
    private array $competitionCache = [];
    private array $editionCache = [];

    private int $totalImported = 0;
    private int $totalDuplicates = 0;
    private int $totalFound = 0;
    private array $countriesCreated = [];
    private array $yearSummary = [];

    // Mapping noms français du site → noms français en BDD
    private array $countryNameMapping = [
        'France' => 'France',
        'Angleterre' => 'Angleterre',
        'Écosse' => 'Écosse',
        'Ecosse' => 'Écosse',
        'Pays de Galles' => 'Pays de Galles',
        'Irlande' => 'Irlande',
        'Italie' => 'Italie',
        'Nouvelle-Zélande' => 'Nouvelle-Zélande',
        'Australie' => 'Australie',
        'Afrique du Sud' => 'Afrique du Sud',
        'Argentine' => 'Argentine',
        'Roumanie' => 'Roumanie',
        'Allemagne' => 'Allemagne',
        'États-Unis' => 'États-Unis',
        'Etats-Unis' => 'États-Unis',
        'USA' => 'États-Unis',
        'Canada' => 'Canada',
        'Espagne' => 'Espagne',
        'Tchécoslovaquie' => 'Tchécoslovaquie',
        'Belgique' => 'Belgique',
        'Grande-Bretagne' => 'Grande-Bretagne',
        'Lions Britanniques' => 'Lions Britanniques',
        'Fidji' => 'Fidji',
        'Samoa' => 'Samoa',
        'Tonga' => 'Tonga',
        'Géorgie' => 'Géorgie',
        'Japon' => 'Japon',
        'Maroc' => 'Maroc',
        'Tunisie' => 'Tunisie',
        'Namibie' => 'Namibie',
        'Portugal' => 'Portugal',
        'Zimbabwe' => 'Zimbabwe',
        'Rhodésie' => 'Rhodésie',
        'Côte d\'Ivoire' => 'Côte d\'Ivoire',
        'Paraguay' => 'Paraguay',
        'Chili' => 'Chili',
        'Uruguay' => 'Uruguay',
        'Hong Kong' => 'Hong Kong',
        'Pacific Islanders' => 'Pacific Islanders',
    ];

    private array $countryCodeMapping = [
        'Angleterre' => 'ENG',
        'Écosse' => 'SCO',
        'Pays de Galles' => 'WAL',
        'Irlande' => 'IRL',
        'Italie' => 'ITA',
        'Nouvelle-Zélande' => 'NZL',
        'Australie' => 'AUS',
        'Afrique du Sud' => 'RSA',
        'Argentine' => 'ARG',
        'Roumanie' => 'ROU',
        'Allemagne' => 'GER',
        'États-Unis' => 'USA',
        'Canada' => 'CAN',
        'Espagne' => 'ESP',
        'Tchécoslovaquie' => 'TCH',
        'Belgique' => 'BEL',
        'Grande-Bretagne' => 'GBR',
        'Lions Britanniques' => 'BIL',
        'Fidji' => 'FIJ',
        'Samoa' => 'SAM',
        'Tonga' => 'TGA',
        'Géorgie' => 'GEO',
        'Japon' => 'JPN',
        'Maroc' => 'MAR',
        'Tunisie' => 'TUN',
        'Namibie' => 'NAM',
        'Portugal' => 'POR',
        'Zimbabwe' => 'ZIM',
        'Rhodésie' => 'RHO',
        'Côte d\'Ivoire' => 'CIV',
        'Paraguay' => 'PAR',
        'Chili' => 'CHI',
        'Uruguay' => 'URU',
        'Hong Kong' => 'HKG',
        'Pacific Islanders' => 'PAC',
    ];

    private array $continentMapping = [
        'ENG' => Continent::EUROPE,
        'SCO' => Continent::EUROPE,
        'WAL' => Continent::EUROPE,
        'IRL' => Continent::EUROPE,
        'ITA' => Continent::EUROPE,
        'GER' => Continent::EUROPE,
        'BEL' => Continent::EUROPE,
        'ESP' => Continent::EUROPE,
        'TCH' => Continent::EUROPE,
        'ROU' => Continent::EUROPE,
        'GBR' => Continent::EUROPE,
        'BIL' => Continent::EUROPE,
        'POR' => Continent::EUROPE,
        'GEO' => Continent::EUROPE,
        'NZL' => Continent::OCEANIE,
        'AUS' => Continent::OCEANIE,
        'FIJ' => Continent::OCEANIE,
        'SAM' => Continent::OCEANIE,
        'TGA' => Continent::OCEANIE,
        'PAC' => Continent::OCEANIE,
        'RSA' => Continent::AFRIQUE,
        'NAM' => Continent::AFRIQUE,
        'ZIM' => Continent::AFRIQUE,
        'RHO' => Continent::AFRIQUE,
        'CIV' => Continent::AFRIQUE,
        'MAR' => Continent::AFRIQUE,
        'TUN' => Continent::AFRIQUE,
        'ARG' => Continent::AMERIQUE_SUD,
        'URU' => Continent::AMERIQUE_SUD,
        'CHI' => Continent::AMERIQUE_SUD,
        'PAR' => Continent::AMERIQUE_SUD,
        'USA' => Continent::AMERIQUE_NORD,
        'CAN' => Continent::AMERIQUE_NORD,
        'JPN' => Continent::ASIE,
        'HKG' => Continent::ASIE,
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $delay = (int) $this->option('delay');
        $singleYear = $this->option('year') ? (int) $this->option('year') : null;

        if ($dryRun) {
            $this->info('MODE DRY-RUN — Aucune écriture en base de données');
            $this->newLine();
        }

        $this->preloadCaches();

        if ($singleYear) {
            $years = [$singleYear];
        } else {
            $years = $this->buildYearList();
        }

        $this->info('Années à scraper : ' . count($years));
        $this->newLine();

        foreach ($years as $i => $year) {
            if (in_array($year, self::SKIP_YEARS)) {
                continue;
            }

            $this->processYear($year, $dryRun);

            // Respecter le serveur
            if ($i < count($years) - 1) {
                sleep($delay);
            }
        }

        $this->displaySummary($dryRun);

        return self::SUCCESS;
    }

    private function buildYearList(): array
    {
        $years = [];
        for ($y = 1906; $y <= 1949; $y++) {
            if (!in_array($y, self::SKIP_YEARS)) {
                $years[] = $y;
            }
        }
        return $years;
    }

    private function processYear(int $year, bool $dryRun): void
    {
        $url = self::BASE_URL . $year;
        $this->info("--- {$year} ({$url}) ---");

        // Ne pas suivre les redirections automatiquement pour détecter les pages single-match
        $response = Http::withUserAgent(self::USER_AGENT)
            ->withOptions(['allow_redirects' => false])
            ->timeout(30)
            ->get($url);

        // Si redirection 301 → page d'un match unique
        if ($response->status() === 301 || $response->status() === 302) {
            $redirectUrl = $response->header('Location');
            $this->line("  Redirection vers match unique : {$redirectUrl}");
            $matches = $this->fetchSingleMatchPage($redirectUrl, $year);
        } elseif ($response->failed()) {
            $this->warn("  Erreur HTTP {$response->status()} pour {$year}, ignoré.");
            $this->yearSummary[] = [$year, 0, 0, 0, 'ERREUR HTTP ' . $response->status()];
            return;
        } else {
            $html = $response->body();
            $matches = $this->parseMatches($html, $year);
        }

        $found = count($matches);
        $imported = 0;
        $duplicates = 0;

        if ($found === 0) {
            $this->line("  Aucun match trouvé.");
            $this->yearSummary[] = [$year, 0, 0, 0, ''];
            return;
        }

        foreach ($matches as $match) {
            $this->line("  {$match['date']->format('d/m/Y')} : {$match['home_team']} {$match['home_score']} - {$match['away_score']} {$match['away_team']} ({$match['competition']})");

            $opponentName = $match['opponent'];
            $opponent = $this->findOrCreateCountry($opponentName, $dryRun);

            if (!$opponent) {
                $this->warn("    Pays '{$opponentName}' non trouvable, match ignoré.");
                continue;
            }

            // Vérifier doublon
            if (!$dryRun) {
                $exists = RugbyMatch::whereDate('match_date', $match['date']->format('Y-m-d'))
                    ->where('opponent_id', $opponent->id)
                    ->exists();

                if ($exists) {
                    $duplicates++;
                    $this->line("    → Doublon, ignoré.");
                    continue;
                }
            }

            $edition = $this->findOrCreateEdition($match['competition'], $year, $dryRun);

            if (!$dryRun) {
                RugbyMatch::create([
                    'match_date' => $match['date'],
                    'opponent_id' => $opponent->id,
                    'edition_id' => $edition?->id,
                    'france_score' => $match['france_score'],
                    'opponent_score' => $match['opponent_score'],
                    'is_home' => $match['is_home'],
                    'is_neutral' => false,
                ]);
            }

            $imported++;
        }

        $this->totalFound += $found;
        $this->totalImported += $imported;
        $this->totalDuplicates += $duplicates;
        $this->yearSummary[] = [$year, $found, $imported, $duplicates, ''];
    }

    private function fetchSingleMatchPage(string $redirectUrl, int $year): array
    {
        // Construire l'URL absolue si nécessaire
        if (str_starts_with($redirectUrl, '/')) {
            $redirectUrl = 'https://www.equipe-france.fr' . $redirectUrl;
        }

        $response = Http::withUserAgent(self::USER_AGENT)
            ->timeout(30)
            ->get($redirectUrl);

        if ($response->failed()) {
            $this->warn("  Erreur HTTP {$response->status()} pour la page match.");
            return [];
        }

        $html = $response->body();
        $crawler = new Crawler($html);

        // Parser la date depuis info_add : "5 janvier 1907"
        $dateText = '';
        $infoAdd = $crawler->filter('.info_add');
        if ($infoAdd->count() > 0) {
            $dateText = trim(strip_tags($infoAdd->html()));
        }

        if (empty($dateText)) {
            $this->warn("  Date introuvable sur la page match.");
            return [];
        }

        // Mois français → numéro
        $monthMap = [
            'janvier' => 1, 'février' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'août' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'décembre' => 12,
        ];

        if (!preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})/', $dateText, $dm)) {
            $this->warn("  Format de date non reconnu : {$dateText}");
            return [];
        }

        $month = $monthMap[mb_strtolower($dm[2])] ?? null;
        if (!$month) {
            $this->warn("  Mois non reconnu : {$dm[2]}");
            return [];
        }

        $date = Carbon::create((int) $dm[3], $month, (int) $dm[1]);

        if ($date->year !== $year) {
            return [];
        }

        // Parser le score depuis score_nbr
        $scoreNode = $crawler->filter('.score_nbr');
        if ($scoreNode->count() === 0) {
            $this->warn("  Score introuvable.");
            return [];
        }

        $scoreText = $scoreNode->text();
        if (!preg_match('/(\d+)\s*-\s*(\d+)/', $scoreText, $sm)) {
            $this->warn("  Format de score non reconnu : {$scoreText}");
            return [];
        }

        $homeScore = (int) $sm[1];
        $awayScore = (int) $sm[2];

        // Équipe domicile (div.dom) et extérieur (div.ext)
        $homeName = '';
        $domNode = $crawler->filter('.dom a');
        if ($domNode->count() > 0) {
            $homeName = trim(preg_replace('/\s+/', ' ', $domNode->text()));
        }

        $awayName = '';
        $extNode = $crawler->filter('.ext a');
        if ($extNode->count() > 0) {
            $awayName = trim(preg_replace('/\s+/', ' ', $extNode->text()));
        }

        // Compétition
        $competition = '';
        $competNode = $crawler->filter('.competition a');
        if ($competNode->count() > 0) {
            $competition = trim($competNode->text());
        }

        $isFranceHome = $this->isFrance($homeName);
        $opponentName = $isFranceHome ? $awayName : $homeName;
        $franceScore = $isFranceHome ? $homeScore : $awayScore;
        $opponentScore = $isFranceHome ? $awayScore : $homeScore;

        $mappedOpponent = $this->countryNameMapping[$opponentName] ?? $opponentName;

        return [[
            'date' => $date,
            'home_team' => $homeName,
            'away_team' => $awayName,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'france_score' => $franceScore,
            'opponent_score' => $opponentScore,
            'opponent' => $mappedOpponent,
            'is_home' => $isFranceHome,
            'competition' => $competition,
        ]];
    }

    private function parseMatches(string $html, int $year): array
    {
        $crawler = new Crawler($html);
        $matches = [];

        $crawler->filter('li.match')->each(function (Crawler $node) use (&$matches, $year) {
            try {
                $match = $this->parseMatchNode($node);
                if ($match && $match['date']->year === $year) {
                    $matches[] = $match;
                }
            } catch (\Exception $e) {
                $this->warn("  Erreur parsing match : {$e->getMessage()}");
            }
        });

        return $matches;
    }

    private function parseMatchNode(Crawler $node): ?array
    {
        // Date depuis <time datetime="...">
        $timeNode = $node->filter('time');
        if ($timeNode->count() === 0) {
            return null;
        }
        $datetime = $timeNode->attr('datetime');
        $date = Carbon::parse($datetime);

        // Compétition
        $competNode = $node->filter('.infocompet');
        $competition = $competNode->count() > 0 ? trim($competNode->text()) : '';

        // Équipe domicile
        $homeNode = $node->filter('.equipedom span');
        $homeName = '';
        $homeNode->each(function (Crawler $span) use (&$homeName) {
            $text = trim($span->text());
            if (!empty($text)) {
                $homeName = $text;
            }
        });

        // Équipe extérieur
        $awayNode = $node->filter('.equipeext span');
        $awayName = '';
        $awayNode->each(function (Crawler $span) use (&$awayName) {
            $text = trim($span->text());
            if (!empty($text)) {
                $awayName = $text;
            }
        });

        // Score depuis <span class="scorenbr">
        $scoreNode = $node->filter('.scorenbr');
        if ($scoreNode->count() === 0) {
            return null;
        }

        $scoreText = $scoreNode->text();
        // Format : "3 - 12" ou avec <strong>
        if (!preg_match('/(\d+)\s*-\s*(\d+)/', $scoreText, $scoreMatches)) {
            return null;
        }

        $homeScore = (int) $scoreMatches[1];
        $awayScore = (int) $scoreMatches[2];

        // Déterminer quel côté est la France
        $isFranceHome = $this->isFrance($homeName);
        $isFranceAway = $this->isFrance($awayName);

        if (!$isFranceHome && !$isFranceAway) {
            return null; // Match qui ne concerne pas la France
        }

        $opponentName = $isFranceHome ? $awayName : $homeName;
        $franceScore = $isFranceHome ? $homeScore : $awayScore;
        $opponentScore = $isFranceHome ? $awayScore : $homeScore;

        // is_home : France est domicile si elle est equipedom
        $isHome = $isFranceHome;

        // Mapper le nom de l'adversaire
        $mappedOpponent = $this->countryNameMapping[$opponentName] ?? $opponentName;

        return [
            'date' => $date,
            'home_team' => $homeName,
            'away_team' => $awayName,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'france_score' => $franceScore,
            'opponent_score' => $opponentScore,
            'opponent' => $mappedOpponent,
            'is_home' => $isHome,
            'competition' => $competition,
        ];
    }

    private function isFrance(string $name): bool
    {
        return str_contains(mb_strtolower($name), 'france');
    }

    private function preloadCaches(): void
    {
        Country::all()->each(function (Country $c) {
            $this->countryCache[mb_strtolower($c->name)] = $c;
        });

        Competition::all()->each(function (Competition $c) {
            $this->competitionCache[$c->short_name] = $c;
        });
    }

    private function findOrCreateCountry(string $name, bool $dryRun): ?Country
    {
        $key = mb_strtolower($name);

        if (isset($this->countryCache[$key])) {
            return $this->countryCache[$key];
        }

        $code = $this->countryCodeMapping[$name] ?? strtoupper(substr($name, 0, 3));
        $continent = $this->continentMapping[$code] ?? Continent::EUROPE;

        if ($dryRun) {
            $placeholder = new Country([
                'id' => 0,
                'name' => $name,
                'code' => $code,
                'continent' => $continent,
            ]);
            $this->countryCache[$key] = $placeholder;
            $this->countriesCreated[] = "{$name} ({$code})";
            return $placeholder;
        }

        // Vérifier par code pour éviter les doublons
        $existing = Country::where('code', $code)->first();
        if ($existing) {
            $this->countryCache[$key] = $existing;
            return $existing;
        }

        $country = Country::create([
            'name' => $name,
            'code' => $code,
            'continent' => $continent,
            'flag_emoji' => '',
        ]);

        $this->countryCache[$key] = $country;
        $this->countriesCreated[] = "{$name} ({$code})";

        return $country;
    }

    private function mapCompetition(string $competitionText): ?string
    {
        $lower = mb_strtolower($competitionText);

        if (str_contains($lower, 'cinq nations') || str_contains($lower, 'five nations')
            || str_contains($lower, 'home nations') || str_contains($lower, 'nations')) {
            return '5/6 Nations';
        }

        if (str_contains($lower, 'coupe du monde') || str_contains($lower, 'world cup')) {
            return 'Coupe du Monde';
        }

        if (str_contains($lower, 'jeux olympiques') || str_contains($lower, 'olympic')) {
            return 'Jeux Olympiques';
        }

        // Test match, match amical, etc. → pas de compétition
        return null;
    }

    private function findOrCreateEdition(string $competitionText, int $year, bool $dryRun): ?CompetitionEdition
    {
        $shortName = $this->mapCompetition($competitionText);
        if (!$shortName) {
            return null;
        }

        $cacheKey = "{$shortName}_{$year}";
        if (isset($this->editionCache[$cacheKey])) {
            return $this->editionCache[$cacheKey];
        }

        $competition = $this->competitionCache[$shortName] ?? null;

        // Créer la compétition si elle n'existe pas (ex: Jeux Olympiques)
        if (!$competition && !$dryRun) {
            $type = $shortName === 'Jeux Olympiques' ? CompetitionType::AUTRE : CompetitionType::TOURNOI;
            $competition = Competition::create([
                'name' => $shortName,
                'short_name' => $shortName,
                'type' => $type,
            ]);
            $this->competitionCache[$shortName] = $competition;
            $this->info("  Compétition créée : {$shortName}");
        }

        if (!$competition) {
            return null;
        }

        if ($dryRun) {
            $placeholder = new CompetitionEdition([
                'id' => 0,
                'competition_id' => $competition->id ?? 0,
                'year' => $year,
                'label' => "{$shortName} {$year}",
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

        $this->newLine(2);
        $this->info("{$prefix}=== RÉSUMÉ DE L'IMPORT HISTORIQUE ===");
        $this->newLine();

        $this->table(
            ['Année', 'Trouvés', 'Importés', 'Doublons', 'Note'],
            $this->yearSummary
        );

        $this->newLine();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Total matches trouvés', $this->totalFound],
                ['Total matches importés', $this->totalImported],
                ['Total doublons ignorés', $this->totalDuplicates],
                ['Pays créés', count($this->countriesCreated)],
            ]
        );

        if (!empty($this->countriesCreated)) {
            $this->newLine();
            $this->warn("{$prefix}Pays créés automatiquement :");
            foreach (array_unique($this->countriesCreated) as $c) {
                $this->line("  → {$c}");
            }
        }

        if (!$dryRun) {
            $this->newLine();
            $totalMatches = RugbyMatch::count();
            $this->info("Total matches en BDD : {$totalMatches}");
        }
    }
}
