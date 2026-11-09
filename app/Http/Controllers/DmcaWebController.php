<?php

namespace App\Http\Controllers;

use App\Modules\Moderation\Models\PolicyVersion;
use App\Modules\Moderation\Services\ComplianceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DmcaWebController extends Controller
{
    public function __construct(
        protected ComplianceService $complianceService
    ) {}

    /**
     * Show the public DMCA Notice & Takedown submission portal.
     */
    public function show(): View
    {
        $policy = PolicyVersion::current(PolicyVersion::TYPE_DMCA);

        return view('pages.dmca', [
            'policy' => $policy,
        ]);
    }

    /**
     * Submit a formal DMCA takedown or non-consensual imagery report.
     */
    public function submit(Request $request): RedirectResponse
    {
        $throttleKey = 'dmca_submit:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'copyrighted_work' => "Too many DMCA notices filed recently. Please wait {$seconds} seconds before trying again.",
            ]);
        }
        RateLimiter::hit($throttleKey, 180);

        $validated = $request->validate([
            'claimant_name' => ['required', 'string', 'max:120'],
            'claimant_email' => ['required', 'email', 'max:120'],
            'claimant_phone' => ['nullable', 'string', 'max:40'],
            'organization' => ['nullable', 'string', 'max:150'],
            'copyrighted_work' => ['required', 'string', 'min:10', 'max:5000'],
            'infringing_url' => ['required', 'url', 'max:255'],
            'target_listing_id' => ['nullable', 'integer', 'exists:listings,id'],
            'good_faith_attested' => ['accepted'],
            'perjury_attested' => ['accepted'],
            'digital_signature' => ['required', 'string', 'min:3', 'max:120'],
        ]);

        $validated['claimant_name'] = strip_tags(trim($validated['claimant_name']));
        $validated['organization'] = $validated['organization'] ? strip_tags(trim($validated['organization'])) : null;
        $validated['copyrighted_work'] = strip_tags(trim($validated['copyrighted_work']));
        $validated['digital_signature'] = strip_tags(trim($validated['digital_signature']));

        $dmca = $this->complianceService->fileDmcaRequest($validated);

        return redirect()->route('dmca.show')->with(
            'success',
            "Your DMCA notice has been received under Ticket #{$dmca->ticket_number}. Infringing content has been quarantined for review."
        );
    }
}

