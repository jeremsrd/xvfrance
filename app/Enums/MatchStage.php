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
}
