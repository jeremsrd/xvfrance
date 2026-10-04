<?php

namespace App\Models;

use App\Enums\MatchStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RugbyMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'match_date', 'kickoff_time', 'venue_id', 'opponent_id', 'edition_id',
        'france_score', 'opponent_score', 'is_home', 'is_neutral', 'stage',
        'match_number', 'attendance', 'referee', 'referee_country_id',
        'weather', 'video_url', 'video_embed_url', 'notes', 'slug',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (RugbyMatch $match) {
            if (empty($match->slug)) {
                $match->slug = $match->generateSlug();
            }
        });
    }

    public function generateSlug(): string
    {
        $date = $this->match_date->format('Y-m-d');
        $opponent = \Illuminate\Support\Str::slug($this->opponent->name);
        return "{$date}-{$opponent}";
    }

    protected $casts = [
        'match_date' => 'date',
        'is_home' => 'boolean',
        'is_neutral' => 'boolean',
        'stage' => MatchStage::class,
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function opponent(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'opponent_id');
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(CompetitionEdition::class, 'edition_id');
    }

    public function refereeCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'referee_country_id');
    }

    public function lineups(): HasMany
    {
        return $this->hasMany(MatchLineup::class, 'match_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class, 'match_id');
    }

    public function substitutions(): HasMany
    {
        return $this->hasMany(MatchSubstitution::class, 'match_id');
    }

    public function scopeWins(Builder $query): Builder
    {
        return $query->whereColumn('france_score', '>', 'opponent_score');
    }

    public function scopeLosses(Builder $query): Builder
    {
        return $query->whereColumn('france_score', '<', 'opponent_score');
    }

    public function scopeDraws(Builder $query): Builder
    {
        return $query->whereColumn('france_score', '=', 'opponent_score');
    }

    public function getResultAttribute(): string
    {
        if ($this->france_score > $this->opponent_score) return 'Victoire';
        if ($this->france_score < $this->opponent_score) return 'Défaite';
        return 'Nul';
    }

    public function getPointDiffAttribute(): int
    {
        return $this->france_score - $this->opponent_score;
    }

    public function getIsVictoryAttribute(): bool
    {
        return $this->france_score > $this->opponent_score;
    }

    public function getIsDefeatAttribute(): bool
    {
        return $this->france_score < $this->opponent_score;
    }

    // --- Affichage domicile/extérieur ---

    /**
     * Identifiant YouTube d'une URL (watch?v=, youtu.be/, embed/, shorts/, live/), ou null.
     */
    public static function youtubeId(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $pattern = '~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})(?:[?&#].*)?$~';

        return preg_match($pattern, $url, $m) ? $m[1] : null;
    }

    public function getVideoIdAttribute(): ?string
    {
        return self::youtubeId($this->video_url);
    }

    /**
     * Adresse du lecteur à intégrer dans la page : déduite pour YouTube, saisie pour les autres diffuseurs.
     */
    public function getVideoPlayerUrlAttribute(): ?string
    {
        if ($this->video_id) {
            return "https://www.youtube-nocookie.com/embed/{$this->video_id}?autoplay=1&rel=0";
        }

        return $this->video_embed_url;
    }

    /**
     * Nom du site qui héberge le résumé vidéo (TF1+, France TV…), affiché quand la vidéo
     * ne peut pas être lue dans la page.
     */
    public function getVideoSourceAttribute(): ?string
    {
        $host = $this->video_url ? parse_url($this->video_url, PHP_URL_HOST) : null;
        if (!$host) {
            return null;
        }
        $host = preg_replace('/^(www|m)\./', '', strtolower($host));

        $names = [
            'youtube.com' => 'YouTube', 'youtu.be' => 'YouTube',
            'tf1.fr' => 'TF1+', 'tf1info.fr' => 'TF1 Info',
            'france.tv' => 'France TV', 'francetvinfo.fr' => 'France TV',
            'dailymotion.com' => 'Dailymotion', 'lequipe.fr' => "L'Équipe",
            'canalplus.com' => 'Canal+', 'ffr.fr' => 'FFR',
        ];

        return $names[$host] ?? $host;
    }

    public function getHomeScoreAttribute(): int
    {
        return $this->is_home ? $this->france_score : $this->opponent_score;
    }

    public function getAwayScoreAttribute(): int
    {
        return $this->is_home ? $this->opponent_score : $this->france_score;
    }

    public function getHomeTeamNameAttribute(): string
    {
        return $this->is_home ? 'France' : $this->opponent->name;
    }

    public function getAwayTeamNameAttribute(): string
    {
        return $this->is_home ? $this->opponent->name : 'France';
    }

    public function getHomeTeamFlagAttribute(): string
    {
        return $this->is_home ? '🇫🇷' : $this->opponent->flag_emoji;
    }

    public function getAwayTeamFlagAttribute(): string
    {
        return $this->is_home ? $this->opponent->flag_emoji : '🇫🇷';
    }
}
