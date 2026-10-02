<?php

namespace App\Http\Controllers;

use App\Enums\CompetitionType;
use App\Models\CompetitionEdition;
use App\Support\RecordSummary;

class CompetitionEditionController extends Controller
{
    public function show(CompetitionEdition $competitionEdition)
    {
        $competitionEdition->load('competition');

        $matches = $competitionEdition->matches()
            ->with(['opponent', 'venue', 'edition.competition'])
            ->orderBy('match_date')
            ->get();

        $record = RecordSummary::fromMatches($matches);
        $competitionEdition->record = $record;
        $grandSlam = $competitionEdition->competition->type === CompetitionType::TOURNOI
            && CompetitionController::isGrandSlam($competitionEdition);

        $siblings = $competitionEdition->competition->editions()->orderBy('year')->get(['id', 'year', 'label', 'competition_id']);
        $index = $siblings->search(fn ($e) => $e->id === $competitionEdition->id);

        return view('competitions.edition', [
            'competitionEdition' => $competitionEdition,
            'matches' => $matches,
            'record' => $record,
            'grandSlam' => $grandSlam,
            'previous' => $index > 0 ? $siblings[$index - 1] : null,
            'next' => $siblings[$index + 1] ?? null,
        ]);
    }
}
