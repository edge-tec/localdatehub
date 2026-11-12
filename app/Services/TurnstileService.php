<?php

namespace App\Services;

use App\Modules\Admin\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileService
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function siteKey(): string
    {
        $dbKey = trim((string) (SystemSetting::get('turnstile_site_key') ?? ''));
        if ($dbKey !== '') {
            return $dbKey;
        }

        return trim((string) config('services.turnstile.site_key', ''));
    }

    public function secretKey(): string
    {
        $dbSecret = trim((string) (SystemSetting::get('turnstile_secret_key') ?? ''));
        if ($dbSecret !== '') {
            return $dbSecret;
        }

        return trim((string) config('services.turnstile.secret_key', ''));
    }

    public function isFlagEnabled(): bool
    {
        $dbSetting = SystemSetting::get('turnstile_enabled');
        if ($dbSetting !== null && $dbSetting !== '') {
            return filter_var($dbSetting, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) config('services.turnstile.enabled', true);
    }

    public function isEnabled(): bool
    {
        return $this->isFlagEnabled()
            && $this->siteKey() !== ''
            && $this->secretKey() !== '';
    }

    /**
     * Verify a Cloudflare Turnstile response token with Cloudflare's siteverify endpoint.
     *
     * @return array{success: bool, message: ?string, error_codes: array<int, string>}
     */
    public function verify(?string $token, ?string $remoteIp = null): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => true,
                'message' => null,
                'error_codes' => [],
            ];
        }

        $token = trim((string) $token);
        if ($token === '') {
            return [
                'success' => false,
                'message' => 'Please complete the Cloudflare Turnstile verification.',
                'error_codes' => ['missing-input-response'],
            ];
        }

        try {
            $payload = [
                'secret' => $this->secretKey(),
                'response' => $token,
            ];

            if (!empty($remoteIp)) {
                $payload['remoteip'] = $remoteIp;
            }

            $response = Http::asForm()
                ->timeout(6)
                ->post(self::VERIFY_URL, $payload);

            if (!$response->successful()) {
                Log::warning('Cloudflare Turnstile verification HTTP error', [
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Security verification service is temporarily unavailable. Please try again.',
                    'error_codes' => ['http-' . $response->status()],
                ];
            }

            $data = $response->json();
            $passed = (bool) ($data['success'] ?? false);
            $errorCodes = is_array($data['error-codes'] ?? null) ? $data['error-codes'] : [];

            return [
                'success' => $passed,
                'message' => $passed ? null : 'Human verification failed. Please complete the Cloudflare Turnstile check and try again.',
                'error_codes' => $errorCodes,
            ];
        } catch (\Throwable $e) {
            Log::warning('Cloudflare Turnstile verification exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Could not verify Cloudflare Turnstile challenge. Please try again.',
                'error_codes' => ['network-error'],
            ];
        }
    }
}

