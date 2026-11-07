<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationMessagesController extends Controller
{
    /**
     * Display the moderation queue of flagged messages and safety reports.
     */
    public function index(Request $request): View
    {
        $query = ContactMessage::query()
            ->where(function ($q) {
                $q->where('is_flagged', true)
                  ->orWhere('inquiry_type', 'safety_report');
            })
            ->with(['listing.city.state', 'recipient', 'sender'])
            ->latest();

        if ($request->filled('type')) {
            $query->where('inquiry_type', $request->input('type'));
        }

        $messages = $query->paginate(20)->withQueryString();

        $totalFlagged = ContactMessage::where('is_flagged', true)->count();
        $totalSafetyReports = ContactMessage::where('inquiry_type', 'safety_report')->count();

        return view('admin.moderation.messages', [
            'messages' => $messages,
            'totalFlagged' => $totalFlagged,
            'totalSafetyReports' => $totalSafetyReports,
        ]);
    }

    /**
     * Dismiss flag on message after moderation review.
     */
    public function dismiss(ContactMessage $message): RedirectResponse
    {
        $message->is_flagged = false;
        $message->flagged_reason = null;
        $message->save();

        return redirect()->back()->with('success', "Flag on message #{$message->id} has been dismissed.");
    }

    /**
     * Take disciplinary moderation action (e.g. suspend listing).
     */
    public function takeAction(Request $request, ContactMessage $message): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:suspend_listing,dismiss'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['action'] === 'suspend_listing' && $message->listing) {
            $listing = $message->listing;
            $listing->status = Listing::STATUS_SUSPENDED;
            $listing->rejection_reason = $validated['note'] ?: "Suspended following safety review of report #{$message->id}";
            $listing->save();

            $listing->recordStatusChange(
                Listing::STATUS_SUSPENDED,
                $listing->rejection_reason,
                $request->user()?->id
            );

            $message->is_flagged = false;
            $message->save();

            return redirect()->back()->with('success', "Listing [{$listing->title}] has been suspended.");
        }

        return $this->dismiss($message);
    }
}

