<?php

namespace Database\Factories;

use App\Models\Coach;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coach>
 */
class CoachFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->date(),
            'birth_city' => fake()->city(),
            'country_id' => Country::factory(),
        ];
    }
}
