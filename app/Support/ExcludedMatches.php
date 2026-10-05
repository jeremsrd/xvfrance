<?php

namespace App\Support;

/**
 * Matches sans sélection officielle (database/data/sources/excluded_matches.csv) : listés par certaines sources
 * (results.csv, equipe-france.fr) mais pas comptés parmi les matches du XV de France. Les imports les ignorent
 * et xv:remove-excluded-matches les retire de la base.
 */
final class ExcludedMatches
{
    private static ?array $rows = null;

    public static function contains(string $date, ?string $opponentCode): bool
    {
        return isset(self::rows()[substr($date, 0, 10) . '|' . $opponentCode]);
    }

    /** @return array<string, array<string, string>> clé « date|code adversaire » */
    public static function rows(): array
    {
        if (self::$rows !== null) {
            return self::$rows;
        }

        self::$rows = [];
        $path = base_path('database/data/sources/excluded_matches.csv');
        if (!is_file($path)) {
            return self::$rows;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, escape: '');
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            if ($line !== [null]) {
                $row = array_combine($header, $line);
                self::$rows[$row['date'] . '|' . $row['opponent_code']] = $row;
            }
        }
        fclose($handle);

        return self::$rows;
    }
}
