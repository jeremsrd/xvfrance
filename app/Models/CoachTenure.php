<?php

namespace App\Models;

use App\Enums\CoachRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachTenure extends Model
{
    protected $fillable = [
        'coach_id', 'role', 'start_date', 'end_date',
    ];

    protected $casts = [
        'role' => CoachRole::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }
}
