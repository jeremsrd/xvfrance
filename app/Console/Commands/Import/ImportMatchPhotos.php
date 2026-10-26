<?php

namespace App\Console\Commands\Import;

use App\Models\Country;
use App\Models\MatchPhoto;
use App\Models\RugbyMatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Photos d'époque des matches (ancien projet « This Is Rugby », 1906-1920, domaine public) : match_photos.csv associe
 * chaque image de public/images/matches à un match (date, adversaire) et à une équipe (france, adversaire, ou vide
 * pour les deux équipes ou une scène de jeu). Les photos sont reconstruites à chaque import, dans l'ordre du fichier.
 */
class ImportMatchPhotos extends Command
{
    protected $signature = 'xv:import-match-photos
        {file=database/data/sources/match_photos.csv : Correspondance image → match}
        {--dry-run : Afficher le bilan sans rien écrire}';

    protected $description = 'Associe les photos d\'époque (public/images/matches) aux feuilles de match';

    private const DIR = '/images/matches/';

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
        $photos = [];
        $positions = [];
        $problems = [];
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            $row = array_combine($header, $line);
            $photo = self::DIR . $row['file'];
            if (!is_file(public_path($photo))) {
                $problems[] = [$row['file'], 'image absente de public/'];
                continue;
            }

            $match = RugbyMatch::whereDate('match_date', $row['match_date'])
                ->where('opponent_id', $countries[$row['opponent_code']] ?? 0)->first();
            if (!$match) {
                $problems[] = [$row['file'], "aucun match le {$row['match_date']} contre {$row['opponent_code']}"];
                continue;
            }

            [$width, $height] = getimagesize(public_path($photo));
            $positions[$match->id] = ($positions[$match->id] ?? 0) + 1;
            $photos[] = [
                'match_id' => $match->id,
                'team_side' => $row['team_side'] ?: null,
                'path' => $photo,
                'caption' => $row['caption'] ?: null,
                'width' => $width,
                'height' => $height,
                'position' => $positions[$match->id],
            ];
        }
        fclose($handle);

        if ($problems) {
            $this->table(['Image', 'Problème'], $problems);
        }
        if (!$this->option('dry-run')) {
            DB::transaction(function () use ($photos) {
                MatchPhoto::query()->delete();
                foreach ($photos as $photo) {
                    MatchPhoto::create($photo);
                }
            });
        }
        $this->info(count($photos) . ' photo(s) sur ' . count($positions) . ' match(es)' . ($this->option('dry-run') ? ' (dry-run)' : '') . '.');

        return self::SUCCESS;
    }
}
