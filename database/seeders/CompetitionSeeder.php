<?php

namespace Database\Seeders;

use App\Enums\CompetitionType;
use App\Models\Competition;
use Illuminate\Database\Seeder;

class CompetitionSeeder extends Seeder
{
    public function run(): void
    {
        $competitions = [
            ['name' => 'Tournoi des 5/6 Nations', 'short_name' => '5/6 Nations', 'type' => CompetitionType::TOURNOI],
            ['name' => 'Coupe du Monde de Rugby', 'short_name' => 'Coupe du Monde', 'type' => CompetitionType::COUPE_DU_MONDE],
            ['name' => 'Tests d\'automne', 'short_name' => 'Tests d\'automne', 'type' => CompetitionType::TEST_MATCH],
            ['name' => 'Tournée d\'été', 'short_name' => 'Tournée d\'été', 'type' => CompetitionType::TEST_MATCH],
        ];

        foreach ($competitions as $competition) {
            Competition::updateOrCreate(
                ['short_name' => $competition['short_name']],
                $competition,
            );
        }
    }
}
