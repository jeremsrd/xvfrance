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
}
