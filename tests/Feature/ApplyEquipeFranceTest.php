<?php

namespace Tests\Feature;

use App\Enums\MatchStage;
use App\Models\Country;
use App\Models\RugbyMatch;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplyEquipeFranceTest extends TestCase
{
    use RefreshDatabase;

    private string $path;
    private Country $sco;
    private Country $fij;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = tempnam(sys_get_temp_dir(), 'equipe_france') . '.csv';
        Country::factory()->create(['name' => 'France', 'code' => 'FRA']);
        $this->sco = Country::factory()->create(['name' => 'Écosse', 'code' => 'SCO']);
        $this->fij = Country::factory()->create(['name' => 'Fidji', 'code' => 'FIJ']);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);

        parent::tearDown();
    }

    private function runApply(array $rows): void
    {
        $lines = ['site_id,date,home,away,home_score,away_score,competition,round,venue,city,url'];
        foreach ($rows as $i => $row) {
            $lines[] = implode(',', array_map(fn ($v) => '"' . $v . '"', [$i + 1, ...$row, '/url']));
        }
        file_put_contents($this->path, implode("\n", $lines) . "\n");

        $relative = str_repeat('../', substr_count(base_path(), '/')) . ltrim($this->path, '/');
        $this->artisan('xv:apply-equipe-france', ['file' => $relative])->assertSuccessful();
    }

    public function test_fills_stage_round_number_and_venue(): void
    {
        $murrayfield = Venue::factory()->create(['name' => 'Murrayfield Stadium', 'country_id' => $this->sco->id]);
        $tournoi = RugbyMatch::factory()->create(['match_date' => '1998-02-21', 'opponent_id' => $this->sco->id, 'venue_id' => null, 'stage' => null, 'match_number' => null]);
        // Match lointain daté la veille dans la base (décalage horaire)
        $quart = RugbyMatch::factory()->create(['match_date' => '1987-06-06', 'opponent_id' => $this->fij->id, 'stage' => null]);

        $this->runApply([
            ['1998-02-21', 'Écosse', 'France', '16', '51', 'Tournoi des Cinq Nations', '2e journée', 'Murrayfield Stadium', 'Édimbourg'],
            ['1987-06-07', 'Fidji', 'France', '16', '31', 'Coupe du monde', 'Quart-de-finale', '', 'Auckland'],
        ]);

        $tournoi->refresh();
        $this->assertSame(MatchStage::JOURNEE, $tournoi->stage);
        $this->assertSame(2, $tournoi->match_number);
        $this->assertSame($murrayfield->id, $tournoi->venue_id);
        $this->assertSame(MatchStage::QUART, $quart->fresh()->stage);
        // Ville seule : pas de fiche de stade au nom de la ville
        $this->assertSame(0, Venue::where('name', 'Auckland')->count());
    }

    public function test_existing_values_are_kept(): void
    {
        $venue = Venue::factory()->create();
        $match = RugbyMatch::factory()->create([
            'match_date' => '2007-10-13', 'opponent_id' => $this->sco->id, 'venue_id' => $venue->id, 'stage' => MatchStage::DEMI,
        ]);

        $this->runApply([['2007-10-13', 'France', 'Écosse', '9', '14', 'Coupe du monde', 'Quart-de-finale', 'Stade de France', 'Saint-Denis']]);

        $this->assertSame(MatchStage::DEMI, $match->fresh()->stage);
        $this->assertSame($venue->id, $match->fresh()->venue_id);
        $this->assertSame(0, Venue::where('name', 'Stade de France')->count());
    }
}
