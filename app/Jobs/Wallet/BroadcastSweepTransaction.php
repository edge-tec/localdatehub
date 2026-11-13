<?php

namespace App\Jobs\Wallet;

use App\Models\Wallet\SweepTransaction;
use App\Services\Blockchain\BlockchainMonitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastSweepTransaction implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 45;

    public function __construct(public int $sweepId, public string $signedTx)
    {
        $this->onQueue((string) config('custody.queues.sweeping', 'sweeping'));
    }

    public function uniqueId(): string
    {
        return "broadcast-sweep-{$this->sweepId}";
    }

    public function handle(BlockchainMonitor $monitor): void
    {
        $sweep = SweepTransaction::with('network')->findOrFail($this->sweepId);
        if ($sweep->transaction_hash !== null) {
            return;
        }

        $txHash = $monitor->adapterFor($sweep->network)->broadcastSignedTransaction($sweep->network, $this->signedTx);
        $sweep->update([
            'transaction_hash' => $txHash,
            'status' => 'SWEEPING',
            'broadcast_at' => now(),
        ]);
    }
}

