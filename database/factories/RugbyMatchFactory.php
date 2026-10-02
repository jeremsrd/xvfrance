<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\RugbyMatch;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RugbyMatch>
 */
class RugbyMatchFactory extends Factory
{
    protected $model = RugbyMatch::class;

    public function definition(): array
    {
        return [
            'match_date' => fake()->unique()->dateTimeBetween('1906-01-01', '2025-12-31')->format('Y-m-d'),
            'venue_id' => Venue::factory(),
            'opponent_id' => Country::factory(),
            'france_score' => fake()->numberBetween(0, 50),
            'opponent_score' => fake()->numberBetween(0, 50),
            'is_home' => fake()->boolean(),
            'is_neutral' => false,
        ];
    }

    public function score(int $france, int $opponent): static
    {
        return $this->state(['france_score' => $france, 'opponent_score' => $opponent]);
    }
}
