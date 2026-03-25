<?php

namespace App\Console\Commands;

use App\Models\RugbyMatch;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class CleanFeminineMatches extends Command
{
    protected $signature = 'clean:feminine-matches
        {--dry-run : Lister les matches féminins sans les supprimer}
        {--year= : Vérifier une seule année}
        {--from=1950 : Année de début}
        {--to=2025 : Année de fin}
        {--delay=2 : Délai en secondes entre les requêtes HTTP}';

    protected $description = 'Identifie et supprime les matches féminins en comparant avec equipe-france.fr (source masculine)';

    private const BASE_URL = 'https://www.equipe-france.fr/rugby/masculin/';
    private const USER_AGENT = 'xvfrance.fr Cleanup Bot / 1.0';

    private const SKIP_YEARS = [
        1915, 1916, 1917, 1918, 1919,
        1940, 1941, 1942, 1943, 1944,
    ];

    // Mapping noms du site → noms en BDD (repris de ImportHistoricalMatches)
    private array $countryNameMapping = [
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
        'Russie' => 'Russie',
        'Pays-Bas' => 'Pays-Bas',
        'Suède' => 'Suède',
        'Kazakhstan' => 'Kazakhstan',
        'Corée du Sud' => 'Corée du Sud',
        'Croatie' => 'Croatie',
        'Serbie' => 'Serbie',
    ];

    private Collection $toDelete;
    private array $yearSummary = [];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $delay = (int) $this->option('delay');
        $this->toDelete = collect();

        if ($this->option('year')) {
            $years = [(int) $this->option('year')];
        } else {
            $from = (int) $this->option('from');
            $to = (int) $this->option('to');
            $years = range($from, $to);
        }

        $years = array_filter($years, fn ($y) => !in_array($y, self::SKIP_YEARS));

        $this->info($dryRun ? 'MODE DRY-RUN — aucune suppression' : 'Mode réel — les matches féminins seront supprimés après confirmation');
        $this->info('Années à vérifier : ' . count($years));
        $this->newLine();

        $bar = $this->output->createProgressBar(count($years));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Démarrage...');

        foreach ($years as $i => $year) {
            $bar->setMessage("Année {$year}");

            $this->processYear($year);

            $bar->advance();

            // Respecter le serveur
            if ($i < count($years) - 1) {
                sleep($delay);
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Afficher le résumé par année
        $summaryRows = array_filter($this->yearSummary, fn ($row) => $row[2] > 0);
        if (!empty($summaryRows)) {
            $this->table(['Année', 'En BDD', 'Féminins détectés', 'Masculins (site)'], $summaryRows);
        }

        if ($this->toDelete->isEmpty()) {
            $this->info('Aucun match féminin détecté. La base est propre.');
            return self::SUCCESS;
        }

        // Afficher les matches à supprimer
        $this->newLine();
        $this->warn("Matches féminins détectés : {$this->toDelete->count()}");
        $this->newLine();

        $tableRows = $this->toDelete->map(fn (RugbyMatch $m) => [
            $m->id,
            $m->match_date->format('d/m/Y'),
            $m->opponent->name,
            $m->france_score . ' - ' . $m->opponent_score,
            $m->is_home ? 'Dom.' : 'Ext.',
        ])->toArray();

        $this->table(['ID', 'Date', 'Adversaire', 'Score', 'Lieu'], $tableRows);

        if ($dryRun) {
            $this->info("[DRY-RUN] {$this->toDelete->count()} matches seraient supprimés.");
            return self::SUCCESS;
        }

        if (!$this->confirm("Supprimer ces {$this->toDelete->count()} matches ?")) {
            $this->info('Annulé.');
            return self::SUCCESS;
        }

        $this->deleteMatches();

        return self::SUCCESS;
    }

    private function processYear(int $year): void
    {
        // Récupérer les matches en BDD pour cette année
        $dbMatches = RugbyMatch::with('opponent')
            ->whereYear('match_date', $year)
            ->get();

        if ($dbMatches->isEmpty()) {
            return;
        }

        // Fetch les matches masculins depuis equipe-france.fr
        $siteMatches = $this->fetchMasculineMatches($year);

        if ($siteMatches === null) {
            // Erreur HTTP — ne pas toucher aux matches de cette année
            $this->yearSummary[] = [$year, $dbMatches->count(), 0, 'ERREUR'];
            return;
        }

        // Comparer : pour chaque match en BDD, chercher une correspondance sur le site
        $feminine = collect();

        foreach ($dbMatches as $dbMatch) {
            if (!$this->hasMatchOnSite($dbMatch, $siteMatches)) {
                $feminine->push($dbMatch);
            }
        }

        if ($feminine->isNotEmpty()) {
            $this->toDelete = $this->toDelete->merge($feminine);
            $this->yearSummary[] = [$year, $dbMatches->count(), $feminine->count(), count($siteMatches)];
        }
    }

    private function fetchMasculineMatches(int $year): ?array
    {
        $url = self::BASE_URL . $year;

        $response = Http::withUserAgent(self::USER_AGENT)
            ->withOptions(['allow_redirects' => false])
            ->timeout(30)
            ->get($url);

        // Redirection 301/302 → page d'un match unique
        if ($response->status() === 301 || $response->status() === 302) {
            $redirectUrl = $response->header('Location');
            if (str_starts_with($redirectUrl, '/')) {
                $redirectUrl = 'https://www.equipe-france.fr' . $redirectUrl;
            }
            return $this->fetchSingleMatchPage($redirectUrl, $year);
        }

        if ($response->failed()) {
            return null;
        }

        return $this->parseYearPage($response->body(), $year);
    }

    private function parseYearPage(string $html, int $year): array
    {
        $crawler = new Crawler($html);
        $matches = [];

        $crawler->filter('li.match')->each(function (Crawler $node) use (&$matches, $year) {
            $match = $this->parseMatchNode($node);
            if ($match && $match['date']->year === $year) {
                $matches[] = $match;
            }
        });

        return $matches;
    }

    private function parseMatchNode(Crawler $node): ?array
    {
        $timeNode = $node->filter('time');
        if ($timeNode->count() === 0) {
            return null;
        }
        $date = Carbon::parse($timeNode->attr('datetime'));

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

        // Score
        $scoreNode = $node->filter('.scorenbr');
        if ($scoreNode->count() === 0) {
            return null;
        }
        if (!preg_match('/(\d+)\s*-\s*(\d+)/', $scoreNode->text(), $sm)) {
            return null;
        }

        $isFranceHome = $this->isFrance($homeName);
        $opponentName = $isFranceHome ? $awayName : $homeName;
        $mappedOpponent = $this->countryNameMapping[$opponentName] ?? $opponentName;

        return [
            'date' => $date,
            'opponent' => $mappedOpponent,
            'france_score' => $isFranceHome ? (int) $sm[1] : (int) $sm[2],
            'opponent_score' => $isFranceHome ? (int) $sm[2] : (int) $sm[1],
        ];
    }

    private function fetchSingleMatchPage(string $url, int $year): ?array
    {
        $response = Http::withUserAgent(self::USER_AGENT)
            ->timeout(30)
            ->get($url);

        if ($response->failed()) {
            return null;
        }

        $crawler = new Crawler($response->body());

        // Date depuis info_add
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
        if (!$month) {
            return [];
        }

        $date = Carbon::create((int) $dm[3], $month, (int) $dm[1]);
        if ($date->year !== $year) {
            return [];
        }

        // Score
        $scoreNode = $crawler->filter('.score_nbr');
        if ($scoreNode->count() === 0) {
            return [];
        }
        if (!preg_match('/(\d+)\s*-\s*(\d+)/', $scoreNode->text(), $sm)) {
            return [];
        }

        // Équipes
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
            'date' => $date,
            'opponent' => $mappedOpponent,
            'france_score' => $isFranceHome ? (int) $sm[1] : (int) $sm[2],
            'opponent_score' => $isFranceHome ? (int) $sm[2] : (int) $sm[1],
        ]];
    }

    private function hasMatchOnSite(RugbyMatch $dbMatch, array $siteMatches): bool
    {
        $dbDate = $dbMatch->match_date->format('Y-m-d');
        $dbOpponent = mb_strtolower($dbMatch->opponent->name);
        $dbFranceScore = $dbMatch->france_score;
        $dbOpponentScore = $dbMatch->opponent_score;

        foreach ($siteMatches as $site) {
            $siteDate = $site['date']->format('Y-m-d');
            $siteOpponent = mb_strtolower($site['opponent']);

            if ($siteDate === $dbDate
                && $siteOpponent === $dbOpponent
                && $site['france_score'] === $dbFranceScore
                && $site['opponent_score'] === $dbOpponentScore
            ) {
                return true;
            }
        }

        return false;
    }

    private function isFrance(string $name): bool
    {
        return str_contains(mb_strtolower($name), 'france');
    }

    private function deleteMatches(): void
    {
        $ids = $this->toDelete->pluck('id');

        // Supprimer les données liées
        \App\Models\MatchLineup::whereIn('match_id', $ids)->delete();
        \App\Models\MatchEvent::whereIn('match_id', $ids)->delete();
        \App\Models\MatchSubstitution::whereIn('match_id', $ids)->delete();

        RugbyMatch::whereIn('id', $ids)->delete();

        $this->info("{$this->toDelete->count()} matches féminins supprimés avec leurs données liées.");

        // Nettoyer les joueurs orphelins (joueurs qui n'ont plus aucune lineup)
        $orphanPlayers = \App\Models\Player::whereDoesntHave('lineups')->count();
        if ($orphanPlayers > 0) {
            $this->warn("{$orphanPlayers} joueurs orphelins détectés (sans aucune sélection).");
            if ($this->confirm('Supprimer les joueurs orphelins ?')) {
                \App\Models\Player::whereDoesntHave('lineups')
                    ->whereDoesntHave('events')
                    ->delete();
                $this->info('Joueurs orphelins supprimés.');
            }
        }
    }
}
