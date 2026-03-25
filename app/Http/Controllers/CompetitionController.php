<?php

namespace App\Http\Controllers;

use App\Models\Competition;

class CompetitionController extends Controller
{
    public function index()
    {
        $competitions = Competition::withCount(['editions'])
            ->with(['editions.matches'])
            ->orderBy('name')
            ->get()
            ->map(function ($competition) {
                $competition->total_matches = $competition->editions->sum(fn ($e) => $e->matches->count());
                return $competition;
            });

        return view('competitions.index', compact('competitions'));
    }

    public function show(Competition $competition)
    {
        $editions = $competition->editions()
            ->withCount('matches')
            ->with('matches')
            ->orderByDesc('year')
            ->get()
            ->map(function ($edition) {
                $matches = $edition->matches;
                $edition->wins = $matches->filter(fn ($m) => $m->france_score > $m->opponent_score)->count();
                $edition->losses = $matches->filter(fn ($m) => $m->france_score < $m->opponent_score)->count();
                $edition->draws = $matches->filter(fn ($m) => $m->france_score === $m->opponent_score)->count();
                return $edition;
            });

        return view('competitions.show', compact('competition', 'editions'));
    }
}
