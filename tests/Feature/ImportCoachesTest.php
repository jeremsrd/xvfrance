<?php

namespace Tests\Feature;

use App\Models\Coach;
use App\Models\Country;
use App\Models\RugbyMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportCoachesTest extends TestCase
{
    use RefreshDatabase;

    public function test_coaches_and_tenures_are_imported_once(): void
    {
        Country::factory()->france()->create();
        RugbyMatch::factory()->create(['match_date' => '1990-05-24']);
        RugbyMatch::factory()->create(['match_date' => '1990-06-24']);

        $csv = tempnam(sys_get_temp_dir(), 'coaches') . '.csv';
        file_put_contents($csv, "first_name,last_name,birth_date,birth_city,start_date,end_date,note\n"
            . "Jacques,Fouroux,1947-07-24,Auch,1981-01-01,1990-05-31,\n"
            . "Daniel,Dubroca,1954-04-25,Aiguillon,1990-06-01,,\n");

        $this->artisan('xv:import-coaches', ['file' => $csv])->assertSuccessful();
        $this->artisan('xv:import-coaches', ['file' => $csv])->assertSuccessful();
        @unlink($csv);

        $this->assertSame(2, Coach::count());
        $dubroca = Coach::where('last_name', 'Dubroca')->first();
        $this->assertSame('Aiguillon', $dubroca->birth_city);
        $this->assertCount(1, $dubroca->tenures);
        $this->assertNull($dubroca->tenures->first()->end_date);
        $this->get(route('coaches.show', $dubroca))->assertOk()->assertSee('Dubroca');
    }
}
