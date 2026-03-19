<?php

namespace App\Models;

use App\Enums\MatchStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RugbyMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'match_date', 'kickoff_time', 'venue_id', 'opponent_id', 'edition_id',
        'france_score', 'opponent_score', 'is_home', 'is_neutral', 'stage',
        'match_number', 'attendance', 'referee', 'referee_country_id',
        'weather', 'notes',
    ];

    protected $casts = [
        'match_date' => 'date',
        'is_home' => 'boolean',
        'is_neutral' => 'boolean',
        'stage' => MatchStage::class,
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function opponent(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'opponent_id');
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(CompetitionEdition::class, 'edition_id');
    }

    public function refereeCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'referee_country_id');
    }

    public function lineups(): HasMany
    {
        return $this->hasMany(MatchLineup::class, 'match_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class, 'match_id');
    }

    public function substitutions(): HasMany
    {
        return $this->hasMany(MatchSubstitution::class, 'match_id');
    }

    public function getResultAttribute(): string
    {
        if ($this->france_score > $this->opponent_score) return 'Victoire';
        if ($this->france_score < $this->opponent_score) return 'Défaite';
        return 'Nul';
    }

    public function getPointDiffAttribute(): int
    {
        return $this->france_score - $this->opponent_score;
    }

    public function getIsVictoryAttribute(): bool
    {
        return $this->france_score > $this->opponent_score;
    }

    public function getIsDefeatAttribute(): bool
    {
        return $this->france_score < $this->opponent_score;
    }
}
