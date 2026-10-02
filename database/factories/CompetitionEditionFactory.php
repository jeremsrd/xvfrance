<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\CompetitionEdition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionEdition>
 */
class CompetitionEditionFactory extends Factory
{
    public function definition(): array
    {
        $year = fake()->numberBetween(1910, 2025);

        return [
            'competition_id' => Competition::factory(),
            'year' => $year,
            'label' => "Tournoi {$year}",
            'france_ranking' => fake()->numberBetween(1, 6),
        ];
    }
}
