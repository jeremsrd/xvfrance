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
     * Barème historique du rugby (les changements s'appliquent à la saison suivante,
     * d'où la bascule au 1er juillet) :
     * jusqu'en 1948 : Essai=3, Transfo=2, Pénalité=3, Drop=4
     * 1948-1971     : Essai=3, Transfo=2, Pénalité=3, Drop=3
     * 1971-1992     : Essai=4
     * depuis 1992   : Essai=5
     * Essai de pénalité : valeur d'un essai (transformation à part), puis 7 points
     * sans transformation depuis juillet 2017.
     */
    public function points(\DateTimeInterface|null $matchDate = null): int
    {
        $date = $matchDate?->format('Y-m-d') ?? '2024-01-01';

        return match ($this) {
            self::CARTON_JAUNE, self::CARTON_ROUGE => 0,
            self::TRANSFORMATION => 2,
            self::PENALITE => 3,
            self::DROP => $date < '1948-07-01' ? 4 : 3,
            self::ESSAI_PENALITE => $date >= '2017-07-01' ? 7 : self::ESSAI->points($matchDate),
            self::ESSAI => match (true) {
                $date < '1971-07-01' => 3,
                $date < '1992-07-01' => 4,
                default => 5,
            },
        };
    }
}
