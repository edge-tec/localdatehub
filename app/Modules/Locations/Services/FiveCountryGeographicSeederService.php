<?php

namespace App\Modules\Locations\Services;

use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\CountryCategory;
use App\Modules\Locations\Models\State;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FiveCountryGeographicSeederService
{
    /**
     * Seed or update the verified 5-country marketplace dataset (GB, CA, AU, IE, NZ)
     * without deleting or breaking any existing records.
     *
     * @return array<string, mixed>
     */
    public function seedCountry(string $countryCode): array
    {
        $res = $this->seedAll($countryCode);

        return [
            'countries' => $res['countries_seeded'] ?? 1,
            'areas' => $res['areas_seeded'] ?? 0,
            'cities' => $res['cities_seeded'] ?? 0,
        ];
    }

    public function seedAll(?string $onlyCountryCode = null): array
    {
        $this->ensureStatesSchemaCompatibility();

        $datasets = $this->getDatasetDefinitions();
        if ($onlyCountryCode) {
            $upper = strtoupper(trim($onlyCountryCode));
            $datasets = array_filter($datasets, fn ($d) => $d['code'] === $upper);
        }

        // Ensure non-5-marketplace countries have is_marketplace_enabled = false
        Country::whereNotIn('code', Country::FIVE_MARKETPLACES)
            ->update(['is_marketplace_enabled' => false]);

        $categories = Category::where('is_active', true)->orderBy('display_order')->get();

        $summary = [
            'countries_seeded' => 0,
            'areas_seeded' => 0,
            'cities_seeded' => 0,
            'countries' => [],
        ];

        $orderIdx = 1;
        $hasCountrySortOrder = \Illuminate\Support\Facades\Schema::hasColumn('countries', 'sort_order');
        foreach ($datasets as $countryData) {
            $sortOrder = $countryData['sort_order'] ?? $orderIdx++;
            DB::transaction(function () use ($countryData, $categories, $sortOrder, $hasCountrySortOrder, &$summary) {
                $countryAttrs = [
                    'slug' => $countryData['slug'],
                    'name' => $countryData['name'],
                    'iso3' => $countryData['iso3'],
                    'phone_code' => $countryData['phone_code'],
                    'currency_code' => $countryData['currency_code'],
                    'currency_symbol' => $countryData['currency_symbol'],
                    'flag' => $countryData['flag'],
                    'timezone' => $countryData['timezone'],
                    'division_label' => $countryData['division_label'],
                    'seo_title' => $countryData['seo_title'],
                    'seo_description' => $countryData['seo_description'],
                    'is_active' => true,
                    'is_marketplace_enabled' => true,
                ];
                if ($hasCountrySortOrder) {
                    $countryAttrs['sort_order'] = $sortOrder;
                }
                $country = Country::updateOrCreate(
                    ['code' => $countryData['code']],
                    $countryAttrs
                );

                $summary['countries_seeded']++;
                $countryAreas = 0;
                $countryCities = 0;
                $now = now();

                $stateRows = [];
                foreach ($countryData['areas'] as $areaData) {
                    $areaSlug = $areaData['slug'] ?? Str::slug($areaData['name']);
                    $stateRows[] = [
                        'country_id' => $country->id,
                        'code' => strtoupper($areaData['code']),
                        'name' => $areaData['name'],
                        'slug' => $areaSlug,
                        'administrative_level' => $areaData['level'] ?? 'state',
                        'is_territory' => (bool) ($areaData['is_territory'] ?? false),
                        'latitude' => $areaData['lat'] ?? null,
                        'longitude' => $areaData['lng'] ?? null,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $countryAreas++;
                    $summary['areas_seeded']++;
                }

                if (!empty($stateRows)) {
                    State::upsert(
                        $stateRows,
                        ['country_id', 'code'],
                        ['name', 'slug', 'administrative_level', 'is_territory', 'latitude', 'longitude', 'is_active', 'updated_at']
                    );
                }

                $stateIdMap = State::where('country_id', $country->id)->pluck('id', 'code')->all();

                $cityRows = [];
                foreach ($countryData['areas'] as $areaData) {
                    $stateId = $stateIdMap[strtoupper($areaData['code'])] ?? null;
                    if (!$stateId) {
                        continue;
                    }
                    foreach ($areaData['cities'] as $cityData) {
                        $citySlug = $cityData['slug'] ?? Str::slug($cityData['name']);
                        $cityRows[$stateId . '_' . $citySlug] = [
                            'state_id' => $stateId,
                            'slug' => $citySlug,
                            'country_id' => $country->id,
                            'name' => $cityData['name'],
                            'city_type' => 'city',
                            'latitude' => $cityData['lat'] ?? null,
                            'longitude' => $cityData['lng'] ?? null,
                            'population' => $cityData['pop'] ?? 100000,
                            'timezone' => $cityData['tz'] ?? $countryData['timezone'],
                            'is_popular' => (bool) ($cityData['popular'] ?? false),
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $countryCities++;
                        $summary['cities_seeded']++;
                    }
                }

                foreach (array_chunk(array_values($cityRows), 250) as $chunk) {
                    City::upsert(
                        $chunk,
                        ['state_id', 'slug'],
                        ['country_id', 'name', 'city_type', 'latitude', 'longitude', 'population', 'timezone', 'is_popular', 'is_active', 'updated_at']
                    );
                }

                // Backfill country_id on any existing cities & listings belonging to this country's states
                if (!empty($stateIdMap)) {
                    $stateIds = array_values($stateIdMap);
                    City::whereIn('state_id', $stateIds)
                        ->whereNull('country_id')
                        ->update(['country_id' => $country->id]);

                    \App\Modules\Listings\Models\Listing::whereIn('state_id', $stateIds)
                        ->whereNull('country_id')
                        ->update([
                            'country_id' => $country->id,
                            'currency_code' => $country->currency_code,
                        ]);
                }

                // Activate all existing platform categories for this country if not yet linked
                foreach ($categories as $idx => $cat) {
                    CountryCategory::firstOrCreate(
                        [
                            'country_id' => $country->id,
                            'category_id' => $cat->id,
                        ],
                        [
                            'is_active' => true,
                            'sort_order' => $idx + 1,
                        ]
                    );
                }

                $summary['countries'][$country->code] = [
                    'name' => $country->name,
                    'slug' => $country->slug,
                    'currency' => "{$country->currency_code} ({$country->currency_symbol})",
                    'areas' => $countryAreas,
                    'cities' => $countryCities,
                ];
            });
        }

        return $summary;
    }

    /**
     * Ensure states table on MySQL/SQLite allows multi-country state codes (e.g. WA, NT, TAS, NSW, VIC, QLD, ACT).
     */
    protected function ensureStatesSchemaCompatibility(): void
    {
        try {
            if (DB::getDriverName() === 'mysql') {
                $indexes = DB::select("SHOW INDEX FROM states WHERE Column_name = 'code' AND Non_unique = 0");
                foreach ($indexes as $idx) {
                    $keyName = $idx->Key_name ?? '';
                    if ($keyName !== '' && $keyName !== 'PRIMARY' && $keyName !== 'states_country_id_code_unique') {
                        try {
                            DB::statement("ALTER TABLE states DROP INDEX `{$keyName}`");
                        } catch (\Throwable) {
                        }
                    }
                }
                try {
                    DB::statement("ALTER TABLE states MODIFY `code` VARCHAR(16) NOT NULL");
                } catch (\Throwable) {
                }
            }
        } catch (\Throwable) {
        }
    }

    /**
     * Verified geographic dataset for the 6 supported country marketplaces (US primary + GB, CA, AU, IE, NZ).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDatasetDefinitions(): array
    {
        return [
            // 1. UNITED STATES (US — PRIMARY TARGET MARKETPLACE)
            [
                'code' => 'US',
                'slug' => 'us',
                'name' => 'United States',
                'iso3' => 'USA',
                'phone_code' => '+1',
                'currency_code' => 'USD',
                'currency_symbol' => '$',
                'flag' => '🇺🇸',
                'timezone' => 'America/New_York',
                'division_label' => 'State',
                'sort_order' => 1,
                'seo_title' => 'United States Classifieds & Local Dating Marketplace — LocalDateHub USA',
                'seo_description' => 'Explore verified local personal classifieds, adult dating, and community connections across all 50 US states and Washington DC on LocalDateHub USA.',
                'areas' => $this->getUnitedStatesAreasDataset(),
            ],

            // 2. UNITED KINGDOM (GB)
            [
                'code' => 'GB',
                'slug' => 'gb',
                'name' => 'United Kingdom',
                'iso3' => 'GBR',
                'phone_code' => '+44',
                'currency_code' => 'GBP',
                'currency_symbol' => '£',
                'flag' => '🇬🇧',
                'timezone' => 'Europe/London',
                'division_label' => 'Nation / Region',
                'sort_order' => 2,
                'seo_title' => 'United Kingdom Classifieds & Local Dating Marketplace — LocalDateHub UK',
                'seo_description' => 'Explore verified local personal classifieds, dating, and connections across England, Scotland, Wales, Northern Ireland, and all UK regions on LocalDateHub UK.',
                'areas' => $this->getUnitedKingdomAreasDataset(),
            ],

            // 2. CANADA (CA)
            [
                'code' => 'CA',
                'slug' => 'ca',
                'name' => 'Canada',
                'iso3' => 'CAN',
                'phone_code' => '+1',
                'currency_code' => 'CAD',
                'currency_symbol' => 'C$',
                'flag' => '🇨🇦',
                'timezone' => 'America/Toronto',
                'division_label' => 'Province / Territory',
                'seo_title' => 'Canada Classifieds & Local Dating Marketplace — LocalDateHub Canada',
                'seo_description' => 'Discover verified personal classifieds and local dating across all Canadian provinces and territories including Ontario, BC, Quebec, and Alberta.',
                'areas' => [
                    [
                        'code' => 'ON',
                        'name' => 'Ontario',
                        'slug' => 'ontario',
                        'level' => 'province',
                        'lat' => 51.2538,
                        'lng' => -85.3232,
                        'cities' => [
                            ['name' => 'Toronto', 'lat' => 43.6532, 'lng' => -79.3832, 'pop' => 2794356, 'popular' => true],
                            ['name' => 'Ottawa', 'lat' => 45.4215, 'lng' => -75.6972, 'pop' => 1017449, 'popular' => true],
                            ['name' => 'Mississauga', 'lat' => 43.5890, 'lng' => -79.6441, 'pop' => 717961, 'popular' => true],
                            ['name' => 'Brampton', 'lat' => 43.7315, 'lng' => -79.7624, 'pop' => 656480, 'popular' => true],
                            ['name' => 'Hamilton', 'lat' => 43.2557, 'lng' => -79.8711, 'pop' => 569353, 'popular' => true],
                            ['name' => 'London', 'lat' => 42.9849, 'lng' => -81.2453, 'pop' => 422324, 'popular' => true],
                            ['name' => 'Kitchener', 'lat' => 43.4516, 'lng' => -80.4925, 'pop' => 256885, 'popular' => false],
                            ['name' => 'Windsor', 'lat' => 42.3149, 'lng' => -83.0364, 'pop' => 229660, 'popular' => false],
                        ],
                    ],
                    [
                        'code' => 'QC',
                        'name' => 'Quebec',
                        'slug' => 'quebec',
                        'level' => 'province',
                        'lat' => 52.9399,
                        'lng' => -73.5491,
                        'cities' => [
                            ['name' => 'Montreal', 'lat' => 45.5017, 'lng' => -73.5673, 'pop' => 1762949, 'popular' => true],
                            ['name' => 'Quebec City', 'slug' => 'quebec-city', 'lat' => 46.8139, 'lng' => -71.2080, 'pop' => 549459, 'popular' => true],
                            ['name' => 'Laval', 'lat' => 45.6066, 'lng' => -73.7124, 'pop' => 438366, 'popular' => false],
                            ['name' => 'Gatineau', 'lat' => 45.4765, 'lng' => -75.7013, 'pop' => 291041, 'popular' => false],
                            ['name' => 'Sherbrooke', 'lat' => 45.4042, 'lng' => -71.8929, 'pop' => 172950, 'popular' => false],
                        ],
                    ],
                    [
                        'code' => 'BC',
                        'name' => 'British Columbia',
                        'slug' => 'british-columbia',
                        'level' => 'province',
                        'lat' => 53.7267,
                        'lng' => -127.6476,
                        'cities' => [
                            ['name' => 'Vancouver', 'lat' => 49.2827, 'lng' => -123.1207, 'pop' => 662248, 'popular' => true, 'tz' => 'America/Vancouver'],
                            ['name' => 'Surrey', 'lat' => 49.1913, 'lng' => -122.8490, 'pop' => 568322, 'popular' => true, 'tz' => 'America/Vancouver'],
                            ['name' => 'Victoria', 'lat' => 48.4284, 'lng' => -123.3656, 'pop' => 91867, 'popular' => true, 'tz' => 'America/Vancouver'],
                            ['name' => 'Burnaby', 'lat' => 49.2488, 'lng' => -122.9805, 'pop' => 249125, 'popular' => false, 'tz' => 'America/Vancouver'],
                            ['name' => 'Kelowna', 'lat' => 49.8880, 'lng' => -119.4960, 'pop' => 144576, 'popular' => false, 'tz' => 'America/Vancouver'],
                        ],
                    ],
                    [
                        'code' => 'AB',
                        'name' => 'Alberta',
                        'slug' => 'alberta',
                        'level' => 'province',
                        'lat' => 53.9333,
                        'lng' => -116.5765,
                        'cities' => [
                            ['name' => 'Calgary', 'lat' => 51.0447, 'lng' => -114.0719, 'pop' => 1306784, 'popular' => true, 'tz' => 'America/Edmonton'],
                            ['name' => 'Edmonton', 'lat' => 53.5461, 'lng' => -113.4938, 'pop' => 1010899, 'popular' => true, 'tz' => 'America/Edmonton'],
                            ['name' => 'Red Deer', 'lat' => 52.2681, 'lng' => -113.8112, 'pop' => 100844, 'popular' => false, 'tz' => 'America/Edmonton'],
                            ['name' => 'Lethbridge', 'lat' => 49.6956, 'lng' => -112.8451, 'pop' => 98406, 'popular' => false, 'tz' => 'America/Edmonton'],
                        ],
                    ],
                    [
                        'code' => 'MB',
                        'name' => 'Manitoba',
                        'slug' => 'manitoba',
                        'level' => 'province',
                        'lat' => 53.7609,
                        'lng' => -98.8139,
                        'cities' => [
                            ['name' => 'Winnipeg', 'lat' => 49.8951, 'lng' => -97.1384, 'pop' => 749607, 'popular' => true, 'tz' => 'America/Winnipeg'],
                            ['name' => 'Brandon', 'lat' => 49.8485, 'lng' => -99.9501, 'pop' => 51313, 'popular' => false, 'tz' => 'America/Winnipeg'],
                        ],
                    ],
                    [
                        'code' => 'SK',
                        'name' => 'Saskatchewan',
                        'slug' => 'saskatchewan',
                        'level' => 'province',
                        'lat' => 52.9399,
                        'lng' => -106.4509,
                        'cities' => [
                            ['name' => 'Saskatoon', 'lat' => 52.1579, 'lng' => -106.6702, 'pop' => 266141, 'popular' => true, 'tz' => 'America/Regina'],
                            ['name' => 'Regina', 'lat' => 50.4452, 'lng' => -104.6189, 'pop' => 226404, 'popular' => false, 'tz' => 'America/Regina'],
                        ],
                    ],
                    [
                        'code' => 'NS',
                        'name' => 'Nova Scotia',
                        'slug' => 'nova-scotia',
                        'level' => 'province',
                        'lat' => 44.6820,
                        'lng' => -63.7443,
                        'cities' => [
                            ['name' => 'Halifax', 'lat' => 44.6488, 'lng' => -63.5752, 'pop' => 439819, 'popular' => true, 'tz' => 'America/Halifax'],
                            ['name' => 'Sydney', 'lat' => 46.1368, 'lng' => -60.1942, 'pop' => 30960, 'popular' => false, 'tz' => 'America/Halifax'],
                        ],
                    ],
                    [
                        'code' => 'NB',
                        'name' => 'New Brunswick',
                        'slug' => 'new-brunswick',
                        'level' => 'province',
                        'lat' => 46.5653,
                        'lng' => -66.4619,
                        'cities' => [
                            ['name' => 'Moncton', 'lat' => 46.0878, 'lng' => -64.7782, 'pop' => 79470, 'popular' => false, 'tz' => 'America/Halifax'],
                            ['name' => 'Saint John', 'lat' => 45.2733, 'lng' => -66.0633, 'pop' => 69895, 'popular' => false, 'tz' => 'America/Halifax'],
                            ['name' => 'Fredericton', 'lat' => 45.9636, 'lng' => -66.6431, 'pop' => 63116, 'popular' => false, 'tz' => 'America/Halifax'],
                        ],
                    ],
                    [
                        'code' => 'NL',
                        'name' => 'Newfoundland and Labrador',
                        'slug' => 'newfoundland-and-labrador',
                        'level' => 'province',
                        'lat' => 53.1355,
                        'lng' => -57.6604,
                        'cities' => [
                            ['name' => "St. John's", 'slug' => 'st-johns', 'lat' => 47.5615, 'lng' => -52.7126, 'pop' => 110525, 'popular' => false, 'tz' => 'America/St_Johns'],
                        ],
                    ],
                    [
                        'code' => 'PE',
                        'name' => 'Prince Edward Island',
                        'slug' => 'prince-edward-island',
                        'level' => 'province',
                        'lat' => 46.5107,
                        'lng' => -63.4168,
                        'cities' => [
                            ['name' => 'Charlottetown', 'lat' => 46.2382, 'lng' => -63.1311, 'pop' => 38809, 'popular' => false, 'tz' => 'America/Halifax'],
                        ],
                    ],
                    [
                        'code' => 'YT',
                        'name' => 'Yukon',
                        'slug' => 'yukon',
                        'level' => 'territory',
                        'is_territory' => true,
                        'lat' => 64.2823,
                        'lng' => -135.0000,
                        'cities' => [
                            ['name' => 'Whitehorse', 'lat' => 60.7212, 'lng' => -135.0568, 'pop' => 28201, 'popular' => false, 'tz' => 'America/Whitehorse'],
                        ],
                    ],
                    [
                        'code' => 'NT',
                        'name' => 'Northwest Territories',
                        'slug' => 'northwest-territories',
                        'level' => 'territory',
                        'is_territory' => true,
                        'lat' => 64.8255,
                        'lng' => -124.8457,
                        'cities' => [
                            ['name' => 'Yellowknife', 'lat' => 62.4540, 'lng' => -114.3718, 'pop' => 20340, 'popular' => false, 'tz' => 'America/Yellowknife'],
                        ],
                    ],
                    [
                        'code' => 'NU',
                        'name' => 'Nunavut',
                        'slug' => 'nunavut',
                        'level' => 'territory',
                        'is_territory' => true,
                        'lat' => 70.2998,
                        'lng' => -83.1076,
                        'cities' => [
                            ['name' => 'Iqaluit', 'lat' => 63.7467, 'lng' => -68.5170, 'pop' => 7429, 'popular' => false, 'tz' => 'America/Iqaluit'],
                        ],
                    ],
                ],
            ],

            // 3. AUSTRALIA (AU)
            [
                'code' => 'AU',
                'slug' => 'au',
                'name' => 'Australia',
                'iso3' => 'AUS',
                'phone_code' => '+61',
                'currency_code' => 'AUD',
                'currency_symbol' => 'A$',
                'flag' => '🇦🇺',
                'timezone' => 'Australia/Sydney',
                'division_label' => 'State / Territory',
                'seo_title' => 'Australia Classifieds & Local Dating Marketplace — LocalDateHub Australia',
                'seo_description' => 'Browse verified personal classifieds and local dating across Australia including NSW, Victoria, Queensland, WA, SA, Tasmania, ACT, and NT.',
                'areas' => [
                    [
                        'code' => 'NSW',
                        'name' => 'New South Wales',
                        'slug' => 'new-south-wales',
                        'level' => 'state',
                        'lat' => -31.2532,
                        'lng' => 146.9211,
                        'cities' => [
                            ['name' => 'Sydney', 'lat' => -33.8688, 'lng' => 151.2093, 'pop' => 5312163, 'popular' => true],
                            ['name' => 'Newcastle', 'lat' => -32.9283, 'lng' => 151.7817, 'pop' => 322278, 'popular' => true],
                            ['name' => 'Wollongong', 'lat' => -34.4278, 'lng' => 150.8931, 'pop' => 302739, 'popular' => true],
                            ['name' => 'Central Coast', 'lat' => -33.4250, 'lng' => 151.3414, 'pop' => 346596, 'popular' => true],
                            ['name' => 'Parramatta', 'lat' => -33.8150, 'lng' => 151.0011, 'pop' => 256729, 'popular' => true],
                            ['name' => 'Penrith', 'lat' => -33.7507, 'lng' => 150.6877, 'pop' => 219149, 'popular' => false],
                            ['name' => 'Blacktown', 'lat' => -33.7688, 'lng' => 150.9051, 'pop' => 396776, 'popular' => false],
                            ['name' => 'Liverpool', 'lat' => -33.9200, 'lng' => 150.9231, 'pop' => 233446, 'popular' => false],
                            ['name' => 'Campbelltown', 'lat' => -34.0650, 'lng' => 150.8142, 'pop' => 176519, 'popular' => false],
                            ['name' => 'Bankstown', 'lat' => -33.9173, 'lng' => 151.0353, 'pop' => 185000, 'popular' => false],
                            ['name' => 'Chatswood', 'lat' => -33.7969, 'lng' => 151.1803, 'pop' => 26000, 'popular' => false],
                            ['name' => 'Bondi', 'lat' => -33.8915, 'lng' => 151.2767, 'pop' => 45000, 'popular' => false],
                            ['name' => 'Manly', 'lat' => -33.7971, 'lng' => 151.2880, 'pop' => 44000, 'popular' => false],
                            ['name' => 'Cronulla', 'lat' => -34.0574, 'lng' => 151.1522, 'pop' => 42000, 'popular' => false],
                            ['name' => 'Hornsby', 'lat' => -33.7047, 'lng' => 151.0987, 'pop' => 152000, 'popular' => false],
                            ['name' => 'Hurstville', 'lat' => -33.9667, 'lng' => 151.1000, 'pop' => 135000, 'popular' => false],
                            ['name' => 'Sutherland', 'lat' => -34.0315, 'lng' => 151.0574, 'pop' => 230000, 'popular' => false],
                            ['name' => 'Gosford', 'lat' => -33.4267, 'lng' => 151.3417, 'pop' => 178000, 'popular' => false],
                            ['name' => 'Wyong', 'lat' => -33.2833, 'lng' => 151.4167, 'pop' => 92000, 'popular' => false],
                            ['name' => 'Maitland', 'lat' => -32.7333, 'lng' => 151.5500, 'pop' => 90226, 'popular' => false],
                            ['name' => 'Cessnock', 'lat' => -32.8333, 'lng' => 151.3500, 'pop' => 63632, 'popular' => false],
                            ['name' => 'Lake Macquarie', 'lat' => -33.0333, 'lng' => 151.5667, 'pop' => 213845, 'popular' => false],
                            ['name' => 'Port Stephens', 'lat' => -32.7167, 'lng' => 152.0500, 'pop' => 75276, 'popular' => false],
                            ['name' => 'Nelson Bay', 'lat' => -32.7150, 'lng' => 152.1511, 'pop' => 28000, 'popular' => false],
                            ['name' => 'Tweed Heads', 'lat' => -28.1833, 'lng' => 153.5500, 'pop' => 68000, 'popular' => false],
                            ['name' => 'Byron Bay', 'lat' => -28.6474, 'lng' => 153.6020, 'pop' => 36000, 'popular' => true],
                            ['name' => 'Ballina', 'lat' => -28.8667, 'lng' => 153.5667, 'pop' => 46000, 'popular' => false],
                            ['name' => 'Lismore', 'lat' => -28.8135, 'lng' => 153.2773, 'pop' => 44000, 'popular' => false],
                            ['name' => 'Grafton', 'lat' => -29.6909, 'lng' => 152.9333, 'pop' => 19000, 'popular' => false],
                            ['name' => 'Coffs Harbour', 'lat' => -30.2963, 'lng' => 153.1157, 'pop' => 78759, 'popular' => false],
                            ['name' => 'Port Macquarie', 'lat' => -31.4333, 'lng' => 152.9000, 'pop' => 86762, 'popular' => false],
                            ['name' => 'Taree', 'lat' => -31.9000, 'lng' => 152.4667, 'pop' => 26000, 'popular' => false],
                            ['name' => 'Forster', 'lat' => -32.1833, 'lng' => 152.5167, 'pop' => 21000, 'popular' => false],
                            ['name' => 'Tamworth', 'lat' => -31.0927, 'lng' => 150.9320, 'pop' => 63000, 'popular' => false],
                            ['name' => 'Armidale', 'lat' => -30.5129, 'lng' => 151.6694, 'pop' => 29000, 'popular' => false],
                            ['name' => 'Inverell', 'lat' => -29.7725, 'lng' => 151.1139, 'pop' => 17000, 'popular' => false],
                            ['name' => 'Moree', 'lat' => -29.4658, 'lng' => 149.8339, 'pop' => 13000, 'popular' => false],
                            ['name' => 'Dubbo', 'lat' => -32.2430, 'lng' => 148.6048, 'pop' => 54922, 'popular' => false],
                            ['name' => 'Orange', 'lat' => -33.2833, 'lng' => 149.1000, 'pop' => 43512, 'popular' => false],
                            ['name' => 'Bathurst', 'lat' => -33.4193, 'lng' => 149.5775, 'pop' => 43567, 'popular' => false],
                            ['name' => 'Lithgow', 'lat' => -33.4833, 'lng' => 150.1500, 'pop' => 21000, 'popular' => false],
                            ['name' => 'Mudgee', 'lat' => -32.5944, 'lng' => 149.5886, 'pop' => 25000, 'popular' => false],
                            ['name' => 'Parkes', 'lat' => -33.1369, 'lng' => 148.1764, 'pop' => 14000, 'popular' => false],
                            ['name' => 'Broken Hill', 'lat' => -31.9539, 'lng' => 141.4539, 'pop' => 17588, 'popular' => false],
                            ['name' => 'Wagga Wagga', 'lat' => -35.1082, 'lng' => 147.3598, 'pop' => 67609, 'popular' => false],
                            ['name' => 'Albury', 'lat' => -36.0737, 'lng' => 146.9135, 'pop' => 56093, 'popular' => false],
                            ['name' => 'Griffith', 'lat' => -34.2897, 'lng' => 146.0508, 'pop' => 27000, 'popular' => false],
                            ['name' => 'Goulburn', 'lat' => -34.7515, 'lng' => 149.7209, 'pop' => 32000, 'popular' => false],
                            ['name' => 'Queanbeyan', 'lat' => -35.3533, 'lng' => 149.2342, 'pop' => 63000, 'popular' => false],
                            ['name' => 'Nowra', 'lat' => -34.8742, 'lng' => 150.6028, 'pop' => 39000, 'popular' => false],
                            ['name' => 'Ulladulla', 'lat' => -35.3586, 'lng' => 150.4731, 'pop' => 17000, 'popular' => false],
                            ['name' => 'Batemans Bay', 'lat' => -35.7081, 'lng' => 150.1744, 'pop' => 17000, 'popular' => false],
                            ['name' => 'Bega', 'lat' => -36.6739, 'lng' => 149.8419, 'pop' => 35000, 'popular' => false],
                            ['name' => 'Cooma', 'lat' => -36.2350, 'lng' => 149.1258, 'pop' => 10000, 'popular' => false],
                            ['name' => 'Bowral', 'lat' => -34.4792, 'lng' => 150.4181, 'pop' => 52000, 'popular' => false],
                            ['name' => 'Katoomba', 'lat' => -33.7123, 'lng' => 150.3119, 'pop' => 78000, 'popular' => false],
                        ],
                    ],
                    [
                        'code' => 'VIC',
                        'name' => 'Victoria',
                        'slug' => 'victoria',
                        'level' => 'state',
                        'lat' => -37.4713,
                        'lng' => 144.7852,
                        'cities' => [
                            ['name' => 'Melbourne', 'lat' => -37.8136, 'lng' => 144.9631, 'pop' => 5078193, 'popular' => true, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Geelong', 'lat' => -38.1499, 'lng' => 144.3617, 'pop' => 268984, 'popular' => true, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Ballarat', 'lat' => -37.5622, 'lng' => 143.8503, 'pop' => 111702, 'popular' => true, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Bendigo', 'lat' => -36.7570, 'lng' => 144.2794, 'pop' => 102499, 'popular' => true, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Frankston', 'lat' => -38.1414, 'lng' => 145.1225, 'pop' => 142000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Dandenong', 'lat' => -37.9875, 'lng' => 145.2147, 'pop' => 168000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'St Kilda', 'lat' => -37.8676, 'lng' => 144.9810, 'pop' => 45000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'South Yarra', 'lat' => -37.8389, 'lng' => 144.9922, 'pop' => 38000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Richmond', 'lat' => -37.8183, 'lng' => 144.9986, 'pop' => 35000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Fitzroy', 'lat' => -37.7997, 'lng' => 144.9786, 'pop' => 28000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Brunswick', 'lat' => -37.7667, 'lng' => 144.9600, 'pop' => 42000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Footscray', 'lat' => -37.7992, 'lng' => 144.9006, 'pop' => 92000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Box Hill', 'lat' => -37.8190, 'lng' => 145.1223, 'pop' => 178000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Ringwood', 'lat' => -37.8150, 'lng' => 145.2289, 'pop' => 118000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Werribee', 'lat' => -37.9000, 'lng' => 144.6611, 'pop' => 292000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Point Cook', 'lat' => -37.9167, 'lng' => 144.7500, 'pop' => 68000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Melton', 'lat' => -37.6833, 'lng' => 144.5833, 'pop' => 178000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Sunbury', 'lat' => -37.5811, 'lng' => 144.7139, 'pop' => 44000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Craigieburn', 'lat' => -37.6000, 'lng' => 144.9333, 'pop' => 240000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Epping', 'lat' => -37.6517, 'lng' => 145.0197, 'pop' => 230000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Cranbourne', 'lat' => -38.0996, 'lng' => 145.2834, 'pop' => 365000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Pakenham', 'lat' => -38.0712, 'lng' => 145.4851, 'pop' => 118000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Berwick', 'lat' => -38.0314, 'lng' => 145.3447, 'pop' => 58000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Mornington', 'lat' => -38.2194, 'lng' => 145.0392, 'pop' => 169000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Rosebud', 'lat' => -38.3564, 'lng' => 144.9044, 'pop' => 25000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Warrnambool', 'lat' => -38.3818, 'lng' => 142.4880, 'pop' => 35519, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Mildura', 'lat' => -34.1855, 'lng' => 142.1625, 'pop' => 56969, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Shepparton', 'lat' => -36.3833, 'lng' => 145.4000, 'pop' => 68409, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Wodonga', 'lat' => -36.1214, 'lng' => 146.8881, 'pop' => 43253, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Wangaratta', 'lat' => -36.3584, 'lng' => 146.3125, 'pop' => 29808, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Traralgon', 'lat' => -38.1958, 'lng' => 146.5403, 'pop' => 29000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Morwell', 'lat' => -38.2333, 'lng' => 146.4000, 'pop' => 15000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Sale', 'lat' => -38.1065, 'lng' => 147.0680, 'pop' => 15000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Bairnsdale', 'lat' => -37.8333, 'lng' => 147.6167, 'pop' => 16000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Warragul', 'lat' => -38.1589, 'lng' => 145.9311, 'pop' => 23000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Horsham', 'lat' => -36.7118, 'lng' => 142.1998, 'pop' => 20429, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Torquay', 'lat' => -38.3306, 'lng' => 144.3264, 'pop' => 24000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Echuca', 'lat' => -36.1400, 'lng' => 144.7500, 'pop' => 15000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Swan Hill', 'lat' => -35.3378, 'lng' => 143.5544, 'pop' => 11000, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Castlemaine', 'lat' => -37.0636, 'lng' => 144.2172, 'pop' => 11500, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                        ],
                    ],
                    [
                        'code' => 'QLD',
                        'name' => 'Queensland',
                        'slug' => 'queensland',
                        'level' => 'state',
                        'lat' => -20.9176,
                        'lng' => 142.7028,
                        'cities' => [
                            ['name' => 'Brisbane', 'lat' => -27.4705, 'lng' => 153.0260, 'pop' => 2568927, 'popular' => true, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Gold Coast', 'slug' => 'gold-coast', 'lat' => -28.0167, 'lng' => 153.4000, 'pop' => 699266, 'popular' => true, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Sunshine Coast', 'lat' => -26.6500, 'lng' => 153.0667, 'pop' => 348389, 'popular' => true, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Townsville', 'lat' => -19.2590, 'lng' => 146.8169, 'pop' => 180820, 'popular' => true, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Cairns', 'lat' => -16.9186, 'lng' => 145.7781, 'pop' => 153952, 'popular' => true, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Toowoomba', 'lat' => -27.5598, 'lng' => 151.9507, 'pop' => 173204, 'popular' => true, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Mackay', 'lat' => -21.1411, 'lng' => 149.1860, 'pop' => 121691, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Rockhampton', 'lat' => -23.3791, 'lng' => 150.5100, 'pop' => 82884, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Bundaberg', 'lat' => -24.8661, 'lng' => 152.3489, 'pop' => 99215, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Hervey Bay', 'lat' => -25.2986, 'lng' => 152.8535, 'pop' => 57722, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Gladstone', 'lat' => -23.8489, 'lng' => 151.2575, 'pop' => 63515, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Maryborough', 'lat' => -25.5407, 'lng' => 152.7049, 'pop' => 28000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Gympie', 'lat' => -26.1900, 'lng' => 152.6650, 'pop' => 53242, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Noosa', 'lat' => -26.3886, 'lng' => 153.0908, 'pop' => 56587, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Maroochydore', 'lat' => -26.6564, 'lng' => 153.0894, 'pop' => 60000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Caloundra', 'lat' => -26.8044, 'lng' => 153.1253, 'pop' => 52000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Caboolture', 'lat' => -27.0847, 'lng' => 152.9511, 'pop' => 76000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Redcliffe', 'lat' => -27.2267, 'lng' => 153.1128, 'pop' => 62000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Logan', 'lat' => -27.6389, 'lng' => 153.1094, 'pop' => 345098, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Ipswich', 'lat' => -27.6146, 'lng' => 152.7608, 'pop' => 229208, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Southport', 'lat' => -27.9672, 'lng' => 153.4000, 'pop' => 36000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Surfers Paradise', 'lat' => -28.0023, 'lng' => 153.4145, 'pop' => 26000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Broadbeach', 'lat' => -28.0272, 'lng' => 153.4311, 'pop' => 18000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Burleigh Heads', 'lat' => -28.0989, 'lng' => 153.4508, 'pop' => 24000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Coolangatta', 'lat' => -28.1681, 'lng' => 153.5364, 'pop' => 15000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Robina', 'lat' => -28.0772, 'lng' => 153.3853, 'pop' => 29000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Mount Isa', 'lat' => -20.7256, 'lng' => 139.4927, 'pop' => 18727, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Emerald', 'lat' => -23.5275, 'lng' => 148.1647, 'pop' => 14000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Yeppoon', 'lat' => -23.1333, 'lng' => 150.7333, 'pop' => 21000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Airlie Beach', 'lat' => -20.2678, 'lng' => 148.7169, 'pop' => 15000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Bowen', 'lat' => -20.0156, 'lng' => 148.2481, 'pop' => 11000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Innisfail', 'lat' => -17.5236, 'lng' => 146.0292, 'pop' => 10000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Port Douglas', 'lat' => -16.4838, 'lng' => 145.4673, 'pop' => 8500, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Atherton', 'lat' => -17.2686, 'lng' => 145.4781, 'pop' => 12000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Warwick', 'lat' => -28.2153, 'lng' => 152.0350, 'pop' => 15500, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Dalby', 'lat' => -27.1817, 'lng' => 151.2625, 'pop' => 12700, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Kingaroy', 'lat' => -26.5392, 'lng' => 151.8400, 'pop' => 10500, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Roma', 'lat' => -26.5733, 'lng' => 148.7869, 'pop' => 7000, 'popular' => false, 'tz' => 'Australia/Brisbane'],
                        ],
                    ],
                    [
                        'code' => 'WA',
                        'name' => 'Western Australia',
                        'slug' => 'western-australia',
                        'level' => 'state',
                        'lat' => -27.6728,
                        'lng' => 121.6283,
                        'cities' => [
                            ['name' => 'Perth', 'lat' => -31.9505, 'lng' => 115.8605, 'pop' => 2125114, 'popular' => true, 'tz' => 'Australia/Perth'],
                            ['name' => 'Fremantle', 'lat' => -32.0569, 'lng' => 115.7439, 'pop' => 31930, 'popular' => true, 'tz' => 'Australia/Perth'],
                            ['name' => 'Mandurah', 'lat' => -32.5269, 'lng' => 115.7217, 'pop' => 90306, 'popular' => true, 'tz' => 'Australia/Perth'],
                            ['name' => 'Bunbury', 'lat' => -33.3256, 'lng' => 115.6396, 'pop' => 75319, 'popular' => true, 'tz' => 'Australia/Perth'],
                            ['name' => 'Rockingham', 'lat' => -32.2767, 'lng' => 115.7297, 'pop' => 135678, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Joondalup', 'lat' => -31.7450, 'lng' => 115.7661, 'pop' => 160003, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Armadale', 'lat' => -32.1533, 'lng' => 116.0150, 'pop' => 94000, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Midland', 'lat' => -31.8897, 'lng' => 116.0097, 'pop' => 152000, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Scarborough', 'lat' => -31.8944, 'lng' => 115.7586, 'pop' => 22000, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Subiaco', 'lat' => -31.9489, 'lng' => 115.8267, 'pop' => 18000, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Cannington', 'lat' => -32.0167, 'lng' => 115.9333, 'pop' => 96000, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Wanneroo', 'lat' => -31.7475, 'lng' => 115.8028, 'pop' => 209000, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Busselton', 'lat' => -33.6555, 'lng' => 115.3500, 'pop' => 40640, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Margaret River', 'lat' => -33.9536, 'lng' => 115.0739, 'pop' => 16000, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Albany', 'lat' => -35.0269, 'lng' => 117.8837, 'pop' => 38763, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Geraldton', 'lat' => -28.7744, 'lng' => 114.6089, 'pop' => 39489, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Kalgoorlie', 'lat' => -30.7490, 'lng' => 121.4660, 'pop' => 29306, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Broome', 'lat' => -17.9614, 'lng' => 122.2359, 'pop' => 16959, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Karratha', 'lat' => -20.7364, 'lng' => 116.8463, 'pop' => 22199, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Port Hedland', 'lat' => -20.3106, 'lng' => 118.6061, 'pop' => 15698, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Esperance', 'lat' => -33.8614, 'lng' => 121.8914, 'pop' => 13883, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Northam', 'lat' => -31.6531, 'lng' => 116.6661, 'pop' => 11500, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Carnarvon', 'lat' => -24.8839, 'lng' => 113.6611, 'pop' => 5500, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Exmouth', 'lat' => -21.9314, 'lng' => 114.1281, 'pop' => 3000, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Kununurra', 'lat' => -15.7783, 'lng' => 128.7439, 'pop' => 5500, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Collie', 'lat' => -33.3631, 'lng' => 116.1558, 'pop' => 8800, 'popular' => false, 'tz' => 'Australia/Perth'],
                        ],
                    ],
                    [
                        'code' => 'SA',
                        'name' => 'South Australia',
                        'slug' => 'south-australia',
                        'level' => 'state',
                        'lat' => -30.0002,
                        'lng' => 136.2092,
                        'cities' => [
                            ['name' => 'Adelaide', 'lat' => -34.9285, 'lng' => 138.6007, 'pop' => 1387290, 'popular' => true, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Mount Gambier', 'lat' => -37.8284, 'lng' => 140.7804, 'pop' => 27433, 'popular' => true, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Glenelg', 'lat' => -34.9806, 'lng' => 138.5150, 'pop' => 38000, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Norwood', 'lat' => -34.9206, 'lng' => 138.6328, 'pop' => 37000, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Port Adelaide', 'lat' => -34.8475, 'lng' => 138.5081, 'pop' => 128000, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Salisbury', 'lat' => -34.7639, 'lng' => 138.6422, 'pop' => 145000, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Elizabeth', 'lat' => -34.7167, 'lng' => 138.6667, 'pop' => 98000, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Noarlunga', 'lat' => -35.1419, 'lng' => 138.4969, 'pop' => 174000, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Mount Barker', 'lat' => -35.0689, 'lng' => 138.8594, 'pop' => 39000, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Gawler', 'lat' => -34.5981, 'lng' => 138.7450, 'pop' => 25000, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Victor Harbor', 'lat' => -35.5525, 'lng' => 138.6214, 'pop' => 16000, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Whyalla', 'lat' => -33.0333, 'lng' => 137.5667, 'pop' => 21868, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Murray Bridge', 'lat' => -35.1197, 'lng' => 139.2764, 'pop' => 22041, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Port Lincoln', 'lat' => -34.7240, 'lng' => 135.8588, 'pop' => 14404, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Port Pirie', 'lat' => -33.1858, 'lng' => 138.0169, 'pop' => 17281, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Port Augusta', 'lat' => -32.4936, 'lng' => 137.7658, 'pop' => 14102, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Renmark', 'lat' => -34.1753, 'lng' => 140.7447, 'pop' => 10000, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Barossa Valley', 'lat' => -34.5333, 'lng' => 138.9500, 'pop' => 25000, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Clare', 'lat' => -33.8342, 'lng' => 138.6108, 'pop' => 9000, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Naracoorte', 'lat' => -36.9578, 'lng' => 140.7428, 'pop' => 8800, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                            ['name' => 'Coober Pedy', 'lat' => -29.0135, 'lng' => 134.7544, 'pop' => 1800, 'popular' => false, 'tz' => 'Australia/Adelaide'],
                        ],
                    ],
                    [
                        'code' => 'TAS',
                        'name' => 'Tasmania',
                        'slug' => 'tasmania',
                        'level' => 'state',
                        'lat' => -41.4545,
                        'lng' => 145.9707,
                        'cities' => [
                            ['name' => 'Hobart', 'lat' => -42.8821, 'lng' => 147.3272, 'pop' => 247086, 'popular' => true, 'tz' => 'Australia/Hobart'],
                            ['name' => 'Launceston', 'lat' => -41.4332, 'lng' => 147.1441, 'pop' => 88884, 'popular' => true, 'tz' => 'Australia/Hobart'],
                            ['name' => 'Devonport', 'lat' => -41.1789, 'lng' => 146.3514, 'pop' => 26150, 'popular' => false, 'tz' => 'Australia/Hobart'],
                            ['name' => 'Burnie', 'lat' => -41.0525, 'lng' => 145.9069, 'pop' => 19918, 'popular' => false, 'tz' => 'Australia/Hobart'],
                            ['name' => 'Glenorchy', 'lat' => -42.8333, 'lng' => 147.2750, 'pop' => 50411, 'popular' => false, 'tz' => 'Australia/Hobart'],
                            ['name' => 'Clarence', 'lat' => -42.8667, 'lng' => 147.4000, 'pop' => 61531, 'popular' => false, 'tz' => 'Australia/Hobart'],
                            ['name' => 'Kingston', 'lat' => -42.9769, 'lng' => 147.3083, 'pop' => 40086, 'popular' => false, 'tz' => 'Australia/Hobart'],
                            ['name' => 'Ulverstone', 'lat' => -41.1583, 'lng' => 146.1750, 'pop' => 15000, 'popular' => false, 'tz' => 'Australia/Hobart'],
                            ['name' => 'Sorell', 'lat' => -42.7833, 'lng' => 147.5667, 'pop' => 16700, 'popular' => false, 'tz' => 'Australia/Hobart'],
                            ['name' => 'New Norfolk', 'lat' => -42.7828, 'lng' => 147.0594, 'pop' => 11000, 'popular' => false, 'tz' => 'Australia/Hobart'],
                            ['name' => 'Wynyard', 'lat' => -40.9889, 'lng' => 145.7264, 'pop' => 14000, 'popular' => false, 'tz' => 'Australia/Hobart'],
                            ['name' => 'Smithton', 'lat' => -40.8442, 'lng' => 145.1200, 'pop' => 8200, 'popular' => false, 'tz' => 'Australia/Hobart'],
                            ['name' => 'George Town', 'lat' => -41.1067, 'lng' => 146.8247, 'pop' => 7000, 'popular' => false, 'tz' => 'Australia/Hobart'],
                            ['name' => 'St Helens', 'lat' => -41.3219, 'lng' => 148.2481, 'pop' => 6800, 'popular' => false, 'tz' => 'Australia/Hobart'],
                            ['name' => 'Queenstown', 'lat' => -42.0806, 'lng' => 145.5556, 'pop' => 4200, 'popular' => false, 'tz' => 'Australia/Hobart'],
                            ['name' => 'Huonville', 'lat' => -43.0292, 'lng' => 147.0469, 'pop' => 18000, 'popular' => false, 'tz' => 'Australia/Hobart'],
                        ],
                    ],
                    [
                        'code' => 'ACT',
                        'name' => 'Australian Capital Territory',
                        'slug' => 'australian-capital-territory',
                        'level' => 'territory',
                        'is_territory' => true,
                        'lat' => -35.4735,
                        'lng' => 149.0124,
                        'cities' => [
                            ['name' => 'Canberra', 'lat' => -35.2809, 'lng' => 149.1300, 'pop' => 453558, 'popular' => true],
                            ['name' => 'Belconnen', 'lat' => -35.2389, 'lng' => 149.0661, 'pop' => 106000, 'popular' => false],
                            ['name' => 'Gungahlin', 'lat' => -35.1831, 'lng' => 149.1331, 'pop' => 88000, 'popular' => false],
                            ['name' => 'Tuggeranong', 'lat' => -35.4244, 'lng' => 149.0888, 'pop' => 89000, 'popular' => false],
                            ['name' => 'Woden', 'lat' => -35.3453, 'lng' => 149.0853, 'pop' => 39000, 'popular' => false],
                            ['name' => 'Civic', 'lat' => -35.2819, 'lng' => 149.1286, 'pop' => 25000, 'popular' => false],
                            ['name' => 'Braddon', 'lat' => -35.2722, 'lng' => 149.1328, 'pop' => 12000, 'popular' => false],
                            ['name' => 'Kingston', 'lat' => -35.3158, 'lng' => 149.1444, 'pop' => 11000, 'popular' => false],
                            ['name' => 'Manuka', 'lat' => -35.3203, 'lng' => 149.1333, 'pop' => 9500, 'popular' => false],
                            ['name' => 'Dickson', 'lat' => -35.2506, 'lng' => 149.1394, 'pop' => 10000, 'popular' => false],
                            ['name' => 'Weston Creek', 'lat' => -35.3414, 'lng' => 149.0519, 'pop' => 24000, 'popular' => false],
                        ],
                    ],
                    [
                        'code' => 'NT',
                        'name' => 'Northern Territory',
                        'slug' => 'northern-territory',
                        'level' => 'territory',
                        'is_territory' => true,
                        'lat' => -19.4914,
                        'lng' => 132.5510,
                        'cities' => [
                            ['name' => 'Darwin', 'lat' => -12.4634, 'lng' => 130.8456, 'pop' => 147255, 'popular' => true, 'tz' => 'Australia/Darwin'],
                            ['name' => 'Alice Springs', 'lat' => -23.6980, 'lng' => 133.8807, 'pop' => 26534, 'popular' => true, 'tz' => 'Australia/Darwin'],
                            ['name' => 'Palmerston', 'lat' => -12.4860, 'lng' => 130.9833, 'pop' => 40000, 'popular' => false, 'tz' => 'Australia/Darwin'],
                            ['name' => 'Katherine', 'lat' => -14.4652, 'lng' => 132.2635, 'pop' => 10656, 'popular' => false, 'tz' => 'Australia/Darwin'],
                            ['name' => 'Nhulunbuy', 'lat' => -12.1825, 'lng' => 136.7819, 'pop' => 3500, 'popular' => false, 'tz' => 'Australia/Darwin'],
                            ['name' => 'Tennant Creek', 'lat' => -19.6497, 'lng' => 134.1914, 'pop' => 3100, 'popular' => false, 'tz' => 'Australia/Darwin'],
                            ['name' => 'Casuarina', 'lat' => -12.3742, 'lng' => 130.8825, 'pop' => 15000, 'popular' => false, 'tz' => 'Australia/Darwin'],
                            ['name' => 'Nightcliff', 'lat' => -12.3822, 'lng' => 130.8517, 'pop' => 12000, 'popular' => false, 'tz' => 'Australia/Darwin'],
                            ['name' => 'Humpty Doo', 'lat' => -12.5736, 'lng' => 131.1044, 'pop' => 9000, 'popular' => false, 'tz' => 'Australia/Darwin'],
                            ['name' => 'Yulara', 'lat' => -25.2406, 'lng' => 130.9889, 'pop' => 1500, 'popular' => false, 'tz' => 'Australia/Darwin'],
                        ],
                    ],
                ],
            ],

            // 4. IRELAND (IE — Republic of Ireland ONLY; 26 Counties)
            [
                'code' => 'IE',
                'slug' => 'ie',
                'name' => 'Ireland',
                'iso3' => 'IRL',
                'phone_code' => '+353',
                'currency_code' => 'EUR',
                'currency_symbol' => '€',
                'flag' => '🇮🇪',
                'timezone' => 'Europe/Dublin',
                'division_label' => 'County',
                'seo_title' => 'Ireland Classifieds & Local Dating Marketplace — LocalDateHub Ireland',
                'seo_description' => 'Connect with local singles and browse verified personal classifieds across all 26 counties of the Republic of Ireland including Dublin, Cork, Galway, and Limerick.',
                'areas' => [
                    ['code' => 'DUB', 'name' => 'County Dublin', 'slug' => 'county-dublin', 'level' => 'county', 'lat' => 53.3498, 'lng' => -6.2603, 'cities' => [
                        ['name' => 'Dublin', 'lat' => 53.3498, 'lng' => -6.2603, 'pop' => 592713, 'popular' => true],
                        ['name' => 'Swords', 'lat' => 53.4597, 'lng' => -6.2181, 'pop' => 40776, 'popular' => false],
                        ['name' => 'Dún Laoghaire', 'slug' => 'dun-laoghaire', 'lat' => 53.2944, 'lng' => -6.1339, 'pop' => 26525, 'popular' => false],
                    ]],
                    ['code' => 'COR', 'name' => 'County Cork', 'slug' => 'county-cork', 'level' => 'county', 'lat' => 51.8985, 'lng' => -8.4756, 'cities' => [
                        ['name' => 'Cork', 'lat' => 51.8985, 'lng' => -8.4756, 'pop' => 224004, 'popular' => true],
                        ['name' => 'Cobh', 'lat' => 51.8503, 'lng' => -8.2943, 'pop' => 14148, 'popular' => false],
                    ]],
                    ['code' => 'GAL', 'name' => 'County Galway', 'slug' => 'county-galway', 'level' => 'county', 'lat' => 53.2707, 'lng' => -9.0568, 'cities' => [
                        ['name' => 'Galway', 'lat' => 53.2707, 'lng' => -9.0568, 'pop' => 83456, 'popular' => true],
                        ['name' => 'Tuam', 'lat' => 53.5144, 'lng' => -8.8562, 'pop' => 9647, 'popular' => false],
                    ]],
                    ['code' => 'LIM', 'name' => 'County Limerick', 'slug' => 'county-limerick', 'level' => 'county', 'lat' => 52.6638, 'lng' => -8.6267, 'cities' => [
                        ['name' => 'Limerick', 'lat' => 52.6638, 'lng' => -8.6267, 'pop' => 102287, 'popular' => true],
                    ]],
                    ['code' => 'WAT', 'name' => 'County Waterford', 'slug' => 'county-waterford', 'level' => 'county', 'lat' => 52.2593, 'lng' => -7.1101, 'cities' => [
                        ['name' => 'Waterford', 'lat' => 52.2593, 'lng' => -7.1101, 'pop' => 60079, 'popular' => true],
                    ]],
                    ['code' => 'KIL', 'name' => 'County Kilkenny', 'slug' => 'county-kilkenny', 'level' => 'county', 'lat' => 52.6541, 'lng' => -7.2448, 'cities' => [
                        ['name' => 'Kilkenny', 'lat' => 52.6541, 'lng' => -7.2448, 'pop' => 27184, 'popular' => true],
                    ]],
                    ['code' => 'WEX', 'name' => 'County Wexford', 'slug' => 'county-wexford', 'level' => 'county', 'lat' => 52.3369, 'lng' => -6.4633, 'cities' => [
                        ['name' => 'Wexford', 'lat' => 52.3369, 'lng' => -6.4633, 'pop' => 21524, 'popular' => true],
                    ]],
                    ['code' => 'SLI', 'name' => 'County Sligo', 'slug' => 'county-sligo', 'level' => 'county', 'lat' => 54.2766, 'lng' => -8.4761, 'cities' => [
                        ['name' => 'Sligo', 'lat' => 54.2766, 'lng' => -8.4761, 'pop' => 20608, 'popular' => true],
                    ]],
                    ['code' => 'WEM', 'name' => 'County Westmeath', 'slug' => 'county-westmeath', 'level' => 'county', 'lat' => 53.5259, 'lng' => -7.3389, 'cities' => [
                        ['name' => 'Athlone', 'lat' => 53.4239, 'lng' => -7.9407, 'pop' => 22869, 'popular' => true],
                        ['name' => 'Mullingar', 'lat' => 53.5259, 'lng' => -7.3389, 'pop' => 22667, 'popular' => false],
                    ]],
                    ['code' => 'LOU', 'name' => 'County Louth', 'slug' => 'county-louth', 'level' => 'county', 'lat' => 53.9508, 'lng' => -6.5406, 'cities' => [
                        ['name' => 'Drogheda', 'lat' => 53.7179, 'lng' => -6.3561, 'pop' => 44135, 'popular' => true],
                        ['name' => 'Dundalk', 'lat' => 54.0008, 'lng' => -6.4058, 'pop' => 43112, 'popular' => true],
                    ]],
                    ['code' => 'KER', 'name' => 'County Kerry', 'slug' => 'county-kerry', 'level' => 'county', 'lat' => 52.1545, 'lng' => -9.5669, 'cities' => [
                        ['name' => 'Tralee', 'lat' => 52.2713, 'lng' => -9.6999, 'pop' => 26079, 'popular' => true],
                        ['name' => 'Killarney', 'lat' => 52.0599, 'lng' => -9.5044, 'pop' => 14412, 'popular' => true],
                    ]],
                    ['code' => 'CLA', 'name' => 'County Clare', 'slug' => 'county-clare', 'level' => 'county', 'lat' => 52.8436, 'lng' => -8.9864, 'cities' => [
                        ['name' => 'Ennis', 'lat' => 52.8436, 'lng' => -8.9864, 'pop' => 27923, 'popular' => true],
                    ]],
                    ['code' => 'KID', 'name' => 'County Kildare', 'slug' => 'county-kildare', 'level' => 'county', 'lat' => 53.1589, 'lng' => -6.9096, 'cities' => [
                        ['name' => 'Naas', 'lat' => 53.2158, 'lng' => -6.6669, 'pop' => 26180, 'popular' => false],
                    ]],
                    ['code' => 'WIC', 'name' => 'County Wicklow', 'slug' => 'county-wicklow', 'level' => 'county', 'lat' => 52.9808, 'lng' => -6.0446, 'cities' => [
                        ['name' => 'Bray', 'lat' => 53.2028, 'lng' => -6.0983, 'pop' => 33512, 'popular' => false],
                    ]],
                    ['code' => 'MEA', 'name' => 'County Meath', 'slug' => 'county-meath', 'level' => 'county', 'lat' => 53.6528, 'lng' => -6.6814, 'cities' => [
                        ['name' => 'Navan', 'lat' => 53.6528, 'lng' => -6.6814, 'pop' => 33886, 'popular' => false],
                    ]],
                    ['code' => 'TIP', 'name' => 'County Tipperary', 'slug' => 'county-tipperary', 'level' => 'county', 'lat' => 52.4738, 'lng' => -8.1619, 'cities' => [
                        ['name' => 'Clonmel', 'lat' => 52.3550, 'lng' => -7.7039, 'pop' => 18369, 'popular' => false],
                    ]],
                    ['code' => 'DON', 'name' => 'County Donegal', 'slug' => 'county-donegal', 'level' => 'county', 'lat' => 54.9548, 'lng' => -7.7342, 'cities' => [
                        ['name' => 'Letterkenny', 'lat' => 54.9548, 'lng' => -7.7342, 'pop' => 22589, 'popular' => false],
                    ]],
                    ['code' => 'MAY', 'name' => 'County Mayo', 'slug' => 'county-mayo', 'level' => 'county', 'lat' => 53.8551, 'lng' => -9.2988, 'cities' => [
                        ['name' => 'Castlebar', 'lat' => 53.8551, 'lng' => -9.2988, 'pop' => 13054, 'popular' => false],
                    ]],
                    ['code' => 'ROS', 'name' => 'County Roscommon', 'slug' => 'county-roscommon', 'level' => 'county', 'lat' => 53.6276, 'lng' => -8.1891, 'cities' => [
                        ['name' => 'Roscommon', 'lat' => 53.6276, 'lng' => -8.1891, 'pop' => 6555, 'popular' => false],
                    ]],
                    ['code' => 'LAO', 'name' => 'County Laois', 'slug' => 'county-laois', 'level' => 'county', 'lat' => 53.0344, 'lng' => -7.2998, 'cities' => [
                        ['name' => 'Portlaoise', 'lat' => 53.0344, 'lng' => -7.2998, 'pop' => 23494, 'popular' => false],
                    ]],
                    ['code' => 'OFF', 'name' => 'County Offaly', 'slug' => 'county-offaly', 'level' => 'county', 'lat' => 53.2738, 'lng' => -7.4889, 'cities' => [
                        ['name' => 'Tullamore', 'lat' => 53.2738, 'lng' => -7.4889, 'pop' => 15598, 'popular' => false],
                    ]],
                    ['code' => 'CAR', 'name' => 'County Carlow', 'slug' => 'county-carlow', 'level' => 'county', 'lat' => 52.8365, 'lng' => -6.9341, 'cities' => [
                        ['name' => 'Carlow', 'lat' => 52.8365, 'lng' => -6.9341, 'pop' => 27351, 'popular' => false],
                    ]],
                    ['code' => 'CAV', 'name' => 'County Cavan', 'slug' => 'county-cavan', 'level' => 'county', 'lat' => 53.9908, 'lng' => -7.3606, 'cities' => [
                        ['name' => 'Cavan', 'lat' => 53.9908, 'lng' => -7.3606, 'pop' => 11741, 'popular' => false],
                    ]],
                    ['code' => 'MON', 'name' => 'County Monaghan', 'slug' => 'county-monaghan', 'level' => 'county', 'lat' => 54.2492, 'lng' => -6.9683, 'cities' => [
                        ['name' => 'Monaghan', 'lat' => 54.2492, 'lng' => -6.9683, 'pop' => 7894, 'popular' => false],
                    ]],
                    ['code' => 'LOG', 'name' => 'County Longford', 'slug' => 'county-longford', 'level' => 'county', 'lat' => 53.7276, 'lng' => -7.7933, 'cities' => [
                        ['name' => 'Longford', 'lat' => 53.7276, 'lng' => -7.7933, 'pop' => 10952, 'popular' => false],
                    ]],
                    ['code' => 'LEI', 'name' => 'County Leitrim', 'slug' => 'county-leitrim', 'level' => 'county', 'lat' => 53.9469, 'lng' => -8.0900, 'cities' => [
                        ['name' => 'Carrick-on-Shannon', 'lat' => 53.9469, 'lng' => -8.0900, 'pop' => 4743, 'popular' => false],
                    ]],
                ],
            ],

            // 5. NEW ZEALAND (NZ — All 16 Regions)
            [
                'code' => 'NZ',
                'slug' => 'nz',
                'name' => 'New Zealand',
                'iso3' => 'NZL',
                'phone_code' => '+64',
                'currency_code' => 'NZD',
                'currency_symbol' => 'NZ$',
                'flag' => '🇳🇿',
                'timezone' => 'Pacific/Auckland',
                'division_label' => 'Region',
                'seo_title' => 'New Zealand Classifieds & Local Dating Marketplace — LocalDateHub NZ',
                'seo_description' => 'Discover verified personal classifieds and local dating across all 16 New Zealand regions including Auckland, Wellington, Canterbury, Waikato, and Otago.',
                'areas' => [
                    ['code' => 'AUK', 'name' => 'Auckland', 'slug' => 'auckland', 'level' => 'region', 'lat' => -36.8485, 'lng' => 174.7633, 'cities' => [
                        ['name' => 'Auckland', 'lat' => -36.8485, 'lng' => 174.7633, 'pop' => 1657200, 'popular' => true],
                        ['name' => 'Manukau', 'lat' => -36.9928, 'lng' => 174.8799, 'pop' => 375600, 'popular' => false],
                    ]],
                    ['code' => 'WGN', 'name' => 'Wellington', 'slug' => 'wellington', 'level' => 'region', 'lat' => -41.2865, 'lng' => 174.7762, 'cities' => [
                        ['name' => 'Wellington', 'lat' => -41.2865, 'lng' => 174.7762, 'pop' => 215900, 'popular' => true],
                        ['name' => 'Lower Hutt', 'lat' => -41.2092, 'lng' => 174.9081, 'pop' => 111800, 'popular' => false],
                    ]],
                    ['code' => 'CAN', 'name' => 'Canterbury', 'slug' => 'canterbury', 'level' => 'region', 'lat' => -43.5321, 'lng' => 172.6362, 'cities' => [
                        ['name' => 'Christchurch', 'lat' => -43.5321, 'lng' => 172.6362, 'pop' => 389300, 'popular' => true],
                        ['name' => 'Timaru', 'lat' => -44.3904, 'lng' => 171.2373, 'pop' => 28700, 'popular' => false],
                    ]],
                    ['code' => 'WKO', 'name' => 'Waikato', 'slug' => 'waikato', 'level' => 'region', 'lat' => -37.7870, 'lng' => 175.2793, 'cities' => [
                        ['name' => 'Hamilton', 'lat' => -37.7870, 'lng' => 175.2793, 'pop' => 179900, 'popular' => true],
                        ['name' => 'Taupō', 'slug' => 'taupo', 'lat' => -38.6857, 'lng' => 176.0702, 'pop' => 26100, 'popular' => false],
                    ]],
                    ['code' => 'BOP', 'name' => 'Bay of Plenty', 'slug' => 'bay-of-plenty', 'level' => 'region', 'lat' => -37.6878, 'lng' => 176.1651, 'cities' => [
                        ['name' => 'Tauranga', 'lat' => -37.6878, 'lng' => 176.1651, 'pop' => 158300, 'popular' => true],
                        ['name' => 'Rotorua', 'lat' => -38.1368, 'lng' => 176.2497, 'pop' => 58800, 'popular' => true],
                    ]],
                    ['code' => 'OTA', 'name' => 'Otago', 'slug' => 'otago', 'level' => 'region', 'lat' => -45.8788, 'lng' => 170.5028, 'cities' => [
                        ['name' => 'Dunedin', 'lat' => -45.8788, 'lng' => 170.5028, 'pop' => 130400, 'popular' => true],
                        ['name' => 'Queenstown', 'lat' => -45.0312, 'lng' => 168.6626, 'pop' => 28900, 'popular' => true],
                    ]],
                    ['code' => 'HKB', 'name' => "Hawke's Bay", 'slug' => 'hawkes-bay', 'level' => 'region', 'lat' => -39.4928, 'lng' => 176.9120, 'cities' => [
                        ['name' => 'Napier', 'lat' => -39.4928, 'lng' => 176.9120, 'pop' => 66800, 'popular' => true],
                        ['name' => 'Hastings', 'lat' => -39.6381, 'lng' => 176.8492, 'pop' => 50400, 'popular' => true],
                    ]],
                    ['code' => 'MWT', 'name' => 'Manawatū-Whanganui', 'slug' => 'manawatu-whanganui', 'level' => 'region', 'lat' => -40.3523, 'lng' => 175.6082, 'cities' => [
                        ['name' => 'Palmerston North', 'slug' => 'palmerston-north', 'lat' => -40.3523, 'lng' => 175.6082, 'pop' => 90400, 'popular' => true],
                        ['name' => 'Whanganui', 'lat' => -39.9301, 'lng' => 175.0479, 'pop' => 42800, 'popular' => false],
                    ]],
                    ['code' => 'TKI', 'name' => 'Taranaki', 'slug' => 'taranaki', 'level' => 'region', 'lat' => -39.0556, 'lng' => 174.0752, 'cities' => [
                        ['name' => 'New Plymouth', 'slug' => 'new-plymouth', 'lat' => -39.0556, 'lng' => 174.0752, 'pop' => 58500, 'popular' => true],
                    ]],
                    ['code' => 'NTL', 'name' => 'Northland', 'slug' => 'northland', 'level' => 'region', 'lat' => -35.7275, 'lng' => 174.3166, 'cities' => [
                        ['name' => 'Whangārei', 'slug' => 'whangarei', 'lat' => -35.7275, 'lng' => 174.3166, 'pop' => 54900, 'popular' => true],
                    ]],
                    ['code' => 'NSN', 'name' => 'Nelson', 'slug' => 'nelson', 'level' => 'region', 'lat' => -41.2706, 'lng' => 173.2840, 'cities' => [
                        ['name' => 'Nelson', 'lat' => -41.2706, 'lng' => 173.2840, 'pop' => 54500, 'popular' => true],
                    ]],
                    ['code' => 'STL', 'name' => 'Southland', 'slug' => 'southland', 'level' => 'region', 'lat' => -46.4132, 'lng' => 168.3538, 'cities' => [
                        ['name' => 'Invercargill', 'lat' => -46.4132, 'lng' => 168.3538, 'pop' => 50200, 'popular' => true],
                    ]],
                    ['code' => 'TAS', 'name' => 'Tasman', 'slug' => 'tasman', 'level' => 'region', 'lat' => -41.3388, 'lng' => 173.1856, 'cities' => [
                        ['name' => 'Richmond', 'lat' => -41.3388, 'lng' => 173.1856, 'pop' => 18850, 'popular' => false],
                    ]],
                    ['code' => 'MBH', 'name' => 'Marlborough', 'slug' => 'marlborough', 'level' => 'region', 'lat' => -41.5134, 'lng' => 173.9612, 'cities' => [
                        ['name' => 'Blenheim', 'lat' => -41.5134, 'lng' => 173.9612, 'pop' => 29000, 'popular' => false],
                    ]],
                    ['code' => 'GIS', 'name' => 'Gisborne', 'slug' => 'gisborne', 'level' => 'region', 'lat' => -38.6623, 'lng' => 178.0176, 'cities' => [
                        ['name' => 'Gisborne', 'lat' => -38.6623, 'lng' => 178.0176, 'pop' => 37700, 'popular' => false],
                    ]],
                    ['code' => 'WTC', 'name' => 'West Coast', 'slug' => 'west-coast', 'level' => 'region', 'lat' => -42.4504, 'lng' => 171.2108, 'cities' => [
                        ['name' => 'Greymouth', 'lat' => -42.4504, 'lng' => 171.2108, 'pop' => 8320, 'popular' => false],
                    ]],
                ],
            ],
        ];
    }

    /**
     * Load all 50 US states + District of Columbia + verified major US cities
     * from database/data/locations/us_states.json & us_cities.json (Census Bureau Gazetteer).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getUnitedStatesAreasDataset(): array
    {
        $statesPath = database_path('data/locations/us_states.json');
        $citiesPath = database_path('data/locations/us_cities.json');

        if (! file_exists($statesPath)) {
            return [];
        }

        $statesJson = json_decode((string) file_get_contents($statesPath), true) ?: [];
        $citiesByState = [];

        if (file_exists($citiesPath)) {
            $citiesJson = json_decode((string) file_get_contents($citiesPath), true) ?: [];
            foreach ($citiesJson as $cityRow) {
                $stCode = strtoupper((string) ($cityRow['state_code'] ?? ''));
                $cityName = trim((string) ($cityRow['name'] ?? ''));
                $cityType = $cityRow['city_type'] ?? 'city';
                if ($stCode === '' || $cityName === '') {
                    continue;
                }
                if (stripos($cityName, 'village') !== false || stripos($cityName, 'township') !== false) {
                    continue;
                }
                if (! in_array($cityType, ['city', 'city and borough', 'municipality', 'consolidated government', 'metropolitan government', 'unified government', 'urban county', 'corporation'], true)) {
                    continue;
                }
                $isPopular = ! empty($cityRow['is_popular']);
                $pop = (int) ($cityRow['population'] ?? 0);
                // Keep all popular cities + major cities (or cap per state for fast seeding; full 30k+ import via locations:import)
                if (! $isPopular && $pop < 15000 && count($citiesByState[$stCode] ?? []) >= 15) {
                    continue;
                }
                $citiesByState[$stCode][] = [
                    'name' => $cityName,
                    'slug' => $cityRow['slug'] ?? Str::slug($cityName),
                    'lat' => $cityRow['latitude'] ?? null,
                    'lng' => $cityRow['longitude'] ?? null,
                    'pop' => $pop > 0 ? $pop : ($isPopular ? 500000 : 50000),
                    'tz' => $cityRow['timezone'] ?? 'America/New_York',
                    'popular' => $isPopular,
                ];
            }
        }

        $areas = [];
        foreach ($statesJson as $st) {
            $code = strtoupper((string) ($st['code'] ?? ''));
            $name = trim((string) ($st['name'] ?? ''));
            if ($code === '' || $name === '') {
                continue;
            }
            $isTerritory = (bool) ($st['is_territory'] ?? false);
            $areas[] = [
                'code' => $code,
                'name' => $name,
                'slug' => $st['slug'] ?? Str::slug($name),
                'level' => $isTerritory ? 'territory' : ($code === 'DC' ? 'district' : 'state'),
                'is_territory' => $isTerritory,
                'lat' => $st['latitude'] ?? null,
                'lng' => $st['longitude'] ?? null,
                'cities' => $citiesByState[$code] ?? [],
            ];
        }

        return $areas;
    }

    /**
     * Complete United Kingdom regions/nations (13) and 170+ verified UK cities & major towns.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getUnitedKingdomAreasDataset(): array
    {
        return [
            [
                'code' => 'ENG',
                'name' => 'England',
                'slug' => 'england',
                'level' => 'nation',
                'lat' => 52.3555,
                'lng' => -1.1743,
                'cities' => [
                    ['name' => 'London', 'lat' => 51.5074, 'lng' => -0.1278, 'pop' => 8982000, 'popular' => true],
                    ['name' => 'Manchester', 'lat' => 53.4808, 'lng' => -2.2426, 'pop' => 553230, 'popular' => true],
                    ['name' => 'Birmingham', 'lat' => 52.4862, 'lng' => -1.8904, 'pop' => 1144900, 'popular' => true],
                    ['name' => 'Liverpool', 'lat' => 53.4084, 'lng' => -2.9916, 'pop' => 498042, 'popular' => true],
                    ['name' => 'Leeds', 'lat' => 53.8008, 'lng' => -1.5491, 'pop' => 792525, 'popular' => true],
                    ['name' => 'Bristol', 'lat' => 51.4545, 'lng' => -2.5879, 'pop' => 467099, 'popular' => true],
                    ['name' => 'Sheffield', 'lat' => 53.3811, 'lng' => -1.4701, 'pop' => 584853, 'popular' => true],
                    ['name' => 'Newcastle', 'slug' => 'newcastle', 'lat' => 54.9783, 'lng' => -1.6178, 'pop' => 302820, 'popular' => true],
                    ['name' => 'Nottingham', 'lat' => 52.9548, 'lng' => -1.1581, 'pop' => 332900, 'popular' => true],
                    ['name' => 'Leicester', 'lat' => 52.6369, 'lng' => -1.1398, 'pop' => 368600, 'popular' => false],
                    ['name' => 'Oxford', 'lat' => 51.7520, 'lng' => -1.2577, 'pop' => 162100, 'popular' => true],
                    ['name' => 'Cambridge', 'lat' => 52.2053, 'lng' => 0.1218, 'pop' => 145700, 'popular' => true],
                    ['name' => 'Brighton', 'lat' => 50.8225, 'lng' => -0.1372, 'pop' => 290885, 'popular' => false],
                    ['name' => 'Southampton', 'lat' => 50.9097, 'lng' => -1.4044, 'pop' => 252520, 'popular' => false],
                    ['name' => 'Portsmouth', 'lat' => 50.8198, 'lng' => -1.0880, 'pop' => 208100, 'popular' => false],
                    ['name' => 'Reading', 'lat' => 51.4543, 'lng' => -0.9781, 'pop' => 174224, 'popular' => false],
                    ['name' => 'Coventry', 'lat' => 52.4068, 'lng' => -1.5197, 'pop' => 345300, 'popular' => false],
                    ['name' => 'York', 'lat' => 53.9599, 'lng' => -1.0873, 'pop' => 210618, 'popular' => false],
                    ['name' => 'Norwich', 'lat' => 52.6309, 'lng' => 1.2974, 'pop' => 143900, 'popular' => false],
                    ['name' => 'Plymouth', 'lat' => 50.3755, 'lng' => -4.1427, 'pop' => 264700, 'popular' => false],
                ],
            ],
            [
                'code' => 'LDN',
                'name' => 'Greater London',
                'slug' => 'greater-london',
                'level' => 'region',
                'lat' => 51.5074,
                'lng' => -0.1278,
                'cities' => [
                    ['name' => 'Central London', 'lat' => 51.5095, 'lng' => -0.1245, 'pop' => 1200000, 'popular' => true],
                    ['name' => 'Westminster', 'lat' => 51.4975, 'lng' => -0.1357, 'pop' => 261000, 'popular' => true],
                    ['name' => 'Camden', 'lat' => 51.5390, 'lng' => -0.1426, 'pop' => 270000, 'popular' => false],
                    ['name' => 'Croydon', 'lat' => 51.3762, 'lng' => -0.0982, 'pop' => 390700, 'popular' => false],
                    ['name' => 'Greenwich', 'lat' => 51.4826, 'lng' => 0.0077, 'pop' => 289100, 'popular' => false],
                    ['name' => 'Hackney', 'lat' => 51.5450, 'lng' => -0.0553, 'pop' => 281100, 'popular' => false],
                    ['name' => 'Islington', 'lat' => 51.5416, 'lng' => -0.1022, 'pop' => 242500, 'popular' => false],
                    ['name' => 'Kensington', 'lat' => 51.5000, 'lng' => -0.1919, 'pop' => 156200, 'popular' => false],
                    ['name' => 'Kingston upon Thames', 'lat' => 51.4123, 'lng' => -0.3007, 'pop' => 177500, 'popular' => false],
                    ['name' => 'Richmond', 'lat' => 51.4613, 'lng' => -0.3037, 'pop' => 198000, 'popular' => false],
                    ['name' => 'Stratford', 'lat' => 51.5416, 'lng' => -0.0033, 'pop' => 353000, 'popular' => false],
                    ['name' => 'Wembley', 'lat' => 51.5505, 'lng' => -0.2960, 'pop' => 339800, 'popular' => false],
                    ['name' => 'Wimbledon', 'lat' => 51.4214, 'lng' => -0.2064, 'pop' => 215000, 'popular' => false],
                    ['name' => 'Bromley', 'lat' => 51.4060, 'lng' => 0.0152, 'pop' => 332300, 'popular' => false],
                    ['name' => 'Ealing', 'lat' => 51.5130, 'lng' => -0.3089, 'pop' => 367100, 'popular' => false],
                    ['name' => 'Hounslow', 'lat' => 51.4746, 'lng' => -0.3680, 'pop' => 288200, 'popular' => false],
                ],
            ],
            [
                'code' => 'SEE',
                'name' => 'South East England',
                'slug' => 'south-east-england',
                'level' => 'region',
                'lat' => 51.2787,
                'lng' => -0.5217,
                'cities' => [
                    ['name' => 'Brighton & Hove', 'lat' => 50.8225, 'lng' => -0.1372, 'pop' => 290885, 'popular' => true],
                    ['name' => 'Milton Keynes', 'lat' => 52.0406, 'lng' => -0.7594, 'pop' => 287060, 'popular' => true],
                    ['name' => 'Guildford', 'lat' => 51.2362, 'lng' => -0.5704, 'pop' => 148000, 'popular' => false],
                    ['name' => 'Canterbury', 'lat' => 51.2802, 'lng' => 1.0789, 'pop' => 165400, 'popular' => false],
                    ['name' => 'Maidstone', 'lat' => 51.2704, 'lng' => 0.5227, 'pop' => 176000, 'popular' => false],
                    ['name' => 'Crawley', 'lat' => 51.1091, 'lng' => -0.1872, 'pop' => 118500, 'popular' => false],
                    ['name' => 'Slough', 'lat' => 51.5105, 'lng' => -0.5950, 'pop' => 158500, 'popular' => false],
                    ['name' => 'Basingstoke', 'lat' => 51.2665, 'lng' => -1.0924, 'pop' => 185200, 'popular' => false],
                    ['name' => 'Winchester', 'lat' => 51.0632, 'lng' => -1.3080, 'pop' => 127500, 'popular' => false],
                    ['name' => 'Eastbourne', 'lat' => 50.7680, 'lng' => 0.2905, 'pop' => 103700, 'popular' => false],
                    ['name' => 'Hastings', 'lat' => 50.8543, 'lng' => 0.5732, 'pop' => 91000, 'popular' => false],
                    ['name' => 'Worthing', 'lat' => 50.8179, 'lng' => -0.3729, 'pop' => 111300, 'popular' => false],
                    ['name' => 'Chichester', 'lat' => 50.8376, 'lng' => -0.7749, 'pop' => 124100, 'popular' => false],
                    ['name' => 'Windsor', 'lat' => 51.4817, 'lng' => -0.6138, 'pop' => 32000, 'popular' => false],
                    ['name' => 'High Wycombe', 'lat' => 51.6286, 'lng' => -0.7482, 'pop' => 125000, 'popular' => false],
                    ['name' => 'Ashford', 'lat' => 51.1465, 'lng' => 0.8750, 'pop' => 132700, 'popular' => false],
                    ['name' => 'Margate', 'lat' => 51.3896, 'lng' => 1.3868, 'pop' => 65000, 'popular' => false],
                ],
            ],
            [
                'code' => 'SWE',
                'name' => 'South West England',
                'slug' => 'south-west-england',
                'level' => 'region',
                'lat' => 50.7772,
                'lng' => -3.9995,
                'cities' => [
                    ['name' => 'Bournemouth', 'lat' => 50.7192, 'lng' => -1.8808, 'pop' => 197000, 'popular' => true],
                    ['name' => 'Exeter', 'lat' => 50.7184, 'lng' => -3.5339, 'pop' => 131400, 'popular' => true],
                    ['name' => 'Bath', 'lat' => 51.3758, 'lng' => -2.3599, 'pop' => 101500, 'popular' => false],
                    ['name' => 'Gloucester', 'lat' => 51.8642, 'lng' => -2.2382, 'pop' => 132400, 'popular' => false],
                    ['name' => 'Cheltenham', 'lat' => 51.8994, 'lng' => -2.0783, 'pop' => 118800, 'popular' => false],
                    ['name' => 'Swindon', 'lat' => 51.5558, 'lng' => -1.7797, 'pop' => 233400, 'popular' => false],
                    ['name' => 'Poole', 'lat' => 50.7151, 'lng' => -1.9872, 'pop' => 151500, 'popular' => false],
                    ['name' => 'Torquay', 'lat' => 50.4619, 'lng' => -3.5253, 'pop' => 65000, 'popular' => false],
                    ['name' => 'Truro', 'lat' => 50.2632, 'lng' => -5.0510, 'pop' => 21500, 'popular' => false],
                    ['name' => 'Salisbury', 'lat' => 51.0688, 'lng' => -1.7945, 'pop' => 45000, 'popular' => false],
                    ['name' => 'Taunton', 'lat' => 51.0150, 'lng' => -3.1029, 'pop' => 69500, 'popular' => false],
                    ['name' => 'Weston-super-Mare', 'lat' => 51.3460, 'lng' => -2.9770, 'pop' => 83600, 'popular' => false],
                ],
            ],
            [
                'code' => 'EE',
                'name' => 'East of England',
                'slug' => 'east-of-england',
                'level' => 'region',
                'lat' => 52.2405,
                'lng' => 0.4027,
                'cities' => [
                    ['name' => 'Peterborough', 'lat' => 52.5695, 'lng' => -0.2405, 'pop' => 215700, 'popular' => true],
                    ['name' => 'Ipswich', 'lat' => 52.0567, 'lng' => 1.1482, 'pop' => 139600, 'popular' => false],
                    ['name' => 'Luton', 'lat' => 51.8787, 'lng' => -0.4200, 'pop' => 225300, 'popular' => false],
                    ['name' => 'Southend-on-Sea', 'lat' => 51.5459, 'lng' => 0.7077, 'pop' => 180700, 'popular' => false],
                    ['name' => 'Colchester', 'lat' => 51.8891, 'lng' => 0.9039, 'pop' => 192700, 'popular' => false],
                    ['name' => 'Chelmsford', 'lat' => 51.7356, 'lng' => 0.4685, 'pop' => 181500, 'popular' => false],
                    ['name' => 'Watford', 'lat' => 51.6565, 'lng' => -0.3903, 'pop' => 102200, 'popular' => false],
                    ['name' => 'Basildon', 'lat' => 51.5761, 'lng' => 0.4887, 'pop' => 187600, 'popular' => false],
                    ['name' => 'Bedford', 'lat' => 52.1360, 'lng' => -0.4667, 'pop' => 185300, 'popular' => false],
                    ['name' => 'St Albans', 'lat' => 51.7527, 'lng' => -0.3394, 'pop' => 148200, 'popular' => false],
                    ['name' => 'Stevenage', 'lat' => 51.9022, 'lng' => -0.2026, 'pop' => 89500, 'popular' => false],
                    ['name' => 'Harlow', 'lat' => 51.7729, 'lng' => 0.1023, 'pop' => 93300, 'popular' => false],
                    ['name' => 'Great Yarmouth', 'lat' => 52.6083, 'lng' => 1.7303, 'pop' => 99800, 'popular' => false],
                ],
            ],
            [
                'code' => 'WM',
                'name' => 'West Midlands',
                'slug' => 'west-midlands',
                'level' => 'region',
                'lat' => 52.4862,
                'lng' => -1.8904,
                'cities' => [
                    ['name' => 'Wolverhampton', 'lat' => 52.5862, 'lng' => -2.1288, 'pop' => 263700, 'popular' => true],
                    ['name' => 'Stoke-on-Trent', 'lat' => 53.0027, 'lng' => -2.1794, 'pop' => 258400, 'popular' => false],
                    ['name' => 'Walsall', 'lat' => 52.5860, 'lng' => -1.9829, 'pop' => 284100, 'popular' => false],
                    ['name' => 'Dudley', 'lat' => 52.5123, 'lng' => -2.0811, 'pop' => 323500, 'popular' => false],
                    ['name' => 'Telford', 'lat' => 52.6784, 'lng' => -2.4453, 'pop' => 185600, 'popular' => false],
                    ['name' => 'Worcester', 'lat' => 52.1936, 'lng' => -2.2216, 'pop' => 103900, 'popular' => false],
                    ['name' => 'Hereford', 'lat' => 52.0565, 'lng' => -2.7160, 'pop' => 63600, 'popular' => false],
                    ['name' => 'Shrewsbury', 'lat' => 52.7073, 'lng' => -2.7553, 'pop' => 76800, 'popular' => false],
                    ['name' => 'Solihull', 'lat' => 52.4118, 'lng' => -1.7776, 'pop' => 216200, 'popular' => false],
                    ['name' => 'Tamworth', 'lat' => 52.6340, 'lng' => -1.6959, 'pop' => 78600, 'popular' => false],
                    ['name' => 'Nuneaton', 'lat' => 52.5230, 'lng' => -1.4683, 'pop' => 88800, 'popular' => false],
                    ['name' => 'Stafford', 'lat' => 52.8063, 'lng' => -2.1166, 'pop' => 136900, 'popular' => false],
                    ['name' => 'Lichfield', 'lat' => 52.6835, 'lng' => -1.8265, 'pop' => 106400, 'popular' => false],
                ],
            ],
            [
                'code' => 'EM',
                'name' => 'East Midlands',
                'slug' => 'east-midlands',
                'level' => 'region',
                'lat' => 52.8300,
                'lng' => -1.3300,
                'cities' => [
                    ['name' => 'Derby', 'lat' => 52.9225, 'lng' => -1.4746, 'pop' => 261400, 'popular' => true],
                    ['name' => 'Northampton', 'lat' => 52.2405, 'lng' => -0.9027, 'pop' => 243500, 'popular' => false],
                    ['name' => 'Lincoln', 'lat' => 53.2307, 'lng' => -0.5406, 'pop' => 103900, 'popular' => false],
                    ['name' => 'Chesterfield', 'lat' => 53.2350, 'lng' => -1.4216, 'pop' => 103600, 'popular' => false],
                    ['name' => 'Mansfield', 'lat' => 53.1446, 'lng' => -1.1963, 'pop' => 110500, 'popular' => false],
                    ['name' => 'Loughborough', 'lat' => 52.7721, 'lng' => -1.2062, 'pop' => 64900, 'popular' => false],
                    ['name' => 'Kettering', 'lat' => 52.3983, 'lng' => -0.7257, 'pop' => 102000, 'popular' => false],
                    ['name' => 'Corby', 'lat' => 52.4877, 'lng' => -0.6858, 'pop' => 75500, 'popular' => false],
                    ['name' => 'Grantham', 'lat' => 52.9118, 'lng' => -0.6418, 'pop' => 45000, 'popular' => false],
                    ['name' => 'Boston', 'lat' => 52.9789, 'lng' => -0.0266, 'pop' => 70500, 'popular' => false],
                ],
            ],
            [
                'code' => 'YH',
                'name' => 'Yorkshire and the Humber',
                'slug' => 'yorkshire-and-the-humber',
                'level' => 'region',
                'lat' => 53.7997,
                'lng' => -1.5492,
                'cities' => [
                    ['name' => 'Bradford', 'lat' => 53.7960, 'lng' => -1.7594, 'pop' => 546400, 'popular' => true],
                    ['name' => 'Kingston upon Hull', 'lat' => 53.7676, 'lng' => -0.3274, 'pop' => 267000, 'popular' => false],
                    ['name' => 'Doncaster', 'lat' => 53.5228, 'lng' => -1.1285, 'pop' => 308100, 'popular' => false],
                    ['name' => 'Wakefield', 'lat' => 53.6833, 'lng' => -1.4977, 'pop' => 353400, 'popular' => false],
                    ['name' => 'Huddersfield', 'lat' => 53.6458, 'lng' => -1.7850, 'pop' => 162900, 'popular' => false],
                    ['name' => 'Rotherham', 'lat' => 53.4326, 'lng' => -1.3635, 'pop' => 265800, 'popular' => false],
                    ['name' => 'Barnsley', 'lat' => 53.5526, 'lng' => -1.4797, 'pop' => 244600, 'popular' => false],
                    ['name' => 'Halifax', 'lat' => 53.7248, 'lng' => -1.8658, 'pop' => 88100, 'popular' => false],
                    ['name' => 'Harrogate', 'lat' => 53.9921, 'lng' => -1.5418, 'pop' => 75100, 'popular' => false],
                    ['name' => 'Scarborough', 'lat' => 54.2831, 'lng' => -0.3998, 'pop' => 61700, 'popular' => false],
                    ['name' => 'Grimsby', 'lat' => 53.5675, 'lng' => -0.0802, 'pop' => 88200, 'popular' => false],
                    ['name' => 'Scunthorpe', 'lat' => 53.5886, 'lng' => -0.6544, 'pop' => 82300, 'popular' => false],
                ],
            ],
            [
                'code' => 'NWE',
                'name' => 'North West England',
                'slug' => 'north-west-england',
                'level' => 'region',
                'lat' => 53.6500,
                'lng' => -2.6000,
                'cities' => [
                    ['name' => 'Preston', 'lat' => 53.7632, 'lng' => -2.7031, 'pop' => 147900, 'popular' => true],
                    ['name' => 'Blackpool', 'lat' => 53.8175, 'lng' => -3.0357, 'pop' => 141100, 'popular' => false],
                    ['name' => 'Bolton', 'lat' => 53.5769, 'lng' => -2.4282, 'pop' => 296000, 'popular' => false],
                    ['name' => 'Wigan', 'lat' => 53.5451, 'lng' => -2.6325, 'pop' => 329300, 'popular' => false],
                    ['name' => 'Stockport', 'lat' => 53.4106, 'lng' => -2.1575, 'pop' => 294800, 'popular' => false],
                    ['name' => 'Salford', 'lat' => 53.4875, 'lng' => -2.2901, 'pop' => 269900, 'popular' => false],
                    ['name' => 'Oldham', 'lat' => 53.5409, 'lng' => -2.1114, 'pop' => 242100, 'popular' => false],
                    ['name' => 'Rochdale', 'lat' => 53.6097, 'lng' => -2.1561, 'pop' => 223800, 'popular' => false],
                    ['name' => 'Blackburn', 'lat' => 53.7500, 'lng' => -2.4849, 'pop' => 154700, 'popular' => false],
                    ['name' => 'Chester', 'lat' => 53.1934, 'lng' => -2.8931, 'pop' => 136700, 'popular' => false],
                    ['name' => 'Warrington', 'lat' => 53.3900, 'lng' => -2.5970, 'pop' => 211000, 'popular' => false],
                    ['name' => 'Lancaster', 'lat' => 54.0466, 'lng' => -2.8007, 'pop' => 142900, 'popular' => false],
                    ['name' => 'Carlisle', 'lat' => 54.8925, 'lng' => -2.9329, 'pop' => 110000, 'popular' => false],
                    ['name' => 'Birkenhead', 'lat' => 53.3934, 'lng' => -3.0148, 'pop' => 88800, 'popular' => false],
                    ['name' => 'St Helens', 'lat' => 53.4541, 'lng' => -2.7361, 'pop' => 183200, 'popular' => false],
                ],
            ],
            [
                'code' => 'NEE',
                'name' => 'North East England',
                'slug' => 'north-east-england',
                'level' => 'region',
                'lat' => 54.9783,
                'lng' => -1.6178,
                'cities' => [
                    ['name' => 'Sunderland', 'lat' => 54.9069, 'lng' => -1.3838, 'pop' => 274200, 'popular' => true],
                    ['name' => 'Middlesbrough', 'lat' => 54.5742, 'lng' => -1.2350, 'pop' => 143900, 'popular' => false],
                    ['name' => 'Durham', 'lat' => 54.7753, 'lng' => -1.5849, 'pop' => 94300, 'popular' => false],
                    ['name' => 'Gateshead', 'lat' => 54.9621, 'lng' => -1.6017, 'pop' => 196100, 'popular' => false],
                    ['name' => 'Darlington', 'lat' => 54.5236, 'lng' => -1.5597, 'pop' => 107800, 'popular' => false],
                    ['name' => 'Hartlepool', 'lat' => 54.6863, 'lng' => -1.2129, 'pop' => 92300, 'popular' => false],
                    ['name' => 'Stockton-on-Tees', 'lat' => 54.5685, 'lng' => -1.3187, 'pop' => 196600, 'popular' => false],
                    ['name' => 'South Shields', 'lat' => 54.9960, 'lng' => -1.4326, 'pop' => 75300, 'popular' => false],
                ],
            ],
            [
                'code' => 'SCT',
                'name' => 'Scotland',
                'slug' => 'scotland',
                'level' => 'nation',
                'lat' => 56.4907,
                'lng' => -4.2026,
                'cities' => [
                    ['name' => 'Glasgow', 'lat' => 55.8642, 'lng' => -4.2518, 'pop' => 635640, 'popular' => true],
                    ['name' => 'Edinburgh', 'lat' => 55.9533, 'lng' => -3.1883, 'pop' => 524930, 'popular' => true],
                    ['name' => 'Aberdeen', 'lat' => 57.1497, 'lng' => -2.0943, 'pop' => 227560, 'popular' => true],
                    ['name' => 'Dundee', 'lat' => 56.4620, 'lng' => -2.9707, 'pop' => 148820, 'popular' => false],
                    ['name' => 'Inverness', 'lat' => 57.4778, 'lng' => -4.2247, 'pop' => 63730, 'popular' => false],
                    ['name' => 'Stirling', 'lat' => 56.1165, 'lng' => -3.9369, 'pop' => 49950, 'popular' => false],
                    ['name' => 'Perth', 'lat' => 56.3950, 'lng' => -3.4308, 'pop' => 47430, 'popular' => false],
                    ['name' => 'Paisley', 'lat' => 55.8456, 'lng' => -4.4239, 'pop' => 77220, 'popular' => false],
                    ['name' => 'East Kilbride', 'lat' => 55.7644, 'lng' => -4.1771, 'pop' => 75390, 'popular' => false],
                    ['name' => 'Livingston', 'lat' => 55.8818, 'lng' => -3.5159, 'pop' => 56840, 'popular' => false],
                    ['name' => 'Dunfermline', 'lat' => 56.0717, 'lng' => -3.4522, 'pop' => 58500, 'popular' => false],
                    ['name' => 'Kirkcaldy', 'lat' => 56.1107, 'lng' => -3.1674, 'pop' => 50370, 'popular' => false],
                    ['name' => 'Ayr', 'lat' => 55.4586, 'lng' => -4.6292, 'pop' => 46800, 'popular' => false],
                    ['name' => 'Kilmarnock', 'lat' => 55.6111, 'lng' => -4.4957, 'pop' => 46970, 'popular' => false],
                    ['name' => 'Falkirk', 'lat' => 56.0019, 'lng' => -3.7839, 'pop' => 35850, 'popular' => false],
                    ['name' => 'Dumfries', 'lat' => 55.0709, 'lng' => -3.6051, 'pop' => 33400, 'popular' => false],
                ],
            ],
            [
                'code' => 'WLS',
                'name' => 'Wales',
                'slug' => 'wales',
                'level' => 'nation',
                'lat' => 52.1307,
                'lng' => -3.7837,
                'cities' => [
                    ['name' => 'Cardiff', 'lat' => 51.4816, 'lng' => -3.1791, 'pop' => 362310, 'popular' => true],
                    ['name' => 'Swansea', 'lat' => 51.6214, 'lng' => -3.9436, 'pop' => 246993, 'popular' => true],
                    ['name' => 'Newport', 'lat' => 51.5842, 'lng' => -2.9977, 'pop' => 159600, 'popular' => false],
                    ['name' => 'Wrexham', 'lat' => 53.0430, 'lng' => -2.9925, 'pop' => 135117, 'popular' => false],
                    ['name' => 'Bangor', 'lat' => 53.2274, 'lng' => -4.1293, 'pop' => 18322, 'popular' => false],
                    ['name' => 'Barry', 'lat' => 51.3999, 'lng' => -3.2845, 'pop' => 54600, 'popular' => false],
                    ['name' => 'Neath', 'lat' => 51.6626, 'lng' => -3.8049, 'pop' => 50600, 'popular' => false],
                    ['name' => 'Bridgend', 'lat' => 51.5079, 'lng' => -3.5784, 'pop' => 49400, 'popular' => false],
                    ['name' => 'Llanelli', 'lat' => 51.6840, 'lng' => -4.1629, 'pop' => 49500, 'popular' => false],
                    ['name' => 'Rhyl', 'lat' => 53.3193, 'lng' => -3.4899, 'pop' => 26000, 'popular' => false],
                    ['name' => 'Aberystwyth', 'lat' => 52.4153, 'lng' => -4.0829, 'pop' => 18000, 'popular' => false],
                    ['name' => 'St Asaph', 'lat' => 53.2578, 'lng' => -3.4422, 'pop' => 3500, 'popular' => false],
                    ['name' => 'St Davids', 'lat' => 51.8812, 'lng' => -5.2660, 'pop' => 1800, 'popular' => false],
                ],
            ],
            [
                'code' => 'NIR',
                'name' => 'Northern Ireland',
                'slug' => 'northern-ireland',
                'level' => 'nation',
                'lat' => 54.7877,
                'lng' => -6.4923,
                'cities' => [
                    ['name' => 'Belfast', 'lat' => 54.5973, 'lng' => -5.9301, 'pop' => 345418, 'popular' => true],
                    ['name' => 'Derry', 'lat' => 54.9966, 'lng' => -7.3086, 'pop' => 110894, 'popular' => false],
                    ['name' => 'Lisburn', 'lat' => 54.5162, 'lng' => -6.0580, 'pop' => 71465, 'popular' => false],
                    ['name' => 'Newry', 'lat' => 54.1751, 'lng' => -6.3402, 'pop' => 27913, 'popular' => false],
                    ['name' => 'Armagh', 'lat' => 54.3503, 'lng' => -6.6528, 'pop' => 14777, 'popular' => false],
                    ['name' => 'Newtownabbey', 'lat' => 54.6667, 'lng' => -5.9167, 'pop' => 65600, 'popular' => false],
                    ['name' => 'Bangor (NI)', 'slug' => 'bangor-ni', 'lat' => 54.6596, 'lng' => -5.6688, 'pop' => 64500, 'popular' => false],
                    ['name' => 'Craigavon', 'lat' => 54.4470, 'lng' => -6.3870, 'pop' => 72000, 'popular' => false],
                    ['name' => 'Ballymena', 'lat' => 54.8639, 'lng' => -6.2764, 'pop' => 31200, 'popular' => false],
                    ['name' => 'Coleraine', 'lat' => 55.1326, 'lng' => -6.6646, 'pop' => 24600, 'popular' => false],
                    ['name' => 'Omagh', 'lat' => 54.5998, 'lng' => -7.3091, 'pop' => 21300, 'popular' => false],
                    ['name' => 'Enniskillen', 'lat' => 54.3456, 'lng' => -7.6383, 'pop' => 13800, 'popular' => false],
                ],
            ],
        ];
    }
}
