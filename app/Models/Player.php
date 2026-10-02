<?php

namespace App\Models;

use App\Enums\PlayerPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'nickname', 'birth_date', 'death_date', 'death_city',
        'birth_city', 'birth_country_id', 'country_id', 'height_cm', 'weight_kg',
        'primary_position', 'photo_path', 'is_active', 'cap_number', 'slug',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        // Le slug utilise l'id en suffixe : il ne peut être calculé qu'après l'insertion
        static::created(function (Player $player) {
            if (empty($player->slug)) {
                $player->slug = $player->generateSlug();
                $player->saveQuietly();
            }
        });
    }

    public function generateSlug(): string
    {
        $base = \Illuminate\Support\Str::slug($this->first_name . ' ' . $this->last_name);
        $suffix = $this->cap_number ?? $this->id;
        return "{$base}-{$suffix}";
    }

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

    /**
     * « Né le 1er mars 1980 à Apia (Samoa) », ou null si rien n'est connu.
     */
    public function birthSummary(): ?string
    {
        if (!$this->birth_date && !$this->birth_city) {
            return null;
        }

        $place = $this->birth_city;
        if ($place && $this->birthCountry && $this->birthCountry->id !== $this->country_id) {
            $place .= ' (' . $this->birthCountry->name . ')';
        }

        return 'Né' . ($this->birth_date ? ' le ' . \App\Support\FrenchDate::long($this->birth_date) : '') . ($place ? ' à ' . $place : '');
    }

    /**
     * « Mort le 12 juin 1950 à Paris », ou null pour un joueur vivant.
     */
    public function deathSummary(): ?string
    {
        if (!$this->death_date && !$this->death_city) {
            return null;
        }

        return 'Mort' . ($this->death_date ? ' le ' . \App\Support\FrenchDate::long($this->death_date) : '') . ($this->death_city ? ' à ' . $this->death_city : '');
    }

    /**
     * Âge actuel, ou âge au décès.
     */
    public function age(): ?int
    {
        return $this->birth_date ? (int) $this->birth_date->diffInYears($this->death_date ?? now()) : null;
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
