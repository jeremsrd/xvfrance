<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Club d'un international, au sens de Wikipédia : un club renommé reste le même (Racing Club de France → Racing 92),
 * une fusion qui crée un club en fait un nouveau (USA Perpignan, Union Bordeaux Bègles).
 */
class Club extends Model
{
    protected $fillable = ['name', 'slug', 'city', 'country_id'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(PlayerClub::class);
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_clubs')->withPivot('name', 'position');
    }

    /** Noms portés par le club dans les sources, autres que son nom usuel (« Racing Club de France ») */
    public function formerNames(): array
    {
        return $this->memberships->pluck('name')->unique()->reject(fn ($name) => $name === $this->name)->values()->all();
    }
}
