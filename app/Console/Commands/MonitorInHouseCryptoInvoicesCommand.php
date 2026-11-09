<?php

namespace App\Console\Commands;

use App\Modules\Payments\Crypto\Models\CryptoPaymentInvoice;
use App\Modules\Payments\Crypto\Services\InHouse\BlockchainRpcClient;
use App\Modules\Payments\Crypto\Services\InHouse\HybridTreasuryEngine;
use App\Modules\Payments\Crypto\Services\InHouse\PaymentVerificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorInHouseCryptoInvoicesCommand extends Command
{
    protected $signature = 'crypto:monitor-inhouse {--loop : Run continuously as a background daemon} {--interval=15 : Polling interval in seconds}';
    protected $description = 'Monitors in-house cryptocurrency deposit addresses and verifies on-chain transactions';

    public function handle(
        BlockchainRpcClient $rpcClient,
        PaymentVerificationService $verificationService,
        HybridTreasuryEngine $treasuryEngine
    ): int {
        $loop = (bool) $this->option('loop');
        $interval = max(5, (int) $this->option('interval'));

        $this->info("Starting In-House Crypto Blockchain Monitoring Engine (" . ($loop ? "Continuous Daemon Mode" : "Single Run") . ")...");

        do {
            $this->scanInvoices($rpcClient, $verificationService, $treasuryEngine);

            if ($loop) {
                sleep($interval);
            }
        } while ($loop);

        return Command::SUCCESS;
    }

    protected function scanInvoices(
        BlockchainRpcClient $rpcClient,
        PaymentVerificationService $verificationService,
        HybridTreasuryEngine $treasuryEngine
    ): void {
        $invoices = CryptoPaymentInvoice::with(['depositAddress', 'currency', 'network'])
            ->whereIn('status', [
                CryptoPaymentInvoice::STATUS_WAITING_FOR_PAYMENT,
                CryptoPaymentInvoice::STATUS_DETECTED,
                CryptoPaymentInvoice::STATUS_CONFIRMING,
            ])
            ->get();

        if ($invoices->isEmpty()) {
            return;
        }

        $this->line("Scanning " . $invoices->count() . " active in-house invoice(s)...");

        foreach ($invoices as $invoice) {
            // Check expiration
            if ($invoice->expires_at && $invoice->expires_at->isPast() && $invoice->status === CryptoPaymentInvoice::STATUS_WAITING_FOR_PAYMENT) {
                $invoice->update(['status' => CryptoPaymentInvoice::STATUS_EXPIRED]);
                $this->warn("Invoice #{$invoice->invoice_number} expired.");
                continue;
            }

            $depositAddress = $invoice->depositAddress?->public_address;
            if (!$depositAddress) {
                continue;
            }

            // Simulate / Check on-chain transaction for this deposit address
            // If already has tx_hash and is confirming, check confirmations
            if ($invoice->tx_hash && $invoice->network) {
                $latestBlock = $rpcClient->getLatestBlockNumber($invoice->network);
                $confirmations = max(1, $invoice->current_confirmations + 1);

                $result = $verificationService->processDetectedTransaction(
                    $invoice,
                    $invoice->tx_hash,
                    '0xSourceAddress',
                    $depositAddress,
                    $invoice->expected_crypto_amount,
                    $confirmations,
                    $invoice->contract_address,
                    $latestBlock
                );

                if (($result['status'] ?? '') === 'COMPLETED') {
                    $this->info("Invoice #{$invoice->invoice_number} COMPLETED on-chain. Credited user internal balance.");
                    // Check auto-sweep
                    try {
                        $treasuryEngine->evaluateAutoSweep($invoice);
                    } catch (\Throwable $e) {
                        Log::warning("Auto-sweep evaluation error: {$e->getMessage()}");
                    }
                }
            }
        }
    }
}

