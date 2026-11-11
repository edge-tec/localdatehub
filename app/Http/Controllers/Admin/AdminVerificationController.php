<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Verification\Models\IdentityVerification;
use App\Modules\Verification\Models\VerificationAuditLog;
use App\Modules\Verification\Services\IdentityVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminVerificationController extends Controller
{
    public function __construct(
        protected IdentityVerificationService $verificationService
    ) {}

    /**
     * Identity Verification Queue & Directory.
     */
    public function index(Request $request)
    {
        $status = (string) $request->input('status', 'pending');

        $query = IdentityVerification::with(['user', 'reviewer'])->latest('id');

        match ($status) {
            'pending' => $query->where('status', IdentityVerification::STATUS_PENDING),
            'under_review' => $query->where('status', IdentityVerification::STATUS_UNDER_REVIEW),
            'approved', 'verified' => $query->validApproved(),
            'rejected' => $query->where('status', IdentityVerification::STATUS_REJECTED),
            'resubmission_required', 'additional_info_required' => $query->whereIn('status', [
                IdentityVerification::STATUS_RESUBMISSION_REQUIRED,
                IdentityVerification::STATUS_ADDITIONAL_INFO_REQUIRED,
            ]),
            'revoked', 'suspended' => $query->whereIn('status', [
                IdentityVerification::STATUS_REVOKED,
                IdentityVerification::STATUS_SUSPENDED,
            ]),
            'expired' => $query->where(function ($q) {
                $q->where('status', IdentityVerification::STATUS_EXPIRED)
                  ->orWhere(function ($sub) {
                      $sub->whereIn('status', IdentityVerification::APPROVED_STATUSES)
                          ->whereNotNull('expires_at')
                          ->where('expires_at', '<=', now());
                  });
            }),
            default => null,
        };

        $verifications = $query->paginate(20)->withQueryString();

        $counts = [
            'pending' => IdentityVerification::where('status', IdentityVerification::STATUS_PENDING)->count(),
            'under_review' => IdentityVerification::where('status', IdentityVerification::STATUS_UNDER_REVIEW)->count(),
            'verified' => IdentityVerification::validApproved()->count(),
            'approved' => IdentityVerification::validApproved()->count(),
            'rejected' => IdentityVerification::where('status', IdentityVerification::STATUS_REJECTED)->count(),
            'resubmission_required' => IdentityVerification::whereIn('status', [
                IdentityVerification::STATUS_RESUBMISSION_REQUIRED,
                IdentityVerification::STATUS_ADDITIONAL_INFO_REQUIRED,
            ])->count(),
            'revoked' => IdentityVerification::whereIn('status', [
                IdentityVerification::STATUS_REVOKED,
                IdentityVerification::STATUS_SUSPENDED,
            ])->count(),
            'expired' => IdentityVerification::where('status', IdentityVerification::STATUS_EXPIRED)
                ->orWhere(function ($sub) {
                    $sub->whereIn('status', IdentityVerification::APPROVED_STATUSES)
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now());
                })->count(),
            'all' => IdentityVerification::count(),
        ];

        return view('admin.verifications.index', [
            'verifications' => $verifications,
            'currentStatus' => $status,
            'counts' => $counts,
        ]);
    }

    /**
     * Inspect Single Identity Verification Request.
     */
    public function show(IdentityVerification $verification)
    {
        $verification->load(['user', 'reviewer', 'auditLogs.actor']);

        // Log document inspection
        VerificationAuditLog::create([
            'verification_id' => $verification->id,
            'actor_id' => Auth::id(),
            'action' => 'document_viewed',
            'details' => 'Administrator inspected verification files and details.',
            'created_at' => now(),
        ]);

        return view('admin.verifications.show', [
            'verification' => $verification,
        ]);
    }

    /**
     * Securely stream private document file for administrator review.
     */
    public function downloadDocument(IdentityVerification $verification, string $type)
    {
        $path = match ($type) {
            'front' => $verification->document_front_path,
            'back' => $verification->document_back_path,
            'selfie' => $verification->selfie_path,
            default => null,
        };

        if (!$path || !Storage::disk(IdentityVerificationService::DISK)->exists($path)) {
            abort(404, 'Requested verification document file not found.');
        }

        return Storage::disk(IdentityVerificationService::DISK)->response($path);
    }

    /**
     * Move verification request into active review.
     */
    public function markUnderReview(IdentityVerification $verification)
    {
        $this->verificationService->markUnderReview($verification, Auth::user());

        return back()->with('status', "Verification for {$verification->user->name} marked as under review.");
    }

    /**
     * Approve verification request.
     */
    public function approve(Request $request, IdentityVerification $verification)
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $expiresAt = !empty($validated['expires_at'])
            ? \Illuminate\Support\Carbon::parse($validated['expires_at'])
            : null;

        $this->verificationService->approve(
            $verification,
            Auth::user(),
            $validated['admin_notes'] ?? null,
            $expiresAt
        );

        return redirect()->route('admin.verifications.index')
            ->with('status', "Identity verification approved for {$verification->user->name}. Verified badge activated.");
    }

    /**
     * Reject verification request.
     */
    public function reject(Request $request, IdentityVerification $verification)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $this->verificationService->reject($verification, Auth::user(), $validated['rejection_reason']);

        return redirect()->route('admin.verifications.index')
            ->with('status', "Verification application rejected for {$verification->user->name}.");
    }

    /**
     * Request resubmission of identity documents.
     */
    public function requestResubmission(Request $request, IdentityVerification $verification)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->verificationService->requestResubmission($verification, Auth::user(), $validated['reason']);

        return redirect()->route('admin.verifications.index')
            ->with('status', "Resubmission requested for {$verification->user->name}.");
    }

    /**
     * Revoke previously approved verification and strip Blue Verified badge immediately.
     */
    public function revoke(Request $request, IdentityVerification $verification)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->verificationService->revoke($verification, Auth::user(), $validated['reason']);

        return redirect()->route('admin.verifications.index')
            ->with('status', "Verification revoked and badge removed for {$verification->user->name}.");
    }

    /**
     * Suspend verification (alias to revoke).
     */
    public function suspend(Request $request, IdentityVerification $verification)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->verificationService->revoke($verification, Auth::user(), $validated['reason']);

        return redirect()->route('admin.verifications.index')
            ->with('status', "Verification revoked and badge removed for {$verification->user->name}.");
    }
}

