<?php

namespace App\Enums;

enum EventType: string
{
    case ESSAI = 'essai';
    case ESSAI_PENALITE = 'essai_penalite';
    case TRANSFORMATION = 'transformation';
    case PENALITE = 'penalite';
    case DROP = 'drop';
    case CARTON_JAUNE = 'carton_jaune';
    case CARTON_ROUGE = 'carton_rouge';

    public function label(): string
    {
        return match ($this) {
            self::ESSAI => 'Essai',
            self::ESSAI_PENALITE => 'Essai de pénalité',
            self::TRANSFORMATION => 'Transformation',
            self::PENALITE => 'Pénalité',
            self::DROP => 'Drop',
            self::CARTON_JAUNE => 'Carton jaune',
            self::CARTON_ROUGE => 'Carton rouge',
        };
    }

    /**
     * Barème historique du rugby :
     * 1893-1948 : Essai=3, Transfo=2, Pénalité=3, Drop=4
     * 1948-1971 : Essai=3, Transfo=2, Pénalité=3, Drop=3
     * 1971-1992 : Essai=4, Transfo=2, Pénalité=3, Drop=3
     * 1992+     : Essai=5, Transfo=2, Pénalité=3, Drop=3
     */
    public function points(\DateTimeInterface|null $matchDate = null): int
    {
        if (in_array($this, [self::CARTON_JAUNE, self::CARTON_ROUGE])) {
            return 0;
        }

        if ($this === self::TRANSFORMATION) {
            return 2;
        }

        if ($this === self::PENALITE) {
            return 3;
        }

        $year = $matchDate?->format('Y') ?? 2024;

        if ($this === self::DROP) {
            return $year < 1948 ? 4 : 3;
        }

        // ESSAI et ESSAI_PENALITE
        if ($year < 1948) {
            return 3;
        }
        if ($year < 1971) {
            return 3;
        }
        if ($year < 1992) {
            return 4;
        }

        return 5;
    }
}
