<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\MatchLineup;
use App\Models\Player;
use App\Models\RugbyMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportPlayerPhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_portraits_go_to_pre_1930_players_only(): void
    {
        $france = Country::factory()->create(['code' => 'FRA']);
        $playedIn = function (string $first, string $last, string $date, array $attributes = []) use ($france) {
            $player = Player::factory()->create(['first_name' => $first, 'last_name' => $last, 'country_id' => $france->id, 'photo_path' => null, ...$attributes]);
            MatchLineup::factory()->create(['player_id' => $player->id, 'match_id' => RugbyMatch::factory()->create(['match_date' => $date])->id]);

            return $player;
        };
        $lane = $playedIn('Gaston', 'Lane', '1906-03-22');
        $modern = $playedIn('Henri', 'Amand', '2020-02-02');
        $chosen = $playedIn('Marcel', 'Communeau', '1906-01-01', ['photo_path' => 'https://example.org/communeau.jpg']);

        $csv = tempnam(sys_get_temp_dir(), 'photos') . '.csv';
        file_put_contents($csv, "source,country_code,first_name,last_name,file\n"
            . "a.png,FRA,Gaston,Lane,fra-gaston-lane.webp\n"
            . "b.png,FRA,Henri,Amand,fra-henri-amand.webp\n"
            . "c.png,FRA,Marcel,Communeau,fra-marcel-communeau.webp\n");

        $this->artisan('xv:import-player-photos', ['file' => $csv])->assertSuccessful();
        @unlink($csv);

        $this->assertSame('/images/joueurs/fra-gaston-lane.webp', $lane->fresh()->photo_path);
        $this->assertNull($modern->fresh()->photo_path);
        $this->assertSame('https://example.org/communeau.jpg', $chosen->fresh()->photo_path);
    }
}
