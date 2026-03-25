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

    public function points(): int
    {
        return match ($this) {
            self::ESSAI, self::ESSAI_PENALITE => 5,
            self::TRANSFORMATION => 2,
            self::PENALITE, self::DROP => 3,
            default => 0,
        };
    }
}
