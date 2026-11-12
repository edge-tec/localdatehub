<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Services\CountryMarketplaceService;
use App\Modules\Locations\Services\LocationService;
use App\Services\GeoLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationApiController extends Controller
{
    public function __construct(
        private readonly LocationService $locationService,
        private readonly CountryMarketplaceService $countryMarketplaceService,
        private readonly GeoLocationService $geoLocationService
    ) {}

    /**
     * List the 5 supported marketplace countries with currency, flag, and counts.
     */
    public function indexCountries(): JsonResponse
    {
        $countries = $this->countryMarketplaceService->getSupportedCountries(true);

        return response()->json([
            'status' => 'ok',
            'data' => $countries->map(fn ($country) => [
                'id' => $country->id,
                'name' => $country->name,
                'code' => $country->code,
                'slug' => $country->slug ?: strtolower($country->code),
                'currency_code' => $country->currency_code,
                'currency_symbol' => $country->currency_symbol,
                'flag' => $country->flag,
                'division_label' => $country->division_label,
                'areas_count' => (int) ($country->areas_count ?? 0),
                'cities_count' => (int) ($country->cities_count ?? 0),
                'active_listings_count' => (int) ($country->active_listings_count ?? 0),
                'url' => url('/' . ($country->slug ?: strtolower($country->code))),
            ])->values(),
        ]);
    }

    /**
     * List all administrative areas (states/provinces/regions/counties/nations) for a country.
     */
    public function countryAreas(string $countryCode): JsonResponse
    {
        $country = $this->countryMarketplaceService->getCountryBySlugOrCode($countryCode, false);
        if (! $country) {
            return response()->json(['status' => 'error', 'message' => 'Country not found.'], 404);
        }

        $areas = $this->countryMarketplaceService->getCountryAreas($country);
        $countrySlug = strtolower($country->slug ?: $country->code);

        return response()->json([
            'status' => 'ok',
            'country' => [
                'id' => $country->id,
                'name' => $country->name,
                'code' => $country->code,
                'slug' => $countrySlug,
                'currency_code' => $country->currency_code,
                'currency_symbol' => $country->currency_symbol,
                'division_label' => $country->division_label,
            ],
            'data' => $areas->map(fn ($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'code' => $area->code,
                'slug' => $area->slug,
                'administrative_level' => $area->administrative_level,
                'parent' => $area->parent ? [
                    'id' => $area->parent->id,
                    'name' => $area->parent->name,
                    'code' => $area->parent->code,
                    'slug' => $area->parent->slug,
                ] : null,
                'cities_count' => (int) ($area->cities_count ?? 0),
                'active_listings_count' => (int) ($area->active_listings_count ?? 0),
                'url' => url('/' . $countrySlug . '/' . $area->slug),
            ])->values(),
        ]);
    }

    /**
     * List all active cities inside a specific country's administrative area.
     */
    public function countryAreaCities(string $countryCode, string $areaCode): JsonResponse
    {
        $country = $this->countryMarketplaceService->getCountryBySlugOrCode($countryCode, false);
        if (! $country) {
            return response()->json(['status' => 'error', 'message' => 'Country not found.'], 404);
        }

        $area = $this->countryMarketplaceService->getCountryArea($country, $areaCode);
        if (! $area) {
            return response()->json(['status' => 'error', 'message' => 'Administrative area not found in ' . $country->name . '.'], 404);
        }

        $countrySlug = strtolower($country->slug ?: $country->code);

        return response()->json([
            'status' => 'ok',
            'country' => [
                'id' => $country->id,
                'name' => $country->name,
                'code' => $country->code,
                'slug' => $countrySlug,
            ],
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
                'code' => $area->code,
                'slug' => $area->slug,
                'administrative_level' => $area->administrative_level,
            ],
            'data' => $area->cities->map(fn ($city) => [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'county_name' => $city->county_name,
                'population' => $city->population,
                'is_major_city' => (bool) $city->is_major_city,
                'active_listings_count' => (int) ($city->active_listings_count ?? 0),
                'url' => url('/' . $countrySlug . '/' . $area->slug . '/' . $city->slug),
            ])->values(),
        ]);
    }

    /**
     * Detect visitor's country and marketplace routing decision via GeoIP / manual selection.
     */
    public function detectCountry(Request $request): JsonResponse
    {
        $geo = $this->geoLocationService->detectLocation($request);
        $decision = $this->geoLocationService->resolveMarketplaceDecision($request);

        return response()->json([
            'status' => 'ok',
            'geolocation' => $geo,
            'marketplace' => $decision,
        ]);
    }

    public function indexStates(Request $request): JsonResponse
    {
        $countryParam = $request->query('country');
        if (is_string($countryParam) && trim($countryParam) !== '') {
            return $this->countryAreas($countryParam);
        }

        $region = $request->query('region');
        $states = $this->locationService->getStateDirectory(is_string($region) ? $region : null);

        return response()->json([
            'status' => 'ok',
            'data' => $states->map(fn ($state) => [
                'id' => $state->id,
                'name' => $state->name,
                'code' => $state->code,
                'slug' => $state->slug,
                'region' => $state->region,
                'capital_city' => $state->capital_city,
                'cities_count' => (int) ($state->cities_count ?? 0),
                'active_listings_count' => (int) ($state->active_listings_count ?? 0),
                'url' => route('locations.state', ['stateCode' => strtolower($state->code)]),
            ])->values(),
        ]);
    }

    public function showState(string $stateCode): JsonResponse
    {
        $state = $this->locationService->getStateWithCities($stateCode);
        if (! $state) {
            return response()->json(['status' => 'error', 'message' => 'State not found.'], 404);
        }

        return response()->json([
            'status' => 'ok',
            'data' => [
                'id' => $state->id,
                'name' => $state->name,
                'code' => $state->code,
                'slug' => $state->slug,
                'region' => $state->region,
                'capital_city' => $state->capital_city,
                'active_listings_count' => (int) ($state->active_listings_count ?? 0),
                'cities' => $state->cities->map(fn ($city) => [
                    'id' => $city->id,
                    'name' => $city->name,
                    'slug' => $city->slug,
                    'county_name' => $city->county_name,
                    'population' => $city->population,
                    'is_major_city' => (bool) $city->is_major_city,
                    'active_listings_count' => (int) ($city->active_listings_count ?? 0),
                    'url' => route('locations.city', [
                        'stateCode' => strtolower($state->code),
                        'citySlug' => $city->slug,
                    ]),
                ])->values(),
            ],
        ]);
    }

    public function showCity(string $stateCode, string $citySlug): JsonResponse
    {
        $result = $this->locationService->getCityWithDetails($stateCode, $citySlug);
        if (! $result) {
            return response()->json(['status' => 'error', 'message' => 'City not found.'], 404);
        }

        $state = $result['state'];
        $city = $result['city'];

        return response()->json([
            'status' => 'ok',
            'data' => [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'state' => [
                    'name' => $state->name,
                    'code' => $state->code,
                    'region' => $state->region,
                ],
                'county_name' => $city->county_name,
                'population' => $city->population,
                'timezone' => $city->timezone,
                'postal_codes' => $city->postal_codes,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
                'active_listings_count' => (int) ($city->active_listings_count ?? 0),
            ],
        ]);
    }

    public function nearbyCities(Request $request, string $stateCode, string $citySlug): JsonResponse
    {
        $result = $this->locationService->getCityWithDetails($stateCode, $citySlug);
        if (! $result) {
            return response()->json(['status' => 'error', 'message' => 'City not found.'], 404);
        }

        $radius = max(5, min(250, (int) $request->query('radius_miles', 60)));
        $nearby = $this->locationService->getNearbyCities($result['city'], $radius, 12);

        return response()->json([
            'status' => 'ok',
            'origin' => [
                'city' => $result['city']->name,
                'state_code' => $result['state']->code,
            ],
            'radius_miles' => $radius,
            'data' => $nearby->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'state_code' => $c->state?->code,
                'distance_miles' => $c->distance_miles,
                'active_listings_count' => (int) ($c->active_listings_count ?? 0),
            ])->values(),
        ]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $limit = max(1, min(20, (int) $request->query('limit', 10)));
        $countryCode = $request->query('country');

        if (is_string($countryCode) && trim($countryCode) !== '') {
            $country = $this->countryMarketplaceService->getCountryBySlugOrCode($countryCode, false);
            if ($country) {
                return $this->countryAutocomplete($country, $query, $limit);
            }
        }

        return response()->json([
            'status' => 'ok',
            'query' => $query,
            'results' => $this->locationService->autocomplete($query, $limit),
        ]);
    }

    private function countryAutocomplete($country, string $query, int $limit): JsonResponse
    {
        $q = trim($query);
        if (mb_strlen($q) < 2) {
            return response()->json(['status' => 'ok', 'query' => $query, 'results' => []]);
        }

        $countrySlug = strtolower($country->slug ?: $country->code);
        $results = [];

        $areas = State::query()
            ->where('country_id', $country->id)
            ->where('is_active', true)
            ->where(function ($sub) use ($q) {
                $sub->where('name', 'like', $q . '%')
                    ->orWhere('code', 'like', strtoupper($q) . '%');
            })
            ->limit(4)
            ->get();

        foreach ($areas as $area) {
            $results[] = [
                'type' => 'area',
                'label' => "{$area->name}, {$country->name}",
                'country_code' => $country->code,
                'area_code' => $area->code,
                'area_slug' => $area->slug,
                'city_slug' => null,
                'url' => url("/{$countrySlug}/{$area->slug}"),
            ];
        }

        $cities = City::query()
            ->where('country_id', $country->id)
            ->where('is_active', true)
            ->where('name', 'like', $q . '%')
            ->with('state:id,name,code,slug')
            ->orderByDesc('is_major_city')
            ->orderByDesc('population')
            ->limit($limit)
            ->get();

        foreach ($cities as $city) {
            if (! $city->state) {
                continue;
            }
            $results[] = [
                'type' => 'city',
                'label' => "{$city->name}, {$city->state->name}",
                'country_code' => $country->code,
                'area_code' => $city->state->code,
                'area_slug' => $city->state->slug,
                'city_id' => $city->id,
                'city_slug' => $city->slug,
                'url' => url("/{$countrySlug}/{$city->state->slug}/{$city->slug}"),
            ];
        }

        return response()->json([
            'status' => 'ok',
            'query' => $query,
            'country' => $country->code,
            'results' => array_slice($results, 0, $limit),
        ]);
    }
}
