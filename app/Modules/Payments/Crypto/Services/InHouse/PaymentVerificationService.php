<?php

namespace App\Modules\Payments\Crypto\Services\InHouse;

use App\Modules\Payments\Crypto\Models\CryptoPaymentInvoice;
use App\Modules\Payments\Crypto\Models\CryptoPaymentSetting;
use App\Modules\Payments\Crypto\Models\CryptoTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentVerificationService
{
    public function __construct(
        protected UserBalanceCreditService $balanceCreditService
    ) {}

    /**
     * Process an incoming detected blockchain transaction and verify against invoice.
     * Enforces the 10 mandatory blockchain verification criteria.
     */
    public function processDetectedTransaction(
        CryptoPaymentInvoice $invoice,
        string $txHash,
        string $fromAddress,
        string $toAddress,
        float|string $amount,
        int $confirmations,
        ?string $tokenContract = null,
        ?int $blockHeight = null
    ): array {
        return DB::transaction(function () use (
            $invoice, $txHash, $fromAddress, $toAddress, $amount, $confirmations, $tokenContract, $blockHeight
        ) {
            $depositAddress = $invoice->depositAddress;
            $requiredConfirmations = $invoice->required_confirmations;
            $receivedAmount = (string) $amount;
            $expectedAmount = (string) $invoice->expected_crypto_amount;

            $errors = [];

            // 1. Recipient address verification
            if (strtolower(trim($toAddress)) !== strtolower(trim($depositAddress->public_address))) {
                $errors[] = "Recipient address mismatch: expected [{$depositAddress->public_address}], got [{$toAddress}].";
            }

            // 2. Token contract verification (if token asset)
            if (!empty($invoice->contract_address)) {
                if (empty($tokenContract) || strtolower(trim($tokenContract)) !== strtolower(trim($invoice->contract_address))) {
                    $errors[] = "Token contract mismatch: expected [{$invoice->contract_address}], got [{$tokenContract}].";
                }
            }

            // 3. Duplicate transaction check
            $existingTx = CryptoTransaction::where('tx_hash', $txHash)->first();
            if ($existingTx && $existingTx->is_valid && $invoice->isCompleted()) {
                return [
                    'status' => 'ALREADY_CREDITED',
                    'invoice' => $invoice,
                    'transaction' => $existingTx,
                ];
            }

            // Record or update transaction
            $cryptoTx = CryptoTransaction::updateOrCreate(
                ['tx_hash' => $txHash],
                [
                    'invoice_id' => $invoice->id,
                    'deposit_address_id' => $depositAddress->id,
                    'block_height' => $blockHeight,
                    'from_address' => $fromAddress,
                    'to_address' => $toAddress,
                    'token_contract' => $tokenContract,
                    'amount' => $receivedAmount,
                    'confirmations' => $confirmations,
                    'is_valid' => empty($errors),
                    'validation_errors' => $errors,
                    'detected_at' => now(),
                    'confirmed_at' => $confirmations >= $requiredConfirmations ? now() : null,
                ]
            );

            if (!empty($errors)) {
                $invoice->update([
                    'status' => CryptoPaymentInvoice::STATUS_MANUAL_REVIEW,
                    'tx_hash' => $txHash,
                    'metadata' => array_merge($invoice->metadata ?? [], ['validation_errors' => $errors]),
                ]);

                return ['status' => 'FAILED_VALIDATION', 'errors' => $errors];
            }

            // Amount Comparison (Underpayment / Overpayment check with configurable tolerance for exchange withdrawal fees)
            $tolerancePct = (float) CryptoPaymentSetting::get('underpayment_threshold_percentage', 2.0);
            $toleranceFactor = bcsub('1', bcdiv((string) max(0, min(10, $tolerancePct)), '100', 6), 6);
            $minimumAcceptable = bcmul($expectedAmount, $toleranceFactor, 8);

            if (bccomp($receivedAmount, $minimumAcceptable, 8) < 0) {
                $amountStatus = CryptoPaymentInvoice::STATUS_UNDERPAID;
            } elseif (bccomp($receivedAmount, $expectedAmount, 8) > 0) {
                $amountStatus = CryptoPaymentInvoice::STATUS_OVERPAID;
            } else {
                $amountStatus = 'EXACT';
            }

            // Update Invoice confirmations and received amount
            $invoice->update([
                'received_crypto_amount' => $receivedAmount,
                'current_confirmations' => $confirmations,
                'tx_hash' => $txHash,
                'detected_at' => $invoice->detected_at ?: now(),
            ]);

            // If confirmations reached and not underpaid, complete and credit balance
            if ($confirmations >= $requiredConfirmations) {
                if ($amountStatus === CryptoPaymentInvoice::STATUS_UNDERPAID) {
                    $invoice->update([
                        'status' => CryptoPaymentInvoice::STATUS_UNDERPAID,
                        'confirmed_at' => now(),
                    ]);
                    return ['status' => 'UNDERPAID', 'invoice' => $invoice];
                }

                $invoice->update([
                    'status' => CryptoPaymentInvoice::STATUS_COMPLETED,
                    'confirmed_at' => now(),
                ]);

                // Atomically credit user balance & financial ledger
                $this->balanceCreditService->creditUserForInvoice($invoice);

                return ['status' => 'COMPLETED', 'invoice' => $invoice];
            }

            // Otherwise confirming
            $invoice->update(['status' => CryptoPaymentInvoice::STATUS_CONFIRMING]);
            return ['status' => 'CONFIRMING', 'invoice' => $invoice];
        });
    }
}

