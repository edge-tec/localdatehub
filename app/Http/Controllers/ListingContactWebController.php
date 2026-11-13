<?php

namespace App\Http\Controllers;

use App\Modules\Contact\Requests\ListingInquiryRequest;
use App\Modules\Contact\Requests\ListingReportRequest;
use App\Modules\Contact\Services\ContactService;
use App\Modules\Listings\Models\Listing;
use App\Support\Exceptions\ProhibitedContentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ListingContactWebController extends Controller
{
    public function __construct(
        protected ContactService $contactService
    ) {}

    /**
     * Submit an inquiry to a listing advertiser.
     */
    public function submitInquiry(ListingInquiryRequest $request, Listing $listing): RedirectResponse
    {
        // Honeypot check
        if (!empty($request->input('contact_hp'))) {
            return redirect()->back()->with('error', 'Spam detection triggered. Your message was discarded.');
        }

        try {
            $this->contactService->sendListingInquiry(
                $listing,
                $request->validated(),
                $request->user(),
                $request->ip(),
                $request->userAgent()
            );

            return redirect()->back()->with('success', 'Your inquiry has been safely encrypted and sent to the advertiser.');
        } catch (ProhibitedContentException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['message' => $e->getMessage()]);
        } catch (InvalidArgumentException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Submit a confidential trust & safety report against a listing.
     */
    public function reportListing(ListingReportRequest $request, Listing $listing): RedirectResponse
    {
        // Honeypot check
        if (!empty($request->input('report_hp'))) {
            return redirect()->back()->with('error', 'Invalid report submission.');
        }

        $this->contactService->reportListingAbuse(
            $listing,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        $listing->refresh();

        if (!$listing->isActive()) {
            $redirect = ($listing->state && $listing->city)
                ? redirect()->route('locations.city', [
                    'stateCode' => $listing->state->code,
                    'citySlug' => $listing->city->slug,
                ])
                : redirect()->route('home');

            return $redirect->with(
                'success',
                'Thank you for reporting. This listing has been temporarily removed from public view for immediate Trust & Safety review.'
            );
        }

        return redirect()->back()->with('success', 'Thank you for reporting. Our Trust & Safety team has received your report and will take appropriate action.');
    }

    /**
     * Toggle saved/favorite status for a listing.
     */
    public function toggleFavorite(Listing $listing, Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Authentication required'], 401);
            }
            return redirect()->guest(route('login'));
        }

        $isFavorited = $this->contactService->toggleFavorite($listing, $user);

        if ($request->wantsJson()) {
            return response()->json([
                'favorited' => $isFavorited,
                'favorite_count' => $listing->fresh()->favorite_count,
            ]);
        }

        $msg = $isFavorited ? 'Listing saved to your favorites!' : 'Listing removed from your favorites.';
        return redirect()->back()->with('status', $msg);
    }
}

