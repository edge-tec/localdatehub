{{-- Enterprise Analytics & Multi-Dimension Report Suite (Real data only, reloads deduplicated) --}}
@php
    $showUnique = in_array($analyticsViewMode, ['unique', 'both'], true);
    $showGross = in_array($analyticsViewMode, ['gross', 'both'], true);
    $showGraph = in_array($analyticsVisualMode, ['graph', 'both'], true);
    $showChart = in_array($analyticsVisualMode, ['chart', 'both'], true);
    $dim = $analyticsDimension;
    $show = fn (string $d) => $dim === 'all' || $dim === $d;
    $pill = 'px-3 py-1.5 rounded-full text-[11px] font-bold transition cursor-pointer border';
    $pillOn = 'bg-[#F20B65] text-white border-[#F20B65] shadow-xs';
    $pillOff = 'bg-white text-slate-600 border-slate-200 hover:border-[#F20B65] hover:text-[#F20B65]';
    $periodLabel = ['today' => 'Today', '7d' => 'Last 7 Days', '30d' => 'Last 30 Days', '90d' => 'Last 90 Days', 'all' => 'All Time'][$analyticsPeriod] ?? 'Last 30 Days';
    $donutColors = ['#F20B65', '#0D9488', '#8B35F6', '#F59E0B', '#3B82F6', '#10B981', '#EF4444', '#64748B'];
    $flag = function ($code) {
        $code = strtoupper((string) $code);
        if (strlen($code) !== 2 || ! ctype_alpha($code)) {
            return '🌐';
        }
        return mb_chr(0x1F1E6 + ord($code[0]) - 65) . mb_chr(0x1F1E6 + ord($code[1]) - 65);
    };
@endphp

<div wire:poll.15s class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900">Analytics &amp; Reports</h1>
            <p class="text-xs text-slate-500 mt-0.5">Real visitor traffic only — admin visits, bots &amp; page reloads (30 min) are excluded. Period: <strong>{{ $periodLabel }}</strong></p>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 shadow-2xs w-max">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span>Live · Auto-updating (15s)</span>
            </div>
            <button type="button" wire:click="resetAnalyticsFilters" class="px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">↺ Reset Filters</button>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="{{ $card }} p-4 space-y-4">
        <div>
            <div class="text-[10px] uppercase tracking-wider text-slate-400 font-black mb-2">Report Type</div>
            <div class="flex flex-wrap gap-2">
                @foreach([
                    'all' => '📋 All Reports',
                    'visitors' => '👥 Visitors',
                    'posts' => '📝 Posts & Post Views',
                    'countries' => '🌍 Countries',
                    'devices' => '📱 Devices',
                    'age' => '🎂 Age Groups',
                ] as $key => $label)
                    <button type="button" wire:click="setAnalyticsDimension('{{ $key }}')" class="{{ $pill }} {{ $dim === $key ? $pillOn : $pillOff }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <label class="block">
                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-black">Period</span>
                <select wire:model.live="analyticsPeriod" class="{{ $input }} mt-1">
                    <option value="today">Today</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                    <option value="90d">Last 90 Days</option>
                    <option value="all">All Time</option>
                </select>
            </label>
            <label class="block">
                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-black">Country</span>
                <select wire:model.live="analyticsCountryFilter" class="{{ $input }} mt-1">
                    <option value="all">All Countries</option>
                    @foreach($availableCountries as $c)
                        @if($c->country_code)
                            <option value="{{ $c->country_code }}">{{ $flag($c->country_code) }} {{ $c->country_name ?: $c->country_code }}</option>
                        @endif
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-black">Device</span>
                <select wire:model.live="analyticsDeviceFilter" class="{{ $input }} mt-1">
                    <option value="all">All Devices</option>
                    <option value="Desktop">🖥 Desktop</option>
                    <option value="Mobile">📱 Mobile</option>
                    <option value="Tablet">📲 Tablet</option>
                </select>
            </label>
            <label class="block">
                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-black">Age Group</span>
                <select wire:model.live="analyticsAgeFilter" class="{{ $input }} mt-1">
                    <option value="all">All Ages</option>
                    <option value="18-24">18-24</option>
                    <option value="25-34">25-34</option>
                    <option value="35-44">35-44</option>
                    <option value="45-54">45-54</option>
                    <option value="55+">55+</option>
                </select>
            </label>
            <div>
                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-black">Views</span>
                <div class="mt-1 inline-flex w-full rounded-xl bg-slate-100 p-0.5 border border-slate-200 text-[11px] font-bold">
                    @foreach(['unique' => 'Unique', 'gross' => 'Gross', 'both' => 'Both'] as $k => $l)
                        <button type="button" wire:click="setAnalyticsViewMode('{{ $k }}')" class="flex-1 px-2 py-1.5 rounded-lg transition {{ $analyticsViewMode === $k ? 'bg-teal-600 text-white' : 'text-slate-600 hover:text-slate-900' }}">{{ $l }}</button>
                    @endforeach
                </div>
            </div>
            <div>
                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-black">Display</span>
                <div class="mt-1 inline-flex w-full rounded-xl bg-slate-100 p-0.5 border border-slate-200 text-[11px] font-bold">
                    @foreach(['graph' => '📈 Graph', 'chart' => '📊 Chart', 'both' => '⚡ Both'] as $k => $l)
                        <button type="button" wire:click="setAnalyticsVisualMode('{{ $k }}')" class="flex-1 px-2 py-1.5 rounded-lg transition {{ $analyticsVisualMode === $k ? 'bg-[#F20B65] text-white' : 'text-slate-600 hover:text-slate-900' }}">{{ $l }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @php
            $kpis = [];
            if ($showUnique) { $kpis[] = ['👤 Unique Visitors', number_format($analytics['uniqueVisitors']), 'text-teal-700']; }
            if ($showGross) { $kpis[] = ['👁 Gross Views', number_format($analytics['grossViews']), 'text-[#F20B65]']; }
            if ($showUnique) { $kpis[] = ['📝 Post Unique Views', number_format($analytics['postUniqueViews']), 'text-teal-700']; }
            if ($showGross) { $kpis[] = ['📝 Post Gross Views', number_format($analytics['postGrossViews']), 'text-[#F20B65]']; }
            $kpis[] = ['🔁 Repeat Views', number_format($analytics['repeatViews']), 'text-slate-900'];
            $kpis[] = ['✅ Active Posts', number_format($analytics['totalActivePosts']), 'text-slate-900'];
            $kpis[] = ['🌍 Countries Reached', number_format($analytics['countriesCount']), 'text-slate-900'];
            $kpis[] = ['✉️ Inquiries', number_format($analytics['contacts30']), 'text-purple-700'];
            $kpis[] = ['⭐ Favorites', number_format($analytics['favorites30']), 'text-slate-900'];
            $kpis[] = ['🆕 New Users', number_format($analytics['newUsers30']), 'text-slate-900'];
            $kpis[] = ['🆕 New Posts', number_format($analytics['newListings30']), 'text-slate-900'];
            $kpis[] = ['💰 Revenue', '$' . number_format($analytics['revenue30'], 2), 'text-amber-700'];
        @endphp
        @foreach($kpis as [$label, $value, $color])
            <div class="{{ $card }} p-4">
                <div class="text-[11px] uppercase tracking-wider text-slate-400 font-bold">{{ $label }}</div>
                <div class="mt-1 text-xl font-black {{ $color }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    {{-- 1. Visitors & Traffic Trend --}}
    @if($show('visitors'))
        <div class="{{ $card }} p-5">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900">👥 Visitors &amp; Traffic Trend</h2>
                    <p class="text-[11px] text-slate-500">Daily unique visitors vs gross views vs post views</p>
                </div>
                <div class="flex flex-wrap gap-3 text-[11px] font-semibold text-slate-600">
                    @if($showUnique)<span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-teal-600"></span>Unique ({{ number_format(array_sum($trendChart['unique'])) }})</span>@endif
                    @if($showGross)<span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#F20B65]"></span>Gross ({{ number_format(array_sum($trendChart['gross'])) }})</span>@endif
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#8B35F6]"></span>Post Views ({{ number_format(array_sum($trendChart['postViews'])) }})</span>
                </div>
            </div>

            <div class="grid gap-6 {{ $showGraph && $showChart ? 'lg:grid-cols-2' : '' }}">
                @if($showGraph)
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-400 font-black mb-2">📈 Line / Area Graph</div>
                        <div class="h-56 w-full">
                            <svg viewBox="0 0 600 160" preserveAspectRatio="none" class="w-full h-full overflow-visible">
                                <defs>
                                    <linearGradient id="anGross" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#F20B65" stop-opacity="0.25"/><stop offset="100%" stop-color="#F20B65" stop-opacity="0"/></linearGradient>
                                    <linearGradient id="anUnique" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#0D9488" stop-opacity="0.25"/><stop offset="100%" stop-color="#0D9488" stop-opacity="0"/></linearGradient>
                                </defs>
                                <line x1="0" y1="30" x2="600" y2="30" stroke="#E2E8F0" stroke-dasharray="3,3"/>
                                <line x1="0" y1="88" x2="600" y2="88" stroke="#E2E8F0" stroke-dasharray="3,3"/>
                                <line x1="0" y1="145" x2="600" y2="145" stroke="#E2E8F0"/>
                                @if($showGross)
                                    <path d="{{ $trendChart['grossAreaD'] }}" fill="url(#anGross)"/>
                                    <path d="{{ $trendChart['grossLineD'] }}" fill="none" stroke="#F20B65" stroke-width="2.5" vector-effect="non-scaling-stroke"/>
                                @endif
                                @if($showUnique)
                                    <path d="{{ $trendChart['uniqueAreaD'] }}" fill="url(#anUnique)"/>
                                    <path d="{{ $trendChart['uniqueLineD'] }}" fill="none" stroke="#0D9488" stroke-width="2.5" vector-effect="non-scaling-stroke"/>
                                @endif
                                <path d="{{ $trendChart['postLineD'] }}" fill="none" stroke="#8B35F6" stroke-width="2" stroke-dasharray="5,3" vector-effect="non-scaling-stroke"/>
                            </svg>
                        </div>
                        <div class="flex justify-between text-[9px] font-bold text-slate-400 mt-1">
                            @foreach($trendChart['labels'] as $i => $lbl)
                                <span class="{{ count($trendChart['labels']) > 7 && $i % 2 ? 'hidden sm:inline' : '' }}">{{ $lbl }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($showChart)
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-400 font-black mb-2">📊 Column Bar Chart</div>
                        <div class="h-56 flex items-end gap-1.5">
                            @foreach($trendChart['bars'] as $b)
                                <div class="flex-1 h-full flex flex-col justify-end items-center group relative">
                                    <div class="absolute -top-1 left-1/2 -translate-x-1/2 hidden group-hover:block z-10 whitespace-nowrap bg-slate-900 text-white text-[10px] font-bold px-2 py-1 rounded-md shadow">
                                        {{ $b['label'] }}: {{ $b['unique'] }} unique · {{ $b['gross'] }} gross · {{ $b['postViews'] }} post
                                    </div>
                                    <div class="w-full h-full flex items-end justify-center gap-0.5">
                                        @if($showUnique)<div class="flex-1 max-w-[10px] rounded-t bg-teal-600" style="height: {{ $b['uniquePct'] }}%"></div>@endif
                                        @if($showGross)<div class="flex-1 max-w-[10px] rounded-t bg-[#F20B65]" style="height: {{ $b['grossPct'] }}%"></div>@endif
                                        <div class="flex-1 max-w-[10px] rounded-t bg-[#8B35F6]" style="height: {{ $b['postPct'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex gap-1.5 text-[9px] font-bold text-slate-400 mt-1">
                            @foreach($trendChart['bars'] as $i => $b)
                                <span class="flex-1 text-center truncate {{ count($trendChart['bars']) > 7 && $i % 2 ? 'invisible sm:visible' : '' }}">{{ $b['label'] }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- 2. Posts & Post Views --}}
    @if($show('posts'))
        <div class="space-y-4">
            <!-- Top Summary Visuals: Category Distribution & Status Donut -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($showChart)
                    <div class="{{ $card }} p-5 flex flex-col justify-between">
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900 mb-3">📊 Post Views by Category</h2>
                            <div class="space-y-2.5">
                                @forelse($topCategories as $c)
                                    @php $catPct = (int) round(((int) $c->total_views / max(1, $maxCatViews)) * 100); @endphp
                                    <div>
                                        <div class="flex justify-between text-[11px] font-semibold text-slate-700">
                                            <span class="truncate font-bold">{{ $c->name }}</span>
                                            <span class="text-slate-500 shrink-0">{{ number_format((int) $c->total_views) }} views · {{ $c->listings_count }} posts</span>
                                        </div>
                                        <div class="h-2 rounded-full bg-slate-100 mt-1 overflow-hidden">
                                            <div class="h-2 rounded-full bg-[#F20B65] transition-all duration-300" style="width: {{ max(2, $catPct) }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400">No categories found.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif

                @if($showGraph)
                    @php
                        $statusTotal = max(1, (int) collect($listingsByStatus)->sum());
                        $offset = 0;
                    @endphp
                    <div class="{{ $card }} p-5 flex flex-col justify-between">
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900 mb-3">🍩 Posts by Status</h2>
                            <div class="flex flex-col sm:flex-row items-center justify-center gap-6 my-auto py-2">
                                <div class="relative w-32 h-32 sm:w-36 sm:h-36 shrink-0 flex items-center justify-center">
                                    <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                                        <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#F1F5F9" stroke-width="4.5"/>
                                        @foreach($listingsByStatus as $status => $total)
                                            @php $p = round(($total / $statusTotal) * 100, 2); @endphp
                                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="{{ $donutColors[$loop->index % count($donutColors)] }}" stroke-width="4.5" stroke-dasharray="{{ $p }} {{ 100 - $p }}" stroke-dashoffset="-{{ $offset }}"/>
                                            @php $offset += $p; @endphp
                                        @endforeach
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                        <span class="text-base font-black text-slate-900 leading-none">{{ number_format($statusTotal) }}</span>
                                        <span class="text-[9px] uppercase font-bold text-slate-400 mt-0.5">Posts</span>
                                    </div>
                                </div>
                                <div class="space-y-2 text-xs w-full sm:w-auto sm:min-w-[150px]">
                                    @forelse($listingsByStatus as $status => $total)
                                        <div class="flex items-center justify-between p-1.5 rounded-lg bg-slate-50 border border-slate-100">
                                            <span class="flex items-center gap-2 capitalize font-semibold text-slate-700">
                                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $donutColors[$loop->index % count($donutColors)] }}"></span>
                                                {{ str_replace('_', ' ', $status) }}
                                            </span>
                                            <span class="font-extrabold text-slate-900 ml-2">{{ number_format($total) }}</span>
                                        </div>
                                    @empty
                                        <p class="text-slate-400 text-xs">No status data available.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Full-Width Responsive Table for Top Posts (Zero Clipping of Favs / Columns) -->
            <div class="{{ $card }} p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-extrabold text-slate-900">📝 Top Posts by Views</h2>
                    <span class="text-[11px] text-slate-400 font-semibold">{{ count($topListingsReport) }} posts ranked</span>
                </div>
                <div class="overflow-x-auto w-full">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80">
                                <th class="{{ $th }} py-2.5">#</th>
                                <th class="{{ $th }} py-2.5">Post</th>
                                <th class="{{ $th }} py-2.5">Category</th>
                                <th class="{{ $th }} py-2.5">Age</th>
                                @if($showUnique)<th class="{{ $th }} py-2.5">Unique Views</th>@endif
                                @if($showGross)<th class="{{ $th }} py-2.5">Gross Views</th>@endif
                                <th class="{{ $th }} py-2.5">Replies</th>
                                <th class="{{ $th }} py-2.5">Favs</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($topListingsReport as $i => $l)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="{{ $td }} font-bold text-slate-400">{{ $i + 1 }}</td>
                                    <td class="{{ $td }} min-w-[200px]">
                                        <a href="{{ route('listings.show', $l['slug']) }}" target="_blank" class="font-bold text-slate-900 hover:text-[#F20B65] line-clamp-1 block">{{ $l['title'] }}</a>
                                        <div class="text-[10px] text-slate-400">{{ $l['city'] }} · <span class="capitalize text-slate-600 font-medium">{{ str_replace('_', ' ', $l['status']) }}</span></div>
                                    </td>
                                    <td class="{{ $td }} font-medium text-slate-700 whitespace-nowrap">{{ $l['category'] }}</td>
                                    <td class="{{ $td }} font-mono">{{ $l['poster_age'] ?? '—' }}</td>
                                    @if($showUnique)<td class="{{ $td }} font-bold text-teal-700 whitespace-nowrap">{{ number_format($l['unique_views']) }}</td>@endif
                                    @if($showGross)<td class="{{ $td }} font-bold text-[#F20B65] whitespace-nowrap">{{ number_format($l['gross_views']) }}</td>@endif
                                    <td class="{{ $td }} font-semibold text-slate-700">{{ $l['reply_count'] }}</td>
                                    <td class="{{ $td }} font-semibold text-slate-700">{{ $l['favorite_count'] }}</td>
                                </tr>
                            @empty
                                <tr><td class="{{ $td }} text-center text-slate-400 py-6" colspan="8">No posts match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- 3. Countries --}}
    @if($show('countries'))
        <div class="{{ $card }} p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900">🌍 Country / Geo Report</h2>
                    <p class="text-[11px] text-slate-500">Real-time visitor geographic distribution</p>
                </div>
                <span class="text-[10px] font-black text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full border border-slate-200">
                    {{ count($countryReport) }} Countries Reached
                </span>
            </div>

            @if(empty($countryReport))
                <p class="text-xs text-slate-400 py-6 text-center">No real visitor traffic recorded for this period yet.</p>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Left: Visual Distribution & Donut Share (5 cols) -->
                    @if($showGraph || $showChart)
                        <div class="lg:col-span-5 space-y-4">
                            @if($showGraph)
                                @php
                                    $cTotal = max(1, array_sum(array_column($countryReport, $showGross ? 'hits' : 'unique_visitors')));
                                    $cOffset = 0;
                                @endphp
                                <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-4 flex flex-col items-center">
                                    <div class="text-[10px] uppercase tracking-wider text-slate-500 font-black mb-3 self-start">🍩 Country Share Graph</div>
                                    <div class="relative w-36 h-36 flex items-center justify-center my-1">
                                        <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#E2E8F0" stroke-width="5"/>
                                            @foreach(array_slice($countryReport, 0, 8) as $c)
                                                @php $p = round((($showGross ? $c['hits'] : $c['unique_visitors']) / $cTotal) * 100, 2); @endphp
                                                <circle cx="18" cy="18" r="15.9155" fill="none" stroke="{{ $donutColors[$loop->index % count($donutColors)] }}" stroke-width="5" stroke-dasharray="{{ $p }} {{ 100 - $p }}" stroke-dashoffset="-{{ $cOffset }}"/>
                                                @php $cOffset += $p; @endphp
                                            @endforeach
                                        </svg>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                            <span class="text-sm font-black text-slate-900 leading-none">{{ number_format($cTotal) }}</span>
                                            <span class="text-[9px] uppercase font-bold text-slate-400 mt-0.5">{{ $showGross ? 'Hits' : 'Visitors' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap justify-center gap-1.5 mt-3 text-[10px] font-bold">
                                        @foreach(array_slice($countryReport, 0, 8) as $c)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 shadow-2xs">
                                                <span class="w-2 h-2 rounded-full" style="background: {{ $donutColors[$loop->index % count($donutColors)] }}"></span>
                                                <span>{{ $c['country_code'] }}</span>
                                                <span class="text-slate-400 font-normal">({{ $showGross ? $c['gross_pct'] : $c['unique_pct'] }}%)</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($showChart)
                                <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-4 space-y-2.5">
                                    <div class="text-[10px] uppercase tracking-wider text-slate-500 font-black mb-1">📊 Top Country Distribution</div>
                                    @foreach(array_slice($countryReport, 0, 6) as $c)
                                        <div>
                                            <div class="flex justify-between text-[11px] font-semibold text-slate-700">
                                                <span class="flex items-center gap-1.5 truncate">
                                                    <span>{{ $flag($c['country_code']) }}</span>
                                                    <span class="font-bold text-slate-800 truncate">{{ $c['country_name'] }}</span>
                                                </span>
                                                <span class="text-slate-600 font-bold shrink-0 ml-2">{{ $showGross ? $c['gross_pct'] : $c['unique_pct'] }}%</span>
                                            </div>
                                            <div class="h-2 rounded-full bg-slate-200/60 mt-1 flex overflow-hidden">
                                                <div class="h-2 {{ $showGross ? 'bg-[#F20B65]' : 'bg-teal-600' }} transition-all duration-300" style="width: {{ max(2, $showGross ? $c['gross_pct'] : $c['unique_pct']) }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Right: Detailed Country Data Table (7 cols) -->
                    <div class="{{ ($showGraph || $showChart) ? 'lg:col-span-7' : 'lg:col-span-12' }} overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                            <thead>
                                <tr class="bg-slate-50/80">
                                    <th class="{{ $th }} py-2.5">Country</th>
                                    @if($showUnique)<th class="{{ $th }} py-2.5">Unique Visitors</th>@endif
                                    @if($showGross)<th class="{{ $th }} py-2.5">Gross Hits</th>@endif
                                    <th class="{{ $th }} py-2.5">Traffic Share</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($countryReport as $c)
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="{{ $td }} font-semibold text-slate-900 whitespace-nowrap">
                                            <span class="mr-1.5">{{ $flag($c['country_code']) }}</span>
                                            {{ $c['country_name'] }}
                                            <span class="text-[10px] text-slate-400 font-mono ml-1">({{ $c['country_code'] }})</span>
                                        </td>
                                        @if($showUnique)<td class="{{ $td }} font-bold text-teal-700 whitespace-nowrap">{{ number_format($c['unique_visitors']) }}</td>@endif
                                        @if($showGross)<td class="{{ $td }} font-bold text-[#F20B65] whitespace-nowrap">{{ number_format($c['hits']) }}</td>@endif
                                        <td class="{{ $td }} font-semibold text-slate-700 whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800">
                                                {{ $showGross ? $c['gross_pct'] : $c['unique_pct'] }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- 4. Devices, Browsers & OS --}}
    @if($show('devices'))
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="{{ $card }} p-5">
                <h2 class="text-sm font-extrabold text-slate-900 mb-4">📱 Devices</h2>
                @php
                    $dKey = $showGross ? 'gross_pct' : 'unique_pct';
                    $dOffset = 0;
                    $dColors = ['Desktop' => '#3B82F6', 'Mobile' => '#F20B65', 'Tablet' => '#F59E0B'];
                    $dIcons = ['Desktop' => '🖥', 'Mobile' => '📱', 'Tablet' => '📲'];
                @endphp
                @if($showGraph)
                    <div class="flex justify-center mb-4">
                        <svg viewBox="0 0 36 36" class="w-36 h-36 -rotate-90">
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#F1F5F9" stroke-width="5"/>
                            @foreach($deviceReport as $d)
                                @if($d[$dKey] > 0)
                                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="{{ $dColors[$d['device']] }}" stroke-width="5" stroke-dasharray="{{ $d[$dKey] }} {{ 100 - $d[$dKey] }}" stroke-dashoffset="-{{ $dOffset }}"/>
                                    @php $dOffset += $d[$dKey]; @endphp
                                @endif
                            @endforeach
                        </svg>
                    </div>
                @endif
                <div class="space-y-2.5">
                    @foreach($deviceReport as $d)
                        <div>
                            <div class="flex justify-between text-[11px] font-semibold text-slate-700">
                                <span>{{ $dIcons[$d['device']] }} {{ $d['device'] }}</span>
                                <span>@if($showUnique){{ number_format($d['unique_visitors']) }}u @endif @if($showGross)· {{ number_format($d['hits']) }}g @endif · {{ $d[$dKey] }}%</span>
                            </div>
                            @if($showChart)
                                <div class="h-2 rounded-full bg-slate-100 mt-1"><div class="h-2 rounded-full" style="width: {{ max(1, $d[$dKey]) }}%; background: {{ $dColors[$d['device']] }}"></div></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            @foreach([['🌐 Browsers', $browserReport, 'browser', '#0D9488'], ['💻 Operating Systems', $osReport, 'os', '#8B35F6']] as [$title, $rows, $field, $color])
                <div class="{{ $card }} p-5">
                    <h2 class="text-sm font-extrabold text-slate-900 mb-4">{{ $title }}</h2>
                    @forelse($rows as $r)
                        <div class="mb-2.5">
                            <div class="flex justify-between text-[11px] font-semibold text-slate-700">
                                <span>{{ $r[$field] }}</span>
                                <span>@if($showUnique){{ number_format($r['unique_visitors']) }} unique @endif @if($showGross)· {{ number_format($r['hits']) }} gross @endif</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100 mt-1"><div class="h-2 rounded-full" style="width: {{ max(2, $r['pct']) }}%; background: {{ $color }}"></div></div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No data for this period.</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    @endif

    {{-- 5. Age Demographics --}}
    @if($show('age'))
        <div class="{{ $card }} p-5">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900">🎂 Age Demographics</h2>
                    <p class="text-[11px] text-slate-500">Posters (post age) + registered users (birthdate) by age group, with post views</p>
                </div>
            </div>
            <div class="grid gap-6 {{ $showGraph && $showChart ? 'lg:grid-cols-2' : '' }}">
                @if($showChart)
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-400 font-black mb-2">📊 Column Chart (Posts / Users / Views)</div>
                        <div class="h-52 flex items-end gap-4 px-2">
                            @foreach($ageReport as $a)
                                <div class="flex-1 h-full flex flex-col justify-end items-center">
                                    <div class="text-[10px] font-bold text-slate-600 mb-1">{{ $a['share_pct'] }}%</div>
                                    <div class="w-full h-full flex items-end justify-center gap-1">
                                        <div class="w-1/2 max-w-[18px] rounded-t bg-[#F20B65]" style="height: {{ $a['bar_pct'] }}%" title="{{ $a['bracket'] }}: {{ $a['posts_count'] }} posts, {{ $a['users_count'] }} users"></div>
                                        <div class="w-1/2 max-w-[18px] rounded-t bg-teal-600" style="height: {{ $a['views_bar_pct'] }}%" title="{{ $a['bracket'] }}: {{ $a['post_views'] }} post views"></div>
                                    </div>
                                    <div class="text-[10px] font-black text-slate-700 mt-1">{{ $a['bracket'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex gap-4 justify-center text-[10px] font-semibold text-slate-600 mt-2">
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-[#F20B65]"></span>Posts + Users</span>
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-teal-600"></span>Post Views</span>
                        </div>
                    </div>
                @endif
                @if($showGraph)
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-400 font-black mb-2">📈 Distribution Graph</div>
                        <div class="h-44">
                            <svg viewBox="0 0 400 135" preserveAspectRatio="none" class="w-full h-full overflow-visible">
                                <defs><linearGradient id="ageGrad" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#8B35F6" stop-opacity="0.3"/><stop offset="100%" stop-color="#8B35F6" stop-opacity="0"/></linearGradient></defs>
                                <line x1="0" y1="130" x2="400" y2="130" stroke="#E2E8F0"/>
                                <path d="{{ $ageAreaD }}" fill="url(#ageGrad)"/>
                                <path d="{{ $ageLineD }}" fill="none" stroke="#8B35F6" stroke-width="2.5" vector-effect="non-scaling-stroke"/>
                            </svg>
                        </div>
                        <div class="flex justify-between text-[10px] font-black text-slate-600 mt-1">
                            @foreach($ageReport as $a)<span>{{ $a['bracket'] }}</span>@endforeach
                        </div>
                    </div>
                @endif
            </div>
            <div class="overflow-x-auto mt-5">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Age Group</th><th class="{{ $th }}">Posts</th><th class="{{ $th }}">Users</th><th class="{{ $th }}">Post Views</th><th class="{{ $th }}">Share</th><th class="{{ $th }}">Views Share</th></tr></thead>
                    <tbody>
                        @foreach($ageReport as $a)
                            <tr>
                                <td class="{{ $td }} font-bold">{{ $a['bracket'] }}</td>
                                <td class="{{ $td }}">{{ number_format($a['posts_count']) }}</td>
                                <td class="{{ $td }}">{{ number_format($a['users_count']) }}</td>
                                <td class="{{ $td }}">{{ number_format($a['post_views']) }}</td>
                                <td class="{{ $td }}">{{ $a['share_pct'] }}%</td>
                                <td class="{{ $td }}">{{ $a['views_share_pct'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

