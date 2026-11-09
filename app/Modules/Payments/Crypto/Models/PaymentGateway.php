<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $table = 'payment_gateways';

    protected $fillable = [
        'name',
        'slug',
        'provider',
        'is_enabled',
        'environment',
        'encrypted_credentials',
        'webhook_secret_encrypted',
        'supported_currencies',
        'supported_networks',
        'configuration',
        'display_order',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'supported_currencies' => 'array',
        'supported_networks' => 'array',
        'configuration' => 'array',
        'display_order' => 'integer',
        'last_tested_at' => 'datetime',
    ];

    /**
     * Get decrypted credentials array.
     */
    public function getCredentials(): array
    {
        if (empty($this->encrypted_credentials)) {
            return [];
        }

        try {
            $decrypted = Crypt::decryptString($this->encrypted_credentials);
            return json_decode($decrypted, true) ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Set credentials securely.
     */
    public function setCredentials(array $credentials): void
    {
        $this->encrypted_credentials = Crypt::encryptString(json_encode($credentials));
    }

    /**
     * Get decrypted webhook secret.
     */
    public function getWebhookSecret(): ?string
    {
        if (empty($this->webhook_secret_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->webhook_secret_encrypted);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Set webhook secret securely.
     */
    public function setWebhookSecret(?string $secret): void
    {
        $this->webhook_secret_encrypted = !empty($secret) ? Crypt::encryptString($secret) : null;
    }

    /**
     * Associated crypto payments.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CryptoPayment::class, 'gateway_id');
    }

    /**
     * Associated webhook events.
     */
    public function webhookEvents(): HasMany
    {
        return $this->hasMany(PaymentWebhookEvent::class, 'gateway_id');
    }

    /**
     * Scope for enabled gateways.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true)->orderBy('display_order');
    }
}

