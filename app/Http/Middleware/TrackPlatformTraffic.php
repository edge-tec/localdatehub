<?php

namespace App\Http\Middleware;

use App\Models\PlatformTrafficLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackPlatformTraffic
{
    /**
     * Handle an incoming request and record real-time visitor telemetry.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $response = $next($request);
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        // Only track real GET page navigations (skip POST, AJAX, JSON, Livewire, prefetch)
        if ($request->method() !== 'GET' || $request->expectsJson() || $request->ajax() || $request->hasHeader('X-Livewire')) {
            return $response;
        }

        // Ignore browser prefetch / prerender requests
        $purpose = strtolower((string) ($request->header('Purpose') ?: $request->header('Sec-Purpose') ?: $request->header('X-moz')));
        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'preview') || str_contains($purpose, 'prerender')) {
            return $response;
        }

        $path = '/' . ltrim($request->path(), '/');

        // Ignore admin panel, assets, health checks, and internal calls
        if ($this->shouldSkipTracking($path)) {
            return $response;
        }

        // Skip if logged-in user is an admin/moderator browsing the site so admin reloads never inflate views
        $user = Auth::user();
        if ($user && ($user->is_admin || in_array($user->role, ['super_admin', 'admin', 'moderator', 'support', 'finance_admin'], true))) {
            return $response;
        }

        try {
            $this->recordTraffic($request, $path, $durationMs);
        } catch (\Throwable $e) {
            // Telemetry must never crash primary user requests
        }

        return $response;
    }

    protected function shouldSkipTracking(string $path): bool
    {
        $normalized = ltrim(strtolower($path), '/');

        // Never track admin dashboard or internal API/notification endpoints as visitor traffic
        $skipPrefixes = [
            'admin',
            'livewire',
            'api/',
            'notifications/',
            'storage/',
            'build/',
            'up',
            'health',
            'telescope',
            'horizon',
            '_debugbar',
        ];

        foreach ($skipPrefixes as $prefix) {
            if ($normalized === $prefix || str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        $extensions = ['.css', '.js', '.map', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf', '.webp', '.json'];
        foreach ($extensions as $ext) {
            if (str_ends_with($normalized, $ext)) {
                return true;
            }
        }

        return false;
    }

    protected function recordTraffic(Request $request, string $path, int $durationMs): void
    {
        $ip = $request->ip() ?: '127.0.0.1';
        $ua = substr($request->userAgent() ?: 'Mozilla/5.0 Direct/1.0', 0, 255);
        $salt = config('app.key', 'LocalAdZoneTrafficSalt2026');
        $hash = hash('sha256', $ip . '|' . $ua . '|' . $salt);

        // Resolve Country Code
        $countryCode = strtoupper(
            $request->header('CF-IPCountry')
            ?: $request->header('X-Country-Code')
            ?: $request->header('CloudFront-Viewer-Country')
            ?: $this->detectCountryFromAcceptLanguage($request)
        );

        if (empty($countryCode) || strlen($countryCode) !== 2) {
            $countryCode = 'US';
        }

        $countryNames = [
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'AU' => 'Australia',
            'DE' => 'Germany',
            'FR' => 'France',
            'IN' => 'India',
            'BD' => 'Bangladesh',
            'PK' => 'Pakistan',
            'BR' => 'Brazil',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'NL' => 'Netherlands',
            'JP' => 'Japan',
            'SG' => 'Singapore',
            'AE' => 'United Arab Emirates',
            'ZA' => 'South Africa',
            'MX' => 'Mexico',
            'PH' => 'Philippines',
        ];

        $countryName = $countryNames[$countryCode] ?? ($countryCode === 'US' ? 'United States' : "Country {$countryCode}");

        // Parse Device, Browser, and OS
        $deviceType = 'Desktop';
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            $deviceType = 'Tablet';
        } elseif (preg_match('/(mobi|ipod|iphone|android|opera mini|blackberry|iemobile)/i', $ua)) {
            $deviceType = 'Mobile';
        }

        $browser = 'Chrome';
        if (preg_match('/edg/i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/firefox|fxios/i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $ua) && ! preg_match('/chrome|crios/i', $ua)) {
            $browser = 'Safari';
        } elseif (preg_match('/opr|opera/i', $ua)) {
            $browser = 'Opera';
        }

        $os = 'Windows';
        if (preg_match('/windows/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/iphone|ipad|ipod/i', $ua)) {
            $os = 'iOS';
        } elseif (preg_match('/android/i', $ua)) {
            $os = 'Android';
        } elseif (preg_match('/linux/i', $ua)) {
            $os = 'Linux';
        }

        $isBot = (bool) preg_match('/(bot|crawl|spider|slurp|facebookexternalhit|bingbot|googlebot|headless|lighthouse|pingdom|uptimerobot)/i', $ua);
        if ($isBot) {
            return;
        }

        // Prevent URL reload inflation: if the same visitor_hash already loaded this exact path in the last 30 minutes, do not add a new view row
        $alreadyVisitedRecently = PlatformTrafficLog::where('visitor_hash', $hash)
            ->where('path', substr($path, 0, 255))
            ->where('created_at', '>=', now()->subMinutes(30))
            ->exists();

        if ($alreadyVisitedRecently) {
            return;
        }

        PlatformTrafficLog::create([
            'ip_address' => $ip,
            'visitor_hash' => $hash,
            'country_code' => $countryCode,
            'country_name' => $countryName,
            'city_name' => null,
            'state_code' => null,
            'path' => substr($path, 0, 255),
            'method' => $request->method(),
            'referrer' => substr($request->headers->get('referer', '') ?: '', 0, 255) ?: null,
            'user_agent' => $ua,
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
            'user_id' => Auth::id(),
            'session_id' => $request->hasSession() ? substr($request->session()->getId(), 0, 80) : null,
            'is_bot' => $isBot,
            'response_time_ms' => $durationMs,
            'created_at' => now(),
        ]);
    }

    protected function detectCountryFromAcceptLanguage(Request $request): string
    {
        $lang = $request->header('Accept-Language', '');
        if (empty($lang)) {
            return 'US';
        }

        if (preg_match('/[a-z]{2}-([A-Z]{2})/i', $lang, $matches)) {
            return strtoupper($matches[1]);
        }

        return 'US';
    }
}

