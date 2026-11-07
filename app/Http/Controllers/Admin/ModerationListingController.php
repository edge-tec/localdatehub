<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Services\ListingManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationListingController extends Controller
{
    public function __construct(
        protected ListingManagementService $listingService
    ) {}

    protected function authorizeModerator(Request $request): void
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'super_admin', 'moderator'], true)) {
            abort(403, 'Unauthorized moderation access.');
        }
    }

    /**
     * Moderation queue of listings pending review.
     */
    public function queue(Request $request): View
    {
        $this->authorizeModerator($request);

        $listings = Listing::where('status', Listing::STATUS_PENDING_REVIEW)
            ->with(['user', 'category', 'city', 'state', 'media', 'attributes'])
            ->oldest('created_at')
            ->paginate(15);

        $pendingCount = Listing::where('status', Listing::STATUS_PENDING_REVIEW)->count();
        $totalApproved = Listing::where('status', Listing::STATUS_ACTIVE)->count();
        $totalRejected = Listing::where('status', Listing::STATUS_REJECTED)->count();

        return view('admin.moderation.listings', [
            'listings' => $listings,
            'pendingCount' => $pendingCount,
            'totalApproved' => $totalApproved,
            'totalRejected' => $totalRejected,
        ]);
    }

    /**
     * Approve listing.
     */
    public function approve(Listing $listing, Request $request): RedirectResponse
    {
        $this->authorizeModerator($request);

        $this->listingService->approveListing($listing, $request->user());
        return back()->with('status', "Listing #{$listing->id} '{$listing->title}' approved and published.");
    }

    /**
     * Reject listing with mandatory reason.
     */
    public function reject(Listing $listing, Request $request): RedirectResponse
    {
        $this->authorizeModerator($request);

        $request->validate([
            'reason' => 'required|string|min:5|max:255',
        ]);

        $this->listingService->rejectListing($listing, $request->input('reason'), $request->user());
        return back()->with('status', "Listing #{$listing->id} rejected.");
    }

    /**
     * Suspend listing.
     */
    public function suspend(Listing $listing, Request $request): RedirectResponse
    {
        $this->authorizeModerator($request);

        $request->validate([
            'reason' => 'required|string|min:5|max:255',
        ]);

        $this->listingService->suspendListing($listing, $request->input('reason'), $request->user());
        return back()->with('status', "Listing #{$listing->id} suspended.");
    }
}
