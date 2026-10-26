<?php

namespace Tests\Feature;

use App\Enums\TeamSide;
use App\Models\Country;
use App\Models\RugbyMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportMatchPhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_photos_go_to_their_match_and_show_on_the_match_sheet(): void
    {
        Country::factory()->france()->create();
        $scotland = Country::factory()->create(['name' => 'Écosse', 'code' => 'SCO']);
        $match = RugbyMatch::factory()->create(['match_date' => '1913-01-01', 'opponent_id' => $scotland->id]);

        // Images versionnées de public/images/matches ; la dernière ligne ne correspond à aucun match
        $csv = tempnam(sys_get_temp_dir(), 'photos') . '.csv';
        file_put_contents($csv, "source,match_date,opponent_code,team_side,caption,file\n"
            . "a.jpeg,1913-01-01,SCO,france,,1913-01-01-sco-france.webp\n"
            . "b.jpeg,1913-01-01,SCO,adversaire,,1913-01-01-sco-adversaire.webp\n"
            . "c.jpeg,1913-01-01,SCO,,Phase de jeu,1913-01-01-sco-adversaire-2.webp\n"
            . "d.jpeg,1913-01-02,SCO,france,,1913-01-01-sco-france.webp\n");

        $this->artisan('xv:import-match-photos', ['file' => $csv])->assertSuccessful();
        $this->artisan('xv:import-match-photos', ['file' => $csv])->assertSuccessful();  // reconstruit, sans doublon
        @unlink($csv);

        $photos = $match->photos()->get();
        $this->assertCount(3, $photos);
        $this->assertSame(TeamSide::FRANCE, $photos[0]->team_side);
        $this->assertSame([1, 2, 3], $photos->pluck('position')->all());
        $this->assertGreaterThan(0, $photos[0]->width);

        $this->get(route('matches.show', $match))
            ->assertSee('Photos d&#039;époque', false)
            ->assertSee('src="/images/matches/1913-01-01-sco-france.webp"', false)
            ->assertSee('Le XV de France')
            ->assertSee('L&#039;équipe d&#039;Écosse', false)
            ->assertSee('Phase de jeu');
    }
}
