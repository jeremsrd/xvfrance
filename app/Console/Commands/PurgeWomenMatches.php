<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\RugbyMatch;
use App\Models\Venue;
use Illuminate\Console\Command;

class PurgeWomenMatches extends Command
{
    protected $signature = 'matches:purge-women {--dry-run : Affiche ce qui serait supprimé sans modifier la BDD}';

    protected $description = 'Supprime les matches féminins importés par erreur (Pays-Bas, Suède, Kazakhstan)';

    /**
     * Adversaires identifiés comme étant exclusivement des matches féminins.
     * Belgique, Maroc, Tunisie, Paraguay sont des adversaires masculins légitimes (FIRA).
     */
    private array $womenOpponents = ['Pays-Bas', 'Suède', 'Kazakhstan'];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('MODE DRY-RUN — Aucune suppression en base de données');
            $this->newLine();
        }

        $totalDeleted = 0;
        $countriesDeleted = [];

        foreach ($this->womenOpponents as $countryName) {
            $country = Country::where('name', $countryName)->first();

            if (!$country) {
                $this->warn("Pays '{$countryName}' non trouvé en BDD, ignoré.");
                continue;
            }

            $matches = RugbyMatch::where('opponent_id', $country->id)->get();
            $count = $matches->count();

            if ($count === 0) {
                $this->info("Aucun match trouvé pour {$countryName}.");
                continue;
            }

            $this->info("{$countryName} : {$count} match(es) à supprimer");

            foreach ($matches as $match) {
                $this->line("  - {$match->match_date->format('d/m/Y')} : France {$match->france_score} - {$match->opponent_score} {$countryName}");
            }

            if (!$dryRun) {
                // Supprimer les événements, lineups et substitutions liés
                foreach ($matches as $match) {
                    $match->events()->delete();
                    $match->lineups()->delete();
                    $match->substitutions()->delete();
                    $match->delete();
                }
            }

            $totalDeleted += $count;

            // Vérifier si le pays est devenu orphelin (aucun match restant)
            $remainingMatches = $dryRun ? $count : RugbyMatch::where('opponent_id', $country->id)->count();
            if (!$dryRun && $remainingMatches === 0) {
                // Vérifier aussi qu'aucun joueur n'est lié à ce pays
                $playerCount = $country->players()->count();
                if ($playerCount === 0) {
                    $country->delete();
                    $countriesDeleted[] = $countryName;
                } else {
                    $this->warn("  Pays {$countryName} conservé ({$playerCount} joueur(s) liés).");
                }
            } elseif ($dryRun) {
                $countriesDeleted[] = "{$countryName} (sera supprimé si orphelin)";
            }
        }

        $this->newLine();
        $this->info('=== RÉSUMÉ ===');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Matches supprimés', $totalDeleted],
                ['Pays supprimés', count($countriesDeleted)],
            ]
        );

        if (!empty($countriesDeleted)) {
            $this->info('Pays supprimés : ' . implode(', ', $countriesDeleted));
        }

        // Nettoyer les stades orphelins
        $orphanVenues = Venue::whereDoesntHave('matches')->get();
        if ($orphanVenues->isNotEmpty()) {
            $this->newLine();
            $this->warn("Stades orphelins détectés ({$orphanVenues->count()}) :");
            foreach ($orphanVenues as $venue) {
                $this->line("  - {$venue->name}" . ($venue->city ? ", {$venue->city}" : ''));
            }
            if (!$dryRun) {
                $this->info("Les stades orphelins ne sont pas supprimés automatiquement.");
            }
        }

        return self::SUCCESS;
    }
}
