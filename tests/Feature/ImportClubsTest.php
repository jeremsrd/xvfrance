<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Country;
use App\Models\Player;
use App\Models\PlayerClub;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportClubsTest extends TestCase
{
    use RefreshDatabase;

    public function test_clubs_of_internationals_are_imported_from_the_versioned_files(): void
    {
        $france = Country::factory()->france()->create();
        Country::factory()->create(['name' => 'Angleterre', 'code' => 'ENG']);
        Country::factory()->create(['name' => 'Afrique du Sud', 'code' => 'RSA']);
        // N° 12 Marcel Communeau : « Stade Français, Vélo Club Beauvaisien » ; n° 947 Pascal Papé : trois clubs
        $communeau = Player::factory()->create(['country_id' => $france->id, 'cap_number' => 12]);
        $pape = Player::factory()->create(['country_id' => $france->id, 'cap_number' => 947]);

        $this->artisan('xv:import-clubs')->doesntExpectOutputToContain('sans correspondance')->assertSuccessful();
        $this->artisan('xv:import-clubs')->assertSuccessful();

        $this->assertSame(['Stade français', 'Vélo club beauvaisien'], $communeau->clubMemberships()->pluck('name')->all());
        $this->assertSame(['cs-bourgoin', 'castres-olympique', 'stade-francais'], $pape->clubMemberships()->with('club')->get()->pluck('club.slug')->all());
        $this->assertSame(5, PlayerClub::count());

        // Le Stade français porte son nom actuel, l'ancien nom reste celui de l'époque de Communeau
        $stadeFrancais = Club::where('slug', 'stade-francais')->first();
        $this->assertSame('Stade français Paris', $stadeFrancais->name);
        $this->get(route('clubs.show', $stadeFrancais))->assertOk()->assertSee('Stade français Paris')->assertSee($communeau->last_name);
    }
}
