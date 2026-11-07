<?php

namespace App\Http\Controllers;

use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Services\ListingManagementService;
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
            ->with(['category', 'state', 'city', 'zipCode', 'location', 'contact', 'attributes.selectedOption', 'approvedMedia'])
            ->firstOrFail();

        Gate::authorize('view', $listing);

        // Record daily analytics view
        $listing->recordAnalyticsEvent('views');

        return view('listings.show', [
            'listing' => $listing,
        ]);
    }
}
