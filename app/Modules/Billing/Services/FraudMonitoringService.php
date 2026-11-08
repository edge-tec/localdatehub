<?php

namespace App\Modules\Billing\Services;

use App\Models\User;
use App\Modules\Billing\Models\PaymentFraudLog;
use App\Modules\Billing\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;

class FraudMonitoringService
{
    /**
     * Assess transaction risk prior to charging.
     *
     * @return array{allowed: bool, risk_score: int, risk_tier: string, flags: array, message: ?string}
     */
    public function assessRisk(
        User $user,
        int $amountCents,
        string $ipAddress,
        ?string $paymentToken = null,
        ?int $transactionId = null
    ): array {
        $score = 0;
        $flags = [];

        // 1. IP Hourly Velocity Check
        $maxIpCharges = config('monetization.fraud.max_charges_per_ip_hour', 5);
        $recentIpTransactions = PaymentTransaction::where('created_at', '>=', now()->subHour())
            ->whereJsonContains('gateway_payload->ip_address', $ipAddress)
            ->count();

        if ($recentIpTransactions >= $maxIpCharges) {
            $score += 45;
            $flags[] = "ip_velocity_exceeded_{$recentIpTransactions}_in_hour";
        }

        // 2. User Decline Frequency Check
        $maxDeclines = config('monetization.fraud.max_declines_per_user_hour', 3);
        $recentDeclines = PaymentTransaction::where('user_id', $user->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentDeclines >= $maxDeclines) {
            $score += 50;
            $flags[] = "user_repeated_declines_{$recentDeclines}_in_hour";
        }

        // 3. Unverified User High-Value Charge Check
        if (! $user->is_age_verified && $amountCents > 5000) {
            $score += 25;
            $flags[] = 'unverified_age_high_value_attempt';
        }

        // 4. Repeated rapid charges on same user account
        $recentUserCharges = PaymentTransaction::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();
        if ($recentUserCharges >= 3) {
            $score += 30;
            $flags[] = 'user_burst_transactions_in_10min';
        }

        // Determine Tier
        $riskTier = 'low';
        $allowed = true;
        $actionTaken = 'allowed';
        $message = null;

        if ($score >= config('monetization.fraud.blocked_risk_threshold', 90)) {
            $riskTier = 'blocked';
            $allowed = false;
            $actionTaken = 'auto_declined';
            $message = 'Transaction blocked by automated risk and fraud security controls.';
        } elseif ($score >= config('monetization.fraud.high_risk_threshold', 70)) {
            $riskTier = 'high';
            $allowed = false;
            $actionTaken = 'flagged_for_review';
            $message = 'Transaction flagged for high risk. Please contact customer support or try again later.';
        } elseif ($score >= 35) {
            $riskTier = 'medium';
            $actionTaken = 'allowed';
        }

        // Log to payment_fraud_logs
        $cardFingerprint = $paymentToken ? hash('sha256', $paymentToken . config('app.key')) : null;

        PaymentFraudLog::create([
            'user_id' => $user->id,
            'payment_transaction_id' => $transactionId,
            'ip_address' => $ipAddress,
            'card_fingerprint_hash' => $cardFingerprint,
            'risk_score' => min(100, $score),
            'risk_tier' => $riskTier,
            'flags' => $flags,
            'action_taken' => $actionTaken,
            'notes' => $message,
        ]);

        if (! $allowed) {
            Log::warning("Payment fraud check blocked transaction: User {$user->id}, IP {$ipAddress}, Score {$score}, Flags: " . implode(', ', $flags));
        }

        return [
            'allowed' => $allowed,
            'risk_score' => min(100, $score),
            'risk_tier' => $riskTier,
            'flags' => $flags,
            'message' => $message,
        ];
    }
}