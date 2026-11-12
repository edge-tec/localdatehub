<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Modules\Advertising\Models\AdCampaign;
use App\Modules\Advertising\Services\AdDeliveryService;
use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Services\CountryMarketplaceService;
use App\Services\GeoLocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CountryMarketplaceController extends Controller
{
    public function __construct(
        private readonly CountryMarketplaceService $marketplaceService,
        private readonly GeoLocationService $geoLocationService
    ) {}

    /**
     * Automatic IP-based country marketplace router.
     * Redirects visitors directly to /us, /gb, /ca, /au, /ie, or /nz.
     */
    public function autoRoute(Request $request): RedirectResponse
    {
        $decision = $this->geoLocationService->resolveMarketplaceDecision($request);
        $slug = $decision['country_slug'] ?: 'us';

        return redirect()->to('/' . $slug);
    }

    /**
     * Legacy /choose-country route redirects directly to the visitor's detected country dashboard (no country list).
     */
    public function chooseCountry(Request $request): RedirectResponse
    {
        $geo = $this->geoLocationService->detectLocation($request);
        $code = strtoupper((string) ($geo['country_code'] ?? 'US'));
        $slug = in_array($code, ['US', 'GB', 'CA', 'AU', 'IE', 'NZ'], true)
            ? strtolower($code)
            : 'us';

        return redirect()->to('/' . $slug);
    }

    /**
     * Persist country selection in session & cookie and redirect to that country marketplace.
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
     * Country Marketplace Landing Page (/us, /gb, /ca, /au, /ie, /nz).
     * Uses the exact main LocalDateHub landing page design (resources/views/welcome.blade.php)
     * scoped to the active country's states/regions, cities, listings, and currency.
     */
    public function home(Request $request, string $countrySlug): Response
    {
        $country = $this->marketplaceService->getCountryBySlugOrCode($countrySlug, true);
        if (! $country) {
            abort(404, 'Country marketplace not found or currently disabled.');
        }

        $this->geoLocationService->setManualCountry($country->code);
        $slug = strtolower($country->slug ?: $country->code);

        $allStates = $this->marketplaceService->getCountryAreas($country);
        $popularStates = $allStates->take(10);
        $popularCities = $this->marketplaceService->getPopularCities($country, 20);
        $allCountryCities = $slug === 'us'
            ? $popularCities
            : $this->marketplaceService->getPopularCities($country, 300);
        $categories = $this->marketplaceService->getCountryCategories($country);
        $featuredListings = $this->marketplaceService->getFeaturedListings($country, 4);
        $latestListings = $this->marketplaceService->getLatestListings($country, 12);
        $stats = $this->marketplaceService->getCountryStatistics($country);

        $topCategoriesSlugs = [
            'men-seeking-women' => ['title' => 'Men Seeking Women', 'subtitle' => '(M4W)', 'icon' => 'couple-mw'],
            'men-seeking-men' => ['title' => 'Men Seeking Men', 'subtitle' => '(M4M)', 'icon' => 'users-male'],
            'men-seeking-couples' => ['title' => 'Men Seeking Couples', 'subtitle' => '', 'icon' => 'users-group'],
            'women-seeking-men' => ['title' => 'Women Seeking Men', 'subtitle' => '(W4M)', 'icon' => 'couple-wm'],
            'women-seeking-women' => ['title' => 'Women Seeking Women', 'subtitle' => '(W4W)', 'icon' => 'users-female'],
            'women-seeking-couples' => ['title' => 'Women Seeking Couples', 'subtitle' => '', 'icon' => 'users-group'],
            'couples-seeking-men' => ['title' => 'Couples Seeking Men', 'subtitle' => '', 'icon' => 'couple-man'],
            'couples-seeking-women' => ['title' => 'Couples Seeking Women', 'subtitle' => '', 'icon' => 'couple-woman'],
            'couples-seeking-couples' => ['title' => 'Couples Seeking Couples', 'subtitle' => '', 'icon' => 'couples'],
        ];

        $homepageCategories = collect();
        foreach ($topCategoriesSlugs as $catSlug => $meta) {
            $cat = Category::where('slug', $catSlug)->first();
            if ($cat) {
                $homepageCategories->push([
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'title' => $meta['title'],
                    'subtitle' => $meta['subtitle'],
                    'slug' => $cat->slug,
                    'icon' => $meta['icon'],
                    'url' => $slug === 'us'
                        ? route('categories.show', $cat->slug)
                        : route('country.search', ['countrySlug' => $slug, 'category' => $cat->slug]),
                ]);
            }
        }

        $delivery = app(AdDeliveryService::class);
        $latestListingsFeed = $delivery->injectIntoListings($latestListings, AdCampaign::SLOT_HOMEPAGE_FEED);
        $homepageFeaturedAds = $delivery->getAdsForSlot(AdCampaign::SLOT_HOMEPAGE_FEATURED, [], 1);

        $brand = $this->resolveBrandBannerSettings();

        $seoTitle = $country->seo_title ?: "{$country->name} Marketplace ({$country->code}) — {$country->currency_code} ({$country->currency_symbol}) | LocalDateHub";
        $seoDescription = $country->seo_description ?: "Browse verified local classifieds, dating personals, and community listings across {$country->name} ({$country->code}) in {$country->currency_code} ({$country->currency_symbol}).";
        $canonicalUrl = url('/' . $slug);

        $seo = (object) [
            'title' => $seoTitle,
            'description' => $seoDescription,
            'canonicalUrl' => $canonicalUrl,
            'robots' => 'index, follow',
            'ogType' => 'website',
            'ogImage' => $brand['heroImageUrl'],
            'structuredData' => null,
        ];

        $response = response()->view('welcome', array_merge($brand, [
            'activeCountry' => $country,
            'country' => $country,
            'countrySlug' => $slug,
            'categories' => $categories,
            'homepageCategories' => $homepageCategories,
            'featuredListings' => $featuredListings,
            'homepageFeaturedAds' => $homepageFeaturedAds,
            'latestListings' => $latestListings,
            'latestListingsFeed' => $latestListingsFeed,
            'popularStates' => $popularStates,
            'allStates' => $allStates,
            'popularCities' => $popularCities,
            'allCountryCities' => $allCountryCities,
            'stats' => $stats,
            'seo' => $seo,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'canonicalUrl' => $canonicalUrl,
            'hreflangLinks' => $this->buildHreflangLinks(),
        ]));

        $response->withCookie(cookie(GeoLocationService::COOKIE_COUNTRY_KEY, $country->code, 60 * 24 * 365, '/'));

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
            'area' => $request->query('area') ?? $request->query('state'),
            'state' => $request->query('state'),
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
            'activeCountry' => $country,
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
            'activeCountry' => $country,
            'country' => $country,
            'countrySlug' => $slug,
            'supportedCountries' => $supportedCountries,
            'area' => $area,
            'allAreas' => $allAreas,
            'categories' => $categories,
            'listings' => $listings,
            'filters' => $filters,
            'seoTitle' => "{$area->name}, {$country->name} Classifieds & Local Dating | LocalDateHub",
            'seoDescription' => "Browse verified local classifieds and dating listings in {$area->name}, {$country->name}. Explore cities and local ads in {$country->currency_code} ({$country->currency_symbol}).",
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
            'activeCountry' => $country,
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
     * Resolve admin-configured hero & sidebar promotional banner settings identically to HomeController.
     */
    private function resolveBrandBannerSettings(): array
    {
        $heroImageUrl = asset('images/hero-model.jpg');
        $heroOfferLink = null;
        $heroOfferNewTab = true;
        $heroOfferAlt = 'LocalDateHub Dating Lifestyle';

        $sidebarPromoImageUrl = asset('images/promo-banner.jpg');
        $sidebarPromoIsCustom = false;
        $sidebarPromoOfferLink = null;
        $sidebarPromoOfferNewTab = true;
        $sidebarPromoOfferAlt = 'Find Your Perfect Match';

        try {
            if (Schema::hasTable('system_settings')) {
                $brandSettings = SystemSetting::whereIn('key', [
                    'hero_image_url',
                    'hero_offer_link',
                    'hero_offer_new_tab',
                    'hero_offer_alt',
                    'sidebar_promo_image_url',
                    'sidebar_promo_offer_link',
                    'sidebar_promo_offer_new_tab',
                    'sidebar_promo_offer_alt',
                ])->pluck('value', 'key');

                $rawHeroImage = trim((string) ($brandSettings['hero_image_url'] ?? ''));
                if ($rawHeroImage !== '') {
                    $heroImageUrl = (str_starts_with($rawHeroImage, 'http://') || str_starts_with($rawHeroImage, 'https://'))
                        ? $rawHeroImage
                        : asset(ltrim($rawHeroImage, '/'));
                }

                $rawHeroOffer = trim((string) ($brandSettings['hero_offer_link'] ?? ''));
                if ($rawHeroOffer !== '') {
                    $heroOfferLink = $rawHeroOffer;
                }
                if (isset($brandSettings['hero_offer_new_tab'])) {
                    $heroOfferNewTab = filter_var($brandSettings['hero_offer_new_tab'], FILTER_VALIDATE_BOOLEAN);
                }
                $rawHeroAlt = trim((string) ($brandSettings['hero_offer_alt'] ?? ''));
                if ($rawHeroAlt !== '') {
                    $heroOfferAlt = $rawHeroAlt;
                }

                $rawSidebarImage = trim((string) ($brandSettings['sidebar_promo_image_url'] ?? ''));
                if ($rawSidebarImage !== '') {
                    $sidebarPromoImageUrl = (str_starts_with($rawSidebarImage, 'http://') || str_starts_with($rawSidebarImage, 'https://'))
                        ? $rawSidebarImage
                        : asset(ltrim($rawSidebarImage, '/'));
                    $sidebarPromoIsCustom = ltrim($rawSidebarImage, '/') !== 'images/promo-banner.jpg';
                }

                $rawSidebarOffer = trim((string) ($brandSettings['sidebar_promo_offer_link'] ?? ''));
                if ($rawSidebarOffer !== '') {
                    $sidebarPromoOfferLink = $rawSidebarOffer;
                }
                if (isset($brandSettings['sidebar_promo_offer_new_tab'])) {
                    $sidebarPromoOfferNewTab = filter_var($brandSettings['sidebar_promo_offer_new_tab'], FILTER_VALIDATE_BOOLEAN);
                }
                $rawSidebarAlt = trim((string) ($brandSettings['sidebar_promo_offer_alt'] ?? ''));
                if ($rawSidebarAlt !== '') {
                    $sidebarPromoOfferAlt = $rawSidebarAlt;
                }
            }
        } catch (\Throwable) {
        }

        return [
            'heroImageUrl' => $heroImageUrl,
            'heroOfferLink' => $heroOfferLink,
            'heroOfferNewTab' => $heroOfferNewTab,
            'heroOfferAlt' => $heroOfferAlt,
            'sidebarPromoImageUrl' => $sidebarPromoImageUrl,
            'sidebarPromoIsCustom' => $sidebarPromoIsCustom,
            'sidebarPromoOfferLink' => $sidebarPromoOfferLink,
            'sidebarPromoOfferNewTab' => $sidebarPromoOfferNewTab,
            'sidebarPromoOfferAlt' => $sidebarPromoOfferAlt,
        ];
    }

    /**
     * Build hreflang alternate links for the supported country marketplaces.
     *
     * @return array<int, array{hreflang: string, href: string}>
     */
    private function buildHreflangLinks(string $suffix = ''): array
    {
        return [
            ['hreflang' => 'en-US', 'href' => url('/us' . $suffix)],
            ['hreflang' => 'en-GB', 'href' => url('/gb' . $suffix)],
            ['hreflang' => 'en-CA', 'href' => url('/ca' . $suffix)],
            ['hreflang' => 'en-AU', 'href' => url('/au' . $suffix)],
            ['hreflang' => 'en-IE', 'href' => url('/ie' . $suffix)],
            ['hreflang' => 'en-NZ', 'href' => url('/nz' . $suffix)],
            ['hreflang' => 'x-default', 'href' => url('/us' . $suffix)],
        ];
    }
}
