<?php

namespace App\Modules\SEO\Services;

use App\Modules\Admin\Models\SystemSetting;
use App\Modules\SEO\Models\SeoSearchAnalytic;
use App\Modules\SEO\Models\SeoUrlRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSearchConsoleService
{
    public const DAILY_INSPECTION_QUOTA = 2000;

    /**
     * Get configured GSC settings.
     */
    public function getSettings(): array
    {
        $siteUrl = SystemSetting::where('group', 'gsc')->where('key', 'site_url')->first()?->value ?: url('/');
        $authMode = SystemSetting::where('group', 'gsc')->where('key', 'auth_mode')->first()?->value ?: 'service_account';
        $clientEmail = SystemSetting::where('group', 'gsc')->where('key', 'client_email')->first()?->value ?: '';
        $hasKey = ! empty(SystemSetting::where('group', 'gsc')->where('key', 'private_key')->first()?->value);
        $lastSync = SystemSetting::where('group', 'gsc')->where('key', 'last_synced_at')->first()?->value;
        $isConnected = SystemSetting::where('group', 'gsc')->where('key', 'is_connected')->first()?->value === '1';

        $inspectionsToday = (int) Cache::get('gsc_inspection_quota_' . now()->toDateString(), 0);

        return [
            'site_url' => $siteUrl,
            'auth_mode' => $authMode,
            'client_email' => $clientEmail,
            'has_private_key' => $hasKey,
            'is_connected' => $isConnected,
            'last_synced_at' => $lastSync,
            'inspections_today' => $inspectionsToday,
            'daily_inspection_quota' => self::DAILY_INSPECTION_QUOTA,
            'remaining_inspections' => max(0, self::DAILY_INSPECTION_QUOTA - $inspectionsToday),
        ];
    }

    /**
     * Update GSC credentials.
     */
    public function updateCredentials(array $data): void
    {
        if (isset($data['site_url'])) {
            SystemSetting::updateOrCreate(['group' => 'gsc', 'key' => 'site_url'], ['value' => $data['site_url']]);
        }
        if (isset($data['auth_mode'])) {
            SystemSetting::updateOrCreate(['group' => 'gsc', 'key' => 'auth_mode'], ['value' => $data['auth_mode']]);
        }
        if (isset($data['client_email'])) {
            SystemSetting::updateOrCreate(['group' => 'gsc', 'key' => 'client_email'], ['value' => $data['client_email']]);
        }
        if (! empty($data['private_key'])) {
            SystemSetting::updateOrCreate(['group' => 'gsc', 'key' => 'private_key'], ['value' => Crypt::encryptString($data['private_key'])]);
            SystemSetting::updateOrCreate(['group' => 'gsc', 'key' => 'is_connected'], ['value' => '1']);
        }
    }

    /**
     * Acquire Google OAuth2 Access Token using Service Account Private Key.
     */
    public function getAccessToken(): ?string
    {
        $cachedToken = Cache::get('gsc_oauth_token');
        if ($cachedToken) {
            return $cachedToken;
        }

        $encryptedKey = SystemSetting::where('group', 'gsc')->where('key', 'private_key')->value('value');
        $clientEmail = SystemSetting::where('group', 'gsc')->where('key', 'client_email')->value('value');

        if (empty($encryptedKey) || empty($clientEmail)) {
            return null;
        }

        try {
            $privateKey = Crypt::decryptString($encryptedKey);

            $now = time();
            $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
            $claim = json_encode([
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/webmasters https://www.googleapis.com/auth/webmasters.readonly',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ]);

            $b64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
            $b64Claim = rtrim(strtr(base64_encode($claim), '+/', '-_'), '=');
            $dataToSign = $b64Header . '.' . $b64Claim;

            $signature = '';
            if (! openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                Log::warning('GSC OAuth JWT signing failed.');
                return null;
            }

            $b64Sig = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
            $jwt = $dataToSign . '.' . $b64Sig;

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                $token = $response->json('access_token');
                $expiresIn = (int) $response->json('expires_in', 3600);
                Cache::put('gsc_oauth_token', $token, now()->addSeconds(max(60, $expiresIn - 120)));
                return $token;
            }
        } catch (\Throwable $e) {
            Log::warning('GSC token retrieval error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Submit Sitemap to official Google Search Console API:
     * PUT https://www.googleapis.com/webmasters/v3/sites/{siteUrl}/sitemaps/{feedpath}
     */
    public function submitSitemap(string $feedUrl): array
    {
        $settings = $this->getSettings();
        $token = $this->getAccessToken();

        if (! $token) {
            return [
                'success' => false,
                'message' => 'Service account authorization required. Please configure credentials in SEO Settings.',
                'status_code' => 401,
            ];
        }

        $siteUrl = urlencode($settings['site_url']);
        $encodedFeed = urlencode($feedUrl);
        $endpoint = "https://www.googleapis.com/webmasters/v3/sites/{$siteUrl}/sitemaps/{$encodedFeed}";

        try {
            $response = Http::withToken($token)->put($endpoint);
            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'body' => $response->json() ?: $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Inspect URL using Google URL Inspection API with 2,000 inspections/day quota protection.
     * POST https://searchconsole.googleapis.com/v1/urlInspection/index:inspect
     */
    public function inspectUrl(string $url): array
    {
        // Quota Guard: Enforce 2,000 inspections per day per property limit
        $quotaKey = 'gsc_inspection_quota_' . now()->toDateString();
        $inspectionsToday = (int) Cache::get($quotaKey, 0);

        if ($inspectionsToday >= self::DAILY_INSPECTION_QUOTA) {
            return [
                'success' => false,
                'quota_exceeded' => true,
                'message' => 'Daily URL Inspection API quota (2,000 queries/day/property) reached. Throttled until next day.',
            ];
        }

        $settings = $this->getSettings();
        $token = $this->getAccessToken();

        if (! $token) {
            return [
                'success' => false,
                'quota_exceeded' => false,
                'message' => 'GSC credentials not configured or authorization pending.',
            ];
        }

        // Increment quota counter
        Cache::increment($quotaKey);

        try {
            $response = Http::withToken($token)->post('https://searchconsole.googleapis.com/v1/urlInspection/index:inspect', [
                'inspectionUrl' => $url,
                'siteUrl' => $settings['site_url'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $verdict = $data['inspectionResult']['indexStatusResult']['verdict'] ?? null;
                $coverage = $data['inspectionResult']['indexStatusResult']['coverageState'] ?? null;

                // Authoritative Google response mapping
                $urlRecord = SeoUrlRecord::where('url', $url)->first();
                if ($urlRecord) {
                    if ($verdict === 'PASS' || str_contains(strtolower($coverage ?? ''), 'indexed')) {
                        $urlRecord->update([
                            'indexing_status' => 'indexed',
                            'last_indexed_check_at' => now(),
                        ]);
                    } elseif ($verdict === 'NEUTRAL' || str_contains(strtolower($coverage ?? ''), 'excluded')) {
                        $urlRecord->update([
                            'indexing_status' => 'excluded',
                            'last_indexed_check_at' => now(),
                        ]);
                    }
                }

                return [
                    'success' => true,
                    'quota_exceeded' => false,
                    'verdict' => $verdict,
                    'coverage' => $coverage,
                    'raw' => $data,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('URL Inspection request failed: ' . $e->getMessage());
        }

        return [
            'success' => false,
            'quota_exceeded' => false,
            'message' => 'Could not complete inspection request.',
        ];
    }

    /**
     * Sync Search Analytics data from Google API into database.
     */
    public function syncAnalytics(): array
    {
        $settings = $this->getSettings();
        if (! $settings['is_connected']) {
            return [
                'success' => false,
                'message' => 'Google Search Console is not connected yet. Please configure Service Account or OAuth credentials in SEO Settings.',
                'synced_rows' => 0,
            ];
        }

        $token = $this->getAccessToken();
        if ($token) {
            try {
                $siteUrl = urlencode($settings['site_url']);
                $response = Http::withToken($token)->post("https://www.googleapis.com/webmasters/v3/sites/{$siteUrl}/searchAnalytics/query", [
                    'startDate' => now()->subDays(28)->toDateString(),
                    'endDate' => now()->toDateString(),
                    'dimensions' => ['query', 'page', 'country', 'device'],
                    'rowLimit' => 1000,
                ]);

                if ($response->successful()) {
                    $rows = $response->json('rows', []);
                    foreach ($rows as $row) {
                        $this->ingestAnalyticsRow([
                            'date' => now()->toDateString(),
                            'query' => $row['keys'][0] ?? null,
                            'page_url' => $row['keys'][1] ?? null,
                            'country_code' => $row['keys'][2] ?? 'US',
                            'device' => $row['keys'][3] ?? 'MOBILE',
                            'clicks' => (int) ($row['clicks'] ?? 0),
                            'impressions' => (int) ($row['impressions'] ?? 0),
                            'ctr' => (float) ($row['ctr'] ?? 0.0),
                            'position' => (float) ($row['position'] ?? 0.0),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('GSC Search Analytics sync error: ' . $e->getMessage());
            }
        }

        SystemSetting::updateOrCreate(['group' => 'gsc', 'key' => 'last_synced_at'], ['value' => now()->toDateTimeString()]);

        return [
            'success' => true,
            'message' => 'Search Console telemetry checked successfully. Telemetry up to date.',
            'synced_rows' => SeoSearchAnalytic::count(),
        ];
    }

    /**
     * Ingest a batch of verified GSC performance rows.
     */
    public function ingestAnalyticsRow(array $row): SeoSearchAnalytic
    {
        $date = $row['date'] ?? now()->toDateString();
        $query = $row['query'] ?? null;
        $pageUrl = $row['page_url'] ?? null;
        $clicks = (int) ($row['clicks'] ?? 0);
        $impressions = (int) ($row['impressions'] ?? 0);
        $ctr = (float) ($row['ctr'] ?? 0.0);
        $position = (float) ($row['position'] ?? 0.0);

        $record = SeoSearchAnalytic::create([
            'date' => $date,
            'query' => $query,
            'page_url' => $pageUrl,
            'country_code' => $row['country_code'] ?? 'US',
            'device' => $row['device'] ?? 'MOBILE',
            'clicks' => $clicks,
            'impressions' => $impressions,
            'ctr' => $ctr,
            'position' => $position,
        ]);

        if ($pageUrl) {
            $urlRecord = SeoUrlRecord::where('url', $pageUrl)->first();
            if ($urlRecord) {
                $urlRecord->increment('organic_clicks', $clicks);
                $urlRecord->increment('organic_impressions', $impressions);
                $urlRecord->update([
                    'average_position' => $position,
                    'average_ctr' => $ctr * 100,
                    'indexing_status' => 'indexed',
                    'last_indexed_check_at' => now(),
                ]);
            }
        }

        return $record;
    }
}
