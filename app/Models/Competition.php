<?php

namespace App\Models;

use App\Enums\CompetitionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Competition extends Model
{
    use HasFactory;

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

    public function matches(): HasManyThrough
    {
        return $this->hasManyThrough(RugbyMatch::class, CompetitionEdition::class, 'competition_id', 'edition_id');
    }
}
