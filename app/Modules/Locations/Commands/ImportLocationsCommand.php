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

class ImportLocationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'locations:import 
                            {--path=database/data/locations : Base path to location dataset files}
                            {--only= : Comma-separated list of tiers to import: states,counties,cities,zips,aliases}
                            {--chunk=500 : Batch size for database bulk operations}
                            {--queue : Dispatch the import process to the background queue worker}';

    /**
     * The console command description.
     */
    protected $description = 'Import official USA geographic location datasets (States, Territories, Counties, Cities, ZIPs, Aliases)';

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
        if (!File::isDirectory($basePath)) {
            $this->error("Dataset directory not found: {$basePath}");
            return Command::FAILURE;
        }

        $only = $this->option('only') ? explode(',', strtolower($this->option('only'))) : null;

        $this->info("=================================================");
        $this->info(" LocalDateHub — USA Location Dataset Importer");
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

        // 1. States & Territories Import
        if (!$only || in_array('states', $only)) {
            $statesFile = $basePath . '/us_states.json';
            if (File::exists($statesFile)) {
                $checksums['states'] = hash_file('sha256', $statesFile);
                $statesData = json_decode(File::get($statesFile), true) ?: [];
                $this->info("Importing " . count($statesData) . " US States, DC & Territories...");
                $bar = $this->output->createProgressBar(count($statesData));

                DB::transaction(function () use ($statesData, $country, $bar, &$stats) {
                    foreach ($statesData as $item) {
                        State::updateOrCreate(
                            ['code' => $item['code']],
                            [
                                'country_id' => $country->id,
                                'name' => $item['name'],
                                'fips_code' => $item['fips'] ?? null,
                                'is_territory' => (bool) ($item['is_territory'] ?? false),
                                'is_active' => true,
                            ]
                        );
                        $stats['states']++;
                        $bar->advance();
                    }
                });
                $bar->finish();
                $this->newLine();
            }
        }

        // Cache state codes to IDs for foreign key lookups
        $stateMap = State::pluck('id', 'code')->toArray();

        // 2. Counties Import
        if (!$only || in_array('counties', $only)) {
            $countiesFile = $basePath . '/us_counties.json';
            if (File::exists($countiesFile)) {
                $checksums['counties'] = hash_file('sha256', $countiesFile);
                $countiesData = json_decode(File::get($countiesFile), true) ?: [];
                $this->info("Importing " . count($countiesData) . " US Counties...");
                $bar = $this->output->createProgressBar(count($countiesData));

                DB::transaction(function () use ($countiesData, $stateMap, $bar, &$stats) {
                    foreach ($countiesData as $item) {
                        $stateId = $stateMap[$item['state_code']] ?? null;
                        if (!$stateId) {
                            continue;
                        }

                        County::updateOrCreate(
                            [
                                'state_id' => $stateId,
                                'name' => $item['name'],
                            ],
                            [
                                'fips_code' => $item['fips'] ?? null,
                            ]
                        );
                        $stats['counties']++;
                        $bar->advance();
                    }
                });
                $bar->finish();
                $this->newLine();
            }
        }

        // Cache counties map [state_id . '_' . name => id]
        $countyMap = [];
        foreach (County::select('id', 'state_id', 'name')->get() as $c) {
            $countyMap[$c->state_id . '_' . strtolower($c->name)] = $c->id;
        }

        // 3. Cities & Populated Places Import
        if (!$only || in_array('cities', $only)) {
            $citiesFile = $basePath . '/us_cities.json';
            if (File::exists($citiesFile)) {
                $checksums['cities'] = hash_file('sha256', $citiesFile);
                $citiesData = json_decode(File::get($citiesFile), true) ?: [];
                $this->info("Importing " . count($citiesData) . " US Populated Places / Cities...");
                $bar = $this->output->createProgressBar(count($citiesData));

                DB::transaction(function () use ($citiesData, $stateMap, $countyMap, $bar, &$stats) {
                    foreach ($citiesData as $item) {
                        $stateId = $stateMap[$item['state_code']] ?? null;
                        if (!$stateId) {
                            continue;
                        }

                        $countyId = null;
                        if (!empty($item['county'])) {
                            $key = $stateId . '_' . strtolower($item['county']);
                            $countyId = $countyMap[$key] ?? null;
                        }

                        City::updateOrCreate(
                            [
                                'state_id' => $stateId,
                                'slug' => $item['slug'],
                            ],
                            [
                                'county_id' => $countyId,
                                'name' => $item['name'],
                                'latitude' => $item['latitude'] ?? null,
                                'longitude' => $item['longitude'] ?? null,
                                'population' => $item['population'] ?? null,
                                'timezone' => $item['timezone'] ?? 'America/New_York',
                                'is_popular' => (bool) ($item['is_popular'] ?? false),
                                'is_active' => true,
                            ]
                        );
                        $stats['cities']++;
                        $bar->advance();
                    }
                });
                $bar->finish();
                $this->newLine();
            }
        }

        // Cache cities map [state_id . '_' . slug => id]
        $cityMap = [];
        foreach (City::select('id', 'state_id', 'slug')->get() as $ct) {
            $cityMap[$ct->state_id . '_' . $ct->slug] = $ct->id;
        }

        // 4. Postal ZIP Codes Import
        if (!$only || in_array('zips', $only)) {
            $zipsFile = $basePath . '/us_zip_codes.json';
            if (File::exists($zipsFile)) {
                $checksums['zips'] = hash_file('sha256', $zipsFile);
                $zipsData = json_decode(File::get($zipsFile), true) ?: [];
                $this->info("Importing " . count($zipsData) . " US Postal ZIP Codes...");
                $bar = $this->output->createProgressBar(count($zipsData));

                DB::transaction(function () use ($zipsData, $stateMap, $cityMap, $bar, &$stats) {
                    foreach ($zipsData as $item) {
                        $stateId = $stateMap[$item['state_code']] ?? null;
                        if (!$stateId) {
                            continue;
                        }

                        $cityId = $cityMap[$stateId . '_' . $item['city_slug']] ?? null;
                        if (!$cityId) {
                            continue;
                        }

                        ZipCode::updateOrCreate(
                            [
                                'zip_code' => $item['zip_code'],
                                'city_id' => $cityId,
                                'state_id' => $stateId,
                            ],
                            [
                                'latitude' => $item['latitude'] ?? null,
                                'longitude' => $item['longitude'] ?? null,
                            ]
                        );
                        $stats['zips']++;
                        $bar->advance();
                    }
                });
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

                DB::transaction(function () use ($aliasesData, $stateMap, $cityMap, $bar, &$stats) {
                    foreach ($aliasesData as $item) {
                        $stateId = $stateMap[$item['state_code']] ?? null;
                        if (!$stateId) {
                            continue;
                        }

                        $cityId = $cityMap[$stateId . '_' . $item['city_slug']] ?? null;
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
            'dataset_name' => $only ? implode(',', $only) : 'full_usa_suite',
            'version' => '2026.1',
            'source_file' => $basePath,
            'checksum' => md5(json_encode($checksums)),
            'records_processed' => $totalProcessed,
            'records_created' => $totalProcessed,
            'records_updated' => 0,
            'status' => 'completed',
            'duration_ms' => $durationMs,
            'notes' => "Imported {$stats['states']} states/territories, {$stats['counties']} counties, {$stats['cities']} cities, {$stats['zips']} ZIPs, and {$stats['aliases']} aliases.",
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
}
