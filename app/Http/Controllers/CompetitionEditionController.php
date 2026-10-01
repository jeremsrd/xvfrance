<?php

namespace App\Http\Controllers;

use App\Models\CompetitionEdition;
use App\Support\RecordSummary;

class CompetitionEditionController extends Controller
{
    public function show(CompetitionEdition $competitionEdition)
    {
        $competitionEdition->load('competition');

        $matches = $competitionEdition->matches()
            ->with(['opponent', 'venue', 'edition.competition'])
            ->orderBy('match_date', 'asc')
            ->get();

        $record = RecordSummary::fromMatches($matches);

        return view('competitions.edition', compact('competitionEdition', 'matches', 'record'));
    }
}
