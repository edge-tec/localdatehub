{{-- Enterprise Advertising Performance Analytics Ledger --}}
@php
    $sum = $analyticsReport['summary'] ?? [];
    $daily = $analyticsReport['daily_breakdown'] ?? collect();
    $bySlot = $analyticsReport['placement_breakdown'] ?? collect();
    $byDevice = $analyticsReport['device_breakdown'] ?? collect();
    $byGeo = $analyticsReport['geo_breakdown'] ?? collect();
    $topCamps = $analyticsReport['top_campaigns'] ?? collect();
    $bottomCamps = $analyticsReport['bottom_campaigns'] ?? collect();
@endphp

<div class="space-y-5 mb-6">
    <!-- Date Range Filter Bar -->
    <div class="{{ $card }} p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <div>
            <h4 class="text-sm font-black text-slate-950">Enterprise Campaign Analytics &amp; Conversion Attribution</h4>
            <p class="text-xs text-slate-600 font-medium">
                Showing verified metrics from <strong class="text-slate-900">{{ $analyticsReport['start_date'] ?? '' }}</strong> to <strong class="text-slate-900">{{ $analyticsReport['end_date'] ?? '' }}</strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @foreach([
                'today' => 'Today',
                'yesterday' => 'Yesterday',
                'last_7_days' => 'Last 7 Days',
                'last_30_days' => 'Last 30 Days',
                'custom' => 'Custom Range',
            ] as $presetKey => $presetLabel)
                <button
                    type="button"
                    wire:click="$set('adAnalyticsRange', '{{ $presetKey }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer {{ $adAnalyticsRange === $presetKey ? 'bg-[#F20B83] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-800' }}"
                >
                    {{ $presetLabel }}
                </button>
            @endforeach

            @if($adAnalyticsRange === 'custom')
                <div class="flex items-center gap-1.5 ml-1">
                    <input type="date" wire:model.live="adAnalyticsCustomStart" class="{{ $input }} py-1 text-xs font-bold w-auto" />
                    <span class="text-xs font-bold text-slate-500">to</span>
                    <input type="date" wire:model.live="adAnalyticsCustomEnd" class="{{ $input }} py-1 text-xs font-bold w-auto" />
                </div>
            @endif
        </div>
    </div>

    <!-- Period KPI Ribbon -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="{{ $card }} p-4">
            <p class="text-[11px] font-black uppercase text-slate-500">Period Impressions</p>
            <div class="text-2xl font-black text-slate-950 mt-1">{{ number_format($sum['impressions'] ?? 0) }}</div>
            <p class="text-[11px] font-semibold text-slate-600 mt-0.5">{{ number_format($sum['unique_impressions'] ?? 0) }} unique views</p>
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-[11px] font-black uppercase text-slate-500">Period Clicks</p>
            <div class="text-2xl font-black text-slate-950 mt-1">{{ number_format($sum['clicks'] ?? 0) }}</div>
            <p class="text-[11px] font-semibold text-slate-600 mt-0.5">{{ number_format($sum['unique_clicks'] ?? 0) }} unique clicks</p>
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-[11px] font-black uppercase text-slate-500">Average CTR</p>
            <div class="text-2xl font-black text-[#F20B83] mt-1">{{ $sum['ctr'] ?? 0 }}%</div>
            <p class="text-[11px] font-semibold text-slate-600 mt-0.5">Verified human traffic</p>
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-[11px] font-black uppercase text-slate-500">S2S Conversions</p>
            <div class="text-2xl font-black text-emerald-700 mt-1">{{ number_format($sum['conversions'] ?? 0) }}</div>
            <p class="text-[11px] font-semibold text-slate-600 mt-0.5">{{ $sum['conversion_rate'] ?? 0 }}% Conv. Rate</p>
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-[11px] font-black uppercase text-slate-500">Attributed Revenue</p>
            <div class="text-2xl font-black text-slate-950 mt-1">${{ number_format($sum['revenue_usd'] ?? 0, 2) }}</div>
            <p class="text-[11px] font-semibold text-slate-600 mt-0.5">Ad Spend: ${{ number_format($sum['spend_usd'] ?? 0, 2) }}</p>
        </div>
    </div>

    <!-- Top vs Bottom Performing Campaigns -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="{{ $card }} p-5">
            <h5 class="text-sm font-black text-slate-950 pb-2.5 border-b border-slate-200 mb-3">Top Performing Campaigns</h5>
            <div class="space-y-2.5">
                @forelse($topCamps as $idx => $row)
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-black text-slate-950 truncate">#{{ $idx + 1 }} · {{ $row->campaign_name }}</p>
                            <p class="text-[11px] text-slate-600 font-semibold">{{ ucfirst($row->campaign_type) }} · {{ number_format($row->impressions) }} impr · {{ number_format($row->clicks) }} clicks · {{ number_format($row->conversions) }} conv</p>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-900 border border-emerald-300 text-xs font-black shrink-0">{{ $row->ctr }}% CTR</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 py-4 text-center">No campaign analytics data recorded in this range.</p>
                @endforelse
            </div>
        </div>

        <div class="{{ $card }} p-5">
            <h5 class="text-sm font-black text-slate-950 pb-2.5 border-b border-slate-200 mb-3">Lowest CTR Campaigns (Optimization Candidates)</h5>
            <div class="space-y-2.5">
                @forelse($bottomCamps as $row)
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-black text-slate-950 truncate">{{ $row->campaign_name }}</p>
                            <p class="text-[11px] text-slate-600 font-semibold">{{ number_format($row->impressions) }} impr · {{ number_format($row->clicks) }} clicks</p>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 border border-amber-300 text-xs font-black shrink-0">{{ $row->ctr }}% CTR</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 py-4 text-center">No underperforming campaign records in this range.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Placement, Device, and Geo Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="{{ $card }} p-5">
            <h5 class="text-sm font-black text-slate-950 pb-2 border-b border-slate-200 mb-3">Placement Breakdown</h5>
            <div class="space-y-2 text-xs">
                @forelse($bySlot as $slotRow)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200">
                        <div class="min-w-0 pr-2">
                            <div class="font-bold text-slate-900 truncate">{{ $slotRow->label }}</div>
                            <div class="text-[10px] text-slate-500">{{ number_format($slotRow->impressions) }} impr · {{ number_format($slotRow->clicks) }} clicks</div>
                        </div>
                        <span class="font-black text-[#F20B83] shrink-0">{{ $slotRow->ctr }}%</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No placement impressions yet.</p>
                @endforelse
            </div>
        </div>

        <div class="{{ $card }} p-5">
            <h5 class="text-sm font-black text-slate-950 pb-2 border-b border-slate-200 mb-3">Device Breakdown</h5>
            <div class="space-y-2 text-xs">
                @forelse($byDevice as $devRow)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200">
                        <div>
                            <span class="font-bold text-slate-900 uppercase">{{ $devRow->device_type }}</span>
                            <div class="text-[10px] text-slate-500">{{ number_format($devRow->impressions) }} impr · {{ number_format($devRow->clicks) }} clicks</div>
                        </div>
                        <span class="font-black text-slate-900">{{ $devRow->ctr }}% CTR</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No device telemetry yet.</p>
                @endforelse
            </div>
        </div>

        <div class="{{ $card }} p-5">
            <h5 class="text-sm font-black text-slate-950 pb-2 border-b border-slate-200 mb-3">Top Geo Locations (State)</h5>
            <div class="space-y-2 text-xs">
                @forelse($byGeo as $geoRow)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-200">
                        <div>
                            <span class="font-black text-slate-900">{{ $geoRow->state_code }}</span>
                            <div class="text-[10px] text-slate-500">{{ number_format($geoRow->impressions) }} impr · {{ number_format($geoRow->clicks) }} clicks</div>
                        </div>
                        <span class="font-black text-emerald-700">{{ $geoRow->ctr }}% CTR</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No state-tagged traffic recorded in range.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Daily Breakdown Table -->
    <div class="{{ $card }} overflow-x-auto">
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
            <h5 class="text-sm font-black text-slate-950">Daily Delivery Breakdown</h5>
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-xs">
            <thead class="bg-slate-50">
                <tr>
                    <th class="{{ $th }}">Date</th>
                    <th class="{{ $th }}">Impressions</th>
                    <th class="{{ $th }}">Unique Views</th>
                    <th class="{{ $th }}">Clicks</th>
                    <th class="{{ $th }}">CTR</th>
                    <th class="{{ $th }}">Conversions</th>
                    <th class="{{ $th }} text-right pr-4">Revenue</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($daily as $dayRow)
                    <tr>
                        <td class="{{ $td }} font-bold text-slate-900">{{ \Illuminate\Support\Carbon::parse($dayRow->date)->format('M j, Y') }}</td>
                        <td class="{{ $td }} font-mono">{{ number_format($dayRow->impressions) }}</td>
                        <td class="{{ $td }} font-mono">{{ number_format($dayRow->unique_impressions) }}</td>
                        <td class="{{ $td }} font-mono">{{ number_format($dayRow->clicks) }}</td>
                        <td class="{{ $td }} font-black text-[#F20B83]">{{ $dayRow->ctr }}%</td>
                        <td class="{{ $td }} font-mono font-bold text-emerald-700">{{ number_format($dayRow->conversions) }}</td>
                        <td class="{{ $td }} text-right pr-4 font-black text-slate-900">${{ number_format(($dayRow->revenue_cents ?? 0) / 100, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-6 text-center text-slate-500">No daily ad events recorded during the selected date window.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

