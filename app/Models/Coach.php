<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coach extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'birth_date', 'birth_city', 'country_id', 'photo_url',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function tenures(): HasMany
    {
        return $this->hasMany(CoachTenure::class);
    }
}
