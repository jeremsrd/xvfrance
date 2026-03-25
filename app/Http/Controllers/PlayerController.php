<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Models\Player;

class PlayerController extends Controller
{
    public function show(Player $player)
    {
        $player->load('country');

        $lineups = $player->lineups()
            ->with(['match.opponent', 'match.edition.competition', 'match.venue'])
            ->join('matches', 'match_lineups.match_id', '=', 'matches.id')
            ->orderBy('matches.match_date', 'desc')
            ->select('match_lineups.*')
            ->get();

        $events = $player->events()
            ->with(['match.opponent'])
            ->join('matches', 'match_events.match_id', '=', 'matches.id')
            ->orderBy('matches.match_date', 'desc')
            ->select('match_events.*')
            ->get();

        // Stats
        $totalCaps = $lineups->count();
        $starts = $lineups->where('is_starter', true)->count();
        $captaincies = $lineups->where('is_captain', true)->count();
        $tries = $events->where('event_type', EventType::ESSAI)->count();

        $points = $events->sum(fn ($e) => $e->event_type->points($e->match->match_date));

        return view('players.show', compact(
            'player', 'lineups', 'events',
            'totalCaps', 'starts', 'captaincies', 'tries', 'points'
        ));
    }
}
