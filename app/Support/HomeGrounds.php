<?php

namespace App\Support;

use App\Models\Country;
use App\Models\Venue;

/**
 * Stades de repli (database/data/sources/venue_home_grounds.csv) : un pays reçoit pendant une période
 * dans un stade hors de ses frontières, ex. le pays de Galles à Wembley pendant la construction
 * du Millennium Stadium. Le match y est alors à domicile pour ce pays, pas sur terrain neutre.
 */
final class HomeGrounds
{
    private static ?array $rows = null;

    /** Pays qui reçoit dans ce stade à cette date : le pays de repli s'il y en a un, sinon le pays du stade */
    public static function hostCountryId(Venue $venue, \DateTimeInterface $date): ?int
    {
        $day = $date->format('Y-m-d');
        $name = Venue::normalizeName($venue->name);

        foreach (self::rows() as $row) {
            if ($row['venue'] === $name && $row['from'] <= $day && $day <= $row['until']) {
                return Country::where('code', $row['team_code'])->value('id') ?? $venue->country_id;
            }
        }

        return $venue->country_id;
    }

    private static function rows(): array
    {
        if (self::$rows !== null) {
            return self::$rows;
        }

        self::$rows = [];
        $path = base_path('database/data/sources/venue_home_grounds.csv');
        if (!is_file($path)) {
            return self::$rows;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '');
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            if ($line === [null]) {
                continue;
            }
            $row = array_combine($header, $line);
            $row['venue'] = Venue::normalizeName($row['venue']);
            self::$rows[] = $row;
        }
        fclose($handle);

        return self::$rows;
    }
}
