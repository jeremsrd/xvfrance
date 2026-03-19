<?php

namespace App\Models;

use App\Enums\CompetitionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competition extends Model
{
    protected $fillable = [
        'name', 'short_name', 'type',
    ];

    protected $casts = [
        'type' => CompetitionType::class,
    ];

    public function editions(): HasMany
    {
        return $this->hasMany(CompetitionEdition::class);
    }
}
