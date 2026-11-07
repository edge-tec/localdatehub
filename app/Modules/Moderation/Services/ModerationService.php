<?php

namespace App\Modules\Moderation\Services;

use App\Models\User;
use App\Modules\Admin\Services\AdminAuditService;
use App\Modules\Listings\Models\Listing;
use App\Modules\Moderation\DTOs\ReportData;
use App\Modules\Moderation\Models\ContentReport;
use App\Modules\Moderation\Models\ModerationAction;
use App\Modules\Moderation\Models\ModerationQueue;
use App\Modules\Moderation\Models\UserSuspension;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModerationService
{
    public const VALID_REASONS = [
        'commercial_solicitation',
        'harassment',
        'underage_suspected',
        'fake_impersonation',
        'non_consensual_media',
        'scam_fraud',
        'hate_speech',
        'trafficking_coercion',
        'other',
    ];

    public function __construct(
        protected TextScreeningService $textScreening,
        protected DuplicateDetectionService $duplicateDetection,
        protected ComplianceService $compliance
    ) {}

    /**
     * Submit and record a safety report with automated severity scoring.
     */
    public function submitReport(ReportData $data): array
    {
        if (!in_array($data->reason, self::VALID_REASONS, true)) {
            throw new \InvalidArgumentException("Invalid report reason: {$data->reason}");
        }

        // Determine risk level based on report reason code
        $isUrgent = in_array($data->reason, [
            'underage_suspected',
            'non_consensual_media',
            'trafficking_coercion',
        ], true);

        $riskLevel = match ($data->reason) {
            'underage_suspected', 'trafficking_coercion', 'non_consensual_media' => 'critical',
            'commercial_solicitation', 'scam_fraud' => 'high',
            'harassment', 'fake_impersonation' => 'medium',
            default => 'low',
        };

        // Determine target reportable
        $reportableType = $data->listingId ? Listing::class : User::class;
        $reportableId = $data->listingId ?: $data->reportedUserId;

        $report = ContentReport::create([
            'reporter_id' => $data->reporterId,
            'reportable_type' => $reportableType,
            'reportable_id' => $reportableId,
            'reason_code' => $data->reason,
            'risk_level' => $riskLevel,
            'is_urgent_takedown' => $isUrgent,
            'details' => $data->details,
            'evidence_urls' => $data->evidenceUrls,
            'status' => 'open',
            'reporter_ip' => request()->ip(),
        ]);

        // Urgent non-consensual media / underage report triggers immediate quarantine
        if ($isUrgent && $data->listingId) {
            $listing = Listing::find($data->listingId);
            if ($listing && $listing->status === Listing::STATUS_ACTIVE) {
                $this->compliance->quarantineListing(
                    $listing,
                    "Urgent takedown report (#{$report->id}) for reason: {$data->reason}"
                );
            }
        }

        // Structured safety log
        Log::channel('single')->warning('Safety report filed', [
            'report_id' => $report->id,
            'reporter_id' => $data->reporterId,
            'reported_user_id' => $data->reportedUserId,
            'reason' => $data->reason,
            'risk_level' => $riskLevel,
            'is_urgent' => $isUrgent,
            'listing_id' => $data->listingId,
            'timestamp' => now()->toIso8601String(),
        ]);

        return [
            'status' => 'queued_for_review',
            'ticket_id' => 'REP-' . strtoupper(substr(md5((string) $report->id . uniqid()), 0, 8)),
            'report_id' => $report->id,
            'risk_level' => $riskLevel,
            'submitted_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Screen and queue listing for human review with automated risk scoring.
     */
    public function screenAndQueueListing(Listing $listing): ModerationQueue
    {
        $screening = $this->textScreening->screen($listing->title . "\n" . $listing->description);
        $duplicate = $this->duplicateDetection->check($listing->title, $listing->description, $listing->id);

        $flags = $screening['flags'];
        $urgencyScore = $screening['score'];

        if ($duplicate['is_duplicate']) {
            $flags[] = "duplicate_content_match: Listing #{$duplicate['match_listing_id']} ({$duplicate['similarity']}%)";
            $urgencyScore = max($urgencyScore, 75);
        }

        $riskLevel = $screening['risk_level'];
        if ($duplicate['is_duplicate'] && $riskLevel === 'low') {
            $riskLevel = 'medium';
        }

        // Create or update queue entry
        $queueItem = ModerationQueue::updateOrCreate(
            [
                'moderatable_type' => Listing::class,
                'moderatable_id' => $listing->id,
            ],
            [
                'status' => 'queued',
                'risk_level' => $riskLevel,
                'urgency_score' => $urgencyScore,
                'trigger_reason' => !empty($flags) ? implode('; ', array_slice($flags, 0, 3)) : 'Standard review',
                'automated_flags' => [
                    'screening' => $screening,
                    'duplicate' => $duplicate,
                ],
            ]
        );

        // Immediate enforcement for critical severity
        if ($screening['action'] === 'auto_reject') {
            $listing->status = Listing::STATUS_REJECTED;
            $listing->rejection_reason = 'Rejected automatically due to prohibited solicitation content.';
            $listing->save();
        } elseif ($screening['action'] === 'auto_suspend') {
            $listing->status = Listing::STATUS_SUSPENDED;
            $listing->rejection_reason = 'Suspended automatically due to critical safety or exploitation indicators.';
            $listing->save();
        }

        return $queueItem;
    }

    /**
     * Record a formal disciplinary moderation action on an entity.
     */
    public function takeAction(
        Model $target,
        string $actionTaken,
        string $reason,
        int $adminId,
        ?string $notes = null,
        ?int $queueId = null
    ): ModerationAction {
        return DB::transaction(function () use ($target, $actionTaken, $reason, $adminId, $notes, $queueId) {
            $action = ModerationAction::create([
                'moderation_queue_id' => $queueId,
                'admin_user_id' => $adminId,
                'action_taken' => $actionTaken,
                'reason' => $reason,
                'internal_notes' => $notes,
                'created_at' => now(),
            ]);

            if ($target instanceof Listing) {
                if ($actionTaken === 'approved') {
                    $target->status = Listing::STATUS_ACTIVE;
                    $target->published_at = $target->published_at ?: now();
                    $target->expires_at = $target->expires_at ?: now()->addDays(30);
                    $target->save();
                } elseif ($actionTaken === 'rejected') {
                    $target->status = Listing::STATUS_REJECTED;
                    $target->rejection_reason = $reason;
                    $target->save();
                } elseif ($actionTaken === 'suspended') {
                    $target->status = Listing::STATUS_SUSPENDED;
                    $target->rejection_reason = $reason;
                    $target->save();
                }
            } elseif ($target instanceof User && in_array($actionTaken, ['user_suspended', 'user_banned'])) {
                $target->account_status = 'suspended';
                $target->save();

                UserSuspension::create([
                    'user_id' => $target->id,
                    'issued_by_admin_id' => $adminId,
                    'reason' => $reason,
                    'notes' => $notes,
                    'suspension_type' => $actionTaken === 'user_banned' ? 'permanent' : 'temporary',
                    'starts_at' => now(),
                    'ends_at' => $actionTaken === 'user_banned' ? null : now()->addDays(30),
                ]);
            }

            if ($queueId) {
                ModerationQueue::whereKey($queueId)->update([
                    'status' => in_array($actionTaken, ['approved']) ? 'approved' : 'rejected',
                    'reviewed_at' => now(),
                    'reviewer_notes' => $notes,
                ]);
            }

            AdminAuditService::log('moderation.action', $target, null, [
                'action_taken' => $actionTaken,
                'reason' => $reason,
                'notes' => $notes,
            ], $adminId);

            return $action;
        });
    }
}
