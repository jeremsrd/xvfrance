<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\RugbyMatch;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchVenueImportTest extends TestCase
{
    use RefreshDatabase;

    private string $path;
    private Country $fra;
    private Country $eng;
    private Country $aus;
    private Country $fij;
    private Venue $twickenham;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = tempnam(sys_get_temp_dir(), 'results') . '.csv';
        $this->fra = Country::factory()->create(['name' => 'France', 'code' => 'FRA']);
        $this->eng = Country::factory()->create(['name' => 'Angleterre', 'code' => 'ENG']);
        $this->aus = Country::factory()->create(['name' => 'Australie', 'code' => 'AUS']);
        $this->fij = Country::factory()->create(['name' => 'Fidji', 'code' => 'FIJ']);
        $this->twickenham = Venue::factory()->create(['name' => 'Twickenham Stadium', 'city' => 'Londres', 'country_id' => $this->eng->id]);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);

        parent::tearDown();
    }

    private function writeCsv(array $rows): void
    {
        $lines = ['date,home_team,away_team,home_score,away_score,competition,stadium,city,country,neutral,world_cup'];
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(fn ($v) => '"' . $v . '"', $row));
        }
        file_put_contents($this->path, implode("\n", $lines) . "\n");
    }

    public function test_generic_stadium_name_is_not_matched_partially(): void
    {
        $this->writeCsv([
            ['2005-11-19', 'France', 'Fiji', '20', '10', '', 'Stadium', 'Toulouse', 'France', 'False', 'False'],
            ['2003-10-11', 'France', 'Fiji', '61', '18', 'Rugby World Cup 2003', 'Suncorp Stadium', 'Brisbane', 'Australia', 'False', 'True'],
        ]);

        $this->artisan('xv:import-csv', ['file' => $this->path])->assertSuccessful();

        $toulouse = RugbyMatch::whereDate('match_date', '2005-11-19')->first();
        $this->assertSame('Stadium', $toulouse->venue->name);
        $this->assertSame($this->fra->id, $toulouse->venue->country_id);
        $this->assertTrue($toulouse->is_home);
        $this->assertFalse($toulouse->is_neutral);

        // Coupe du Monde en Australie : terrain neutre, même si la colonne neutral du CSV dit le contraire
        $brisbane = RugbyMatch::whereDate('match_date', '2003-10-11')->first();
        $this->assertSame('Suncorp Stadium', $brisbane->venue->name);
        $this->assertTrue($brisbane->is_neutral);
        $this->assertSame(0, $this->twickenham->matches()->count());
    }

    public function test_variant_name_resolves_to_existing_venue(): void
    {
        $toulouse = Venue::factory()->create(['name' => 'Stadium de Toulouse', 'city' => 'Toulouse', 'country_id' => $this->fra->id]);
        $toulouse->names()->create(['name' => 'Stadium']);
        $this->writeCsv([['2005-11-19', 'France', 'Fiji', '20', '10', '', 'Stadium', 'Toulouse', 'France', 'False', 'False']]);

        $this->artisan('xv:import-csv', ['file' => $this->path])->assertSuccessful();

        $this->assertSame($toulouse->id, RugbyMatch::first()->venue_id);
    }

    public function test_sync_venues_fixes_existing_matches_without_creating_any(): void
    {
        $match = RugbyMatch::factory()->create([
            'match_date' => '2005-11-19', 'opponent_id' => $this->fij->id, 'venue_id' => $this->twickenham->id,
        ]);
        $this->writeCsv([
            ['2005-11-19', 'France', 'Fiji', '20', '10', '', 'Stadium', 'Toulouse', 'France', 'False', 'False'],
            ['2010-06-01', 'France', 'Netherlands', '50', '0', '', 'Stadium', 'Toulouse', 'France', 'False', 'False'],
        ]);

        $this->artisan('xv:import-csv', ['file' => $this->path, '--sync-venues' => true])->assertSuccessful();

        $this->assertSame('Stadium', $match->fresh()->venue->name);
        $this->assertSame(1, RugbyMatch::count());
        $this->assertSame(4, Country::count());
    }

    public function test_venue_flags_follow_the_venue_country(): void
    {
        $sydney = Venue::factory()->create(['country_id' => $this->aus->id]);
        $paris = Venue::factory()->create(['country_id' => $this->fra->id]);

        $neutral = RugbyMatch::factory()->create(['opponent_id' => $this->fij->id, 'venue_id' => $sydney->id, 'is_home' => true]);
        $away = RugbyMatch::factory()->create(['opponent_id' => $this->aus->id, 'venue_id' => $sydney->id, 'is_home' => true]);
        $home = RugbyMatch::factory()->create(['opponent_id' => $this->aus->id, 'venue_id' => $paris->id, 'is_home' => false]);

        $this->artisan('xv:compute-venue-flags')->assertSuccessful();

        $this->assertTrue($neutral->fresh()->is_neutral);
        $this->assertTrue($neutral->fresh()->is_home, 'terrain neutre : ordre d\'affichage conservé');
        $this->assertFalse($away->fresh()->is_neutral);
        $this->assertFalse($away->fresh()->is_home);
        $this->assertTrue($home->fresh()->is_home);
    }
}
