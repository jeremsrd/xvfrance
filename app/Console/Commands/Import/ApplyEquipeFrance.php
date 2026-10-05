<?php

namespace App\Console\Commands\Import;

use App\Enums\MatchStage;
use App\Models\Country;
use App\Models\RugbyMatch;
use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Complète les matches en base d'après database/data/sources/equipe_france_matches.csv (xv:fetch-equipe-france) :
 * phase (journée du Tournoi, phase de Coupe du Monde, test), numéro de journée et stade.
 * Seuls les champs vides sont remplis : une valeur déjà en base (feuille JSON, correction) est gardée.
 */
class ApplyEquipeFrance extends Command
{
    protected $signature = 'xv:apply-equipe-france
        {file=database/data/sources/equipe_france_matches.csv : CSV relevé par xv:fetch-equipe-france}
        {--dry-run : Compter les changements sans les appliquer}';

    protected $description = 'Complète phase, numéro de journée et stade des matches d\'après le relevé equipe-france.fr';

    /** Noms du site différents de ceux de la base */
    private const COUNTRY_ALIASES = [
        "Allemagne de l'Ouest" => 'Allemagne',
        "Fédération de l'Armée de terre britannique" => 'Armée britannique',
    ];

    private Collection $countries;

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        if (!is_file($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->countries = Country::all()->keyBy(fn (Country $c) => Venue::normalizeName($c->name));
        $france = $this->countries[Venue::normalizeName('France')];

        $stats = ['stage' => 0, 'match_number' => 0, 'venue' => 0];
        $missing = $unknownCountries = $createdVenues = [];
        $matchedIds = [];

        foreach ($this->readRows($path) as $row) {
            $opponentName = $row['home'] === 'France' ? $row['away'] : $row['home'];
            $opponent = $this->country($opponentName);
            if (!$opponent) {
                $unknownCountries[$opponentName] = true;
                continue;
            }

            $match = $this->findMatch($row['date'], $opponent);
            if (!$match) {
                $missing[] = [$row['date'], $opponentName, "{$row['home_score']}-{$row['away_score']}", "{$row['competition']} {$row['round']}"];
                continue;
            }
            $matchedIds[] = $match->id;

            [$stage, $number] = $this->mapRound($row['competition'], $row['round']);
            if ($match->stage === null && $stage) {
                $match->stage = $stage;
                $stats['stage']++;
            }
            if ($match->match_number === null && $number && $stage === MatchStage::JOURNEE) {
                $match->match_number = $number;
                $stats['match_number']++;
            }

            if ($match->venue_id === null && $row['venue'] !== '') {
                $name = $row['venue'];
                $venue = Venue::findByAnyName($name);
                if (!$venue) {
                    // Pays du stade : celui de l'équipe qui reçoit (à vérifier pour un terrain neutre)
                    $hostId = $row['home'] === 'France' ? $france->id : $opponent->id;
                    $createdVenues[] = [$name, $row['city'], $this->countries->firstWhere('id', $hostId)?->code, $row['date']];
                    $venue = $dryRun ? null : Venue::create(['name' => $name, 'city' => $row['city'] ?: null, 'country_id' => $hostId]);
                }
                $match->venue_id = $venue?->id;
                $stats['venue']++;
            }

            if (!$dryRun && $match->isDirty()) {
                $match->save();
            }
        }

        $suffix = $dryRun ? ' (dry-run)' : '';
        $this->info("Phases : {$stats['stage']}, numéros de journée : {$stats['match_number']}, stades : {$stats['venue']}{$suffix}.");

        if ($createdVenues) {
            $this->warn(count($createdVenues) . ' stade(s) créé(s), pays à vérifier pour un terrain neutre :');
            $this->table(['Stade', 'Ville', 'Pays', 'Premier match'], $createdVenues);
        }
        if ($unknownCountries) {
            $this->warn('Pays du site inconnus en base : ' . implode(', ', array_keys($unknownCountries)));
        }
        if ($missing) {
            $this->warn(count($missing) . ' match(es) du site absent(s) de la base :');
            $this->table(['Date', 'Adversaire', 'Score (dom.-ext.)', 'Compétition'], $missing);
        }

        $notOnSite = RugbyMatch::whereNotIn('id', $matchedIds)->count();
        $this->line("{$notOnSite} match(es) en base absent(s) du relevé.");

        return self::SUCCESS;
    }

    private function country(string $name): ?Country
    {
        return $this->countries[Venue::normalizeName(self::COUNTRY_ALIASES[$name] ?? $name)] ?? null;
    }

    /** Même adversaire, à la date du site ou à un jour près (décalage horaire des matches lointains) */
    private function findMatch(string $date, Country $opponent): ?RugbyMatch
    {
        $day = Carbon::parse($date);

        return RugbyMatch::where('opponent_id', $opponent->id)
            ->whereBetween('match_date', [$day->copy()->subDay()->startOfDay(), $day->copy()->addDay()->endOfDay()])
            ->get()
            ->sortBy(fn (RugbyMatch $m) => abs($m->match_date->diffInDays($day)))
            ->first();
    }

    /**
     * « Tournoi des Six Nations » + « 3e journée » → journée 3 ; « Coupe du monde » + « Quart-de-finale » → quart ;
     * test match et tournée → test. Autres compétitions (FIRA, Coupe latine…) : pas de phase.
     *
     * @return array{0: ?MatchStage, 1: ?int}
     */
    private function mapRound(string $competition, string $round): array
    {
        $competition = mb_strtolower($competition);
        $round = mb_strtolower($round);
        preg_match('/(\d+)\s*(?:re|e|er)\b/u', $round, $number);

        if (str_contains($competition, 'tournoi') || str_contains($competition, 'championnat des nations')) {
            return [MatchStage::JOURNEE, isset($number[1]) ? (int) $number[1] : null];
        }

        if (str_contains($competition, 'coupe du monde')) {
            return [match (true) {
                str_contains($round, 'poule') || str_contains($round, 'groupe') => MatchStage::POULE,
                str_contains($round, 'huiti') || str_contains($round, 'barrage') => MatchStage::HUITIEME,
                str_contains($round, 'quart') => MatchStage::QUART,
                str_contains($round, 'demi') => MatchStage::DEMI,
                str_contains($round, 'place') => MatchStage::PETITE_FINALE,
                str_contains($round, 'finale') => MatchStage::FINALE,
                default => null,
            }, null];
        }

        if (str_contains($competition, 'test') || str_contains($competition, 'tournée')) {
            return [MatchStage::TEST, null];
        }

        return [null, null];
    }

    private function readRows(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '');
        $rows = [];
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            if ($line !== [null]) {
                $rows[] = array_combine($header, $line);
            }
        }
        fclose($handle);

        return $rows;
    }
}
