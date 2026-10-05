<?php

namespace App\Console\Commands\Import;

use App\Enums\CoachRole;
use App\Models\Coach;
use App\Models\CoachTenure;
use App\Models\Country;
use App\Models\RugbyMatch;
use Illuminate\Console\Command;

/**
 * Sélectionneurs du XV de France depuis 1963 (selectionneurs.csv, d'après le tableau « Entraîneurs » de l'article
 * Wikipédia « Équipe de France de rugby à XV »). Les mandats se suivent sans se chevaucher : le bilan d'un
 * sélectionneur porte sur les matches de son mandat. Relancer la commande ne crée pas de doublon.
 */
class ImportCoaches extends Command
{
    protected $signature = 'xv:import-coaches
        {file=database/data/sources/selectionneurs.csv : Liste des sélectionneurs}
        {--dry-run : Afficher les mandats sans les enregistrer}';

    protected $description = 'Importe les sélectionneurs du XV de France et leurs mandats';

    public function handle(): int
    {
        $path = $this->argument('file');
        $path = str_starts_with($path, '/') ? $path : base_path($path);
        if (!is_file($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        $france = Country::where('code', 'FRA')->value('id');
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '');
        $rows = [];
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            $row = array_combine($header, $line);
            $end = $row['end_date'] ?: null;
            $matches = RugbyMatch::where('match_date', '>=', $row['start_date'])
                ->when($end, fn ($q) => $q->where('match_date', '<=', $end))
                ->count();
            $rows[] = ["{$row['first_name']} {$row['last_name']}", $row['start_date'], $end ?? 'en cours', $matches];

            if ($this->option('dry-run')) {
                continue;
            }
            $coach = Coach::updateOrCreate(
                ['first_name' => $row['first_name'], 'last_name' => $row['last_name']],
                ['birth_date' => $row['birth_date'] ?: null, 'birth_city' => $row['birth_city'] ?: null, 'country_id' => $france],
            );
            CoachTenure::updateOrCreate(
                ['coach_id' => $coach->id, 'role' => CoachRole::SELECTIONNEUR],
                ['start_date' => $row['start_date'], 'end_date' => $end],
            );
        }
        fclose($handle);

        $this->table(['Sélectionneur', 'Début', 'Fin', 'Matches'], $rows);
        $this->info(count($rows) . ' sélectionneur(s)' . ($this->option('dry-run') ? ' (dry-run)' : ' importé(s)') . '.');

        return self::SUCCESS;
    }
}
