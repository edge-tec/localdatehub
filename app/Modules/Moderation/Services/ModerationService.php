<?php

namespace App\Modules\Moderation\Services;

use App\Modules\Moderation\DTOs\ReportData;
use Illuminate\Support\Facades\Log;

class ModerationService
{
    /**
     * Valid user reporting reasons.
     */
    public const VALID_REASONS = [
        'commercial_solicitation',
        'harassment',
        'underage_suspected',
        'fake_impersonation',
        'non_consensual_media',
        'scam_fraud',
        'hate_speech',
        'other',
    ];

    /**
     * Log and process user safety report.
     */
    public function submitReport(ReportData $data): array
    {
        if (!in_array($data->reason, self::VALID_REASONS, true)) {
            throw new \InvalidArgumentException("Invalid report reason: {$data->reason}");
        }

        // Structured safety log
        Log::channel('single')->warning('Safety report filed', [
            'reporter_id' => $data->reporterId,
            'reported_user_id' => $data->reportedUserId,
            'reason' => $data->reason,
            'listing_id' => $data->listingId,
            'timestamp' => now()->toIso8601String(),
        ]);

        return [
            'status' => 'queued_for_review',
            'ticket_id' => 'REP-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'submitted_at' => now()->toIso8601String(),
        ];
    }
}
