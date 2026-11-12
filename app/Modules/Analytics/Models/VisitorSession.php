<?php

namespace App\Modules\Analytics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorSession extends Model
{
    protected $table = 'visitor_sessions';

    public const STATUS_ONLINE = 'online';
    public const STATUS_IDLE = 'idle';
    public const STATUS_OFFLINE = 'offline';

    protected $fillable = [
        'visitor_id',
        'session_token',
        'user_id',
        'ip_address',
        'country_code',
        'country_name',
        'region_name',
        'city_name',
        'latitude',
        'longitude',
        'isp',
        'organization',
        'device_type',
        'device_brand',
        'device_model',
        'os_name',
        'os_version',
        'browser_name',
        'browser_version',
        'screen_resolution',
        'landing_page',
        'current_page_url',
        'current_page_title',
        'referrer_url',
        'traffic_source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'pageviews_count',
        'is_returning',
        'status',
        'first_seen_at',
        'last_activity_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'pageviews_count' => 'integer',
            'is_returning' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pageviews(): HasMany
    {
        return $this->hasMany(VisitorPageview::class, 'visitor_session_id')->orderByDesc('viewed_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(VisitorActivity::class, 'visitor_session_id')->orderByDesc('occurred_at');
    }

    public function getSessionDurationSecondsAttribute(): int
    {
        if (!$this->first_seen_at) {
            return 0;
        }
        $end = $this->ended_at ?: ($this->last_activity_at ?: now());

        return max(0, (int) $this->first_seen_at->diffInSeconds($end));
    }

    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->session_duration_seconds;
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $minutes = intdiv($seconds, 60);
        $remSeconds = $seconds % 60;
        if ($minutes < 60) {
            return "{$minutes}m {$remSeconds}s";
        }
        $hours = intdiv($minutes, 60);
        $remMinutes = $minutes % 60;

        return "{$hours}h {$remMinutes}m";
    }
}

