<?php

namespace App\Modules\Advertising\Services;

use App\Modules\Advertising\Models\AdCampaign;
use App\Modules\Advertising\Models\AdCampaignPlacement;
use App\Modules\Advertising\Models\AdCreative;
use App\Modules\Advertising\Models\AdTargetingRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdCampaignManager
{
    public function __construct(
        protected AdDeliveryService $deliveryService
    ) {}

    /**
     * Create or update a campaign along with its primary creative, placements, and targeting rules.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveCampaign(array $data, ?int $campaignId = null, ?int $adminId = null): AdCampaign
    {
        $campaign = DB::transaction(function () use ($data, $campaignId, $adminId) {
            $status = $data['status'] ?? AdCampaign::STATUS_DRAFT;
            $startsAt = !empty($data['starts_at']) ? Carbon::parse($data['starts_at']) : null;
            $endsAt = !empty($data['ends_at']) ? Carbon::parse($data['ends_at']) : null;

            // Automatically promote/classify scheduled future campaigns cleanly when saved as active with future start
            if ($status === AdCampaign::STATUS_ACTIVE && $startsAt && $startsAt->isFuture()) {
                $status = AdCampaign::STATUS_SCHEDULED;
            }
            if ($endsAt && $endsAt->isPast() && in_array($status, [AdCampaign::STATUS_ACTIVE, AdCampaign::STATUS_SCHEDULED], true)) {
                $status = AdCampaign::STATUS_EXPIRED;
            }

            $campaignAttributes = [
                'name' => trim((string) $data['name']),
                'campaign_type' => $data['campaign_type'] ?? AdCampaign::TYPE_BANNER,
                'advertiser_name' => $this->nullableString($data['advertiser_name'] ?? null),
                'description' => $this->nullableString($data['description'] ?? null),
                'destination_url' => $this->nullableString($data['destination_url'] ?? null),
                'internal_notes' => $this->nullableString($data['internal_notes'] ?? null),
                'status' => $status,
                'priority' => (int) ($data['priority'] ?? 10),
                'weight' => max(1, (int) ($data['weight'] ?? 100)),
                'price_cents' => $this->toCents($data['price_dollars'] ?? null) ?? 0,
                'daily_budget_cents' => $this->toCents($data['daily_budget_dollars'] ?? null),
                'total_budget_cents' => $this->toCents($data['total_budget_dollars'] ?? null),
                'cpc_cents' => $this->toCents($data['cpc_dollars'] ?? null),
                'cpm_cents' => $this->toCents($data['cpm_dollars'] ?? null),
                'max_impressions' => $this->nullablePositiveInt($data['max_impressions'] ?? null),
                'max_clicks' => $this->nullablePositiveInt($data['max_clicks'] ?? null),
                'daily_impression_cap' => $this->nullablePositiveInt($data['daily_impression_cap'] ?? null),
                'daily_click_cap' => $this->nullablePositiveInt($data['daily_click_cap'] ?? null),
                'frequency_cap_per_session' => $this->nullablePositiveInt($data['frequency_cap_per_session'] ?? null),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'updated_by_admin_id' => $adminId,
            ];

            if ($campaignId) {
                $campaign = AdCampaign::findOrFail($campaignId);
                $campaign->update($campaignAttributes);
            } else {
                $campaignAttributes['created_by_admin_id'] = $adminId;
                $campaign = AdCampaign::create($campaignAttributes);
            }

            $this->syncCreative($campaign, $data);
            $this->syncPlacements($campaign, $data['placements'] ?? []);
            $this->syncTargetingRules($campaign, $data);

            return $campaign->fresh(['creatives', 'placements', 'targetingRules']);
        });

        $this->deliveryService->clearCache();

        return $campaign;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncCreative(AdCampaign $campaign, array $data): void
    {
        $format = ($data['campaign_type'] ?? AdCampaign::TYPE_BANNER) === AdCampaign::TYPE_AFFILIATE
            ? AdCreative::FORMAT_AFFILIATE
            : AdCreative::FORMAT_BANNER;

        $creativePayload = [
            'format' => $format,
            'title' => $this->nullableString($data['creative_title'] ?? null) ?: $campaign->name,
            'short_description' => $this->nullableString($data['creative_description'] ?? null) ?: $campaign->description,
            'cta_text' => $this->nullableString($data['cta_text'] ?? null) ?: ($format === AdCreative::FORMAT_AFFILIATE ? 'Claim Offer' : 'Learn More'),
            'destination_url' => $this->nullableString($data['destination_url'] ?? null),
            'desktop_banner_url' => $this->nullableString($data['desktop_banner_url'] ?? null),
            'mobile_banner_url' => $this->nullableString($data['mobile_banner_url'] ?? null),
            'thumbnail_url' => $this->nullableString($data['thumbnail_url'] ?? null),
            'alt_text' => $this->nullableString($data['alt_text'] ?? null) ?: $campaign->name,
            'affiliate_brand_name' => $this->nullableString($data['affiliate_brand_name'] ?? null) ?: $campaign->advertiser_name,
            // Store affiliate tracking URL completely verbatim without mutation
            'affiliate_tracking_url' => isset($data['affiliate_tracking_url']) && trim((string) $data['affiliate_tracking_url']) !== ''
                ? trim((string) $data['affiliate_tracking_url'])
                : null,
            'offer_badge' => $this->nullableString($data['offer_badge'] ?? null),
            'disclosure_label' => isset($data['disclosure_label']) && in_array($data['disclosure_label'], AdCreative::DISCLOSURE_LABELS, true)
                ? $data['disclosure_label']
                : ($format === AdCreative::FORMAT_AFFILIATE ? 'Affiliate Offer' : 'Sponsored'),
            'offer_expires_at' => !empty($data['offer_expires_at']) ? Carbon::parse($data['offer_expires_at']) : null,
            'subid_param' => $this->nullableString($data['subid_param'] ?? null),
            'click_id_param' => $this->nullableString($data['click_id_param'] ?? null),
            'html_snippet' => AdUrlService::sanitizeHtmlSnippet($data['html_snippet'] ?? null),
            'is_active' => true,
        ];

        $existing = $campaign->creatives()->orderBy('id')->first();
        if ($existing) {
            $existing->update($creativePayload);
        } else {
            $campaign->creatives()->create($creativePayload);
        }
    }

    /**
     * @param  array<int, string>  $slots
     */
    protected function syncPlacements(AdCampaign $campaign, array $slots): void
    {
        $validSlots = collect($slots)
            ->map(fn ($s) => (string) $s)
            ->filter(fn ($s) => in_array($s, AdCampaign::SLOTS, true))
            ->unique()
            ->values();

        AdCampaignPlacement::where('campaign_id', $campaign->id)->delete();

        foreach ($validSlots as $idx => $slot) {
            AdCampaignPlacement::create([
                'campaign_id' => $campaign->id,
                'placement_slot' => $slot,
                'display_order' => $idx,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncTargetingRules(AdCampaign $campaign, array $data): void
    {
        AdTargetingRule::where('campaign_id', $campaign->id)->delete();

        $ruleMappings = [
            AdTargetingRule::RULE_COUNTRY => $this->parseCsvOrArray($data['target_countries'] ?? []),
            AdTargetingRule::RULE_STATE => $this->parseCsvOrArray($data['target_states'] ?? []),
            AdTargetingRule::RULE_CITY => $this->parseCsvOrArray($data['target_cities'] ?? []),
            AdTargetingRule::RULE_ZIP_CODE => $this->parseCsvOrArray($data['target_zip_codes'] ?? ''),
            AdTargetingRule::RULE_CATEGORY => $this->parseCsvOrArray($data['target_categories'] ?? []),
            AdTargetingRule::RULE_DEVICE => $this->parseCsvOrArray($data['target_devices'] ?? []),
        ];

        foreach ($ruleMappings as $ruleType => $values) {
            if (collect($values)->contains(fn ($v) => strcasecmp(trim((string) $v), 'all') === 0)) {
                continue;
            }

            foreach ($values as $value) {
                if ($value === '') {
                    continue;
                }
                AdTargetingRule::create([
                    'campaign_id' => $campaign->id,
                    'rule_type' => $ruleType,
                    'rule_value' => $ruleType === AdTargetingRule::RULE_STATE || $ruleType === AdTargetingRule::RULE_COUNTRY
                        ? strtoupper($value)
                        : ($ruleType === AdTargetingRule::RULE_DEVICE ? strtolower($value) : $value),
                    'match_mode' => AdTargetingRule::MODE_INCLUDE,
                ]);
            }
        }
    }

    /**
     * @param  array<int, mixed>|string  $input
     * @return array<int, string>
     */
    protected function parseCsvOrArray(array|string $input): array
    {
        $items = is_array($input) ? $input : preg_split('/[\s,]+/', $input, -1, PREG_SPLIT_NO_EMPTY);

        return collect($items ?: [])
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected function nullableString(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }
        $trimmed = trim((string) $val);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function toCents(mixed $dollars): ?int
    {
        if ($dollars === null || $dollars === '') {
            return null;
        }

        return max(0, (int) round(((float) $dollars) * 100));
    }

    protected function nullablePositiveInt(mixed $val): ?int
    {
        if ($val === null || $val === '' || (int) $val <= 0) {
            return null;
        }

        return (int) $val;
    }
}
