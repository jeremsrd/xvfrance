<?php

namespace App\Console\Commands\Import;

use App\Enums\TeamSide;
use App\Models\Club;
use App\Models\Country;
use App\Models\MatchLineup;
use App\Models\Player;
use App\Models\PlayerClub;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Clubs des internationaux français. Les clubs (clubs.csv) et la correspondance des noms des sources
 * (club_names.csv : nom brut → club et nom à l'époque) sont versionnés. Les clubs d'un joueur viennent de la liste
 * « This Is Rugby » (internationaux_encyclo.csv, par numéro d'international, jusqu'à 2016), à défaut de l'encadré
 * de leur article Wikipédia (wikipedia_clubs.csv, scripts/wikipedia/clubs.py), à défaut du club actuel donné par
 * equipe-france.fr. Les liens joueur ↔ club sont reconstruits à chaque import.
 */
class ImportClubs extends Command
{
    protected $signature = 'xv:import-clubs
        {--dry-run : Afficher le bilan sans rien écrire}';

    protected $description = 'Importe les clubs des internationaux (clubs.csv, club_names.csv, liste des internationaux, equipe-france.fr)';

    private const CLUBS = 'database/data/sources/clubs.csv';
    private const NAMES = 'database/data/sources/club_names.csv';
    private const ENCYCLO = 'database/data/sources/internationaux_encyclo.csv';
    private const WIKIPEDIA = 'database/data/sources/wikipedia_clubs.csv';
    private const EQUIPE_FRANCE = 'database/data/sources/equipe_france_players.csv';

    /** @var array<string, array{0: string, 1: string}> nom brut en minuscules → [slug du club, nom à l'époque] */
    private array $names = [];

    /** @var array<string, int> noms bruts sans correspondance → nombre de joueurs */
    private array $unknown = [];

    public function handle(): int
    {
        $countries = Country::pluck('id', 'code');
        $clubs = [];
        foreach ($this->readCsv(self::CLUBS) as $row) {
            $clubs[$row['slug']] = ['name' => $row['name'], 'city' => $row['city'] ?: null, 'country_id' => $countries[$row['country_code']]];
        }
        foreach ($this->readCsv(self::NAMES) as $row) {
            $this->names[mb_strtolower($row['source_name'])] = [$row['club'], $row['name']];
        }

        // Clubs de chaque joueur, par id : [[slug, nom à l'époque, source], …]
        $france = $countries['FRA'];
        $byCap = Player::where('country_id', $france)->whereNotNull('cap_number')->pluck('id', 'cap_number');
        $memberships = [];
        foreach ($this->readCsv(self::ENCYCLO) as $row) {
            $playerId = $byCap[(int) $row['cap_number']] ?? null;
            if ($playerId) {
                $memberships[$playerId] = $this->resolve(explode(',', $row['clubs']), 'liste des internationaux');
            }
        }
        foreach ($this->readCsv(self::WIKIPEDIA) as $row) {
            $playerId = $this->playerAt($row['match_date'], $row['jersey']);
            if ($playerId && empty($memberships[$playerId])) {
                $memberships[$playerId] = $this->resolve(explode(';', $row['clubs']), 'Wikipédia');
            }
        }
        foreach ($this->readCsv(self::EQUIPE_FRANCE) as $row) {
            if ($row['club'] === '') {
                continue;
            }
            $playerId = $this->playerAt($row['match_date'], $row['jersey']);
            // Club actuel selon le site (« Stade toulousain (31) ») : seulement pour les joueurs absents de la liste
            if ($playerId && empty($memberships[$playerId])) {
                $memberships[$playerId] = $this->resolve([preg_replace('/\s*\(\d+\)$/', '', $row['club'])], 'equipe-france.fr');
            }
        }
        $memberships = array_filter($memberships);

        if ($this->unknown) {
            arsort($this->unknown);
            $this->warn(count($this->unknown) . ' nom(s) de club sans correspondance dans club_names.csv :');
            $this->table(['Nom', 'Joueurs'], collect($this->unknown)->map(fn ($n, $name) => [$name, $n])->values());
        }

        $links = array_sum(array_map('count', $memberships));
        $used = collect($memberships)->flatten(1)->pluck(0)->unique()->count();
        $this->info(count($clubs) . ' clubs (' . $used . ' avec au moins un international), '
            . count($memberships) . ' joueurs, ' . $links . ' passages en club' . ($this->option('dry-run') ? ' (dry-run)' : '') . '.');
        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($clubs, $memberships) {
            foreach ($clubs as $slug => $values) {
                Club::updateOrCreate(['slug' => $slug], $values);
            }
            // Club retiré de clubs.csv (rattaché à un autre, par exemple) : sa page disparaît
            Club::whereNotIn('slug', array_keys($clubs))->delete();
            $ids = Club::pluck('id', 'slug');
            PlayerClub::query()->delete();
            foreach ($memberships as $playerId => $list) {
                foreach ($list as $i => [$slug, $name, $source]) {
                    PlayerClub::create(['player_id' => $playerId, 'club_id' => $ids[$slug], 'position' => $i + 1, 'name' => $name, 'source' => $source]);
                }
            }
        });

        return self::SUCCESS;
    }

    /** Joueur français qui portait ce numéro ce jour-là */
    private function playerAt(string $date, string $jersey): ?int
    {
        return MatchLineup::where('team_side', TeamSide::FRANCE)->where('jersey_number', $jersey)
            ->whereHas('match', fn ($q) => $q->whereDate('match_date', $date))->value('player_id');
    }

    /** Noms bruts → [[slug, nom à l'époque, source]], un club n'apparaissant qu'une fois (son premier nom) */
    private function resolve(array $rawNames, string $source): array
    {
        $list = [];
        foreach ($rawNames as $raw) {
            $raw = rtrim(trim($raw), '. ');
            if ($raw === '') {
                continue;
            }
            $match = $this->names[mb_strtolower($raw)] ?? null;
            if ($match === null) {
                $this->unknown[$raw] = ($this->unknown[$raw] ?? 0) + 1;
                continue;
            }
            [$slug, $name] = $match;
            // Slug vide : nom écarté volontairement (contrat fédéral, pas un club)
            if ($slug !== '' && !isset($list[$slug])) {
                $list[$slug] = [$slug, $name, $source];
            }
        }

        return array_values($list);
    }

    private function readCsv(string $file): array
    {
        $handle = fopen(base_path($file), 'r');
        $header = fgetcsv($handle, escape: '');
        $rows = [];
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            $rows[] = array_combine($header, $line);
        }
        fclose($handle);

        return $rows;
    }
}
