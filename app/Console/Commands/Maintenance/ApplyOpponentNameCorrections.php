<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Country;
use App\Models\Player;
use Illuminate\Console\Command;

/**
 * Applique les noms vérifiés de noms_adversaires.csv (accents retrouvés sur Wikidata, voir
 * scripts/wikipedia/accents_adversaires.py) aux joueurs adverses, retrouvés par leur pays et leur nom avant
 * correction ; des homonymes sont départagés par leurs années de sélection. Le slug suit le nouveau nom.
 * Les feuilles JSON portent déjà les noms corrigés (renommer_adversaires.py).
 */
class ApplyOpponentNameCorrections extends Command
{
    protected $signature = 'xv:apply-opponent-name-corrections
        {file=database/data/sources/noms_adversaires.csv : Noms vérifiés des joueurs adverses}
        {--dry-run : Afficher les changements sans les appliquer}';

    protected $description = 'Renomme les joueurs adverses d\'après les noms vérifiés (noms_adversaires.csv)';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        if (!is_file($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        $countries = Country::pluck('id', 'code');
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '');
        $renamed = [];
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            $row = array_combine($header, $line);
            if ($row['statut'] !== 'vérifié' || !isset($countries[$row['country']])) {
                continue;
            }
            // Comparaison exacte en PHP : en MySQL, la collation confond déjà « Morne » et « Morné »
            $players = Player::where('country_id', $countries[$row['country']])
                ->where('first_name', $row['base_first_name'])->where('last_name', $row['base_last_name'])->get()
                ->filter(fn (Player $p) => $p->first_name === $row['base_first_name'] && $p->last_name === $row['base_last_name']);
            if ($players->count() > 1 && $row['annees'] !== '') {
                [$from, $to] = explode('-', $row['annees']);
                $players = $players->filter(fn (Player $p) => $p->lineups()->whereHas('match',
                    fn ($q) => $q->whereBetween('match_date', ["{$from}-01-01", "{$to}-12-31"]))->exists());
            }
            if ($players->count() !== 1) {
                continue;
            }

            $player = $players->first();
            $renamed[] = [$row['country'], $player->fullName(), "{$row['first_name']} {$row['last_name']}"];
            if (!$this->option('dry-run')) {
                $player->first_name = $row['first_name'];
                $player->last_name = $row['last_name'];
                $player->slug = $player->generateSlug();
                $player->save();
            }
        }
        fclose($handle);

        if ($renamed) {
            $this->table(['Pays', 'Ancien nom', 'Nouveau nom'], $renamed);
        }
        $this->info(count($renamed) . ' joueur(s) renommé(s)' . ($this->option('dry-run') ? ' (dry-run)' : '') . '.');

        return self::SUCCESS;
    }
}
