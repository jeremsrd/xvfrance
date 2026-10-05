<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Autre nom d'un stade. Avec used_until : ancien nom, affiché pour les matches joués avant cette date.
 * Sans : variante (nom de sponsor, autre graphie), seulement reconnue à l'import.
 */
class VenueName extends Model
{
    protected $fillable = ['venue_id', 'name', 'used_until'];

    protected $casts = [
        'used_until' => 'date',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
