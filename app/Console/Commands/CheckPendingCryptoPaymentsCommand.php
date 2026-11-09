<?php

namespace App\Console\Commands;

use App\Modules\Payments\Crypto\Models\CryptoPayment;
use App\Modules\Payments\Crypto\Services\CryptoPaymentService;
use Illuminate\Console\Command;

class CheckPendingCryptoPaymentsCommand extends Command
{
    protected $signature = 'crypto:check-pending';
    protected $description = 'Poll provider APIs to check status of awaiting and confirming cryptocurrency payments';

    public function handle(CryptoPaymentService $service): int
    {
        $pendingPayments = CryptoPayment::whereIn('status', [
            CryptoPayment::STATUS_AWAITING_PAYMENT,
            CryptoPayment::STATUS_PENDING,
            CryptoPayment::STATUS_CONFIRMING,
        ])->get();

        $this->info("Found {$pendingPayments->count()} pending crypto payment(s) to check.");

        $updated = 0;
        foreach ($pendingPayments as $payment) {
            $prevStatus = $payment->status;
            $synced = $service->syncPaymentStatus($payment);
            if ($synced->status !== $prevStatus) {
                $updated++;
                $this->line("Payment #{$payment->id} ({$payment->internal_order_id}): {$prevStatus} -> {$synced->status}");
            }
        }

        $this->info("Completed status checks. Updated {$updated} payment(s).");
        return Command::SUCCESS;
    }
}

