<?php

namespace Database\Factories;

use App\Enums\Continent;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->country(),
            // Préfixe Z pour ne jamais entrer en collision avec un vrai code (FRA, NZL…)
            'code' => 'Z' . strtoupper(fake()->unique()->lexify('??')),
            'continent' => fake()->randomElement(Continent::cases()),
            'flag_emoji' => '🏳️',
        ];
    }

    public function france(): static
    {
        return $this->state([
            'name' => 'France',
            'code' => 'FRA',
            'continent' => Continent::EUROPE,
            'flag_emoji' => '🇫🇷',
        ]);
    }
}
