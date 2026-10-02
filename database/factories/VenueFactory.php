<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Stade ' . fake()->lastName(),
            'city' => fake()->city(),
            'country_id' => Country::factory(),
            'capacity' => fake()->numberBetween(10_000, 80_000),
            'opened_year' => fake()->numberBetween(1900, 2020),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ];
    }
}
