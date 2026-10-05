<?php

use App\Models\Country;
use App\Models\Player;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * xv:seed-1906 avait rattaché William Crichton à l'Angleterre et Allan Muhr aux États-Unis (pays de naissance) :
     * l'import de France–Angleterre 1906 a créé deux doublons français (« William Crichton », « Alan Muhr »).
     * Les joueurs du seed passent en France, avec leur pays de naissance, et absorbent les doublons.
     */
    public function up(): void
    {
        $france = Country::where('code', 'FRA')->first();
        if (!$france) {
            return;
        }

        foreach ([['William', 'Crichton', 'ENG', ['William']], ['Allan', 'Muhr', 'USA', ['Allan', 'Alan']]] as [$first, $last, $born, $variants]) {
            $bornId = Country::where('code', $born)->value('id');
            $seeded = Player::where('first_name', $first)->where('last_name', $last)->where('country_id', $bornId)->first();
            if (!$seeded) {
                continue;
            }
            Player::where('country_id', $france->id)->where('last_name', $last)->whereIn('first_name', $variants)
                ->get()->each(fn (Player $duplicate) => $duplicate->mergeInto($seeded));
            $seeded->update(['country_id' => $france->id, 'birth_country_id' => $bornId]);
        }
    }

    public function down(): void
    {
        // Correction de données : pas de retour arrière
    }
};
