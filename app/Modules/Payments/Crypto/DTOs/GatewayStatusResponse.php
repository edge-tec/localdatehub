<?php

namespace App\Modules\Payments\Crypto\DTOs;

class GatewayStatusResponse
{
    public function __construct(
        public bool $success,
        public string $status, // CREATED, AWAITING_PAYMENT, CONFIRMING, COMPLETED, EXPIRED, FAILED, etc.
        public ?string $receivedAmount = null,
        public ?string $transactionHash = null,
        public int $confirmations = 0,
        public int $requiredConfirmations = 1,
        public ?string $errorMessage = null,
        public array $rawResponse = []
    ) {}

    public static function successful(
        string $status,
        ?string $receivedAmount = null,
        ?string $transactionHash = null,
        int $confirmations = 0,
        int $requiredConfirmations = 1,
        array $rawResponse = []
    ): self {
        return new self(
            success: true,
            status: $status,
            receivedAmount: $receivedAmount,
            transactionHash: $transactionHash,
            confirmations: $confirmations,
            requiredConfirmations: $requiredConfirmations,
            rawResponse: $rawResponse
        );
    }

    public static function failed(string $errorMessage, array $rawResponse = []): self
    {
        return new self(
            success: false,
            status: 'FAILED',
            errorMessage: $errorMessage,
            rawResponse: $rawResponse
        );
    }
}

