<?php

namespace App\Modules\Locations\Services;

use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\DTOs\CoordinateDTO;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\LocationAlias;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Models\ZipCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocationService
{
    /**
     * Approximate radius of Earth in kilometers and miles.
     */
    public const EARTH_RADIUS_KM = 6371.0;
    public const EARTH_RADIUS_MILES = 3958.8;

    /**
     * Calculate spherical distance between two coordinate pairs using the Haversine formula.
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
     * Calculate distance between two coordinate pairs in miles.
     */
    public function calculateDistanceMiles(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            sin($latDelta / 2) ** 2 +
            cos($latFrom) * cos($latTo) * (sin($lonDelta / 2) ** 2)
        ));

        return round($angle * self::EARTH_RADIUS_MILES, 1);
    }

    /**
     * Compute latitude and longitude bounding box for a given origin and radius in kilometers.
     *
     * @return array{minLat: float, maxLat: float, minLng: float, maxLng: float}
     */
    public function calculateBoundingBox(CoordinateDTO $origin, float $radiusKm): array
    {
        // 1 degree latitude ~= 111.045 km
        $latDelta = $radiusKm / 111.045;
        $cosLat = cos(deg2rad($origin->latitude));
        $lngDelta = $radiusKm / (111.045 * max(abs($cosLat), 0.0001));

        return [
            'minLat' => $origin->latitude - $latDelta,
            'maxLat' => $origin->latitude + $latDelta,
            'minLng' => $origin->longitude - $lngDelta,
            'maxLng' => $origin->longitude + $lngDelta,
        ];
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

    /**
     * Normalize colloquial names, punctuation, and common abbreviations.
     */
    public function normalizeLocationQuery(string $input): string
    {
        $normalized = trim($input);

        // Common abbreviation expansions (before stripping punctuation)
        $abbreviations = [
            '/\bnyc\b/i' => 'New York City',
            '/\bla\b/i' => 'Los Angeles',
            '/\bsf\b/i' => 'San Francisco',
            '/\bdfw\b/i' => 'Dallas',
            '/\batx\b/i' => 'Austin',
            '/\bphilly\b/i' => 'Philadelphia',
            '/\bst\.?\s+/i' => 'Saint ',
            '/\bft\.?\s+/i' => 'Fort ',
            '/\bmt\.?\s+/i' => 'Mount ',
        ];

        foreach ($abbreviations as $pattern => $replacement) {
            $normalized = preg_replace($pattern, $replacement, $normalized);
        }

        // Strip remaining non-alphanumeric except spaces, commas, and hyphens
        $normalized = preg_replace('/[^\w\s,\-]/u', '', $normalized);

        return trim($normalized);
    }

    /**
     * Fast, multi-tier location autocomplete search.
     * Matches across ZIP codes, Cities, States, and Metropolitan Aliases.
     *
     * @return Collection<int, array{type: string, title: string, subtitle: string, city_id: int|null, state_code: string, city_slug: string|null, zip_code: string|null, latitude: float|null, longitude: float|null, score: int}>
     */
    public function searchAutocomplete(string $query, int $limit = 10): Collection
    {
        $cleanQuery = trim($query);
        if (strlen($cleanQuery) < 2) {
            return collect();
        }

        $normalized = $this->normalizeLocationQuery($cleanQuery);
        $results = collect();

        // 1. Check if input is a 5-digit ZIP code prefix or exact match
        if (is_numeric($cleanQuery)) {
            $zips = ZipCode::with(['city.state'])
                ->where('zip_code', 'like', "{$cleanQuery}%")
                ->limit($limit)
                ->get();

            foreach ($zips as $z) {
                if (!$z->city || !$z->city->state) {
                    continue;
                }
                $isExact = ($z->zip_code === $cleanQuery);
                $results->push([
                    'type' => 'zip',
                    'title' => "{$z->zip_code} — {$z->city->name}, {$z->city->state->code}",
                    'subtitle' => "Postal Code in {$z->city->name}, {$z->city->state->name}",
                    'city_id' => $z->city->id,
                    'state_code' => $z->city->state->code,
                    'city_slug' => $z->city->slug,
                    'zip_code' => $z->zip_code,
                    'latitude' => $z->latitude ?? $z->city->latitude,
                    'longitude' => $z->longitude ?? $z->city->longitude,
                    'score' => $isExact ? 100 : 85,
                ]);
            }
        }

        // 2. Search Metropolitan & Colloquial Aliases (e.g. "Silicon Valley", "DFW", "Twin Cities")
        $aliases = LocationAlias::with(['city.state'])
            ->where('alias', 'like', "%{$normalized}%")
            ->orWhere('alias', 'like', "%{$cleanQuery}%")
            ->limit($limit)
            ->get();

        foreach ($aliases as $alias) {
            if (!$alias->city || !$alias->city->state) {
                continue;
            }
            $isExact = (strcasecmp($alias->alias, $cleanQuery) === 0 || strcasecmp($alias->alias, $normalized) === 0);
            $results->push([
                'type' => 'alias',
                'title' => "{$alias->alias} ({$alias->city->name}, {$alias->city->state->code})",
                'subtitle' => ucfirst(str_replace('_', ' ', $alias->alias_type)) . " in {$alias->city->state->name}",
                'city_id' => $alias->city->id,
                'state_code' => $alias->city->state->code,
                'city_slug' => $alias->city->slug,
                'zip_code' => null,
                'latitude' => $alias->city->latitude,
                'longitude' => $alias->city->longitude,
                'score' => $isExact ? 95 : 75,
            ]);
        }

        // 3. Search Cities by Name
        $cities = City::with('state')
            ->where('is_active', true)
            ->where(function ($q) use ($normalized, $cleanQuery) {
                $q->where('name', 'like', "{$normalized}%")
                  ->orWhere('name', 'like', "{$cleanQuery}%")
                  ->orWhere('name', 'like', "%{$normalized}%");
            })
            ->orderBy('is_popular', 'desc')
            ->orderBy('population', 'desc')
            ->limit($limit)
            ->get();

        foreach ($cities as $city) {
            if (!$city->state) {
                continue;
            }
            $isExact = (strcasecmp($city->name, $cleanQuery) === 0 || strcasecmp($city->name, $normalized) === 0);
            $isPrefix = Str::startsWith(strtolower($city->name), strtolower($normalized));

            $results->push([
                'type' => 'city',
                'title' => "{$city->name}, {$city->state->code}",
                'subtitle' => "City in {$city->state->name}" . ($city->population ? " • Pop. " . number_format($city->population) : ''),
                'city_id' => $city->id,
                'state_code' => $city->state->code,
                'city_slug' => $city->slug,
                'zip_code' => null,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
                'score' => $isExact ? 90 : ($isPrefix ? 80 : 60),
            ]);
        }

        // 4. Search States by Code or Name
        if (strlen($cleanQuery) <= 20) {
            $states = State::where('is_active', true)
                ->where(function ($q) use ($cleanQuery) {
                    $q->where('code', strtoupper($cleanQuery))
                      ->orWhere('name', 'like', "{$cleanQuery}%");
                })
                ->limit(5)
                ->get();

            foreach ($states as $st) {
                $isExactCode = (strtoupper($cleanQuery) === $st->code);
                $results->push([
                    'type' => 'state',
                    'title' => "{$st->name} ({$st->code})",
                    'subtitle' => "State Directory (Browse All Cities)",
                    'city_id' => null,
                    'state_code' => $st->code,
                    'city_slug' => null,
                    'zip_code' => null,
                    'latitude' => null,
                    'longitude' => null,
                    'score' => $isExactCode ? 85 : 50,
                ]);
            }
        }

        // Deduplicate and rank by score descending
        return $results
            ->unique(fn ($item) => $item['type'] . '_' . ($item['city_id'] ?? '') . '_' . ($item['zip_code'] ?? '') . '_' . $item['state_code'])
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * Search localities mapped to a given 5-digit ZIP code.
     * Supports multiple locality relationships (a ZIP code spanning multiple cities).
     */
    public function lookupZipCode(string $zip): Collection
    {
        $cleanZip = trim($zip);

        return ZipCode::with(['city.state', 'city.county'])
            ->where('zip_code', $cleanZip)
            ->get()
            ->map(function ($record) {
                return [
                    'zip_code' => $record->zip_code,
                    'city_id' => $record->city_id,
                    'city_name' => $record->city?->name,
                    'city_slug' => $record->city?->slug,
                    'county_name' => $record->city?->county?->name,
                    'state_code' => $record->state?->code ?? $record->city?->state?->code,
                    'state_name' => $record->state?->name ?? $record->city?->state?->name,
                    'latitude' => $record->latitude ?? $record->city?->latitude,
                    'longitude' => $record->longitude ?? $record->city?->longitude,
                ];
            });
    }

    /**
     * Find nearby cities within a radius using Haversine formula and bounding-box optimization.
     *
     * @return Collection<int, City>
     */
    public function findNearbyCities(float $latitude, float $longitude, float $radiusMiles = 35.0, int $limit = 15): Collection
    {
        // 1 degree latitude ~= 69 miles
        $latDelta = $radiusMiles / 69.0;
        $cosLat = cos(deg2rad($latitude));
        $lngDelta = $radiusMiles / (69.0 * max(abs($cosLat), 0.0001));

        $minLat = $latitude - $latDelta;
        $maxLat = $latitude + $latDelta;
        $minLng = $longitude - $lngDelta;
        $maxLng = $longitude + $lngDelta;

        // Bounding box query + distance calculation
        $candidates = City::with('state')
            ->where('is_active', true)
            ->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng])
            ->get();

        return $candidates
            ->map(function ($city) use ($latitude, $longitude) {
                $city->distance_miles = $this->calculateDistanceMiles(
                    $latitude,
                    $longitude,
                    $city->latitude,
                    $city->longitude
                );
                return $city;
            })
            ->filter(fn ($c) => $c->distance_miles <= $radiusMiles && $c->distance_miles > 0.1)
            ->sortBy([
                ['is_popular', 'desc'],
                ['distance_miles', 'asc'],
            ])
            ->take($limit)
            ->values();
    }

    /**
     * Find active listings within a geographic radius.
     */
    public function findNearbyListings(float $latitude, float $longitude, float $radiusMiles = 25.0, ?int $categoryId = null, int $limit = 20): Collection
    {
        $latDelta = $radiusMiles / 69.0;
        $cosLat = cos(deg2rad($latitude));
        $lngDelta = $radiusMiles / (69.0 * max(abs($cosLat), 0.0001));

        $minLat = $latitude - $latDelta;
        $maxLat = $latitude + $latDelta;
        $minLng = $longitude - $lngDelta;
        $maxLng = $longitude + $lngDelta;

        $query = Listing::with(['category', 'state', 'city', 'location', 'primaryPhoto'])
            ->active()
            ->whereHas('location', function ($q) use ($minLat, $maxLat, $minLng, $maxLng) {
                $q->whereBetween('latitude', [$minLat, $maxLat])
                  ->whereBetween('longitude', [$minLng, $maxLng]);
            });

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $listings = $query->limit(100)->get();

        return $listings
            ->map(function ($listing) use ($latitude, $longitude) {
                $loc = $listing->location;
                if ($loc) {
                    $listing->calculated_distance_miles = $this->calculateDistanceMiles(
                        $latitude,
                        $longitude,
                        $loc->latitude,
                        $loc->longitude
                    );
                } else {
                    $listing->calculated_distance_miles = 999.0;
                }
                return $listing;
            })
            ->filter(fn ($l) => $l->calculated_distance_miles <= $radiusMiles)
            ->sortBy('calculated_distance_miles')
            ->take($limit)
            ->values();
    }

    /**
     * Get full directory of US states with city counts.
     */
    public function getStateDirectory(): Collection
    {
        return State::where('is_active', true)
            ->withCount(['cities' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get State landing page data with cities and active counts.
     */
    public function getStateWithCities(string $stateCode): ?State
    {
        return State::where('is_active', true)
            ->where(function ($q) use ($stateCode) {
                if (is_numeric($stateCode)) {
                    $q->where('id', (int) $stateCode);
                } else {
                    $q->where('code', strtoupper($stateCode))
                      ->orWhere('slug', strtolower($stateCode));
                }
            })
            ->with([
                'cities' => fn ($q) => $q->where('is_active', true)
                    ->where(function ($sub) {
                        $sub->whereNull('city_type')
                            ->orWhere('city_type', 'city');
                    })
                    ->orderBy('is_popular', 'desc')
                    ->orderBy('name', 'asc'),
                'counties',
            ])
            ->withCount(['cities' => fn ($q) => $q->where('is_active', true)->where(function ($sub) {
                $sub->whereNull('city_type')
                    ->orWhere('city_type', 'city');
            })])
            ->first();
    }

    /**
     * Get City landing page data with state, ZIP codes, aliases, and nearby cities.
     */
    public function getCityWithDetails(string $stateCode, string $citySlug): ?City
    {
        $state = State::where('code', strtoupper($stateCode))
            ->orWhere('slug', strtolower($stateCode))
            ->first();
        if (!$state) {
            return null;
        }

        $city = City::where('state_id', $state->id)
            ->where('slug', $citySlug)
            ->where('is_active', true)
            ->with(['state', 'county', 'zipCodes', 'aliases'])
            ->withCount(['listings' => fn ($q) => $q->active()])
            ->first();

        if ($city && $city->latitude && $city->longitude) {
            $city->nearby_cities = $this->findNearbyCities($city->latitude, $city->longitude, 85.0, 8);
        }

        return $city;
    }
}
