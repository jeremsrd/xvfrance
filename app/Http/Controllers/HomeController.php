<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\RugbyMatch;
use App\Support\RecordSummary;

class HomeController extends Controller
{
    public function index()
    {
        $latestMatch = RugbyMatch::with(['opponent', 'venue', 'edition.competition'])
            ->orderBy('match_date', 'desc')
            ->first();

        $recentMatches = RugbyMatch::with(['opponent', 'venue', 'edition.competition'])
            ->orderBy('match_date', 'desc')
            ->take(5)
            ->get();

        $record = RecordSummary::fromQuery(RugbyMatch::query());

        $biggestWin = RugbyMatch::with('opponent')
            ->wins()
            ->orderByRaw('(france_score - opponent_score) DESC')
            ->first();

        $mostFaced = Country::withCount('matchesAsOpponent')
            ->orderBy('matches_as_opponent_count', 'desc')
            ->first();

        return view('home.index', compact(
            'latestMatch',
            'recentMatches',
            'record',
            'biggestWin',
            'mostFaced'
        ));
    }
}
