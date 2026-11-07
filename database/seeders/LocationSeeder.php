<?php

namespace Database\Seeders;

use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Models\ZipCode;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::firstOrCreate(
            ['code' => 'US'],
            [
                'name' => 'United States',
                'iso3' => 'USA',
                'phone_code' => '+1',
                'is_active' => true,
            ]
        );

        $statesData = [
            ['code' => 'AL', 'name' => 'Alabama'],
            ['code' => 'AK', 'name' => 'Alaska'],
            ['code' => 'AZ', 'name' => 'Arizona'],
            ['code' => 'AR', 'name' => 'Arkansas'],
            ['code' => 'CA', 'name' => 'California'],
            ['code' => 'CO', 'name' => 'Colorado'],
            ['code' => 'CT', 'name' => 'Connecticut'],
            ['code' => 'DE', 'name' => 'Delaware'],
            ['code' => 'DC', 'name' => 'District of Columbia'],
            ['code' => 'FL', 'name' => 'Florida'],
            ['code' => 'GA', 'name' => 'Georgia'],
            ['code' => 'HI', 'name' => 'Hawaii'],
            ['code' => 'ID', 'name' => 'Idaho'],
            ['code' => 'IL', 'name' => 'Illinois'],
            ['code' => 'IN', 'name' => 'Indiana'],
            ['code' => 'IA', 'name' => 'Iowa'],
            ['code' => 'KS', 'name' => 'Kansas'],
            ['code' => 'KY', 'name' => 'Kentucky'],
            ['code' => 'LA', 'name' => 'Louisiana'],
            ['code' => 'ME', 'name' => 'Maine'],
            ['code' => 'MD', 'name' => 'Maryland'],
            ['code' => 'MA', 'name' => 'Massachusetts'],
            ['code' => 'MI', 'name' => 'Michigan'],
            ['code' => 'MN', 'name' => 'Minnesota'],
            ['code' => 'MS', 'name' => 'Mississippi'],
            ['code' => 'MO', 'name' => 'Missouri'],
            ['code' => 'MT', 'name' => 'Montana'],
            ['code' => 'NE', 'name' => 'Nebraska'],
            ['code' => 'NV', 'name' => 'Nevada'],
            ['code' => 'NH', 'name' => 'New Hampshire'],
            ['code' => 'NJ', 'name' => 'New Jersey'],
            ['code' => 'NM', 'name' => 'New Mexico'],
            ['code' => 'NY', 'name' => 'New York'],
            ['code' => 'NC', 'name' => 'North Carolina'],
            ['code' => 'ND', 'name' => 'North Dakota'],
            ['code' => 'OH', 'name' => 'Ohio'],
            ['code' => 'OK', 'name' => 'Oklahoma'],
            ['code' => 'OR', 'name' => 'Oregon'],
            ['code' => 'PA', 'name' => 'Pennsylvania'],
            ['code' => 'RI', 'name' => 'Rhode Island'],
            ['code' => 'SC', 'name' => 'South Carolina'],
            ['code' => 'SD', 'name' => 'South Dakota'],
            ['code' => 'TN', 'name' => 'Tennessee'],
            ['code' => 'TX', 'name' => 'Texas'],
            ['code' => 'UT', 'name' => 'Utah'],
            ['code' => 'VT', 'name' => 'Vermont'],
            ['code' => 'VA', 'name' => 'Virginia'],
            ['code' => 'WA', 'name' => 'Washington'],
            ['code' => 'WV', 'name' => 'West Virginia'],
            ['code' => 'WI', 'name' => 'Wisconsin'],
            ['code' => 'WY', 'name' => 'Wyoming'],
        ];

        $stateModels = [];
        foreach ($statesData as $s) {
            $stateModels[$s['code']] = State::firstOrCreate(
                ['code' => $s['code']],
                ['country_id' => $country->id, 'name' => $s['name'], 'is_active' => true]
            );
        }

        // Major metropolitan cities across key states
        $citiesData = [
            // Texas
            ['state' => 'TX', 'name' => 'Austin', 'slug' => 'austin', 'lat' => 30.2672, 'lng' => -97.7431, 'pop' => 974447, 'zip' => '78701'],
            ['state' => 'TX', 'name' => 'Houston', 'slug' => 'houston', 'lat' => 29.7604, 'lng' => -95.3698, 'pop' => 2304580, 'zip' => '77002'],
            ['state' => 'TX', 'name' => 'Dallas', 'slug' => 'dallas', 'lat' => 32.7767, 'lng' => -96.7970, 'pop' => 1304379, 'zip' => '75201'],
            ['state' => 'TX', 'name' => 'San Antonio', 'slug' => 'san-antonio', 'lat' => 29.4241, 'lng' => -98.4936, 'pop' => 1434625, 'zip' => '78205'],
            // California
            ['state' => 'CA', 'name' => 'Los Angeles', 'slug' => 'los-angeles', 'lat' => 34.0522, 'lng' => -118.2437, 'pop' => 3898747, 'zip' => '90012'],
            ['state' => 'CA', 'name' => 'San Francisco', 'slug' => 'san-francisco', 'lat' => 37.7749, 'lng' => -122.4194, 'pop' => 873965, 'zip' => '94102'],
            ['state' => 'CA', 'name' => 'San Diego', 'slug' => 'san-diego', 'lat' => 32.7157, 'lng' => -117.1611, 'pop' => 1386932, 'zip' => '92101'],
            // New York
            ['state' => 'NY', 'name' => 'New York City', 'slug' => 'new-york-city', 'lat' => 40.7128, 'lng' => -74.0060, 'pop' => 8336817, 'zip' => '10001'],
            // Illinois
            ['state' => 'IL', 'name' => 'Chicago', 'slug' => 'chicago', 'lat' => 41.8781, 'lng' => -87.6298, 'pop' => 2746388, 'zip' => '60601'],
            // Florida
            ['state' => 'FL', 'name' => 'Miami', 'slug' => 'miami', 'lat' => 25.7617, 'lng' => -80.1918, 'pop' => 442241, 'zip' => '33101'],
            ['state' => 'FL', 'name' => 'Orlando', 'slug' => 'orlando', 'lat' => 28.5383, 'lng' => -81.3792, 'pop' => 307573, 'zip' => '32801'],
            // Washington
            ['state' => 'WA', 'name' => 'Seattle', 'slug' => 'seattle', 'lat' => 47.6062, 'lng' => -122.3321, 'pop' => 737015, 'zip' => '98101'],
            // Washington, DC
            ['state' => 'DC', 'name' => 'Washington', 'slug' => 'washington-dc', 'lat' => 38.9072, 'lng' => -77.0369, 'pop' => 689545, 'zip' => '20001'],
        ];

        foreach ($citiesData as $c) {
            $stateModel = $stateModels[$c['state']];
            $city = City::firstOrCreate(
                ['state_id' => $stateModel->id, 'slug' => $c['slug']],
                [
                    'name' => $c['name'],
                    'latitude' => $c['lat'],
                    'longitude' => $c['lng'],
                    'population' => $c['pop'],
                    'is_popular' => true,
                    'is_active' => true,
                ]
            );

            ZipCode::firstOrCreate(
                ['zip_code' => $c['zip']],
                [
                    'city_id' => $city->id,
                    'state_id' => $stateModel->id,
                    'latitude' => $c['lat'],
                    'longitude' => $c['lng'],
                ]
            );
        }
    }
}
