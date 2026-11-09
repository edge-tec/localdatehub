<?php

namespace App\Modules\Payments\Crypto\DTOs;

class GatewayPaymentResponse
{
    public function __construct(
        public bool $success,
        public ?string $providerPaymentId = null,
        public ?string $providerInvoiceId = null,
        public ?string $paymentAddress = null,
        public ?string $paymentUrl = null,
        public ?string $qrCodeData = null,
        public ?string $expectedAmount = null,
        public ?string $paymentCurrency = null,
        public ?string $paymentNetwork = null,
        public ?\DateTimeInterface $expiresAt = null,
        public ?string $errorMessage = null,
        public array $rawResponse = []
    ) {}

    public static function successful(
        string $providerPaymentId,
        ?string $paymentAddress,
        ?string $paymentUrl = null,
        ?string $expectedAmount = null,
        ?string $qrCodeData = null,
        ?string $providerInvoiceId = null,
        ?\DateTimeInterface $expiresAt = null,
        array $rawResponse = []
    ): self {
        return new self(
            success: true,
            providerPaymentId: $providerPaymentId,
            providerInvoiceId: $providerInvoiceId,
            paymentAddress: $paymentAddress,
            paymentUrl: $paymentUrl,
            qrCodeData: $qrCodeData,
            expectedAmount: $expectedAmount,
            expiresAt: $expiresAt,
            rawResponse: $rawResponse
        );
    }

    public static function failed(string $errorMessage, array $rawResponse = []): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            rawResponse: $rawResponse
        );
    }
}

