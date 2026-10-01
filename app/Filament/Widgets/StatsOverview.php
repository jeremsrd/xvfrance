<?php

namespace App\Filament\Widgets;

use App\Models\Player;
use App\Models\RugbyMatch;
use App\Support\RecordSummary;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalMatches = RugbyMatch::count();
        $totalPlayers = Player::count();
        $frenchPlayers = Player::french()->count();

        $record = RecordSummary::fromQuery(RugbyMatch::query());

        $lastMatch = RugbyMatch::with('opponent')->orderByDesc('match_date')->first();
        $lastMatchLabel = $lastMatch
            ? $lastMatch->match_date->format('d/m/Y') . ' vs ' . $lastMatch->opponent->name . ' (' . $lastMatch->france_score . '-' . $lastMatch->opponent_score . ')'
            : 'Aucun match';

        return [
            Stat::make('Total matches', $totalMatches),
            Stat::make('Joueurs', $frenchPlayers . ' FR / ' . $totalPlayers . ' total'),
            Stat::make('Bilan', $record->wins . 'V - ' . $record->losses . 'D - ' . $record->draws . 'N'),
            Stat::make('Dernier match', $lastMatchLabel),
        ];
    }
}
