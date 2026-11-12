<?php

namespace App\Services;

use App\Modules\Analytics\Services\VisitorGeolocationService;
use App\Modules\Locations\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class GeoLocationService
{
    public const SESSION_COUNTRY_KEY = 'marketplace_country_code';
    public const COOKIE_COUNTRY_KEY = 'ldh_country';
    public const CACHE_TTL_SECONDS = 86400; // 24 hours

    /**
     * Common search engine & social crawler User-Agent tokens.
     * Crawlers must never be force-redirected away from the canonical page they request.
     */
    private const CRAWLER_TOKENS = [
        'googlebot',
        'bingbot',
        'slurp',
        'duckduckbot',
        'baiduspider',
        'yandexbot',
        'facebot',
        'facebookexternalhit',
        'twitterbot',
        'linkedinbot',
        'applebot',
        'semrushbot',
        'ahrefsbot',
        'mj12bot',
        'petalbot',
        'crawler',
        'spider',
    ];

    public function __construct(
        private readonly VisitorGeolocationService $visitorGeoService
    ) {}

    /**
     * Return the 5 supported marketplace ISO-2 country codes.
     *
     * @return array<int, string>
     */
    public function supportedCountryCodes(): array
    {
        return Country::FIVE_MARKETPLACES;
    }

    /**
     * Return the 5 supported marketplace URL slugs.
     *
     * @return array<int, string>
     */
    public function supportedCountrySlugs(): array
    {
        return Country::FIVE_MARKETPLACE_SLUGS;
    }

    /**
     * Check whether an ISO-2 country code is one of the 5 supported marketplaces.
     */
    public function isSupportedCountry(?string $countryCode): bool
    {
        if (! is_string($countryCode) || trim($countryCode) === '') {
            return false;
        }

        return in_array(strtoupper(trim($countryCode)), Country::FIVE_MARKETPLACES, true);
    }

    /**
     * Detect whether the current request comes from a search engine crawler.
     */
    public function isSearchCrawler(Request $request): bool
    {
        $userAgent = strtolower((string) $request->userAgent());
        if ($userAgent === '') {
            return false;
        }

        foreach (self::CRAWLER_TOKENS as $token) {
            if (str_contains($userAgent, $token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Store the visitor's manual country selection in session and long-lived cookie (365 days).
     */
    public function setManualCountry(string $countryCode): string
    {
        $normalized = strtoupper(trim($countryCode));

        Session::put(self::SESSION_COUNTRY_KEY, $normalized);
        Cookie::queue(Cookie::make(self::COOKIE_COUNTRY_KEY, $normalized, 60 * 24 * 365, '/'));

        return $normalized;
    }

    /**
     * Retrieve a visitor's manually chosen country code from session or cookie if valid.
     */
    public function getManualCountry(?Request $request = null): ?string
    {
        $fromSession = Session::get(self::SESSION_COUNTRY_KEY);
        if (is_string($fromSession) && $this->isSupportedCountry($fromSession)) {
            return strtoupper(trim($fromSession));
        }

        $request = $request ?: request();
        $fromCookie = $request?->cookie(self::COOKIE_COUNTRY_KEY);
        if (is_string($fromCookie) && $this->isSupportedCountry($fromCookie)) {
            $normalized = strtoupper(trim($fromCookie));
            Session::put(self::SESSION_COUNTRY_KEY, $normalized);

            return $normalized;
        }

        return null;
    }

    /**
     * Extract client IP (IPv4 or IPv6) safely behind Cloudflare / reverse proxy headers.
     */
    public function resolveClientIp(Request $request): string
    {
        $candidates = [
            $request->header('CF-Connecting-IP'),
            $request->header('True-Client-IP'),
            $request->header('X-Real-IP'),
        ];

        $forwardedFor = $request->header('X-Forwarded-For');
        if (is_string($forwardedFor) && trim($forwardedFor) !== '') {
            $parts = array_map('trim', explode(',', $forwardedFor));
            if (! empty($parts[0])) {
                array_unshift($candidates, $parts[0]);
            }
        }

        $candidates[] = $request->ip();

        foreach ($candidates as $ip) {
            if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '127.0.0.1';
    }

    /**
     * Detect visitor geographic location from trusted proxy headers, MaxMind GeoLite2 .mmdb (if present),
     * or VisitorGeolocationService (with 24h cache + DB cache).
     *
     * @return array{
     *   ip_address: string,
     *   country_code: ?string,
     *   country_name: ?string,
     *   region_name: ?string,
     *   city_name: ?string,
     *   latitude: ?float,
     *   longitude: ?float,
     *   is_supported_marketplace: bool,
     *   marketplace_slug: ?string
     * }
     */
    public function detectLocation(Request $request): array
    {
        $ip = $this->resolveClientIp($request);

        // 1. Check explicit test/proxy override header or Cloudflare IPCountry header first
        $headerCountry = $request->header('X-Test-Country')
            ?: $request->header('CF-IPCountry')
            ?: $request->header('X-Country-Code');

        if (is_string($headerCountry)) {
            $headerCountry = strtoupper(trim($headerCountry));
            if (strlen($headerCountry) === 2 && ! in_array($headerCountry, ['XX', 'T1'], true)) {
                return $this->formatLocationResult([
                    'ip_address' => $ip,
                    'country_code' => $headerCountry,
                    'country_name' => $this->countryNameFromCode($headerCountry),
                    'region_name' => $request->header('X-Region-Name'),
                    'city_name' => $request->header('X-City-Name'),
                    'latitude' => null,
                    'longitude' => null,
                ]);
            }
        }

        // 2. Cache lookup per IP for 24 hours
        $cacheKey = 'ldh_geo_ip_' . md5($ip);
        $resolved = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($ip, $request): array {
            // Try local MaxMind GeoLite2 database if configured and reader class exists
            $mmdbResult = $this->lookupMaxMindDatabase($ip);
            if ($mmdbResult !== null) {
                return $mmdbResult;
            }

            // Fallback to existing VisitorGeolocationService (which also caches in visitor_geolocation table)
            return $this->visitorGeoService->resolve($ip, $request);
        });

        return $this->formatLocationResult($resolved);
    }

    /**
     * Determine which marketplace country applies to this request, respecting:
     * 1. Manual country selection (session/cookie)
     * 2. Search crawler non-redirect rule
     * 3. Automatic IP detection
     * 4. Unsupported country fallback to /choose-country
     *
     * @return array{
     *   action: 'marketplace'|'choose_country'|'crawler_passthrough',
     *   country_code: ?string,
     *   country_slug: ?string,
     *   redirect_url: string,
     *   source: 'manual'|'geoip'|'crawler'|'fallback',
     *   detected_country_code: ?string
     * }
     */
    public function resolveMarketplaceDecision(Request $request): array
    {
        // 1. Manual selection always takes priority over IP detection
        $manualCode = $this->getManualCountry($request);
        if ($manualCode !== null && $this->isMarketplaceActive($manualCode)) {
            $slug = strtolower($manualCode);

            return [
                'action' => 'marketplace',
                'country_code' => $manualCode,
                'country_slug' => $slug,
                'redirect_url' => url('/' . $slug),
                'source' => 'manual',
                'detected_country_code' => $manualCode,
            ];
        }

        // 2. Search engine crawlers are never force-redirected
        if ($this->isSearchCrawler($request)) {
            return [
                'action' => 'crawler_passthrough',
                'country_code' => null,
                'country_slug' => null,
                'redirect_url' => url('/'),
                'source' => 'crawler',
                'detected_country_code' => null,
            ];
        }

        // 3. Detect via IP
        $geo = $this->detectLocation($request);
        $detectedCode = $geo['country_code'] ? strtoupper($geo['country_code']) : null;

        if ($detectedCode !== null && $this->isSupportedCountry($detectedCode) && $this->isMarketplaceActive($detectedCode)) {
            $slug = strtolower($detectedCode);

            return [
                'action' => 'marketplace',
                'country_code' => $detectedCode,
                'country_slug' => $slug,
                'redirect_url' => url('/' . $slug),
                'source' => 'geoip',
                'detected_country_code' => $detectedCode,
            ];
        }

        // 4. Visitor is outside the 5 supported countries -> show /choose-country
        return [
            'action' => 'choose_country',
            'country_code' => null,
            'country_slug' => null,
            'redirect_url' => route('country.choose'),
            'source' => 'fallback',
            'detected_country_code' => $detectedCode,
        ];
    }

    /**
     * Check whether the given country code is active & marketplace-enabled in the database.
     */
    public function isMarketplaceActive(string $countryCode): bool
    {
        $code = strtoupper(trim($countryCode));
        if (! $this->isSupportedCountry($code)) {
            return false;
        }

        try {
            $country = Country::query()->where('code', $code)->first();
            if (! $country) {
                return true; // Allow before seeding if in FIVE_MARKETPLACES
            }

            return (bool) $country->is_active && (bool) ($country->is_marketplace_enabled ?? true);
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * Optional MaxMind GeoLite2 .mmdb lookup when database file and MaxMind reader exist.
     */
    private function lookupMaxMindDatabase(string $ip): ?array
    {
        $dbPath = config('services.maxmind.database_path', storage_path('app/geoip/GeoLite2-City.mmdb'));
        if (! is_string($dbPath) || ! is_file($dbPath)) {
            return null;
        }

        if (! class_exists(\GeoIp2\Database\Reader::class)) {
            return null;
        }

        try {
            $reader = new \GeoIp2\Database\Reader($dbPath);
            $record = $reader->city($ip);

            return [
                'ip_address' => $ip,
                'country_code' => $record->country->isoCode ? strtoupper($record->country->isoCode) : null,
                'country_name' => $record->country->name,
                'region_name' => $record->mostSpecificSubdivision->name,
                'city_name' => $record->city->name,
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatLocationResult(array $raw): array
    {
        $code = isset($raw['country_code']) && is_string($raw['country_code'])
            ? strtoupper(trim($raw['country_code']))
            : null;

        $supported = $this->isSupportedCountry($code);

        return [
            'ip_address' => (string) ($raw['ip_address'] ?? '127.0.0.1'),
            'country_code' => $code,
            'country_name' => $raw['country_name'] ?? ($code ? $this->countryNameFromCode($code) : null),
            'region_name' => $raw['region_name'] ?? null,
            'city_name' => $raw['city_name'] ?? null,
            'latitude' => isset($raw['latitude']) ? (float) $raw['latitude'] : null,
            'longitude' => isset($raw['longitude']) ? (float) $raw['longitude'] : null,
            'is_supported_marketplace' => $supported,
            'marketplace_slug' => $supported ? strtolower((string) $code) : null,
        ];
    }

    private function countryNameFromCode(string $code): string
    {
        return match (strtoupper($code)) {
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'IE' => 'Ireland',
            'NZ' => 'New Zealand',
            'US' => 'United States',
            default => strtoupper($code),
        };
    }
}
