<?php

namespace App\Models;

use App\Enums\TeamSide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchSubstitution extends Model
{
    protected $fillable = [
        'match_id', 'player_off_id', 'player_on_id', 'minute', 'is_tactical', 'team_side',
    ];

    protected $casts = [
        'is_tactical' => 'boolean',
        'team_side' => TeamSide::class,
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(RugbyMatch::class, 'match_id');
    }

    public function playerOff(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_off_id');
    }

    public function playerOn(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_on_id');
    }

    public function scopeFrance(Builder $query): Builder
    {
        return $query->where('team_side', TeamSide::FRANCE);
    }

    public function scopeAdversaire(Builder $query): Builder
    {
        return $query->where('team_side', TeamSide::ADVERSAIRE);
    }
}
