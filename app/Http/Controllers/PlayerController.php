<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Services\RecordsService;
use App\Support\PlayerProfile;

class PlayerController extends Controller
{
    public function show(Player $player, RecordsService $records)
    {
        $player->load(['country', 'birthCountry', 'clubMemberships.club']);

        return view('players.show', [
            'player' => $player,
            'profile' => new PlayerProfile($player),
            'recordReigns' => $player->country?->code === 'FRA' ? $records->playerRecordReigns($player) : [],
        ]);
    }
}
