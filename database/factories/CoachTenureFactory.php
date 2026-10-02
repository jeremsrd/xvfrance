<?php

namespace Database\Factories;

use App\Enums\CoachRole;
use App\Models\Coach;
use App\Models\CoachTenure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoachTenure>
 */
class CoachTenureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'coach_id' => Coach::factory(),
            'role' => CoachRole::SELECTIONNEUR,
            'start_date' => '2020-01-01',
            'end_date' => null,
        ];
    }
}
