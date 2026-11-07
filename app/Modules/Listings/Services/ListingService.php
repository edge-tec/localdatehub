<?php

namespace App\Modules\Listings\Services;

use App\Modules\Listings\DTOs\CreateListingData;
use App\Modules\Locations\DTOs\CoordinateDTO;
use App\Modules\Locations\Services\LocationService;
use App\Support\Exceptions\AgeVerificationException;
use App\Support\Exceptions\ProhibitedContentException;
use Illuminate\Support\Str;

class ListingService
{
    /**
     * Prohibited patterns covering commercial sex work, solicitation, trafficking keywords, or non-consensual activities.
     */
    protected array $prohibitedPatterns = [
        '/\b(escort|prostitut|ppm|pay\s*per\s*meet|hourly\s*rate|donation\s*for\s*time|full\s*service|happy\s*ending)\b/i',
        '/\b(incall|outcall|roses|compensation\s*required|sugar\s*baby|sugar\s*daddy)\b/i',
        '/\b(underage|teen|minor|barely\s*legal)\b/i',
    ];

    public function __construct(
        protected LocationService $locationService
    ) {}

    /**
     * Validate listing compliance and prepare listing payload.
     *
     * @param  CreateListingData  $data
     * @return array
     * @throws AgeVerificationException
     * @throws ProhibitedContentException
     */
    public function validateAndPrepare(CreateListingData $data): array
    {
        $minAge = config('localdatehub.safety.min_age', 18);
        if ($data->age < $minAge) {
            throw new AgeVerificationException("Listing poster must be at least {$minAge} years old.");
        }

        $fullText = $data->title . ' ' . $data->content;
        foreach ($this->prohibitedPatterns as $pattern) {
            if (preg_match($pattern, $fullText)) {
                throw new ProhibitedContentException(
                    'Your listing contains prohibited commercial terms or non-compliant language under LocalDateHub community standards.'
                );
            }
        }

        // Apply anti-triangulation location fuzzing to protect user safety
        $rawCoords = new CoordinateDTO(
            latitude: $data->latitude,
            longitude: $data->longitude,
            cityName: $data->cityName
        );
        $fuzzedCoords = $this->locationService->fuzzCoordinates($rawCoords);

        return [
            'user_id' => $data->userId,
            'title' => e($data->title),
            'slug' => Str::slug($data->title) . '-' . Str::random(6),
            'content' => e($data->content),
            'category_slug' => $data->categorySlug,
            'city_name' => e($data->cityName),
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'fuzzed_latitude' => $fuzzedCoords->latitude,
            'fuzzed_longitude' => $fuzzedCoords->longitude,
            'age' => $data->age,
            'intent' => $data->intent,
            'status' => 'active',
            'is_verified' => false,
            'created_at' => now(),
        ];
    }
}
