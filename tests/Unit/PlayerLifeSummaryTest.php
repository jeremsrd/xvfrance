<?php

namespace Tests\Unit;

use App\Models\Country;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlayerLifeSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_birth_and_death_summaries_in_french(): void
    {
        $france = Country::factory()->france()->create();
        $player = Player::factory()->make([
            'country_id' => $france->id, 'birth_date' => '1880-03-01', 'birth_city' => 'Bordeaux',
            'death_date' => '1950-06-12', 'death_city' => 'Paris',
        ]);

        $this->assertSame("Né le 1\u{1D49}\u{02B3} mars 1880 à Bordeaux", $player->birthSummary());
        $this->assertSame('Mort le 12 juin 1950 à Paris', $player->deathSummary());
        $this->assertSame(70, $player->age());
    }

    public function test_partial_or_missing_data(): void
    {
        $samoa = Country::factory()->create(['name' => 'Samoa']);
        $france = Country::factory()->france()->create();
        $player = Player::factory()->make(['country_id' => $france->id, 'birth_city' => 'Apia', 'birth_country_id' => $samoa->id, 'birth_date' => null]);

        $this->assertSame('Né à Apia (Samoa)', $player->birthSummary());
        $this->assertNull($player->deathSummary());
        $this->assertNull($player->age());
        $this->assertNull(Player::factory()->make(['birth_date' => null, 'birth_city' => null])->birthSummary());
    }

    public function test_age_of_living_player(): void
    {
        Carbon::setTestNow('2026-10-02');
        $player = Player::factory()->make(['birth_date' => '1996-11-15', 'death_date' => null]);

        $this->assertSame(29, $player->age());
        Carbon::setTestNow();
    }
}
