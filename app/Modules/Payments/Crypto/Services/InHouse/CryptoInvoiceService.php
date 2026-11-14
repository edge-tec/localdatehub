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

        [$currency, $network] = $this->resolveCurrencyAndNetwork($currencySymbol, $networkKey);

        $usd = (float) $usdAmount;
        $minAllowed = ($creditPackageId !== null || $listingId !== null)
            ? min(0.10, (float) $currency->min_deposit_usd)
            : (float) $currency->min_deposit_usd;

        if ($usd < $minAllowed || $usd > (float) $currency->max_deposit_usd) {
            throw new InvalidArgumentException(
                "Deposit amount [\${$usd}] is outside permitted range (\${$minAllowed} - \${$currency->max_deposit_usd})."
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

    /**
     * Resolve an active currency and network, auto-provisioning registry-supported defaults if missing.
     *
     * @return array{0: CryptoCurrency, 1: CryptoNetwork}
     */
    protected function resolveCurrencyAndNetwork(string $currencySymbol, string $networkKey): array
    {
        $registryCurrencies = CryptoCurrencyRegistry::getSupportedCurrencies();

        $currency = CryptoCurrency::where('symbol', $currencySymbol)
            ->where('is_active', true)
            ->first();

        if (!$currency && isset($registryCurrencies[$currencySymbol])) {
            $meta = $registryCurrencies[$currencySymbol];
            $currency = CryptoCurrency::updateOrCreate(
                ['symbol' => $currencySymbol],
                [
                    'name' => $meta['name'],
                    'is_active' => true,
                    'decimal_precision' => $meta['decimals'] ?? 8,
                    'min_deposit_usd' => 0.10,
                    'max_deposit_usd' => 50000.00,
                ]
            );
        }

        if (!$currency) {
            throw new InvalidArgumentException("Unsupported or inactive cryptocurrency: {$currencySymbol}.");
        }

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
            ->first();

        if (!$network && isset($registryCurrencies[$currencySymbol])) {
            $canonicalKey = match ($networkKey) {
                'TRON' => 'TRX',
                'BSC', 'BNB' => 'BEP20',
                'ETHEREUM' => 'ERC20',
                'BITCOIN' => 'BTC',
                'LITECOIN' => 'LTC',
                default => $networkKey,
            };

            $defaults = match ($canonicalKey) {
                'TRC20', 'TRX' => ['chain_name' => 'TRON Network', 'chain_id' => 728126428, 'gas_currency' => 'TRX', 'confirmations' => 1],
                'ERC20', 'ETH' => ['chain_name' => 'Ethereum Mainnet', 'chain_id' => 1, 'gas_currency' => 'ETH', 'confirmations' => 12],
                'BEP20', 'BNB' => ['chain_name' => 'BNB Smart Chain', 'chain_id' => 56, 'gas_currency' => 'BNB', 'confirmations' => 15],
                'POLYGON', 'MATIC' => ['chain_name' => 'Polygon PoS', 'chain_id' => 137, 'gas_currency' => 'MATIC', 'confirmations' => 15],
                'BTC' => ['chain_name' => 'Bitcoin Core', 'chain_id' => null, 'gas_currency' => 'BTC', 'confirmations' => 1],
                'LTC' => ['chain_name' => 'Litecoin Mainnet', 'chain_id' => null, 'gas_currency' => 'LTC', 'confirmations' => 2],
                default => ['chain_name' => $canonicalKey, 'chain_id' => null, 'gas_currency' => $currencySymbol, 'confirmations' => 1],
            };

            $network = CryptoNetwork::updateOrCreate(
                [
                    'currency_id' => $currency->id,
                    'network_key' => $canonicalKey,
                    'is_testnet' => false,
                ],
                [
                    'chain_name' => $defaults['chain_name'],
                    'chain_id' => $defaults['chain_id'],
                    'gas_currency' => $defaults['gas_currency'],
                    'required_confirmations' => $defaults['confirmations'],
                    'is_active' => true,
                    'payment_timeout_minutes' => 30,
                ]
            );
        }

        if (!$network) {
            $network = CryptoNetwork::where('currency_id', $currency->id)
                ->where('is_active', true)
                ->firstOrFail();
        }

        return [$currency, $network];
    }
}

