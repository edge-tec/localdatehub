<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformTrafficLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'ip_address',
        'visitor_hash',
        'event_key',
        'country_code',
        'country_name',
        'city_name',
        'state_code',
        'path',
        'method',
        'referrer',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'user_id',
        'session_id',
        'is_bot',
        'response_time_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'response_time_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to get country flag emoji
     */
    public function getCountryFlag(): string
    {
        return self::countryCodeToFlag($this->country_code);
    }

    public static function countryCodeToFlag(string $code): string
    {
        $code = strtoupper(trim($code));
        if (strlen($code) !== 2) {
            return '🌐';
        }

        $flags = [
            'US' => '🇺🇸',
            'GB' => '🇬🇧',
            'CA' => '🇨🇦',
            'AU' => '🇦🇺',
            'DE' => '🇩🇪',
            'FR' => '🇫🇷',
            'IN' => '🇮🇳',
            'BD' => '🇧🇩',
            'PK' => '🇵🇰',
            'BR' => '🇧🇷',
            'IT' => '🇮🇹',
            'ES' => '🇪🇸',
            'NL' => '🇳🇱',
            'JP' => '🇯🇵',
            'SG' => '🇸🇬',
            'AE' => '🇦🇪',
            'ZA' => '🇿🇦',
            'MX' => '🇲🇽',
            'PH' => '🇵🇭',
        ];

        return $flags[$code] ?? '🌐';
    }
}

