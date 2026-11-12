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
        foreach ($datasets as $countryData) {
            $sortOrder = $countryData['sort_order'] ?? $orderIdx++;
            DB::transaction(function () use ($countryData, $categories, $sortOrder, &$summary) {
                $country = Country::updateOrCreate(
                    ['code' => $countryData['code']],
                    [
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
                        'sort_order' => $sortOrder,
                    ]
                );

                $summary['countries_seeded']++;
                $countryAreas = 0;
                $countryCities = 0;

                foreach ($countryData['areas'] as $areaData) {
                    $areaSlug = $areaData['slug'] ?? Str::slug($areaData['name']);
                    $state = State::updateOrCreate(
                        [
                            'country_id' => $country->id,
                            'code' => $areaData['code'],
                        ],
                        [
                            'name' => $areaData['name'],
                            'slug' => $areaSlug,
                            'administrative_level' => $areaData['level'] ?? 'state',
                            'is_territory' => (bool) ($areaData['is_territory'] ?? false),
                            'latitude' => $areaData['lat'] ?? null,
                            'longitude' => $areaData['lng'] ?? null,
                            'is_active' => true,
                        ]
                    );

                    $countryAreas++;
                    $summary['areas_seeded']++;

                    foreach ($areaData['cities'] as $cityData) {
                        $citySlug = $cityData['slug'] ?? Str::slug($cityData['name']);
                        City::updateOrCreate(
                            [
                                'state_id' => $state->id,
                                'slug' => $citySlug,
                            ],
                            [
                                'country_id' => $country->id,
                                'name' => $cityData['name'],
                                'city_type' => 'city',
                                'latitude' => $cityData['lat'],
                                'longitude' => $cityData['lng'],
                                'population' => $cityData['pop'] ?? 100000,
                                'timezone' => $cityData['tz'] ?? $countryData['timezone'],
                                'is_popular' => (bool) ($cityData['popular'] ?? false),
                                'is_active' => true,
                            ]
                        );

                        $countryCities++;
                        $summary['cities_seeded']++;
                    }
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
     * Verified geographic dataset for the 5 supported country marketplaces.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDatasetDefinitions(): array
    {
        return [
            // 1. UNITED KINGDOM (GB)
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
                'seo_title' => 'United Kingdom Classifieds & Local Dating Marketplace — LocalDateHub UK',
                'seo_description' => 'Explore verified local personal classifieds, dating, and connections across England, Scotland, Wales, and Northern Ireland on LocalDateHub UK.',
                'areas' => [
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
                        'code' => 'SCT',
                        'name' => 'Scotland',
                        'slug' => 'scotland',
                        'level' => 'nation',
                        'lat' => 56.4907,
                        'lng' => -4.2026,
                        'cities' => [
                            ['name' => 'Glasgow', 'lat' => 55.8642, 'lng' => -4.2518, 'pop' => 635640, 'popular' => true],
                            ['name' => 'Edinburgh', 'lat' => 55.9533, 'lng' => -3.1883, 'pop' => 524930, 'popular' => true],
                            ['name' => 'Aberdeen', 'lat' => 57.1497, 'lng' => -2.0943, 'pop' => 227560, 'popular' => false],
                            ['name' => 'Dundee', 'lat' => 56.4620, 'lng' => -2.9707, 'pop' => 148820, 'popular' => false],
                            ['name' => 'Inverness', 'lat' => 57.4778, 'lng' => -4.2247, 'pop' => 63730, 'popular' => false],
                            ['name' => 'Stirling', 'lat' => 56.1165, 'lng' => -3.9369, 'pop' => 49950, 'popular' => false],
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
                            ['name' => 'Swansea', 'lat' => 51.6214, 'lng' => -3.9436, 'pop' => 246993, 'popular' => false],
                            ['name' => 'Newport', 'lat' => 51.5842, 'lng' => -2.9977, 'pop' => 159600, 'popular' => false],
                            ['name' => 'Wrexham', 'lat' => 53.0430, 'lng' => -2.9925, 'pop' => 135117, 'popular' => false],
                            ['name' => 'Bangor', 'lat' => 53.2274, 'lng' => -4.1293, 'pop' => 18322, 'popular' => false],
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
                        ],
                    ],
                ],
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
                            ['name' => 'Central Coast', 'lat' => -33.4250, 'lng' => 151.3414, 'pop' => 346596, 'popular' => false],
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
                            ['name' => 'Ballarat', 'lat' => -37.5622, 'lng' => 143.8503, 'pop' => 111702, 'popular' => false, 'tz' => 'Australia/Melbourne'],
                            ['name' => 'Bendigo', 'lat' => -36.7570, 'lng' => 144.2794, 'pop' => 102499, 'popular' => false, 'tz' => 'Australia/Melbourne'],
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
                            ['name' => 'Cairns', 'lat' => -16.9186, 'lng' => 145.7781, 'pop' => 153952, 'popular' => true, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Townsville', 'lat' => -19.2590, 'lng' => 146.8169, 'pop' => 180820, 'popular' => true, 'tz' => 'Australia/Brisbane'],
                            ['name' => 'Sunshine Coast', 'lat' => -26.6500, 'lng' => 153.0667, 'pop' => 348389, 'popular' => false, 'tz' => 'Australia/Brisbane'],
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
                            ['name' => 'Fremantle', 'lat' => -32.0569, 'lng' => 115.7439, 'pop' => 31930, 'popular' => false, 'tz' => 'Australia/Perth'],
                            ['name' => 'Bunbury', 'lat' => -33.3256, 'lng' => 115.6396, 'pop' => 75319, 'popular' => false, 'tz' => 'Australia/Perth'],
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
                            ['name' => 'Mount Gambier', 'lat' => -37.8284, 'lng' => 140.7804, 'pop' => 27433, 'popular' => false, 'tz' => 'Australia/Adelaide'],
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
                            ['name' => 'Launceston', 'lat' => -41.4332, 'lng' => 147.1441, 'pop' => 88884, 'popular' => false, 'tz' => 'Australia/Hobart'],
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
                            ['name' => 'Alice Springs', 'lat' => -23.6980, 'lng' => 133.8807, 'pop' => 26534, 'popular' => false, 'tz' => 'Australia/Darwin'],
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
}
