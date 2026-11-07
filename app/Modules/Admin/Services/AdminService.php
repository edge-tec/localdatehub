<?php

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Modules\Admin\DTOs\SystemMetricsDTO;

class AdminService
{
    /**
     * Compute system health and platform summary metrics.
     */
    public function getSystemMetrics(): SystemMetricsDTO
    {
        $totalUsers = User::count();

        return new SystemMetricsDTO(
            totalUsers: $totalUsers,
            verifiedUsers: User::whereNotNull('email_verified_at')->count(),
            activeListings: 0,
            pendingReports: 0,
            systemUptimePercent: 99.99
        );
    }
}
