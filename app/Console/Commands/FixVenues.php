<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\RugbyMatch;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class FixVenues extends Command
{
    protected $signature = 'fix:venues
        {--dry-run : Lister les corrections sans les appliquer}
        {--delay=2 : Délai en secondes entre les requêtes HTTP}';

    protected $description = 'Corrige les stades mal attribués (ex: Twickenham pour des matches hors Angleterre) en se basant sur equipe-france.fr';

    private const BASE_URL = 'https://www.equipe-france.fr/rugby/masculin/';
    private const USER_AGENT = 'xvfrance.fr Venue Fix Bot / 1.0';

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
        'Fidji' => 'Fidji',
        'Samoa' => 'Samoa',
        'Tonga' => 'Tonga',
        'Géorgie' => 'Géorgie',
        'Japon' => 'Japon',
        'Namibie' => 'Namibie',
        'Portugal' => 'Portugal',
        'Uruguay' => 'Uruguay',
    ];

    private array $venueCache = [];
    private array $countryCache = [];
    private Collection $corrections;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $delay = (int) $this->option('delay');
        $this->corrections = collect();

        // Pré-charger les caches
        Venue::all()->each(fn (Venue $v) => $this->venueCache[mb_strtolower($v->name . '|' . $v->city)] = $v);
        Country::all()->each(fn (Country $c) => $this->countryCache[mb_strtolower($c->name)] = $c);

        // Trouver les matches mal attribués
        $twickenham = Venue::where('name', 'like', '%Twickenham%')->first();
        if (!$twickenham) {
            $this->error('Twickenham non trouvé en base.');
            return self::FAILURE;
        }

        $england = Country::where('code', 'ENG')->first();

        $wrongMatches = RugbyMatch::with(['opponent', 'venue'])
            ->where('venue_id', $twickenham->id)
            ->where('is_home', false)
            ->where('opponent_id', '!=', $england->id)
            ->orderBy('match_date')
            ->get();

        $this->info("Matches extérieurs à Twickenham hors Angleterre : {$wrongMatches->count()}");

        if ($wrongMatches->isEmpty()) {
            $this->info('Rien à corriger.');
            return self::SUCCESS;
        }

        // Grouper par année
        $byYear = $wrongMatches->groupBy(fn (RugbyMatch $m) => $m->match_date->year);

        $bar = $this->output->createProgressBar($byYear->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Démarrage...');

        foreach ($byYear as $year => $matches) {
            $bar->setMessage("Année {$year}");

            $this->processYear($year, $matches, $delay);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Afficher le résumé
        if ($this->corrections->isEmpty()) {
            $this->warn('Aucun lieu trouvé sur le site. Les stades restent inchangés.');
            return self::SUCCESS;
        }

        $found = $this->corrections->where('venue_name', '!=', null);
        $notFound = $this->corrections->where('venue_name', null);

        if ($found->isNotEmpty()) {
            $this->info("Corrections trouvées : {$found->count()}");
            $this->table(
                ['ID', 'Date', 'Adversaire', 'Score', 'Ancien stade', 'Nouveau stade', 'Ville'],
                $found->map(fn ($c) => [
                    $c['match']->id,
                    $c['match']->match_date->format('d/m/Y'),
                    $c['match']->opponent->name,
                    $c['match']->france_score . ' - ' . $c['match']->opponent_score,
                    'Twickenham',
                    $c['venue_name'],
                    $c['city'],
                ])->toArray()
            );
        }

        if ($notFound->isNotEmpty()) {
            $this->newLine();
            $this->warn("Aucun lieu trouvé sur le site pour {$notFound->count()} matches (stade mis à NULL) :");
            foreach ($notFound as $c) {
                $m = $c['match'];
                $this->line("  {$m->match_date->format('d/m/Y')} vs {$m->opponent->name} ({$m->france_score}-{$m->opponent_score})");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->info("[DRY-RUN] Aucune modification appliquée.");
            return self::SUCCESS;
        }

        $this->newLine();
        if (!$this->confirm("Appliquer ces {$this->corrections->count()} corrections ?")) {
            $this->info('Annulé.');
            return self::SUCCESS;
        }

        $this->applyCorrections();

        return self::SUCCESS;
    }

    private function processYear(int $year, Collection $matches, int $delay): void
    {
        // Fetch la page année pour récupérer les liens des matches
        $matchUrls = $this->fetchYearMatchUrls($year);

        if ($matchUrls === null) {
            // Erreur HTTP — mettre les matches à NULL
            foreach ($matches as $match) {
                $this->corrections->push([
                    'match' => $match,
                    'venue_name' => null,
                    'city' => null,
                    'venue_id' => null,
                ]);
            }
            return;
        }

        sleep($delay);

        foreach ($matches as $match) {
            // Trouver l'URL du match sur le site
            $url = $this->findMatchUrl($match, $matchUrls);

            if (!$url) {
                $this->corrections->push([
                    'match' => $match,
                    'venue_name' => null,
                    'city' => null,
                    'venue_id' => null,
                ]);
                continue;
            }

            // Fetch la page du match pour le lieu
            $venueInfo = $this->fetchMatchVenue($url);
            sleep($delay);

            if ($venueInfo) {
                $venue = $this->findOrCreateVenue($venueInfo, $match->opponent);
                $this->corrections->push([
                    'match' => $match,
                    'venue_name' => $venueInfo['name'] ?? $venueInfo['city'],
                    'city' => $venueInfo['city'],
                    'venue_id' => $venue?->id,
                ]);
            } else {
                $this->corrections->push([
                    'match' => $match,
                    'venue_name' => null,
                    'city' => null,
                    'venue_id' => null,
                ]);
            }
        }
    }

    private function fetchYearMatchUrls(int $year): ?array
    {
        $url = self::BASE_URL . $year;

        $response = Http::withUserAgent(self::USER_AGENT)
            ->withOptions(['allow_redirects' => false])
            ->timeout(30)
            ->get($url);

        if ($response->status() === 301 || $response->status() === 302) {
            // Année avec un seul match — le redirect EST l'URL du match
            $redirectUrl = $response->header('Location');
            if (str_starts_with($redirectUrl, '/')) {
                $redirectUrl = 'https://www.equipe-france.fr' . $redirectUrl;
            }
            return $this->parseSingleMatchUrl($redirectUrl, $year);
        }

        if ($response->failed()) {
            return null;
        }

        return $this->parseYearPageUrls($response->body(), $year);
    }

    private function parseYearPageUrls(string $html, int $year): array
    {
        $crawler = new Crawler($html);
        $results = [];

        $crawler->filter('li.match')->each(function (Crawler $node) use (&$results, $year) {
            $timeNode = $node->filter('time');
            if ($timeNode->count() === 0) {
                return;
            }
            $date = Carbon::parse($timeNode->attr('datetime'));
            if ($date->year !== $year) {
                return;
            }

            $linkNode = $node->filter('a');
            if ($linkNode->count() === 0) {
                return;
            }
            $href = $linkNode->attr('href');

            // Extraire les noms des équipes
            $homeName = '';
            $node->filter('.equipedom span')->each(function (Crawler $span) use (&$homeName) {
                $text = trim($span->text());
                if (!empty($text)) {
                    $homeName = $text;
                }
            });

            $awayName = '';
            $node->filter('.equipeext span')->each(function (Crawler $span) use (&$awayName) {
                $text = trim($span->text());
                if (!empty($text)) {
                    $awayName = $text;
                }
            });

            // Score
            $scoreNode = $node->filter('.scorenbr');
            $franceScore = null;
            $opponentScore = null;
            if ($scoreNode->count() > 0 && preg_match('/(\d+)\s*-\s*(\d+)/', $scoreNode->text(), $sm)) {
                $isFranceHome = $this->isFrance($homeName);
                $franceScore = $isFranceHome ? (int) $sm[1] : (int) $sm[2];
                $opponentScore = $isFranceHome ? (int) $sm[2] : (int) $sm[1];
                $opponentName = $isFranceHome ? $awayName : $homeName;
                $mappedOpponent = $this->countryNameMapping[$opponentName] ?? $opponentName;

                $results[] = [
                    'date' => $date->format('Y-m-d'),
                    'opponent' => $mappedOpponent,
                    'france_score' => $franceScore,
                    'opponent_score' => $opponentScore,
                    'url' => str_starts_with($href, '/') ? 'https://www.equipe-france.fr' . $href : $href,
                ];
            }
        });

        return $results;
    }

    private function parseSingleMatchUrl(string $redirectUrl, int $year): array
    {
        // Fetch la page du match pour obtenir les infos
        $response = Http::withUserAgent(self::USER_AGENT)->timeout(30)->get($redirectUrl);
        if ($response->failed()) {
            return [];
        }

        $crawler = new Crawler($response->body());

        // Parser la date
        $infoAdd = $crawler->filter('.info_add');
        if ($infoAdd->count() === 0) {
            return [];
        }

        $dateText = trim(strip_tags($infoAdd->html()));
        $monthMap = [
            'janvier' => 1, 'février' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'août' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'décembre' => 12,
        ];

        if (!preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})/', $dateText, $dm)) {
            return [];
        }
        $month = $monthMap[mb_strtolower($dm[2])] ?? null;
        if (!$month || (int) $dm[3] !== $year) {
            return [];
        }
        $date = Carbon::create((int) $dm[3], $month, (int) $dm[1]);

        // Score et équipes
        $scoreNode = $crawler->filter('.score_nbr');
        if ($scoreNode->count() === 0 || !preg_match('/(\d+)\s*-\s*(\d+)/', $scoreNode->text(), $sm)) {
            return [];
        }

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

        $isFranceHome = $this->isFrance($homeName);
        $opponentName = $isFranceHome ? $awayName : $homeName;
        $mappedOpponent = $this->countryNameMapping[$opponentName] ?? $opponentName;

        return [[
            'date' => $date->format('Y-m-d'),
            'opponent' => $mappedOpponent,
            'france_score' => $isFranceHome ? (int) $sm[1] : (int) $sm[2],
            'opponent_score' => $isFranceHome ? (int) $sm[2] : (int) $sm[1],
            'url' => $redirectUrl,
        ]];
    }

    private function findMatchUrl(RugbyMatch $match, array $siteMatches): ?string
    {
        $dbDate = $match->match_date->format('Y-m-d');
        $dbOpponent = mb_strtolower($match->opponent->name);

        foreach ($siteMatches as $site) {
            if ($site['date'] === $dbDate
                && mb_strtolower($site['opponent']) === $dbOpponent
                && $site['france_score'] === $match->france_score
                && $site['opponent_score'] === $match->opponent_score
            ) {
                return $site['url'];
            }
        }

        // Fallback : même date + même adversaire (score peut différer légèrement dans les anciennes sources)
        foreach ($siteMatches as $site) {
            if ($site['date'] === $dbDate && mb_strtolower($site['opponent']) === $dbOpponent) {
                return $site['url'];
            }
        }

        return null;
    }

    private function fetchMatchVenue(string $url): ?array
    {
        $response = Http::withUserAgent(self::USER_AGENT)->timeout(30)->get($url);
        if ($response->failed()) {
            return null;
        }

        $crawler = new Crawler($response->body());
        $infoAdd = $crawler->filter('.info_add');
        if ($infoAdd->count() === 0) {
            return null;
        }

        // Pattern 1 : lien stade <a href="/stade/...">Nom, Ville</a>
        $stadeLink = $infoAdd->filter('a[href*="/stade/"]');
        if ($stadeLink->count() > 0) {
            $text = trim($stadeLink->text());
            if (str_contains($text, ',')) {
                [$name, $city] = array_map('trim', explode(',', $text, 2));
                return ['name' => $name, 'city' => $city];
            }
            return ['name' => $text, 'city' => $text];
        }

        // Pattern 2 : <img class="imgpaystxt" ...> Ville (matches extérieurs)
        $html = $infoAdd->html();
        if (preg_match('/<img[^>]*class="imgpaystxt"[^>]*alt="([^"]*)"[^>]*>\s*(.+?)$/s', $html, $m)) {
            $city = trim(strip_tags($m[2]));
            if (!empty($city)) {
                return ['name' => null, 'city' => $city, 'country_hint' => trim($m[1])];
            }
        }

        return null;
    }

    private function findOrCreateVenue(array $venueInfo, Country $opponent): ?Venue
    {
        $city = $venueInfo['city'];
        $name = $venueInfo['name'];

        if (!$city) {
            return null;
        }

        // Chercher par nom exact si on a un nom de stade
        if ($name) {
            $key = mb_strtolower($name . '|' . $city);
            if (isset($this->venueCache[$key])) {
                return $this->venueCache[$key];
            }

            // Chercher en BDD par nom
            $venue = Venue::where('name', $name)->where('city', $city)->first();
            if ($venue) {
                $this->venueCache[$key] = $venue;
                return $venue;
            }
        }

        // Chercher par ville dans le pays adverse
        $venue = Venue::where('city', $city)->where('country_id', $opponent->id)->first();
        if ($venue) {
            return $venue;
        }

        // Chercher par ville (tout pays)
        $venue = Venue::where('city', $city)->first();
        if ($venue) {
            return $venue;
        }

        // Déterminer le pays du stade
        $countryId = $opponent->id;
        if (isset($venueInfo['country_hint'])) {
            $hintCountry = $this->countryCache[mb_strtolower($venueInfo['country_hint'])] ?? null;
            if ($hintCountry) {
                $countryId = $hintCountry->id;
            }
        }

        // Créer le stade
        $venueName = $name ?? $city;
        $venue = Venue::create([
            'name' => $venueName,
            'city' => $city,
            'country_id' => $countryId,
        ]);

        $key = mb_strtolower($venueName . '|' . $city);
        $this->venueCache[$key] = $venue;

        return $venue;
    }

    private function applyCorrections(): void
    {
        $updated = 0;
        $nullified = 0;

        foreach ($this->corrections as $correction) {
            $match = $correction['match'];
            $venueId = $correction['venue_id'];

            $match->update(['venue_id' => $venueId]);

            if ($venueId) {
                $updated++;
            } else {
                $nullified++;
            }
        }

        $this->info("{$updated} matches corrigés avec un nouveau stade.");
        if ($nullified > 0) {
            $this->info("{$nullified} matches mis à stade NULL (lieu introuvable sur le site).");
        }
    }

    private function isFrance(string $name): bool
    {
        return str_contains(mb_strtolower($name), 'france');
    }
}
