<?php

namespace App\Console\Commands\Maintenance;

use App\Models\MatchLineup;
use App\Models\Player;
use Illuminate\Console\Command;

class ComputePrimaryPositions extends Command
{
    protected $signature = 'xv:compute-primary-positions {--dry-run : Compter les changements sans les appliquer}';

    protected $description = 'Déduit le poste principal de chaque joueur du poste le plus joué dans ses compositions';

    public function handle(): int
    {
        // Poste le plus fréquent ; à égalité, celui du match le plus récent
        $positions = MatchLineup::whereNotNull('position_played')
            ->join('matches', 'matches.id', '=', 'match_lineups.match_id')
            ->orderByDesc('matches.match_date')
            ->get(['match_lineups.player_id', 'match_lineups.position_played'])
            ->groupBy('player_id')
            ->map(fn ($rows) => $rows->countBy(fn (MatchLineup $l) => $l->position_played->value)
                ->sortDesc(SORT_NUMERIC)->keys()->first());

        $changed = 0;
        foreach (Player::whereIn('id', $positions->keys())->get() as $player) {
            $player->primary_position = $positions[$player->id];
            if (!$player->isDirty('primary_position')) {
                continue;
            }
            $changed++;
            if (!$this->option('dry-run')) {
                $player->save();
            }
        }

        $this->info("{$changed} poste(s) principal(aux) mis à jour" . ($this->option('dry-run') ? ' (dry-run)' : '')
            . ', ' . Player::whereNull('primary_position')->count() . ' joueur(s) sans poste connu.');

        return self::SUCCESS;
    }
}
