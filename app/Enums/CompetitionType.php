<?php

namespace App\Enums;

enum CompetitionType: string
{
    case TOURNOI = 'tournoi';
    case COUPE_DU_MONDE = 'coupe_du_monde';
    case TEST_MATCH = 'test_match';
    case TOURNEE = 'tournee';
    case AUTRE = 'autre';
}
