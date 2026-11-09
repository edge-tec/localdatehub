<?php

namespace App\Modules\Payments\Crypto\DTOs;

class WebhookProcessingResult
{
    public function __construct(
        public bool $success,
        public ?string $internalOrderId = null,
        public ?string $providerPaymentId = null,
        public ?string $status = null, // CREATED, CONFIRMING, COMPLETED, EXPIRED, FAILED, UNDERPAID
        public ?string $receivedAmount = null,
        public ?string $transactionHash = null,
        public int $confirmations = 0,
        public ?string $errorMessage = null,
        public bool $isDuplicate = false,
        public array $rawPayload = []
    ) {}

    public static function successful(
        ?string $internalOrderId,
        ?string $providerPaymentId,
        string $status,
        ?string $receivedAmount = null,
        ?string $transactionHash = null,
        int $confirmations = 0,
        array $rawPayload = []
    ): self {
        return new self(
            success: true,
            internalOrderId: $internalOrderId,
            providerPaymentId: $providerPaymentId,
            status: $status,
            receivedAmount: $receivedAmount,
            transactionHash: $transactionHash,
            confirmations: $confirmations,
            rawPayload: $rawPayload
        );
    }

    public static function duplicate(string $orderId, array $rawPayload = []): self
    {
        return new self(
            success: true,
            internalOrderId: $orderId,
            isDuplicate: true,
            rawPayload: $rawPayload
        );
    }

    public static function invalid(string $errorMessage, array $rawPayload = []): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            rawPayload: $rawPayload
        );
    }
}

