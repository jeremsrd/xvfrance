<?php

namespace App\Http\Controllers;

use App\Models\CompetitionEdition;

class CompetitionEditionController extends Controller
{
    public function show(CompetitionEdition $competitionEdition)
    {
        $competitionEdition->load('competition');

        $matches = $competitionEdition->matches()
            ->with(['opponent', 'venue', 'edition.competition'])
            ->orderBy('match_date', 'asc')
            ->get();

        $wins = $matches->filter(fn ($m) => $m->france_score > $m->opponent_score)->count();
        $losses = $matches->filter(fn ($m) => $m->france_score < $m->opponent_score)->count();
        $draws = $matches->filter(fn ($m) => $m->france_score === $m->opponent_score)->count();

        return view('competitions.edition', compact('competitionEdition', 'matches', 'wins', 'losses', 'draws'));
    }
}
