<?php

namespace Database\Seeders;

use App\Enums\Continent;
use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            // France
            ['name' => 'France', 'code' => 'FRA', 'continent' => Continent::EUROPE, 'flag_emoji' => '🇫🇷'],

            // Six Nations
            ['name' => 'Angleterre', 'code' => 'ENG', 'continent' => Continent::EUROPE, 'flag_emoji' => '🏴󠁧󠁢󠁥󠁮󠁧󠁿'],
            ['name' => 'Écosse', 'code' => 'SCO', 'continent' => Continent::EUROPE, 'flag_emoji' => '🏴󠁧󠁢󠁳󠁣󠁴󠁿'],
            ['name' => 'Pays de Galles', 'code' => 'WAL', 'continent' => Continent::EUROPE, 'flag_emoji' => '🏴󠁧󠁢󠁷󠁬󠁳󠁿'],
            ['name' => 'Irlande', 'code' => 'IRL', 'continent' => Continent::EUROPE, 'flag_emoji' => '🇮🇪'],
            ['name' => 'Italie', 'code' => 'ITA', 'continent' => Continent::EUROPE, 'flag_emoji' => '🇮🇹'],

            // Southern Hemisphere
            ['name' => 'Nouvelle-Zélande', 'code' => 'NZL', 'continent' => Continent::OCEANIE, 'flag_emoji' => '🇳🇿'],
            ['name' => 'Australie', 'code' => 'AUS', 'continent' => Continent::OCEANIE, 'flag_emoji' => '🇦🇺'],
            ['name' => 'Afrique du Sud', 'code' => 'RSA', 'continent' => Continent::AFRIQUE, 'flag_emoji' => '🇿🇦'],
            ['name' => 'Argentine', 'code' => 'ARG', 'continent' => Continent::AMERIQUE_SUD, 'flag_emoji' => '🇦🇷'],

            // Pacific Islands
            ['name' => 'Fidji', 'code' => 'FIJ', 'continent' => Continent::OCEANIE, 'flag_emoji' => '🇫🇯'],
            ['name' => 'Samoa', 'code' => 'SAM', 'continent' => Continent::OCEANIE, 'flag_emoji' => '🇼🇸'],
            ['name' => 'Tonga', 'code' => 'TGA', 'continent' => Continent::OCEANIE, 'flag_emoji' => '🇹🇴'],

            // Europe
            ['name' => 'Géorgie', 'code' => 'GEO', 'continent' => Continent::EUROPE, 'flag_emoji' => '🇬🇪'],
            ['name' => 'Roumanie', 'code' => 'ROU', 'continent' => Continent::EUROPE, 'flag_emoji' => '🇷🇴'],
            ['name' => 'Russie', 'code' => 'RUS', 'continent' => Continent::EUROPE, 'flag_emoji' => '🇷🇺'],
            ['name' => 'Espagne', 'code' => 'ESP', 'continent' => Continent::EUROPE, 'flag_emoji' => '🇪🇸'],
            ['name' => 'Portugal', 'code' => 'POR', 'continent' => Continent::EUROPE, 'flag_emoji' => '🇵🇹'],
            ['name' => 'Allemagne', 'code' => 'GER', 'continent' => Continent::EUROPE, 'flag_emoji' => '🇩🇪'],

            // Americas
            ['name' => 'États-Unis', 'code' => 'USA', 'continent' => Continent::AMERIQUE_NORD, 'flag_emoji' => '🇺🇸'],
            ['name' => 'Canada', 'code' => 'CAN', 'continent' => Continent::AMERIQUE_NORD, 'flag_emoji' => '🇨🇦'],
            ['name' => 'Uruguay', 'code' => 'URU', 'continent' => Continent::AMERIQUE_SUD, 'flag_emoji' => '🇺🇾'],

            // Africa
            ['name' => 'Namibie', 'code' => 'NAM', 'continent' => Continent::AFRIQUE, 'flag_emoji' => '🇳🇦'],
            ['name' => 'Zimbabwe', 'code' => 'ZIM', 'continent' => Continent::AFRIQUE, 'flag_emoji' => '🇿🇼'],
            ['name' => 'Côte d\'Ivoire', 'code' => 'CIV', 'continent' => Continent::AFRIQUE, 'flag_emoji' => '🇨🇮'],

            // Asia
            ['name' => 'Japon', 'code' => 'JPN', 'continent' => Continent::ASIE, 'flag_emoji' => '🇯🇵'],

            // British & Irish Lions (équipe spéciale)
            ['name' => 'Lions Britanniques et Irlandais', 'code' => 'BIL', 'continent' => Continent::EUROPE, 'flag_emoji' => '🦁'],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['code' => $country['code']],
                $country,
            );
        }
    }
}
