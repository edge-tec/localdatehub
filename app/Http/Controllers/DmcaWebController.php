<?php

namespace App\Http\Controllers;

use App\Modules\Moderation\Models\PolicyVersion;
use App\Modules\Moderation\Services\ComplianceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $dmca = $this->complianceService->fileDmcaRequest($validated);

        return redirect()->route('dmca.show')->with(
            'success',
            "Your DMCA notice has been received under Ticket #{$dmca->ticket_number}. Infringing content has been quarantined for review."
        );
    }
}

