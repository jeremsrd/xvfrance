<?php

namespace App\Models;

use App\Enums\Continent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'continent', 'flag_emoji',
    ];

    protected $casts = [
        'continent' => Continent::class,
    ];

    /** Article propre aux noms de pays qui ne suivent pas la règle (pluriels, masculins) */
    private const ARTICLES = [
        'WAL' => 'le ', 'JPN' => 'le ', 'CAN' => 'le ', 'PAK' => 'le ', 'PRT' => 'le ', 'POR' => 'le ', 'CHL' => 'le ', 'CHI' => 'le ',
        'ZIM' => 'le ', 'MAR' => 'le ', 'BRA' => 'le ', 'KEN' => 'le ', 'PAR' => 'le ', 'URU' => 'l\'', 'HKG' => '', 'MON' => '',
        'FIJ' => 'les ', 'BIL' => 'les ', 'NZM' => 'les ', 'PAC' => 'les ', 'SAM' => 'les ', 'TGA' => 'les ', 'USA' => 'les ', 'NED' => 'les ', 'PHI' => 'les ',
    ];

    /**
     * Nom précédé de son article : « l'Angleterre », « le pays de Galles », « les Fidji ».
     */
    public function withArticle(?string $preposition = null): string
    {
        $phrase = $this->articleAndName();

        // Contractions : à le → au, à les → aux, de le → du, de les → des
        return match (true) {
            $preposition === null => $phrase,
            str_starts_with($phrase, 'le ') => ($preposition === 'à' ? 'au ' : 'du ') . substr($phrase, 3),
            str_starts_with($phrase, 'les ') => ($preposition === 'à' ? 'aux ' : 'des ') . substr($phrase, 4),
            default => $preposition . ' ' . $phrase,
        };
    }

    private function articleAndName(): string
    {
        $name = $this->name;
        if (array_key_exists($this->code, self::ARTICLES)) {
            $article = self::ARTICLES[$this->code];
        } elseif (preg_match('/^[AEIOUYÉÈÊÂÎÔ]/iu', $name)) {
            $article = 'l\'';
        } else {
            $article = 'la ';
        }

        // « Pays de Galles » s'écrit avec une minuscule après l'article
        if ($this->code === 'WAL') {
            $name = mb_strtolower(mb_substr($name, 0, 1)) . mb_substr($name, 1);
        }

        return $article . $name;
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function coaches(): HasMany
    {
        return $this->hasMany(Coach::class);
    }

    public function matchesAsOpponent(): HasMany
    {
        return $this->hasMany(RugbyMatch::class, 'opponent_id');
    }

    public function matchesAsRefereeCountry(): HasMany
    {
        return $this->hasMany(RugbyMatch::class, 'referee_country_id');
    }
}
