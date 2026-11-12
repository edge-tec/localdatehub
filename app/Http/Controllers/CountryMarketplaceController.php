<?php

namespace App\Http\Controllers;

use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Services\CountryMarketplaceService;
use App\Services\GeoLocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CountryMarketplaceController extends Controller
{
    public function __construct(
        private readonly CountryMarketplaceService $marketplaceService,
        private readonly GeoLocationService $geoLocationService
    ) {}

    /**
     * Automatic IP-based country marketplace router.
     * Redirects supported visitors to /gb, /ca, /au, /ie, or /nz;
     * sends unsupported visitors to /choose-country.
     */
    public function autoRoute(Request $request): RedirectResponse
    {
        $decision = $this->geoLocationService->resolveMarketplaceDecision($request);

        if ($decision['action'] === 'marketplace' && ! empty($decision['country_slug'])) {
            return redirect()->to('/' . $decision['country_slug']);
        }

        return redirect()->route('country.choose');
    }

    /**
     * Display the Choose Country selector for visitors outside the 5 supported countries
     * or visitors who want to switch marketplaces.
     */
    public function chooseCountry(Request $request): View
    {
        $countries = $this->marketplaceService->getSupportedCountries(true);
        $geo = $this->geoLocationService->detectLocation($request);
        $selectedCode = $this->geoLocationService->getManualCountry($request);

        return view('country.choose', [
            'countries' => $countries,
            'detectedGeo' => $geo,
            'selectedCountryCode' => $selectedCode,
            'seoTitle' => 'Choose Your Country Marketplace | LocalDateHub (UK, Canada, Australia, Ireland, New Zealand)',
            'seoDescription' => 'Select your LocalDateHub country marketplace: United Kingdom, Canada, Australia, Ireland, or New Zealand.',
            'canonicalUrl' => route('country.choose'),
            'hreflangLinks' => $this->buildHreflangLinks(),
        ]);
    }

    /**
     * Persist manual country selection in session & cookie and redirect to that country marketplace.
     */
    public function switchCountry(Request $request, string $countrySlug): RedirectResponse
    {
        $country = $this->marketplaceService->getCountryBySlugOrCode($countrySlug, true);
        if (! $country) {
            abort(404, 'Country marketplace is not available.');
        }

        $this->geoLocationService->setManualCountry($country->code);
        $slug = strtolower($country->slug ?: $country->code);

        return redirect()
            ->to('/' . $slug)
            ->withCookie(cookie(GeoLocationService::COOKIE_COUNTRY_KEY, $country->code, 60 * 24 * 365, '/'));
    }

    /**
     * Independent Country Marketplace Homepage (/gb, /ca, /au, /ie, /nz).
     */
    public function home(Request $request, string $countrySlug): Response
    {
        $country = $this->marketplaceService->getCountryBySlugOrCode($countrySlug, true);
        if (! $country) {
            abort(404, 'Country marketplace not found or currently disabled.');
        }

        if ($request->boolean('remember') || $request->has('switch')) {
            $this->geoLocationService->setManualCountry($country->code);
        }

        $supportedCountries = $this->marketplaceService->getSupportedCountries(true);
        $areas = $this->marketplaceService->getCountryAreas($country);
        $popularCities = $this->marketplaceService->getPopularCities($country, 16);
        $categories = $this->marketplaceService->getCountryCategories($country);
        $featuredListings = $this->marketplaceService->getFeaturedListings($country, 6);
        $latestListings = $this->marketplaceService->getLatestListings($country, 12);
        $stats = $this->marketplaceService->getCountryStatistics($country);
        $slug = strtolower($country->slug ?: $country->code);

        $response = response()->view('country.home', [
            'country' => $country,
            'countrySlug' => $slug,
            'supportedCountries' => $supportedCountries,
            'areas' => $areas,
            'popularCities' => $popularCities,
            'categories' => $categories,
            'featuredListings' => $featuredListings,
            'latestListings' => $latestListings,
            'stats' => $stats,
            'seoTitle' => $country->seo_title ?: "{$country->name} Classifieds & Local Dating Marketplace | LocalDateHub {$country->code}",
            'seoDescription' => $country->seo_description ?: "Browse verified local classifieds, dating personals, and community listings across {$country->name} in {$country->currency_code} ({$country->currency_symbol}).",
            'canonicalUrl' => url('/' . $slug),
            'hreflangLinks' => $this->buildHreflangLinks(),
        ]);

        if ($request->boolean('remember') || $request->has('switch')) {
            $response->withCookie(cookie(GeoLocationService::COOKIE_COUNTRY_KEY, $country->code, 60 * 24 * 365, '/'));
        }

        return $response;
    }

    /**
     * Country-scoped Search & Filter Page (/{countrySlug}/search).
     */
    public function search(Request $request, string $countrySlug): View
    {
        $country = $this->marketplaceService->getCountryBySlugOrCode($countrySlug, true);
        if (! $country) {
            abort(404);
        }

        $filters = [
            'q' => $request->query('q'),
            'area_id' => $request->query('area_id'),
            'area' => $request->query('area'),
            'city_id' => $request->query('city_id'),
            'city' => $request->query('city'),
            'category_id' => $request->query('category_id'),
            'category' => $request->query('category'),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'sort' => $request->query('sort', 'latest'),
        ];

        $listings = $this->marketplaceService->searchCountryListings($country, $filters, 18);
        $areas = $this->marketplaceService->getCountryAreas($country);
        $categories = $this->marketplaceService->getCountryCategories($country);
        $supportedCountries = $this->marketplaceService->getSupportedCountries(true);
        $slug = strtolower($country->slug ?: $country->code);

        $selectedArea = null;
        if (! empty($filters['area_id'])) {
            $selectedArea = $areas->firstWhere('id', (int) $filters['area_id']);
        } elseif (! empty($filters['area'])) {
            $selectedArea = $areas->first(fn ($a) => $a->slug === strtolower((string) $filters['area']) || $a->code === strtoupper((string) $filters['area']));
        }

        $areaCities = $selectedArea
            ? ($this->marketplaceService->getCountryArea($country, $selectedArea->slug)?->cities ?? collect())
            : $this->marketplaceService->getPopularCities($country, 24);

        return view('country.search', [
            'country' => $country,
            'countrySlug' => $slug,
            'supportedCountries' => $supportedCountries,
            'areas' => $areas,
            'selectedArea' => $selectedArea,
            'areaCities' => $areaCities,
            'categories' => $categories,
            'listings' => $listings,
            'filters' => $filters,
            'seoTitle' => "Search {$country->name} Classifieds & Personals | LocalDateHub {$country->code}",
            'seoDescription' => "Search and filter verified local classified listings across {$country->name} by {$country->division_label}, city, category, and {$country->currency_code} ({$country->currency_symbol}) price.",
            'canonicalUrl' => url("/{$slug}/search"),
            'hreflangLinks' => $this->buildHreflangLinks('/search'),
        ]);
    }

    /**
     * Country Administrative Area Page (e.g. /gb/england, /ca/ontario, /au/victoria, /ie/county-cork, /nz/auckland).
     */
    public function showArea(Request $request, string $countrySlug, string $areaSlug): View
    {
        $country = $this->marketplaceService->getCountryBySlugOrCode($countrySlug, true);
        if (! $country) {
            abort(404);
        }

        $area = $this->marketplaceService->getCountryArea($country, $areaSlug);
        if (! $area) {
            abort(404, "Location not found in {$country->name}.");
        }

        $filters = [
            'area_id' => $area->id,
            'q' => $request->query('q'),
            'city_id' => $request->query('city_id'),
            'category' => $request->query('category'),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'sort' => $request->query('sort', 'latest'),
        ];

        // If this is an Irish province (e.g. Leinster) that has child counties, include child county listings too
        if ($area->children->isNotEmpty()) {
            $areaIds = $area->children->pluck('id')->push($area->id)->all();
            $listings = $this->marketplaceService->publicListingsQuery($country)
                ->whereIn('state_id', $areaIds)
                ->with(['city', 'state', 'category', 'media', 'user'])
                ->orderByDesc('is_featured')
                ->latest('published_at')
                ->latest('id')
                ->paginate(18)
                ->withQueryString();
        } else {
            $listings = $this->marketplaceService->searchCountryListings($country, $filters, 18);
        }

        $allAreas = $this->marketplaceService->getCountryAreas($country);
        $categories = $this->marketplaceService->getCountryCategories($country);
        $supportedCountries = $this->marketplaceService->getSupportedCountries(true);
        $slug = strtolower($country->slug ?: $country->code);

        return view('country.area', [
            'country' => $country,
            'countrySlug' => $slug,
            'supportedCountries' => $supportedCountries,
            'area' => $area,
            'allAreas' => $allAreas,
            'categories' => $categories,
            'listings' => $listings,
            'filters' => $filters,
            'seoTitle' => $area->seo_title ?: "{$area->name}, {$country->name} Classifieds & Local Dating | LocalDateHub",
            'seoDescription' => $area->seo_description ?: "Browse verified local classifieds and dating listings in {$area->name}, {$country->name}. Explore cities and local ads in {$country->currency_code} ({$country->currency_symbol}).",
            'canonicalUrl' => url("/{$slug}/{$area->slug}"),
            'hreflangLinks' => $this->buildHreflangLinks(),
        ]);
    }

    /**
     * Country City Page (e.g. /gb/england/london, /ca/ontario/toronto, /au/victoria/melbourne, /ie/county-cork/cork, /nz/auckland/auckland).
     */
    public function showCity(
        Request $request,
        string $countrySlug,
        string $areaSlug,
        string $citySlug,
        ?string $categorySlug = null
    ): View {
        $country = $this->marketplaceService->getCountryBySlugOrCode($countrySlug, true);
        if (! $country) {
            abort(404);
        }

        $area = $this->marketplaceService->getCountryArea($country, $areaSlug);
        if (! $area) {
            abort(404);
        }

        $city = $this->marketplaceService->getCountryCity($country, $area, $citySlug);
        if (! $city) {
            abort(404, "City not found in {$area->name}, {$country->name}.");
        }

        $activeCategory = null;
        if ($categorySlug !== null) {
            $activeCategory = Category::query()
                ->where('slug', strtolower(trim($categorySlug)))
                ->where('is_active', true)
                ->firstOrFail();
        }

        $filters = [
            'area_id' => $area->id,
            'city_id' => $city->id,
            'category_id' => $activeCategory?->id,
            'category' => $request->query('category'),
            'q' => $request->query('q'),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'sort' => $request->query('sort', 'latest'),
        ];

        $listings = $this->marketplaceService->searchCountryListings($country, $filters, 18);
        $categories = $this->marketplaceService->getCountryCategories($country);
        $supportedCountries = $this->marketplaceService->getSupportedCountries(true);
        $slug = strtolower($country->slug ?: $country->code);

        $titlePrefix = $activeCategory
            ? "{$activeCategory->name} in {$city->name}, {$area->name}"
            : "{$city->name}, {$area->name} Classifieds & Local Dating";

        $canonicalPath = $activeCategory
            ? "/{$slug}/{$area->slug}/{$city->slug}/{$activeCategory->slug}"
            : "/{$slug}/{$area->slug}/{$city->slug}";

        return view('country.city', [
            'country' => $country,
            'countrySlug' => $slug,
            'supportedCountries' => $supportedCountries,
            'area' => $area,
            'city' => $city,
            'activeCategory' => $activeCategory,
            'categories' => $categories,
            'listings' => $listings,
            'filters' => $filters,
            'seoTitle' => "{$titlePrefix} ({$country->name}) | LocalDateHub",
            'seoDescription' => "Explore verified local classified listings in {$city->name}, {$area->name}, {$country->name}. Local ads and personals in {$country->currency_code} ({$country->currency_symbol}).",
            'canonicalUrl' => url($canonicalPath),
            'hreflangLinks' => $this->buildHreflangLinks(),
        ]);
    }

    /**
     * Country-specific XML Sitemap (/{countrySlug}/sitemap.xml).
     */
    public function sitemap(string $countrySlug): Response
    {
        $country = $this->marketplaceService->getCountryBySlugOrCode($countrySlug, true);
        if (! $country) {
            abort(404);
        }

        $slug = strtolower($country->slug ?: $country->code);
        $areas = $this->marketplaceService->getCountryAreas($country);
        $cities = $this->marketplaceService->getPopularCities($country, 250);
        $listings = $this->marketplaceService->getLatestListings($country, 200);

        $urls = [
            [
                'loc' => url('/' . $slug),
                'changefreq' => 'hourly',
                'priority' => '1.0',
            ],
            [
                'loc' => url("/{$slug}/search"),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ],
        ];

        foreach ($areas as $area) {
            $urls[] = [
                'loc' => url("/{$slug}/{$area->slug}"),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ];
        }

        foreach ($cities as $city) {
            if ($city->state) {
                $urls[] = [
                    'loc' => url("/{$slug}/{$city->state->slug}/{$city->slug}"),
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                ];
            }
        }

        foreach ($listings as $listing) {
            $urls[] = [
                'loc' => route('listings.show', $listing->slug),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $loc = htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$loc}</loc>\n";
            $xml .= "    <changefreq>{$u['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$u['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * Build hreflang alternate links for the 5 supported English-speaking country marketplaces.
     *
     * @return array<int, array{hreflang: string, href: string}>
     */
    private function buildHreflangLinks(string $suffix = ''): array
    {
        return [
            ['hreflang' => 'en-GB', 'href' => url('/gb' . $suffix)],
            ['hreflang' => 'en-CA', 'href' => url('/ca' . $suffix)],
            ['hreflang' => 'en-AU', 'href' => url('/au' . $suffix)],
            ['hreflang' => 'en-IE', 'href' => url('/ie' . $suffix)],
            ['hreflang' => 'en-NZ', 'href' => url('/nz' . $suffix)],
            ['hreflang' => 'x-default', 'href' => route('country.choose')],
        ];
    }
}

