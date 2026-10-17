<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\MatchLineup;
use App\Models\RugbyMatch;
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
        $this->foreignClubCountries();
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

    public function test_clubs_come_from_wikipedia_for_players_missing_from_the_list(): void
    {
        $france = Country::factory()->france()->create();
        $this->foreignClubCountries();
        // Gabin Villière, n° 11 le 28/11/2020 : « Rouen Normandie rugby ; Rugby club toulonnais » sur Wikipédia
        $villiere = Player::factory()->create(['country_id' => $france->id]);
        $match = RugbyMatch::factory()->create(['match_date' => '2020-11-28']);
        MatchLineup::factory()->create(['match_id' => $match->id, 'player_id' => $villiere->id, 'jersey_number' => 11]);

        $this->artisan('xv:import-clubs')->assertSuccessful();

        $this->assertSame(['rouen-normandie', 'rc-toulon'], $villiere->clubMemberships()->with('club')->get()->pluck('club.slug')->all());
        $this->assertSame(['Wikipédia'], $villiere->clubMemberships()->distinct()->pluck('source')->all());
    }

    /** Pays des clubs étrangers de clubs.csv */
    private function foreignClubCountries(): void
    {
        foreach (['ENG', 'RSA', 'AUS', 'ARG', 'FIJ', 'IRL'] as $code) {
            Country::factory()->create(['name' => $code, 'code' => $code]);
        }
    }
}
