<?php

namespace App\Support;

/**
 * Nombres accordés à la française : « 0 victoire », « 1 match », « 3 matches ».
 */
final class FrenchText
{
    public static function count(int $n, string $singular, ?string $plural = null): string
    {
        return number_format($n, 0, ',', "\u{202F}") . ' ' . ($n > 1 ? ($plural ?? $singular . 's') : $singular);
    }

    /** « de 1906 à 1910 », ou « en 1906 » si les deux années sont les mêmes */
    public static function years(int $from, int $to): string
    {
        return $from === $to ? 'en ' . $from : 'de ' . $from . ' à ' . $to;
    }
}
