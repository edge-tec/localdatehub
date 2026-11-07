<?php

namespace App\Http\Controllers;

use App\Modules\Categories\Services\CategoryService;
use App\Modules\Locations\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationWebController extends Controller
{
    public function __construct(
        protected LocationService $locationService,
        protected CategoryService $categoryService
    ) {}

    /**
     * US National Location Overview directory.
     */
    public function index(): View
    {
        $states = $this->locationService->getStateDirectory();

        return view('locations.index', [
            'states' => $states,
        ]);
    }

    /**
     * State Landing Page (e.g. /locations/TX).
     */
    public function showState(string $stateCode): View
    {
        $state = $this->locationService->getStateWithCities($stateCode);
        if (!$state) {
            abort(404, "State [{$stateCode}] not found or not currently supported.");
        }

        $categories = $this->categoryService->getActiveCategories();

        return view('locations.state', [
            'state' => $state,
            'cities' => $state->cities,
            'counties' => $state->counties,
            'categories' => $categories,
        ]);
    }

    /**
     * City Landing Page with optional Category filter and distance radius expansion.
     * e.g. /locations/TX/austin or /locations/TX/austin/women-seeking-men
     */
    public function showCity(Request $request, string $stateCode, string $citySlug, ?string $categorySlug = null): View
    {
        $city = $this->locationService->getCityWithDetails($stateCode, $citySlug);
        if (!$city) {
            abort(404, "City [{$citySlug}] in [{$stateCode}] not found or not currently active.");
        }

        $categories = $this->categoryService->getActiveCategories();
        $selectedCategory = null;

        if ($categorySlug) {
            $selectedCategory = \App\Modules\Categories\Models\Category::where('slug', $categorySlug)->first();
            if (!$selectedCategory) {
                abort(404, "Category [{$categorySlug}] not found.");
            }
        }

        $radiusMiles = (float) $request->query('radius', 0);
        $radiusListings = null;

        if ($radiusMiles > 0 && $city->latitude && $city->longitude) {
            $radiusListings = $this->locationService->findNearbyListings(
                $city->latitude,
                $city->longitude,
                $radiusMiles,
                $selectedCategory?->id
            );
        }

        // Load active listings for this city
        $query = $city->listings()
            ->active()
            ->with(['category', 'primaryPhoto'])
            ->latest('published_at');

        if ($selectedCategory) {
            $query->where('category_id', $selectedCategory->id);
        }

        $listings = $query->paginate(15)->withQueryString();

        return view('locations.city', [
            'city' => $city,
            'state' => $city->state,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'listings' => $listings,
            'radiusListings' => $radiusListings,
            'radiusMiles' => $radiusMiles,
            'nearbyCities' => $city->nearby_cities ?? collect(),
        ]);
    }
}

