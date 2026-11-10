<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserMessagesController extends Controller
{
    /**
     * Display private inbox of inquiries received on the user's classified listings.
     */
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $query = ContactMessage::query()
            ->where('recipient_id', $userId)
            ->where('inquiry_type', 'listing_inquiry')
            ->with(['listing.primaryPhoto', 'listing.city', 'listing.state'])
            ->latest();

        // Filter by listing
        if ($request->filled('listing_id')) {
            $query->where('listing_id', (int) $request->input('listing_id'));
        }

        // Filter by unread
        if ($request->boolean('unread')) {
            $query->where('is_read', false);
        }

        // Filter by replied
        if ($request->boolean('replied')) {
            $query->where('is_replied', true);
        }

        $messages = $query->paginate(15)->withQueryString();

        $unreadCount = ContactMessage::where('recipient_id', $userId)
            ->where('inquiry_type', 'listing_inquiry')
            ->where('is_read', false)
            ->count();

        $userListings = Listing::where('user_id', $userId)
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'status']);

        return view('account.messages.index', [
            'messages' => $messages,
            'unreadCount' => $unreadCount,
            'userListings' => $userListings,
            'selectedListingId' => $request->input('listing_id'),
            'unreadOnly' => $request->boolean('unread'),
        ]);
    }

    /**
     * View inquiry details and decrypt communication payload.
     */
    public function show(Request $request, ContactMessage $message): View
    {
        Gate::authorize('view', $message);

        // Mark as read upon inspection
        if (!$message->is_read) {
            $message->is_read = true;
            $message->read_at = now();
            $message->save();
        }

        $user = $request->user();

        if ($message->listing_id && $user && $message->recipient_id === $user->id) {
            ContactMessage::where('listing_id', $message->listing_id)
                ->where('recipient_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);
        }

        if ($user) {
            \App\Modules\Notifications\Models\NotificationLog::where('user_id', $user->id)
                ->whereIn('notification_type', ['contact_received', 'message_reply'])
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);
        }

        $message->loadMissing(['listing.primaryPhoto', 'listing.city', 'listing.state', 'sender']);

        return view('account.messages.show', [
            'message' => $message,
            'listing' => $message->listing,
        ]);
    }

    /**
     * Mark an inquiry as replied.
     */
    public function markReplied(ContactMessage $message): RedirectResponse
    {
        Gate::authorize('update', $message);

        $message->is_replied = true;
        $message->save();

        return redirect()->back()->with('success', 'Inquiry marked as replied.');
    }

    /**
     * Flag an inquiry message as spam, harassment, or violation.
     */
    public function flag(Request $request, ContactMessage $message): RedirectResponse
    {
        Gate::authorize('update', $message);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:spam,harassment,commercial_solicitation,inappropriate,other'],
        ]);

        $message->is_flagged = true;
        $message->flagged_reason = $validated['reason'];
        $message->save();

        return redirect()->back()->with('success', 'Message has been flagged and submitted to Trust & Safety moderation.');
    }

    /**
     * Soft delete an inquiry from the user's inbox.
     */
    public function destroy(ContactMessage $message): RedirectResponse
    {
        Gate::authorize('delete', $message);

        $message->delete();

        return redirect()->route('account.messages.index')->with('success', 'Inquiry deleted from your inbox.');
    }
}

