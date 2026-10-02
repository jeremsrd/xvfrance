<?php

namespace App\Http\Controllers;

use App\Models\RugbyMatch;
use App\Support\MatchSheet;
use App\Support\RecordSummary;

class MatchController extends Controller
{
    public function show(RugbyMatch $rugbyMatch)
    {
        $rugbyMatch->load([
            'opponent',
            'venue',
            'edition.competition',
            'refereeCountry',
            'lineups.player',
            'events.player',
            'substitutions.playerOff',
            'substitutions.playerOn',
        ]);

        $sheet = new MatchSheet($rugbyMatch);

        // Face-à-face avec cet adversaire, jusqu'à ce match inclus
        $meetings = RugbyMatch::where('opponent_id', $rugbyMatch->opponent_id);
        $headToHead = RecordSummary::fromQuery((clone $meetings)->where('match_date', '<=', $rugbyMatch->match_date));
        $headToHeadTotal = RecordSummary::fromQuery(clone $meetings);
        $recentMeetings = (clone $meetings)
            ->with(['venue', 'edition.competition'])
            ->whereKeyNot($rugbyMatch->id)
            ->where('match_date', '<=', $rugbyMatch->match_date)
            ->orderByDesc('match_date')
            ->limit(5)
            ->get()
            ->each->setRelation('opponent', $rugbyMatch->opponent);

        // Rang de la rencontre dans l'histoire des confrontations
        $meetingNumber = $headToHead->total;

        $previous = RugbyMatch::with('opponent')
            ->where(fn ($q) => $q->where('match_date', '<', $rugbyMatch->match_date)
                ->orWhere(fn ($q) => $q->where('match_date', $rugbyMatch->match_date)->where('id', '<', $rugbyMatch->id)))
            ->orderByDesc('match_date')->orderByDesc('id')
            ->first();

        $next = RugbyMatch::with('opponent')
            ->where(fn ($q) => $q->where('match_date', '>', $rugbyMatch->match_date)
                ->orWhere(fn ($q) => $q->where('match_date', $rugbyMatch->match_date)->where('id', '>', $rugbyMatch->id)))
            ->orderBy('match_date')->orderBy('id')
            ->first();

        // Numéro du match dans l'histoire du XV de France
        $franceMatchNumber = RugbyMatch::where('match_date', '<', $rugbyMatch->match_date)->count() + 1;

        return view('matches.show', [
            'rugbyMatch' => $rugbyMatch,
            'sheet' => $sheet,
            'headToHead' => $headToHead,
            'headToHeadTotal' => $headToHeadTotal,
            'recentMeetings' => $recentMeetings,
            'meetingNumber' => $meetingNumber,
            'previous' => $previous,
            'next' => $next,
            'franceMatchNumber' => $franceMatchNumber,
        ]);
    }
}
