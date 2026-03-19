<?php

namespace App\Models;

use App\Enums\PlayerPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'nickname', 'birth_date', 'death_date',
        'birth_city', 'birth_country_id', 'country_id', 'height_cm', 'weight_kg',
        'primary_position', 'photo_path', 'is_active', 'cap_number',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
        'primary_position' => PlayerPosition::class,
        'is_active' => 'boolean',
    ];

    public function fullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function isDeceased(): bool
    {
        return $this->death_date !== null;
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function birthCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'birth_country_id');
    }

    public function lineups(): HasMany
    {
        return $this->hasMany(MatchLineup::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class);
    }

    public function substitutionsOff(): HasMany
    {
        return $this->hasMany(MatchSubstitution::class, 'player_off_id');
    }

    public function substitutionsOn(): HasMany
    {
        return $this->hasMany(MatchSubstitution::class, 'player_on_id');
    }

    public function scopeFrench(Builder $query): Builder
    {
        return $query->whereHas('country', fn (Builder $q) => $q->where('code', 'FRA'));
    }

    public function scopeByCountry(Builder $query, int $countryId): Builder
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
