<?php

namespace App\Console\Commands\Import;

use App\Models\Venue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Photos libres des stades (Wikimedia Commons) : venue_photos.csv, relevé par scripts/wikipedia/stades.py, donne pour
 * chaque stade le fichier Commons, son auteur et sa licence ; l'image est dans public/images/stades/{slug}.webp
 * (scripts/photos/convertir_stades.py). Les photos des stades sont reconstruites à chaque import.
 */
class ImportVenuePhotos extends Command
{
    protected $signature = 'xv:import-venue-photos
        {file=database/data/sources/venue_photos.csv : Photos des stades (Wikimedia Commons)}
        {--dry-run : Afficher le bilan sans rien écrire}';

    protected $description = 'Associe les photos libres (public/images/stades) aux stades, avec leur crédit';

    private const DIR = '/images/stades/';

    /** Libellés de Commons affichés en français ; les licences Creative Commons gardent leur sigle */
    private const LICENSES = ['Public domain' => 'Domaine public', 'Copyrighted free use' => 'Libre de droits'];

    private const COLUMNS = ['photo_path', 'photo_width', 'photo_height', 'photo_author', 'photo_license',
        'photo_license_url', 'photo_source_url'];

    public function handle(): int
    {
        $path = $this->argument('file');
        $path = str_starts_with($path, '/') ? $path : base_path($path);
        if (!is_file($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        $venues = Venue::pluck('id', 'slug');
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '');
        $photos = [];
        $problems = [];
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            $row = array_combine($header, $line);
            $photo = self::DIR . $row['venue_slug'] . '.webp';
            if (!isset($venues[$row['venue_slug']])) {
                $problems[] = [$row['venue_slug'], 'stade absent de la base'];
                continue;
            }
            if (!is_file(public_path($photo))) {
                $problems[] = [$row['venue_slug'], 'image absente de public/'];
                continue;
            }

            [$width, $height] = getimagesize(public_path($photo));
            $photos[$venues[$row['venue_slug']]] = [
                'photo_path' => $photo,
                'photo_width' => $width,
                'photo_height' => $height,
                'photo_author' => $row['author'] ?: null,
                'photo_license' => self::LICENSES[$row['license']] ?? ($row['license'] ?: null),
                'photo_license_url' => $row['license_url'] ?: null,
                'photo_source_url' => $row['page'],
            ];
        }
        fclose($handle);

        if ($problems) {
            $this->table(['Stade', 'Problème'], $problems);
        }
        if (!$this->option('dry-run')) {
            DB::transaction(function () use ($photos) {
                Venue::query()->update(array_fill_keys(self::COLUMNS, null));
                foreach ($photos as $id => $photo) {
                    Venue::whereKey($id)->update($photo);
                }
            });
        }
        $this->info(count($photos) . ' photo(s) de stade' . ($this->option('dry-run') ? ' (dry-run)' : '') . '.');

        return self::SUCCESS;
    }
}
