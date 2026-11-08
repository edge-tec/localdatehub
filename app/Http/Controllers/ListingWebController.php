<?php

namespace App\Http\Controllers;

use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Services\AnalyticsTrackingService;
use App\Modules\Listings\Services\ListingManagementService;
use App\Modules\SEO\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ListingWebController extends Controller
{
    public function __construct(
        protected ListingManagementService $listingService
    ) {}

    /**
     * Show multi-step listing creation wizard.
     */
    public function create(): View
    {
        return view('listings.create');
    }

    /**
     * Show edit listing wizard.
     */
    public function edit(Listing $listing): View
    {
        Gate::authorize('update', $listing);

        return view('listings.edit', [
            'listing' => $listing,
        ]);
    }

    /**
     * Display public listing detail page.
     */
    public function show(string $slug, Request $request): View
    {
        $listing = Listing::where('slug', $slug)
            ->with(['user', 'category.parent', 'state', 'city', 'zipCode', 'location', 'contact', 'attributes.selectedOption', 'approvedMedia'])
            ->firstOrFail();

        Gate::authorize('view', $listing);

        // Record privacy-conscious daily analytics view
        app(AnalyticsTrackingService::class)->recordListingView($listing, $request);

        // Fetch similar listings in same category or city
        $similarListings = Listing::query()
            ->active()
            ->where('id', '!=', $listing->id)
            ->where(function ($q) use ($listing) {
                $q->where('category_id', $listing->category_id)
                  ->orWhere('city_id', $listing->city_id);
            })
            ->with(['category', 'city.state', 'state', 'primaryPhoto'])
            ->latest('published_at')
            ->take(3)
            ->get();

        $isFavorited = $request->user()
            ? $request->user()->favorites()->where('listing_id', $listing->id)->exists()
            : false;

        $seo = app(SeoService::class)->generateForListing($listing);

        return view('listings.show', [
            'listing' => $listing,
            'similarListings' => $similarListings,
            'isFavorited' => $isFavorited,
            'seo' => $seo,
        ]);
    }
}

