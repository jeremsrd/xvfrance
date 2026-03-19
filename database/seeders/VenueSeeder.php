<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $france = Country::where('code', 'FRA')->first();
        $england = Country::where('code', 'ENG')->first();
        $scotland = Country::where('code', 'SCO')->first();
        $wales = Country::where('code', 'WAL')->first();
        $ireland = Country::where('code', 'IRL')->first();
        $italy = Country::where('code', 'ITA')->first();

        $venues = [
            // France
            [
                'name' => 'Stade de France',
                'city' => 'Saint-Denis',
                'country_id' => $france->id,
                'capacity' => 80698,
                'opened_year' => 1998,
                'latitude' => 48.9244592,
                'longitude' => 2.3602144,
            ],
            [
                'name' => 'Parc des Princes',
                'city' => 'Paris',
                'country_id' => $france->id,
                'capacity' => 47929,
                'opened_year' => 1972,
                'latitude' => 48.8414356,
                'longitude' => 2.2530099,
            ],
            [
                'name' => 'Stade Olympique Yves-du-Manoir',
                'city' => 'Colombes',
                'country_id' => $france->id,
                'capacity' => 14000,
                'opened_year' => 1907,
                'latitude' => 48.9280556,
                'longitude' => 2.2505556,
            ],

            // England
            [
                'name' => 'Twickenham Stadium',
                'city' => 'Londres',
                'country_id' => $england->id,
                'capacity' => 82000,
                'opened_year' => 1907,
                'latitude' => 51.4559,
                'longitude' => -0.3415,
            ],

            // Scotland
            [
                'name' => 'Murrayfield Stadium',
                'city' => 'Édimbourg',
                'country_id' => $scotland->id,
                'capacity' => 67144,
                'opened_year' => 1925,
                'latitude' => 55.9422,
                'longitude' => -3.2406,
            ],

            // Wales
            [
                'name' => 'Principality Stadium',
                'city' => 'Cardiff',
                'country_id' => $wales->id,
                'capacity' => 73931,
                'opened_year' => 1999,
                'latitude' => 51.4783,
                'longitude' => -3.1828,
            ],

            // Ireland
            [
                'name' => 'Aviva Stadium',
                'city' => 'Dublin',
                'country_id' => $ireland->id,
                'capacity' => 51700,
                'opened_year' => 2010,
                'latitude' => 53.3352,
                'longitude' => -6.2286,
            ],

            // Italy
            [
                'name' => 'Stadio Olimpico',
                'city' => 'Rome',
                'country_id' => $italy->id,
                'capacity' => 72698,
                'opened_year' => 1953,
                'latitude' => 41.9341,
                'longitude' => 12.4547,
            ],
        ];

        foreach ($venues as $venue) {
            Venue::updateOrCreate(
                ['name' => $venue['name']],
                $venue,
            );
        }
    }
}
