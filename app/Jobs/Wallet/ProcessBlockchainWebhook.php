<?php

namespace App\Jobs\Wallet;

use App\Models\Wallet\WalletAddress;
use App\Models\Wallet\WebhookEvent;
use App\Services\Blockchain\BlockchainMonitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBlockchainWebhook implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 45;

    public function __construct(public int $webhookEventId, public string $txHash, public string $recipientAddress)
    {
        $this->onQueue((string) config('custody.queues.verification', 'verification'));
    }

    public function uniqueId(): string
    {
        return "webhook-event-{$this->webhookEventId}";
    }

    public function handle(BlockchainMonitor $monitor): void
    {
        $event = WebhookEvent::with('network')->findOrFail($this->webhookEventId);
        $address = WalletAddress::with(['network', 'asset'])
            ->where('address', trim($this->recipientAddress))
            ->where('status', 'ACTIVE')
            ->first();

        if (!$address || !$address->network || !$address->asset) {
            $event->update(['verification_status' => 'IGNORED_UNKNOWN_ADDRESS', 'processed_at' => now()]);
            return;
        }

        // Independent blockchain verification — never trust webhook payload blindly
        $chain = $monitor->adapterFor($address->network)->verifyTransaction($address->network, $address->asset, $this->txHash);
        if (!$chain['exists'] || empty($chain['amount_base_units'])) {
            $event->update(['verification_status' => 'REJECTED_UNVERIFIED_ON_CHAIN', 'processed_at' => now()]);
            return;
        }

        $event->update(['verification_status' => 'VERIFIED_ON_CHAIN', 'processed_at' => now()]);

        ProcessIncomingTransaction::dispatch($address->id, [
            'tx_hash' => $this->txHash,
            'to_address' => $address->address,
            'from_address' => $chain['from_address'] ?? '',
            'contract_address' => $chain['contract_address'],
            'amount_base_units' => $chain['amount_base_units'],
            'confirmations' => $chain['confirmations'],
            'block_height' => $chain['block_height'],
            'block_hash' => $chain['block_hash'],
            'is_reverted' => $chain['is_reverted'],
        ])->onQueue((string) config('custody.queues.verification', 'verification'));
    }
}

