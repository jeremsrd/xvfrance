<?php

namespace App\Filament\Widgets;

use App\Models\Player;
use App\Models\RugbyMatch;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalMatches = RugbyMatch::count();
        $totalPlayers = Player::count();
        $frenchPlayers = Player::french()->count();

        $victories = RugbyMatch::whereColumn('france_score', '>', 'opponent_score')->count();
        $defeats = RugbyMatch::whereColumn('france_score', '<', 'opponent_score')->count();
        $draws = RugbyMatch::whereColumn('france_score', '=', 'opponent_score')->count();

        $lastMatch = RugbyMatch::with('opponent')->orderByDesc('match_date')->first();
        $lastMatchLabel = $lastMatch
            ? $lastMatch->match_date->format('d/m/Y') . ' vs ' . $lastMatch->opponent->name . ' (' . $lastMatch->france_score . '-' . $lastMatch->opponent_score . ')'
            : 'Aucun match';

        return [
            Stat::make('Total matches', $totalMatches),
            Stat::make('Joueurs', $frenchPlayers . ' FR / ' . $totalPlayers . ' total'),
            Stat::make('Bilan', $victories . 'V - ' . $defeats . 'D - ' . $draws . 'N'),
            Stat::make('Dernier match', $lastMatchLabel),
        ];
    }
}
