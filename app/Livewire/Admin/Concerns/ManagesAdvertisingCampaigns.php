<?php

namespace App\Livewire\Admin\Concerns;

use App\Modules\Admin\Models\AdPlacement;
use App\Modules\Admin\Services\AdminAuditService;
use App\Modules\Advertising\Models\AdCampaign;
use App\Modules\Advertising\Models\AdCreative;
use App\Modules\Advertising\Models\AdPlacementConfig;
use App\Modules\Advertising\Models\AdTargetingRule;
use App\Modules\Advertising\Services\AdAnalyticsService;
use App\Modules\Advertising\Services\AdCampaignManager;
use App\Modules\Advertising\Services\AdDeliveryService;
use App\Modules\Advertising\Services\AdUrlService;
use App\Modules\Categories\Models\Category;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\Country;
use App\Modules\Locations\Models\State;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

trait ManagesAdvertisingCampaigns
{
    public bool $showCampaignModal = false;
    public ?int $editingCampaignId = null;
    public array $campaignForm = [];

    public $campaignDesktopBannerUpload = null;
    public $campaignMobileBannerUpload = null;
    public $campaignThumbnailUpload = null;

    public string $campaignSearch = '';
    public string $campaignTypeFilter = 'all';
    public string $campaignStatusFilter = 'all';
    public string $campaignSlotFilter = 'all';

    public array $selectedCampaignIds = [];

    public bool $showDeleteCampaignModal = false;
    public ?int $deleteCampaignId = null;
    public ?string $deleteCampaignName = null;

    public bool $showPreviewCampaignModal = false;
    public ?array $previewCampaignData = null;

    public bool $showDiagnosticModal = false;
    public ?array $diagnosticData = null;

    public string $adAnalyticsRange = 'last_7_days';
    public string $adAnalyticsCustomStart = '';
    public string $adAnalyticsCustomEnd = '';

    public array $placementConfigsForm = [];

    public function updatingCampaignSearch(): void
    {
        $this->resetPage('campaignPage');
    }

    public function updatingCampaignTypeFilter(): void
    {
        $this->resetPage('campaignPage');
    }

    public function updatingCampaignStatusFilter(): void
    {
        $this->resetPage('campaignPage');
    }

    public function updatingCampaignSlotFilter(): void
    {
        $this->resetPage('campaignPage');
    }

    public function resetCampaignFilters(): void
    {
        $this->campaignSearch = '';
        $this->campaignTypeFilter = 'all';
        $this->campaignStatusFilter = 'all';
        $this->campaignSlotFilter = 'all';
        $this->selectedCampaignIds = [];
        $this->resetPage('campaignPage');
    }

    public function toggleSelectAllCampaigns(array $visibleIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $visibleIds)));
        $current = array_map('intval', $this->selectedCampaignIds);
        $allSelected = !empty($ids) && empty(array_diff($ids, $current));

        $this->selectedCampaignIds = $allSelected
            ? array_values(array_diff($current, $ids))
            : array_values(array_unique(array_merge($current, $ids)));
    }

    public function bulkUpdateCampaignStatus(string $newStatus): void
    {
        $this->authorizePermission('ads.manage');
        if (!in_array($newStatus, AdCampaign::STATUSES, true) || empty($this->selectedCampaignIds)) {
            return;
        }

        $ids = array_map('intval', $this->selectedCampaignIds);
        $campaigns = AdCampaign::whereIn('id', $ids)->get();

        foreach ($campaigns as $campaign) {
            $before = ['status' => $campaign->status];
            $campaign->update([
                'status' => $newStatus,
                'updated_by_admin_id' => Auth::id(),
            ]);
            AdminAuditService::log('ads.campaign.bulk_status', $campaign, $before, ['status' => $newStatus]);
        }

        $count = $campaigns->count();
        $this->selectedCampaignIds = [];
        $label = ucwords(str_replace('_', ' ', $newStatus));
        session()->flash('success', "{$count} campaign(s) updated to {$label}.");
        $this->dispatch('toast', type: 'success', message: "{$count} campaign(s) set to {$label}.");
    }

    public function bulkDeleteCampaigns(): void
    {
        $this->authorizePermission('ads.manage');
        if (empty($this->selectedCampaignIds)) {
            return;
        }

        $ids = array_map('intval', $this->selectedCampaignIds);
        $campaigns = AdCampaign::whereIn('id', $ids)->get();

        foreach ($campaigns as $campaign) {
            AdminAuditService::log('ads.campaign.bulk_delete', $campaign, ['name' => $campaign->name, 'status' => $campaign->status], null);
            $campaign->delete();
        }

        $count = $campaigns->count();
        $this->selectedCampaignIds = [];
        session()->flash('success', "{$count} campaign(s) moved to trash.");
        $this->dispatch('toast', type: 'info', message: "{$count} campaign(s) deleted.");
    }

    public function clearCampaignMedia(string $field): void
    {
        $this->authorizePermission('ads.manage');
        if (in_array($field, ['desktop_banner_url', 'mobile_banner_url', 'thumbnail_url'], true)) {
            $this->campaignForm[$field] = '';
        }
    }

    public function selectPlacementGroup(string $group): void
    {
        $this->authorizePermission('ads.manage');
        $feeds = [
            AdCampaign::SLOT_HOMEPAGE_FEED,
            AdCampaign::SLOT_BETWEEN_LISTINGS,
            AdCampaign::SLOT_CATEGORY_PAGE,
            AdCampaign::SLOT_SEARCH_RESULTS,
            AdCampaign::SLOT_LOCATION_PAGE,
            AdCampaign::SLOT_RELATED_LISTINGS,
            AdCampaign::SLOT_MOBILE_FEED,
            AdCampaign::SLOT_DESKTOP_FEED,
        ];
        $banners = [
            AdCampaign::SLOT_HOMEPAGE_FEATURED,
            AdCampaign::SLOT_HOMEPAGE_TOP,
            AdCampaign::SLOT_SIDEBAR,
            AdCampaign::SLOT_LISTING_DETAIL,
            AdCampaign::SLOT_FOOTER,
        ];

        $this->campaignForm['placements'] = match ($group) {
            'all' => AdCampaign::SLOTS,
            'feeds' => $feeds,
            'banners' => $banners,
            'clear' => [],
            default => $this->campaignForm['placements'] ?? [],
        };
    }

    /**
     * Quick-toggle audience targeting dimensions between nationwide/all-traffic and full selection.
     */
    public function selectTargetingScope(string $dimension, string $mode = 'all'): void
    {
        $this->authorizePermission('ads.manage');

        $fieldMap = [
            'countries' => 'target_countries',
            'states' => 'target_states',
            'categories' => 'target_categories',
            'cities' => 'target_cities',
        ];

        $field = $fieldMap[$dimension] ?? null;
        if (!$field) {
            return;
        }

        if ($mode !== 'every') {
            $this->campaignForm[$field] = [];
            return;
        }

        $this->campaignForm[$field] = match ($dimension) {
            'countries' => Schema::hasTable('countries')
                ? Country::where('is_active', true)->orderBy('display_order')->orderBy('name')->pluck('code')->map(fn ($c) => strtoupper((string) $c))->all()
                : ['US', 'GB', 'CA', 'AU', 'IE', 'NZ'],
            'states' => Schema::hasTable('states')
                ? State::where('is_active', true)->orderBy('name')->pluck('code')->all()
                : [],
            'categories' => Schema::hasTable('categories')
                ? Category::where('is_active', true)->orderBy('display_order')->orderBy('name')->pluck('id')->map(fn ($id) => (string) $id)->all()
                : [],
            'cities' => Schema::hasTable('cities')
                ? City::orderByDesc('is_popular')->orderBy('name')->take(120)->pluck('id')->map(fn ($id) => (string) $id)->all()
                : [],
            default => [],
        };
    }

    public function resetCampaignForm(): void
    {
        $this->editingCampaignId = null;
        $this->campaignDesktopBannerUpload = null;
        $this->campaignMobileBannerUpload = null;
        $this->campaignThumbnailUpload = null;

        $this->campaignForm = [
            'name' => '',
            'campaign_type' => AdCampaign::TYPE_BANNER,
            'advertiser_name' => '',
            'description' => '',
            'destination_url' => '',
            'internal_notes' => '',
            'status' => AdCampaign::STATUS_ACTIVE,
            'priority' => 10,
            'weight' => 100,
            'price_dollars' => '',
            'daily_budget_dollars' => '',
            'total_budget_dollars' => '',
            'cpc_dollars' => '',
            'cpm_dollars' => '',
            'max_impressions' => '',
            'max_clicks' => '',
            'daily_impression_cap' => '',
            'daily_click_cap' => '',
            'frequency_cap_per_session' => '',
            'starts_at' => '',
            'ends_at' => '',
            'timezone' => (string) config('app.timezone', 'UTC'),
            'creative_title' => '',
            'creative_description' => '',
            'cta_text' => 'Learn More',
            'desktop_banner_url' => '',
            'mobile_banner_url' => '',
            'thumbnail_url' => '',
            'alt_text' => '',
            'affiliate_brand_name' => '',
            'affiliate_tracking_url' => '',
            'offer_badge' => '',
            'disclosure_label' => 'Sponsored',
            'offer_expires_at' => '',
            'subid_param' => '',
            'click_id_param' => '',
            'html_snippet' => '',
            'placements' => [AdCampaign::SLOT_BETWEEN_LISTINGS],
            'target_countries' => [],
            'target_states' => [],
            'target_cities' => [],
            'target_zip_codes' => '',
            'target_categories' => [],
            'target_devices' => [],
        ];
    }

    public function openCampaignModal(?int $campaignId = null, ?string $defaultSlot = null): void
    {
        $this->authorizePermission('ads.manage');
        $this->resetValidation();
        $this->resetCampaignForm();

        if ($defaultSlot && in_array($defaultSlot, AdCampaign::SLOTS, true)) {
            $this->campaignForm['placements'] = [$defaultSlot];
        }

        if ($campaignId) {
            $campaign = AdCampaign::with(['creatives', 'placements', 'targetingRules'])->findOrFail($campaignId);
            $creative = $campaign->creatives->first();
            $rules = $campaign->targetingRules->groupBy('rule_type');

            $this->editingCampaignId = $campaign->id;
            $this->campaignForm = [
                'name' => $campaign->name,
                'campaign_type' => $campaign->campaign_type,
                'advertiser_name' => (string) $campaign->advertiser_name,
                'description' => (string) $campaign->description,
                'destination_url' => (string) ($creative?->destination_url ?: $campaign->destination_url),
                'internal_notes' => (string) $campaign->internal_notes,
                'status' => $campaign->status,
                'priority' => $campaign->priority,
                'weight' => $campaign->weight,
                'price_dollars' => $campaign->price_cents ? number_format($campaign->price_cents / 100, 2, '.', '') : '',
                'daily_budget_dollars' => $campaign->daily_budget_cents ? number_format($campaign->daily_budget_cents / 100, 2, '.', '') : '',
                'total_budget_dollars' => $campaign->total_budget_cents ? number_format($campaign->total_budget_cents / 100, 2, '.', '') : '',
                'cpc_dollars' => $campaign->cpc_cents ? number_format($campaign->cpc_cents / 100, 2, '.', '') : '',
                'cpm_dollars' => $campaign->cpm_cents ? number_format($campaign->cpm_cents / 100, 2, '.', '') : '',
                'max_impressions' => $campaign->max_impressions ? (string) $campaign->max_impressions : '',
                'max_clicks' => $campaign->max_clicks ? (string) $campaign->max_clicks : '',
                'daily_impression_cap' => $campaign->daily_impression_cap ? (string) $campaign->daily_impression_cap : '',
                'daily_click_cap' => $campaign->daily_click_cap ? (string) $campaign->daily_click_cap : '',
                'frequency_cap_per_session' => $campaign->frequency_cap_per_session ? (string) $campaign->frequency_cap_per_session : '',
                'starts_at' => $campaign->starts_at?->format('Y-m-d\TH:i') ?? '',
                'ends_at' => $campaign->ends_at?->format('Y-m-d\TH:i') ?? '',
                'timezone' => (string) config('app.timezone', 'UTC'),
                'creative_title' => (string) $creative?->title,
                'creative_description' => (string) $creative?->short_description,
                'cta_text' => (string) ($creative?->cta_text ?: 'Learn More'),
                'desktop_banner_url' => (string) $creative?->desktop_banner_url,
                'mobile_banner_url' => (string) $creative?->mobile_banner_url,
                'thumbnail_url' => (string) $creative?->thumbnail_url,
                'alt_text' => (string) $creative?->alt_text,
                'affiliate_brand_name' => (string) $creative?->affiliate_brand_name,
                'affiliate_tracking_url' => (string) $creative?->affiliate_tracking_url,
                'offer_badge' => (string) $creative?->offer_badge,
                'disclosure_label' => (string) ($creative?->disclosure_label ?: 'Sponsored'),
                'offer_expires_at' => $creative?->offer_expires_at?->format('Y-m-d\TH:i') ?? '',
                'subid_param' => (string) $creative?->subid_param,
                'click_id_param' => (string) $creative?->click_id_param,
                'html_snippet' => (string) $creative?->html_snippet,
                'placements' => $campaign->placements->pluck('placement_slot')->all(),
                'target_countries' => $rules->get(AdTargetingRule::RULE_COUNTRY)?->pluck('rule_value')->all() ?: [],
                'target_states' => $rules->get(AdTargetingRule::RULE_STATE)?->pluck('rule_value')->all() ?: [],
                'target_cities' => $rules->get(AdTargetingRule::RULE_CITY)?->pluck('rule_value')->all() ?: [],
                'target_zip_codes' => $rules->get(AdTargetingRule::RULE_ZIP_CODE)?->pluck('rule_value')->implode(', ') ?? '',
                'target_categories' => $rules->get(AdTargetingRule::RULE_CATEGORY)?->pluck('rule_value')->all() ?: [],
                'target_devices' => $rules->get(AdTargetingRule::RULE_DEVICE)?->pluck('rule_value')->all() ?: [],
            ];
        }

        $this->showCampaignModal = true;
    }

    public function updatedCampaignDesktopBannerUpload(): void
    {
        $this->authorizePermission('ads.manage');
        $this->validate([
            'campaignDesktopBannerUpload' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);
        $path = $this->campaignDesktopBannerUpload->store('ad-banners', 'public');
        $this->campaignForm['desktop_banner_url'] = '/storage/' . $path;
        $this->campaignDesktopBannerUpload = null;
    }

    public function updatedCampaignMobileBannerUpload(): void
    {
        $this->authorizePermission('ads.manage');
        $this->validate([
            'campaignMobileBannerUpload' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);
        $path = $this->campaignMobileBannerUpload->store('ad-banners', 'public');
        $this->campaignForm['mobile_banner_url'] = '/storage/' . $path;
        $this->campaignMobileBannerUpload = null;
    }

    public function updatedCampaignThumbnailUpload(): void
    {
        $this->authorizePermission('ads.manage');
        $this->validate([
            'campaignThumbnailUpload' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);
        $path = $this->campaignThumbnailUpload->store('ad-banners', 'public');
        $this->campaignForm['thumbnail_url'] = '/storage/' . $path;
        $this->campaignThumbnailUpload = null;
    }

    public function saveCampaign(): void
    {
        $this->authorizePermission('ads.manage');

        if ($this->campaignDesktopBannerUpload) {
            $this->updatedCampaignDesktopBannerUpload();
        }
        if ($this->campaignMobileBannerUpload) {
            $this->updatedCampaignMobileBannerUpload();
        }
        if ($this->campaignThumbnailUpload) {
            $this->updatedCampaignThumbnailUpload();
        }

        $safeUrlRule = function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value !== null && trim((string) $value) !== '' && !AdUrlService::isSafeDestinationUrl((string) $value)) {
                $fail('URL must start with http:// or https://.');
            }
        };

        $safeImageRule = function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value !== null && trim((string) $value) !== '' && !AdUrlService::isSafeImageUrl((string) $value)) {
                $fail('Image must be a valid http(s) URL or uploaded storage image.');
            }
        };

        $validated = $this->validate([
            'campaignForm.name' => ['required', 'string', 'max:150'],
            'campaignForm.campaign_type' => ['required', Rule::in(AdCampaign::TYPES)],
            'campaignForm.advertiser_name' => ['nullable', 'string', 'max:150'],
            'campaignForm.description' => ['nullable', 'string', 'max:2000'],
            'campaignForm.destination_url' => ['nullable', 'string', 'max:2000', $safeUrlRule],
            'campaignForm.internal_notes' => ['nullable', 'string', 'max:2000'],
            'campaignForm.status' => ['required', Rule::in(AdCampaign::STATUSES)],
            'campaignForm.priority' => ['required', 'integer', 'min:0', 'max:1000'],
            'campaignForm.weight' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'campaignForm.price_dollars' => ['nullable', 'numeric', 'min:0'],
            'campaignForm.daily_budget_dollars' => ['nullable', 'numeric', 'min:0'],
            'campaignForm.total_budget_dollars' => ['nullable', 'numeric', 'min:0'],
            'campaignForm.cpc_dollars' => ['nullable', 'numeric', 'min:0'],
            'campaignForm.cpm_dollars' => ['nullable', 'numeric', 'min:0'],
            'campaignForm.max_impressions' => ['nullable', 'integer', 'min:1'],
            'campaignForm.max_clicks' => ['nullable', 'integer', 'min:1'],
            'campaignForm.daily_impression_cap' => ['nullable', 'integer', 'min:1'],
            'campaignForm.daily_click_cap' => ['nullable', 'integer', 'min:1'],
            'campaignForm.frequency_cap_per_session' => ['nullable', 'integer', 'min:1', 'max:100'],
            'campaignForm.starts_at' => ['nullable', 'date'],
            'campaignForm.ends_at' => ['nullable', 'date', 'after_or_equal:campaignForm.starts_at'],
            'campaignForm.timezone' => ['nullable', 'string', 'max:64'],
            'campaignForm.creative_title' => ['nullable', 'string', 'max:160'],
            'campaignForm.creative_description' => ['nullable', 'string', 'max:1000'],
            'campaignForm.cta_text' => ['nullable', 'string', 'max:60'],
            'campaignForm.desktop_banner_url' => ['nullable', 'string', 'max:500', $safeImageRule],
            'campaignForm.mobile_banner_url' => ['nullable', 'string', 'max:500', $safeImageRule],
            'campaignForm.thumbnail_url' => ['nullable', 'string', 'max:500', $safeImageRule],
            'campaignForm.alt_text' => ['nullable', 'string', 'max:255'],
            'campaignForm.affiliate_brand_name' => ['nullable', 'string', 'max:150'],
            'campaignForm.affiliate_tracking_url' => ['nullable', 'string', 'max:2000', $safeUrlRule],
            'campaignForm.offer_badge' => ['nullable', 'string', 'max:60'],
            'campaignForm.disclosure_label' => ['required', Rule::in(AdCreative::DISCLOSURE_LABELS)],
            'campaignForm.offer_expires_at' => ['nullable', 'date'],
            'campaignForm.subid_param' => ['nullable', 'string', 'max:60', 'alpha_dash'],
            'campaignForm.click_id_param' => ['nullable', 'string', 'max:60', 'alpha_dash'],
            'campaignForm.html_snippet' => ['nullable', 'string', 'max:4000'],
            'campaignForm.placements' => ['required', 'array', 'min:1'],
            'campaignForm.placements.*' => ['string', Rule::in(AdCampaign::SLOTS)],
            'campaignForm.target_countries' => ['nullable', 'array'],
            'campaignForm.target_states' => ['nullable', 'array'],
            'campaignForm.target_cities' => ['nullable', 'array'],
            'campaignForm.target_zip_codes' => ['nullable', 'string', 'max:1000'],
            'campaignForm.target_categories' => ['nullable', 'array'],
            'campaignForm.target_devices' => ['nullable', 'array'],
        ])['campaignForm'];

        $manager = app(AdCampaignManager::class);
        $isEdit = $this->editingCampaignId !== null;
        $campaign = $manager->saveCampaign($validated, $this->editingCampaignId, Auth::id());

        AdminAuditService::log(
            $isEdit ? 'ads.campaign.update' : 'ads.campaign.create',
            $campaign,
            null,
            ['name' => $campaign->name, 'status' => $campaign->status, 'type' => $campaign->campaign_type]
        );

        $this->showCampaignModal = false;
        $this->resetCampaignForm();
        session()->flash('success', "Campaign [{$campaign->name}] saved and synchronized live.");
        $this->dispatch('toast', type: 'success', message: "Campaign [{$campaign->name}] saved!");
    }

    public function updateCampaignStatus(int $campaignId, string $newStatus): void
    {
        $this->authorizePermission('ads.manage');
        if (!in_array($newStatus, AdCampaign::STATUSES, true)) {
            return;
        }

        $campaign = AdCampaign::findOrFail($campaignId);
        $before = ['status' => $campaign->status];
        $updates = [
            'status' => $newStatus,
            'updated_by_admin_id' => Auth::id(),
        ];

        // When explicitly activating a campaign whose starts_at is in the future (e.g. timezone offset), start immediately
        if ($newStatus === AdCampaign::STATUS_ACTIVE && $campaign->starts_at && $campaign->starts_at->isFuture()) {
            $updates['starts_at'] = now();
        }

        $campaign->update($updates);
        app(AdDeliveryService::class)->clearCache();

        AdminAuditService::log('ads.campaign.status', $campaign, $before, ['status' => $newStatus]);
        session()->flash('success', "Campaign [{$campaign->name}] updated to " . ucwords(str_replace('_', ' ', $newStatus)) . '.');
        $this->dispatch('toast', type: 'info', message: "Status set to " . ucwords(str_replace('_', ' ', $newStatus)));
    }

    public function openDiagnosticModal(int $campaignId): void
    {
        $this->authorizePermission('ads.manage');
        $campaign = AdCampaign::with(['creatives', 'placements', 'targetingRules'])->findOrFail($campaignId);
        $this->diagnosticData = app(AdDeliveryService::class)->diagnoseCampaign($campaign);
        $this->showDiagnosticModal = true;
    }

    public function flushAdDeliveryCache(?int $refreshCampaignId = null): void
    {
        $this->authorizePermission('ads.manage');
        $delivery = app(AdDeliveryService::class);
        $delivery->clearCache();

        if ($refreshCampaignId) {
            $campaign = AdCampaign::with(['creatives', 'placements', 'targetingRules'])->findOrFail($refreshCampaignId);
            $this->diagnosticData = $delivery->diagnoseCampaign($campaign);
        }

        $this->dispatch('toast', type: 'success', message: 'Ad delivery cache flushed and re-evaluated.');
    }

    public function startCampaignImmediately(int $campaignId): void
    {
        $this->authorizePermission('ads.manage');
        $campaign = AdCampaign::with(['creatives', 'placements', 'targetingRules'])->findOrFail($campaignId);
        $campaign->update([
            'status' => AdCampaign::STATUS_ACTIVE,
            'starts_at' => now()->subMinute(),
            'updated_by_admin_id' => Auth::id(),
        ]);

        $delivery = app(AdDeliveryService::class);
        $delivery->clearCache();
        $this->diagnosticData = $delivery->diagnoseCampaign($campaign->fresh(['creatives', 'placements', 'targetingRules']));

        AdminAuditService::log('ads.campaign.start_now', $campaign, null, ['starts_at' => now()->toIso8601String()]);
        $this->dispatch('toast', type: 'success', message: "Campaign [{$campaign->name}] is now live immediately.");
    }

    public function duplicateCampaign(int $campaignId): void
    {
        $this->authorizePermission('ads.manage');
        $original = AdCampaign::with(['creatives', 'placements', 'targetingRules'])->findOrFail($campaignId);
        $clone = $original->duplicateCampaign(Auth::id());

        AdminAuditService::log('ads.campaign.duplicate', $clone, ['source_id' => $original->id], null);
        session()->flash('success', "Campaign duplicated as [{$clone->name}] (Draft).");
        $this->dispatch('toast', type: 'success', message: "Campaign duplicated!");
    }

    public function openPreviewCampaignModal(int $campaignId): void
    {
        $this->authorizePermission('ads.manage');
        $campaign = AdCampaign::with(['creatives', 'placements', 'targetingRules'])->findOrFail($campaignId);
        $creative = $campaign->creatives->first();
        $delivery = app(AdDeliveryService::class);

        $this->previewCampaignData = [
            'id' => $campaign->id,
            'uuid' => $campaign->uuid,
            'name' => $campaign->name,
            'campaign_type' => $campaign->campaign_type,
            'status' => $campaign->status,
            'priority' => $campaign->priority,
            'advertiser_name' => $campaign->advertiser_name ?: 'Direct Sponsor',
            'placements' => $campaign->placements->pluck('placement_slot')->all(),
            'impressions' => $campaign->total_impressions,
            'clicks' => $campaign->total_clicks,
            'conversions' => $campaign->total_conversions,
            'ctr' => $campaign->clickThroughRate(),
            'postback_url' => route('ads.postback', ['campaign' => $campaign->uuid]),
            'postback_secret' => $campaign->postback_secret,
            'payload' => $delivery->formatCreativePayload($campaign, $creative, $campaign->placements->first()?->placement_slot ?? AdCampaign::SLOT_BETWEEN_LISTINGS),
        ];

        $this->showPreviewCampaignModal = true;
    }

    public function confirmDeleteCampaign(int $campaignId): void
    {
        $this->authorizePermission('ads.manage');
        $campaign = AdCampaign::findOrFail($campaignId);
        $this->deleteCampaignId = $campaign->id;
        $this->deleteCampaignName = $campaign->name;
        $this->showDeleteCampaignModal = true;
    }

    public function deleteCampaign(): void
    {
        $this->authorizePermission('ads.manage');
        if (!$this->deleteCampaignId) {
            return;
        }

        $campaign = AdCampaign::findOrFail($this->deleteCampaignId);
        $name = $campaign->name;
        AdminAuditService::log('ads.campaign.delete', $campaign, ['name' => $name, 'status' => $campaign->status], null);
        $campaign->delete();

        $this->showDeleteCampaignModal = false;
        $this->deleteCampaignId = null;
        $this->deleteCampaignName = null;
        $this->showPreviewCampaignModal = false;
        $this->previewCampaignData = null;

        session()->flash('success', "Campaign [{$name}] moved to trash.");
        $this->dispatch('toast', type: 'info', message: "Campaign [{$name}] deleted.");
    }

    private function persistPlacementConfig(string $slot): AdPlacementConfig
    {
        $input = $this->placementConfigsForm[$slot] ?? [];
        $config = AdPlacementConfig::forSlot($slot);

        $config->update([
            'insert_every_n' => max(1, min(50, (int) ($input['insert_every_n'] ?? $config->insert_every_n))),
            'max_ads_per_page' => max(0, min(20, (int) ($input['max_ads_per_page'] ?? $config->max_ads_per_page))),
            'rotation_mode' => in_array($input['rotation_mode'] ?? '', [
                AdPlacementConfig::ROTATION_PRIORITY_WEIGHTED,
                AdPlacementConfig::ROTATION_RANDOM,
                AdPlacementConfig::ROTATION_ROUND_ROBIN,
            ], true) ? $input['rotation_mode'] : $config->rotation_mode,
            'dedupe_session' => (bool) ($input['dedupe_session'] ?? true),
            'is_enabled' => (bool) ($input['is_enabled'] ?? true),
        ]);

        return $config;
    }

    public function savePlacementConfig(string $slot): void
    {
        $this->authorizePermission('ads.manage');
        if (!in_array($slot, AdCampaign::SLOTS, true)) {
            return;
        }

        $config = $this->persistPlacementConfig($slot);

        AdminAuditService::log('ads.placement_config.update', $config, null, ['slot' => $slot, 'every_n' => $config->insert_every_n]);
        $this->dispatch('toast', type: 'success', message: "Delivery frequency for [" . str_replace('_', ' ', $slot) . "] updated!");
    }

    public function saveAllPlacementConfigs(): void
    {
        $this->authorizePermission('ads.manage');

        foreach (AdCampaign::SLOTS as $slot) {
            $this->persistPlacementConfig($slot);
        }

        AdminAuditService::log('ads.placement_config.bulk_update', null, null, ['slots' => count(AdCampaign::SLOTS)]);
        session()->flash('success', 'All 13 placement delivery rules saved.');
        $this->dispatch('toast', type: 'success', message: 'All 13 slot delivery rules updated!');
    }

    public function exportAdAnalyticsCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorizePermission('ads.manage');
        $report = app(AdAnalyticsService::class)->getReport(
            $this->adAnalyticsRange,
            $this->adAnalyticsCustomStart ?: null,
            $this->adAnalyticsCustomEnd ?: null
        );

        $filename = 'ad-analytics-' . ($report['start_date'] ?? now()->toDateString()) . '-to-' . ($report['end_date'] ?? now()->toDateString()) . '.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Impressions', 'Unique Views', 'Clicks', 'CTR (%)', 'Conversions', 'Revenue (USD)']);
            foreach ($report['daily_breakdown'] ?? [] as $row) {
                fputcsv($out, [
                    $row->date,
                    (int) $row->impressions,
                    (int) $row->unique_impressions,
                    (int) $row->clicks,
                    (float) $row->ctr,
                    (int) $row->conversions,
                    number_format(((int) ($row->revenue_cents ?? 0)) / 100, 2, '.', ''),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Gather enterprise campaign tables, slot configs, analytics & targeting options for the Ads tab.
     *
     * @return array<string, mixed>
     */
    protected function buildEnterpriseAdsData(): array
    {
        if (!Schema::hasTable('ad_campaigns')) {
            return [
                'campaigns' => collect(),
                'placementConfigs' => collect(),
                'analyticsReport' => app(AdAnalyticsService::class)->getReport($this->adAnalyticsRange),
                'enterpriseStats' => [],
                'targetingOptions' => ['countries' => collect(), 'categories' => collect(), 'states' => collect(), 'cities' => collect()],
            ];
        }

        // Sync scheduled/expired statuses passively on admin view
        app(AdDeliveryService::class)->syncLifecycleStatuses();

        $query = AdCampaign::query()
            ->with(['creatives', 'placements', 'targetingRules'])
            ->orderByDesc('priority')
            ->latest('updated_at');

        if ($this->campaignStatusFilter !== 'all') {
            $query->where('status', $this->campaignStatusFilter);
        }

        if ($this->campaignTypeFilter !== 'all') {
            $query->where('campaign_type', $this->campaignTypeFilter);
        }

        if ($this->campaignSlotFilter !== 'all') {
            $query->whereHas('placements', fn ($q) => $q->where('placement_slot', $this->campaignSlotFilter));
        }

        if (trim($this->campaignSearch) !== '') {
            $term = trim($this->campaignSearch);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('advertiser_name', 'like', "%{$term}%")
                    ->orWhere('destination_url', 'like', "%{$term}%");
            });
        }

        $allCampaigns = AdCampaign::with(['placements'])->get();
        $placementConfigs = collect(AdCampaign::SLOTS)->mapWithKeys(function (string $slot) {
            $cfg = AdPlacementConfig::forSlot($slot);
            if (!isset($this->placementConfigsForm[$slot])) {
                $this->placementConfigsForm[$slot] = [
                    'insert_every_n' => $cfg->insert_every_n,
                    'max_ads_per_page' => $cfg->max_ads_per_page,
                    'rotation_mode' => $cfg->rotation_mode,
                    'dedupe_session' => $cfg->dedupe_session,
                    'is_enabled' => $cfg->is_enabled,
                ];
            }

            return [$slot => $cfg];
        });

        $analyticsReport = app(AdAnalyticsService::class)->getReport(
            $this->adAnalyticsRange,
            $this->adAnalyticsCustomStart ?: null,
            $this->adAnalyticsCustomEnd ?: null
        );

        $legacyImpr = Schema::hasTable('ad_placements') ? (int) AdPlacement::sum('impressions') : 0;
        $legacyClicks = Schema::hasTable('ad_placements') ? (int) AdPlacement::sum('clicks') : 0;
        $legacyActive = Schema::hasTable('ad_placements') ? AdPlacement::where('is_active', true)->count() : 0;
        $legacyTotal = Schema::hasTable('ad_placements') ? AdPlacement::count() : 0;

        $totalImpressions = (int) $allCampaigns->sum('total_impressions') + $legacyImpr;
        $totalClicks = (int) $allCampaigns->sum('total_clicks') + $legacyClicks;
        $totalConversions = (int) $allCampaigns->sum('total_conversions');

        $paginatedCampaigns = $query->paginate(12, ['*'], 'campaignPage');
        $deliveryService = app(AdDeliveryService::class);
        $campaignDiagnostics = [];
        foreach ($paginatedCampaigns->getCollection() as $campRow) {
            $campaignDiagnostics[$campRow->id] = $deliveryService->diagnoseCampaign($campRow);
        }

        return [
            'campaigns' => $paginatedCampaigns,
            'campaignDiagnostics' => $campaignDiagnostics,
            'placementConfigs' => $placementConfigs,
            'analyticsReport' => $analyticsReport,
            'enterpriseStats' => [
                'total_campaigns' => $allCampaigns->count() + $legacyTotal,
                'enterprise_campaigns' => $allCampaigns->count(),
                'active_campaigns' => $allCampaigns->where('status', AdCampaign::STATUS_ACTIVE)->count() + $legacyActive,
                'paused_campaigns' => $allCampaigns->where('status', AdCampaign::STATUS_PAUSED)->count(),
                'scheduled_campaigns' => $allCampaigns->where('status', AdCampaign::STATUS_SCHEDULED)->count(),
                'draft_campaigns' => $allCampaigns->where('status', AdCampaign::STATUS_DRAFT)->count(),
                'expired_campaigns' => $allCampaigns->where('status', AdCampaign::STATUS_EXPIRED)->count(),
                'total_impressions' => $totalImpressions,
                'total_clicks' => $totalClicks,
                'total_conversions' => $totalConversions,
                'global_ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0.0,
                'conversion_revenue_usd' => ((int) $allCampaigns->sum('total_conversion_revenue_cents')) / 100,
                'pipeline_value_usd' => ((int) $allCampaigns->where('status', AdCampaign::STATUS_ACTIVE)->sum('price_cents')) / 100,
            ],
            'targetingOptions' => [
                'countries' => Schema::hasTable('countries')
                    ? Country::where('is_active', true)->orderBy('display_order')->orderBy('name')->get(['id', 'name', 'code', 'slug'])
                    : collect(),
                'categories' => Schema::hasTable('categories')
                    ? Category::where('is_active', true)->orderBy('display_order')->orderBy('name')->get(['id', 'name', 'parent_id'])
                    : collect(),
                'states' => Schema::hasTable('states')
                    ? State::with('country:id,code')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'country_id'])
                    : collect(),
                'cities' => Schema::hasTable('cities')
                    ? City::with('state:id,code')->orderByDesc('is_popular')->orderBy('name')->take(120)->get(['id', 'name', 'state_id'])
                    : collect(),
            ],
        ];
    }
}

