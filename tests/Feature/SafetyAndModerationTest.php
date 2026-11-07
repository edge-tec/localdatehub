<?php

namespace Tests\Feature;

use App\Modules\Listings\DTOs\CreateListingData;
use App\Modules\Listings\Services\ListingService;
use App\Support\Exceptions\AgeVerificationException;
use App\Support\Exceptions\ProhibitedContentException;
use Tests\TestCase;

class SafetyAndModerationTest extends TestCase
{
    public function test_listing_service_rejects_underage_users(): void
    {
        $service = app(ListingService::class);

        $this->expectException(AgeVerificationException::class);

        $service->validateAndPrepare(new CreateListingData(
            userId: 1,
            title: 'Looking for a date',
            content: 'Friendly coffee date',
            categorySlug: 'casual-coffee-meets',
            cityName: 'Austin',
            latitude: 30.2672,
            longitude: -97.7431,
            age: 17 // Underage
        ));
    }

    public function test_listing_service_blocks_prohibited_commercial_solicitation(): void
    {
        $service = app(ListingService::class);

        $this->expectException(ProhibitedContentException::class);

        $service->validateAndPrepare(new CreateListingData(
            userId: 1,
            title: 'Discreet escort available tonight',
            content: 'Generous ppm hourly rate applies.',
            categorySlug: 'casual-coffee-meets',
            cityName: 'Austin',
            latitude: 30.2672,
            longitude: -97.7431,
            age: 25
        ));
    }

    public function test_compliant_dating_listing_is_fuzzed_and_prepared(): void
    {
        $service = app(ListingService::class);

        $prepared = $service->validateAndPrepare(new CreateListingData(
            userId: 1,
            title: 'Coffee and live music companion',
            content: 'Looking for someone who enjoys indie rock and good espresso.',
            categorySlug: 'activity-companions',
            cityName: 'Austin',
            latitude: 30.2672,
            longitude: -97.7431,
            age: 28
        ));

        $this->assertEquals('Coffee and live music companion', $prepared['title']);
        $this->assertEquals(28, $prepared['age']);
        $this->assertNotEmpty($prepared['fuzzed_latitude']);
        $this->assertNotEmpty($prepared['fuzzed_longitude']);
        // Assert coordinates were fuzzed (not identical to raw GPS)
        $this->assertNotEquals(30.2672, $prepared['fuzzed_latitude']);
    }
}
