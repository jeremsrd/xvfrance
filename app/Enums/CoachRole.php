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

    public function label(): string
    {
        return match ($this) {
            self::SELECTIONNEUR => 'Sélectionneur',
            self::ENTRAINEUR_AVANTS => 'Entraîneur des avants',
            self::ENTRAINEUR_ARRIERES => 'Entraîneur des arrières',
            self::ENTRAINEUR_DEFENSE => 'Entraîneur de la défense',
            self::ENTRAINEUR_TOUCHE => 'Entraîneur de la touche',
            self::ENTRAINEUR_MELEE => 'Entraîneur de la mêlée',
            self::PREPARATEUR_PHYSIQUE => 'Préparateur physique',
            self::ADJOINT => 'Adjoint',
        };
    }
}
