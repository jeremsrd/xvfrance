<?php

namespace App\Console\Commands\Maintenance;

use App\Models\RugbyMatch;
use App\Support\ExcludedMatches;
use Illuminate\Console\Command;

class RemoveExcludedMatches extends Command
{
    protected $signature = 'xv:remove-excluded-matches {--dry-run : Lister les matches sans les supprimer}';

    protected $description = 'Retire les matches sans sélection officielle listés dans excluded_matches.csv';

    public function handle(): int
    {
        $removed = 0;
        foreach (ExcludedMatches::rows() as $row) {
            $match = RugbyMatch::whereDate('match_date', $row['date'])
                ->whereHas('opponent', fn ($q) => $q->where('code', $row['opponent_code']))
                ->first();
            if (!$match) {
                continue;
            }

            $this->line("  Retiré : {$row['date']} {$row['opponent_code']} {$match->france_score}-{$match->opponent_score} ({$row['reason']})");
            $removed++;
            if (!$this->option('dry-run')) {
                // Compositions, événements et remplacements suivent (clés étrangères en cascade)
                $match->delete();
            }
        }

        $this->info("{$removed} match(es) retiré(s)" . ($this->option('dry-run') ? ' (dry-run)' : '') . ', ' . RugbyMatch::count() . ' en base.');

        return self::SUCCESS;
    }
}
