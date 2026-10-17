<?php

namespace App\Console\Commands\Import;

use App\Enums\TeamSide;
use App\Models\RugbyMatch;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Relève l'identité des internationaux français sur leur fiche equipe-france.fr (naissance, décès, taille, poids,
 * club) dans database/data/sources/equipe_france_players.csv. Ne modifie pas la base : voir
 * xv:import-equipe-france-players.
 *
 * Les fiches sont trouvées dans les compositions des pages de match déjà en cache (xv:fetch-equipe-france --venues),
 * où seuls les joueurs français ont un lien. Chaque fiche est rattachée à un joueur de la base par sa première
 * apparition : date du match et numéro de maillot (ou, à défaut, nom) dans la composition française.
 */
class FetchEquipeFrancePlayers extends Command
{
    protected $signature = 'xv:fetch-equipe-france-players
        {--delay=1 : Délai en secondes entre deux requêtes}';

    protected $description = 'Relève l\'identité des internationaux sur equipe-france.fr dans un CSV source (sans toucher la base)';

    private const BASE_URL = 'https://www.equipe-france.fr';
    private const USER_AGENT = 'xvfrance.fr / 1.0';
    private const MATCHES = 'database/data/sources/equipe_france_matches.csv';
    private const OUTPUT = 'database/data/sources/equipe_france_players.csv';
    /** Dates que le site met faute de mieux : 13/12/1901 en naissance, 01/01/2000 en décès */
    private const PLACEHOLDER_DATES = ['birth_date' => '1901-12-13', 'death_date' => '2000-01-01'];
    /** Champs faux sur le site, laissés vides (Yoann Huget est vivant ; Lamaison : 1971, 1,80 m, 90 kg selon Wikipédia) */
    private const KNOWN_ERRORS = [
        'yoann-huget' => ['death_date'],
        'christophe-lamaison' => ['birth_date', 'height_cm', 'weight_kg'],
    ];

    private const COLUMNS = [
        'site_slug', 'match_date', 'jersey', 'first_name', 'last_name', 'site_name', 'given_names',
        'birth_date', 'birth_city', 'birth_region', 'death_date', 'death_city', 'height_cm', 'weight_kg', 'club',
    ];

    public function handle(): int
    {
        $players = $this->findPlayers();
        $this->info(count($players) . ' fiches rattachées à un joueur de la base.');

        $rows = [];
        $bar = $this->output->createProgressBar(count($players));
        foreach ($players as $slug => $player) {
            $html = $this->get("/rugby/{$slug}", "joueurs/{$slug}.html");
            if ($html !== null) {
                $profile = array_diff_key($this->parseProfile($html), array_flip(self::KNOWN_ERRORS[$slug] ?? []));
                $rows[] = ['site_slug' => $slug] + $player + $profile;
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        usort($rows, fn ($a, $b) => [$a['match_date'], (int) $a['jersey']] <=> [$b['match_date'], (int) $b['jersey']]);
        $handle = fopen(base_path(self::OUTPUT), 'w');
        fputcsv($handle, self::COLUMNS, escape: '');
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($c) => $row[$c] ?? '', self::COLUMNS), escape: '');
        }
        fclose($handle);

        $this->info(count($rows) . ' fiches écrites : ' . self::OUTPUT);

        return self::SUCCESS;
    }

    /**
     * Fiches liées dans les compositions, rattachées à leur première apparition dans la base :
     * [slug du site => [match_date, jersey, first_name, last_name, site_name]]
     */
    private function findPlayers(): array
    {
        $handle = fopen(base_path(self::MATCHES), 'r');
        $header = fgetcsv($handle, escape: '');
        $matches = [];
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            $matches[] = array_combine($header, $line);
        }
        fclose($handle);
        usort($matches, fn ($a, $b) => $a['date'] <=> $b['date']);

        $players = [];
        $unresolved = [];
        foreach ($matches as $match) {
            $cache = storage_path("app/equipe-france/matches/{$match['site_id']}.html");
            if (!is_file($cache)) {
                continue;
            }
            $links = $this->parseLineupLinks(file_get_contents($cache));
            $links = array_filter($links, fn ($link) => !isset($players[$link['slug']]));
            if (!$links) {
                continue;
            }

            $lineup = RugbyMatch::whereDate('match_date', $match['date'])->first()?->lineups()
                ->where('team_side', TeamSide::FRANCE)->with('player')->get() ?? collect();
            foreach ($links as $link) {
                $found = $this->resolve($link, $lineup);
                if ($found) {
                    $players[$link['slug']] = [
                        'match_date' => $match['date'], 'jersey' => $found->jersey_number,
                        'first_name' => $found->player->first_name, 'last_name' => $found->player->last_name,
                        'site_name' => $link['name'],
                    ];
                    unset($unresolved[$link['slug']]);
                } else {
                    $unresolved[$link['slug']] ??= [$link['slug'], $link['name'], $match['date']];
                }
            }
        }

        if ($unresolved) {
            $this->warn(count($unresolved) . ' fiches sans joueur rattaché :');
            $this->table(['Slug', 'Nom sur le site', 'Premier match'], array_values($unresolved));
        }

        return $players;
    }

    /**
     * Joueur de la composition française : même numéro et nom de famille proche (les noms corrigés d'après Wikipédia
     * diffèrent du site : Duffour / Dufour, Garuet-Lempirou / Garuet), sinon même nom seul (s'il est unique)
     */
    private function resolve(array $link, $lineup)
    {
        $name = $this->normalize($link['name']);
        $hasName = fn ($l) => str_ends_with($name, $this->normalize($l->player->last_name));

        if ($link['num'] !== null) {
            $byNumber = $lineup->firstWhere('jersey_number', $link['num']);
            if ($byNumber && ($hasName($byNumber) || $this->isCloseName($name, $this->normalize($byNumber->player->last_name)))) {
                return $byNumber;
            }
        }

        $byFullName = $lineup->filter(fn ($l) => $this->normalize($l->player->fullName()) === $name);
        if ($byFullName->count() === 1) {
            return $byFullName->first();
        }
        $byLastName = $lineup->filter($hasName);

        return $byLastName->count() === 1 ? $byLastName->first() : null;
    }

    /** Dernier mot du nom du site proche du nom de famille en base (début commun ou 75 % de ressemblance) */
    private function isCloseName(string $siteName, string $lastName): bool
    {
        $siteLast = Str::afterLast($siteName, ' ');
        if (str_starts_with($lastName, $siteLast) || str_ends_with($lastName, $siteLast)) {
            return true;
        }
        similar_text($siteLast, str_replace(' ', '', $lastName), $percent);

        return $percent >= 75;
    }

    private function normalize(string $name): string
    {
        return Str::slug($name, ' ');
    }

    /** Joueurs liés du bloc « Effectifs » (titulaires et remplaçants) : [slug, name, num] */
    private function parseLineupLinks(string $html): array
    {
        $block = (new Crawler($html))->filter('.matcheffectif');
        if ($block->count() === 0) {
            return [];
        }

        $links = $block->filter('li')->each(function (Crawler $li) {
            $link = $li->filter('a[href^="/rugby/"]');
            if ($link->count() === 0) {
                return null;
            }
            $num = $li->filter('.num');

            return [
                'slug' => substr($link->attr('href'), strlen('/rugby/')),
                'name' => trim($link->text()),
                'num' => $num->count() && is_numeric(trim($num->text())) ? (int) trim($num->text()) : null,
            ];
        });

        return array_values(array_filter($links));
    }

    /** Bloc d'identité de la fiche (« Prénoms », « Naissance », « → Lieu », « Taille et Poids »…) */
    private function parseProfile(string $html): array
    {
        $profile = [];
        $previous = null;
        (new Crawler($html))->filter('.inlineinfo .trow')->each(function (Crawler $row) use (&$profile, &$previous) {
            $label = trim($row->filter('.tth')->text(''));
            $cell = $row->filter('.ttd');
            $value = trim(preg_replace('/\s+/', ' ', $cell->text('')));

            switch ($label) {
                case 'Prénoms':
                case 'Prénom':
                    $profile['given_names'] = $value;
                    break;
                case 'Naissance':
                    $time = $cell->filter('time');
                    $profile['birth_date'] = $time->count() ? substr($time->attr('datetime'), 0, 10) : $this->parseDate($value);
                    break;
                case 'Décès':
                    $profile['death_date'] = $this->parseDate($value);
                    break;
                case '→ Lieu':
                    // « Lourdes, Hautes-Pyrénées » : ville, puis département (ou pays)
                    $parts = array_map('trim', explode(',', $value, 2));
                    if ($previous === 'Naissance') {
                        $profile['birth_city'] = $parts[0];
                        $profile['birth_region'] = $parts[1] ?? '';
                    } elseif ($previous === 'Décès') {
                        $profile['death_city'] = $parts[0];
                    }
                    break;
                case 'Taille et Poids':
                case 'Taille':
                case 'Poids':
                    if (preg_match('/(\d),(\d{2}) m/', $value, $m)) {
                        $profile['height_cm'] = (int) ($m[1] . $m[2]);
                    }
                    if (preg_match('/(\d+) kg/', $value, $m)) {
                        $profile['weight_kg'] = (int) $m[1];
                    }
                    break;
                case 'Club':
                    $profile['club'] = $value;
                    break;
            }
            if (!str_starts_with($label, '→')) {
                $previous = $label;
            }
        });

        foreach (self::PLACEHOLDER_DATES as $field => $date) {
            if (($profile[$field] ?? '') === $date) {
                unset($profile[$field]);
            }
        }

        return $profile;
    }

    /** « 25 février 2005 » → 2005-02-25 ; « 1er janvier 1945 » aussi */
    private function parseDate(string $value): string
    {
        $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        if (!preg_match('/(\d{1,2})(?:er)?\s+(\p{L}+)\s+(\d{4})/u', $value, $m)) {
            return '';
        }
        $month = array_search(mb_strtolower($m[2]), $months, true);

        return $month === false ? '' : sprintf('%04d-%02d-%02d', $m[3], $month + 1, $m[1]);
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
}
