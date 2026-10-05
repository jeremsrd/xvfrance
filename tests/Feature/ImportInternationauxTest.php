<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\MatchLineup;
use App\Models\Player;
use App\Models\RugbyMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportInternationauxTest extends TestCase
{
    use RefreshDatabase;

    private string $path;
    private Country $france;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = tempnam(sys_get_temp_dir(), 'internationaux') . '.csv';
        $this->france = Country::factory()->create(['name' => 'France', 'code' => 'FRA']);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        parent::tearDown();
    }

    private function playerIn(string $first, string $last, string $date, ?int $cap = null): Player
    {
        $player = Player::factory()->create(['first_name' => $first, 'last_name' => $last, 'country_id' => $this->france->id, 'cap_number' => $cap, 'birth_date' => null]);
        MatchLineup::factory()->create(['player_id' => $player->id, 'match_id' => RugbyMatch::factory()->create(['match_date' => $date])->id]);

        return $player;
    }

    private function runImport(array $rows): void
    {
        $lines = ["cap_number,last_name,first_name,birth_date,positions,caps,years,clubs"];
        foreach ($rows as $row) {
            $lines[] = implode(',', $row);
        }
        file_put_contents($this->path, implode("\n", $lines) . "\n");

        $this->artisan('xv:import-internationaux', ['file' => $this->path, '--mapping' => 'absent.csv'])->assertSuccessful();
    }

    public function test_cap_number_and_birth_date_by_name_and_years(): void
    {
        $crichton = $this->playerIn('William', 'Crichton', '1906-01-01', cap: 10);
        $janik = $this->playerIn('Kail', 'Janik', '1987-11-11');
        $oldFaure = $this->playerIn('Paul', 'Faure', '1914-01-01');
        $modern = $this->playerIn('Lionel', 'Faure', '2008-06-28');

        $this->runImport([
            [4, 'CRICHTON', 'William', '1881-12-15', 'Arrière', 2, '1906', 'Le Havre'],
            [757, 'JANIK', 'Karl', '1960-01-12', 'Troisième ligne', 1, '1987', 'Toulouse'],
            [106, 'FAURE', 'Paul', '', 'Avant', 3, '1914', ''],
        ]);

        $this->assertSame(4, $crichton->fresh()->cap_number);
        $this->assertSame('1881-12-15', $crichton->fresh()->birth_date->format('Y-m-d'));
        $this->assertSame(757, $janik->fresh()->cap_number);
        $this->assertSame(106, $oldFaure->fresh()->cap_number);
        $this->assertNull($modern->fresh()->cap_number);
    }

    public function test_namesake_from_another_era_is_not_matched(): void
    {
        $david = $this->playerIn('Yann', 'David', '2008-11-08');

        $this->runImport([[893, 'DAVID', 'Gérard', '1977-11-26', 'Deuxième ligne', 1, '1999', 'Bègles']]);

        $this->assertNull($david->fresh()->cap_number);
        $this->assertNull($david->fresh()->birth_date);
    }
}
