<?php

namespace Database\Factories;

use App\Enums\PlayerPosition;
use App\Enums\TeamSide;
use App\Models\MatchLineup;
use App\Models\Player;
use App\Models\RugbyMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchLineup>
 */
class MatchLineupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'match_id' => RugbyMatch::factory(),
            'player_id' => Player::factory(),
            'jersey_number' => 1,
            'is_starter' => true,
            'position_played' => PlayerPosition::PILIER_GAUCHE,
            'is_captain' => false,
            'team_side' => TeamSide::FRANCE,
        ];
    }
}
