{{-- Enterprise Advertising Performance Analytics Ledger --}}
@php
    $sum = $analyticsReport['summary'] ?? [];
    $daily = $analyticsReport['daily_breakdown'] ?? collect();
    $bySlot = $analyticsReport['placement_breakdown'] ?? collect();
    $byDevice = $analyticsReport['device_breakdown'] ?? collect();
    $byGeo = $analyticsReport['geo_breakdown'] ?? collect();
    $topCamps = $analyticsReport['top_campaigns'] ?? collect();
    $bottomCamps = $analyticsReport['bottom_campaigns'] ?? collect();

    $maxDailyImpr = max(1, (int) $daily->max('impressions'));
    $maxSlotImpr = max(1, (int) $bySlot->max('impressions'));
    $maxDeviceImpr = max(1, (int) $byDevice->max('impressions'));
    $maxGeoImpr = max(1, (int) $byGeo->max('impressions'));
@endphp

<div class="space-y-5">
    <!-- Date Range Filter & Export Bar -->
    <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col xl:flex-row xl:items-center justify-between gap-4">
        <div class="space-y-0.5">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-pink-50 border border-pink-200 text-[#F20B83] flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
                <h4 class="text-sm sm:text-base font-black text-slate-950">Enterprise Campaign Analytics &amp; Attribution</h4>
            </div>
            <p class="text-xs text-slate-600 font-medium">
                Verified delivery telemetry from <strong class="text-slate-900">{{ $analyticsReport['start_date'] ?? '' }}</strong> to <strong class="text-slate-900">{{ $analyticsReport['end_date'] ?? '' }}</strong>
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
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $adAnalyticsRange === $presetKey ? 'bg-[#F20B83] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-800' }}"
                >
                    {{ $presetLabel }}
                </button>
            @endforeach

            @if($adAnalyticsRange === 'custom')
                <div class="flex flex-wrap items-center gap-1.5">
                    <input type="date" wire:model.live="adAnalyticsCustomStart" class="rounded-xl bg-slate-50 border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-900" />
                    <span class="text-xs font-bold text-slate-500">to</span>
                    <input type="date" wire:model.live="adAnalyticsCustomEnd" class="rounded-xl bg-slate-50 border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-900" />
                </div>
            @endif

            <button
                type="button"
                wire:click="exportAdAnalyticsCsv"
                class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black transition inline-flex items-center gap-1.5 cursor-pointer"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Export CSV</span>
            </button>
        </div>
    </div>

    <!-- Period KPI Ribbon (5 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-black uppercase text-slate-500">Impressions</p>
                <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </div>
            <div class="text-2xl font-black text-slate-950 mt-1">{{ number_format($sum['impressions'] ?? 0) }}</div>
            <p class="text-[11px] font-semibold text-slate-500 mt-0.5">{{ number_format($sum['unique_impressions'] ?? 0) }} unique views</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-black uppercase text-slate-500">Clicks</p>
                <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/></svg>
            </div>
            <div class="text-2xl font-black text-slate-950 mt-1">{{ number_format($sum['clicks'] ?? 0) }}</div>
            <p class="text-[11px] font-semibold text-slate-500 mt-0.5">{{ number_format($sum['unique_clicks'] ?? 0) }} unique clicks</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-black uppercase text-slate-500">Average CTR</p>
                <svg class="w-4 h-4 text-[#F20B83]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div class="text-2xl font-black text-[#F20B83] mt-1">{{ $sum['ctr'] ?? 0 }}%</div>
            <p class="text-[11px] font-semibold text-slate-500 mt-0.5">Verified human traffic</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-black uppercase text-slate-500">S2S Conversions</p>
                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="text-2xl font-black text-emerald-700 mt-1">{{ number_format($sum['conversions'] ?? 0) }}</div>
            <p class="text-[11px] font-semibold text-slate-500 mt-0.5">{{ $sum['conversion_rate'] ?? 0 }}% Conv. Rate</p>
        </div>
        <div class="col-span-2 sm:col-span-1 bg-white border border-slate-200 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-black uppercase text-slate-500">Revenue</p>
                <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="text-2xl font-black text-slate-950 mt-1">${{ number_format($sum['revenue_usd'] ?? 0, 2) }}</div>
            <p class="text-[11px] font-semibold text-slate-500 mt-0.5">Spend: ${{ number_format($sum['spend_usd'] ?? 0, 2) }}</p>
        </div>
    </div>

    <!-- Daily Delivery Trend Visual Bar Graph -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <div>
                <h5 class="text-sm font-black text-slate-950">Daily Delivery &amp; Click Trend</h5>
                <p class="text-xs text-slate-500 font-medium">Relative daily volume of ad impressions and click-throughs across the selected period.</p>
            </div>
            <div class="flex items-center gap-3 text-[11px] font-bold">
                <span class="inline-flex items-center gap-1.5 text-slate-700"><span class="w-2.5 h-2.5 rounded-sm bg-blue-500"></span>Impressions</span>
                <span class="inline-flex items-center gap-1.5 text-slate-700"><span class="w-2.5 h-2.5 rounded-sm bg-[#F20B83]"></span>Clicks</span>
            </div>
        </div>
        @if($daily->isNotEmpty())
            <div class="grid grid-cols-{{ min(14, max(4, $daily->count())) }} gap-2 items-end h-36 pt-4 border-b border-slate-200 pb-2 overflow-x-auto">
                @foreach($daily->take(14) as $d)
                    @php
                        $imprPct = max(6, (int) round(((int) $d->impressions / $maxDailyImpr) * 100));
                        $clkPct = (int) $d->impressions > 0 ? max(4, min(100, (int) round(((int) $d->clicks / $maxDailyImpr) * 400))) : 0;
                    @endphp
                    <div class="flex flex-col items-center gap-1 h-full justify-end group min-w-[36px]" title="{{ $d->date }}: {{ number_format($d->impressions) }} impr / {{ number_format($d->clicks) }} clicks ({{ $d->ctr }}% CTR)">
                        <div class="w-full flex items-end justify-center gap-1 h-24">
                            <div class="w-2.5 sm:w-3 rounded-t-md bg-blue-500/85 group-hover:bg-blue-600 transition-all" style="height: {{ $imprPct }}%"></div>
                            <div class="w-2 sm:w-2.5 rounded-t-md bg-[#F20B83] group-hover:bg-[#D00972] transition-all" style="height: {{ max(4, $clkPct) }}%"></div>
                        </div>
                        <span class="text-[9px] font-bold text-slate-500 truncate">{{ \Illuminate\Support\Carbon::parse($d->date)->format('M j') }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-8 text-center text-xs text-slate-500 bg-slate-50 rounded-xl border border-slate-200">
                No delivery events recorded in this date window yet.
            </div>
        @endif
    </div>

    <!-- Top vs Bottom Performing Campaigns -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-3">
                <h5 class="text-sm font-black text-slate-950">Top Performing Campaigns</h5>
                <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] font-black uppercase">Best CTR</span>
            </div>
            <div class="space-y-2.5">
                @forelse($topCamps as $idx => $row)
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-6 h-6 rounded-full bg-slate-900 text-white font-black text-[10px] flex items-center justify-center shrink-0">#{{ $idx + 1 }}</span>
                            <div class="min-w-0">
                                <p class="text-xs font-black text-slate-950 truncate">{{ $row->campaign_name }}</p>
                                <p class="text-[11px] text-slate-600 font-semibold">{{ ucfirst($row->campaign_type) }} · {{ number_format($row->impressions) }} impr · {{ number_format($row->clicks) }} clicks · {{ number_format($row->conversions) }} conv</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-300 text-xs font-black shrink-0">{{ $row->ctr }}% CTR</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 py-5 text-center">No campaign analytics data recorded in this range.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-3">
                <h5 class="text-sm font-black text-slate-950">Optimization Candidates</h5>
                <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-black uppercase">Lowest CTR</span>
            </div>
            <div class="space-y-2.5">
                @forelse($bottomCamps as $row)
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-black text-slate-950 truncate">{{ $row->campaign_name }}</p>
                            <p class="text-[11px] text-slate-600 font-semibold">{{ number_format($row->impressions) }} impr · {{ number_format($row->clicks) }} clicks</p>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-300 text-xs font-black shrink-0">{{ $row->ctr }}% CTR</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 py-5 text-center">No underperforming campaign records in this range.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Placement, Device & Geo Breakdowns with Visual Progress Bars -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
            <h5 class="text-sm font-black text-slate-950 pb-2.5 border-b border-slate-200 mb-3">Placement Performance</h5>
            <div class="space-y-3 text-xs">
                @forelse($bySlot as $slotRow)
                    @php $pct = max(4, (int) round(((int) $slotRow->impressions / $maxSlotImpr) * 100)); @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-slate-900 truncate">{{ $slotRow->label }}</span>
                            <span class="font-black text-[#F20B83] shrink-0">{{ $slotRow->ctr }}% CTR</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full bg-[#F20B83] rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="text-[10px] text-slate-500">{{ number_format($slotRow->impressions) }} impr · {{ number_format($slotRow->clicks) }} clicks</div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No placement impressions yet.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
            <h5 class="text-sm font-black text-slate-950 pb-2.5 border-b border-slate-200 mb-3">Device Performance</h5>
            <div class="space-y-3 text-xs">
                @forelse($byDevice as $devRow)
                    @php $pct = max(4, (int) round(((int) $devRow->impressions / $maxDeviceImpr) * 100)); @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900 uppercase">{{ $devRow->device_type }}</span>
                            <span class="font-black text-slate-900">{{ $devRow->ctr }}% CTR</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full bg-blue-600 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="text-[10px] text-slate-500">{{ number_format($devRow->impressions) }} impr · {{ number_format($devRow->clicks) }} clicks</div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No device telemetry yet.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
            <h5 class="text-sm font-black text-slate-950 pb-2.5 border-b border-slate-200 mb-3">Geographic Performance (State)</h5>
            <div class="space-y-3 text-xs">
                @forelse($byGeo as $geoRow)
                    @php $pct = max(4, (int) round(((int) $geoRow->impressions / $maxGeoImpr) * 100)); @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-slate-900">{{ $geoRow->state_code }}</span>
                            <span class="font-black text-emerald-700">{{ $geoRow->ctr }}% CTR</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="text-[10px] text-slate-500">{{ number_format($geoRow->impressions) }} impr · {{ number_format($geoRow->clicks) }} clicks</div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 text-center py-4">No state-tagged traffic recorded in range.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Daily Delivery Breakdown Table -->
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
            <h5 class="text-sm font-black text-slate-950">Daily Delivery Breakdown</h5>
            <span class="text-[11px] font-bold text-slate-500">{{ $daily->count() }} {{ Str::plural('day', $daily->count()) }}</span>
        </div>
        <div class="overflow-x-auto">
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
                        <tr class="hover:bg-slate-50/70">
                            <td class="{{ $td }} font-bold text-slate-900 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($dayRow->date)->format('M j, Y') }}</td>
                            <td class="{{ $td }} font-mono">{{ number_format($dayRow->impressions) }}</td>
                            <td class="{{ $td }} font-mono">{{ number_format($dayRow->unique_impressions) }}</td>
                            <td class="{{ $td }} font-mono">{{ number_format($dayRow->clicks) }}</td>
                            <td class="{{ $td }} font-black text-[#F20B83]">{{ $dayRow->ctr }}%</td>
                            <td class="{{ $td }} font-mono font-bold text-emerald-700">{{ number_format($dayRow->conversions) }}</td>
                            <td class="{{ $td }} text-right pr-4 font-black text-slate-900">${{ number_format(($dayRow->revenue_cents ?? 0) / 100, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">No daily ad events recorded during the selected date window.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
