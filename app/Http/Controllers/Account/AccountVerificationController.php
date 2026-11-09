<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Modules\Verification\Models\IdentityVerification;
use App\Modules\Verification\Services\IdentityVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountVerificationController extends Controller
{
    public function __construct(
        protected IdentityVerificationService $verificationService
    ) {}

    /**
     * View verification status & submission page.
     */
    public function show()
    {
        $user = Auth::user();
        $latestVerification = $user->latestVerification;

        return view('account.verification.show', [
            'user' => $user,
            'verification' => $latestVerification,
        ]);
    }

    /**
     * Submit identity verification application.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Check if already verified or pending
        if ($user->is_identity_verified) {
            return back()->with('status', 'Your account is already identity-verified.');
        }

        $latest = $user->latestVerification;
        if ($latest && $latest->isPending()) {
            return back()->with('error', 'You already have an identity verification application under review.');
        }

        $validated = $request->validate([
            'document_type' => 'required|in:drivers_license,passport,national_id,state_id',
            'document_front' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240',
            'document_back' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:10240',
            'selfie' => 'required|file|mimes:jpeg,jpg,png|max:10240',
        ]);

        $this->verificationService->submitVerification(
            $user,
            $validated['document_type'],
            $request->file('document_front'),
            $request->file('document_back'),
            $request->file('selfie')
        );

        return redirect()->route('account.verification.show')
            ->with('status', 'Your identity verification documents have been submitted securely. An administrator will review your application shortly.');
    }
}

