<?php

namespace App\Support;

/**
 * CSV versionné des coordonnées des stades (database/data/sources/venue_coordinates.csv).
 * Une ligne par stade, identifiée par nom + ville + code pays.
 */
final class VenueCoordinatesFile
{
    public const COLUMNS = ['name', 'city', 'country_code', 'latitude', 'longitude', 'precision', 'source'];

    public function __construct(private readonly string $path)
    {
    }

    public static function key(string $name, ?string $city, ?string $countryCode): string
    {
        return mb_strtolower(trim($name) . '|' . trim($city ?? '') . '|' . trim($countryCode ?? ''));
    }

    /** @return array<string, array<string, string>> */
    public function read(): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $handle = fopen($this->path, 'r');
        $header = fgetcsv($handle, escape: '');
        $rows = [];

        while (($line = fgetcsv($handle, escape: '')) !== false) {
            if ($line === [null]) {
                continue;
            }
            $row = array_combine($header, $line);
            $rows[self::key($row['name'], $row['city'], $row['country_code'])] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /** @param  array<string, array<string, mixed>>  $rows */
    public function write(array $rows): void
    {
        uasort($rows, fn ($a, $b) => [$a['country_code'], $a['city'], $a['name']] <=> [$b['country_code'], $b['city'], $b['name']]);

        $handle = fopen($this->path, 'w');
        fputcsv($handle, self::COLUMNS, escape: '');
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($column) => $row[$column] ?? '', self::COLUMNS), escape: '');
        }
        fclose($handle);
    }
}
