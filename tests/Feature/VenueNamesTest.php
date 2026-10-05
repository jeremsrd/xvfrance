<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\RugbyMatch;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VenueNamesTest extends TestCase
{
    use RefreshDatabase;

    private string $path;
    private Country $fra;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = tempnam(sys_get_temp_dir(), 'venue_names') . '.csv';
        $this->fra = Country::factory()->create(['code' => 'FRA']);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);

        parent::tearDown();
    }

    private function writeCsv(array $rows): void
    {
        $lines = ['venue,city,country_code,name,kind,used_until,source'];
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(fn ($v) => '"' . $v . '"', $row)) . ',';
        }
        file_put_contents($this->path, implode("\n", $lines) . "\n");
    }

    private function runImport(): void
    {
        $this->artisan('xv:import-venue-names', ['file' => $this->relativePath()])->assertSuccessful();
    }

    private function relativePath(): string
    {
        // La commande résout le chemin depuis la racine du projet
        return str_repeat('../', substr_count(base_path(), '/')) . ltrim($this->path, '/');
    }

    public function test_duplicates_are_merged_into_the_current_name(): void
    {
        $mauroy = Venue::factory()->create(['name' => 'Stade Pierre-Mauroy', 'city' => "Villeneuve-d'Ascq", 'country_id' => $this->fra->id]);
        $grandStade = Venue::factory()->create(['name' => 'Grand Stade Lille Metropole', 'city' => 'Lille', 'country_id' => $this->fra->id]);
        $decathlon = Venue::factory()->create(['name' => 'Decathlon Arena', 'city' => 'Lille', 'country_id' => $this->fra->id]);
        $match2012 = RugbyMatch::factory()->create(['venue_id' => $grandStade->id, 'match_date' => '2012-11-17']);
        $match2024 = RugbyMatch::factory()->create(['venue_id' => $decathlon->id, 'match_date' => '2024-02-25']);

        $this->writeCsv([
            ['Stade Pierre-Mauroy', "Villeneuve-d'Ascq", 'FRA', 'Grand Stade Lille Métropole', 'former', '2013-06-01'],
            ['Stade Pierre-Mauroy', "Villeneuve-d'Ascq", 'FRA', 'Decathlon Arena', 'variant', ''],
        ]);
        $this->runImport();

        $this->assertSame(1, Venue::count());
        $this->assertSame($mauroy->id, $match2012->fresh()->venue_id);
        $this->assertSame($mauroy->id, $match2024->fresh()->venue_id);
        $this->assertSame(2, $mauroy->names()->count());

        // Une seconde passe ne change rien
        $this->runImport();
        $this->assertSame(1, Venue::count());
        $this->assertSame(2, $mauroy->names()->count());
    }

    public function test_venue_is_renamed_and_keeps_its_former_name_for_older_matches(): void
    {
        $venue = Venue::factory()->create(['name' => 'U Arena', 'city' => 'Nanterre', 'country_id' => $this->fra->id]);

        $this->writeCsv([['Paris La Défense Arena', 'Nanterre', 'FRA', 'U Arena', 'former', '2018-06-01']]);
        $this->runImport();

        $venue->refresh()->load('names');
        $this->assertSame('Paris La Défense Arena', $venue->name);
        $this->assertSame('U Arena', $venue->nameOn(Carbon::parse('2017-11-25')));
        $this->assertSame('Paris La Défense Arena', $venue->nameOn(Carbon::parse('2026-11-07')));
    }

    public function test_name_on_date_follows_successive_former_names(): void
    {
        $venue = Venue::factory()->create(['name' => 'Marvel Stadium']);
        $venue->names()->createMany([
            ['name' => 'Colonial Stadium', 'used_until' => '2002-01-01'],
            ['name' => 'Telstra Dome', 'used_until' => '2009-03-01'],
            ['name' => 'Etihad Stadium', 'used_until' => '2018-09-01'],
            ['name' => 'Docklands Stadium', 'used_until' => null],
        ]);
        $venue->load('names');

        $this->assertSame('Colonial Stadium', $venue->nameOn(Carbon::parse('2000-06-01')));
        $this->assertSame('Telstra Dome', $venue->nameOn(Carbon::parse('2003-11-09')));
        $this->assertSame('Etihad Stadium', $venue->nameOn(Carbon::parse('2014-06-14')));
        $this->assertSame('Marvel Stadium', $venue->nameOn(Carbon::parse('2026-07-01')));
    }

    public function test_venue_is_found_by_any_of_its_names(): void
    {
        $velodrome = Venue::factory()->create(['name' => 'Stade Vélodrome']);
        $velodrome->names()->create(['name' => 'Orange Vélodrome']);

        $this->assertTrue($velodrome->is(Venue::findByAnyName('Stade Velodrome')));
        $this->assertTrue($velodrome->is(Venue::findByAnyName('orange velodrome')));
        $this->assertNull(Venue::findByAnyName('Stade de France'));
    }

    public function test_error_names_are_merged_but_not_kept(): void
    {
        $stadium = Venue::factory()->create(['name' => 'Stade du Nord', 'city' => "Villeneuve-d'Ascq", 'country_id' => $this->fra->id]);
        $wrong = Venue::factory()->create(['name' => 'Estadio Niort', 'city' => 'Lille', 'country_id' => $this->fra->id]);
        $match = RugbyMatch::factory()->create(['venue_id' => $wrong->id]);

        $this->writeCsv([
            ['Stadium Lille Métropole', "Villeneuve-d'Ascq", 'FRA', 'Stade du Nord', 'variant', ''],
            ['Stadium Lille Métropole', "Villeneuve-d'Ascq", 'FRA', 'Estadio Niort', 'error', ''],
        ]);
        $this->runImport();

        $this->assertSame($stadium->id, $match->fresh()->venue_id);
        $this->assertSame('Stadium Lille Métropole', $stadium->fresh()->name);
        $this->assertNull(Venue::findByAnyName('Estadio Niort'));
    }
}
