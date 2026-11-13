<?php

namespace App\Jobs\Wallet;

use App\Services\Treasury\ReconciliationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReconcileTreasuryBalance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct()
    {
        $this->onQueue((string) config('custody.queues.recovery', 'recovery'));
    }

    public function handle(ReconciliationService $reconciliation): void
    {
        $result = $reconciliation->reconcile();
        if (!$result['healthy']) {
            Log::warning('Treasury reconciliation alerts detected', $result['alerts']);
        }
    }
}

