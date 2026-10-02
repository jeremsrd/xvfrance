<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Venue;
use App\Support\VenueCoordinatesFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueCoordinatesTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = tempnam(sys_get_temp_dir(), 'venues') . '.csv';
    }

    protected function tearDown(): void
    {
        @unlink($this->path);

        parent::tearDown();
    }

    public function test_file_round_trip_keeps_names_with_quotes_and_commas(): void
    {
        $file = new VenueCoordinatesFile($this->path);
        $row = ['name' => "Campo 'Las Cortas'", 'city' => 'Carson, California', 'country_code' => 'USA',
            'latitude' => '33.86', 'longitude' => '-118.26', 'precision' => 'stade', 'source' => 'nominatim'];

        $file->write([VenueCoordinatesFile::key($row['name'], $row['city'], 'USA') => $row]);

        $this->assertSame($row, $file->read()[VenueCoordinatesFile::key($row['name'], $row['city'], 'usa')]);
    }

    public function test_import_applies_coordinates_by_name_city_and_country(): void
    {
        $fra = Country::factory()->france()->create();
        $venue = Venue::factory()->create(['name' => 'Stade Vélodrome', 'city' => 'Marseille', 'country_id' => $fra->id, 'latitude' => null, 'longitude' => null]);
        $homonym = Venue::factory()->create(['name' => 'Stade Vélodrome', 'city' => 'Marseille', 'latitude' => null, 'longitude' => null]);

        (new VenueCoordinatesFile($this->path))->write([
            'k' => ['name' => 'Stade Vélodrome', 'city' => 'Marseille', 'country_code' => 'FRA',
                'latitude' => '43.2698', 'longitude' => '5.3959', 'precision' => 'stade', 'source' => 'nominatim'],
        ]);

        $this->artisan('xv:import-venue-coordinates', ['file' => $this->relativePath()])->assertSuccessful();

        $this->assertEqualsWithDelta(43.2698, (float) $venue->fresh()->latitude, 0.0001);
        $this->assertNull($homonym->fresh()->latitude);
    }

    public function test_import_dry_run_changes_nothing(): void
    {
        $venue = Venue::factory()->create(['latitude' => null, 'longitude' => null]);
        (new VenueCoordinatesFile($this->path))->write([
            'k' => ['name' => $venue->name, 'city' => $venue->city, 'country_code' => $venue->country->code,
                'latitude' => '1', 'longitude' => '2', 'precision' => 'ville', 'source' => 'nominatim'],
        ]);

        $this->artisan('xv:import-venue-coordinates', ['file' => $this->relativePath(), '--dry-run' => true])->assertSuccessful();

        $this->assertNull($venue->fresh()->latitude);
    }

    /** La commande résout le fichier depuis base_path() */
    private function relativePath(): string
    {
        return str_repeat('../', substr_count(base_path(), '/')) . ltrim($this->path, '/');
    }
}
