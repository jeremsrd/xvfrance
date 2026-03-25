<?php

namespace App\Enums;

enum CompetitionType: string
{
    case TOURNOI = 'tournoi';
    case COUPE_DU_MONDE = 'coupe_du_monde';
    case TEST_MATCH = 'test_match';
    case TOURNEE = 'tournee';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::TOURNOI => 'Tournoi',
            self::COUPE_DU_MONDE => 'Coupe du Monde',
            self::TEST_MATCH => 'Test match',
            self::TOURNEE => 'Tournée',
            self::AUTRE => 'Autre',
        };
    }
}
