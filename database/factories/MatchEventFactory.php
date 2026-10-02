<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Enums\TeamSide;
use App\Models\MatchEvent;
use App\Models\Player;
use App\Models\RugbyMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchEvent>
 */
class MatchEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'match_id' => RugbyMatch::factory(),
            'player_id' => Player::factory(),
            'event_type' => EventType::ESSAI,
            'minute' => fake()->numberBetween(1, 80),
            'team_side' => TeamSide::FRANCE,
        ];
    }
}
