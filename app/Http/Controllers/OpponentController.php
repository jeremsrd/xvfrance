<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\RugbyMatch;

class OpponentController extends Controller
{
    public function index()
    {
        $opponents = Country::withCount('matchesAsOpponent')
            ->whereHas('matchesAsOpponent')
            ->orderBy('matches_as_opponent_count', 'desc')
            ->get()
            ->map(function ($country) {
                $country->victories = RugbyMatch::where('opponent_id', $country->id)
                    ->whereColumn('france_score', '>', 'opponent_score')->count();
                $country->defeats = RugbyMatch::where('opponent_id', $country->id)
                    ->whereColumn('france_score', '<', 'opponent_score')->count();
                $country->draws = RugbyMatch::where('opponent_id', $country->id)
                    ->whereColumn('france_score', '=', 'opponent_score')->count();
                return $country;
            });

        return view('opponents.index', compact('opponents'));
    }

    public function show(Country $country)
    {
        $matches = RugbyMatch::with(['venue', 'edition.competition'])
            ->where('opponent_id', $country->id)
            ->orderBy('match_date', 'desc')
            ->get();

        $victories = $matches->where('is_victory', true)->count();
        $defeats = $matches->where('is_defeat', true)->count();
        $draws = $matches->count() - $victories - $defeats;

        $stats = [
            'total' => $matches->count(),
            'victories' => $victories,
            'defeats' => $defeats,
            'draws' => $draws,
            'win_pct' => $matches->count() > 0 ? round(($victories / $matches->count()) * 100, 1) : 0,
        ];

        $biggestWin = $matches->where('is_victory', true)->sortByDesc('point_diff')->first();
        $biggestLoss = $matches->where('is_defeat', true)->sortBy('point_diff')->first();

        return view('opponents.show', compact('country', 'matches', 'stats', 'biggestWin', 'biggestLoss'));
    }
}
