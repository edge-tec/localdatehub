<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Display the Enterprise Administration Hub.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'dashboard');

        return view('admin.dashboard', [
            'initialTab' => $tab,
        ]);
    }

    /**
     * Real-time administrative notification & alert feed.
     */
    public function notificationFeed(Request $request): \Illuminate\Http\JsonResponse
    {
        $admin = Auth::user();
        if (!$admin) {
            return response()->json(['ok' => false], 401);
        }

        $items = collect();

        // 1. Pending Identity Verification Requests (Government ID + Selfie)
        $pendingIdVerifications = \App\Modules\Verification\Models\IdentityVerification::with('user')
            ->where('status', 'pending')
            ->latest('id')
            ->take(6)
            ->get();
        $pendingIdCount = \App\Modules\Verification\Models\IdentityVerification::where('status', 'pending')->count();

        foreach ($pendingIdVerifications as $iv) {
            $items->push([
                'id' => 'id_verif_' . $iv->id,
                'category' => 'id_verification',
                'badge_color' => 'bg-blue-50 text-blue-700 border-blue-200',
                'badge_label' => 'ID Verification',
                'icon_type' => 'id_verification',
                'title' => "ID Verification: " . ($iv->user?->name ?? 'Candidate #' . $iv->user_id),
                'snippet' => ucwords(str_replace('_', ' ', $iv->document_type)) . " submitted with photo ID & selfie",
                'url' => route('admin.verifications.show', $iv),
                'timestamp' => $iv->submitted_at?->timestamp ?? $iv->created_at->timestamp,
                'time_ago' => ($iv->submitted_at ?? $iv->created_at)->diffForHumans(),
                'is_unread' => true,
            ]);
        }

        // 2. Pending Listings (Awaiting moderation approval)
        $pendingListings = \App\Modules\Listings\Models\Listing::with(['user', 'city'])
            ->whereIn('status', ['pending', 'under_review'])
            ->latest('id')
            ->take(6)
            ->get();
        $pendingListingCount = \App\Modules\Listings\Models\Listing::whereIn('status', ['pending', 'under_review'])->count();

        foreach ($pendingListings as $l) {
            $items->push([
                'id' => 'listing_' . $l->id,
                'category' => 'listing_pending',
                'badge_color' => 'bg-amber-50 text-amber-700 border-amber-200',
                'badge_label' => 'Pending Listing',
                'icon_type' => 'listing_pending',
                'title' => "Pending Ad: " . \Illuminate\Support\Str::limit($l->title, 35),
                'snippet' => ($l->city?->name ? "{$l->city->name} · " : '') . "Posted by " . ($l->user?->name ?? 'Member'),
                'url' => route('admin.dashboard', ['tab' => 'listings', 'inspect_listing' => $l->id]),
                'timestamp' => $l->created_at->timestamp,
                'time_ago' => $l->created_at->diffForHumans(),
                'is_unread' => true,
            ]);
        }

        // 3. Unread Abuse & Content Reports
        $unreadReports = \App\Modules\Moderation\Models\ContentReport::where('is_read', false)
            ->latest('id')
            ->take(6)
            ->get();
        $unreadReportsCount = \App\Modules\Moderation\Models\ContentReport::where('is_read', false)->count();

        foreach ($unreadReports as $r) {
            $items->push([
                'id' => 'report_' . $r->id,
                'category' => 'abuse_report',
                'badge_color' => 'bg-rose-50 text-rose-700 border-rose-200',
                'badge_label' => 'Abuse Report',
                'icon_type' => 'abuse_report',
                'title' => "Report #{$r->id}: " . ucwords(str_replace('_', ' ', $r->reason_code)),
                'snippet' => \Illuminate\Support\Str::limit($r->details ?: 'Content reported for safety review.', 55),
                'url' => route('admin.dashboard', ['tab' => 'reports', 'inspect_report' => $r->id]),
                'timestamp' => $r->created_at->timestamp,
                'time_ago' => $r->created_at->diffForHumans(),
                'is_unread' => true,
            ]);
        }

        // 4. Unread Messages / Inquiries to Admin
        $unreadMessages = \App\Modules\Contact\Models\ContactMessage::where(function ($q) use ($admin) {
                $q->where('is_flagged', true)
                  ->orWhere('recipient_id', $admin->id)
                  ->orWhereNull('listing_id');
            })
            ->where('is_read', false)
            ->latest('id')
            ->take(6)
            ->get();
        $unreadMessagesCount = \App\Modules\Contact\Models\ContactMessage::where(function ($q) use ($admin) {
                $q->where('is_flagged', true)
                  ->orWhere('recipient_id', $admin->id)
                  ->orWhereNull('listing_id');
            })
            ->where('is_read', false)
            ->count();

        foreach ($unreadMessages as $m) {
            $items->push([
                'id' => 'message_' . $m->id,
                'category' => 'contact_message',
                'badge_color' => 'bg-pink-50 text-[#F20B65] border-pink-200',
                'badge_label' => 'Inquiry',
                'icon_type' => 'contact_message',
                'title' => "Message: " . ($m->sender_name ?: 'Visitor'),
                'snippet' => \Illuminate\Support\Str::limit($m->subject ?: $m->message_encrypted, 55),
                'url' => route('admin.dashboard', ['tab' => $m->listing_id === null ? 'support_tickets' : 'abuse']),
                'timestamp' => $m->created_at->timestamp,
                'time_ago' => $m->created_at->diffForHumans(),
                'is_unread' => true,
            ]);
        }

        // 5. System Notification Logs (Admin target)
        $unreadLogs = \App\Modules\Notifications\Models\NotificationLog::where('is_read', false)
            ->where(function ($q) use ($admin) {
                $q->where('recipient_target', 'admin')
                  ->orWhere('user_id', $admin->id)
                  ->orWhereNull('user_id');
            })
            ->latest('id')
            ->take(6)
            ->get();
        $unreadLogsCount = \App\Modules\Notifications\Models\NotificationLog::where('is_read', false)
            ->where(function ($q) use ($admin) {
                $q->where('recipient_target', 'admin')
                  ->orWhere('user_id', $admin->id)
                  ->orWhereNull('user_id');
            })
            ->count();

        foreach ($unreadLogs as $nl) {
            $items->push([
                'id' => 'notif_' . $nl->id,
                'category' => 'system_log',
                'badge_color' => 'bg-slate-100 text-slate-700 border-slate-200',
                'badge_label' => 'System Alert',
                'icon_type' => 'system_log',
                'title' => \Illuminate\Support\Str::limit($nl->subject ?: 'System Alert', 40),
                'snippet' => \Illuminate\Support\Str::limit($nl->message, 55),
                'url' => route('admin.dashboard', ['tab' => 'notifications', 'inspect_notification' => $nl->id]),
                'timestamp' => $nl->created_at->timestamp,
                'time_ago' => $nl->created_at->diffForHumans(),
                'is_unread' => true,
            ]);
        }

        // Sort combined feed by timestamp descending
        $sortedItems = $items->unique('id')->sortByDesc('timestamp')->values()->take(12);

        $totalUnread = $pendingIdCount + $pendingListingCount + $unreadReportsCount + $unreadMessagesCount + $unreadLogsCount;

        return response()->json([
            'ok' => true,
            'total_unread' => $totalUnread,
            'counts' => [
                'id_verifications' => $pendingIdCount,
                'pending_listings' => $pendingListingCount,
                'reports' => $unreadReportsCount,
                'messages' => $unreadMessagesCount,
                'system_logs' => $unreadLogsCount,
            ],
            'items' => $sortedItems,
        ]);
    }

    /**
     * Stop active impersonation session and return to the original administrator account.
     */
    public function stopImpersonation(Request $request): RedirectResponse
    {
        $adminId = session()->pull('impersonator_admin_id');
        session()->forget('impersonator_admin_name');
        session()->forget('impersonation_target_role');

        if ($adminId) {
            $admin = User::find($adminId);
            if ($admin) {
                Auth::login($admin);
                $request->session()->regenerate();
                return redirect()->route('admin.index', ['tab' => 'users'])->with('success', "Exited user impersonation session. Returned to Administrator account ({$admin->name}).");
            }
        }

        return redirect()->route('home');
    }
}

