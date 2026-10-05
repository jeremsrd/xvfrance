<?php

namespace App\Console\Commands\Import;

use App\Models\Country;
use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Numéro d'international et date de naissance des joueurs français, d'après la liste chronologique des
 * internationaux arrêtée au 31/12/2016 (internationaux_encyclo.csv, voir scripts/encyclo/convertir.py).
 * La liste fait foi pour le numéro ; la date de naissance n'est remplie que si elle est vide. Rapprochement par nom (accents ignorés, prénom compatible), les années de
 * sélection devant recouper les matches du joueur en base ; internationaux_correspondances.csv tranche les cas
 * que le nom ne suffit pas à résoudre.
 */
class ImportInternationaux extends Command
{
    protected $signature = 'xv:import-internationaux
        {file=database/data/sources/internationaux_encyclo.csv : Liste des internationaux}
        {--mapping=database/data/sources/internationaux_correspondances.csv : Correspondances manuelles}
        {--dry-run : Afficher les changements sans les appliquer}
        {--report : Lister les internationaux non rapprochés}
        {--ecarts= : Écrire dans ce CSV les noms différents entre la base et la liste}';

    protected $description = 'Complète numéro d\'international et date de naissance des joueurs français (liste arrêtée en 2016)';

    /** @var Collection<int, array{player: Player, last: string, first: string, full: string, from: ?int, to: ?int}> */
    private Collection $players;

    public function handle(): int
    {
        $rows = $this->readCsv($this->path($this->argument('file')));
        if ($rows === null) {
            $this->error('Fichier introuvable : ' . $this->argument('file'));
            return self::FAILURE;
        }
        $mapping = collect($this->readCsv($this->path($this->option('mapping'))) ?? [])->keyBy('cap_number');

        $this->loadPlayers();
        $stats = ['cap_number' => 0, 'birth_date' => 0];
        $unmatched = $ambiguous = $conflicts = $changed = $names = [];
        $used = [];

        DB::transaction(function () use ($rows, $mapping, &$stats, &$unmatched, &$ambiguous, &$conflicts, &$changed, &$names, &$used) {
            foreach ($rows as $row) {
                $candidates = isset($mapping[$row['cap_number']])
                    ? $this->byExactName($mapping[$row['cap_number']])
                    : $this->candidates($row);

                if ($candidates->count() !== 1) {
                    $line = [$row['cap_number'], trim("{$row['first_name']} {$row['last_name']}"), $row['years'],
                        $candidates->map(fn ($c) => $c['player']->fullName() . " ({$c['from']}-{$c['to']})")->join(' / ')];
                    $candidates->isEmpty() ? $unmatched[] = $line : $ambiguous[] = $line;
                    continue;
                }

                $player = $candidates->first()['player'];
                if (isset($used[$player->id])) {
                    $conflicts[] = [$row['cap_number'], $player->fullName(), "déjà rapproché du n° {$used[$player->id]}"];
                    continue;
                }
                $used[$player->id] = $row['cap_number'];

                if ($player->cap_number !== (int) $row['cap_number']) {
                    if ($player->cap_number !== null) {
                        $changed[] = [$row['cap_number'], $player->fullName(), "n° {$player->cap_number} remplacé"];
                    }
                    $player->cap_number = (int) $row['cap_number'];
                    $stats['cap_number']++;
                }
                if ($this->normalize($player->first_name . $player->last_name) !== $this->normalize($row['first_name'] . $row['last_name'])) {
                    $names[] = [$row['cap_number'], $player->fullName(), trim("{$row['first_name']} {$row['last_name']}"),
                        $player->first_name, $player->last_name, $row['first_name'], $row['last_name'], $row['years'],
                        $candidates->first()['from'] . '-' . $candidates->first()['to']];
                }
                if ($player->birth_date === null && $row['birth_date'] !== '') {
                    $player->birth_date = $row['birth_date'];
                    $stats['birth_date']++;
                }
                if (!$this->option('dry-run')) {
                    $player->save();
                }
            }
        });

        $this->info(count($rows) . ' internationaux lus, ' . count($used) . ' rapprochés d\'un joueur en base'
            . ($this->option('dry-run') ? ' (dry-run)' : '') . ".");
        $this->info("Numéros d'international écrits : {$stats['cap_number']}, dates de naissance : {$stats['birth_date']}.");
        if ($changed) {
            $this->warn(count($changed) . ' numéro(s) remplacé(s) :');
            $this->table(['N°', 'Joueur', ''], $changed);
        }
        if ($names && $this->option('report')) {
            $this->warn(count($names) . ' nom(s) différent(s) entre la base et la liste :');
            $this->table(['N°', 'Base', 'Liste'], array_map(fn ($n) => array_slice($n, 0, 3), $names));
        }
        if ($names && $this->option('ecarts')) {
            $handle = fopen($this->path($this->option('ecarts')), 'w');
            fputcsv($handle, ['cap_number', 'base_first_name', 'base_last_name', 'liste_first_name', 'liste_last_name', 'liste_annees', 'base_annees'], escape: '');
            foreach ($names as $n) {
                fputcsv($handle, [$n[0], ...array_slice($n, 3)], escape: '');
            }
            fclose($handle);
            $this->info(count($names) . ' écart(s) de nom écrits dans ' . $this->option('ecarts'));
        }
        if ($conflicts) {
            $this->warn(count($conflicts) . ' conflit(s) :');
            $this->table(['N°', 'Joueur', 'Conflit'], $conflicts);
        }
        if ($ambiguous) {
            $this->warn(count($ambiguous) . ' international(aux) ambigu(s), à trancher dans internationaux_correspondances.csv :');
            $this->table(['N°', 'Liste', 'Années', 'Candidats'], $ambiguous);
        }
        if ($unmatched) {
            $this->warn(count($unmatched) . ' international(aux) sans joueur en base' . ($this->option('report') ? ' :' : ' (--report pour la liste).'));
            if ($this->option('report')) {
                $this->table(['N°', 'Liste', 'Années', ''], $unmatched);
            }
        }

        return self::SUCCESS;
    }

    private function loadPlayers(): void
    {
        $france = Country::where('code', 'FRA')->value('id');
        $years = DB::table('match_lineups')
            ->join('matches', 'matches.id', '=', 'match_lineups.match_id')
            ->join('players', 'players.id', '=', 'match_lineups.player_id')
            ->where('players.country_id', $france)
            ->get(['match_lineups.player_id', 'matches.match_date'])
            ->groupBy('player_id')
            ->map(fn ($rows) => $rows->map(fn ($r) => (int) substr($r->match_date, 0, 4)));

        $this->players = Player::where('country_id', $france)->get()->map(fn (Player $p) => [
            'player' => $p,
            'last' => $this->normalize($p->last_name),
            'first' => $this->normalize($p->first_name),
            'full' => $this->normalize($p->first_name . $p->last_name),
            'from' => $years->get($p->id)?->min(),
            'to' => $years->get($p->id)?->max(),
        ]);
    }

    /** Même nom (ou nom composé contenant l'autre, « Jourdain de Muizon » / « De Muizon »), prénom compatible, années recoupées */
    private function candidates(array $row): Collection
    {
        $last = $this->normalize($row['last_name']);
        $first = $this->normalize($row['first_name']);
        [$from, $to] = $this->years($row['years']);
        $inYears = fn (array $c) => $from === null || $c['from'] === null || ($c['from'] <= $to + 1 && $c['to'] >= $from - 1);

        $tiers = [
            // même nom, prénom compatible (ou même nom complet découpé autrement)
            fn ($c) => $c['last'] === $last && $this->firstNamesCompatible($first, $c['first']) || $c['full'] === $first . $last,
            // les niveaux suivants exigent des années connues des deux côtés
            // même nom, prénom différent (Henri / Pierre Lacassagne) : seul joueur de ce nom à l'époque
            fn ($c) => $c['from'] !== null && $c['last'] === $last,
            // nom proche ou composé (« Leuvielle » / « Leuvieille », « Jourdain de Muizon » / « De Muizon »), prénom proche
            fn ($c) => $c['from'] !== null
                && ($this->firstNamesCompatible($first, $c['first']) || levenshtein($first, $c['first']) <= 2)
                && (levenshtein($c['last'], $last) <= 2
                    || strlen($c['last']) >= 4 && str_contains($last, $c['last'])
                    || strlen($last) >= 4 && str_contains($c['last'], $last)),
        ];
        foreach ($tiers as $i => $tier) {
            if ($i > 0 && $from === null) {
                break;
            }
            $found = $this->players->filter(fn ($c) => $inYears($c) && $tier($c))->values();
            if ($found->isNotEmpty()) {
                return $found;
            }
        }

        return collect();
    }

    private function byExactName(array $mapping): Collection
    {
        return $this->players->filter(fn ($c) => $c['last'] === $this->normalize($mapping['last_name'])
            && $c['first'] === $this->normalize($mapping['first_name']))->values();
    }

    /** « 1906-13 » → [1906, 1913], « 1893-00 » → [1893, 1900], « 2016 » → [2016, 2016] */
    private function years(string $value): array
    {
        if (!preg_match('/^(\d{4})(?:-(\d{2,4}))?$/', trim($value), $m)) {
            return [null, null];
        }
        $from = (int) $m[1];
        if (empty($m[2])) {
            return [$from, $from];
        }
        $to = strlen($m[2]) === 4 ? (int) $m[2] : intdiv($from, 100) * 100 + (int) $m[2];

        return [$from, $to < $from ? $to + 100 : $to];
    }

    /** Égaux, l'un absent ou réduit à une initiale, ou l'un commence par l'autre (« Jean » / « Jean-Pierre ») */
    private function firstNamesCompatible(string $a, string $b): bool
    {
        if ($a === '' || $b === '' || $a === $b) {
            return true;
        }

        return (strlen($a) === 1 || strlen($b) === 1) ? $a[0] === $b[0] : str_starts_with($a, $b) || str_starts_with($b, $a);
    }

    private function normalize(?string $value): string
    {
        return preg_replace('/[^a-z]/', '', Str::lower(Str::ascii($value ?? '')));
    }

    private function path(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    private function readCsv(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }
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
