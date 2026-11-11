<?php

namespace App\Modules\Advertising\Services;

use App\Modules\Admin\Models\AdPlacement;
use App\Modules\Advertising\Models\AdCampaign;
use App\Modules\Advertising\Models\AdDailyStatistic;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AdAnalyticsService
{
    /**
     * Resolve start & end carbon bounds from dashboard date filters.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    public function resolveDateRange(string $preset = 'last_7_days', ?string $customStart = null, ?string $customEnd = null): array
    {
        return match ($preset) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'yesterday' => [
                'start' => now()->subDay()->startOfDay(),
                'end' => now()->subDay()->endOfDay(),
            ],
            'last_30_days' => [
                'start' => now()->subDays(29)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'custom' => [
                'start' => $customStart ? Carbon::parse($customStart)->startOfDay() : now()->subDays(6)->startOfDay(),
                'end' => $customEnd ? Carbon::parse($customEnd)->endOfDay() : now()->endOfDay(),
            ],
            default => [
                'start' => now()->subDays(6)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
        };
    }

    /**
     * Build complete analytics ledger for Admin Advertising Analytics sub-tab.
     *
     * @return array<string, mixed>
     */
    public function getReport(string $preset = 'last_7_days', ?string $customStart = null, ?string $customEnd = null): array
    {
        $range = $this->resolveDateRange($preset, $customStart, $customEnd);
        $startDate = $range['start']->toDateString();
        $endDate = $range['end']->toDateString();

        if (!Schema::hasTable('ad_daily_statistics') || !Schema::hasTable('ad_campaigns')) {
            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'summary' => [
                    'impressions' => 0,
                    'unique_impressions' => 0,
                    'clicks' => 0,
                    'unique_clicks' => 0,
                    'conversions' => 0,
                    'ctr' => 0.0,
                    'conversion_rate' => 0.0,
                    'revenue_usd' => 0.0,
                    'spend_usd' => 0.0,
                ],
                'daily_breakdown' => collect(),
                'placement_breakdown' => collect(),
                'device_breakdown' => collect(),
                'geo_breakdown' => collect(),
                'top_campaigns' => collect(),
                'bottom_campaigns' => collect(),
            ];
        }

        $baseQuery = AdDailyStatistic::query()
            ->whereBetween('date', [$startDate, $endDate]);

        $totals = (clone $baseQuery)
            ->selectRaw('COALESCE(SUM(impressions), 0) as impr, COALESCE(SUM(unique_impressions), 0) as u_impr, COALESCE(SUM(clicks), 0) as clk, COALESCE(SUM(unique_clicks), 0) as u_clk, COALESCE(SUM(conversions), 0) as conv, COALESCE(SUM(revenue_cents), 0) as rev, COALESCE(SUM(spend_cents), 0) as spnd')
            ->first();

        $impressions = (int) ($totals->impr ?? 0);
        $clicks = (int) ($totals->clk ?? 0);
        $conversions = (int) ($totals->conv ?? 0);

        $dailyBreakdown = (clone $baseQuery)
            ->selectRaw('date, SUM(impressions) as impressions, SUM(unique_impressions) as unique_impressions, SUM(clicks) as clicks, SUM(unique_clicks) as unique_clicks, SUM(conversions) as conversions, SUM(revenue_cents) as revenue_cents')
            ->groupBy('date')
            ->orderByDesc('date')
            ->get()
            ->map(function ($row) {
                $i = (int) $row->impressions;
                $c = (int) $row->clicks;
                $row->ctr = $i > 0 ? round(($c / $i) * 100, 2) : 0.0;

                return $row;
            });

        $placementBreakdown = (clone $baseQuery)
            ->selectRaw('placement_slot, SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(conversions) as conversions, SUM(revenue_cents) as revenue_cents')
            ->groupBy('placement_slot')
            ->orderByDesc('impressions')
            ->get()
            ->map(function ($row) {
                $i = (int) $row->impressions;
                $c = (int) $row->clicks;
                $row->label = AdCampaign::SLOT_CATALOG[$row->placement_slot]['label'] ?? ucwords(str_replace('_', ' ', (string) $row->placement_slot));
                $row->ctr = $i > 0 ? round(($c / $i) * 100, 2) : 0.0;

                return $row;
            });

        $deviceBreakdown = (clone $baseQuery)
            ->selectRaw('device_type, SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(conversions) as conversions')
            ->groupBy('device_type')
            ->orderByDesc('impressions')
            ->get()
            ->map(function ($row) {
                $i = (int) $row->impressions;
                $c = (int) $row->clicks;
                $row->ctr = $i > 0 ? round(($c / $i) * 100, 2) : 0.0;

                return $row;
            });

        $geoBreakdown = (clone $baseQuery)
            ->where('state_code', '!=', '')
            ->selectRaw('state_code, SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(conversions) as conversions')
            ->groupBy('state_code')
            ->orderByDesc('impressions')
            ->take(10)
            ->get()
            ->map(function ($row) {
                $i = (int) $row->impressions;
                $c = (int) $row->clicks;
                $row->ctr = $i > 0 ? round(($c / $i) * 100, 2) : 0.0;

                return $row;
            });

        $campaignStats = (clone $baseQuery)
            ->selectRaw('campaign_id, SUM(impressions) as impressions, SUM(clicks) as clicks, SUM(conversions) as conversions, SUM(revenue_cents) as revenue_cents')
            ->groupBy('campaign_id')
            ->with('campaign')
            ->get()
            ->filter(fn ($r) => $r->campaign !== null)
            ->map(function ($row) {
                $i = (int) $row->impressions;
                $c = (int) $row->clicks;
                $row->ctr = $i > 0 ? round(($c / $i) * 100, 2) : 0.0;
                $row->campaign_name = $row->campaign->name;
                $row->campaign_type = $row->campaign->campaign_type;
                $row->status = $row->campaign->status;

                return $row;
            });

        $topCampaigns = $campaignStats->sortByDesc('ctr')->sortByDesc('clicks')->take(5)->values();
        $bottomCampaigns = $campaignStats->where('impressions', '>', 0)->sortBy('ctr')->take(5)->values();

        // Include legacy AdPlacement metrics only when daily ledger is zero so legacy overview remains visible
        $legacyImpr = Schema::hasTable('ad_placements') ? (int) AdPlacement::sum('impressions') : 0;
        $legacyClicks = Schema::hasTable('ad_placements') ? (int) AdPlacement::sum('clicks') : 0;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'summary' => [
                'impressions' => $impressions,
                'unique_impressions' => (int) ($totals->u_impr ?? 0),
                'clicks' => $clicks,
                'unique_clicks' => (int) ($totals->u_clk ?? 0),
                'conversions' => $conversions,
                'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0,
                'conversion_rate' => $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0.0,
                'revenue_usd' => ((int) ($totals->rev ?? 0)) / 100,
                'spend_usd' => ((int) ($totals->spnd ?? 0)) / 100,
                'legacy_impressions' => $legacyImpr,
                'legacy_clicks' => $legacyClicks,
            ],
            'daily_breakdown' => $dailyBreakdown,
            'placement_breakdown' => $placementBreakdown,
            'device_breakdown' => $deviceBreakdown,
            'geo_breakdown' => $geoBreakdown,
            'top_campaigns' => $topCampaigns,
            'bottom_campaigns' => $bottomCampaigns,
        ];
    }
}

