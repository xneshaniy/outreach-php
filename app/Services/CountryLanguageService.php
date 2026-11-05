<?php

namespace App\Services;

class CountryLanguageService
{
    /**
     * Map of standard countries to their ISO code and default primary language.
     */
    protected static array $countryMap = [
        'france' => ['code' => 'FR', 'lang' => 'fr', 'name' => 'France'],
        'germany' => ['code' => 'DE', 'lang' => 'de', 'name' => 'Germany'],
        'deutschland' => ['code' => 'DE', 'lang' => 'de', 'name' => 'Germany'],
        'belgium' => ['code' => 'BE', 'lang' => 'fr', 'name' => 'Belgium'], // French default
        'switzerland' => ['code' => 'CH', 'lang' => 'de', 'name' => 'Switzerland'], // German default
        'austria' => ['code' => 'AT', 'lang' => 'de', 'name' => 'Austria'],
        'österreich' => ['code' => 'AT', 'lang' => 'de', 'name' => 'Austria'],
        'spain' => ['code' => 'ES', 'lang' => 'es', 'name' => 'Spain'],
        'españa' => ['code' => 'ES', 'lang' => 'es', 'name' => 'Spain'],
        'italy' => ['code' => 'IT', 'lang' => 'it', 'name' => 'Italy'],
        'italia' => ['code' => 'IT', 'lang' => 'it', 'name' => 'Italy'],
        'netherlands' => ['code' => 'NL', 'lang' => 'nl', 'name' => 'Netherlands'],
        'united kingdom' => ['code' => 'GB', 'lang' => 'en', 'name' => 'United Kingdom'],
        'uk' => ['code' => 'GB', 'lang' => 'en', 'name' => 'United Kingdom'],
        'united states' => ['code' => 'US', 'lang' => 'en', 'name' => 'United States'],
        'usa' => ['code' => 'US', 'lang' => 'en', 'name' => 'United States'],
        'canada' => ['code' => 'CA', 'lang' => 'en', 'name' => 'Canada'],
    ];

    /**
     * Common French cities for location inference
     */
    protected static array $frenchCities = [
        'paris', 'marseille', 'lyon', 'toulouse', 'nice', 'nantes', 'strasbourg',
        'montpellier', 'bordeaux', 'lille', 'rennes', 'reims', 'toulon', 'saint-étienne',
        'grenoble', 'dijon', 'angers', 'nîmes', 'villeurbanne', 'clermont-ferrand',
        'le havre', 'aix-en-provence', 'brest', 'tours', 'amiens', 'limoges', 'annecy'
    ];

    /**
     * Common German cities for location inference
     */
    protected static array $germanCities = [
        'berlin', 'hamburg', 'munich', 'münchen', 'cologne', 'köln', 'frankfurt',
        'stuttgart', 'düsseldorf', 'leipzig', 'dortmund', 'essen', 'bremen', 'dresden',
        'hannover', 'nuremberg', 'nürnberg', 'duisburg', 'bochum', 'wuppertal', 'bonn',
        'bielefeld', 'mannheim', 'karlsruhe', 'münster', 'augsburg', 'wiesbaden'
    ];

    /**
     * Detect country and language based on country string, city string, website TLD, or full address
     */
    public static function detect(string $country = '', string $city = '', string $website = '', string $address = ''): array
    {
        $countryLower = strtolower(trim($country));
        $cityLower = strtolower(trim($city));
        $addressLower = strtolower(trim($address));
        $tld = '';

        if (!empty($website)) {
            $host = parse_url($website, PHP_URL_HOST) ?? $website;
            $parts = explode('.', $host);
            $tld = strtolower(end($parts));
        }

        // 1. Direct country string match
        if (!empty($countryLower) && isset(self::$countryMap[$countryLower])) {
            return self::$countryMap[$countryLower];
        }

        // 2. City match
        if (in_array($cityLower, self::$frenchCities) || str_contains($addressLower, 'france')) {
            return ['code' => 'FR', 'lang' => 'fr', 'name' => 'France'];
        }
        if (in_array($cityLower, self::$germanCities) || str_contains($addressLower, 'germany') || str_contains($addressLower, 'deutschland')) {
            return ['code' => 'DE', 'lang' => 'de', 'name' => 'Germany'];
        }

        // 3. TLD match
        if ($tld === 'fr') {
            return ['code' => 'FR', 'lang' => 'fr', 'name' => 'France'];
        }
        if ($tld === 'de') {
            return ['code' => 'DE', 'lang' => 'de', 'name' => 'Germany'];
        }
        if ($tld === 'es') {
            return ['code' => 'ES', 'lang' => 'es', 'name' => 'Spain'];
        }
        if ($tld === 'it') {
            return ['code' => 'IT', 'lang' => 'it', 'name' => 'Italy'];
        }
        if ($tld === 'nl') {
            return ['code' => 'NL', 'lang' => 'nl', 'name' => 'Netherlands'];
        }
        if ($tld === 'uk' || $tld === 'co.uk') {
            return ['code' => 'GB', 'lang' => 'en', 'name' => 'United Kingdom'];
        }

        // Default fallback to English
        $displayName = !empty($country) ? ucfirst($country) : 'International';
        return ['code' => 'GLOBAL', 'lang' => 'en', 'name' => $displayName];
    }
}
