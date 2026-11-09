<?php

namespace App\Modules\Payments\Crypto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CryptoPaymentSetting extends Model
{
    use HasFactory;

    protected $table = 'crypto_payment_settings';

    protected $fillable = [
        'key',
        'value',
        'description',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        if ($setting->is_encrypted && !empty($setting->value)) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Exception) {
                return $setting->value;
            }
        }

        $decoded = json_decode($setting->value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $setting->value;
    }

    public static function set(string $key, mixed $value, ?string $description = null, bool $encrypt = false): static
    {
        $formattedValue = is_array($value) ? json_encode($value) : (string) $value;
        if ($encrypt && !empty($formattedValue)) {
            $formattedValue = Crypt::encryptString($formattedValue);
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $formattedValue,
                'description' => $description,
                'is_encrypted' => $encrypt,
            ]
        );
    }
}

