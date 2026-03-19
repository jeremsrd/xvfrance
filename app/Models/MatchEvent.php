<?php

namespace App\Models;

use App\Enums\EventType;
use App\Enums\TeamSide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchEvent extends Model
{
    protected $fillable = [
        'match_id', 'player_id', 'event_type', 'minute', 'team_side', 'detail',
    ];

    protected $casts = [
        'event_type' => EventType::class,
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

    public function scopeEssais(Builder $query): Builder
    {
        return $query->whereIn('event_type', [EventType::ESSAI, EventType::ESSAI_PENALITE]);
    }

    public function scopeCartons(Builder $query): Builder
    {
        return $query->whereIn('event_type', [EventType::CARTON_JAUNE, EventType::CARTON_ROUGE]);
    }
}
