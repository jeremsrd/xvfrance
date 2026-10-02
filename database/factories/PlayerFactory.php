<?php

namespace Database\Factories;

use App\Enums\PlayerPosition;
use App\Models\Country;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->date(),
            'birth_city' => fake()->city(),
            'country_id' => Country::factory(),
            'primary_position' => fake()->randomElement(PlayerPosition::cases()),
            'is_active' => true,
        ];
    }
}
