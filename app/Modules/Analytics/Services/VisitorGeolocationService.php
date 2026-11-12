<?php

namespace App\Modules\Analytics\Services;

use App\Modules\Analytics\Models\VisitorGeolocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VisitorGeolocationService
{
    public const COUNTRY_NAMES = [
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

    /**
     * Resolve approximate IP-based geolocation (cached in visitor_geolocation).
     *
     * @return array{
     *     country_code: string,
     *     country_name: string,
     *     region_name: ?string,
     *     city_name: ?string,
     *     latitude: ?float,
     *     longitude: ?float,
     *     isp: ?string,
     *     organization: ?string
     * }
     */
    public function resolve(Request $request, string $ipAddress): array
    {
        $headerCountry = strtoupper((string) (
            $request->header('CF-IPCountry')
            ?: $request->header('X-Country-Code')
            ?: $request->header('CloudFront-Viewer-Country')
            ?: ''
        ));
        if (strlen($headerCountry) !== 2 || $headerCountry === 'XX') {
            $headerCountry = '';
        }

        $headerCity = trim((string) ($request->header('CF-IPCity') ?: $request->header('X-City') ?: ''));
        $headerRegion = trim((string) ($request->header('CF-Region') ?: $request->header('X-Region') ?: ''));
        $headerLat = $request->header('CF-IPLatitude');
        $headerLon = $request->header('CF-IPLongitude');

        $cached = VisitorGeolocation::where('ip_address', $ipAddress)->first();
        if ($cached && (!$cached->cached_until || $cached->cached_until->isFuture())) {
            return [
                'country_code' => $headerCountry ?: $cached->country_code,
                'country_name' => $headerCountry ? (self::COUNTRY_NAMES[$headerCountry] ?? $cached->country_name) : $cached->country_name,
                'region_name' => $headerRegion ?: $cached->region_name,
                'city_name' => $headerCity ?: $cached->city_name,
                'latitude' => is_numeric($headerLat) ? (float) $headerLat : $cached->latitude,
                'longitude' => is_numeric($headerLon) ? (float) $headerLon : $cached->longitude,
                'isp' => $cached->isp,
                'organization' => $cached->organization,
            ];
        }

        $isPublicIp = filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;

        $geo = [
            'country_code' => $headerCountry ?: 'US',
            'country_name' => self::COUNTRY_NAMES[$headerCountry ?: 'US'] ?? 'United States',
            'region_name' => $headerRegion ?: null,
            'city_name' => $headerCity ?: null,
            'latitude' => is_numeric($headerLat) ? (float) $headerLat : null,
            'longitude' => is_numeric($headerLon) ? (float) $headerLon : null,
            'isp' => null,
            'organization' => null,
            'source' => $headerCountry ? 'headers' : 'local',
        ];

        if ($isPublicIp && !app()->runningUnitTests()) {
            try {
                $response = Http::timeout(1.2)->get("http://ip-api.com/json/{$ipAddress}", [
                    'fields' => 'status,country,countryCode,regionName,city,lat,lon,isp,org',
                ]);
                if ($response->ok() && $response->json('status') === 'success') {
                    $data = $response->json();
                    $code = strtoupper((string) ($data['countryCode'] ?? $geo['country_code']));
                    $geo['country_code'] = strlen($code) === 2 ? $code : $geo['country_code'];
                    $geo['country_name'] = $data['country'] ?: $geo['country_name'];
                    $geo['region_name'] = $headerRegion ?: ($data['regionName'] ?? null);
                    $geo['city_name'] = $headerCity ?: ($data['city'] ?? null);
                    $geo['latitude'] = is_numeric($headerLat) ? (float) $headerLat : (isset($data['lat']) ? (float) $data['lat'] : null);
                    $geo['longitude'] = is_numeric($headerLon) ? (float) $headerLon : (isset($data['lon']) ? (float) $data['lon'] : null);
                    $geo['isp'] = $data['isp'] ?? null;
                    $geo['organization'] = $data['org'] ?? null;
                    $geo['source'] = 'ip_api';
                }
            } catch (\Throwable $e) {
                // Gracefully fall back to headers/local when offline or rate-limited
            }
        }

        VisitorGeolocation::updateOrCreate(
            ['ip_address' => $ipAddress],
            [
                'country_code' => $geo['country_code'],
                'country_name' => $geo['country_name'],
                'region_name' => $geo['region_name'],
                'city_name' => $geo['city_name'],
                'latitude' => $geo['latitude'],
                'longitude' => $geo['longitude'],
                'isp' => $geo['isp'],
                'organization' => $geo['organization'],
                'source' => $geo['source'],
                'cached_until' => now()->addHours(24),
            ]
        );

        unset($geo['source']);

        return $geo;
    }
}

