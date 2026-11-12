<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Primary Target Marketplace
    |--------------------------------------------------------------------------
    |
    | United States (US) is the primary target marketplace for LocalDateHub.
    | All 6 supported country marketplaces remain independently accessible
    | and automatically routed via GeoIP detection.
    |
    */
    'primary_country' => env('MARKETPLACE_PRIMARY_COUNTRY', 'US'),
    'primary_slug' => 'us',
    'primary_currency' => 'USD',

    'supported_countries' => [
        'US' => [
            'code' => 'US',
            'slug' => 'us',
            'name' => 'United States',
            'iso3' => 'USA',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'flag' => '🇺🇸',
            'timezone' => 'America/New_York',
            'division_label' => 'State',
            'is_primary' => true,
            'sort_order' => 1,
        ],
        'GB' => [
            'code' => 'GB',
            'slug' => 'gb',
            'name' => 'United Kingdom',
            'iso3' => 'GBR',
            'currency_code' => 'GBP',
            'currency_symbol' => '£',
            'flag' => '🇬🇧',
            'timezone' => 'Europe/London',
            'division_label' => 'Nation / Region',
            'is_primary' => false,
            'sort_order' => 2,
        ],
        'CA' => [
            'code' => 'CA',
            'slug' => 'ca',
            'name' => 'Canada',
            'iso3' => 'CAN',
            'currency_code' => 'CAD',
            'currency_symbol' => 'C$',
            'flag' => '🇨🇦',
            'timezone' => 'America/Toronto',
            'division_label' => 'Province / Territory',
            'is_primary' => false,
            'sort_order' => 3,
        ],
        'AU' => [
            'code' => 'AU',
            'slug' => 'au',
            'name' => 'Australia',
            'iso3' => 'AUS',
            'currency_code' => 'AUD',
            'currency_symbol' => 'A$',
            'flag' => '🇦🇺',
            'timezone' => 'Australia/Sydney',
            'division_label' => 'State / Territory',
            'is_primary' => false,
            'sort_order' => 4,
        ],
        'IE' => [
            'code' => 'IE',
            'slug' => 'ie',
            'name' => 'Ireland',
            'iso3' => 'IRL',
            'currency_code' => 'EUR',
            'currency_symbol' => '€',
            'flag' => '🇮🇪',
            'timezone' => 'Europe/Dublin',
            'division_label' => 'County',
            'is_primary' => false,
            'sort_order' => 5,
        ],
        'NZ' => [
            'code' => 'NZ',
            'slug' => 'nz',
            'name' => 'New Zealand',
            'iso3' => 'NZL',
            'currency_code' => 'NZD',
            'currency_symbol' => 'NZ$',
            'flag' => '🇳🇿',
            'timezone' => 'Pacific/Auckland',
            'division_label' => 'Region',
            'is_primary' => false,
            'sort_order' => 6,
        ],
    ],
];

