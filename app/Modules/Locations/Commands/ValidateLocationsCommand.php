<?php

namespace App\Modules\Locations\Commands;

use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\County;
use App\Modules\Locations\Models\LocationAlias;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Models\ZipCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateLocationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'locations:validate';

    /**
     * The console command description.
     */
    protected $description = 'Perform exhaustive data integrity and geographic boundary validation on location tables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("=================================================");
        $this->info(" LocalDateHub — Location Integrity Audit Suite");
        $this->info("=================================================");

        $errors = [];
        $warnings = [];
        $checks = [];

        // Check 1: 50 States + DC coverage
        $stateCount = State::where('is_active', true)->count();
        if ($stateCount < 51) {
            $errors[] = "Expected at least 51 active US States + DC, found: {$stateCount}";
            $checks[] = ['US States & DC Representation', 'FAIL', "Found {$stateCount}/51 entities"];
        } else {
            $checks[] = ['US States & DC Representation', 'PASS', "{$stateCount} active entities verified"];
        }

        // Check 2: City Coordinate Range Validation (-90 <= lat <= 90, -180 <= lng <= 180)
        $invalidCityCoords = City::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(function ($q) {
                $q->where('latitude', '<', -90)
                  ->orWhere('latitude', '>', 90)
                  ->orWhere('longitude', '<', -180)
                  ->orWhere('longitude', '>', 180);
            })->count();

        if ($invalidCityCoords > 0) {
            $errors[] = "Found {$invalidCityCoords} cities with out-of-bounds WGS84 coordinates.";
            $checks[] = ['City Coordinate Boundaries', 'FAIL', "{$invalidCityCoords} invalid coordinates"];
        } else {
            $checks[] = ['City Coordinate Boundaries', 'PASS', "100% of city coordinates within [-90, 90] & [-180, 180]"];
        }

        // Check 3: ZIP Code Coordinate Range Validation
        $invalidZipCoords = ZipCode::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(function ($q) {
                $q->where('latitude', '<', -90)
                  ->orWhere('latitude', '>', 90)
                  ->orWhere('longitude', '<', -180)
                  ->orWhere('longitude', '>', 180);
            })->count();

        if ($invalidZipCoords > 0) {
            $errors[] = "Found {$invalidZipCoords} ZIP codes with out-of-bounds coordinates.";
            $checks[] = ['ZIP Coordinate Boundaries', 'FAIL', "{$invalidZipCoords} invalid coordinates"];
        } else {
            $checks[] = ['ZIP Coordinate Boundaries', 'PASS', "100% of ZIP centroids within valid boundaries"];
        }

        // Check 4: 5-digit postal code pattern verification
        $allZips = ZipCode::pluck('zip_code')->toArray();
        $malformedZips = 0;
        foreach ($allZips as $zip) {
            if (!preg_match('/^\d{5}$/', $zip)) {
                $malformedZips++;
            }
        }

        if ($malformedZips > 0) {
            $errors[] = "Found {$malformedZips} non-conforming ZIP codes (must be exactly 5 numeric digits).";
            $checks[] = ['Postal Code Format (5-digit numeric)', 'FAIL', "{$malformedZips} malformed ZIP codes"];
        } else {
            $checks[] = ['Postal Code Format (5-digit numeric)', 'PASS', "All " . count($allZips) . " ZIP codes match ^\\d{5}$ pattern"];
        }

        // Check 5: Orphaned Cities (State FK missing)
        $validStateIds = State::pluck('id')->toArray();
        $orphanedCities = City::whereNotIn('state_id', $validStateIds)->count();
        if ($orphanedCities > 0) {
            $errors[] = "Found {$orphanedCities} orphaned cities referencing non-existent states.";
            $checks[] = ['Referential Integrity (Cities -> States)', 'FAIL', "{$orphanedCities} orphaned records"];
        } else {
            $checks[] = ['Referential Integrity (Cities -> States)', 'PASS', "Zero orphaned cities"];
        }

        // Check 6: Orphaned ZIP Codes (City FK or State FK missing)
        $validCityIds = City::pluck('id')->toArray();
        $orphanedZips = ZipCode::whereNotIn('city_id', $validCityIds)
            ->orWhereNotIn('state_id', $validStateIds)
            ->count();

        if ($orphanedZips > 0) {
            $errors[] = "Found {$orphanedZips} orphaned ZIP codes referencing non-existent cities or states.";
            $checks[] = ['Referential Integrity (ZIPs -> Cities/States)', 'FAIL', "{$orphanedZips} orphaned records"];
        } else {
            $checks[] = ['Referential Integrity (ZIPs -> Cities/States)', 'PASS', "Zero orphaned ZIP codes"];
        }

        // Check 7: Multi-Locality ZIP Code Audit (Supporting real-world 1-to-many relationships)
        $multiCityZips = DB::table('zip_codes')
            ->select('zip_code', DB::raw('count(distinct city_id) as city_count'))
            ->groupBy('zip_code')
            ->having('city_count', '>', 1)
            ->get();

        $checks[] = [
            'Multi-Locality ZIP Relationships',
            'INFO',
            $multiCityZips->count() . ' ZIP codes successfully mapping across multiple municipal localities',
        ];

        // Check 8: Location Aliases Integrity
        $orphanedAliases = LocationAlias::whereNotIn('city_id', $validCityIds)->count();
        if ($orphanedAliases > 0) {
            $errors[] = "Found {$orphanedAliases} orphaned aliases referencing non-existent cities.";
            $checks[] = ['Referential Integrity (Aliases -> Cities)', 'FAIL', "{$orphanedAliases} orphaned aliases"];
        } else {
            $aliasCount = LocationAlias::count();
            $checks[] = ['Referential Integrity (Aliases -> Cities)', 'PASS', "{$aliasCount} aliases active and linked"];
        }

        // Print Results Table
        $this->table(['Integrity Check Component', 'Status', 'Diagnostic Details'], $checks);

        if (!empty($errors)) {
            $this->newLine();
            $this->error("Validation failed with " . count($errors) . " error(s):");
            foreach ($errors as $e) {
                $this->line("  ✗ {$e}");
            }
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info("✓ Location Engine Dataset Integrity Audit Passed 100%!");
        return Command::SUCCESS;
    }
}

