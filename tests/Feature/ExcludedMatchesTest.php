<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\MatchLineup;
use App\Models\RugbyMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExcludedMatchesTest extends TestCase
{
    use RefreshDatabase;

    public function test_matches_without_official_caps_are_removed(): void
    {
        $ita = Country::factory()->create(['code' => 'ITA']);
        // Championnat FIRA 1975, listé dans excluded_matches.csv
        $fira = RugbyMatch::factory()->create(['match_date' => '1975-02-15', 'opponent_id' => $ita->id]);
        MatchLineup::factory()->create(['match_id' => $fira->id]);
        $tournoi = RugbyMatch::factory()->create(['match_date' => '1975-03-15', 'opponent_id' => $ita->id]);

        $this->artisan('xv:remove-excluded-matches')->assertSuccessful();

        $this->assertModelMissing($fira);
        $this->assertSame(0, MatchLineup::count());
        $this->assertModelExists($tournoi);
    }
}
