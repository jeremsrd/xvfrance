<?php

namespace App\Console\Commands\Maintenance;

use App\Enums\TeamSide;
use App\Models\MatchLineup;
use Illuminate\Console\Command;

/**
 * Applique les dates de naissance et de décès (et lieux de décès) vérifiées de dates_corrections.csv
 * (comparaison avec Wikidata, equipe-france.fr et le fichier des décès de l'INSEE, voir scripts/wikipedia/naissances.py).
 * Le joueur est retrouvé par son numéro de maillot à son premier match. Une valeur n'est changée que si la base porte
 * encore l'ancienne : une correction faite depuis n'est pas écrasée. Passe avant xv:import-equipe-france-players,
 * qui écarte la fiche d'un joueur dont la naissance en base est trop éloignée de celle du site.
 */
class ApplyDateCorrections extends Command
{
    protected $signature = 'xv:apply-date-corrections
        {file=database/data/sources/dates_corrections.csv : Dates vérifiées}
        {--dry-run : Afficher les changements sans les appliquer}';

    protected $description = 'Corrige naissance, décès et lieu de décès des internationaux (dates_corrections.csv)';

    private const FIELDS = ['birth_date', 'death_date', 'death_city'];

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        if (!is_file($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        $changed = [];
        $ignored = [];
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '');
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            $row = array_combine($header, $line);
            if (!in_array($row['champ'], self::FIELDS, true)) {
                $ignored[] = [$row['joueur'], $row['champ'], 'champ inconnu'];
                continue;
            }
            $player = MatchLineup::where('team_side', TeamSide::FRANCE)->where('jersey_number', $row['jersey'])
                ->whereHas('match', fn ($q) => $q->whereDate('match_date', $row['match_date']))
                ->first()?->player;
            if (!$player) {
                $ignored[] = [$row['joueur'], $row['champ'], "personne au n° {$row['jersey']} le {$row['match_date']}"];
                continue;
            }

            $value = $player->{$row['champ']};
            $current = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
            if ($current === $row['correction']) {
                continue;
            }
            if ($current !== $row['base']) {
                $ignored[] = [$player->fullName(), $row['champ'], "base à {$current}, attendu {$row['base']}"];
                continue;
            }

            $changed[] = [$player->fullName(), $row['champ'], $current, $row['correction'], $row['source']];
            if (!$this->option('dry-run')) {
                $player->{$row['champ']} = $row['correction'] ?: null;
                $player->save();
            }
        }
        fclose($handle);

        if ($changed && $this->getOutput()->isVerbose()) {
            $this->table(['Joueur', 'Champ', 'Avant', 'Après', 'Source'], $changed);
        }
        if ($ignored) {
            $this->warn(count($ignored) . ' correction(s) non appliquée(s) :');
            $this->table(['Joueur', 'Champ', 'Motif'], $ignored);
        }
        $this->info(count($changed) . ' date(s) corrigée(s)' . ($this->option('dry-run') ? ' (dry-run)' : '') . '.');

        return self::SUCCESS;
    }
}
