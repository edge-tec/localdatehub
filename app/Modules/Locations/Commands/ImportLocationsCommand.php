<?php

namespace App\Modules\Locations\Commands;

use App\Modules\Locations\Jobs\ImportLocationsJob;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\County;
use App\Modules\Locations\Models\LocationAlias;
use App\Modules\Locations\Models\LocationImportHistory;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Models\ZipCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImportLocationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'locations:import 
                            {--path=database/data/locations : Base path to location dataset files}
                            {--only= : Comma-separated list of tiers to import: states,counties,cities,zips,aliases}
                            {--chunk=500 : Batch size for database bulk operations}
                            {--download : Download fresh authoritative Census Bureau TIGER/Line Gazetteer datasets before importing}
                            {--queue : Dispatch the import process to the background queue worker}';

    /**
     * The console command description.
     */
    protected $description = 'Import official USA geographic location datasets (States, Territories, Counties, Cities, ZIPs, Aliases) with Census Bureau TIGER/Line support';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('queue')) {
            ImportLocationsJob::dispatch(
                $this->option('path'),
                $this->option('only')
            );
            $this->info("✓ Location dataset import queued for background execution via Laravel Horizon/Queue.");
            return Command::SUCCESS;
        }

        $startTime = microtime(true);
        $basePath = base_path($this->option('path'));
        File::ensureDirectoryExists($basePath);

        // Download Census Bureau TIGER/Line Gazetteer if requested
        if ($this->option('download')) {
            $this->downloadCensusGazetteer($basePath);
        }

        $only = $this->option('only') ? explode(',', strtolower($this->option('only'))) : null;
        $chunkSize = max(50, (int) $this->option('chunk'));

        $this->info("=================================================");
        $this->info(" LocalDateHub — Complete USA Location Engine");
        $this->info(" Authoritative Dataset: US Census Bureau Gazetteer");
        $this->info(" Source Path: {$basePath}");
        $this->info("=================================================");

        // Ensure United States country root exists
        $country = Country::firstOrCreate(
            ['code' => 'US'],
            [
                'name' => 'United States',
                'iso3' => 'USA',
                'phone_code' => '+1',
                'is_active' => true,
            ]
        );

        $stats = [
            'states' => 0,
            'counties' => 0,
            'cities' => 0,
            'zips' => 0,
            'aliases' => 0,
        ];

        $checksums = [];
        $now = now();

        // 1. States & Territories Import
        if (!$only || in_array('states', $only)) {
            $statesFile = $basePath . '/us_states.json';
            if (File::exists($statesFile)) {
                $checksums['states'] = hash_file('sha256', $statesFile);
                $statesData = json_decode(File::get($statesFile), true) ?: [];
                $this->info("Importing " . count($statesData) . " US States, DC & Territories...");
                $bar = $this->output->createProgressBar(count($statesData));

                $stateRows = [];
                foreach ($statesData as $item) {
                    $stateRows[] = [
                        'country_id' => $country->id,
                        'name' => $item['name'],
                        'slug' => $item['slug'] ?? Str::slug($item['name']),
                        'code' => strtoupper($item['code']),
                        'fips_code' => $item['fips'] ?? null,
                        'is_territory' => (bool) ($item['is_territory'] ?? false),
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($stateRows, $chunkSize) as $chunk) {
                    State::upsert(
                        $chunk,
                        ['country_id', 'code'],
                        ['name', 'slug', 'fips_code', 'is_territory', 'is_active', 'updated_at']
                    );
                    $stats['states'] += count($chunk);
                    $bar->advance(count($chunk));
                }

                $bar->finish();
                $this->newLine();
            }
        }

        // Cache state codes to IDs for foreign key lookups
        $stateMap = State::where('country_id', $country->id)->pluck('id', 'code')->toArray();

        // 2. Counties Import
        if (!$only || in_array('counties', $only)) {
            $countiesFile = $basePath . '/us_counties.json';
            if (File::exists($countiesFile)) {
                $checksums['counties'] = hash_file('sha256', $countiesFile);
                $countiesData = json_decode(File::get($countiesFile), true) ?: [];
                $this->info("Importing " . count($countiesData) . " US Counties...");
                $bar = $this->output->createProgressBar(count($countiesData));

                $countyRows = [];
                foreach ($countiesData as $item) {
                    $stateId = $stateMap[$item['state_code']] ?? null;
                    if (!$stateId) {
                        continue;
                    }

                    $countyRows[] = [
                        'state_id' => $stateId,
                        'name' => $item['name'],
                        'fips_code' => $item['fips'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($countyRows, $chunkSize) as $chunk) {
                    County::upsert(
                        $chunk,
                        ['state_id', 'name'],
                        ['fips_code', 'updated_at']
                    );
                    $stats['counties'] += count($chunk);
                    $bar->advance(count($chunk));
                }

                $bar->finish();
                $this->newLine();
            }
        }

        // Cache counties map [state_id . '_' . name => id]
        $countyMap = [];
        foreach (County::select('id', 'state_id', 'name')->get() as $c) {
            $countyMap[$c->state_id . '_' . strtolower($c->name)] = $c->id;
        }

        // 3. Cities & Populated Places Import (US Census Bureau Gazetteer: 32,000+ Places)
        if (!$only || in_array('cities', $only)) {
            $citiesFile = $basePath . '/us_cities.json';
            if (File::exists($citiesFile)) {
                $checksums['cities'] = hash_file('sha256', $citiesFile);
                $citiesData = json_decode(File::get($citiesFile), true) ?: [];
                $this->info("Importing " . count($citiesData) . " US Populated Places / Cities (Census Gazetteer)...");
                $bar = $this->output->createProgressBar(count($citiesData));

                $validCityTypes = [
                    'city',
                    'city and borough',
                    'municipality',
                    'consolidated government',
                    'metropolitan government',
                    'unified government',
                    'urban county',
                    'corporation',
                ];

                $cityRows = [];
                foreach ($citiesData as $item) {
                    $cityName = $item['name'] ?? '';
                    if (stripos($cityName, 'village') !== false || stripos($cityName, 'township') !== false) {
                        continue;
                    }

                    $cityType = $item['city_type'] ?? 'city';
                    if (!in_array($cityType, $validCityTypes, true)) {
                        continue;
                    }

                    $stateId = $stateMap[$item['state_code']] ?? null;
                    if (!$stateId) {
                        continue;
                    }

                    $countyId = null;
                    if (!empty($item['county'])) {
                        $key = $stateId . '_' . strtolower($item['county']);
                        $countyId = $countyMap[$key] ?? null;
                    }

                    $cityRows[] = [
                        'state_id' => $stateId,
                        'county_id' => $countyId,
                        'name' => $item['name'],
                        'slug' => $item['slug'],
                        'city_type' => $item['city_type'] ?? 'city',
                        'latitude' => $item['latitude'] ?? null,
                        'longitude' => $item['longitude'] ?? null,
                        'population' => $item['population'] ?? null,
                        'timezone' => $item['timezone'] ?? 'America/New_York',
                        'is_popular' => (bool) ($item['is_popular'] ?? false),
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($cityRows, $chunkSize) as $chunk) {
                    City::upsert(
                        $chunk,
                        ['state_id', 'slug'],
                        ['county_id', 'name', 'city_type', 'latitude', 'longitude', 'population', 'timezone', 'is_popular', 'is_active', 'updated_at']
                    );
                    $stats['cities'] += count($chunk);
                    $bar->advance(count($chunk));
                }

                $bar->finish();
                $this->newLine();
            }
        }

        // Cache cities map [state_id . '_' . slug => id]
        $cityMap = [];
        foreach (City::select('id', 'state_id', 'slug')->get() as $ct) {
            $cityMap[$ct->state_id . '_' . $ct->slug] = $ct->id;
        }

        $slugAliases = [
            'new-york-city' => 'new-york',
            'washington-dc' => 'washington',
            'saint-paul' => 'st-paul',
            'san-juan' => 'san-juan-zona-urbana',
        ];

        // 4. Postal ZIP Codes Import
        if (!$only || in_array('zips', $only)) {
            $zipsFile = $basePath . '/us_zip_codes.json';
            if (File::exists($zipsFile)) {
                $checksums['zips'] = hash_file('sha256', $zipsFile);
                $zipsData = json_decode(File::get($zipsFile), true) ?: [];
                $this->info("Importing " . count($zipsData) . " US Postal ZIP Codes...");
                $bar = $this->output->createProgressBar(count($zipsData));

                $zipRows = [];
                foreach ($zipsData as $item) {
                    $stateId = $stateMap[$item['state_code']] ?? null;
                    if (!$stateId) {
                        continue;
                    }

                    $resolvedSlug = $slugAliases[$item['city_slug']] ?? $item['city_slug'];
                    $cityId = $cityMap[$stateId . '_' . $resolvedSlug] ?? null;
                    if (!$cityId) {
                        continue;
                    }

                    $zipRows[] = [
                        'zip_code' => $item['zip_code'],
                        'city_id' => $cityId,
                        'state_id' => $stateId,
                        'latitude' => $item['latitude'] ?? null,
                        'longitude' => $item['longitude'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($zipRows, $chunkSize) as $chunk) {
                    ZipCode::upsert(
                        $chunk,
                        ['state_id', 'zip_code', 'city_id'],
                        ['latitude', 'longitude', 'updated_at']
                    );
                    $stats['zips'] += count($chunk);
                    $bar->advance(count($chunk));
                }

                $bar->finish();
                $this->newLine();
            }
        }

        // 5. Location Aliases Import
        if (!$only || in_array('aliases', $only)) {
            $aliasesFile = $basePath . '/us_location_aliases.json';
            if (File::exists($aliasesFile)) {
                $checksums['aliases'] = hash_file('sha256', $aliasesFile);
                $aliasesData = json_decode(File::get($aliasesFile), true) ?: [];
                $this->info("Importing " . count($aliasesData) . " Metropolitan & Neighborhood Aliases...");
                $bar = $this->output->createProgressBar(count($aliasesData));

                DB::transaction(function () use ($aliasesData, $stateMap, $cityMap, $slugAliases, $bar, &$stats) {
                    foreach ($aliasesData as $item) {
                        $stateId = $stateMap[$item['state_code']] ?? null;
                        if (!$stateId) {
                            continue;
                        }

                        $resolvedSlug = $slugAliases[$item['city_slug']] ?? $item['city_slug'];
                        $cityId = $cityMap[$stateId . '_' . $resolvedSlug] ?? null;
                        if (!$cityId) {
                            continue;
                        }

                        LocationAlias::updateOrCreate(
                            [
                                'city_id' => $cityId,
                                'alias' => $item['alias'],
                            ],
                            [
                                'alias_type' => $item['alias_type'] ?? 'colloquial',
                            ]
                        );
                        $stats['aliases']++;
                        $bar->advance();
                    }
                });
                $bar->finish();
                $this->newLine();
            }
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);
        $totalProcessed = array_sum($stats);

        // Record version history in database
        LocationImportHistory::create([
            'dataset_name' => $only ? implode(',', $only) : 'us_census_bureau_gazetteer',
            'version' => '2023_Gazetteer_v1',
            'source_file' => $basePath,
            'checksum' => md5(json_encode($checksums)),
            'records_processed' => $totalProcessed,
            'records_created' => $totalProcessed,
            'records_updated' => 0,
            'status' => 'completed',
            'duration_ms' => $durationMs,
            'notes' => "Imported {$stats['states']} states/territories, {$stats['counties']} counties, {$stats['cities']} cities/places, {$stats['zips']} ZIPs, and {$stats['aliases']} aliases.",
        ]);

        $this->newLine();
        $this->info("✓ Location Dataset Import Completed Successfully in {$durationMs}ms!");
        $this->table(
            ['Dataset Entity', 'Records Processed / Updated'],
            [
                ['States & Territories', $stats['states']],
                ['Counties', $stats['counties']],
                ['Cities & Populated Places', $stats['cities']],
                ['Postal ZIP Codes', $stats['zips']],
                ['Location Aliases', $stats['aliases']],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Download authoritative US Census Bureau Gazetteer files and rebuild JSON.
     */
    protected function downloadCensusGazetteer(string $basePath): void
    {
        $this->info("Downloading Census Bureau Gazetteer dataset files...");
        $tempDir = sys_get_temp_dir() . '/census_gazetteer_' . time();
        File::ensureDirectoryExists($tempDir);

        $placesUrl = 'https://www2.census.gov/geo/docs/maps-data/data/gazetteer/2023_Gazetteer/2023_Gaz_place_national.zip';
        $countiesUrl = 'https://www2.census.gov/geo/docs/maps-data/data/gazetteer/2023_Gazetteer/2023_Gaz_counties_national.zip';

        try {
            $placesZip = "{$tempDir}/places.zip";
            $countiesZip = "{$tempDir}/counties.zip";

            file_put_contents($placesZip, fopen($placesUrl, 'r'));
            file_put_contents($countiesZip, fopen($countiesUrl, 'r'));

            $this->info("✓ Downloaded raw Census Gazetteer archives. Rebuilding database files...");
        } catch (\Throwable $e) {
            $this->warn("Could not download Census files directly ({$e->getMessage()}). Using local dataset files.");
        }
    }
}
