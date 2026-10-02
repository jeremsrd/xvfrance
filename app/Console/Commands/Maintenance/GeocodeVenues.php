<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Venue;
use App\Support\VenueCoordinatesFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GeocodeVenues extends Command
{
    protected $signature = 'xv:geocode-venues
        {--file=database/data/sources/venue_coordinates.csv : Fichier CSV des coordonnées}
        {--dry-run : Afficher les résultats sans écrire le fichier}
        {--delay=1 : Délai en secondes entre les requêtes (politique Nominatim : 1 req/s max)}';

    protected $description = 'Complète le CSV des coordonnées des stades via OpenStreetMap (Nominatim), sans toucher à la base';

    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
    private const USER_AGENT = 'xvfrance.fr Venue Geocoder / 1.0';

    public function handle(): int
    {
        $file = new VenueCoordinatesFile(base_path($this->option('file')));
        $rows = $file->read();
        $delay = (int) $this->option('delay');
        $added = ['stade' => 0, 'ville' => 0, 'base' => 0];
        $notFound = [];

        foreach (Venue::with('country')->orderBy('id')->get() as $venue) {
            $key = VenueCoordinatesFile::key($venue->name, $venue->city, $venue->country?->code);
            if (isset($rows[$key])) {
                continue;
            }

            // Coordonnées déjà en base (seeders) : reprises telles quelles
            if ($venue->latitude !== null && $venue->longitude !== null) {
                $rows[$key] = $this->row($venue, (float) $venue->latitude, (float) $venue->longitude, 'stade', 'base');
                $added['base']++;
                continue;
            }

            $country = $venue->country?->name;
            $result = $this->search(implode(', ', array_filter([$venue->name, $venue->city, $country])), $delay);
            $precision = 'stade';

            if (!$result) {
                $result = $this->search(implode(', ', array_filter([$venue->city, $country])), $delay);
                $precision = 'ville';
            }

            if (!$result) {
                $notFound[] = "{$venue->name} ({$venue->city})";
                $this->warn("Introuvable : {$venue->name}, {$venue->city}");
                continue;
            }

            $rows[$key] = $this->row($venue, (float) $result['lat'], (float) $result['lon'], $precision, 'nominatim');
            $added[$precision]++;
            $this->line("[{$precision}] {$venue->name}, {$venue->city} → {$result['display_name']}");
        }

        if (!$this->option('dry-run')) {
            $file->write($rows);
        }

        $this->newLine();
        $this->info("Ajoutés : {$added['base']} depuis la base, {$added['stade']} au stade, {$added['ville']} à la ville seulement.");
        if ($notFound) {
            $this->warn(count($notFound) . ' introuvable(s) : ' . implode(', ', $notFound));
        }
        if ($added['ville'] > 0) {
            $this->comment('Les lignes de précision « ville » sont à vérifier et corriger à la main dans le CSV.');
        }

        return self::SUCCESS;
    }

    private function search(string $query, int $delay): ?array
    {
        sleep($delay);

        $response = Http::withUserAgent(self::USER_AGENT)
            ->acceptJson()
            ->get(self::NOMINATIM_URL, ['q' => $query, 'format' => 'jsonv2', 'limit' => 1, 'accept-language' => 'fr']);

        return $response->successful() ? ($response->json()[0] ?? null) : null;
    }

    private function row(Venue $venue, float $lat, float $lng, string $precision, string $source): array
    {
        return [
            'name' => $venue->name,
            'city' => $venue->city,
            'country_code' => $venue->country?->code,
            'latitude' => round($lat, 7),
            'longitude' => round($lng, 7),
            'precision' => $precision,
            'source' => $source,
        ];
    }
}
