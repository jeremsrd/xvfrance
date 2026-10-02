<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Support\PlayerProfile;

class PlayerController extends Controller
{
    public function show(Player $player)
    {
        $player->load(['country', 'birthCountry']);

        return view('players.show', [
            'player' => $player,
            'profile' => new PlayerProfile($player),
        ]);
    }
}
