<?php

namespace Tests\Unit;

use App\Modules\Locations\DTOs\CoordinateDTO;
use App\Modules\Locations\Services\LocationService;
use Tests\TestCase;

class LocationServiceTest extends TestCase
{
    public function test_haversine_distance_calculation(): void
    {
        $service = new LocationService();

        // Austin, TX to San Antonio, TX (~118 km)
        $austin = new CoordinateDTO(30.2672, -97.7431);
        $sanAntonio = new CoordinateDTO(29.4241, -98.4936);

        $distance = $service->calculateDistanceKm($austin, $sanAntonio);

        $this->assertGreaterThan(110.0, $distance);
        $this->assertLessThan(125.0, $distance);
    }

    public function test_fuzz_coordinates_applies_offset(): void
    {
        $service = new LocationService();
        $exact = new CoordinateDTO(30.2672, -97.7431, 'Austin');

        $fuzzed = $service->fuzzCoordinates($exact);

        // Distance between raw and fuzzed should be within 0.2km and 1.2km
        $offsetKm = $service->calculateDistanceKm($exact, $fuzzed);

        $this->assertGreaterThan(0.2, $offsetKm);
        $this->assertLessThan(1.2, $offsetKm);
    }

    public function test_distance_formatting_bucketing(): void
    {
        $service = new LocationService();

        $this->assertEquals('Less than 1 km away', $service->formatDistanceForDisplay(0.4));
        $this->assertEquals('~5 km away', $service->formatDistanceForDisplay(4.8));
        $this->assertEquals('Nearby', $service->formatDistanceForDisplay(3.0, hideExactDistance: true));
    }
}
