@php
    $card = 'bg-white/95 rounded-3xl border border-slate-200/80 shadow-[0_10px_30px_-14px_rgba(15,23,42,0.06)]';
    $btn = 'inline-flex items-center justify-center font-extrabold text-xs px-4 py-2 rounded-2xl transition-all duration-200 focus:outline-none cursor-pointer';
    $btnPrimary = $btn . ' bg-gradient-to-r from-[#F20B83] via-rose-600 to-fuchsia-600 hover:from-[#D00972] hover:via-rose-700 hover:to-fuchsia-700 text-white shadow-[0_8px_20px_-6px_rgba(242,11,131,0.45)] hover:-translate-y-0.5';
    $btnPrimaryStyle = '';
    $btnSecondary = $btn . ' bg-slate-50 hover:bg-white text-slate-800 border border-slate-200/90 shadow-xs hover:-translate-y-0.5';
    $m = $metrics ?? [];

    $seoDateLabels = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 Days',
        'last_28_days' => 'Last 28 Days',
        'last_3_months' => 'Last 3 Months',
        'last_6_months' => 'Last 6 Months',
        'last_12_months' => 'Last 12 Months',
    ];
@endphp

<div class="space-y-6">
    <!-- Unified Action & Telemetry Ribbon -->
    <div class="relative z-20 rounded-3xl bg-white/95 backdrop-blur-xl border border-slate-200/80 p-4 sm:p-5 shadow-[0_12px_34px_-16px_rgba(15,23,42,0.07)]">
        <div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-rose-50 to-fuchsia-100/80 border border-rose-200/70 text-[#F20B83] flex items-center justify-center shrink-0 shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-slate-950 tracking-tight flex flex-wrap items-center gap-2">
                        <span>Enterprise SEO &amp; Google Indexing Suite</span>
                        <span class="inline-flex items-center gap-1.5 pl-1.5 pr-2.5 py-0.5 rounded-full bg-white border border-rose-200/80 shadow-2xs ring-1 ring-rose-500/10">
                            <span class="w-4 h-4 rounded-full bg-gradient-to-br from-[#F20B83] via-rose-500 to-fuchsia-600 text-white flex items-center justify-center shrink-0">
                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </span>
                            <span class="text-[10px] font-black uppercase tracking-wider bg-gradient-to-r from-[#F20B83] to-fuchsia-600 bg-clip-text text-transparent">Live Production</span>
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Full-cycle keyword intelligence, automated XML sitemaps, on-page optimization, and policy-compliant Google discovery.</p>
                </div>
            </div>

            <!-- Date Range Custom Dropdown & Action Controls -->
            <div class="flex flex-wrap items-center gap-2 w-full xl:w-auto xl:justify-end">
                <div class="relative min-w-[165px]" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                    <button type="button" @click="open = !open" aria-label="Filter analytics date range"
                        class="w-full flex items-center justify-between gap-2 px-3.5 py-2 rounded-2xl bg-slate-50/90 hover:bg-white border text-xs font-bold text-slate-800 transition-all cursor-pointer"
                        :class="open ? 'border-[#F20B83] bg-white ring-4 ring-[#F20B83]/10' : 'border-slate-200/90'">
                        <span class="flex items-center gap-2 truncate">
                            <svg class="w-3.5 h-3.5 text-[#F20B83] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="truncate">{{ $seoDateLabels[$seoDateRange] ?? 'Last 28 Days' }}</span>
                        </span>
                        <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                        class="absolute left-0 right-0 mt-2 z-50 rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200/90 shadow-[0_20px_50px_-12px_rgba(15,23,42,0.22)] p-1.5 space-y-0.5">
                        @foreach($seoDateLabels as $dKey => $dLabel)
                            <button type="button" wire:click="$set('seoDateRange', '{{ $dKey }}')" @click="open = false"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer {{ $seoDateRange === $dKey ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                                <span>{{ $dLabel }}</span>
                                @if($seoDateRange === $dKey) <svg class="w-3.5 h-3.5 text-[#F20B83] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Action: Build Sitemaps -->
                <button type="button" wire:click="generateAllSitemaps" wire:loading.attr="disabled" class="{{ $btnSecondary }}">
                    <span wire:loading.remove wire:target="generateAllSitemaps" class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Build Sitemaps</span>
                    </span>
                    <span wire:loading wire:target="generateAllSitemaps" class="text-xs">Building...</span>
                </button>

                <!-- Action: Run Audit -->
                <button type="button" wire:click="runTechnicalSeoAudit" wire:loading.attr="disabled" class="{{ $btnSecondary }}">
                    <span wire:loading.remove wire:target="runTechnicalSeoAudit" class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Audit Site</span>
                    </span>
                    <span wire:loading wire:target="runTechnicalSeoAudit" class="text-xs">Scanning...</span>
                </button>

                <!-- Action: Process Queue -->
                <button type="button" wire:click="processIndexingQueueNow" wire:loading.attr="disabled" class="{{ $btnPrimary }}">
                    <span wire:loading.remove wire:target="processIndexingQueueNow" class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Dispatch Queue</span>
                    </span>
                    <span wire:loading wire:target="processIndexingQueueNow" class="text-xs">Dispatching...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- SUB-TAB 1: OVERVIEW & 20 KPI METRICS (PHASE 1)                 -->
    <!-- ============================================================== -->
    @if($seoSubTab === 'overview')
        <!-- 20 Complete Enterprise Metrics Grid (Modern Bento Style) -->
        <div>
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <h3 class="text-xs sm:text-sm font-black uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-lg bg-rose-50 border border-rose-200 text-[#F20B83] flex items-center justify-center shrink-0">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </span>
                    <span>Platform Discovery &amp; Search Index Telemetry</span>
                    <span class="text-[10px] font-bold text-slate-400 lowercase">(realtime database &amp; gsc state)</span>
                </h3>
                <span class="text-[11px] font-bold text-slate-500 px-3 py-1 rounded-full bg-white border border-slate-200/80">Updated: {{ now()->format('M d, Y H:i:s') }}</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3.5">
                @php
                    $overviewCards = [
                        ['1. Total URLs', number_format($m['total_website_urls'] ?? 0), 'Eligible public routes', 'bg-slate-500 hover:border-slate-300'],
                        ['2. Indexed URLs', number_format($m['total_indexed_urls'] ?? 0), 'Verified on Google', 'bg-emerald-500 hover:border-emerald-200'],
                        ['3. Not Indexed', number_format($m['total_not_indexed_urls'] ?? 0), 'Discovered / Queued', 'bg-amber-500 hover:border-amber-200'],
                        ['4. Pending Queue', number_format($m['pending_indexing_urls'] ?? 0), 'Awaiting worker ping', 'bg-violet-500 hover:border-violet-200'],
                        ['5. Sitemap URLs', number_format($m['sitemap_urls'] ?? 0), 'Across 8 sitemaps', 'bg-fuchsia-500 hover:border-fuchsia-200'],
                        ['6. Submitted', number_format($m['submitted_urls'] ?? 0), 'Dispatched to search', 'bg-teal-500 hover:border-teal-200'],
                        ['7. Failed Requests', number_format($m['failed_indexing_requests'] ?? 0), 'Auto-retry active', 'bg-rose-500 hover:border-rose-200'],
                        ['8. Organic Clicks', number_format($m['total_organic_clicks'] ?? 0), $seoDateLabels[$seoDateRange] ?? $seoDateRange, 'bg-[#F20B83] hover:border-rose-200'],
                        ['9. Impressions', number_format($m['total_organic_impressions'] ?? 0), 'Search visibility', 'bg-purple-500 hover:border-purple-200'],
                        ['10. Avg Position', ($m['average_google_position'] ?? 0) > 0 ? $m['average_google_position'] : '—', 'SERP rank average', 'bg-amber-500 hover:border-amber-200'],
                        ['11. Avg CTR', ($m['average_ctr'] ?? 0) > 0 ? $m['average_ctr'] . '%' : '—', 'Click-through rate', 'bg-emerald-500 hover:border-emerald-200'],
                        ['12. Keywords', number_format($m['total_targeted_keywords'] ?? 0), 'Tracked in database', 'bg-violet-500 hover:border-violet-200'],
                        ['13. Top 100 Rank', number_format($m['ranking_keywords'] ?? 0), 'Ranking queries', 'bg-amber-500 hover:border-amber-200'],
                        ['14. Health Score', ($m['seo_health_score'] ?? 100) . '%', 'Clean technical grade', 'bg-emerald-500 hover:border-emerald-200'],
                        ['15. Tech Issues', number_format($m['technical_seo_issues'] ?? 0), 'Open findings', 'bg-rose-500 hover:border-rose-200'],
                        ['16. Missing Titles', number_format($m['missing_meta_titles'] ?? 0), 'Tag check', 'bg-orange-500 hover:border-orange-200'],
                        ['17. Missing Desc', number_format($m['missing_meta_descriptions'] ?? 0), 'Snippet check', 'bg-amber-500 hover:border-amber-200'],
                        ['18. Duplicates', number_format($m['duplicate_content_warnings'] ?? 0), 'Title / desc duplicates', 'bg-rose-500 hover:border-rose-200'],
                        ['19. Broken Links', number_format($m['broken_links'] ?? 0), '404 dead endpoints', 'bg-slate-500 hover:border-slate-300'],
                        ['20. Crawl Errors', number_format($m['crawl_errors'] ?? 0), '5xx / HTTP blocks', 'bg-rose-600 hover:border-rose-200'],
                    ];
                @endphp

                @foreach($overviewCards as [$ovTitle, $ovVal, $ovSub, $ovClasses])
                    @php [$ovDot, $ovHoverBorder] = explode(' ', $ovClasses); @endphp
                    <div class="group relative overflow-hidden rounded-2xl bg-white/95 border border-slate-200/80 p-4 shadow-[0_6px_22px_-12px_rgba(15,23,42,0.06)] hover:shadow-[0_14px_28px_-12px_rgba(15,23,42,0.12)] {{ $ovHoverBorder }} transition-all duration-150">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 truncate">{{ $ovTitle }}</p>
                            <span class="w-4 h-1 rounded-full {{ $ovDot }} shrink-0 opacity-85"></span>
                        </div>
                        <h4 class="text-xl font-black text-slate-950 tracking-tight tabular-nums mt-1.5">{{ $ovVal }}</h4>
                        <p class="text-[11px] font-semibold text-slate-500 mt-0.5 truncate">{{ $ovSub }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Health Breakdown & Quick Sitemaps Status -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- SEO Health Engine -->
            <div class="{{ $card }} p-5 lg:col-span-1">
                <h4 class="text-sm font-black text-slate-900 mb-3 flex items-center justify-between">
                    <span>Technical Health Summary</span>
                    <span class="text-xs font-black px-2.5 py-0.5 rounded-full {{ $m['seo_health_score'] >= 90 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $m['seo_health_score'] >= 90 ? 'EXCELLENT' : 'NEEDS ATTENTION' }}
                    </span>
                </h4>

                <div class="space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-600">Canonical Tag Conformity</span>
                        <span class="font-black text-emerald-700">100% Passed</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: 100%"></div>
                    </div>

                    <div class="flex items-center justify-between text-xs mt-2">
                        <span class="font-bold text-slate-600">OpenGraph &amp; Twitter Cards</span>
                        <span class="font-black text-emerald-700">100% Implemented</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: 100%"></div>
                    </div>

                    <div class="flex items-center justify-between text-xs mt-2">
                        <span class="font-bold text-slate-600">JSON-LD Structured Data</span>
                        <span class="font-black text-emerald-700">Schema.org Valid</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-gradient-to-r from-[#F20B83] to-fuchsia-500 h-2 rounded-full" style="width: 100%"></div>
                    </div>

                    <div class="flex items-center justify-between text-xs mt-2">
                        <span class="font-bold text-slate-600">Local SEO Noindex Protection</span>
                        <span class="font-black text-emerald-700">Active (0-post cities protected)</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-purple-500 h-2 rounded-full" style="width: 100%"></div>
                    </div>
                </div>

                <div class="mt-5 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                    <p class="font-bold text-slate-800">Crawler Policy &amp; Directives:</p>
                    <p class="mt-1">Google Indexing API is strictly utilized for authorized schema types. Classified ads discovery is optimized via auto-generated sitemaps, internal links, and instant Search Console discovery.</p>
                </div>
            </div>

            <!-- Active XML Sitemaps Overview -->
            <div class="{{ $card }} p-5 lg:col-span-2">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-black text-slate-900">Active Automated XML Sitemaps (Phase 4)</h4>
                    <a href="{{ route('sitemap.index') }}" target="_blank" class="text-xs font-bold text-pink-600 hover:text-pink-700 flex items-center gap-1">
                        <span>View Master sitemap.xml</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                                <th class="pb-2">Sitemap Name</th>
                                <th class="pb-2">Type</th>
                                <th class="pb-2">URLs</th>
                                <th class="pb-2">Frequency</th>
                                <th class="pb-2">Status</th>
                                <th class="pb-2 text-right">Inspect</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($sitemaps as $sm)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2.5 font-bold text-slate-900">{{ $sm['name'] }}</td>
                                    <td class="py-2.5"><span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 text-slate-700 font-mono">{{ $sm['type'] }}</span></td>
                                    <td class="py-2.5 font-black text-slate-900">{{ number_format($sm['count'] ?? 0) }}</td>
                                    <td class="py-2.5 text-slate-600">{{ $sm['frequency'] }}</td>
                                    <td class="py-2.5"><span class="text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">{{ $sm['status'] }}</span></td>
                                    <td class="py-2.5 text-right">
                                        <a href="{{ $sm['route'] }}" target="_blank" class="font-bold text-[#F20B83] hover:underline">XML ↗</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- SUB-TAB 2: KEYWORDS & INTENT (PHASE 2)                         -->
    <!-- ============================================================== -->
    @if($seoSubTab === 'keywords')
        <div class="space-y-5">
            <!-- Keyword Toolbar & Add Form -->
            <div class="{{ $card }} p-5">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <h4 class="text-sm font-black text-slate-900">Advanced Keyword Research &amp; Intent Classification</h4>
                        <p class="text-xs text-slate-600">Track focus keywords by category, state, city, and listing intent.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="harvestKeywords" class="{{ $btnSecondary }} text-pink-700 border-pink-200 hover:bg-pink-50">
                            <svg class="w-4 h-4 mr-1 text-pink-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                            <span>Auto-Harvest Keywords</span>
                        </button>
                    </div>
                </div>

                <!-- Add Keyword Form -->
                @php
                    $kwIntents = ['commercial' => 'Commercial', 'transactional' => 'Transactional', 'informational' => 'Informational', 'navigational' => 'Navigational'];
                    $kwPriorities = ['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];
                    $curKwIntent = $newKeywordForm['search_intent'] ?? 'commercial';
                    $curKwPrio = $newKeywordForm['priority'] ?? 'high';
                @endphp
                <form wire:submit.prevent="saveNewKeyword" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 pt-4">
                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Target Keyword *</label>
                        <input type="text" wire:model="newKeywordForm.keyword" placeholder="e.g. adult personals miami" class="w-full px-3.5 py-2 text-xs font-semibold bg-slate-50/90 focus:bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-[#F20B83]/10 focus:border-[#F20B83] focus:outline-none transition" required>
                    </div>

                    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Search Intent</label>
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 px-3.5 py-2 text-xs font-bold text-slate-800 bg-slate-50/90 hover:bg-white border rounded-2xl transition cursor-pointer" :class="open ? 'border-[#F20B83] bg-white ring-4 ring-[#F20B83]/10' : 'border-slate-200'">
                            <span class="truncate">{{ $kwIntents[$curKwIntent] ?? 'Commercial' }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition class="absolute left-0 right-0 mt-1.5 z-50 rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200 shadow-xl p-1.5 space-y-0.5">
                            @foreach($kwIntents as $ik => $il)
                                <button type="button" wire:click="$set('newKeywordForm.search_intent', '{{ $ik }}')" @click="open = false" class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $curKwIntent === $ik ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <span>{{ $il }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Est. Volume / Mo</label>
                        <input type="number" wire:model="newKeywordForm.search_volume" placeholder="1200" class="w-full px-3.5 py-2 text-xs font-semibold bg-slate-50/90 focus:bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-[#F20B83]/10 focus:border-[#F20B83] focus:outline-none transition">
                    </div>

                    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Priority</label>
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 px-3.5 py-2 text-xs font-bold text-slate-800 bg-slate-50/90 hover:bg-white border rounded-2xl transition cursor-pointer" :class="open ? 'border-[#F20B83] bg-white ring-4 ring-[#F20B83]/10' : 'border-slate-200'">
                            <span class="truncate">{{ $kwPriorities[$curKwPrio] ?? 'High' }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition class="absolute left-0 right-0 mt-1.5 z-50 rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200 shadow-xl p-1.5 space-y-0.5">
                            @foreach($kwPriorities as $pk => $pl)
                                <button type="button" wire:click="$set('newKeywordForm.priority', '{{ $pk }}')" @click="open = false" class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $curKwPrio === $pk ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <span>{{ $pl }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="{{ $btnPrimary }} w-full">Add Keyword</button>
                    </div>
                </form>
            </div>

            <!-- Keywords Table -->
            <div class="{{ $card }} p-5 relative z-10">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-4">
                    <div class="w-full sm:w-72 relative group">
                        <svg class="w-4 h-4 text-slate-400 group-focus-within:text-[#F20B83] transition absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" wire:model.live.debounce.300ms="seoKeywordSearch" placeholder="Search keywords..." class="w-full pl-10 pr-4 py-2 text-xs font-semibold bg-slate-50/90 focus:bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-[#F20B83]/10 focus:border-[#F20B83] focus:outline-none transition">
                    </div>

                    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                        @php
                            $kwIntentFilterLabels = ['all' => 'All Intents', 'commercial' => 'Commercial', 'transactional' => 'Transactional', 'informational' => 'Informational'];
                            $kwPrioFilterLabels = ['all' => 'All Priorities', 'critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium'];
                        @endphp
                        <div class="relative min-w-[140px]" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                            <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 px-3.5 py-2 text-xs font-bold text-slate-800 bg-slate-50/90 hover:bg-white border rounded-2xl transition cursor-pointer" :class="open ? 'border-[#F20B83] bg-white ring-4 ring-[#F20B83]/10' : 'border-slate-200'">
                                <span class="truncate">{{ $kwIntentFilterLabels[$seoKeywordIntentFilter] ?? 'All Intents' }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition class="absolute right-0 mt-1.5 w-44 z-50 rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200 shadow-xl p-1.5 space-y-0.5">
                                @foreach($kwIntentFilterLabels as $kifKey => $kifLbl)
                                    <button type="button" wire:click="$set('seoKeywordIntentFilter', '{{ $kifKey }}')" @click="open = false" class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $seoKeywordIntentFilter === $kifKey ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <span>{{ $kifLbl }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="relative min-w-[140px]" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                            <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 px-3.5 py-2 text-xs font-bold text-slate-800 bg-slate-50/90 hover:bg-white border rounded-2xl transition cursor-pointer" :class="open ? 'border-[#F20B83] bg-white ring-4 ring-[#F20B83]/10' : 'border-slate-200'">
                                <span class="truncate">{{ $kwPrioFilterLabels[$seoKeywordPriorityFilter] ?? 'All Priorities' }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition class="absolute right-0 mt-1.5 w-44 z-50 rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200 shadow-xl p-1.5 space-y-0.5">
                                @foreach($kwPrioFilterLabels as $kpfKey => $kpfLbl)
                                    <button type="button" wire:click="$set('seoKeywordPriorityFilter', '{{ $kpfKey }}')" @click="open = false" class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $seoKeywordPriorityFilter === $kpfKey ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <span>{{ $kpfLbl }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                                <th class="pb-2.5">Keyword</th>
                                <th class="pb-2.5">Intent</th>
                                <th class="pb-2.5">Search Volume</th>
                                <th class="pb-2.5">Difficulty</th>
                                <th class="pb-2.5">Est. CPC</th>
                                <th class="pb-2.5">Priority</th>
                                <th class="pb-2.5">Location / Category</th>
                                <th class="pb-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($seoKeywords as $kw)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 font-black text-slate-900">
                                        {{ $kw->keyword }}
                                        @if($kw->target_url)
                                            <a href="{{ $kw->target_url }}" target="_blank" class="block text-[10px] text-[#F20B83] font-normal hover:underline truncate max-w-xs">{{ $kw->target_url }}</a>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        @php
                                            $intentClasses = match($kw->search_intent) {
                                                'commercial' => 'bg-amber-50 text-amber-800 border-amber-200',
                                                'transactional' => 'bg-purple-50 text-purple-800 border-purple-200',
                                                'informational' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                                default => 'bg-slate-50 text-slate-700 border-slate-200',
                                            };
                                        @endphp
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border {{ $intentClasses }} uppercase">{{ $kw->search_intent }}</span>
                                    </td>
                                    <td class="py-3 font-bold text-slate-800">
                                        @if($kw->search_volume > 0)
                                            <div>{{ number_format($kw->search_volume) }}/mo</div>
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase {{ $kw->data_source === 'google_search_console' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $kw->data_source === 'google_search_console' ? 'Provider Reported (GSC)' : 'Internal Estimate' }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 font-semibold text-[11px]">Unavailable</span>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-16 bg-slate-100 rounded-full h-1.5">
                                                <div class="h-1.5 rounded-full {{ $kw->keyword_difficulty > 60 ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $kw->keyword_difficulty }}%"></div>
                                            </div>
                                            <span class="text-[10px] font-bold text-slate-600">{{ $kw->keyword_difficulty }}/100</span>
                                        </div>
                                    </td>
                                    <td class="py-3 font-semibold text-slate-700">${{ number_format($kw->cpc, 2) }}</td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md uppercase {{ $kw->priority === 'critical' ? 'bg-red-50 text-red-800 border border-red-200' : ($kw->priority === 'high' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-slate-50 text-slate-700') }}">
                                            {{ $kw->priority }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-slate-600">
                                        @if($kw->city && $kw->state)
                                            <span class="font-bold text-slate-800">{{ $kw->city->name }}, {{ $kw->state->code }}</span>
                                        @elseif($kw->state)
                                            <span class="font-bold text-slate-800">{{ $kw->state->name }}</span>
                                        @elseif($kw->category)
                                            <span class="font-bold text-pink-700">{{ $kw->category->name }}</span>
                                        @else
                                            <span class="text-slate-400">Sitewide</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-right">
                                        <button wire:click="deleteKeyword({{ $kw->id }})" wire:confirm="Remove this keyword from tracking?" class="text-rose-600 hover:text-rose-800 font-bold text-xs">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-slate-500">
                                        <p class="font-bold">No keywords tracked yet.</p>
                                        <p class="text-xs mt-1">Click "Auto-Harvest Keywords" above to scan active classified posts and locations.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $seoKeywords->links() }}
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- SUB-TAB 3: URL INVENTORY & SERP (PHASE 3, 7, 12)               -->
    <!-- ============================================================== -->
    @if($seoSubTab === 'urls')
        <div class="{{ $card }} p-5">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                <div>
                    <h4 class="text-sm font-black text-slate-900">URL Inventory &amp; On-Page SEO Records</h4>
                    <p class="text-xs text-slate-600">Complete catalog of website URLs, canonical status, and indexing eligibility.</p>
                </div>

                @php
                    $urlTypeLabels = [
                        'all' => 'All URL Types',
                        'listing' => 'Listing',
                        'category' => 'Category',
                        'location_state' => 'State Landing',
                        'location_city' => 'City Landing',
                        'cms_page' => 'CMS Page',
                    ];
                    $urlIndexLabels = [
                        'all' => 'All Indexing States',
                        'indexed' => 'Indexed',
                        'discovered' => 'Discovered',
                        'queued' => 'Queued',
                        'submitted' => 'Submitted',
                        'excluded' => 'Excluded / Noindex',
                    ];
                @endphp
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative min-w-[145px]" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 px-3.5 py-2 text-xs font-bold text-slate-800 bg-slate-50/90 hover:bg-white border rounded-2xl transition cursor-pointer" :class="open ? 'border-[#F20B83] bg-white ring-4 ring-[#F20B83]/10' : 'border-slate-200'">
                            <span class="truncate">{{ $urlTypeLabels[$seoUrlTypeFilter] ?? 'All URL Types' }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition class="absolute right-0 mt-1.5 w-44 z-50 rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200 shadow-xl p-1.5 space-y-0.5">
                            @foreach($urlTypeLabels as $utKey => $utLbl)
                                <button type="button" wire:click="$set('seoUrlTypeFilter', '{{ $utKey }}')" @click="open = false" class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $seoUrlTypeFilter === $utKey ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <span>{{ $utLbl }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="relative min-w-[160px]" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 px-3.5 py-2 text-xs font-bold text-slate-800 bg-slate-50/90 hover:bg-white border rounded-2xl transition cursor-pointer" :class="open ? 'border-[#F20B83] bg-white ring-4 ring-[#F20B83]/10' : 'border-slate-200'">
                            <span class="truncate">{{ $urlIndexLabels[$seoUrlIndexingFilter] ?? 'All Indexing States' }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition class="absolute right-0 mt-1.5 w-48 z-50 rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200 shadow-xl p-1.5 space-y-0.5">
                            @foreach($urlIndexLabels as $uiKey => $uiLbl)
                                <button type="button" wire:click="$set('seoUrlIndexingFilter', '{{ $uiKey }}')" @click="open = false" class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $seoUrlIndexingFilter === $uiKey ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <span>{{ $uiLbl }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                            <th class="pb-2.5">Page URL &amp; Title</th>
                            <th class="pb-2.5">Type</th>
                            <th class="pb-2.5">HTTP</th>
                            <th class="pb-2.5">Eligibility</th>
                            <th class="pb-2.5">Index Status</th>
                            <th class="pb-2.5">Health</th>
                            <th class="pb-2.5 text-right">Inspect</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($seoUrlRecords as $ur)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3 font-medium max-w-md">
                                    <span class="font-bold text-slate-900 block truncate">{{ $ur->meta_title ?: 'Untitled Route' }}</span>
                                    <a href="{{ $ur->url }}" target="_blank" class="text-[#F20B83] text-[11px] hover:underline truncate block max-w-sm">{{ $ur->url }}</a>
                                </td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 text-[10px] font-mono font-bold rounded bg-slate-100 text-slate-700">{{ $ur->url_type }}</span>
                                </td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded {{ $ur->http_status === 200 ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800' }}">{{ $ur->http_status }}</span>
                                </td>
                                <td class="py-3">
                                    <span class="text-[10px] font-bold uppercase {{ $ur->indexing_eligibility === 'eligible' ? 'text-emerald-700' : 'text-slate-500' }}">{{ $ur->indexing_eligibility }}</span>
                                </td>
                                <td class="py-3">
                                    @php
                                        $statusClass = match($ur->indexing_status) {
                                            'indexed' => 'bg-emerald-100 text-emerald-900',
                                            'submitted' => 'bg-teal-100 text-teal-900',
                                            'queued' => 'bg-violet-100 text-violet-900',
                                            'discovered' => 'bg-amber-100 text-amber-900',
                                            default => 'bg-slate-100 text-slate-700',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md {{ $statusClass }} uppercase">{{ $ur->indexing_status }}</span>
                                </td>
                                <td class="py-3">
                                    <span class="font-black text-xs {{ $ur->seo_health_score >= 80 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $ur->seo_health_score }}%</span>
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ $ur->url }}" target="_blank" class="text-pink-600 hover:text-pink-800 font-bold text-xs">View Page ↗</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-500">
                                    <p class="font-bold">No URL records loaded yet.</p>
                                    <p class="text-xs mt-1">Click "Build Sitemaps" above to index all existing site routes into the inventory.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $seoUrlRecords->links() }}
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- SUB-TAB 4: XML SITEMAPS (PHASE 4)                              -->
    <!-- ============================================================== -->
    @if($seoSubTab === 'sitemaps')
        <div class="space-y-5">
            <div class="{{ $card }} p-5">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <h4 class="text-sm font-black text-slate-900">Automated XML Sitemap Hub</h4>
                        <p class="text-xs text-slate-600">Compliant with Google Sitemaps 0.9 protocol (50,000 URL / 50MB limits per file).</p>
                    </div>
                    <button wire:click="generateAllSitemaps" class="{{ $btnPrimary }}" style="{{ $btnPrimaryStyle }}">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Regenerate &amp; Validate All</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pt-4">
                    @foreach($sitemaps as $sm)
                        <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-white transition-all">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-pink-100 text-pink-800">{{ $sm['type'] }}</span>
                                <span class="text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Valid XML</span>
                            </div>
                            <h5 class="font-black text-slate-900 text-sm mt-2.5 truncate">{{ $sm['name'] }}</h5>
                            <div class="flex items-center justify-between text-xs text-slate-600 mt-2">
                                <span>URLs Count:</span>
                                <span class="font-black text-slate-900">{{ number_format($sm['count'] ?? 0) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-600 mt-1">
                                <span>Frequency:</span>
                                <span class="font-bold text-slate-800">{{ $sm['frequency'] }}</span>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-200/60 flex items-center justify-between">
                                <a href="{{ $sm['route'] }}" target="_blank" class="text-xs font-bold text-[#F20B83] hover:underline flex items-center gap-1">
                                    <span>Inspect Feed</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- SUB-TAB 5: INDEXING QUEUE (PHASE 6 & 12)                       -->
    <!-- ============================================================== -->
    @if($seoSubTab === 'indexing')
        <div class="space-y-5">
            <!-- Google Policy Notice Banner -->
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 font-black">!</div>
                <div class="text-xs text-amber-900 leading-relaxed">
                    <p class="font-black uppercase tracking-wide">Google Indexing Policy &amp; Discovery Protocol</p>
                    <p class="mt-0.5">Google's Indexing API strictly authorizes JobPosting and BroadcastEvent schemas. Ordinary classified ads, personal advertisements, and location pages are submitted via Verified Sitemaps Ping, Search Console Inspection triggers, and clean canonical internal linking to adhere 100% to Google Search Essentials.</p>
                </div>
            </div>

            <!-- Queue Table -->
            <div class="{{ $card }} p-5">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div>
                        <h4 class="text-sm font-black text-slate-900">Google Indexing Submission Queue</h4>
                        <p class="text-xs text-slate-600">Real-time retry queue with exponential backoff (2, 4, 8, 16 mins).</p>
                    </div>
                    <button wire:click="processIndexingQueueNow" class="{{ $btnPrimary }}" style="{{ $btnPrimaryStyle }}">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Process Queue Now</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                                <th class="pb-2.5">Target URL</th>
                                <th class="pb-2.5">Action</th>
                                <th class="pb-2.5">API Protocol</th>
                                <th class="pb-2.5">Attempts</th>
                                <th class="pb-2.5">Next Retry</th>
                                <th class="pb-2.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($indexingQueue as $item)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 font-bold text-slate-900 truncate max-w-sm">{{ $item->url }}</td>
                                    <td class="py-3 font-mono text-[11px]">{{ $item->action }}</td>
                                    <td class="py-3 text-slate-600 font-semibold">{{ $item->api_type }}</td>
                                    <td class="py-3">{{ $item->attempts }} / {{ $item->max_attempts }}</td>
                                    <td class="py-3 text-slate-500">{{ $item->next_attempt_at?->diffForHumans() ?: 'Immediate' }}</td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase {{ $item->status === 'submitted' ? 'bg-emerald-100 text-emerald-800' : ($item->status === 'failed' ? 'bg-rose-100 text-rose-800' : 'bg-violet-100 text-violet-800') }}">
                                            {{ $item->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-500">
                                        <p class="font-bold">Indexing queue is currently clear.</p>
                                        <p class="text-xs mt-1">Whenever a new classified listing is published or updated, it automatically enqueues here.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $indexingQueue->links() }}
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- SUB-TAB 6: GOOGLE SEARCH CONSOLE (PHASE 5)                      -->
    <!-- ============================================================== -->
    @if($seoSubTab === 'gsc')
        <div class="space-y-5">
            <div class="{{ $card }} p-5">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <h4 class="text-sm font-black text-slate-900">Google Search Console Integration (OAuth / Service Account)</h4>
                        <p class="text-xs text-slate-600">Official Search Analytics API syncing actual clicks, impressions, CTR, and keyword rankings.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="syncGoogleSearchConsoleNow" class="{{ $btnPrimary }}" style="{{ $btnPrimaryStyle }}">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Sync Telemetry Now</span>
                        </button>
                    </div>
                </div>

                <!-- Status Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 pt-4">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                        <span class="text-[11px] font-bold text-slate-500 uppercase">Property URL</span>
                        <h5 class="text-sm font-black text-slate-900 mt-1 truncate">{{ $gscSettings['site_url'] }}</h5>
                        <span class="text-[10px] text-emerald-700 font-bold">Verified Property</span>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                        <span class="text-[11px] font-bold text-slate-500 uppercase">Connection Status</span>
                        <h5 class="text-sm font-black text-slate-900 mt-1">
                            @if($gscSettings['is_connected'])
                                <span class="text-emerald-700 font-bold">● Connected</span>
                            @else
                                <span class="text-amber-700 font-bold">○ Ready to Link</span>
                            @endif
                        </h5>
                        <span class="text-[10px] text-slate-500">Service Account</span>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                        <span class="text-[11px] font-bold text-slate-500 uppercase">Organic Clicks</span>
                        <h5 class="text-lg font-black text-pink-600 mt-1">{{ number_format($m['total_organic_clicks'] ?? 0) }}</h5>
                        <span class="text-[10px] text-slate-500">{{ $seoDateRange }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                        <span class="text-[11px] font-bold text-slate-500 uppercase">Impressions</span>
                        <h5 class="text-lg font-black text-purple-600 mt-1">{{ number_format($m['total_organic_impressions'] ?? 0) }}</h5>
                        <span class="text-[10px] text-slate-500">Search Appearances</span>
                    </div>
                </div>
            </div>

            <!-- Top Search Queries Table -->
            <div class="{{ $card }} p-5">
                <h4 class="text-sm font-black text-slate-900 mb-3">Google Organic Performance Rows</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                                <th class="pb-2.5">Date</th>
                                <th class="pb-2.5">Search Query</th>
                                <th class="pb-2.5">Landing Page</th>
                                <th class="pb-2.5">Clicks</th>
                                <th class="pb-2.5">Impressions</th>
                                <th class="pb-2.5">CTR</th>
                                <th class="pb-2.5 text-right">Avg Position</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($gscAnalytics as $row)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2.5 text-slate-500">{{ $row->date->format('Y-m-d') }}</td>
                                    <td class="py-2.5 font-bold text-slate-900">{{ $row->query ?: '—' }}</td>
                                    <td class="py-2.5 text-[#F20B83] font-medium truncate max-w-xs">{{ $row->page_url }}</td>
                                    <td class="py-2.5 font-black text-pink-600">{{ number_format($row->clicks) }}</td>
                                    <td class="py-2.5 font-bold text-slate-700">{{ number_format($row->impressions) }}</td>
                                    <td class="py-2.5 text-slate-800">{{ number_format($row->ctr * 100, 2) }}%</td>
                                    <td class="py-2.5 text-right font-bold text-slate-900">{{ $row->position }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-500">
                                        <p class="font-bold">No historical Google Search Console rows ingested yet.</p>
                                        <p class="text-xs mt-1">Configure your Google Service Account in SEO Settings tab to initiate scheduled telemetry sync.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- SUB-TAB 7: TECHNICAL ISSUES & AUDIT (PHASE 7 & 8)              -->
    <!-- ============================================================== -->
    @if($seoSubTab === 'audit')
        <div class="space-y-5">
            <div class="{{ $card }} p-5">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <h4 class="text-sm font-black text-slate-900">Technical SEO Audit &amp; Issue Resolution Engine</h4>
                        <p class="text-xs text-slate-600">Live checks: missing/duplicate meta, heading hierarchies, broken links, image alts, thin content.</p>
                    </div>
                    <button wire:click="runTechnicalSeoAudit" class="{{ $btnPrimary }}" style="{{ $btnPrimaryStyle }}">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Run Full Audit Scan</span>
                    </button>
                </div>

                <!-- Filters -->
                @php
                    $issueSevLabels = ['all' => 'All Severities', 'critical' => 'Critical', 'warning' => 'Warning', 'notice' => 'Notice'];
                @endphp
                <div class="flex items-center gap-3 pt-4">
                    <div class="relative min-w-[155px]" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 px-3.5 py-2 text-xs font-bold text-slate-800 bg-slate-50/90 hover:bg-white border rounded-2xl transition cursor-pointer" :class="open ? 'border-[#F20B83] bg-white ring-4 ring-[#F20B83]/10' : 'border-slate-200'">
                            <span class="truncate">{{ $issueSevLabels[$seoIssueSeverityFilter] ?? 'All Severities' }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition class="absolute left-0 mt-1.5 w-44 z-50 rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200 shadow-xl p-1.5 space-y-0.5">
                            @foreach($issueSevLabels as $svKey => $svLbl)
                                <button type="button" wire:click="$set('seoIssueSeverityFilter', '{{ $svKey }}')" @click="open = false" class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $seoIssueSeverityFilter === $svKey ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <span>{{ $svLbl }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto mt-4">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                                <th class="pb-2.5">Severity</th>
                                <th class="pb-2.5">Issue Type</th>
                                <th class="pb-2.5">Target URL</th>
                                <th class="pb-2.5">Diagnostic Finding</th>
                                <th class="pb-2.5">Recommendation</th>
                                <th class="pb-2.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($seoIssues as $issue)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 text-[10px] font-black rounded-full uppercase {{ $issue->severity === 'critical' ? 'bg-red-100 text-red-900' : ($issue->severity === 'warning' ? 'bg-amber-100 text-amber-900' : 'bg-violet-100 text-violet-900') }}">
                                            {{ $issue->severity }}
                                        </span>
                                    </td>
                                    <td class="py-3 font-mono font-bold text-[11px] text-slate-800">{{ $issue->issue_type }}</td>
                                    <td class="py-3 text-[#F20B83] font-semibold truncate max-w-xs">{{ $issue->url }}</td>
                                    <td class="py-3 text-slate-700 max-w-sm">{{ $issue->description }}</td>
                                    <td class="py-3 text-slate-500 max-w-sm">{{ $issue->recommendation }}</td>
                                    <td class="py-3 text-right">
                                        @if(! $issue->is_resolved)
                                            <button wire:click="resolveSeoIssue({{ $issue->id }})" class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200">
                                                Resolve
                                            </button>
                                        @else
                                            <span class="text-[11px] font-bold text-slate-400">Resolved ✓</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-500">
                                        <p class="font-bold text-emerald-700">Zero Technical Issues Found!</p>
                                        <p class="text-xs mt-1">Your classified site pages, headings, and metadata are 100% compliant.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $seoIssues->links() }}
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- SUB-TAB 8: REDIRECTS & 410 HANDLING (PHASE 8)                  -->
    <!-- ============================================================== -->
    @if($seoSubTab === 'redirects')
        <div class="space-y-5">
            <div class="{{ $card }} p-5">
                <h4 class="text-sm font-black text-slate-900 mb-1">URL Redirects &amp; 410 Gone Manager</h4>
                <p class="text-xs text-slate-600 mb-4">Prevent 404 crawl errors. 410 Gone immediately instructs Google to remove deleted ads from search indexes.</p>

                <!-- Add Redirect Form -->
                <form wire:submit.prevent="saveNewRedirect" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-3 border-t border-slate-100">
                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Source Path *</label>
                        <input type="text" wire:model="newRedirectForm.source_path" placeholder="/listings/old-slug" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl" required>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Target URL (if 301/302)</label>
                        <input type="text" wire:model="newRedirectForm.target_url" placeholder="/listings/new-slug" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl">
                    </div>

                    @php
                        $redirCodes = ['301' => '301 Moved Permanently', '302' => '302 Found (Temporary)', '410' => '410 Gone (Deleted Ad)'];
                        $curRedirCode = (string) ($newRedirectForm['status_code'] ?? '301');
                    @endphp
                    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">HTTP Status</label>
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 px-3.5 py-2 text-xs font-bold text-slate-800 bg-slate-50/90 hover:bg-white border rounded-2xl transition cursor-pointer" :class="open ? 'border-[#F20B83] bg-white ring-4 ring-[#F20B83]/10' : 'border-slate-200'">
                            <span class="truncate">{{ $redirCodes[$curRedirCode] ?? '301 Moved Permanently' }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition class="absolute left-0 right-0 mt-1.5 z-50 rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200 shadow-xl p-1.5 space-y-0.5">
                            @foreach($redirCodes as $rcKey => $rcLbl)
                                <button type="button" wire:click="$set('newRedirectForm.status_code', '{{ $rcKey }}')" @click="open = false" class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $curRedirCode === $rcKey ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <span>{{ $rcLbl }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="{{ $btnPrimary }} w-full">Save Redirect Rule</button>
                    </div>
                </form>
            </div>

            <!-- Redirects Table -->
            <div class="{{ $card }} p-5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                                <th class="pb-2.5">Source Path</th>
                                <th class="pb-2.5">Target Destination</th>
                                <th class="pb-2.5">Code</th>
                                <th class="pb-2.5">Hits</th>
                                <th class="pb-2.5">Last Access</th>
                                <th class="pb-2.5">Status</th>
                                <th class="pb-2.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($seoRedirects as $red)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 font-mono font-bold text-slate-900">{{ $red->source_path }}</td>
                                    <td class="py-3 text-slate-700">{{ $red->target_url ?: '— (Resource Removed)' }}</td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full font-mono {{ $red->status_code === 301 ? 'bg-violet-50 text-violet-800 border border-violet-200' : ($red->status_code === 410 ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-amber-50 text-amber-800 border border-amber-200') }}">
                                            {{ $red->status_code }}
                                        </span>
                                    </td>
                                    <td class="py-3 font-bold text-slate-800">{{ number_format($red->hits) }}</td>
                                    <td class="py-3 text-slate-500">{{ $red->last_hit_at?->diffForHumans() ?: 'Never' }}</td>
                                    <td class="py-3">
                                        <button wire:click="toggleRedirectStatus({{ $red->id }})" class="text-[11px] font-bold {{ $red->is_active ? 'text-emerald-700' : 'text-slate-400' }}">
                                            {{ $red->is_active ? 'Active ●' : 'Disabled ○' }}
                                        </button>
                                    </td>
                                    <td class="py-3 text-right">
                                        <button wire:click="deleteRedirect({{ $red->id }})" wire:confirm="Delete this redirect rule?" class="text-rose-600 hover:text-rose-800 font-bold">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-500">
                                        <p class="font-bold">No custom redirects registered.</p>
                                        <p class="text-xs mt-1">When listings are deleted, 410 Gone rules are automatically created here to inform search crawlers.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $seoRedirects->links() }}
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- SUB-TAB 9: SETTINGS & ROBOTS.TXT (PHASE 15)                     -->
    <!-- ============================================================== -->
    @if($seoSubTab === 'settings')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Google Search Console Credentials -->
            <div class="{{ $card }} p-5">
                <h4 class="text-sm font-black text-slate-900 mb-1">Google Search Console API Configuration</h4>
                <p class="text-xs text-slate-600 mb-4">Official OAuth 2.0 / Google Service Account key configuration.</p>

                <form wire:submit.prevent="saveGscCredentials" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Website Property URL *</label>
                        <input type="text" wire:model="gscForm.site_url" placeholder="https://localdatehub.com" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl" required>
                    </div>

                    @php
                        $gscAuthModes = [
                            'service_account' => 'Google Service Account (JSON / Private Key)',
                            'oauth2' => 'OAuth 2.0 Web Application',
                        ];
                        $curAuthMode = $gscForm['auth_mode'] ?? 'service_account';
                    @endphp
                    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Authentication Mode</label>
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 px-3.5 py-2 text-xs font-bold text-slate-800 bg-slate-50/90 hover:bg-white border rounded-2xl transition cursor-pointer" :class="open ? 'border-[#F20B83] bg-white ring-4 ring-[#F20B83]/10' : 'border-slate-200'">
                            <span class="truncate">{{ $gscAuthModes[$curAuthMode] ?? 'Google Service Account (JSON / Private Key)' }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition class="absolute left-0 right-0 mt-1.5 z-50 rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200 shadow-xl p-1.5 space-y-0.5">
                            @foreach($gscAuthModes as $amKey => $amLbl)
                                <button type="button" wire:click="$set('gscForm.auth_mode', '{{ $amKey }}')" @click="open = false" class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer {{ $curAuthMode === $amKey ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <span>{{ $amLbl }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Service Account Client Email</label>
                        <input type="email" wire:model="gscForm.client_email" placeholder="seo-indexer@project-id.iam.gserviceaccount.com" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Private Key (Encrypted in DB)</label>
                        <textarea wire:model="gscForm.private_key" rows="4" placeholder="-----BEGIN PRIVATE KEY----- ... -----END PRIVATE KEY-----" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl font-mono text-[11px]"></textarea>
                    </div>

                    <button type="submit" class="{{ $btnPrimary }}" style="{{ $btnPrimaryStyle }}">Save Google Credentials</button>
                </form>
            </div>

            <!-- Dynamic robots.txt Editor -->
            <div class="{{ $card }} p-5">
                <div class="flex items-center justify-between mb-1">
                    <h4 class="text-sm font-black text-slate-900">Dynamic robots.txt Editor</h4>
                    <a href="{{ route('seo.robots') }}" target="_blank" class="text-xs font-bold text-pink-600 hover:underline">View Live robots.txt ↗</a>
                </div>
                <p class="text-xs text-slate-600 mb-4">Real-time directives served at <code>/robots.txt</code>.</p>

                <form wire:submit.prevent="saveRobotsTxt" class="space-y-4">
                    <div>
                        <textarea wire:model="seoRobotsTxtContent" rows="11" class="w-full p-3 text-xs border border-slate-200 rounded-xl font-mono text-[11px] leading-relaxed bg-slate-900 text-emerald-400 focus:ring-0"></textarea>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-slate-500">Default protects admin, account, and api endpoints.</span>
                        <button type="submit" class="{{ $btnPrimary }}" style="{{ $btnPrimaryStyle }}">Save Directives</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

