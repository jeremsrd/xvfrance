<?php

namespace App\Console\Commands\Import;

use App\Models\Country;
use App\Models\RugbyMatch;
use App\Models\Venue;
use App\Models\VenueName;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImportVenueNames extends Command
{
    protected $signature = 'xv:import-venue-names
        {file=database/data/sources/venue_names.csv : Fichier CSV des autres noms des stades}
        {--dry-run : Afficher les changements sans les appliquer}';

    protected $description = 'Applique le CSV des noms des stades : nom actuel, anciens noms, variantes, fusion des doublons';

    private const KINDS = ['former', 'variant', 'error'];

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        if (!is_file($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        $groups = $this->readGroups($path);
        if ($groups === null) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $created = $renamed = $merged = $moved = 0;

        DB::transaction(function () use ($groups, $dryRun, &$created, &$renamed, &$merged, &$moved) {
            foreach ($groups as $group) {
                $country = Country::where('code', $group['country_code'])->first();
                $candidates = $this->candidates($group, $country);

                if ($candidates->isEmpty()) {
                    // Stade encore absent : créé pour que l'import du CSV de résultats reconnaisse ses noms
                    $this->line("  Créé : {$group['venue']} ({$group['city']})");
                    $created++;
                    if ($dryRun) {
                        continue;
                    }
                    $candidates = collect([Venue::create([
                        'name' => $group['venue'],
                        'city' => $group['city'],
                        'country_id' => $country?->id,
                    ])->loadCount('matches')]);
                }

                // On garde la fiche qui porte déjà le nom actuel, sinon celle qui a le plus de matches,
                // une fiche au nom erroné en dernier recours
                $target = Venue::normalizeName($group['venue']);
                $errors = collect($group['names'])->where('kind', 'error')
                    ->map(fn (array $n) => Venue::normalizeName($n['name']))->all();
                $byMatches = $candidates->sortBy([
                    fn (Venue $a, Venue $b) => in_array(Venue::normalizeName($a->name), $errors, true)
                        <=> in_array(Venue::normalizeName($b->name), $errors, true),
                    ['matches_count', 'desc'],
                ]);
                $keep = $byMatches->first(fn (Venue $v) => Venue::normalizeName($v->name) === $target)
                    ?? $byMatches->first();
                $others = $candidates->reject(fn (Venue $v) => $v->is($keep));

                if ($keep->name !== $group['venue'] || $keep->city !== $group['city']) {
                    $this->line("  Renommé : {$keep->name} ({$keep->city}) → {$group['venue']} ({$group['city']})");
                    $renamed++;
                }

                foreach ($others as $other) {
                    $this->line("  Fusionné : {$other->name} ({$other->city}, {$other->matches_count} match(es)) → {$group['venue']}");
                    $merged++;
                    $moved += $other->matches_count;
                }

                if ($dryRun) {
                    continue;
                }

                foreach ($others as $other) {
                    RugbyMatch::where('venue_id', $other->id)->update(['venue_id' => $keep->id]);
                    if ($keep->latitude === null && $other->latitude !== null) {
                        $keep->fill(['latitude' => $other->latitude, 'longitude' => $other->longitude]);
                    }
                    $other->delete();
                }

                $keep->fill(['name' => $group['venue'], 'city' => $group['city']])->save();

                // Le CSV fait foi : les noms absents du CSV sont retirés. Les noms erronés sont gardés comme variantes,
                // pour que l'import du CSV de résultats, qui les contient, retrouve le bon stade
                $names = collect($group['names']);
                $keep->names()->whereNotIn('name', $names->pluck('name'))->delete();
                foreach ($names as $name) {
                    VenueName::updateOrCreate(
                        ['venue_id' => $keep->id, 'name' => $name['name']],
                        ['used_until' => $name['used_until'] ?: null],
                    );
                }
            }
        });

        $this->info("{$created} stade(s) créé(s), {$renamed} stade(s) renommé(s), {$merged} doublon(s) fusionné(s), {$moved} match(es) déplacé(s)"
            . ($dryRun ? ' (dry-run)' : '') . '.');

        return self::SUCCESS;
    }

    /** Fiches du même pays qui portent le nom actuel ou l'un des autres noms du groupe */
    private function candidates(array $group, ?Country $country): Collection
    {
        $wanted = collect($group['names'])->pluck('name')->push($group['venue'])
            ->map(fn (string $n) => Venue::normalizeName($n))->all();

        return Venue::with('names')->withCount('matches')
            ->where('country_id', $country?->id)
            ->get()
            ->filter(fn (Venue $v) => in_array(Venue::normalizeName($v->name), $wanted, true)
                || $v->names->contains(fn (VenueName $n) => in_array(Venue::normalizeName($n->name), $wanted, true)))
            ->values();
    }

    /** Lignes du CSV regroupées par stade (nom actuel + ville + pays) */
    private function readGroups(string $path): ?array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '');
        $groups = [];
        $line = 1;

        while (($values = fgetcsv($handle, escape: '')) !== false) {
            $line++;
            if ($values === [null]) {
                continue;
            }

            $row = array_combine($header, $values);
            if (!in_array($row['kind'], self::KINDS, true)) {
                $this->error("Ligne {$line} : type « {$row['kind']} » inconnu (former, variant ou error)");
                return null;
            }
            if ($row['kind'] === 'former' && !$row['used_until']) {
                $this->error("Ligne {$line} : un ancien nom (former) exige une date de fin (used_until)");
                return null;
            }

            $key = $row['venue'] . '|' . $row['city'] . '|' . $row['country_code'];
            $groups[$key] ??= [
                'venue' => $row['venue'],
                'city' => $row['city'],
                'country_code' => $row['country_code'],
                'names' => [],
            ];
            $groups[$key]['names'][] = $row;
        }

        fclose($handle);

        return array_values($groups);
    }
}
