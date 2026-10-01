<?php

namespace App\Enums;

enum MatchStage: string
{
    case POULE = 'poule';
    case HUITIEME = 'huitieme';
    case QUART = 'quart';
    case DEMI = 'demi';
    case FINALE = 'finale';
    case PETITE_FINALE = 'petite_finale';
    case JOURNEE = 'journee';
    case TEST = 'test';

    public function label(): string
    {
        return match ($this) {
            self::POULE => 'Phase de poules',
            self::HUITIEME => 'Huitième de finale',
            self::QUART => 'Quart de finale',
            self::DEMI => 'Demi-finale',
            self::FINALE => 'Finale',
            self::PETITE_FINALE => 'Petite finale',
            self::JOURNEE => 'Journée',
            self::TEST => 'Test',
        };
    }
}
