<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Country;
use App\Models\Player;
use Illuminate\Console\Command;

/**
 * Applique les noms vérifiés de noms_ecarts.csv aux joueurs français, retrouvés par leur numéro d'international
 * (xv:import-internationaux), à défaut par leur nom avant correction. Le slug suit le nouveau nom. Les feuilles JSON portent déjà les noms corrigés,
 * et equipe_france_name_splits.csv les impose au générateur : un réimport ne recrée pas l'ancien nom.
 */
class ApplyNameCorrections extends Command
{
    protected $signature = 'xv:apply-name-corrections
        {file=database/data/sources/noms_ecarts.csv : Écarts de nom vérifiés}
        {--dry-run : Afficher les changements sans les appliquer}';

    protected $description = 'Renomme les joueurs français d\'après les écarts de nom vérifiés (noms_ecarts.csv)';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        if (!is_file($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        $france = Country::where('code', 'FRA')->value('id');
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '');
        $renamed = [];
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            $row = array_combine($header, $line);
            if ($row['statut'] !== 'vérifié') {
                continue;
            }
            // Sans numéro d'international (Porra, « 255 bis » dans la liste) : par le nom actuel, s'il est unique
            $players = $row['cap_number'] !== ''
                ? Player::where('country_id', $france)->where('cap_number', (int) $row['cap_number'])->get()
                : Player::where('country_id', $france)->where('first_name', $row['base_first_name'])
                    ->where('last_name', $row['base_last_name'])->get();
            $player = $players->count() === 1 ? $players->first() : null;
            if (!$player || ($player->first_name === $row['first_name'] && $player->last_name === $row['last_name'])) {
                continue;
            }

            $renamed[] = [$row['cap_number'], $player->fullName(), "{$row['first_name']} {$row['last_name']}"];
            if (!$this->option('dry-run')) {
                $player->first_name = $row['first_name'];
                $player->last_name = $row['last_name'];
                $player->slug = $player->generateSlug();
                $player->save();
            }
        }
        fclose($handle);

        if ($renamed) {
            $this->table(['N°', 'Ancien nom', 'Nouveau nom'], $renamed);
        }
        $this->info(count($renamed) . ' joueur(s) renommé(s)' . ($this->option('dry-run') ? ' (dry-run)' : '') . '.');

        return self::SUCCESS;
    }
}
