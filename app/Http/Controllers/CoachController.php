<?php

namespace App\Http\Controllers;

use App\Enums\CoachRole;
use App\Models\Coach;
use App\Models\RugbyMatch;

class CoachController extends Controller
{
    public function index()
    {
        $coaches = Coach::whereHas('tenures', fn ($q) => $q->where('role', CoachRole::SELECTIONNEUR))
            ->with(['country', 'tenures' => fn ($q) => $q->where('role', CoachRole::SELECTIONNEUR)->orderByDesc('start_date')])
            ->get()
            ->sortByDesc(fn ($coach) => $coach->tenures->first()->start_date)
            ->values()
            ->map(function ($coach) {
                $tenure = $coach->tenures->first();
                $matches = $this->getMatchesForTenure($tenure);
                $coach->tenure = $tenure;
                $coach->total_matches = $matches->count();
                $coach->wins = $matches->filter(fn ($m) => $m->france_score > $m->opponent_score)->count();
                $coach->losses = $matches->filter(fn ($m) => $m->france_score < $m->opponent_score)->count();
                $coach->draws = $matches->filter(fn ($m) => $m->france_score === $m->opponent_score)->count();
                $coach->win_pct = $coach->total_matches > 0
                    ? round(($coach->wins / $coach->total_matches) * 100, 1)
                    : 0;
                return $coach;
            });

        return view('coaches.index', compact('coaches'));
    }

    public function show(Coach $coach)
    {
        $coach->load(['country', 'tenures' => fn ($q) => $q->orderByDesc('start_date')]);

        $selectorTenure = $coach->tenures->firstWhere('role', CoachRole::SELECTIONNEUR);

        $matches = collect();
        $wins = $losses = $draws = 0;
        $winPct = 0;

        if ($selectorTenure) {
            $matches = $this->getMatchesForTenure($selectorTenure)
                ->load(['opponent', 'venue', 'edition.competition']);
            $wins = $matches->filter(fn ($m) => $m->france_score > $m->opponent_score)->count();
            $losses = $matches->filter(fn ($m) => $m->france_score < $m->opponent_score)->count();
            $draws = $matches->filter(fn ($m) => $m->france_score === $m->opponent_score)->count();
            $winPct = $matches->count() > 0
                ? round(($wins / $matches->count()) * 100, 1)
                : 0;
        }

        return view('coaches.show', compact('coach', 'selectorTenure', 'matches', 'wins', 'losses', 'draws', 'winPct'));
    }

    private function getMatchesForTenure($tenure)
    {
        $query = RugbyMatch::where('match_date', '>=', $tenure->start_date);

        if ($tenure->end_date) {
            $query->where('match_date', '<=', $tenure->end_date);
        }

        return $query->orderByDesc('match_date')->get();
    }
}
