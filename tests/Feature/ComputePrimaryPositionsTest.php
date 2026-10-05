<?php

namespace Tests\Feature;

use App\Enums\PlayerPosition;
use App\Models\MatchLineup;
use App\Models\Player;
use App\Models\RugbyMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComputePrimaryPositionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_position_is_the_most_played_one(): void
    {
        $player = Player::factory()->create(['primary_position' => null]);
        $bench = Player::factory()->create(['primary_position' => null]);
        foreach ([[PlayerPosition::CENTRE, '1990-01-01'], [PlayerPosition::AILIER, '1991-01-01'], [PlayerPosition::AILIER, '1992-01-01'], [null, '1993-01-01']] as [$position, $date]) {
            MatchLineup::factory()->create([
                'player_id' => $player->id,
                'match_id' => RugbyMatch::factory()->create(['match_date' => $date])->id,
                'position_played' => $position,
            ]);
        }
        MatchLineup::factory()->create(['player_id' => $bench->id, 'is_starter' => false, 'position_played' => null]);

        $this->artisan('xv:compute-primary-positions')->assertSuccessful();

        $this->assertSame(PlayerPosition::AILIER, $player->fresh()->primary_position);
        $this->assertNull($bench->fresh()->primary_position);
    }
}
