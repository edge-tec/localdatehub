<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Services\LocationService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationApiController extends Controller
{
    public function __construct(
        protected LocationService $locationService
    ) {}

    /**
     * List all supported US states.
     */
    public function indexStates(): JsonResponse
    {
        $states = $this->locationService->getStateDirectory();

        return ApiResponse::success([
            'count' => $states->count(),
            'states' => $states->map(fn ($s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'cities_count' => $s->cities_count,
            ]),
        ]);
    }

    /**
     * List all active cities in a given state.
     */
    public function stateCities(string $stateCode): JsonResponse
    {
        $state = $this->locationService->getStateWithCities($stateCode);
        if (!$state) {
            return ApiResponse::error("State [{$stateCode}] not found or inactive.", 404, 'STATE_NOT_FOUND');
        }

        return ApiResponse::success([
            'state' => [
                'code' => $state->code,
                'name' => $state->name,
            ],
            'cities_count' => $state->cities->count(),
            'cities' => $state->cities->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'latitude' => $c->latitude,
                'longitude' => $c->longitude,
                'population' => $c->population,
                'is_popular' => $c->is_popular,
            ]),
        ]);
    }

    /**
     * Get detailed information for a single city.
     */
    public function cityDetails(int $cityId): JsonResponse
    {
        $city = City::with(['state', 'county', 'zipCodes', 'aliases'])->find($cityId);
        if (!$city) {
            return ApiResponse::error("City ID [{$cityId}] not found.", 404, 'CITY_NOT_FOUND');
        }

        return ApiResponse::success([
            'id' => $city->id,
            'name' => $city->name,
            'slug' => $city->slug,
            'state' => [
                'code' => $city->state?->code,
                'name' => $city->state?->name,
            ],
            'county' => $city->county?->name,
            'latitude' => $city->latitude,
            'longitude' => $city->longitude,
            'population' => $city->population,
            'timezone' => $city->timezone,
            'is_popular' => $city->is_popular,
            'zip_codes' => $city->zipCodes->pluck('zip_code'),
            'aliases' => $city->aliases->map(fn ($a) => [
                'alias' => $a->alias,
                'type' => $a->alias_type,
            ]),
        ]);
    }

    /**
     * Search localities mapped to a given 5-digit ZIP code.
     * Supports multiple locality relationships (one ZIP code to multiple cities).
     */
    public function lookupZip(string $zip): JsonResponse
    {
        if (!preg_match('/^\d{5}$/', $zip)) {
            return ApiResponse::error("ZIP code must be a 5-digit numeric string.", 422, 'INVALID_ZIP_FORMAT');
        }

        $localities = $this->locationService->lookupZipCode($zip);
        if ($localities->isEmpty()) {
            return ApiResponse::error("No localities found for ZIP code [{$zip}].", 404, 'ZIP_NOT_FOUND');
        }

        return ApiResponse::success([
            'zip_code' => $zip,
            'localities_count' => $localities->count(),
            'localities' => $localities,
        ]);
    }

    /**
     * Fast autocomplete endpoint matching Cities, ZIPs, States, and Aliases.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');
        $limit = min((int) $request->query('limit', 10), 25);

        $results = $this->locationService->searchAutocomplete($query, $limit);

        return ApiResponse::success([
            'query' => $query,
            'count' => $results->count(),
            'results' => $results,
        ]);
    }

    /**
     * Search cities by name, optionally filtered by state code or slug.
     */
    public function searchCities(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $stateParam = $request->query('state');
        $limit = min((int) $request->query('limit', 20), 100);

        $cityQuery = City::with('state')->where('is_active', true);

        if ($stateParam) {
            $cityQuery->whereHas('state', function ($q) use ($stateParam) {
                if (is_numeric($stateParam)) {
                    $q->where('id', (int) $stateParam);
                } else {
                    $q->where('code', strtoupper($stateParam))
                      ->orWhere('slug', strtolower($stateParam));
                }
            });
        }

        if ($query !== '') {
            $cityQuery->where('name', 'like', "{$query}%");
        }

        $cities = $cityQuery->orderBy('is_popular', 'desc')
            ->orderBy('name', 'asc')
            ->take($limit)
            ->get();

        return ApiResponse::success([
            'query' => $query,
            'state' => $stateParam,
            'count' => $cities->count(),
            'cities' => $cities->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'state_code' => $c->state?->code,
                'state_name' => $c->state?->name,
                'latitude' => $c->latitude,
                'longitude' => $c->longitude,
                'is_popular' => (bool) $c->is_popular,
            ]),
        ]);
    }

    /**
     * Radius geographic search for nearby cities.
     */
    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:250'],
        ]);

        $lat = (float) $request->query('lat');
        $lng = (float) $request->query('lng');
        $radius = (float) ($request->query('radius') ?: 35.0);

        $cities = $this->locationService->findNearbyCities($lat, $lng, $radius);

        return ApiResponse::success([
            'center' => [
                'latitude' => $lat,
                'longitude' => $lng,
            ],
            'radius_miles' => $radius,
            'count' => $cities->count(),
            'cities' => $cities->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'state_code' => $c->state?->code,
                'latitude' => $c->latitude,
                'longitude' => $c->longitude,
                'distance_miles' => $c->distance_miles,
            ]),
        ]);
    }
}

