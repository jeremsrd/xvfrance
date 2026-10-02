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

        $onThisDay = $this->onThisDay();

        return view('home.index', compact(
            'onThisDay',
            'latestMatch',
            'recentMatches',
            'record',
            'firstMatchDate',
            'biggestWin',
            'mostFaced',
            'counts'
        ));
    }

    /**
     * Matches joués à la date du jour dans l'histoire ; à défaut, les plus proches (± 3 jours).
     *
     * @return array{exact: bool, matches: \Illuminate\Support\Collection<int, RugbyMatch>}
     */
    private function onThisDay(): array
    {
        $today = now();
        $distance = function (RugbyMatch $m) use ($today) {
            $sameYear = $m->match_date->copy()->year($today->year);

            return abs($today->copy()->startOfDay()->diffInDays($sameYear, false));
        };

        $candidates = RugbyMatch::with(['opponent', 'edition.competition'])
            ->whereYear('match_date', '<', $today->year)
            ->get()
            ->map(fn (RugbyMatch $m) => [$m, $distance($m)])
            ->filter(fn ($pair) => $pair[1] <= 3)
            ->sortBy([fn ($a, $b) => $a[1] <=> $b[1], fn ($a, $b) => $b[0]->match_date <=> $a[0]->match_date]);

        $exact = $candidates->filter(fn ($pair) => $pair[1] === 0.0 || $pair[1] === 0);

        return [
            'exact' => $exact->isNotEmpty(),
            'matches' => ($exact->isNotEmpty() ? $exact : $candidates)->take(4)->map(fn ($pair) => $pair[0])->values(),
        ];
    }
}
