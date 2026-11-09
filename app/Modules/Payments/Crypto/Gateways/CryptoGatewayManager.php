<?php

namespace App\Modules\Payments\Crypto\Gateways;

use App\Modules\Payments\Crypto\Gateways\Adapters\BinancePayAdapter;
use App\Modules\Payments\Crypto\Gateways\Adapters\BitPayAdapter;
use App\Modules\Payments\Crypto\Gateways\Adapters\CoinGateAdapter;
use App\Modules\Payments\Crypto\Gateways\Adapters\CoinPaymentsAdapter;
use App\Modules\Payments\Crypto\Gateways\Adapters\ExodusCheckoutAdapter;
use App\Modules\Payments\Crypto\Gateways\Adapters\InHouseCryptoAdapter;
use App\Modules\Payments\Crypto\Gateways\Adapters\NowPaymentsAdapter;
use App\Modules\Payments\Crypto\Models\PaymentGateway;
use InvalidArgumentException;

class CryptoGatewayManager
{
    /**
     * @var array<string, CryptoPaymentGatewayInterface>
     */
    protected array $adapters = [];

    public function __construct()
    {
        $this->registerDefaultAdapters();
    }

    protected function registerDefaultAdapters(): void
    {
        $this->register(new InHouseCryptoAdapter());
        $this->register(new NowPaymentsAdapter());
        $this->register(new CoinPaymentsAdapter());
        $this->register(new CoinGateAdapter());
        $this->register(new BitPayAdapter());
        $this->register(new BinancePayAdapter());
        $this->register(new ExodusCheckoutAdapter());
    }

    public function register(CryptoPaymentGatewayInterface $adapter): void
    {
        $this->adapters[$adapter->getIdentifier()] = $adapter;
    }

    /**
     * Get adapter instance for a given identifier or PaymentGateway model.
     */
    public function get(string|PaymentGateway $gateway): CryptoPaymentGatewayInterface
    {
        $identifier = $gateway instanceof PaymentGateway ? $gateway->provider : $gateway;
        $normalized = str_replace('-', '_', strtolower($identifier));

        if (isset($this->adapters[$normalized])) {
            return $this->adapters[$normalized];
        }

        // Try direct key
        if (isset($this->adapters[strtolower($identifier)])) {
            return $this->adapters[strtolower($identifier)];
        }

        throw new InvalidArgumentException("Unsupported cryptocurrency payment provider: [{$identifier}].");
    }

    /**
     * Alias for get() to resolve an adapter.
     */
    public function resolve(string|PaymentGateway $gateway): CryptoPaymentGatewayInterface
    {
        return $this->get($gateway);
    }

    /**
     * Check if a provider adapter is registered.
     */
    public function has(string $identifier): bool
    {
        $normalized = str_replace('-', '_', strtolower($identifier));
        return isset($this->adapters[$normalized]) || isset($this->adapters[strtolower($identifier)]);
    }

    /**
     * Return all registered adapters.
     *
     * @return array<string, CryptoPaymentGatewayInterface>
     */
    public function all(): array
    {
        return $this->adapters;
    }
}

