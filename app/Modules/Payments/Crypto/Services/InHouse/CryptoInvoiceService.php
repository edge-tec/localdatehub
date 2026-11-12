<?php

namespace App\Modules\Payments\Crypto\Services\InHouse;

use App\Models\User;
use App\Modules\Payments\Crypto\Models\CryptoCurrency;
use App\Modules\Payments\Crypto\Models\CryptoNetwork;
use App\Modules\Payments\Crypto\Models\CryptoPaymentInvoice;
use App\Modules\Payments\Crypto\Support\CryptoCurrencyRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CryptoInvoiceService
{
    public function __construct(
        protected CryptoPricingService $pricingService,
        protected DepositAddressService $depositAddressService
    ) {}

    /**
     * Create a new cryptocurrency deposit invoice for a user.
     */
    public function createInvoice(
        User $user,
        float|string $usdAmount,
        string $currencySymbol,
        string $networkKey,
        ?int $creditPackageId = null,
        ?int $listingId = null,
        array $metadata = []
    ): CryptoPaymentInvoice {
        $currencySymbol = strtoupper($currencySymbol);
        $networkKey = strtoupper($networkKey);

        $currency = CryptoCurrency::where('symbol', $currencySymbol)
            ->where('is_active', true)
            ->firstOrFail();

        $networkAliases = match ($networkKey) {
            'BEP20', 'BNB', 'BSC' => ['BEP20', 'BNB', 'BSC'],
            'ERC20', 'ETH', 'ETHEREUM' => ['ERC20', 'ETH', 'ETHEREUM'],
            'TRC20', 'TRX', 'TRON' => ['TRC20', 'TRX', 'TRON'],
            'SOLANA', 'SOL' => ['SOLANA', 'SOL'],
            'BITCOIN', 'BTC' => ['BTC', 'BITCOIN'],
            'LITECOIN', 'LTC' => ['LTC', 'LITECOIN'],
            'DOGECOIN', 'DOGE' => ['DOGE', 'DOGECOIN'],
            'POLYGON', 'MATIC' => ['POLYGON', 'MATIC', 'ERC20'],
            default => [$networkKey],
        };

        $network = CryptoNetwork::where('currency_id', $currency->id)
            ->whereIn('network_key', $networkAliases)
            ->where('is_active', true)
            ->first()
            ?? CryptoNetwork::where('currency_id', $currency->id)
                ->where('is_active', true)
                ->firstOrFail();

        $usd = (float) $usdAmount;
        if ($usd < (float) $currency->min_deposit_usd || $usd > (float) $currency->max_deposit_usd) {
            throw new InvalidArgumentException(
                "Deposit amount [\${$usd}] is outside permitted range (\${$currency->min_deposit_usd} - \${$currency->max_deposit_usd})."
            );
        }

        return DB::transaction(function () use ($user, $currency, $network, $usd, $creditPackageId, $listingId, $metadata) {
            // Calculate crypto equivalent
            $conversion = $this->pricingService->convertUsdToCrypto($usd, $currency->symbol);

            // Allocate unique deposit address
            $depositAddress = $this->depositAddressService->allocateAddress($user, $currency, $network);

            $invoiceNumber = 'CRYPTO-INV-' . date('Ymd') . '-' . strtoupper(Str::random(8));
            $timeoutMinutes = $network->payment_timeout_minutes ?: 30;

            $invoice = CryptoPaymentInvoice::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $user->id,
                'deposit_address_id' => $depositAddress->id,
                'currency_symbol' => $currency->symbol,
                'network_key' => $network->network_key,
                'fiat_amount_usd' => $conversion['fiat_amount_usd'],
                'expected_crypto_amount' => $conversion['crypto_amount'],
                'received_crypto_amount' => 0.00000000,
                'exchange_rate_snapshot' => $conversion['rate'],
                'status' => CryptoPaymentInvoice::STATUS_WAITING_FOR_PAYMENT,
                'required_confirmations' => $network->required_confirmations,
                'current_confirmations' => 0,
                'contract_address' => $network->contract_address,
                'expires_at' => now()->addMinutes($timeoutMinutes),
                'credit_package_id' => $creditPackageId,
                'listing_id' => $listingId,
                'metadata' => array_merge($metadata, [
                    'qr_uri' => $depositAddress->public_address,
                    'chain_name' => $network->chain_name,
                    'gas_currency' => $network->gas_currency,
                ]),
            ]);

            $depositAddress->update(['assigned_invoice_id' => $invoice->id]);

            return $invoice;
        });
    }
}

