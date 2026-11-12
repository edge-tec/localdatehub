<?php

namespace App\Modules\Locations\Services;

use App\Modules\Categories\Models\Category;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\State;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CountryMarketplaceService
{
    private const CACHE_TTL_MINUTES = 30;

    public function __construct(
        private readonly FiveCountryGeographicSeederService $seederService
    ) {}

    /**
     * Ensure the 5 countries are seeded in the database if missing, and return all 5 active marketplaces.
     */
    public function getSupportedCountries(bool $onlyEnabled = true): Collection
    {
        $this->ensureFiveCountriesExist();

        $query = Country::query()
            ->whereIn('code', Country::SIX_MARKETPLACES)
            ->withCount([
                'states as areas_count' => fn ($q) => $q->where('is_active', true),
                'cities as cities_count' => fn ($q) => $q->where('is_active', true),
                'listings as active_listings_count' => fn ($q) => $this->applyPublicListingScope($q),
            ])
            ->orderByRaw("CASE code WHEN 'US' THEN 1 WHEN 'GB' THEN 2 WHEN 'CA' THEN 3 WHEN 'AU' THEN 4 WHEN 'IE' THEN 5 WHEN 'NZ' THEN 6 ELSE 99 END")
            ->orderBy('name');

        if ($onlyEnabled) {
            $query->where('is_active', true)
                ->where('is_marketplace_enabled', true);
        }

        return $query->get();
    }

    /**
     * Resolve a country marketplace by slug (`us`, `gb`, `ca`, `au`, `ie`, `nz`) or ISO code (`US`, `GB`, `CA`, `AU`, `IE`, `NZ`).
     */
    public function getCountryBySlugOrCode(string $slugOrCode, bool $requireEnabled = true): ?Country
    {
        $normalizedCode = strtoupper(trim($slugOrCode));
        $normalizedSlug = strtolower(trim($slugOrCode));

        $allowedCodes = array_merge(['US'], Country::FIVE_MARKETPLACES);
        $allowedSlugs = array_merge(['us'], Country::FIVE_MARKETPLACE_SLUGS);

        if (! in_array($normalizedCode, $allowedCodes, true)
            && ! in_array($normalizedSlug, $allowedSlugs, true)) {
            return null;
        }

        $this->ensureFiveCountriesExist();

        if ($normalizedCode === 'US' || $normalizedSlug === 'us') {
            return Country::query()->firstOrCreate(
                ['code' => 'US'],
                [
                    'slug' => 'us',
                    'name' => 'United States',
                    'iso3' => 'USA',
                    'phone_code' => '+1',
                    'currency_code' => 'USD',
                    'currency_symbol' => '$',
                    'flag' => '🇺🇸',
                    'timezone' => 'America/New_York',
                    'division_label' => 'State',
                    'is_active' => true,
                    'is_marketplace_enabled' => true,
                ]
            );
        }

        $query = Country::query()
            ->where(function ($q) use ($normalizedCode, $normalizedSlug) {
                $q->where('code', $normalizedCode)
                    ->orWhere('slug', $normalizedSlug);
            });

        if ($requireEnabled) {
            $query->where('is_active', true)
                ->where('is_marketplace_enabled', true);
        }

        return $query->first();
    }

    /**
     * Return all active administrative areas (states/provinces/regions/counties/nations) for a country,
     * along with active city counts and active listing counts.
     */
    public function getCountryAreas(Country $country): Collection
    {
        $areas = State::query()
            ->where('country_id', $country->id)
            ->where('is_active', true)
            ->with('parent:id,name,code,slug,administrative_level')
            ->withCount(['cities as cities_count' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('is_territory')
            ->orderBy('name')
            ->get();

        if ($areas->isEmpty()) {
            return $areas;
        }

        $listingCounts = $this->publicListingsQuery($country)
            ->select('state_id', DB::raw('COUNT(*) as total'))
            ->whereIn('state_id', $areas->pluck('id'))
            ->groupBy('state_id')
            ->pluck('total', 'state_id');

        return $areas->map(function (State $area) use ($listingCounts, $country) {
            $area->setAttribute('active_listings_count', (int) ($listingCounts[$area->id] ?? 0));
            $area->setAttribute('country_slug', strtolower($country->slug ?: $country->code));

            return $area;
        });
    }

    /**
     * Resolve an administrative area inside a specific country by slug or code.
     */
    public function getCountryArea(Country $country, string $areaSlugOrCode): ?State
    {
        $normalizedSlug = strtolower(trim($areaSlugOrCode));
        $normalizedCode = strtoupper(trim($areaSlugOrCode));

        $area = State::query()
            ->where('country_id', $country->id)
            ->where('is_active', true)
            ->where(function ($q) use ($normalizedSlug, $normalizedCode) {
                $q->where('slug', $normalizedSlug)
                    ->orWhere('code', $normalizedCode);
            })
            ->with([
                'parent:id,name,code,slug,administrative_level',
                'children' => fn ($q) => $q->where('is_active', true)->orderBy('name'),
                'cities' => fn ($q) => $q->where('is_active', true)
                    ->orderByDesc('is_popular')
                    ->orderByDesc('population')
                    ->orderBy('name'),
            ])
            ->first();

        if (! $area) {
            return null;
        }

        // Attach listing counts per city in this area
        $cityIds = $area->cities->pluck('id');
        $cityListingCounts = $cityIds->isNotEmpty()
            ? $this->publicListingsQuery($country)
                ->select('city_id', DB::raw('COUNT(*) as total'))
                ->whereIn('city_id', $cityIds)
                ->groupBy('city_id')
                ->pluck('total', 'city_id')
            : collect();

        $area->cities->each(function (City $city) use ($cityListingCounts) {
            $city->setAttribute('active_listings_count', (int) ($cityListingCounts[$city->id] ?? 0));
        });

        $area->setAttribute(
            'active_listings_count',
            (int) $this->publicListingsQuery($country)->where('state_id', $area->id)->count()
        );

        return $area;
    }

    /**
     * Resolve a specific city inside a country and administrative area.
     */
    public function getCountryCity(Country $country, State $area, string $citySlug): ?City
    {
        $city = City::query()
            ->where('state_id', $area->id)
            ->where('slug', strtolower(trim($citySlug)))
            ->where('is_active', true)
            ->with(['state', 'country'])
            ->first();

        if (! $city) {
            return null;
        }

        $city->setAttribute(
            'active_listings_count',
            (int) $this->publicListingsQuery($country)->where('city_id', $city->id)->count()
        );

        return $city;
    }

    /**
     * Return popular / major cities for a country with active listing counts.
     */
    public function getPopularCities(Country $country, int $limit = 16): Collection
    {
        $cities = City::query()
            ->where('country_id', $country->id)
            ->where('is_active', true)
            ->with('state:id,country_id,name,code,slug')
            ->orderByDesc('is_popular')
            ->orderByDesc('population')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        if ($cities->isEmpty()) {
            return $cities;
        }

        $cityListingCounts = $this->publicListingsQuery($country)
            ->select('city_id', DB::raw('COUNT(*) as total'))
            ->whereIn('city_id', $cities->pluck('id'))
            ->groupBy('city_id')
            ->pluck('total', 'city_id');

        return $cities->map(function (City $city) use ($cityListingCounts) {
            $city->setAttribute('active_listings_count', (int) ($cityListingCounts[$city->id] ?? 0));

            return $city;
        });
    }

    /**
     * Return active categories for a country (using country_categories pivot when configured),
     * with country-scoped active listing counts.
     */
    public function getCountryCategories(Country $country): Collection
    {
        $disabledCategoryIds = [];
        try {
            $disabledCategoryIds = DB::table('country_categories')
                ->where('country_id', $country->id)
                ->where('is_active', false)
                ->pluck('category_id')
                ->all();
        } catch (\Throwable) {
        }

        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->when(! empty($disabledCategoryIds), fn ($q) => $q->whereNotIn('id', $disabledCategoryIds))
            ->with(['children' => function ($q) use ($disabledCategoryIds) {
                $q->where('is_active', true)
                    ->when(! empty($disabledCategoryIds), fn ($sq) => $sq->whereNotIn('id', $disabledCategoryIds))
                    ->orderBy('display_order')
                    ->orderBy('name');
            }])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        // Count active listings per category_id in this country
        $countsByCategory = $this->publicListingsQuery($country)
            ->select('category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return $categories->map(function (Category $parent) use ($countsByCategory) {
            $directCount = (int) ($countsByCategory[$parent->id] ?? 0);
            $childrenSum = 0;

            $parent->children->each(function (Category $child) use ($countsByCategory, &$childrenSum) {
                $childCount = (int) ($countsByCategory[$child->id] ?? 0);
                $child->setAttribute('country_listings_count', $childCount);
                $childrenSum += $childCount;
            });

            $parent->setAttribute('country_listings_count', $directCount + $childrenSum);

            return $parent;
        });
    }

    /**
     * Fetch featured listings strictly belonging to this country.
     */
    public function getFeaturedListings(Country $country, int $limit = 6): Collection
    {
        $featured = $this->publicListingsQuery($country)
            ->where('is_featured', true)
            ->with(['city', 'state', 'category', 'media', 'user'])
            ->latest('published_at')
            ->latest('id')
            ->limit($limit)
            ->get();

        if ($featured->count() < $limit) {
            $FallbackIds = $featured->pluck('id')->all();
            $more = $this->publicListingsQuery($country)
                ->when(! empty($FallbackIds), fn ($q) => $q->whereNotIn('id', $FallbackIds))
                ->with(['city', 'state', 'category', 'media', 'user'])
                ->latest('published_at')
                ->latest('id')
                ->limit($limit - $featured->count())
                ->get();

            $featured = $featured->concat($more);
        }

        return $featured;
    }

    /**
     * Fetch latest listings strictly belonging to this country.
     */
    public function getLatestListings(Country $country, int $limit = 12): Collection
    {
        return $this->publicListingsQuery($country)
            ->with(['city', 'state', 'category', 'media', 'user'])
            ->latest('published_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Paginated & filtered listings strictly scoped to a country (and optionally area, city, category, keyword, price, sort).
     */
    public function searchCountryListings(Country $country, array $filters = [], int $perPage = 18): LengthAwarePaginator
    {
        $query = $this->publicListingsQuery($country)
            ->with(['city', 'state', 'category', 'media', 'user']);

        $areaFilter = $filters['area'] ?? $filters['state'] ?? null;
        if (! empty($filters['area_id'])) {
            $query->where('state_id', (int) $filters['area_id']);
        } elseif (! empty($areaFilter)) {
            $areaVal = trim((string) $areaFilter);
            $area = State::query()
                ->where('country_id', $country->id)
                ->where(fn ($q) => $q->where('slug', strtolower($areaVal))->orWhere('code', strtoupper($areaVal)))
                ->first();
            if ($area) {
                $query->where('state_id', $area->id);
            }
        }

        if (! empty($filters['city_id'])) {
            $query->where('city_id', (int) $filters['city_id']);
        } elseif (! empty($filters['city'])) {
            $cityVal = strtolower(trim((string) $filters['city']));
            $cityIds = City::query()
                ->where('country_id', $country->id)
                ->where(fn ($q) => $q->where('slug', $cityVal)->orWhereRaw('LOWER(name) = ?', [$cityVal]))
                ->pluck('id');
            if ($cityIds->isNotEmpty()) {
                $query->whereIn('city_id', $cityIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (! empty($filters['category_id'])) {
            $catId = (int) $filters['category_id'];
            $childIds = Category::query()->where('parent_id', $catId)->pluck('id')->push($catId)->all();
            $query->whereIn('category_id', $childIds);
        } elseif (! empty($filters['category'])) {
            $catSlug = strtolower(trim((string) $filters['category']));
            $cat = Category::query()->where('slug', $catSlug)->first();
            if ($cat) {
                $childIds = Category::query()->where('parent_id', $cat->id)->pluck('id')->push($cat->id)->all();
                $query->whereIn('category_id', $childIds);
            }
        }

        if (! empty($filters['q'])) {
            $keyword = trim((string) $filters['q']);
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%');
            });
        }

        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'featured' => $query->orderByDesc('is_featured')->latest('published_at')->latest('id'),
            'price_asc' => $query->orderByRaw('CASE WHEN price IS NULL THEN 1 ELSE 0 END')->orderBy('price')->latest('id'),
            'price_desc' => $query->orderByDesc('price')->latest('id'),
            'popular' => $query->orderByDesc('view_count')->latest('published_at'),
            default => $query->orderByDesc('is_featured')->latest('published_at')->latest('id'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Country-specific marketplace statistics.
     *
     * @return array{
     *   active_listings: int,
     *   featured_listings: int,
     *   total_areas: int,
     *   total_cities: int,
     *   active_categories: int,
     *   currency_code: string,
     *   currency_symbol: string
     * }
     */
    public function getCountryStatistics(Country $country): array
    {
        return [
            'active_listings' => (int) $this->publicListingsQuery($country)->count(),
            'featured_listings' => (int) $this->publicListingsQuery($country)->where('is_featured', true)->count(),
            'total_areas' => (int) State::query()->where('country_id', $country->id)->where('is_active', true)->count(),
            'total_cities' => (int) City::query()->where('country_id', $country->id)->where('is_active', true)->count(),
            'active_categories' => (int) Category::query()->whereNull('parent_id')->where('is_active', true)->count(),
            'currency_code' => $country->currency_code ?: 'GBP',
            'currency_symbol' => $country->currency_symbol ?: '£',
        ];
    }

    /**
     * Base query for public, active, non-expired listings strictly belonging to a country.
     * Ensures Northern Ireland stays in GB and never leaks into IE.
     */
    public function publicListingsQuery(Country $country): Builder
    {
        $query = Listing::query()
            ->where(function (Builder $q) use ($country) {
                $q->where('country_id', $country->id)
                    ->orWhereHas('state', fn (Builder $sq) => $sq->where('country_id', $country->id));
            });

        // Strict safeguard: Republic of Ireland (IE) must never include Northern Ireland (NIR) listings
        if (strtoupper($country->code) === 'IE') {
            $query->whereDoesntHave('state', function (Builder $sq) {
                $sq->where('code', 'NIR')
                    ->orWhere('slug', 'northern-ireland');
            });
        }

        return $this->applyPublicListingScope($query);
    }

    private function applyPublicListingScope(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Automatically migrate (if needed) and seed the 5 countries if they haven't been seeded yet
     * or if the expanded Australian city dataset has not been seeded yet.
     */
    public function ensureFiveCountriesExist(): void
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasColumn('countries', 'is_marketplace_enabled')
                || ! \Illuminate\Support\Facades\Schema::hasColumn('cities', 'country_id')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', [
                    '--path' => 'database/migrations/2026_11_12_000003_create_five_country_marketplace_tables.php',
                    '--force' => true,
                ]);
            }

            $count = Country::query()
                ->whereIn('code', Country::SIX_MARKETPLACES)
                ->where('is_marketplace_enabled', true)
                ->count();

            $us = Country::query()->where('code', 'US')->first();
            $usStatesCount = $us ? State::query()->where('country_id', $us->id)->count() : 0;

            $au = Country::query()->where('code', 'AU')->first();
            $auStatesCount = $au ? State::query()->where('country_id', $au->id)->count() : 0;
            $auCitiesCount = $au ? City::query()->where('country_id', $au->id)->count() : 0;

            if ($count < 6 || $usStatesCount < 51 || $auStatesCount < 8 || $auCitiesCount < 180) {
                $this->seederService->seedAll();
            }
        } catch (\Throwable) {
            // Ignore during pre-migration boot
        }
    }
}
