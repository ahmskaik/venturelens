<?php

namespace App\Support;

use App\Services\Integrations\GohortoCountryMapper;

class CountryCodes
{
    public static function displayName(string $iso2): string
    {
        return GohortoCountryMapper::displayName($iso2);
    }

    /**
     * @param  iterable<string|null>  $codes
     * @return list<array{code: string, name: string}>
     */
    public static function optionsForCodes(iterable $codes): array
    {
        $options = [];

        foreach ($codes as $code) {
            if (! is_string($code) || trim($code) === '') {
                continue;
            }

            $iso2 = strtoupper(trim($code));
            $options[$iso2] = [
                'code' => $iso2,
                'name' => self::displayName($iso2),
            ];
        }

        $options = array_values($options);
        usort($options, fn (array $a, array $b) => strcasecmp($a['name'], $b['name']));

        return $options;
    }
}
