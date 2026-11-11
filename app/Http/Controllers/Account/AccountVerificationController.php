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
        $latestVerification = $user->verifications()
            ->with('reviewer')
            ->latest('id')
            ->first();

        return view('account.verification.show', [
            'user' => $user,
            'verification' => $latestVerification,
            'canonicalStatus' => $user->identityVerificationStatus(),
            'statusLabel' => $latestVerification?->statusLabel() ?? 'Not Submitted',
            'isVerified' => $user->hasVerifiedIdentity(),
        ]);
    }

    /**
     * Return authoritative identity verification status for the authenticated user.
     */
    public function status()
    {
        $user = Auth::user();
        $latestVerification = $user->verifications()->latest('id')->first();
        $canonicalStatus = $user->identityVerificationStatus();
        $isVerified = $user->hasVerifiedIdentity();

        return response()->json([
            'user_id' => $user->id,
            'is_identity_verified' => $isVerified,
            'status' => $canonicalStatus,
            'status_label' => $latestVerification?->statusLabel() ?? 'Not Submitted',
            'document_type' => $latestVerification?->document_type,
            'reviewed_at' => $latestVerification?->reviewed_at?->toIso8601String(),
            'verified_at' => $isVerified ? ($user->identity_verified_at?->toIso8601String() ?? $latestVerification?->reviewed_at?->toIso8601String()) : null,
            'expires_at' => $latestVerification?->expires_at?->toIso8601String(),
            'rejection_reason' => $latestVerification?->rejection_reason,
            'resubmit_allowed' => !$isVerified && !in_array($canonicalStatus, [
                IdentityVerification::STATUS_PENDING,
                IdentityVerification::STATUS_UNDER_REVIEW,
            ], true),
        ]);
    }

    /**
     * Submit identity verification application.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Check if already validly verified via an approved non-expired ID record
        if ($user->hasVerifiedIdentity()) {
            return back()->with('status', 'Your account is already identity-verified.');
        }

        $latest = $user->verifications()->latest('id')->first();
        if ($latest && ($latest->isPending() || $latest->isUnderReview())) {
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

