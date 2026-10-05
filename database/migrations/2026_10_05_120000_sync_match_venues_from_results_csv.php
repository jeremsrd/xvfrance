<?php

use App\Models\RugbyMatch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Correction ponctuelle des données en place : l'import CSV rattachait par correspondance partielle
     * tout stade contenant « stadium » à Twickenham Stadium. On réapplique les noms des stades, le stade
     * de chaque match d'après results.csv, domicile / extérieur / terrain neutre et les coordonnées des stades créés.
     * Base vide (tests, reconstruction) : rien à corriger, la reconstruction utilise l'import corrigé.
     */
    public function up(): void
    {
        if (!RugbyMatch::exists()) {
            return;
        }

        Artisan::call('xv:import-venue-names');
        Artisan::call('xv:import-csv', ['file' => base_path('database/data/sources/results.csv'), '--sync-venues' => true]);
        Artisan::call('xv:compute-venue-flags');
        Artisan::call('xv:import-venue-coordinates');
    }

    public function down(): void
    {
        // Correction de données : pas de retour arrière
    }
};
