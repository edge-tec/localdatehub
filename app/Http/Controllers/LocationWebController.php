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
     * City Landing Page (e.g. /locations/TX/austin).
     */
    public function showCity(string $stateCode, string $citySlug): View
    {
        $city = $this->locationService->getCityWithDetails($stateCode, $citySlug);
        if (!$city) {
            abort(404, "City [{$citySlug}] in [{$stateCode}] not found or not currently active.");
        }

        $categories = $this->categoryService->getActiveCategories();

        // Load active listings for this city
        $listings = $city->listings()
            ->active()
            ->with(['category', 'primaryPhoto'])
            ->latest('published_at')
            ->paginate(15);

        return view('locations.city', [
            'city' => $city,
            'state' => $city->state,
            'categories' => $categories,
            'listings' => $listings,
            'nearbyCities' => $city->nearby_cities ?? collect(),
        ]);
    }
}
