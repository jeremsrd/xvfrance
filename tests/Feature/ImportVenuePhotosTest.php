<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\RugbyMatch;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportVenuePhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_venue_photo_is_imported_with_its_credit_and_shown(): void
    {
        $france = Country::factory()->france()->create();
        $parc = Venue::factory()->create(['name' => 'Parc des Princes', 'slug' => 'parc-des-princes', 'city' => 'Paris', 'country_id' => $france->id]);
        $other = Venue::factory()->create(['slug' => 'stade-sans-photo', 'photo_path' => '/images/stades/ancienne.webp']);
        RugbyMatch::factory()->create(['venue_id' => $parc->id]);

        // Image versionnée de public/images/stades ; la deuxième ligne vise un stade absent de la base
        $csv = tempnam(sys_get_temp_dir(), 'stades') . '.csv';
        file_put_contents($csv, "venue_slug,venue_name,wikidata,commons_file,author,license,license_url,page\n"
            . "parc-des-princes,Parc des Princes,Q1,a.jpg,Jean Dupont,Public domain,,https://commons.wikimedia.org/wiki/File:A.jpg\n"
            . "inconnu,Inconnu,Q2,b.jpg,X,CC BY-SA 4.0,https://creativecommons.org/licenses/by-sa/4.0,https://commons.wikimedia.org/wiki/File:B.jpg\n");

        $this->artisan('xv:import-venue-photos', ['file' => $csv])->assertSuccessful();
        @unlink($csv);

        $parc->refresh();
        $this->assertSame('/images/stades/parc-des-princes.webp', $parc->photo_path);
        $this->assertSame('/images/stades/parc-des-princes-vignette.webp', $parc->photoThumbPath());
        $this->assertSame('Domaine public', $parc->photo_license);
        $this->assertGreaterThan(0, $parc->photo_width);
        $this->assertNull($other->fresh()->photo_path);  // photos reconstruites à chaque import

        $this->get(route('venues.show', $parc))
            ->assertSee('src="/images/stades/parc-des-princes.webp"', false)
            ->assertSee('Photo : Jean Dupont')
            ->assertSee('https://commons.wikimedia.org/wiki/File:A.jpg', false);
        $this->get(route('venues.index'))
            ->assertSee('src="/images/stades/parc-des-princes-vignette.webp"', false);
    }
}
