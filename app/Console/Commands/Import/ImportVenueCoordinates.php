<?php

namespace App\Console\Commands\Import;

use App\Models\Venue;
use App\Support\VenueCoordinatesFile;
use Illuminate\Console\Command;

class ImportVenueCoordinates extends Command
{
    protected $signature = 'xv:import-venue-coordinates
        {file=database/data/sources/venue_coordinates.csv : Fichier CSV des coordonnées}
        {--dry-run : Compter les mises à jour sans les appliquer}';

    protected $description = 'Applique le CSV des coordonnées aux stades (correspondance nom + ville + pays)';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));
        if (!is_file($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        $rows = (new VenueCoordinatesFile($path))->read();
        $updated = 0;

        foreach (Venue::with(['country', 'names'])->get() as $venue) {
            // Ligne du CSV au nom actuel du stade, sinon à l'un de ses autres noms
            $row = $venue->names->pluck('name')->prepend($venue->name)
                ->map(fn (string $name) => $rows[VenueCoordinatesFile::key($name, $venue->city, $venue->country?->code)] ?? null)
                ->filter()->first();
            if (!$row) {
                continue;
            }

            $venue->fill(['latitude' => $row['latitude'], 'longitude' => $row['longitude']]);
            if ($venue->isDirty()) {
                $updated++;
                if (!$this->option('dry-run')) {
                    $venue->save();
                }
            }
        }

        $missing = Venue::whereNull('latitude')->count();
        $this->info("{$updated} stade(s) mis à jour" . ($this->option('dry-run') ? ' (dry-run)' : '') . ", {$missing} sans coordonnées.");

        return self::SUCCESS;
    }
}
