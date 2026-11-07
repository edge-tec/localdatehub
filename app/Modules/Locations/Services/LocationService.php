<?php

namespace App\Modules\Locations\Services;

use App\Modules\Locations\DTOs\CoordinateDTO;

class LocationService
{
    /**
     * Approximate radius of Earth in kilometers.
     */
    public const EARTH_RADIUS_KM = 6371.0;

    /**
     * Calculate spherical distance between two points using the Haversine formula.
     */
    public function calculateDistanceKm(CoordinateDTO $from, CoordinateDTO $to): float
    {
        $latFrom = deg2rad($from->latitude);
        $lonFrom = deg2rad($from->longitude);
        $latTo = deg2rad($to->latitude);
        $lonTo = deg2rad($to->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            sin($latDelta / 2) ** 2 +
            cos($latFrom) * cos($latTo) * (sin($lonDelta / 2) ** 2)
        ));

        return round($angle * self::EARTH_RADIUS_KM, 1);
    }

    /**
     * Apply random geographic jitter to coordinates (anti-triangulation protection).
     * Displaces point randomly by 300 to 800 meters.
     */
    public function fuzzCoordinates(CoordinateDTO $coordinates): CoordinateDTO
    {
        if (!config('localdatehub.geolocation.fuzzing_enabled', true)) {
            return $coordinates;
        }

        $minMeters = config('localdatehub.geolocation.fuzzing_jitter_meters.min', 300);
        $maxMeters = config('localdatehub.geolocation.fuzzing_jitter_meters.max', 800);
        $distanceKm = mt_rand($minMeters, $maxMeters) / 1000.0;
        $bearingRad = deg2rad(mt_rand(0, 360));

        $latRad = deg2rad($coordinates->latitude);
        $lonRad = deg2rad($coordinates->longitude);
        $angularDistance = $distanceKm / self::EARTH_RADIUS_KM;

        $newLatRad = asin(
            sin($latRad) * cos($angularDistance) +
            cos($latRad) * sin($angularDistance) * cos($bearingRad)
        );

        $newLonRad = $lonRad + atan2(
            sin($bearingRad) * sin($angularDistance) * cos($latRad),
            cos($angularDistance) - sin($latRad) * sin($newLatRad)
        );

        return new CoordinateDTO(
            latitude: rad2deg($newLatRad),
            longitude: rad2deg($newLonRad),
            cityName: $coordinates->cityName,
            state: $coordinates->state,
            country: $coordinates->country
        );
    }

    /**
     * Format distance for privacy-safe client rendering (distance bucketing).
     */
    public function formatDistanceForDisplay(float $distanceKm, bool $hideExactDistance = false): string
    {
        if ($hideExactDistance) {
            return 'Nearby';
        }

        if ($distanceKm < 1.0) {
            return 'Less than 1 km away';
        }

        $rounded = (int) round($distanceKm);
        return "~{$rounded} km away";
    }
}
