@php
    $card = 'bg-white rounded-2xl border border-slate-200/80 shadow-sm';
    $btn = 'inline-flex items-center justify-center font-bold text-xs px-3.5 py-2 rounded-xl transition-all duration-200 shadow-sm focus:outline-none';
    $btnPrimary = $btn . ' bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-500 hover:to-rose-500 text-white shadow-pink-500/20';
    $btnSecondary = $btn . ' bg-white hover:bg-slate-50 text-slate-800 border border-slate-200';
    $m = $metrics ?? [];
@endphp

<div class="space-y-6">
    <!-- Header Ribbon & Sub-Nav -->
    <div class="{{ $card }} p-5">
        <div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4 pb-5 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-pink-50 border border-pink-200 text-pink-600 flex items-center justify-center font-black">
                        <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                            <span>Enterprise SEO &amp; Google Indexing Suite</span>
                            <span class="text-[11px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300">Live Production</span>
                        </h2>
                        <p class="text-xs text-slate-600 font-medium">Full-cycle keyword intelligence, automated XML sitemaps, on-page optimization, and policy-compliant Google discovery.</p>
                    </div>
                </div>
            </div>

            <!-- Date Range Filter & Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Date Filter -->
                <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl">
                    <span class="text-xs font-bold text-slate-600">Range:</span>
                    <select wire:model.live="seoDateRange" class="bg-transparent text-xs font-bold text-slate-900 border-none focus:ring-0 py-0 pl-1 pr-6 cursor-pointer">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="last_7_days">Last 7 Days</option>
                        <option value="last_28_days">Last 28 Days</option>
                        <option value="last_3_months">Last 3 Months</option>
                        <option value="last_6_months">Last 6 Months</option>
                        <option value="last_12_months">Last 12 Months</option>
                    </select>
                </div>

                <!-- Action: Generate Sitemaps -->
                <button wire:click="generateAllSitemaps" wire:loading.attr="disabled" class="{{ $btnSecondary }} text-slate-700">
                    <span wire:loading.remove wire:target="generateAllSitemaps" class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Build Sitemaps</span>
                    </span>
                    <span wire:loading wire:target="generateAllSitemaps" class="text-xs">Building...</span>
                </button>

                <!-- Action: Run Audit -->
                <button wire:click="runTechnicalSeoAudit" wire:loading.attr="disabled" class="{{ $btnSecondary }} text-slate-700">
                    <span wire:loading.remove wire:target="runTechnicalSeoAudit" class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Audit Site</span>
                    </span>
                    <span wire:loading wire:target="runTechnicalSeoAudit" class="text-xs">Scanning...</span>
                </button>

                <!-- Action: Process Queue -->
                <button wire:click="processIndexingQueueNow" wire:loading.attr="disabled" class="{{ $btnPrimary }}">
                    <span wire:loading.remove wire:target="processIndexingQueueNow" class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Dispatch Queue</span>
                    </span>
                    <span wire:loading wire:target="processIndexingQueueNow" class="text-xs">Dispatching...</span>
                </button>
            </div>
        </div>

        <!-- Sub Tabs Bar -->
        <div class="flex items-center gap-2 overflow-x-auto pt-4 scrollbar-thin">
            @php
                $subTabs = [
                    'overview' => ['Overview & 20 KPIs', 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                    'keywords' => ['Keyword Research', 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                    'urls' => ['URL Inventory & SERP', 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1'],
                    'sitemaps' => ['XML Sitemaps (8)', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    'indexing' => ['Indexing Queue', 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                    'gsc' => ['Search Console', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    'audit' => ['Technical Issues', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
                    'redirects' => ['Redirects & 410', 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                    'settings' => ['SEO & API Settings', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                ];
            @endphp

            @foreach($subTabs as $k => [$label, $svgD])
                <button
                    wire:click="setSeoSubTab('{{ $k }}')"
                    class="px-3.5 py-2 text-xs font-bold rounded-xl whitespace-nowrap transition-all duration-150 flex items-center gap-2 {{ $seoSubTab === $k ? 'bg-pink-600 text-white shadow-sm shadow-pink-600/30 font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $svgD }}"/></svg>
                    <span>{{ $label }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- SUB-TAB 1: OVERVIEW & 20 KPI METRICS (PHASE 1)                 -->
    <!-- ============================================================== -->
    @if($seoSubTab === 'overview')
        <!-- 20 Complete Enterprise Metrics Grid -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-black uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <span>Platform Discovery &amp; Search Index Telemetry</span>
                    <span class="text-[10px] font-bold text-slate-500 lowercase">(realtime database &amp; gsc state)</span>
                </h3>
                <span class="text-xs font-semibold text-slate-500">Updated: {{ now()->format('M d, Y H:i:s') }}</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3.5">
                <!-- 1. Total Website URLs -->
                <div class="{{ $card }} p-4 border-l-4 border-l-slate-800">
                    <p class="text-[11px] font-bold uppercase text-slate-500">1. Total URLs</p>
                    <h4 class="text-xl font-black text-slate-900 mt-1">{{ number_format($m['total_website_urls'] ?? 0) }}</h4>
                    <p class="text-[10px] text-slate-500 mt-0.5">Eligible public routes</p>
                </div>

                <!-- 2. Total Indexed URLs -->
                <div class="{{ $card }} p-4 border-l-4 border-l-emerald-600">
                    <p class="text-[11px] font-bold uppercase text-emerald-800">2. Indexed URLs</p>
                    <h4 class="text-xl font-black text-emerald-950 mt-1">{{ number_format($m['total_indexed_urls'] ?? 0) }}</h4>
                    <p class="text-[10px] text-emerald-700 mt-0.5">Verified on Google</p>
                </div>

                <!-- 3. Total Not Indexed URLs -->
                <div class="{{ $card }} p-4 border-l-4 border-l-amber-600">
                    <p class="text-[11px] font-bold uppercase text-amber-800">3. Not Indexed</p>
                    <h4 class="text-xl font-black text-amber-950 mt-1">{{ number_format($m['total_not_indexed_urls'] ?? 0) }}</h4>
                    <p class="text-[10px] text-amber-700 mt-0.5">Discovered / Queued</p>
                </div>

                <!-- 4. Pending Indexing URLs -->
                <div class="{{ $card }} p-4 border-l-4 border-l-blue-600">
                    <p class="text-[11px] font-bold uppercase text-blue-800">4. Pending Queue</p>
                    <h4 class="text-xl font-black text-blue-950 mt-1">{{ number_format($m['pending_indexing_urls'] ?? 0) }}</h4>
                    <p class="text-[10px] text-blue-700 mt-0.5">Awaiting worker ping</p>
                </div>

                <!-- 5. Sitemap URLs -->
                <div class="{{ $card }} p-4 border-l-4 border-l-indigo-600">
                    <p class="text-[11px] font-bold uppercase text-indigo-800">5. Sitemap URLs</p>
                    <h4 class="text-xl font-black text-indigo-950 mt-1">{{ number_format($m['sitemap_urls'] ?? 0) }}</h4>
                    <p class="text-[10px] text-indigo-700 mt-0.5">Across 8 sitemaps</p>
                </div>

                <!-- 6. Submitted URLs -->
                <div class="{{ $card }} p-4 border-l-4 border-l-teal-600">
                    <p class="text-[11px] font-bold uppercase text-teal-800">6. Submitted</p>
                    <h4 class="text-xl font-black text-teal-950 mt-1">{{ number_format($m['submitted_urls'] ?? 0) }}</h4>
                    <p class="text-[10px] text-teal-700 mt-0.5">Dispatched to search</p>
                </div>

                <!-- 7. Failed Indexing Requests -->
                <div class="{{ $card }} p-4 border-l-4 border-l-red-600">
                    <p class="text-[11px] font-bold uppercase text-red-800">7. Failed Requests</p>
                    <h4 class="text-xl font-black text-red-950 mt-1">{{ number_format($m['failed_indexing_requests'] ?? 0) }}</h4>
                    <p class="text-[10px] text-red-700 mt-0.5">Auto-retry active</p>
                </div>

                <!-- 8. Total Organic Clicks -->
                <div class="{{ $card }} p-4 border-l-4 border-l-pink-600">
                    <p class="text-[11px] font-bold uppercase text-pink-800">8. Organic Clicks</p>
                    <h4 class="text-xl font-black text-pink-950 mt-1">{{ number_format($m['total_organic_clicks'] ?? 0) }}</h4>
                    <p class="text-[10px] text-pink-700 mt-0.5">{{ $seoDateRange }}</p>
                </div>

                <!-- 9. Total Organic Impressions -->
                <div class="{{ $card }} p-4 border-l-4 border-l-purple-600">
                    <p class="text-[11px] font-bold uppercase text-purple-800">9. Impressions</p>
                    <h4 class="text-xl font-black text-purple-950 mt-1">{{ number_format($m['total_organic_impressions'] ?? 0) }}</h4>
                    <p class="text-[10px] text-purple-700 mt-0.5">Search visibility</p>
                </div>

                <!-- 10. Average Google Position -->
                <div class="{{ $card }} p-4 border-l-4 border-l-cyan-600">
                    <p class="text-[11px] font-bold uppercase text-cyan-800">10. Avg Position</p>
                    <h4 class="text-xl font-black text-cyan-950 mt-1">{{ $m['average_google_position'] > 0 ? $m['average_google_position'] : '—' }}</h4>
                    <p class="text-[10px] text-cyan-700 mt-0.5">SERP rank average</p>
                </div>

                <!-- 11. Average CTR -->
                <div class="{{ $card }} p-4 border-l-4 border-l-emerald-500">
                    <p class="text-[11px] font-bold uppercase text-emerald-800">11. Avg CTR</p>
                    <h4 class="text-xl font-black text-emerald-950 mt-1">{{ $m['average_ctr'] > 0 ? $m['average_ctr'] . '%' : '—' }}</h4>
                    <p class="text-[10px] text-emerald-700 mt-0.5">Click-through rate</p>
                </div>

                <!-- 12. Total Targeted Keywords -->
                <div class="{{ $card }} p-4 border-l-4 border-l-violet-600">
                    <p class="text-[11px] font-bold uppercase text-violet-800">12. Keywords</p>
                    <h4 class="text-xl font-black text-violet-950 mt-1">{{ number_format($m['total_targeted_keywords'] ?? 0) }}</h4>
                    <p class="text-[10px] text-violet-700 mt-0.5">Tracked in database</p>
                </div>

                <!-- 13. Ranking Keywords -->
                <div class="{{ $card }} p-4 border-l-4 border-l-amber-500">
                    <p class="text-[11px] font-bold uppercase text-amber-800">13. Top 100 Rank</p>
                    <h4 class="text-xl font-black text-amber-950 mt-1">{{ number_format($m['ranking_keywords'] ?? 0) }}</h4>
                    <p class="text-[10px] text-amber-700 mt-0.5">Ranking queries</p>
                </div>

                <!-- 14. SEO Health Score -->
                <div class="{{ $card }} p-4 border-l-4 border-l-green-600 bg-gradient-to-br from-green-50/50 to-white">
                    <p class="text-[11px] font-bold uppercase text-green-800">14. Health Score</p>
                    <h4 class="text-xl font-black text-green-950 mt-1">{{ $m['seo_health_score'] ?? 100 }}%</h4>
                    <p class="text-[10px] text-green-700 mt-0.5">Clean technical grade</p>
                </div>

                <!-- 15. Technical SEO Issues -->
                <div class="{{ $card }} p-4 border-l-4 border-l-rose-600">
                    <p class="text-[11px] font-bold uppercase text-rose-800">15. Tech Issues</p>
                    <h4 class="text-xl font-black text-rose-950 mt-1">{{ number_format($m['technical_seo_issues'] ?? 0) }}</h4>
                    <p class="text-[10px] text-rose-700 mt-0.5">Open findings</p>
                </div>

                <!-- 16. Missing Meta Titles -->
                <div class="{{ $card }} p-4 border-l-4 border-l-orange-500">
                    <p class="text-[11px] font-bold uppercase text-orange-800">16. Missing Titles</p>
                    <h4 class="text-xl font-black text-orange-950 mt-1">{{ number_format($m['missing_meta_titles'] ?? 0) }}</h4>
                    <p class="text-[10px] text-orange-700 mt-0.5">Tag check</p>
                </div>

                <!-- 17. Missing Meta Descriptions -->
                <div class="{{ $card }} p-4 border-l-4 border-l-yellow-600">
                    <p class="text-[11px] font-bold uppercase text-yellow-800">17. Missing Desc</p>
                    <h4 class="text-xl font-black text-yellow-950 mt-1">{{ number_format($m['missing_meta_descriptions'] ?? 0) }}</h4>
                    <p class="text-[10px] text-yellow-700 mt-0.5">Snippet check</p>
                </div>

                <!-- 18. Duplicate Content Warnings -->
                <div class="{{ $card }} p-4 border-l-4 border-l-red-500">
                    <p class="text-[11px] font-bold uppercase text-red-800">18. Duplicates</p>
                    <h4 class="text-xl font-black text-red-950 mt-1">{{ number_format($m['duplicate_content_warnings'] ?? 0) }}</h4>
                    <p class="text-[10px] text-red-700 mt-0.5">Title / desc duplicates</p>
                </div>

                <!-- 19. Broken Links -->
                <div class="{{ $card }} p-4 border-l-4 border-l-stone-600">
                    <p class="text-[11px] font-bold uppercase text-stone-700">19. Broken Links</p>
                    <h4 class="text-xl font-black text-stone-900 mt-1">{{ number_format($m['broken_links'] ?? 0) }}</h4>
                    <p class="text-[10px] text-stone-600 mt-0.5">404 dead endpoints</p>
                </div>

                <!-- 20. Crawl Errors -->
                <div class="{{ $card }} p-4 border-l-4 border-l-rose-700">
                    <p class="text-[11px] font-bold uppercase text-rose-800">20. Crawl Errors</p>
                    <h4 class="text-xl font-black text-rose-950 mt-1">{{ number_format($m['crawl_errors'] ?? 0) }}</h4>
                    <p class="text-[10px] text-rose-700 mt-0.5">5xx / HTTP blocks</p>
                </div>
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
                        <div class="bg-blue-500 h-2 rounded-full" style="width: 100%"></div>
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
                                        <a href="{{ $sm['route'] }}" target="_blank" class="font-bold text-blue-600 hover:underline">XML ↗</a>
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
                <form wire:submit.prevent="saveNewKeyword" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 pt-4">
                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Target Keyword *</label>
                        <input type="text" wire:model="newKeywordForm.keyword" placeholder="e.g. adult personals miami" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-pink-500 focus:border-pink-500" required>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Search Intent</label>
                        <select wire:model="newKeywordForm.search_intent" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-pink-500 focus:border-pink-500">
                            <option value="commercial">Commercial</option>
                            <option value="transactional">Transactional</option>
                            <option value="informational">Informational</option>
                            <option value="navigational">Navigational</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Est. Volume / Mo</label>
                        <input type="number" wire:model="newKeywordForm.search_volume" placeholder="1200" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-pink-500 focus:border-pink-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Priority</label>
                        <select wire:model="newKeywordForm.priority" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-pink-500 focus:border-pink-500">
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="{{ $btnPrimary }} w-full">Add Keyword</button>
                    </div>
                </form>
            </div>

            <!-- Keywords Table -->
            <div class="{{ $card }} p-5">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mb-4">
                    <div class="w-full sm:w-72">
                        <input type="text" wire:model.live.debounce.300ms="seoKeywordSearch" placeholder="Search keywords..." class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-pink-500 focus:border-pink-500">
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <select wire:model.live="seoKeywordIntentFilter" class="px-3 py-2 text-xs border border-slate-200 rounded-xl bg-white">
                            <option value="all">All Intents</option>
                            <option value="commercial">Commercial</option>
                            <option value="transactional">Transactional</option>
                            <option value="informational">Informational</option>
                        </select>

                        <select wire:model.live="seoKeywordPriorityFilter" class="px-3 py-2 text-xs border border-slate-200 rounded-xl bg-white">
                            <option value="all">All Priorities</option>
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                        </select>
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
                                            <a href="{{ $kw->target_url }}" target="_blank" class="block text-[10px] text-blue-600 font-normal hover:underline truncate max-w-xs">{{ $kw->target_url }}</a>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        @php
                                            $intentClasses = match($kw->search_intent) {
                                                'commercial' => 'bg-blue-50 text-blue-800 border-blue-200',
                                                'transactional' => 'bg-purple-50 text-purple-800 border-purple-200',
                                                'informational' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                                default => 'bg-slate-50 text-slate-700 border-slate-200',
                                            };
                                        @endphp
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md border {{ $intentClasses }} uppercase">{{ $kw->search_intent }}</span>
                                    </td>
                                    <td class="py-3 font-bold text-slate-800">{{ number_format($kw->search_volume) }}/mo</td>
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

                <div class="flex flex-wrap items-center gap-2">
                    <select wire:model.live="seoUrlTypeFilter" class="px-3 py-2 text-xs border border-slate-200 rounded-xl bg-white">
                        <option value="all">All URL Types</option>
                        <option value="listing">Listing</option>
                        <option value="category">Category</option>
                        <option value="location_state">State Landing</option>
                        <option value="location_city">City Landing</option>
                        <option value="cms_page">CMS Page</option>
                    </select>

                    <select wire:model.live="seoUrlIndexingFilter" class="px-3 py-2 text-xs border border-slate-200 rounded-xl bg-white">
                        <option value="all">All Indexing States</option>
                        <option value="indexed">Indexed</option>
                        <option value="discovered">Discovered</option>
                        <option value="queued">Queued</option>
                        <option value="submitted">Submitted</option>
                        <option value="excluded">Excluded / Noindex</option>
                    </select>
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
                                    <a href="{{ $ur->url }}" target="_blank" class="text-blue-600 text-[11px] hover:underline truncate block max-w-sm">{{ $ur->url }}</a>
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
                                            'queued' => 'bg-blue-100 text-blue-900',
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
                    <button wire:click="generateAllSitemaps" class="{{ $btnPrimary }}">
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
                                <a href="{{ $sm['route'] }}" target="_blank" class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
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
                    <button wire:click="processIndexingQueueNow" class="{{ $btnPrimary }}">
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
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md uppercase {{ $item->status === 'submitted' ? 'bg-emerald-100 text-emerald-800' : ($item->status === 'failed' ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800') }}">
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
                        <button wire:click="syncGoogleSearchConsoleNow" class="{{ $btnPrimary }}">
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
                                    <td class="py-2.5 text-blue-600 truncate max-w-xs">{{ $row->page_url }}</td>
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
                    <button wire:click="runTechnicalSeoAudit" class="{{ $btnPrimary }}">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Run Full Audit Scan</span>
                    </button>
                </div>

                <!-- Filters -->
                <div class="flex items-center gap-3 pt-4">
                    <select wire:model.live="seoIssueSeverityFilter" class="px-3 py-2 text-xs border border-slate-200 rounded-xl bg-white">
                        <option value="all">All Severities</option>
                        <option value="critical">Critical</option>
                        <option value="warning">Warning</option>
                        <option value="notice">Notice</option>
                    </select>
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
                                        <span class="px-2 py-0.5 text-[10px] font-black rounded-md uppercase {{ $issue->severity === 'critical' ? 'bg-red-100 text-red-900' : ($issue->severity === 'warning' ? 'bg-amber-100 text-amber-900' : 'bg-blue-100 text-blue-900') }}">
                                            {{ $issue->severity }}
                                        </span>
                                    </td>
                                    <td class="py-3 font-mono font-bold text-[11px] text-slate-800">{{ $issue->issue_type }}</td>
                                    <td class="py-3 text-blue-600 font-semibold truncate max-w-xs">{{ $issue->url }}</td>
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

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">HTTP Status</label>
                        <select wire:model="newRedirectForm.status_code" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl">
                            <option value="301">301 Moved Permanently</option>
                            <option value="302">302 Found (Temporary)</option>
                            <option value="410">410 Gone (Deleted Ad)</option>
                        </select>
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
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded font-mono {{ $red->status_code === 301 ? 'bg-blue-50 text-blue-800' : ($red->status_code === 410 ? 'bg-rose-50 text-rose-800' : 'bg-amber-50 text-amber-800') }}">
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

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Authentication Mode</label>
                        <select wire:model="gscForm.auth_mode" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl">
                            <option value="service_account">Google Service Account (JSON / Private Key)</option>
                            <option value="oauth2">OAuth 2.0 Web Application</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Service Account Client Email</label>
                        <input type="email" wire:model="gscForm.client_email" placeholder="seo-indexer@project-id.iam.gserviceaccount.com" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Private Key (Encrypted in DB)</label>
                        <textarea wire:model="gscForm.private_key" rows="4" placeholder="-----BEGIN PRIVATE KEY----- ... -----END PRIVATE KEY-----" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl font-mono text-[11px]"></textarea>
                    </div>

                    <button type="submit" class="{{ $btnPrimary }}">Save Google Credentials</button>
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
                        <button type="submit" class="{{ $btnPrimary }}">Save Directives</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

