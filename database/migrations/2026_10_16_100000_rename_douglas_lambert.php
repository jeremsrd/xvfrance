<?php

use App\Models\Country;
use App\Models\Player;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * L'ailier anglais de 1907-1911 (5 essais face à la France en 1907) s'appelle Douglas Lambert, pas « Daniel »
     * (Wikipédia, liste des internationaux anglais). Renommé avant l'import des feuilles, qui portent le bon prénom :
     * sinon l'import créerait un doublon.
     */
    public function up(): void
    {
        $england = Country::where('code', 'ENG')->value('id');
        $player = Player::where('country_id', $england)->where('first_name', 'Daniel')->where('last_name', 'Lambert')->first();
        if (!$player) {
            return;
        }

        $player->first_name = 'Douglas';
        $player->slug = $player->generateSlug();
        $player->save();
    }

    public function down(): void
    {
        // Correction de données : pas de retour arrière
    }
};
