<?php

namespace App\Modules\Analytics\Services;

use App\Modules\Analytics\Models\VisitorActivity;
use App\Modules\Analytics\Models\VisitorPageview;
use App\Modules\Analytics\Models\VisitorSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LiveVisitorTrackingService
{
    public const VISITOR_COOKIE = 'ldh_vid';

    public function __construct(
        protected VisitorGeolocationService $geolocation
    ) {}

    /**
     * Record a page visit without duplicating sessions on page refreshes or navigation.
     *
     * @return array{session: VisitorSession, visitor_cookie_value: string, is_new_cookie: bool}
     */
    public function recordPageVisit(Request $request, string $path, int $durationMs = 0, ?string $pageTitle = null): array
    {
        $ip = $this->resolveClientIp($request);
        $ua = substr((string) ($request->userAgent() ?: 'Mozilla/5.0'), 0, 255);

        $existingCookie = trim((string) $request->cookie(self::VISITOR_COOKIE, ''));
        $isNewCookie = $existingCookie === '';
        $visitorId = $existingCookie !== '' ? substr($existingCookie, 0, 64) : ('v_' . Str::random(24));

        $laravelSessionId = $request->hasSession() ? $request->session()->getId() : null;
        $sessionToken = $laravelSessionId
            ? substr('s_' . hash('sha256', $laravelSessionId), 0, 64)
            : substr('s_' . hash('sha256', $visitorId . '|' . $ip . '|' . $ua), 0, 64);

        $now = now();
        $fullUrl = substr($request->fullUrl(), 0, 500);
        $referrer = $request->headers->get('referer') ? substr((string) $request->headers->get('referer'), 0, 500) : null;
        $device = $this->parseUserAgent($ua);
        $utm = $this->extractUtm($request);
        $trafficSource = $this->classifyTrafficSource($referrer, $utm, $request->getHost());

        $session = VisitorSession::where('session_token', $sessionToken)
            ->where('status', '!=', VisitorSession::STATUS_OFFLINE)
            ->first();

        if (!$session && !$isNewCookie) {
            $session = VisitorSession::where('visitor_id', $visitorId)
                ->where('status', '!=', VisitorSession::STATUS_OFFLINE)
                ->where('last_activity_at', '>=', $now->copy()->subMinutes(30))
                ->latest('last_activity_at')
                ->first();
        }

        $isNewSession = !$session;

        if ($isNewSession) {
            $geo = $this->geolocation->resolve($request, $ip);
            $isReturning = !$isNewCookie || VisitorSession::where('visitor_id', $visitorId)->exists();

            // Reuse existing token if an old offline session had the same Laravel session token
            if (VisitorSession::where('session_token', $sessionToken)->exists()) {
                $sessionToken = 's_' . Str::random(32);
            }

            $session = VisitorSession::create([
                'visitor_id' => $visitorId,
                'session_token' => $sessionToken,
                'user_id' => Auth::id(),
                'ip_address' => $ip,
                'country_code' => $geo['country_code'],
                'country_name' => $geo['country_name'],
                'region_name' => $geo['region_name'],
                'city_name' => $geo['city_name'],
                'latitude' => $geo['latitude'],
                'longitude' => $geo['longitude'],
                'isp' => $geo['isp'],
                'organization' => $geo['organization'],
                'device_type' => $device['device_type'],
                'device_brand' => $device['device_brand'],
                'device_model' => $device['device_model'],
                'os_name' => $device['os_name'],
                'os_version' => $device['os_version'],
                'browser_name' => $device['browser_name'],
                'browser_version' => $device['browser_version'],
                'landing_page' => $fullUrl,
                'current_page_url' => $fullUrl,
                'current_page_title' => $pageTitle ?: $this->inferTitleFromPath($path),
                'referrer_url' => $referrer,
                'traffic_source' => $trafficSource,
                'utm_source' => $utm['utm_source'],
                'utm_medium' => $utm['utm_medium'],
                'utm_campaign' => $utm['utm_campaign'],
                'utm_term' => $utm['utm_term'],
                'utm_content' => $utm['utm_content'],
                'pageviews_count' => 1,
                'is_returning' => $isReturning,
                'status' => VisitorSession::STATUS_ONLINE,
                'first_seen_at' => $now,
                'last_activity_at' => $now,
            ]);

            VisitorActivity::create([
                'visitor_session_id' => $session->id,
                'event_type' => 'entered',
                'page_url' => $fullUrl,
                'page_title' => $session->current_page_title,
                'details' => "Entered via {$trafficSource} ({$device['device_type']} · {$device['browser_name']})",
                'occurred_at' => $now,
                'created_at' => $now,
            ]);
        } else {
            $previousUrl = $session->current_page_url;
            $eventType = $previousUrl === $fullUrl ? 'pageview' : 'navigated';

            $session->update([
                'user_id' => Auth::id() ?: $session->user_id,
                'current_page_url' => $fullUrl,
                'current_page_title' => $pageTitle ?: $this->inferTitleFromPath($path),
                'pageviews_count' => $session->pageviews_count + 1,
                'status' => VisitorSession::STATUS_ONLINE,
                'last_activity_at' => $now,
                'ended_at' => null,
            ]);

            VisitorActivity::create([
                'visitor_session_id' => $session->id,
                'event_type' => $eventType,
                'page_url' => $fullUrl,
                'page_title' => $session->current_page_title,
                'details' => $eventType === 'navigated' ? "Navigated to {$path}" : "Viewed {$path}",
                'occurred_at' => $now,
                'created_at' => $now,
            ]);
        }

        VisitorPageview::create([
            'visitor_session_id' => $session->id,
            'url' => $fullUrl,
            'path' => substr($path, 0, 255),
            'page_title' => $session->current_page_title,
            'referrer_url' => $referrer,
            'response_time_ms' => max(0, $durationMs),
            'viewed_at' => $now,
            'created_at' => $now,
        ]);

        return [
            'session' => $session,
            'visitor_cookie_value' => $visitorId,
            'is_new_cookie' => $isNewCookie,
        ];
    }

    /**
     * Update live session state from browser heartbeat or page departure beacon.
     *
     * @param array<string, mixed> $payload
     */
    public function recordHeartbeat(Request $request, array $payload): ?VisitorSession
    {
        $visitorId = trim((string) $request->cookie(self::VISITOR_COOKIE, ''));
        $laravelSessionId = $request->hasSession() ? $request->session()->getId() : null;
        $sessionToken = $laravelSessionId ? substr('s_' . hash('sha256', $laravelSessionId), 0, 64) : null;

        $session = null;
        if ($sessionToken) {
            $session = VisitorSession::where('session_token', $sessionToken)
                ->where('status', '!=', VisitorSession::STATUS_OFFLINE)
                ->first();
        }
        if (!$session && $visitorId !== '') {
            $session = VisitorSession::where('visitor_id', $visitorId)
                ->where('status', '!=', VisitorSession::STATUS_OFFLINE)
                ->latest('last_activity_at')
                ->first();
        }

        if (!$session) {
            return null;
        }

        $now = now();
        $state = strtolower((string) ($payload['state'] ?? 'active'));
        $screen = isset($payload['screen_resolution']) ? substr(trim((string) $payload['screen_resolution']), 0, 32) : null;
        $title = isset($payload['page_title']) ? substr(trim((string) $payload['page_title']), 0, 255) : null;
        $pageUrl = isset($payload['page_url']) ? substr(trim((string) $payload['page_url']), 0, 500) : null;

        $updates = [
            'last_activity_at' => $now,
        ];
        if ($screen && preg_match('/^\d{3,5}x\d{3,5}$/', $screen)) {
            $updates['screen_resolution'] = $screen;
        }
        if ($title) {
            $updates['current_page_title'] = $title;
        }
        if ($pageUrl) {
            $updates['current_page_url'] = $pageUrl;
        }

        $previousStatus = $session->status;

        if ($state === 'left') {
            $updates['status'] = VisitorSession::STATUS_OFFLINE;
            $updates['ended_at'] = $now;
            $session->update($updates);

            VisitorActivity::create([
                'visitor_session_id' => $session->id,
                'event_type' => 'left',
                'page_url' => $session->current_page_url,
                'page_title' => $session->current_page_title,
                'details' => 'Visitor left the page',
                'occurred_at' => $now,
                'created_at' => $now,
            ]);
        } elseif ($state === 'idle') {
            $updates['status'] = VisitorSession::STATUS_IDLE;
            $session->update($updates);

            if ($previousStatus !== VisitorSession::STATUS_IDLE) {
                VisitorActivity::create([
                    'visitor_session_id' => $session->id,
                    'event_type' => 'idle',
                    'page_url' => $session->current_page_url,
                    'page_title' => $session->current_page_title,
                    'details' => 'Visitor became idle',
                    'occurred_at' => $now,
                    'created_at' => $now,
                ]);
            }
        } else {
            $updates['status'] = VisitorSession::STATUS_ONLINE;
            $updates['ended_at'] = null;
            $session->update($updates);
        }

        return $session;
    }

    /**
     * Transition inactive sessions to idle/offline automatically.
     */
    public function expireInactiveSessions(int $idleAfterSeconds = 120, int $offlineAfterSeconds = 300): int
    {
        $now = now();
        $idleCutoff = $now->copy()->subSeconds($idleAfterSeconds);
        $offlineCutoff = $now->copy()->subSeconds($offlineAfterSeconds);
        $changed = 0;

        $toIdle = VisitorSession::where('status', VisitorSession::STATUS_ONLINE)
            ->where('last_activity_at', '<', $idleCutoff)
            ->where('last_activity_at', '>=', $offlineCutoff)
            ->get();

        foreach ($toIdle as $session) {
            $session->update(['status' => VisitorSession::STATUS_IDLE]);
            VisitorActivity::create([
                'visitor_session_id' => $session->id,
                'event_type' => 'idle',
                'page_url' => $session->current_page_url,
                'page_title' => $session->current_page_title,
                'details' => 'Marked idle due to inactivity',
                'occurred_at' => $now,
                'created_at' => $now,
            ]);
            $changed++;
        }

        $toOffline = VisitorSession::whereIn('status', [VisitorSession::STATUS_ONLINE, VisitorSession::STATUS_IDLE])
            ->where('last_activity_at', '<', $offlineCutoff)
            ->get();

        foreach ($toOffline as $session) {
            $session->update([
                'status' => VisitorSession::STATUS_OFFLINE,
                'ended_at' => $session->last_activity_at ?: $now,
            ]);
            VisitorActivity::create([
                'visitor_session_id' => $session->id,
                'event_type' => 'expired',
                'page_url' => $session->current_page_url,
                'page_title' => $session->current_page_title,
                'details' => 'Session expired due to inactivity',
                'occurred_at' => $now,
                'created_at' => $now,
            ]);
            $changed++;
        }

        return $changed;
    }

    /**
     * Count currently online/active visitors after expiring stale sessions.
     */
    public function getOnlineCount(int $offlineAfterSeconds = 300): int
    {
        $this->expireInactiveSessions(120, $offlineAfterSeconds);

        return VisitorSession::whereIn('status', [VisitorSession::STATUS_ONLINE, VisitorSession::STATUS_IDLE])
            ->where('last_activity_at', '>=', now()->subSeconds($offlineAfterSeconds))
            ->count();
    }

    /**
     * Privacy data retention purge.
     */
    public function purgeOldRecords(int $retentionDays = 30): int
    {
        return VisitorSession::where('last_activity_at', '<', now()->subDays($retentionDays))->delete();
    }

    protected function resolveClientIp(Request $request): string
    {
        $cfIp = trim((string) $request->header('CF-Connecting-IP', ''));
        if ($cfIp !== '' && filter_var($cfIp, FILTER_VALIDATE_IP)) {
            return $cfIp;
        }

        return $request->ip() ?: '127.0.0.1';
    }

    /**
     * @return array{device_type: string, device_brand: ?string, device_model: ?string, os_name: string, os_version: ?string, browser_name: string, browser_version: ?string}
     */
    public function parseUserAgent(string $ua): array
    {
        $deviceType = 'Desktop';
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            $deviceType = 'Tablet';
        } elseif (preg_match('/(mobi|ipod|iphone|android|opera mini|blackberry|iemobile)/i', $ua)) {
            $deviceType = 'Mobile';
        }

        $brand = null;
        $model = null;
        if (preg_match('/iPhone/i', $ua)) {
            $brand = 'Apple';
            $model = 'iPhone';
        } elseif (preg_match('/iPad/i', $ua)) {
            $brand = 'Apple';
            $model = 'iPad';
        } elseif (preg_match('/Macintosh/i', $ua)) {
            $brand = 'Apple';
            $model = 'Mac';
        } elseif (preg_match('/Samsung|SM-([A-Z0-9]+)/i', $ua, $m)) {
            $brand = 'Samsung';
            $model = $m[1] ?? 'Galaxy';
        } elseif (preg_match('/Pixel\s?([A-Za-z0-9]+)/i', $ua, $m)) {
            $brand = 'Google';
            $model = 'Pixel ' . $m[1];
        } elseif (preg_match('/(Xiaomi|Redmi|POCO)\s?([A-Za-z0-9]+)/i', $ua, $m)) {
            $brand = 'Xiaomi';
            $model = trim($m[1] . ' ' . $m[2]);
        }

        $osName = 'Unknown';
        $osVersion = null;
        if (preg_match('/Windows NT ([0-9.]+)/i', $ua, $m)) {
            $osName = 'Windows';
            $osVersion = $m[1] === '10.0' ? '10/11' : $m[1];
        } elseif (preg_match('/Mac OS X ([0-9_]+)/i', $ua, $m)) {
            $osName = 'macOS';
            $osVersion = str_replace('_', '.', $m[1]);
        } elseif (preg_match('/(?:iPhone|iPad).*OS ([0-9_]+)/i', $ua, $m)) {
            $osName = 'iOS';
            $osVersion = str_replace('_', '.', $m[1]);
        } elseif (preg_match('/Android ([0-9.]+)/i', $ua, $m)) {
            $osName = 'Android';
            $osVersion = $m[1];
        } elseif (preg_match('/Linux/i', $ua)) {
            $osName = 'Linux';
        }

        $browserName = 'Unknown';
        $browserVersion = null;
        if (preg_match('/Edg(?:A|iOS)?\/([0-9.]+)/i', $ua, $m)) {
            $browserName = 'Edge';
            $browserVersion = $m[1];
        } elseif (preg_match('/OPR\/([0-9.]+)/i', $ua, $m)) {
            $browserName = 'Opera';
            $browserVersion = $m[1];
        } elseif (preg_match('/(?:Firefox|FxiOS)\/([0-9.]+)/i', $ua, $m)) {
            $browserName = 'Firefox';
            $browserVersion = $m[1];
        } elseif (preg_match('/(?:Chrome|CriOS)\/([0-9.]+)/i', $ua, $m)) {
            $browserName = 'Chrome';
            $browserVersion = $m[1];
        } elseif (preg_match('/Version\/([0-9.]+).*Safari/i', $ua, $m)) {
            $browserName = 'Safari';
            $browserVersion = $m[1];
        } elseif (preg_match('/Safari/i', $ua)) {
            $browserName = 'Safari';
        }

        return [
            'device_type' => $deviceType,
            'device_brand' => $brand,
            'device_model' => $model,
            'os_name' => $osName,
            'os_version' => $osVersion,
            'browser_name' => $browserName,
            'browser_version' => $browserVersion,
        ];
    }

    protected function extractUtm(Request $request): array
    {
        return [
            'utm_source' => $request->query('utm_source') ? substr((string) $request->query('utm_source'), 0, 100) : null,
            'utm_medium' => $request->query('utm_medium') ? substr((string) $request->query('utm_medium'), 0, 100) : null,
            'utm_campaign' => $request->query('utm_campaign') ? substr((string) $request->query('utm_campaign'), 0, 120) : null,
            'utm_term' => $request->query('utm_term') ? substr((string) $request->query('utm_term'), 0, 120) : null,
            'utm_content' => $request->query('utm_content') ? substr((string) $request->query('utm_content'), 0, 120) : null,
        ];
    }

    protected function classifyTrafficSource(?string $referrer, array $utm, string $host): string
    {
        if (!empty($utm['utm_source'])) {
            return 'Campaign / ' . ucfirst($utm['utm_source']);
        }
        if (!$referrer) {
            return 'Direct';
        }

        $refHost = strtolower((string) parse_url($referrer, PHP_URL_HOST));
        if ($refHost === '' || str_contains($refHost, strtolower($host))) {
            return 'Direct';
        }
        if (preg_match('/(google|bing|yahoo|duckduckgo|baidu|yandex)/i', $refHost)) {
            return 'Organic Search';
        }
        if (preg_match('/(facebook|instagram|twitter|x\.com|t\.co|tiktok|reddit|linkedin|pinterest)/i', $refHost)) {
            return 'Social';
        }

        return 'Referral (' . $refHost . ')';
    }

    protected function inferTitleFromPath(string $path): string
    {
        $trimmed = trim($path, '/');
        if ($trimmed === '') {
            return 'Home Page';
        }

        return Str::title(str_replace(['-', '_', '/'], ' ', $trimmed));
    }
}

