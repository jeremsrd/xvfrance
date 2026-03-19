<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\RugbyMatch;

class HomeController extends Controller
{
    public function index()
    {
        $latestMatch = RugbyMatch::with(['opponent', 'venue', 'edition.competition'])
            ->orderBy('match_date', 'desc')
            ->first();

        $recentMatches = RugbyMatch::with(['opponent', 'edition.competition'])
            ->orderBy('match_date', 'desc')
            ->take(5)
            ->get();

        $total = RugbyMatch::count();
        $victories = RugbyMatch::whereColumn('france_score', '>', 'opponent_score')->count();
        $defeats = RugbyMatch::whereColumn('france_score', '<', 'opponent_score')->count();
        $draws = RugbyMatch::whereColumn('france_score', '=', 'opponent_score')->count();

        $stats = [
            'total' => $total,
            'victories' => $victories,
            'defeats' => $defeats,
            'draws' => $draws,
            'win_pct' => $total > 0 ? round(($victories / $total) * 100, 1) : 0,
        ];

        $biggestWin = RugbyMatch::with('opponent')
            ->whereColumn('france_score', '>', 'opponent_score')
            ->orderByRaw('(france_score - opponent_score) DESC')
            ->first();

        $mostFaced = Country::withCount('matchesAsOpponent')
            ->orderBy('matches_as_opponent_count', 'desc')
            ->first();

        return view('home.index', compact(
            'latestMatch',
            'recentMatches',
            'stats',
            'biggestWin',
            'mostFaced'
        ));
    }
}
