<?php

namespace App\Http\Controllers;

use App\Enums\CoachRole;
use App\Models\Coach;
use App\Models\Competition;
use App\Models\Country;
use App\Models\Player;
use App\Models\RugbyMatch;
use App\Support\RecordSummary;

class HomeController extends Controller
{
    public function index()
    {
        // Le dernier match est mis en avant, la liste affiche les 5 précédents
        $matches = RugbyMatch::with(['opponent', 'venue', 'edition.competition'])
            ->orderBy('match_date', 'desc')
            ->take(6)
            ->get();

        $latestMatch = $matches->first();
        $recentMatches = $matches->skip(1)->values();

        $record = RecordSummary::fromQuery(RugbyMatch::query());
        $firstMatchDate = RugbyMatch::min('match_date');

        $biggestWin = RugbyMatch::with('opponent')
            ->wins()
            ->orderByRaw('(france_score - opponent_score) DESC')
            ->first();

        $mostFaced = Country::withCount('matchesAsOpponent')
            ->orderBy('matches_as_opponent_count', 'desc')
            ->first();

        $counts = [
            'matches' => $record->total,
            'players' => Player::french()->count(),
            'opponents' => RugbyMatch::distinct()->count('opponent_id'),
            'competitions' => Competition::count(),
            'coaches' => Coach::whereHas('tenures', fn ($q) => $q->where('role', CoachRole::SELECTIONNEUR))->count(),
        ];

        return view('home.index', compact(
            'latestMatch',
            'recentMatches',
            'record',
            'firstMatchDate',
            'biggestWin',
            'mostFaced',
            'counts'
        ));
    }
}
