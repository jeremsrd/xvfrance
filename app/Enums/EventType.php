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
}
