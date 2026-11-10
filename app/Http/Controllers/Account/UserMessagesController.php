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
     * Display premium Messenger dashboard.
     */
    public function index(Request $request): View
    {
        return view('account.messages.index', [
            'conversation' => $request->input('conversation') ? (int) $request->input('conversation') : null,
            'listing' => $request->input('listing') ? (int) $request->input('listing') : null,
            'user' => $request->input('user') ? (int) $request->input('user') : null,
        ]);
    }

    /**
     * View inquiry details and redirect to conversation in Messenger.
     */
    public function show(Request $request, ContactMessage $message): RedirectResponse
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

        return redirect()->route('account.messages.index', ['conversation' => $message->id]);
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

