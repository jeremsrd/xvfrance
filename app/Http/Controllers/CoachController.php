<?php

namespace App\Http\Controllers;

use App\Enums\CoachRole;
use App\Models\Coach;
use App\Models\RugbyMatch;
use App\Support\RecordSummary;
use Illuminate\Database\Eloquent\Builder;

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
                $coach->tenure = $tenure;
                $coach->record = RecordSummary::fromQuery($this->matchesForTenure($tenure));
                return $coach;
            });

        return view('coaches.index', compact('coaches'));
    }

    public function show(Coach $coach)
    {
        $coach->load(['country', 'tenures' => fn ($q) => $q->orderByDesc('start_date')]);

        $selectorTenure = $coach->tenures->firstWhere('role', CoachRole::SELECTIONNEUR);

        $matches = $selectorTenure
            ? $this->matchesForTenure($selectorTenure)
                ->with(['opponent', 'venue', 'edition.competition'])
                ->orderByDesc('match_date')
                ->get()
            : collect();

        $record = RecordSummary::fromMatches($matches);

        return view('coaches.show', compact('coach', 'selectorTenure', 'matches', 'record'));
    }

    private function matchesForTenure($tenure): Builder
    {
        $query = RugbyMatch::where('match_date', '>=', $tenure->start_date);

        if ($tenure->end_date) {
            $query->where('match_date', '<=', $tenure->end_date);
        }

        return $query;
    }
}
