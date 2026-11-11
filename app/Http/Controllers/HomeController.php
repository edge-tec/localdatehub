<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Services\CategoryService;
use App\Modules\Listings\Models\Listing;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Admin\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Public Homepage for LocalDateHub — Premium Adult Classifieds & Dating Connections
     */
    public function index(): View
    {
        $categories = $this->categoryService->getActiveCategories();

        $featuredListings = collect();
        $latestListings = collect();
        $popularStates = collect();
        $allStates = collect();
        $popularCities = collect();
        $stats = [
            'activeListings' => 0,
            'coveredStates' => 50,
            'citiesCount' => 0,
            'verifiedMembers' => 0,
        ];

        if (Schema::hasTable('listings')) {
            $featuredListings = Listing::active()
                ->featured()
                ->with(['category', 'city.state', 'state', 'primaryPhoto', 'ownerIdentityVerification'])
                ->withCount('media')
                ->latest('published_at')
                ->take(4)
                ->get();

            $latestListings = Listing::active()
                ->with(['category', 'city.state', 'state', 'primaryPhoto', 'ownerIdentityVerification'])
                ->withCount('media')
                ->latest('published_at')
                ->take(8)
                ->get();

            $stats['activeListings'] = Listing::active()->count();
        }

        if (Schema::hasTable('states')) {
            $allStates = State::orderBy('name')->get();
            $stats['coveredStates'] = $allStates->count();

            // Popular states in exact order matching reference design
            $targetCodes = ['CA', 'TX', 'FL', 'NY', 'IL', 'NV', 'WA', 'GA', 'CO', 'AZ'];
            $statesMap = State::whereIn('code', $targetCodes)->withCount(['listings' => fn($q) => $q->where('status', Listing::STATUS_ACTIVE)])->get()->keyBy('code');

            $popularStates = collect($targetCodes)->map(function ($code) use ($statesMap) {
                return $statesMap->get($code) ?? State::where('code', $code)->first();
            })->filter()->values();

            if ($popularStates->isEmpty()) {
                $popularStates = State::take(10)->get();
            }
        }

        if (Schema::hasTable('cities')) {
            $popularCities = City::with('state')
                ->where('is_popular', true)
                ->orWhere('population', '>', 500000)
                ->orderBy('name')
                ->take(12)
                ->get();

            if ($popularCities->isEmpty()) {
                $popularCities = City::with('state')->take(12)->get();
            }

            $stats['citiesCount'] = City::count();
        }

        if (Schema::hasTable('users')) {
            $stats['verifiedMembers'] = User::whereHas('validIdentityVerification')->count();
        }

        // Ordered Top Categories for horizontal card row matching reference design
        $topCategoriesSlugs = [
            'men-seeking-women' => ['title' => 'Men Seeking Women', 'subtitle' => '(M4W)', 'icon' => 'heart'],
            'men-seeking-men' => ['title' => 'Men Seeking Men', 'subtitle' => '(M4M)', 'icon' => 'users-male'],
            'men-seeking-couples' => ['title' => 'Men Seeking Couples', 'subtitle' => '', 'icon' => 'users-couple'],
            'women-seeking-men' => ['title' => 'Women Seeking Men', 'subtitle' => '(W4M)', 'icon' => 'female'],
            'women-seeking-women' => ['title' => 'Women Seeking Women', 'subtitle' => '(W4W)', 'icon' => 'users-female'],
            'women-seeking-couples' => ['title' => 'Women Seeking Couples', 'subtitle' => '', 'icon' => 'users-group'],
            'couples-seeking-men' => ['title' => 'Couples Seeking Men', 'subtitle' => '', 'icon' => 'couple-man'],
            'couples-seeking-women' => ['title' => 'Couples Seeking Women', 'subtitle' => '', 'icon' => 'couple-woman'],
            'couples-seeking-couples' => ['title' => 'Couples Seeking Couples', 'subtitle' => '', 'icon' => 'couples'],
        ];

        $homepageCategories = collect();
        foreach ($topCategoriesSlugs as $slug => $meta) {
            $cat = Category::where('slug', $slug)->first();
            if ($cat) {
                $homepageCategories->push([
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'title' => $meta['title'],
                    'subtitle' => $meta['subtitle'],
                    'slug' => $cat->slug,
                    'icon' => $meta['icon'],
                    'url' => route('categories.show', $cat->slug),
                ]);
            }
        }

        $seo = app(\App\Modules\SEO\Services\SeoService::class)->generateForHome();

        $delivery = app(\App\Modules\Advertising\Services\AdDeliveryService::class);
        $latestListingsFeed = $delivery->injectIntoListings($latestListings, \App\Modules\Advertising\Models\AdCampaign::SLOT_HOMEPAGE_FEED);
        $homepageFeaturedAds = $delivery->getAdsForSlot(\App\Modules\Advertising\Models\AdCampaign::SLOT_HOMEPAGE_FEATURED, [], 1);

        $heroImageUrl = asset('images/hero-model.jpg');
        $heroOfferLink = null;
        $heroOfferNewTab = true;
        $heroOfferAlt = 'LocalDateHub Dating Lifestyle';

        $sidebarPromoImageUrl = asset('images/promo-banner.jpg');
        $sidebarPromoIsCustom = false;
        $sidebarPromoOfferLink = null;
        $sidebarPromoOfferNewTab = true;
        $sidebarPromoOfferAlt = 'Find Your Perfect Match';

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
                $heroImageUrl = $this->resolveBrandImageUrl($rawHeroImage);
            }

            $heroOfferLink = $this->sanitizeOfferLink((string) ($brandSettings['hero_offer_link'] ?? ''));
            if (isset($brandSettings['hero_offer_new_tab'])) {
                $heroOfferNewTab = filter_var($brandSettings['hero_offer_new_tab'], FILTER_VALIDATE_BOOLEAN);
            }
            $rawHeroAlt = trim((string) ($brandSettings['hero_offer_alt'] ?? ''));
            if ($rawHeroAlt !== '') {
                $heroOfferAlt = $rawHeroAlt;
            }

            $rawSidebarImage = trim((string) ($brandSettings['sidebar_promo_image_url'] ?? ''));
            if ($rawSidebarImage !== '') {
                $sidebarPromoImageUrl = $this->resolveBrandImageUrl($rawSidebarImage);
                $sidebarPromoIsCustom = ltrim($rawSidebarImage, '/') !== 'images/promo-banner.jpg';
            }

            $sidebarPromoOfferLink = $this->sanitizeOfferLink((string) ($brandSettings['sidebar_promo_offer_link'] ?? ''));
            if (isset($brandSettings['sidebar_promo_offer_new_tab'])) {
                $sidebarPromoOfferNewTab = filter_var($brandSettings['sidebar_promo_offer_new_tab'], FILTER_VALIDATE_BOOLEAN);
            }
            $rawSidebarAlt = trim((string) ($brandSettings['sidebar_promo_offer_alt'] ?? ''));
            if ($rawSidebarAlt !== '') {
                $sidebarPromoOfferAlt = $rawSidebarAlt;
            }
        }

        return view('welcome', [
            'categories' => $categories,
            'homepageCategories' => $homepageCategories,
            'featuredListings' => $featuredListings,
            'homepageFeaturedAds' => $homepageFeaturedAds,
            'latestListings' => $latestListings,
            'latestListingsFeed' => $latestListingsFeed,
            'popularStates' => $popularStates,
            'allStates' => $allStates,
            'popularCities' => $popularCities,
            'stats' => $stats,
            'seo' => $seo,
            'heroImageUrl' => $heroImageUrl,
            'heroOfferLink' => $heroOfferLink,
            'heroOfferNewTab' => $heroOfferNewTab,
            'heroOfferAlt' => $heroOfferAlt,
            'sidebarPromoImageUrl' => $sidebarPromoImageUrl,
            'sidebarPromoIsCustom' => $sidebarPromoIsCustom,
            'sidebarPromoOfferLink' => $sidebarPromoOfferLink,
            'sidebarPromoOfferNewTab' => $sidebarPromoOfferNewTab,
            'sidebarPromoOfferAlt' => $sidebarPromoOfferAlt,
        ]);
    }

    protected function resolveBrandImageUrl(string $rawImage): string
    {
        return (str_starts_with($rawImage, 'http://') || str_starts_with($rawImage, 'https://'))
            ? $rawImage
            : asset(ltrim($rawImage, '/'));
    }

    protected function sanitizeOfferLink(string $rawUrl): ?string
    {
        $trimmed = trim($rawUrl);
        if ($trimmed === '') {
            return null;
        }

        $isRelative = str_starts_with($trimmed, '/') && !str_starts_with($trimmed, '//');
        $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));
        if ($isRelative || (in_array($scheme, ['http', 'https'], true) && filter_var($trimmed, FILTER_VALIDATE_URL))) {
            return $trimmed;
        }

        return null;
    }
}
