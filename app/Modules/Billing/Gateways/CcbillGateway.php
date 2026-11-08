<?php

namespace App\Modules\Billing\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CcbillGateway implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config ?: config('monetization.gateways.ccbill', []);
    }

    public function getIdentifier(): string
    {
        return 'ccbill';
    }

    public function getName(): string
    {
        return 'CCBill High-Risk Gateway';
    }

    public function createCharge(array $params): ChargeResult
    {
        if (! config('monetization.adult_merchant_approved')) {
            return new ChargeResult(
                success: false,
                errorMessage: 'CCBill live processing disabled: Adult merchant underwriting approval not yet verified.'
            );
        }

        $amountCents = (int) ($params['amount_cents'] ?? 0);
        $txnId = 'ccb_' . Str::random(18);

        return new ChargeResult(
            success: true,
            transactionId: $txnId,
            referenceId: $params['reference_id'] ?? null,
            amountCents: $amountCents,
            currency: $params['currency'] ?? 'USD',
            cardLastFour: $params['card_last_four'] ?? '1234',
            cardBrand: 'Mastercard',
            rawPayload: ['ccbill_transaction_id' => $txnId]
        );
    }

    public function createSubscription(array $params): SubscriptionResult
    {
        if (! config('monetization.adult_merchant_approved')) {
            return new SubscriptionResult(
                success: false,
                errorMessage: 'CCBill recurring subscriptions disabled: Adult merchant underwriting approval not yet verified.'
            );
        }

        $subId = 'ccb_sub_' . Str::random(18);

        return new SubscriptionResult(
            success: true,
            subscriptionId: $subId,
            status: 'active',
            rawPayload: ['subscription_id' => $subId]
        );
    }

    public function cancelSubscription(string $gatewaySubscriptionId): bool
    {
        return true;
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $salt = $this->config['salt_key'] ?? '';
        if (empty($salt)) {
            return false;
        }

        // CCBill Datalink signature verification: md5(transactionId + isApproved + salt)
        $txnId = $request->input('transactionId', '');
        $approved = $request->input('isApproved', '1');
        $providedSig = $request->input('responseDigest', '');

        $computed = md5($txnId . $approved . $salt);

        return hash_equals($computed, $providedSig);
    }

    public function parseWebhook(Request $request): WebhookResult
    {
        $txnId = $request->input('transactionId', '');
        $approved = $request->input('isApproved') === '1' || $request->input('isApproved') === 1;
        $amount = (int) (floatval($request->input('billedAmount', 0)) * 100);
        $reference = $request->input('customField1') ?: $request->input('reference_id');

        return new WebhookResult(
            valid: true,
            eventId: $txnId,
            eventType: $approved ? 'charge.succeeded' : 'charge.failed',
            transactionReference: $reference,
            gatewayTransactionId: $txnId,
            amountCents: $amount,
            data: $request->all()
        );
    }

    public function refund(string $gatewayTransactionId, int $amountCents, string $reason): RefundResult
    {
        $refundId = 'ccb_ref_' . Str::random(16);

        return new RefundResult(
            success: true,
            refundId: $refundId,
            amountCents: $amountCents,
            rawPayload: ['refund_id' => $refundId]
        );
    }
}
