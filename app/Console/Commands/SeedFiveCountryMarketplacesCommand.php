<?php

namespace App\Console\Commands;

use App\Modules\Locations\Services\FiveCountryGeographicSeederService;
use Illuminate\Console\Command;

class SeedFiveCountryMarketplacesCommand extends Command
{
    protected $signature = 'app:seed-five-country-marketplaces {--country= : Optional 2-letter ISO code (GB, CA, AU, IE, NZ)}';

    protected $description = 'Seed or update verified geographic locations, currencies, and categories for the 5 country marketplaces (GB, CA, AU, IE, NZ).';

    public function handle(FiveCountryGeographicSeederService $seeder): int
    {
        $only = $this->option('country') ? (string) $this->option('country') : null;
        $summary = $seeder->seedAll($only);

        $rows = [];
        foreach ($summary['countries'] as $code => $info) {
            $rows[] = [
                $code,
                $info['name'],
                '/' . $info['slug'],
                $info['currency'],
                $info['areas'],
                $info['cities'],
            ];
        }

        $this->table(['ISO', 'Country', 'URL', 'Currency', 'Areas', 'Cities'], $rows);
        $this->info("Seeded {$summary['countries_seeded']} countries, {$summary['areas_seeded']} administrative areas, {$summary['cities_seeded']} cities.");

        return self::SUCCESS;
    }
}

