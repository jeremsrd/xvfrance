<?php

namespace App\Http\Controllers;

use App\Models\RugbyMatch;

class MatchController extends Controller
{
    public function show(RugbyMatch $rugbyMatch)
    {
        $rugbyMatch->load([
            'opponent',
            'venue',
            'edition.competition',
            'refereeCountry',
            'lineups.player',
            'events.player',
            'substitutions.playerOff',
            'substitutions.playerOn',
        ]);

        return view('matches.show', compact('rugbyMatch'));
    }
}
