<?php

namespace App\Modules\Moderation\Services;

use App\Models\User;
use App\Modules\Admin\Services\AdminAuditService;
use App\Modules\Listings\Models\Listing;
use App\Modules\Moderation\Models\DmcaRequest;
use App\Modules\Moderation\Models\EvidencePreservation;
use App\Modules\Moderation\Models\LegalRequest;
use App\Modules\Moderation\Models\StateCompliance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ComplianceService
{
    /**
     * Check statutory state adult compliance requirements for a given state code.
     *
     * @return array{allowed: bool, requires_id_check: bool, min_age: int, warning: ?string, legal_ref: ?string}
     */
    public function checkStateCompliance(string $stateCode, ?User $user = null): array
    {
        $compliance = StateCompliance::forCode($stateCode);

        if (!$compliance) {
            return [
                'allowed' => true,
                'requires_id_check' => false,
                'min_age' => 18,
                'warning' => null,
                'legal_ref' => null,
            ];
        }

        $requiresId = $compliance->requires_id_verification;
        $isAllowed = true;

        if ($compliance->is_restricted) {
            $isAllowed = false;
        } elseif ($requiresId && (!$user || !$user->is_age_verified)) {
            $isAllowed = false;
        }

        return [
            'allowed' => $isAllowed,
            'requires_id_check' => $requiresId,
            'min_age' => $compliance->statutory_min_age,
            'warning' => $compliance->mandatory_warning_text,
            'legal_ref' => $compliance->legal_reference,
        ];
    }

    /**
     * Fast-removal / quarantine workflow for high-risk or prohibited content.
     */
    public function quarantineListing(Listing $listing, string $reason, ?int $adminId = null): void
    {
        $before = ['status' => $listing->status];

        $listing->status = Listing::STATUS_SUSPENDED;
        $listing->rejection_reason = "Quarantined for compliance review: {$reason}";
        $listing->save();

        $listing->recordStatusChange(
            Listing::STATUS_SUSPENDED,
            $listing->rejection_reason,
            $adminId
        );

        AdminAuditService::log('compliance.quarantine', $listing, $before, [
            'status' => Listing::STATUS_SUSPENDED,
            'reason' => $reason,
        ], $adminId);
    }

    /**
     * File a formal DMCA copyright or non-consensual imagery takedown request.
     */
    public function fileDmcaRequest(array $data): DmcaRequest
    {
        $ticket = 'DMCA-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        return DB::transaction(function () use ($data, $ticket) {
            $dmca = DmcaRequest::create([
                'ticket_number' => $ticket,
                'claimant_name' => $data['claimant_name'],
                'claimant_email' => $data['claimant_email'],
                'claimant_phone' => $data['claimant_phone'] ?? null,
                'organization' => $data['organization'] ?? null,
                'copyrighted_work' => $data['copyrighted_work'],
                'infringing_url' => $data['infringing_url'],
                'target_listing_id' => $data['target_listing_id'] ?? null,
                'good_faith_attested' => (bool) ($data['good_faith_attested'] ?? true),
                'perjury_attested' => (bool) ($data['perjury_attested'] ?? true),
                'digital_signature' => $data['digital_signature'],
                'status' => DmcaRequest::STATUS_PENDING,
            ]);

            // If a listing is directly identified, fast quarantine it pending review
            if (!empty($data['target_listing_id'])) {
                $listing = Listing::find($data['target_listing_id']);
                if ($listing && $listing->status === Listing::STATUS_ACTIVE) {
                    $this->quarantineListing($listing, "DMCA Notice #{$ticket} received.");
                }
            }

            AdminAuditService::log('dmca.filed', $dmca, null, [
                'ticket_number' => $ticket,
                'claimant_email' => $data['claimant_email'],
            ]);

            return $dmca;
        });
    }

    /**
     * Adjudicate a DMCA request.
     */
    public function actionDmca(
        DmcaRequest $request,
        string $action,
        ?string $notes = null,
        ?User $admin = null
    ): void {
        $before = ['status' => $request->status];

        $request->status = $action;
        $request->reviewed_by_admin_id = $admin?->id;
        $request->reviewed_at = now();
        $request->admin_notes = $notes;
        $request->save();

        if ($action === DmcaRequest::STATUS_TAKEDOWN_ISSUED && $request->targetListing) {
            $request->targetListing->status = Listing::STATUS_REJECTED;
            $request->targetListing->rejection_reason = 'Removed following verified DMCA takedown notice.';
            $request->targetListing->save();
        } elseif ($action === DmcaRequest::STATUS_RESTORED && $request->targetListing) {
            $request->targetListing->status = Listing::STATUS_ACTIVE;
            $request->targetListing->save();
        }

        AdminAuditService::log('dmca.action', $request, $before, [
            'status' => $action,
            'notes' => $notes,
        ], $admin?->id);
    }

    /**
     * Freeze and snapshot evidence for 18 U.S.C. 2703(f) legal hold or investigation.
     */
    public function freezeEvidence(
        Model $model,
        string $reason,
        int $adminId,
        ?int $legalRequestId = null,
        ?int $daysToHold = 90
    ): EvidencePreservation {
        $preservation = EvidencePreservation::create([
            'preservable_type' => get_class($model),
            'preservable_id' => $model->getKey(),
            'legal_request_id' => $legalRequestId,
            'hold_reason' => $reason,
            'snapshot_data' => $model->toArray(),
            'expires_at' => $daysToHold ? now()->addDays($daysToHold) : null,
            'is_active' => true,
            'frozen_by_admin_id' => $adminId,
        ]);

        AdminAuditService::log('evidence.freeze', $model, null, [
            'reason' => $reason,
            'preservation_id' => $preservation->id,
            'legal_request_id' => $legalRequestId,
        ], $adminId);

        return $preservation;
    }

    /**
     * Verify whether a user's account data may be lawfully purged/erased,
     * ensuring statutory legal holds or active preservation orders take precedence.
     *
     * @return array{can_erase: bool, reason: ?string}
     */
    public function canEraseUserData(User $user): array
    {
        // 1. Check if user model is under an active evidence hold
        if (EvidencePreservation::isUnderHold($user)) {
            return [
                'can_erase' => false,
                'reason' => 'Account is subject to an active 18 U.S.C. 2703(f) evidence preservation order.',
            ];
        }

        // 2. Check if any listings are under active legal hold
        $userListingIds = Listing::where('user_id', $user->id)->pluck('id');
        $hasListingHold = EvidencePreservation::where('preservable_type', Listing::class)
            ->whereIn('preservable_id', $userListingIds)
            ->active()
            ->exists();

        if ($hasListingHold) {
            return [
                'can_erase' => false,
                'reason' => 'One or more user listings are under an active statutory preservation hold.',
            ];
        }

        // 3. Check for active legal requests targeting user
        $hasActiveLegalRequest = LegalRequest::where('target_user_id', $user->id)
            ->active()
            ->exists();

        if ($hasActiveLegalRequest) {
            return [
                'can_erase' => false,
                'reason' => 'Account is named in an active pending law enforcement inquiry.',
            ];
        }

        return [
            'can_erase' => true,
            'reason' => null,
        ];
    }
}
