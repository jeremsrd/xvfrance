<?php

namespace Database\Factories;

use App\Enums\CompetitionType;
use App\Models\Competition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Tournoi des 6 Nations',
            'short_name' => '6 Nations',
            'type' => CompetitionType::TOURNOI,
        ];
    }
}
