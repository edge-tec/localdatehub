<?php

namespace App\Jobs\Wallet;

use App\Models\Wallet\BlockchainNetwork;
use App\Services\Treasury\GasManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckGasReserve implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(public int $networkId, public string $requiredFeeBaseUnits)
    {
        $this->onQueue((string) config('custody.queues.sweeping', 'sweeping'));
    }

    public function handle(GasManager $gasManager): void
    {
        $network = BlockchainNetwork::findOrFail($this->networkId);
        $gasManager->hasSufficientGasReserve($network, $this->requiredFeeBaseUnits);
    }
}

