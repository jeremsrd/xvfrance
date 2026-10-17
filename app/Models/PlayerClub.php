<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Passage d'un joueur dans un club : position dans sa carrière, nom du club à l'époque, source */
class PlayerClub extends Model
{
    protected $fillable = ['player_id', 'club_id', 'position', 'name', 'source'];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
}
