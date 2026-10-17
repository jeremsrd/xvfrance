<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\MatchLineup;
use Illuminate\Support\Facades\DB;

class ClubController extends Controller
{
    public function index()
    {
        $clubs = Club::with('country')
            ->whereHas('memberships')
            ->withCount(['memberships as internationals_count' => fn ($q) => $q->select(DB::raw('count(distinct player_id)'))])
            ->orderByDesc('internationals_count')
            ->orderBy('name')
            ->get();

        return view('clubs.index', compact('clubs'));
    }

    public function show(Club $club)
    {
        $club->load(['country', 'memberships']);

        // Sélections, première et dernière sélection de chaque international du club
        $caps = fn ($aggregate) => MatchLineup::selectRaw($aggregate)
            ->join('matches', 'matches.id', '=', 'match_lineups.match_id')
            ->whereColumn('match_lineups.player_id', 'players.id')
            ->where('match_lineups.team_side', 'france')
            ->where('match_lineups.played', true);

        $players = $club->players()
            ->select('players.*')
            ->addSelect(['caps' => $caps('count(*)'), 'first_cap' => $caps('min(match_date)'), 'last_cap' => $caps('max(match_date)')])
            ->get()
            ->sortBy([['first_cap', 'asc'], ['last_name', 'asc']])
            ->values();

        return view('clubs.show', compact('club', 'players'));
    }
}
