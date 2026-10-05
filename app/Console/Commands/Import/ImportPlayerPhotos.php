<?php

namespace App\Console\Commands\Import;

use App\Models\Country;
use App\Models\Player;
use Illuminate\Console\Command;

/**
 * Portraits des joueurs d'avant 1930 (ancien projet « This Is Rugby », domaine public) : player_photos.csv associe
 * chaque image de public/images/joueurs à un joueur (pays, prénom, nom, au moins un match avant 1930).
 * Ne remplace pas une photo posée par un autre moyen (photo_path hors de /images/joueurs/).
 */
class ImportPlayerPhotos extends Command
{
    protected $signature = 'xv:import-player-photos
        {file=database/data/sources/player_photos.csv : Correspondance image → joueur}
        {--dry-run : Afficher les changements sans les appliquer}';

    protected $description = 'Associe les portraits d\'avant 1930 (public/images/joueurs) aux fiches des joueurs';

    private const DIR = '/images/joueurs/';

    public function handle(): int
    {
        $path = $this->argument('file');
        $path = str_starts_with($path, '/') ? $path : base_path($path);
        if (!is_file($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        $countries = Country::pluck('id', 'code');
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '');
        $set = 0;
        $problems = [];
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            $row = array_combine($header, $line);
            $photo = self::DIR . $row['file'];
            if (!is_file(public_path($photo))) {
                $problems[] = [$row['file'], 'image absente de public/'];
                continue;
            }

            $players = Player::where('country_id', $countries[$row['country_code']] ?? 0)
                ->where('first_name', $row['first_name'])->where('last_name', $row['last_name'])
                ->whereHas('lineups.match', fn ($q) => $q->where('match_date', '<', '1930-01-01'))
                ->get();
            if ($players->count() !== 1) {
                $problems[] = [$row['file'], $players->count() . " joueur(s) {$row['first_name']} {$row['last_name']} ({$row['country_code']}) avant 1930"];
                continue;
            }

            $player = $players->first();
            if ($player->photo_path === $photo || ($player->photo_path && !str_starts_with($player->photo_path, self::DIR))) {
                continue;
            }
            $player->photo_path = $photo;
            $set++;
            if (!$this->option('dry-run')) {
                $player->save();
            }
        }
        fclose($handle);

        if ($problems) {
            $this->table(['Image', 'Problème'], $problems);
        }
        $this->info("{$set} portrait(s) associé(s)" . ($this->option('dry-run') ? ' (dry-run)' : '') . '.');

        return self::SUCCESS;
    }
}
