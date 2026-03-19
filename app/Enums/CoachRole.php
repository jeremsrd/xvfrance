<?php

namespace App\Enums;

enum CoachRole: string
{
    case SELECTIONNEUR = 'selectionneur';
    case ENTRAINEUR_AVANTS = 'entraineur_avants';
    case ENTRAINEUR_ARRIERES = 'entraineur_arrieres';
    case ENTRAINEUR_DEFENSE = 'entraineur_defense';
    case ENTRAINEUR_TOUCHE = 'entraineur_touche';
    case ENTRAINEUR_MELEE = 'entraineur_melee';
    case PREPARATEUR_PHYSIQUE = 'preparateur_physique';
    case ADJOINT = 'adjoint';
}
