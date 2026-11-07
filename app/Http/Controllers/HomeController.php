<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Categories\Services\CategoryService;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Public Homepage with dynamic classified listings, popular cities, and category taxonomy.
     */
    public function index(): View
    {
        $categories = $this->categoryService->getActiveCategories();

        $featuredListings = collect();
        $latestListings = collect();
        $popularCities = collect();
        $popularStates = collect();
        $stats = [
            'activeListings' => 0,
            'coveredStates' => 50,
            'citiesCount' => 0,
            'verifiedMembers' => 0,
        ];

        if (Schema::hasTable('listings')) {
            $featuredListings = Listing::active()
                ->featured()
                ->with(['category', 'city.state', 'state', 'primaryPhoto'])
                ->latest('published_at')
                ->take(6)
                ->get();

            $latestListings = Listing::active()
                ->with(['category', 'city.state', 'state', 'primaryPhoto'])
                ->latest('published_at')
                ->take(8)
                ->get();

            $stats['activeListings'] = Listing::active()->count();
        }

        if (Schema::hasTable('cities')) {
            $popularCities = City::where('is_popular', true)
                ->with('state')
                ->orderBy('name')
                ->take(12)
                ->get();

            $stats['citiesCount'] = City::count();
        }

        if (Schema::hasTable('states')) {
            $popularStates = State::where('is_supported', true)
                ->orderBy('name')
                ->take(12)
                ->get();

            $stats['coveredStates'] = State::where('is_supported', true)->count();
        }

        if (Schema::hasTable('users')) {
            $stats['verifiedMembers'] = User::where('is_age_verified', true)->count();
        }

        return view('welcome', [
            'categories' => $categories,
            'featuredListings' => $featuredListings,
            'latestListings' => $latestListings,
            'popularCities' => $popularCities,
            'popularStates' => $popularStates,
            'stats' => $stats,
        ]);
    }
}

