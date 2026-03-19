<?php

namespace App\Models;

use App\Enums\Continent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = [
        'name', 'code', 'continent', 'flag_emoji',
    ];

    protected $casts = [
        'continent' => Continent::class,
    ];

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
