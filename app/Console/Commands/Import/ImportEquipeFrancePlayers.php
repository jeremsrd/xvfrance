<?php

namespace App\Console\Commands\Import;

use App\Enums\TeamSide;
use App\Models\Country;
use App\Models\MatchLineup;
use Illuminate\Console\Command;

/**
 * Complète l'identité des internationaux français d'après leur fiche equipe-france.fr (equipe_france_players.csv,
 * voir xv:fetch-equipe-france-players) : ville et pays de naissance, date et ville de décès, taille, poids, et la
 * date de naissance quand la liste des internationaux ne la donne pas. Seuls les champs vides sont remplis.
 * Le joueur est retrouvé par sa première apparition sur le site : date du match et numéro dans la composition française.
 */
class ImportEquipeFrancePlayers extends Command
{
    protected $signature = 'xv:import-equipe-france-players
        {file=database/data/sources/equipe_france_players.csv : Fiches relevées sur equipe-france.fr}
        {--dry-run : Afficher les changements sans les appliquer}
        {--report : Lister les dates de naissance différentes de la base}';

    protected $description = 'Complète naissance, décès, taille et poids des internationaux d\'après equipe-france.fr';

    private const FIELDS = ['birth_date', 'birth_city', 'birth_country_id', 'death_date', 'death_city', 'height_cm', 'weight_kg'];

    /** Pays de naissance donnés par le site et absents de la table countries : gardés dans la ville, « Dakar (Sénégal) » */
    private const OTHER_COUNTRIES = ['Sénégal', 'Viêt Nam', 'Algérie', 'Venezuela', 'Cameroun', 'RD Congo', 'Burkina Faso', 'Vanuatu'];

    /** Le site dit Royaume-Uni pour Londres, Croydon et Warrington */
    private const COUNTRY_ALIASES = ['Royaume-Uni' => 'Angleterre'];

    public function handle(): int
    {
        $path = $this->argument('file');
        $path = str_starts_with($path, '/') ? $path : base_path($path);
        if (!is_file($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        $countries = Country::pluck('id', 'name');
        $france = $countries['France'];
        $stats = array_fill_keys(self::FIELDS, 0);
        $missing = [];
        $conflicts = [];
        $skipped = [];

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '');
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            $row = array_combine($header, $line);
            $lineup = MatchLineup::where('team_side', TeamSide::FRANCE)->where('jersey_number', $row['jersey'])
                ->whereHas('match', fn ($q) => $q->whereDate('match_date', $row['match_date']))
                ->with('player')->first();
            if (!$lineup) {
                $missing[] = [$row['site_slug'], "{$row['first_name']} {$row['last_name']}", $row['match_date'], $row['jersey']];
                continue;
            }

            $player = $lineup->player;
            [$birthCity, $birthCountry] = $this->birthPlace($row, $countries, $france);
            $values = [
                'birth_date' => $row['birth_date'],
                'birth_city' => $birthCity,
                'birth_country_id' => $birthCountry,
                'death_date' => $row['death_date'],
                'death_city' => $row['death_city'],
                'height_cm' => $row['height_cm'],
                'weight_kg' => $row['weight_kg'],
            ];

            // Une fiche dont la naissance ne colle pas est sans doute celle d'un homonyme : rien n'en est repris
            $doubt = $this->doubt($row, $player->birth_date?->format('Y-m-d'));
            if ($doubt) {
                $skipped[] = [$player->fullName(), $row['match_date'], $player->birth_date?->format('Y-m-d') ?? '', $row['birth_date'], $doubt];
                continue;
            }
            if ($player->birth_date && $row['birth_date'] !== '' && $player->birth_date->format('Y-m-d') !== $row['birth_date']) {
                $conflicts[] = [$player->fullName(), $player->birth_date->format('Y-m-d'), $row['birth_date']];
            }

            foreach ($values as $field => $value) {
                if (($value ?? '') !== '' && $player->{$field} === null) {
                    $player->{$field} = $value;
                    $stats[$field]++;
                }
            }
            if ($player->isDirty() && !$this->option('dry-run')) {
                $player->save();
            }
        }
        fclose($handle);

        if ($missing) {
            $this->warn(count($missing) . ' fiches sans joueur à ce numéro :');
            $this->table(['Slug du site', 'Joueur', 'Match', 'N°'], $missing);
        }
        if ($skipped) {
            $this->warn(count($skipped) . ' fiches écartées (homonyme probable) :');
            $this->table(['Joueur', 'Premier match', 'Naissance (base)', 'Naissance (site)', 'Motif'], $skipped);
        }
        if ($this->option('report') && $conflicts) {
            $this->warn(count($conflicts) . ' dates de naissance différentes (la base est gardée) :');
            $this->table(['Joueur', 'Base', 'equipe-france.fr'], $conflicts);
        }

        $this->info('Champs remplis' . ($this->option('dry-run') ? ' (dry-run)' : '') . ' : '
            . collect($stats)->map(fn ($n, $field) => "{$field} {$n}")->implode(', ') . '.');
        if ($conflicts && !$this->option('report')) {
            $this->line(count($conflicts) . ' dates de naissance différentes de la base : --report pour les lister.');
        }

        return self::SUCCESS;
    }

    /** Motif d'écarter la fiche : âge au premier match hors de 16-42 ans, ou 2 ans d'écart avec la base (jour et mois différents) */
    private function doubt(array $row, ?string $baseBirth): ?string
    {
        if ($row['birth_date'] === '') {
            return null;
        }
        $age = (int) substr($row['match_date'], 0, 4) - (int) substr($row['birth_date'], 0, 4);
        if ($age < 16 || $age > 42) {
            return "{$age} ans au premier match";
        }
        // Même jour et même mois : une coquille sur l'année, d'un côté ou de l'autre, pas un homonyme
        if ($baseBirth && substr($baseBirth, 5) !== substr($row['birth_date'], 5)
            && abs(strtotime($baseBirth) - strtotime($row['birth_date'])) >= 2 * 365 * 86400) {
            return 'naissance trop éloignée de la base';
        }

        return null;
    }

    /**
     * Ville et pays de naissance. Le site écrit « Pau, Pyrénées-Atlantiques » pour une naissance en France
     * (département après la virgule, ou Paris seul) et « Dakar Sénégal » à l'étranger (pays collé à la ville).
     */
    private function birthPlace(array $row, $countries, int $france): array
    {
        $city = $row['birth_city'];
        if ($city === '') {
            return [null, null];
        }
        if ($row['birth_region'] !== '') {
            return [$city, $france];
        }

        foreach ([...$countries->keys(), ...self::OTHER_COUNTRIES, ...array_keys(self::COUNTRY_ALIASES)] as $country) {
            if (str_ends_with($city, " {$country}")) {
                $name = trim(substr($city, 0, -strlen($country)));
                $id = $countries[self::COUNTRY_ALIASES[$country] ?? $country] ?? null;

                return $id ? [$name, $id] : ["{$name} ({$country})", null];
            }
        }

        return [$city, $france];
    }
}
