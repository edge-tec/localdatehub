<?php

namespace App\Modules\Moderation\DTOs;

readonly class ReportData
{
    public function __construct(
        public int $reporterId,
        public int $reportedUserId,
        public string $reason,
        public string $details,
        public ?int $listingId = null,
        public array $evidenceUrls = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            reporterId: (int) $data['reporter_id'],
            reportedUserId: (int) $data['reported_user_id'],
            reason: $data['reason'],
            details: trim($data['details']),
            listingId: isset($data['listing_id']) ? (int) $data['listing_id'] : null,
            evidenceUrls: $data['evidence_urls'] ?? [],
        );
    }
}
