<?php

namespace App\Models;

use App\Enums\TeamSide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Photo d'époque d'un match : une équipe (team_side) ou les deux équipes / une scène de jeu (team_side NULL) */
class MatchPhoto extends Model
{
    protected $fillable = ['match_id', 'team_side', 'path', 'caption', 'width', 'height', 'position'];

    protected function casts(): array
    {
        return ['team_side' => TeamSide::class];
    }

    /** Légende du fichier, sinon l'équipe photographiée : « Le XV de France », « L'équipe d'Angleterre » */
    public function label(Country $opponent): string
    {
        if ($this->caption) {
            return $this->caption;
        }

        return match ($this->team_side) {
            TeamSide::FRANCE => 'Le XV de France',
            TeamSide::ADVERSAIRE => 'L\'équipe ' . match (true) {
                $opponent->code === 'WAL' => 'du pays de Galles',
                (bool) preg_match('/^[AEIOUYÉÈÊÂÎÔ]/iu', $opponent->name) => 'd\'' . $opponent->name,
                default => 'de ' . $opponent->name,
            },
            default => 'Les deux équipes',
        };
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(RugbyMatch::class, 'match_id');
    }
}
