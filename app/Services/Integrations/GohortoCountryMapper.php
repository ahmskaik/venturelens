<?php

namespace App\Services\Integrations;

class GohortoCountryMapper
{
    /** @var array<string, string> */
    private const NAME_TO_ISO2 = [
        'afghanistan' => 'AF',
        'albania' => 'AL',
        'algeria' => 'DZ',
        'argentina' => 'AR',
        'australia' => 'AU',
        'austria' => 'AT',
        'bahrain' => 'BH',
        'bangladesh' => 'BD',
        'belgium' => 'BE',
        'brazil' => 'BR',
        'bulgaria' => 'BG',
        'canada' => 'CA',
        'chile' => 'CL',
        'china' => 'CN',
        'colombia' => 'CO',
        'croatia' => 'HR',
        'cyprus' => 'CY',
        'czech republic' => 'CZ',
        'czechia' => 'CZ',
        'denmark' => 'DK',
        'egypt' => 'EG',
        'estonia' => 'EE',
        'ethiopia' => 'ET',
        'finland' => 'FI',
        'france' => 'FR',
        'germany' => 'DE',
        'greece' => 'GR',
        'hong kong' => 'HK',
        'hungary' => 'HU',
        'india' => 'IN',
        'indonesia' => 'ID',
        'iran' => 'IR',
        'iraq' => 'IQ',
        'ireland' => 'IE',
        'israel' => 'IL',
        'italy' => 'IT',
        'japan' => 'JP',
        'jordan' => 'JO',
        'kenya' => 'KE',
        'kuwait' => 'KW',
        'lebanon' => 'LB',
        'libya' => 'LY',
        'lithuania' => 'LT',
        'luxembourg' => 'LU',
        'malaysia' => 'MY',
        'mexico' => 'MX',
        'morocco' => 'MA',
        'netherlands' => 'NL',
        'new zealand' => 'NZ',
        'nigeria' => 'NG',
        'norway' => 'NO',
        'oman' => 'OM',
        'pakistan' => 'PK',
        'palestine' => 'PS',
        'philippines' => 'PH',
        'poland' => 'PL',
        'portugal' => 'PT',
        'qatar' => 'QA',
        'romania' => 'RO',
        'russia' => 'RU',
        'saudi arabia' => 'SA',
        'serbia' => 'RS',
        'singapore' => 'SG',
        'slovakia' => 'SK',
        'slovenia' => 'SI',
        'south africa' => 'ZA',
        'south korea' => 'KR',
        'korea' => 'KR',
        'spain' => 'ES',
        'sri lanka' => 'LK',
        'sudan' => 'SD',
        'sweden' => 'SE',
        'switzerland' => 'CH',
        'syria' => 'SY',
        'taiwan' => 'TW',
        'thailand' => 'TH',
        'tunisia' => 'TN',
        'turkey' => 'TR',
        'türkiye' => 'TR',
        'uae' => 'AE',
        'united arab emirates' => 'AE',
        'ukraine' => 'UA',
        'united kingdom' => 'GB',
        'uk' => 'GB',
        'united states' => 'US',
        'usa' => 'US',
        'venezuela' => 'VE',
        'vietnam' => 'VN',
        'yemen' => 'YE',
        'zambia' => 'ZM',
    ];

    /** Preferred English labels for ISO2 codes (overrides longest-name heuristic). */
    private const ISO2_LABEL_OVERRIDES = [
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'KR' => 'South Korea',
        'PS' => 'Palestine',
        'TR' => 'Türkiye',
        'US' => 'United States',
    ];

    public static function displayName(string $iso2): string
    {
        $code = strtoupper(trim($iso2));
        if (strlen($code) !== 2 || ! ctype_alpha($code)) {
            return $iso2;
        }

        if (extension_loaded('intl')) {
            $name = \Locale::getDisplayRegion('-'.$code, 'en');
            if (is_string($name) && $name !== '' && $name !== $code) {
                return $name;
            }
        }

        return self::iso2Labels()[$code] ?? $code;
    }

    /**
     * @return array<string, string>
     */
    public static function iso2Labels(): array
    {
        static $labels = null;
        if (is_array($labels)) {
            return $labels;
        }

        $labels = [];
        foreach (self::NAME_TO_ISO2 as $name => $code) {
            $label = ucwords($name);
            if (! isset($labels[$code]) || strlen($name) > strlen(strtolower($labels[$code]))) {
                $labels[$code] = $label;
            }
        }

        foreach (self::ISO2_LABEL_OVERRIDES as $code => $label) {
            $labels[$code] = $label;
        }

        return $labels;
    }

    public static function resolve(?string ...$candidates): string
    {
        foreach ($candidates as $candidate) {
            if ($candidate === null || trim($candidate) === '') {
                continue;
            }

            $normalized = strtolower(trim($candidate));

            if (strlen($normalized) === 2 && ctype_alpha($normalized)) {
                return strtoupper($normalized);
            }

            if (isset(self::NAME_TO_ISO2[$normalized])) {
                return self::NAME_TO_ISO2[$normalized];
            }

            foreach (self::NAME_TO_ISO2 as $name => $code) {
                if (str_contains($normalized, $name)) {
                    return $code;
                }
            }
        }

        return 'TR';
    }
}
