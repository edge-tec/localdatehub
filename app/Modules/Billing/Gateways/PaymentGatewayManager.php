<?php

namespace App\Modules\Billing\Gateways;

use InvalidArgumentException;

class PaymentGatewayManager
{
    protected array $resolved = [];

    public function driver(?string $name = null): PaymentGatewayInterface
    {
        $name = $name ?: config('monetization.default', 'sandbox');

        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        $config = config("monetization.gateways.{$name}");
        if (! $config || ! isset($config['driver'])) {
            throw new InvalidArgumentException("Payment gateway driver [{$name}] is not configured.");
        }

        $driverClass = $config['driver'];
        return $this->resolved[$name] = new $driverClass($config);
    }

    /**
     * Set a custom or mock driver for testing.
     */
    public function setDriver(string $name, PaymentGatewayInterface $gateway): void
    {
        $this->resolved[$name] = $gateway;
    }
}
