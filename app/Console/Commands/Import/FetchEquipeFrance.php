<?php

namespace App\Console\Commands\Import;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Relève les matches du XV de France sur equipe-france.fr (compétition, journée ou phase, lieu)
 * dans database/data/sources/equipe_france_matches.csv. Ne touche pas la base : voir xv:apply-equipe-france.
 * Les pages téléchargées sont gardées en cache (storage/app/equipe-france) pour ne pas les redemander.
 */
class FetchEquipeFrance extends Command
{
    protected $signature = 'xv:fetch-equipe-france
        {--from=1906 : Première année}
        {--to= : Dernière année (par défaut l\'année en cours)}
        {--venues : Lire aussi le lieu sur la page de chaque match}
        {--delay=1 : Délai en secondes entre deux requêtes}';

    protected $description = 'Relève les matches du XV de France sur equipe-france.fr dans un CSV source (sans toucher la base)';

    private const BASE_URL = 'https://www.equipe-france.fr';
    private const USER_AGENT = 'xvfrance.fr / 1.0';
    private const OUTPUT = 'database/data/sources/equipe_france_matches.csv';
    private const COLUMNS = ['site_id', 'date', 'home', 'away', 'home_score', 'away_score', 'competition', 'round', 'venue', 'city', 'url'];

    public function handle(): int
    {
        $from = (int) $this->option('from');
        $to = (int) ($this->option('to') ?: date('Y'));

        $rows = [];
        foreach (range($from, $to) as $year) {
            $html = $this->get("/rugby/masculin/{$year}", "years/{$year}.html");
            if ($html === null) {
                continue;
            }
            foreach ($this->parseYear($html, $year) as $row) {
                $rows[$row['site_id']] = $row;
            }
        }
        $this->info(count($rows) . ' matches relevés sur les pages annuelles.');

        if ($this->option('venues')) {
            $bar = $this->output->createProgressBar(count($rows));
            foreach ($rows as $id => $row) {
                $html = $this->get($row['url'], "matches/{$id}.html");
                if ($html !== null) {
                    [$rows[$id]['venue'], $rows[$id]['city']] = $this->parseVenue($html);
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        usort($rows, fn ($a, $b) => [$a['date'], $a['site_id']] <=> [$b['date'], $b['site_id']]);
        $handle = fopen(base_path(self::OUTPUT), 'w');
        fputcsv($handle, self::COLUMNS, escape: '');
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($c) => $row[$c] ?? '', self::COLUMNS), escape: '');
        }
        fclose($handle);

        $this->info('Écrit : ' . self::OUTPUT);

        return self::SUCCESS;
    }

    /** Page du site, depuis le cache si elle y est déjà */
    private function get(string $path, string $cacheFile): ?string
    {
        $cache = storage_path("app/equipe-france/{$cacheFile}");
        if (is_file($cache)) {
            return file_get_contents($cache);
        }

        try {
            $response = Http::withUserAgent(self::USER_AGENT)->connectTimeout(15)->timeout(30)->retry(2, 5000, throw: false)
                ->get(self::BASE_URL . $path);
        } catch (ConnectionException $e) {
            $this->warn("Échec réseau : {$path}");
            return null;
        }
        sleep((int) $this->option('delay'));
        if ($response->failed()) {
            $this->warn("Échec {$response->status()} : {$path}");
            return null;
        }

        @mkdir(dirname($cache), 0755, true);
        file_put_contents($cache, $response->body());

        return $response->body();
    }

    /** Matches d'une page annuelle (les matches masqués « dnone » compris) */
    private function parseYear(string $html, int $year): array
    {
        $rows = [];
        (new Crawler($html))->filter('li[id^="match_"]')->each(function (Crawler $li) use (&$rows, $year) {
            $time = $li->filter('time');
            if ($time->count() === 0 || (int) substr($time->attr('datetime'), 0, 4) !== $year) {
                return;
            }

            // « Écosse - France Samedi 21 février 1998 (16 - 51) » : équipes et score dans l'ordre domicile / extérieur
            $summary = trim(preg_replace('/\s+/', ' ', $li->filter('.equipescore .dnone')->text('')));
            if (!preg_match('/\((\d+)\s*-\s*(\d+)\)\s*$/', $summary, $score)) {
                return;
            }

            $round = $li->filter('.infoadd > span')->reduce(fn (Crawler $s) => $s->filter('time')->count() === 0);

            $rows[] = [
                'site_id' => (int) substr($li->attr('id'), 6),
                'date' => substr($time->attr('datetime'), 0, 10),
                'home' => trim($li->filter('.equipedom span')->text('')),
                'away' => trim($li->filter('.equipeext span')->text('')),
                'home_score' => (int) $score[1],
                'away_score' => (int) $score[2],
                'competition' => trim($li->filter('.infocompet')->text('')),
                'round' => $round->count() ? trim(preg_replace('/\s+/', ' ', $round->first()->text())) : '',
                'url' => $li->filter('a')->attr('href'),
            ];
        });

        return $rows;
    }

    /** Lieu de la page d'un match : « Stade Vélodrome, Marseille », ou la ville seule */
    private function parseVenue(string $html): array
    {
        $info = (new Crawler($html))->filter('.info_add');
        if ($info->count() === 0) {
            return ['', ''];
        }

        // Lien vers la fiche du stade : « Stade Vélodrome, Marseille »
        $stadium = $info->filter('a[href^="/stade/"]');
        if ($stadium->count()) {
            $parts = array_map('trim', explode(',', $stadium->text()));
            $city = count($parts) > 1 ? array_pop($parts) : '';

            return [implode(', ', $parts), $city];
        }

        // Sinon, après la date : « Eden Park, Auckland » ou la ville seule (« Toulouse, Haute-Garonne »)
        $html = $info->html();
        if (!preg_match('/<br\s*\/?>(.*)$/s', $html, $after)) {
            return ['', ''];
        }
        $parts = array_map('trim', explode(',', trim(preg_replace('/\s+/', ' ', strip_tags($after[1])))));
        if ($parts[0] === '') {
            return ['', ''];
        }

        // Un nom de stade contient un mot comme « Park », « Stade », « Stadium »… ; sinon c'est la ville
        $isStadium = preg_match('/\b(stade|stadium|park|parc|ground|oval|arena|estadio|stadio|stadionul|campo|field|dome|centre|center|road)\b/iu', $parts[0]);

        return $isStadium ? [$parts[0], $parts[1] ?? ''] : ['', $parts[0]];
    }
}
