<?php

namespace App\Console\Commands;

use App\Modules\Payments\Crypto\Models\CryptoPaymentInvoice;
use App\Modules\Payments\Crypto\Services\InHouse\BlockchainDepositScannerService;
use App\Modules\Payments\Crypto\Services\InHouse\HybridTreasuryEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorInHouseCryptoInvoicesCommand extends Command
{
    protected $signature = 'crypto:monitor-inhouse {--loop : Run continuously as a background daemon} {--interval=15 : Polling interval in seconds}';
    protected $description = 'Monitors in-house cryptocurrency deposit addresses and verifies on-chain transactions';

    public function handle(
        BlockchainDepositScannerService $scannerService,
        HybridTreasuryEngine $treasuryEngine
    ): int {
        $loop = (bool) $this->option('loop');
        $interval = max(5, (int) $this->option('interval'));

        $this->info("Starting In-House Crypto Blockchain Monitoring Engine (" . ($loop ? "Continuous Daemon Mode" : "Single Run") . ")...");

        do {
            $this->scanInvoices($scannerService, $treasuryEngine);

            if ($loop) {
                sleep($interval);
            }
        } while ($loop);

        return Command::SUCCESS;
    }

    protected function scanInvoices(
        BlockchainDepositScannerService $scannerService,
        HybridTreasuryEngine $treasuryEngine
    ): void {
        $invoices = CryptoPaymentInvoice::with(['depositAddress.network', 'depositAddress.currency', 'creditPackage'])
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
            $updatedInvoice = $scannerService->scanAndVerify($invoice);

            if ($updatedInvoice->status === CryptoPaymentInvoice::STATUS_COMPLETED) {
                $this->info("Invoice #{$updatedInvoice->invoice_number} COMPLETED on-chain ({$updatedInvoice->received_crypto_amount} {$updatedInvoice->currency_symbol}). Credited user internal balance.");
                try {
                    $treasuryEngine->evaluateAutoSweep($updatedInvoice);
                } catch (\Throwable $e) {
                    Log::warning("Auto-sweep evaluation error: {$e->getMessage()}");
                }
            } elseif ($updatedInvoice->status === CryptoPaymentInvoice::STATUS_CONFIRMING) {
                $this->info("Invoice #{$updatedInvoice->invoice_number} CONFIRMING ({$updatedInvoice->current_confirmations}/{$updatedInvoice->required_confirmations} confirmations, received {$updatedInvoice->received_crypto_amount} {$updatedInvoice->currency_symbol}).");
            } elseif ($updatedInvoice->status === CryptoPaymentInvoice::STATUS_UNDERPAID) {
                $this->warn("Invoice #{$updatedInvoice->invoice_number} UNDERPAID (received {$updatedInvoice->received_crypto_amount} of {$updatedInvoice->expected_crypto_amount} {$updatedInvoice->currency_symbol}).");
            } elseif ($updatedInvoice->status === CryptoPaymentInvoice::STATUS_EXPIRED) {
                $this->warn("Invoice #{$updatedInvoice->invoice_number} expired.");
            } else {
                $this->line("Invoice #{$updatedInvoice->invoice_number}: {$updatedInvoice->status} (address: {$updatedInvoice->depositAddress?->public_address})");
            }
        }
    }
}

