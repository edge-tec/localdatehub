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
        $status = $request->input('status', 'pending');

        $query = IdentityVerification::with(['user', 'reviewer'])->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $verifications = $query->paginate(20)->withQueryString();

        $counts = [
            'pending' => IdentityVerification::where('status', IdentityVerification::STATUS_PENDING)->count(),
            'verified' => IdentityVerification::where('status', IdentityVerification::STATUS_VERIFIED)->count(),
            'rejected' => IdentityVerification::where('status', IdentityVerification::STATUS_REJECTED)->count(),
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
     * Approve verification request.
     */
    public function approve(Request $request, IdentityVerification $verification)
    {
        $notes = $request->input('admin_notes');
        $this->verificationService->approve($verification, Auth::user(), $notes);

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
     * Suspend verification.
     */
    public function suspend(Request $request, IdentityVerification $verification)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->verificationService->suspend($verification, Auth::user(), $validated['reason']);

        return redirect()->route('admin.verifications.index')
            ->with('status', "Verification suspended and badge revoked for {$verification->user->name}.");
    }
}

