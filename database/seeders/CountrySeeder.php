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
            ['name' => 'Maroc', 'code' => 'MAR', 'continent' => Continent::AFRIQUE, 'flag_emoji' => '🇲🇦'],
            ['name' => 'Tunisie', 'code' => 'TUN', 'continent' => Continent::AFRIQUE, 'flag_emoji' => '🇹🇳'],
            ['name' => 'Kenya', 'code' => 'KEN', 'continent' => Continent::AFRIQUE, 'flag_emoji' => '🇰🇪'],
            ['name' => 'Rhodésie', 'code' => 'RHO', 'continent' => Continent::AFRIQUE, 'flag_emoji' => ''],

            // Asia
            ['name' => 'Japon', 'code' => 'JPN', 'continent' => Continent::ASIE, 'flag_emoji' => '🇯🇵'],
            ['name' => 'Kazakhstan', 'code' => 'KAZ', 'continent' => Continent::ASIE, 'flag_emoji' => '🇰🇿'],
            ['name' => 'Hong Kong', 'code' => 'HKG', 'continent' => Continent::ASIE, 'flag_emoji' => '🇭🇰'],

            // Europe supplémentaires
            ['name' => 'Pays-Bas', 'code' => 'NED', 'continent' => Continent::EUROPE, 'flag_emoji' => '🇳🇱'],
            ['name' => 'Belgique', 'code' => 'BEL', 'continent' => Continent::EUROPE, 'flag_emoji' => '🇧🇪'],
            ['name' => 'Suède', 'code' => 'SWE', 'continent' => Continent::EUROPE, 'flag_emoji' => '🇸🇪'],
            ['name' => 'Tchécoslovaquie', 'code' => 'TCH', 'continent' => Continent::EUROPE, 'flag_emoji' => ''],

            // Americas supplémentaires
            ['name' => 'Chili', 'code' => 'CHI', 'continent' => Continent::AMERIQUE_SUD, 'flag_emoji' => '🇨🇱'],
            ['name' => 'Paraguay', 'code' => 'PAR', 'continent' => Continent::AMERIQUE_SUD, 'flag_emoji' => '🇵🇾'],

            // Équipes spéciales
            ['name' => 'Lions Britanniques', 'code' => 'BIL', 'continent' => Continent::EUROPE, 'flag_emoji' => '🦁'],
            ['name' => 'Grande-Bretagne', 'code' => 'GBR', 'continent' => Continent::EUROPE, 'flag_emoji' => '🇬🇧'],
            ['name' => 'Pacific Islanders', 'code' => 'PAC', 'continent' => Continent::OCEANIE, 'flag_emoji' => '🏝️'],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['code' => $country['code']],
                $country,
            );
        }
    }
}
