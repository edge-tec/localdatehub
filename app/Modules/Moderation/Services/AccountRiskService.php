<?php

namespace App\Modules\Moderation\Services;

use App\Models\User;
use App\Modules\Listings\Models\Listing;
use App\Modules\Moderation\Models\ContentReport;
use App\Modules\Moderation\Models\UserSuspension;

class AccountRiskService
{
    /**
     * Compute the risk score and risk level for a user account.
     *
     * @return array{score: int, risk_level: string, flags: array<string>}
     */
    public function evaluate(User $user, ?string $clientIp = null): array
    {
        $score = 0;
        $flags = [];

        // 1. Suspension history
        $suspensionCount = UserSuspension::where('user_id', $user->id)->count();
        if ($suspensionCount > 0) {
            $score += $suspensionCount * 30;
            $flags[] = "historical_suspensions_count: {$suspensionCount}";
        }

        // 2. High posting velocity
        $recent15m = Listing::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();
        if ($recent15m >= 3) {
            $score += 40;
            $flags[] = "rapid_posting_velocity: {$recent15m} listings in 15 minutes";
        }

        $dailyCount = Listing::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
        if ($dailyCount >= 10) {
            $score += 30;
            $flags[] = "high_daily_volume: {$dailyCount} listings in 24 hours";
        }

        // 3. User reports filed against this user's listings
        $reportsAgainstUser = ContentReport::where('reportable_type', Listing::class)
            ->whereIn('reportable_id', Listing::where('user_id', $user->id)->pluck('id'))
            ->whereIn('status', ['open', 'investigating', 'resolved'])
            ->count();
        if ($reportsAgainstUser > 0) {
            $score += $reportsAgainstUser * 20;
            $flags[] = "abuse_reports_received: {$reportsAgainstUser}";
        }

        // 4. Age verification check
        if (!$user->is_age_verified) {
            $score += 15;
            $flags[] = 'unverified_age_assurance';
        }

        // Determine risk level
        if ($score >= 70) {
            $risk = 'critical';
        } elseif ($score >= 45) {
            $risk = 'high';
        } elseif ($score >= 20) {
            $risk = 'medium';
        } else {
            $risk = 'low';
        }

        return [
            'score' => min(100, $score),
            'risk_level' => $risk,
            'flags' => $flags,
        ];
    }
}
