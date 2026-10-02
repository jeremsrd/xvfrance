<?php

namespace Database\Factories;

use App\Enums\TeamSide;
use App\Models\MatchSubstitution;
use App\Models\Player;
use App\Models\RugbyMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchSubstitution>
 */
class MatchSubstitutionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'match_id' => RugbyMatch::factory(),
            'player_off_id' => Player::factory(),
            'player_on_id' => Player::factory(),
            'minute' => fake()->numberBetween(40, 80),
            'is_tactical' => true,
            'team_side' => TeamSide::FRANCE,
        ];
    }
}
