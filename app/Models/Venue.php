<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'city', 'country_id', 'capacity', 'opened_year', 'latitude', 'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(RugbyMatch::class);
    }

    public function names(): HasMany
    {
        return $this->hasMany(VenueName::class);
    }

    /** Anciens noms, du plus ancien au plus récent */
    public function formerNames(): Collection
    {
        return $this->names->whereNotNull('used_until')->sortBy('used_until')->values();
    }

    /** Nom du stade à une date : l'ancien nom alors en usage, sinon le nom actuel */
    public function nameOn(\DateTimeInterface $date): string
    {
        return $this->formerNames()->first(fn (VenueName $n) => $date < $n->used_until)?->name ?? $this->name;
    }

    /**
     * Stade portant ce nom (actuel, ancien ou variante), sans tenir compte des accents,
     * de la casse ni des tirets : « Stade Velodrome » = « Stade Vélodrome ». Limité à un pays si fourni.
     */
    public static function findByAnyName(string $name, ?int $countryId = null): ?self
    {
        $wanted = self::normalizeName($name);

        return self::with('names')->when($countryId, fn ($q) => $q->where('country_id', $countryId))->get()->first(
            fn (self $venue) => self::normalizeName($venue->name) === $wanted
                || $venue->names->contains(fn (VenueName $n) => self::normalizeName($n->name) === $wanted)
        );
    }

    public static function normalizeName(string $name): string
    {
        $name = mb_strtolower(Str::ascii($name));

        return trim(preg_replace('/[\s\-\'’]+/u', ' ', $name));
    }
}
