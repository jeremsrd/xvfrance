<?php

namespace App\Models;

use App\Enums\PlayerPosition;
use App\Enums\TeamSide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchLineup extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id', 'player_id', 'jersey_number', 'is_starter',
        'position_played', 'is_captain', 'played', 'team_side',
    ];

    protected $casts = [
        'is_starter' => 'boolean',
        'is_captain' => 'boolean',
        'played' => 'boolean',
        'position_played' => PlayerPosition::class,
        'team_side' => TeamSide::class,
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(RugbyMatch::class, 'match_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function scopeFrance(Builder $query): Builder
    {
        return $query->where('team_side', TeamSide::FRANCE);
    }

    public function scopeAdversaire(Builder $query): Builder
    {
        return $query->where('team_side', TeamSide::ADVERSAIRE);
    }

    public function scopeStarters(Builder $query): Builder
    {
        return $query->where('is_starter', true);
    }

    public function scopeSubstitutes(Builder $query): Builder
    {
        return $query->where('is_starter', false);
    }

    /** Joueurs entrés en jeu : une sélection (les remplaçants restés sur le banc n'en sont pas). */
    public function scopePlayed(Builder $query): Builder
    {
        return $query->where('played', true);
    }
}
