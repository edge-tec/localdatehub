<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Services\ListingManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserListingController extends Controller
{
    public function __construct(
        protected ListingManagementService $listingService
    ) {}

    /**
     * User Listings Dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Listing::where('user_id', $user->id)
            ->with(['category', 'city', 'state', 'primaryPhoto'])
            ->latest();

        $status = $request->input('status');
        if ($status && in_array($status, ['active', 'pending_review', 'draft', 'rejected', 'expired', 'paused'])) {
            $query->where('status', $status);
        }

        $listings = $query->paginate(12)->withQueryString();

        $counts = [
            'all' => Listing::where('user_id', $user->id)->count(),
            'active' => Listing::where('user_id', $user->id)->where('status', 'active')->count(),
            'pending' => Listing::where('user_id', $user->id)->where('status', 'pending_review')->count(),
            'draft' => Listing::where('user_id', $user->id)->where('status', 'draft')->count(),
            'rejected' => Listing::where('user_id', $user->id)->where('status', 'rejected')->count(),
            'expired' => Listing::where('user_id', $user->id)->where('status', 'expired')->count(),
        ];

        return view('account.listings.index', [
            'listings' => $listings,
            'counts' => $counts,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Renew an active or expired listing.
     */
    public function renew(Listing $listing, Request $request): RedirectResponse
    {
        Gate::authorize('renew', $listing);

        try {
            $this->listingService->renewListing($listing, $request->user());
            return back()->with('status', "Listing '{$listing->title}' renewed for 30 days.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pause an active listing.
     */
    public function pause(Listing $listing, Request $request): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $this->listingService->pauseListing($listing, $request->user());
        return back()->with('status', "Listing '{$listing->title}' has been paused.");
    }

    /**
     * Resume a paused listing.
     */
    public function resume(Listing $listing, Request $request): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $this->listingService->resumeListing($listing, $request->user());
        return back()->with('status', "Listing '{$listing->title}' is now active.");
    }

    /**
     * Soft delete a listing.
     */
    public function destroy(Listing $listing, Request $request): RedirectResponse
    {
        Gate::authorize('delete', $listing);

        $this->listingService->deleteListing($listing, $request->user(), $request->input('reason'));
        return back()->with('status', "Listing '{$listing->title}' removed.");
    }
}

