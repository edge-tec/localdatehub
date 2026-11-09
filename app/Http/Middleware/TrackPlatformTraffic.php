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

        // Only track GET/POST requests and skip internal / asset routes
        if (! in_array($request->method(), ['GET', 'POST'], true)) {
            return $response;
        }

        $path = '/' . ltrim($request->path(), '/');

        // Ignore assets, health checks, and livewire internal calls
        if ($this->shouldSkipTracking($path)) {
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
        $skipPatterns = [
            'livewire/livewire',
            'livewire/update',
            'up',
            'health',
            'telescope',
            'horizon',
            '_debugbar',
            'api/v1/ping',
        ];

        foreach ($skipPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        $extensions = ['.css', '.js', '.map', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf'];
        foreach ($extensions as $ext) {
            if (str_ends_with(strtolower($path), $ext)) {
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

        $isBot = (bool) preg_match('/(bot|crawl|spider|slurp|facebookexternalhit|bingbot|googlebot)/i', $ua);

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

