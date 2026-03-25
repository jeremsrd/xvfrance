<?php

namespace App\Enums;

enum PlayerPosition: string
{
    case PILIER_GAUCHE = 'pilier_gauche';
    case TALONNEUR = 'talonneur';
    case PILIER_DROIT = 'pilier_droit';
    case DEUXIEME_LIGNE = 'deuxieme_ligne';
    case TROISIEME_LIGNE_AILE = 'troisieme_ligne_aile';
    case NUMERO_HUIT = 'numero_huit';
    case DEMI_DE_MELEE = 'demi_de_melee';
    case DEMI_OUVERTURE = 'demi_ouverture';
    case AILIER = 'ailier';
    case CENTRE = 'centre';
    case ARRIERE = 'arriere';

    public function label(): string
    {
        return match ($this) {
            self::PILIER_GAUCHE => 'Pilier gauche',
            self::TALONNEUR => 'Talonneur',
            self::PILIER_DROIT => 'Pilier droit',
            self::DEUXIEME_LIGNE => '2e ligne',
            self::TROISIEME_LIGNE_AILE => '3e ligne aile',
            self::NUMERO_HUIT => 'N° 8',
            self::DEMI_DE_MELEE => 'Demi de mêlée',
            self::DEMI_OUVERTURE => 'Demi d\'ouverture',
            self::AILIER => 'Ailier',
            self::CENTRE => 'Centre',
            self::ARRIERE => 'Arrière',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::PILIER_GAUCHE => 'PG',
            self::TALONNEUR => 'TL',
            self::PILIER_DROIT => 'PD',
            self::DEUXIEME_LIGNE => '2L',
            self::TROISIEME_LIGNE_AILE => '3L',
            self::NUMERO_HUIT => 'N8',
            self::DEMI_DE_MELEE => 'DM',
            self::DEMI_OUVERTURE => 'DO',
            self::AILIER => 'AI',
            self::CENTRE => 'CE',
            self::ARRIERE => 'AR',
        };
    }
}
