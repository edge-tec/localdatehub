<?php

namespace App\Modules\Admin\DTOs;

readonly class SystemMetricsDTO
{
    public function __construct(
        public int $totalUsers,
        public int $verifiedUsers,
        public int $activeListings,
        public int $pendingReports,
        public float $systemUptimePercent,
    ) {}
}
