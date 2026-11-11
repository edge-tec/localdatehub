<?php

namespace App\Modules\Advertising\Services;

use App\Modules\Admin\Models\AdPlacement;
use App\Modules\Advertising\Models\AdCampaign;
use App\Modules\Advertising\Models\AdClick;
use App\Modules\Advertising\Models\AdConversion;
use App\Modules\Advertising\Models\AdCreative;
use App\Modules\Advertising\Models\AdDailyStatistic;
use App\Modules\Advertising\Models\AdImpression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdTrackingService
{
    public function isBotRequest(?Request $request): bool
    {
        if (!$request) {
            return false;
        }

        $ua = strtolower((string) $request->userAgent());
        if ($ua === '') {
            return false;
        }

        return (bool) preg_match('/(bot|crawl|spider|slurp|mediapartners|headless|curl\/|wget\/|python-requests|go-http-client)/i', $ua);
    }

    public function visitorHash(Request $request): string
    {
        $sessionId = $request->hasSession() ? $request->session()->getId() : '';
        $raw = ($sessionId ?: $request->ip()) . '|' . substr((string) $request->userAgent(), 0, 120);

        return hash_hmac('sha256', $raw, (string) config('app.key', 'localdatehub'));
    }

    /**
     * Record an ad impression with short-window throttling & unique-visitor detection.
     *
     * @param  array<string, mixed>  $context
     */
    public function recordImpression(
        AdCampaign $campaign,
        ?AdCreative $creative,
        string $slot,
        Request $request,
        array $context = []
    ): ?AdImpression {
        if ($this->isBotRequest($request)) {
            return null;
        }

        $visitorHash = $this->visitorHash($request);
        $throttleKey = "ads:impr_throttle:{$visitorHash}:{$campaign->id}:{$slot}";

        // Prevent rapid page reload impression flooding (10 seconds cooldown per slot/campaign/visitor)
        if (!Cache::add($throttleKey, true, now()->addSeconds(10))) {
            return null;
        }

        $isUnique = !AdImpression::query()
            ->where('campaign_id', $campaign->id)
            ->where('placement_slot', $slot)
            ->where('visitor_hash', $visitorHash)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->exists();

        $deviceType = strtolower((string) ($context['device'] ?? 'desktop'));
        $stateCode = strtoupper((string) ($context['state'] ?? ''));

        $impression = DB::transaction(function () use ($campaign, $creative, $slot, $request, $context, $visitorHash, $isUnique, $deviceType, $stateCode) {
            $row = AdImpression::create([
                'campaign_id' => $campaign->id,
                'creative_id' => $creative?->id,
                'placement_slot' => $slot,
                'visitor_hash' => $visitorHash,
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'user_id' => $request->user()?->id,
                'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                'user_agent' => substr((string) $request->userAgent(), 0, 250),
                'device_type' => $deviceType,
                'country_code' => strtoupper((string) ($context['country'] ?? 'US')),
                'state_code' => $stateCode !== '' ? $stateCode : null,
                'city_id' => $context['city_id'] ?? null,
                'category_id' => $context['category_id'] ?? null,
                'page_url' => substr((string) $request->fullUrl(), 0, 490),
                'is_unique' => $isUnique,
                'created_at' => now(),
            ]);

            $cpmSpend = $campaign->cpm_cents ? (int) floor($campaign->cpm_cents / 1000) : 0;

            $campaign->increment('total_impressions');
            if ($isUnique) {
                $campaign->increment('total_unique_impressions');
            }
            if ($cpmSpend > 0) {
                $campaign->increment('spent_cents', $cpmSpend);
            }

            if ($campaign->legacy_placement_id) {
                AdPlacement::where('id', $campaign->legacy_placement_id)->increment('impressions');
            }

            $this->upsertDailyStat($campaign->id, $slot, $deviceType, $stateCode, [
                'impressions' => 1,
                'unique_impressions' => $isUnique ? 1 : 0,
                'spend_cents' => $cpmSpend,
            ]);

            return $row;
        });

        return $impression;
    }

    /**
     * Track outbound ad click securely, check click-fraud heuristics, and return target URL.
     *
     * @param  array<string, mixed>  $context
     * @return array{click: AdClick, redirect_url: string}
     */
    public function recordClickAndBuildRedirect(
        AdCampaign $campaign,
        ?AdCreative $creative,
        string $slot,
        Request $request,
        array $context = []
    ): array {
        $rawDestination = $creative?->resolvedTargetUrl() ?: $campaign->destination_url;
        if (!AdUrlService::isSafeDestinationUrl($rawDestination)) {
            $rawDestination = url('/');
        }

        $visitorHash = $this->visitorHash($request);
        $ipHash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $isBot = $this->isBotRequest($request);
        $recentClickExists = AdClick::query()
            ->where('campaign_id', $campaign->id)
            ->where(fn ($q) => $q->where('visitor_hash', $visitorHash)->orWhere('ip_hash', $ipHash))
            ->where('created_at', '>=', now()->subSeconds(30))
            ->exists();

        $isSuspicious = $isBot || $recentClickExists;
        $isUnique = !AdClick::query()
            ->where('campaign_id', $campaign->id)
            ->where(fn ($q) => $q->where('visitor_hash', $visitorHash)->orWhere('ip_hash', $ipHash))
            ->where('is_suspicious', false)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        $clickToken = 'clk_' . str_replace('-', '', (string) Str::uuid());
        $deviceType = strtolower((string) ($context['device'] ?? $this->detectDeviceFromRequest($request)));
        $stateCode = strtoupper((string) ($context['state'] ?? ''));

        // Build intact affiliate destination URL + optional configured tracking keys
        $trackingParams = [];
        if ($creative?->click_id_param) {
            $trackingParams[$creative->click_id_param] = $clickToken;
        }
        if ($creative?->subid_param) {
            $trackingParams[$creative->subid_param] = 'ldh_' . $campaign->id . '_' . $slot;
        }
        if (is_array($creative?->extra_tracking_params)) {
            foreach ($creative->extra_tracking_params as $k => $v) {
                $trackingParams[(string) $k] = (string) $v;
            }
        }

        $finalDestination = AdUrlService::buildAffiliateRedirectUrl($rawDestination, $trackingParams);

        $click = DB::transaction(function () use (
            $clickToken,
            $campaign,
            $creative,
            $slot,
            $request,
            $context,
            $visitorHash,
            $finalDestination,
            $isUnique,
            $isSuspicious,
            $deviceType,
            $stateCode
        ) {
            $clickRow = AdClick::create([
                'click_token' => $clickToken,
                'campaign_id' => $campaign->id,
                'creative_id' => $creative?->id,
                'placement_slot' => $slot,
                'visitor_hash' => $visitorHash,
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'user_id' => $request->user()?->id,
                'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                'user_agent' => substr((string) $request->userAgent(), 0, 250),
                'device_type' => $deviceType,
                'country_code' => strtoupper((string) ($context['country'] ?? 'US')),
                'state_code' => $stateCode !== '' ? $stateCode : null,
                'city_id' => $context['city_id'] ?? null,
                'category_id' => $context['category_id'] ?? null,
                'destination_url' => $finalDestination,
                'is_unique' => $isUnique,
                'is_suspicious' => $isSuspicious,
                'created_at' => now(),
            ]);

            if (!$isSuspicious) {
                $cpcSpend = (int) ($campaign->cpc_cents ?? 0);
                $campaign->increment('total_clicks');
                if ($isUnique) {
                    $campaign->increment('total_unique_clicks');
                }
                if ($cpcSpend > 0) {
                    $campaign->increment('spent_cents', $cpcSpend);
                }

                if ($campaign->legacy_placement_id) {
                    AdPlacement::where('id', $campaign->legacy_placement_id)->increment('clicks');
                }

                $this->upsertDailyStat($campaign->id, $slot, $deviceType, $stateCode, [
                    'clicks' => 1,
                    'unique_clicks' => $isUnique ? 1 : 0,
                    'spend_cents' => $cpcSpend,
                ]);
            }

            return $clickRow;
        });

        return [
            'click' => $click,
            'redirect_url' => $finalDestination,
        ];
    }

    /**
     * Record a verified S2S conversion postback.
     *
     * @param  array<string, mixed>  $payload
     */
    public function recordConversion(AdCampaign $campaign, array $payload): AdConversion
    {
        $externalTxId = isset($payload['tx_id']) ? trim((string) $payload['tx_id']) : null;
        if ($externalTxId === '') {
            $externalTxId = null;
        }

        // Idempotent duplicate callback guard
        if ($externalTxId !== null) {
            $existing = AdConversion::where('campaign_id', $campaign->id)
                ->where('external_transaction_id', $externalTxId)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $click = null;
        if (!empty($payload['click_id'])) {
            $click = AdClick::where('campaign_id', $campaign->id)
                ->where('click_token', trim((string) $payload['click_id']))
                ->first();
        }

        $payoutCents = isset($payload['payout']) ? (int) round((float) $payload['payout'] * 100) : 0;
        $revenueCents = isset($payload['revenue']) ? (int) round((float) $payload['revenue'] * 100) : $payoutCents;

        return DB::transaction(function () use ($campaign, $click, $externalTxId, $payload, $payoutCents, $revenueCents) {
            $conversion = AdConversion::create([
                'conversion_token' => 'cnv_' . str_replace('-', '', (string) Str::uuid()),
                'campaign_id' => $campaign->id,
                'click_id' => $click?->id,
                'external_transaction_id' => $externalTxId,
                'sub_id' => isset($payload['sub_id']) ? substr((string) $payload['sub_id'], 0, 120) : null,
                'payout_cents' => max(0, $payoutCents),
                'revenue_cents' => max(0, $revenueCents),
                'currency' => strtoupper(substr((string) ($payload['currency'] ?? 'USD'), 0, 10)),
                'status' => AdConversion::STATUS_CONFIRMED,
                'verified_via' => !empty($payload['secret']) ? 'postback_secret' : 'click_token',
                'metadata_json' => collect($payload)->except(['secret'])->all(),
                'created_at' => now(),
            ]);

            $campaign->increment('total_conversions');
            if ($revenueCents > 0) {
                $campaign->increment('total_conversion_revenue_cents', $revenueCents);
            }

            $this->upsertDailyStat(
                $campaign->id,
                $click?->placement_slot ?? 'direct_postback',
                $click?->device_type ?? 'desktop',
                $click?->state_code ?? '',
                [
                    'conversions' => 1,
                    'revenue_cents' => max(0, $revenueCents),
                ]
            );

            return $conversion;
        });
    }

    /**
     * @param  array<string, int>  $deltas
     */
    protected function upsertDailyStat(int $campaignId, string $slot, string $deviceType, string $stateCode, array $deltas): void
    {
        $date = now()->toDateString();
        $normalizedDevice = $deviceType ?: 'desktop';
        $normalizedState = $stateCode ?: '';

        $row = AdDailyStatistic::query()
            ->whereDate('date', $date)
            ->where('campaign_id', $campaignId)
            ->where('placement_slot', $slot)
            ->where('device_type', $normalizedDevice)
            ->where('state_code', $normalizedState)
            ->first();

        if (!$row) {
            try {
                $row = AdDailyStatistic::create([
                    'date' => $date,
                    'campaign_id' => $campaignId,
                    'placement_slot' => $slot,
                    'device_type' => $normalizedDevice,
                    'state_code' => $normalizedState,
                    'impressions' => 0,
                    'unique_impressions' => 0,
                    'clicks' => 0,
                    'unique_clicks' => 0,
                    'conversions' => 0,
                    'revenue_cents' => 0,
                    'spend_cents' => 0,
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                $row = AdDailyStatistic::query()
                    ->whereDate('date', $date)
                    ->where('campaign_id', $campaignId)
                    ->where('placement_slot', $slot)
                    ->where('device_type', $normalizedDevice)
                    ->where('state_code', $normalizedState)
                    ->firstOrFail();
            }
        }

        foreach ($deltas as $col => $delta) {
            if ($delta > 0) {
                $row->increment($col, $delta);
            }
        }
    }

    protected function detectDeviceFromRequest(Request $request): string
    {
        $ua = strtolower((string) $request->userAgent());
        if (preg_match('/(ipad|tablet|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/(mobile|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop|android.*mobile)/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }
}
