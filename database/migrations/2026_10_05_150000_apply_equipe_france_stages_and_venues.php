<?php

use App\Models\RugbyMatch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Correction ponctuelle des données en place : phase (journée du Tournoi, phase de Coupe du Monde, test),
     * numéro de journée et stade manquants, d'après le relevé equipe-france.fr (equipe_france_matches.csv).
     * Base vide (tests, reconstruction) : rien à faire, la reconstruction lance xv:apply-equipe-france.
     */
    public function up(): void
    {
        if (!RugbyMatch::exists()) {
            return;
        }

        Artisan::call('xv:import-venue-names');
        Artisan::call('xv:apply-equipe-france');
        Artisan::call('xv:compute-venue-flags');
        Artisan::call('xv:import-venue-coordinates');
    }

    public function down(): void
    {
        // Correction de données : pas de retour arrière
    }
};
