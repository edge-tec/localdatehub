<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Moderation\DTOs\ReportData;
use App\Modules\Moderation\Services\ModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class UserReportWebController extends Controller
{
    public function __construct(
        protected ModerationService $moderationService
    ) {}

    /**
     * Submit a user abuse report.
     */
    public function submit(Request $request, User $user): RedirectResponse
    {
        $throttleKey = 'user_report:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'details' => "Too many reports submitted recently. Please wait {$seconds} seconds before filing another report.",
            ]);
        }
        RateLimiter::hit($throttleKey, 120);

        // Prevent reporting oneself
        if ($request->user() && $request->user()->id === $user->id) {
            return redirect()->back()->with('error', 'You cannot report your own account.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:' . implode(',', ModerationService::VALID_REASONS)],
            'details' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        $report = $this->moderationService->submitReport(new ReportData(
            reporterId: $request->user()?->id ?: 0,
            reportedUserId: $user->id,
            reason: $validated['reason'],
            details: strip_tags(trim($validated['details'])),
            listingId: null,
            evidenceUrls: []
        ));

        return redirect()->back()->with(
            'success',
            "Thank you. Your report (#{$report['ticket_id']}) has been filed and routed to Trust & Safety reviewers."
        );
    }
}

