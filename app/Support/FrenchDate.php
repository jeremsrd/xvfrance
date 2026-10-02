<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Dates en toutes lettres à la française : « 1ᵉʳ janvier 1906 », « lundi 1ᵉʳ janvier 1906 ».
 */
final class FrenchDate
{
    public static function long(\DateTimeInterface $date, bool $weekday = false, bool $year = true): string
    {
        $carbon = Carbon::instance($date);
        $day = $carbon->day === 1 ? "1\u{1D49}\u{02B3}" : (string) $carbon->day;

        return ($weekday ? $carbon->translatedFormat('l') . ' ' : '') . $day . ' ' . $carbon->translatedFormat($year ? 'F Y' : 'F');
    }
}
