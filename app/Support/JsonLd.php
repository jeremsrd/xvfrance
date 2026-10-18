<?php

namespace App\Support;

/**
 * Données structurées schema.org, sans les valeurs vides.
 * Construites en PHP : dans un gabarit Blade, « @context » serait pris pour une directive.
 */
final class JsonLd
{
    public static function script(array $data): string
    {
        $json = json_encode(['@context' => 'https://schema.org'] + self::clean($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    private static function clean(array $value): array
    {
        return array_filter(
            array_map(fn ($v) => is_array($v) ? self::clean($v) : $v, $value),
            fn ($v) => $v !== null && $v !== '' && $v !== [],
        );
    }
}
