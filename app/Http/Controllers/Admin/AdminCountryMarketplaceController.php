<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\State;
use App\Modules\Locations\Services\CountryMarketplaceService;
use App\Modules\Locations\Services\FiveCountryGeographicSeederService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminCountryMarketplaceController extends Controller
{
    public function __construct(
        private readonly CountryMarketplaceService $marketplaceService,
        private readonly FiveCountryGeographicSeederService $seederService
    ) {}

    /**
     * Admin Country Marketplaces Overview (/admin/countries).
     */
    public function index(): View
    {
        $this->marketplaceService->ensureFiveCountriesExist();

        $countries = Country::query()
            ->whereIn('code', Country::SIX_MARKETPLACES)
            ->withCount([
                'states as areas_count' => fn ($q) => $q->where('is_active', true),
                'cities as cities_count' => fn ($q) => $q->where('is_active', true),
                'listings as total_listings_count',
                'listings as active_listings_count' => fn ($q) => $q->where('status', 'active'),
                'listings as pending_listings_count' => fn ($q) => $q->where('status', 'pending'),
                'listings as featured_listings_count' => fn ($q) => $q->where('is_featured', true),
            ])
            ->orderByRaw("CASE code WHEN 'US' THEN 1 WHEN 'GB' THEN 2 WHEN 'CA' THEN 3 WHEN 'AU' THEN 4 WHEN 'IE' THEN 5 WHEN 'NZ' THEN 6 ELSE 99 END")
            ->get();

        // Country visitor counts from visitor_sessions / visitor_geolocation if tables exist
        $visitorCounts = [];
        try {
            $visitorCounts = DB::table('visitor_geolocation')
                ->select('country_code', DB::raw('COUNT(*) as total'))
                ->whereIn('country_code', Country::SIX_MARKETPLACES)
                ->groupBy('country_code')
                ->pluck('total', 'country_code')
                ->all();
        } catch (\Throwable) {
        }

        return view('admin.countries.index', [
            'countries' => $countries,
            'visitorCounts' => $visitorCounts,
        ]);
    }

    /**
     * Admin Single Country Marketplace Management (/admin/countries/{countryCode}).
     */
    public function show(string $countryCode): View
    {
        $country = $this->marketplaceService->getCountryBySlugOrCode($countryCode, false);
        if (! $country) {
            abort(404);
        }

        $areas = State::query()
            ->where('country_id', $country->id)
            ->withCount(['cities', 'listings'])
            ->orderBy('is_territory')
            ->orderBy('name')
            ->get();

        $cities = City::query()
            ->where('country_id', $country->id)
            ->with('state:id,name,code,slug')
            ->withCount('listings')
            ->orderByDesc('is_popular')
            ->orderByDesc('population')
            ->limit(60)
            ->get();

        $categories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $disabledCategoryIds = DB::table('country_categories')
            ->where('country_id', $country->id)
            ->where('is_active', false)
            ->pluck('category_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $recentListings = Listing::query()
            ->where('country_id', $country->id)
            ->with(['city', 'state', 'category', 'user'])
            ->latest('id')
            ->limit(20)
            ->get();

        $popularCategories = Category::query()
            ->whereNull('parent_id')
            ->withCount(['listings' => fn ($q) => $q->where('country_id', $country->id)->where('status', 'active')])
            ->orderByDesc('listings_count')
            ->limit(6)
            ->get();

        $totalUsersCount = 0;
        $visitorsCount = 0;
        try {
            $totalUsersCount = \App\Models\User::query()
                ->whereHas('listings', fn ($q) => $q->where('country_id', $country->id))
                ->count();
            $visitorsCount = (int) DB::table('visitor_geolocation')
                ->where('country_code', $country->code)
                ->count();
        } catch (\Throwable) {
        }

        $stats = [
            'total_listings' => Listing::query()->where('country_id', $country->id)->count(),
            'active_listings' => Listing::query()->where('country_id', $country->id)->where('status', 'active')->count(),
            'pending_listings' => Listing::query()->where('country_id', $country->id)->where('status', 'pending')->count(),
            'featured_listings' => Listing::query()->where('country_id', $country->id)->where('is_featured', true)->count(),
            'total_users' => $totalUsersCount,
            'total_visitors' => $visitorsCount,
        ];

        return view('admin.countries.show', [
            'country' => $country,
            'areas' => $areas,
            'cities' => $cities,
            'categories' => $categories,
            'disabledCategoryIds' => $disabledCategoryIds,
            'recentListings' => $recentListings,
            'popularCategories' => $popularCategories,
            'stats' => $stats,
        ]);
    }

    /**
     * Update Country Marketplace configuration (status, currency, SEO, division label).
     */
    public function update(Request $request, string $countryCode): RedirectResponse
    {
        $country = $this->marketplaceService->getCountryBySlugOrCode($countryCode, false);
        if (! $country) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'currency_code' => ['required', 'string', 'size:3'],
            'currency_symbol' => ['required', 'string', 'max:8'],
            'division_label' => ['nullable', 'string', 'max:40'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'seo_title' => ['nullable', 'string', 'max:190'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'is_active' => ['nullable', 'boolean'],
            'is_marketplace_enabled' => ['nullable', 'boolean'],
        ]);

        $country->update([
            'name' => $validated['name'],
            'currency_code' => strtoupper($validated['currency_code']),
            'currency_symbol' => $validated['currency_symbol'],
            'division_label' => $validated['division_label'] ?? $country->division_label,
            'timezone' => $validated['timezone'] ?? $country->timezone,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'is_marketplace_enabled' => $request->boolean('is_marketplace_enabled', true),
        ]);

        return back()->with('status', "{$country->name} ({$country->code}) marketplace settings updated.");
    }

    /**
     * Update active categories for a specific country marketplace.
     */
    public function syncCategories(Request $request, string $countryCode): RedirectResponse
    {
        $country = $this->marketplaceService->getCountryBySlugOrCode($countryCode, false);
        if (! $country) {
            abort(404);
        }

        $enabledIds = collect((array) $request->input('enabled_category_ids', []))
            ->map(fn ($id) => (int) $id)
            ->all();

        $allCategories = Category::query()->whereNull('parent_id')->orderBy('display_order')->get();
        $now = now();

        foreach ($allCategories as $idx => $cat) {
            $isActive = in_array((int) $cat->id, $enabledIds, true);
            DB::table('country_categories')->updateOrInsert(
                [
                    'country_id' => $country->id,
                    'category_id' => $cat->id,
                ],
                [
                    'is_active' => $isActive,
                    'sort_order' => $idx + 1,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        return back()->with('status', "Category availability updated for {$country->name}.");
    }

    /**
     * Add or update an administrative area (state/province/region/county) in a country.
     */
    public function storeArea(Request $request, string $countryCode): RedirectResponse
    {
        $country = $this->marketplaceService->getCountryBySlugOrCode($countryCode, false);
        if (! $country) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:16'],
            'administrative_level' => ['nullable', 'string', 'max:40'],
            'capital_city' => ['nullable', 'string', 'max:120'],
        ]);

        State::query()->updateOrCreate(
            [
                'country_id' => $country->id,
                'code' => strtoupper(trim($validated['code'])),
            ],
            [
                'name' => trim($validated['name']),
                'slug' => Str::slug($validated['name']),
                'administrative_level' => $validated['administrative_level'] ?: strtolower($country->division_label ?: 'region'),
                'capital_city' => $validated['capital_city'] ?? null,
                'is_active' => true,
            ]
        );

        return back()->with('status', "Administrative area '{$validated['name']}' saved in {$country->name}.");
    }

    /**
     * Add or update a city in a country's administrative area.
     */
    public function storeCity(Request $request, string $countryCode): RedirectResponse
    {
        $country = $this->marketplaceService->getCountryBySlugOrCode($countryCode, false);
        if (! $country) {
            abort(404);
        }

        $validated = $request->validate([
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'name' => ['required', 'string', 'max:120'],
            'population' => ['nullable', 'integer', 'min:0'],
            'is_major_city' => ['nullable', 'boolean'],
        ]);

        $area = State::query()
            ->where('country_id', $country->id)
            ->where('id', (int) $validated['state_id'])
            ->firstOrFail();

        City::query()->updateOrCreate(
            [
                'state_id' => $area->id,
                'slug' => Str::slug($validated['name']),
            ],
            [
                'country_id' => $country->id,
                'name' => trim($validated['name']),
                'population' => (int) ($validated['population'] ?? 0),
                'is_popular' => $request->boolean('is_major_city', false),
                'timezone' => $country->timezone,
                'is_active' => true,
            ]
        );

        return back()->with('status', "City '{$validated['name']}' saved in {$area->name}, {$country->name}.");
    }

    /**
     * Seed or refresh the verified 5-country geographic dataset.
     */
    public function seedGeography(Request $request): RedirectResponse
    {
        $countryCode = $request->input('country_code');
        if (is_string($countryCode) && in_array(strtoupper($countryCode), Country::FIVE_MARKETPLACES, true)) {
            $stats = $this->seederService->seedCountry(strtoupper($countryCode));

            return back()->with('status', "Seeded {$countryCode}: {$stats['areas']} areas and {$stats['cities']} cities.");
        }

        $stats = $this->seederService->seedAll();

        return back()->with(
            'status',
            "Seeded all {$stats['countries']} country marketplaces ({$stats['areas']} administrative areas, {$stats['cities']} cities)."
        );
    }
}
