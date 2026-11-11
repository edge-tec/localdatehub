{{-- Enterprise Advertising Campaigns Hub (Banner & Affiliate Offer Campaigns) --}}
@php
    $hasActiveFilters = trim($campaignSearch) !== ''
        || $campaignTypeFilter !== 'all'
        || $campaignStatusFilter !== 'all'
        || $campaignSlotFilter !== 'all';

    $visibleIds = method_exists($campaigns, 'pluck') ? $campaigns->pluck('id')->all() : [];
    $allVisibleSelected = !empty($visibleIds) && empty(array_diff($visibleIds, array_map('intval', $selectedCampaignIds)));

    $statusCounts = [
        'all' => $enterpriseStats['enterprise_campaigns'] ?? 0,
        'active' => \App\Modules\Advertising\Models\AdCampaign::where('status', 'active')->count(),
        'scheduled' => $enterpriseStats['scheduled_campaigns'] ?? 0,
        'paused' => $enterpriseStats['paused_campaigns'] ?? 0,
        'draft' => $enterpriseStats['draft_campaigns'] ?? 0,
        'expired' => $enterpriseStats['expired_campaigns'] ?? 0,
    ];

    $typeLabels = [
        'all' => ['All Formats', 'text-slate-400', 'M4 6h16M4 12h16M4 18h16'],
        'banner' => ['Banner Ads', 'text-amber-500', 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14'],
        'affiliate' => ['Affiliate Offers', 'text-purple-500', 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101'],
        'hybrid' => ['Hybrid Format', 'text-[#F20B83]', 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21'],
    ];

    $statusIconMap = [
        'all' => ['text-slate-400', 'M4 6h16M4 12h16M4 18h16'],
        'active' => ['text-[#F20B83]', 'M13 10V3L4 14h7v7l9-11h-7z'],
        'scheduled' => ['text-violet-500', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        'paused' => ['text-amber-500', 'M10 9v6m4-6v6'],
        'draft' => ['text-slate-400', 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5'],
        'expired' => ['text-rose-500', 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'archived' => ['text-zinc-400', 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8'],
        'pending_approval' => ['text-fuchsia-500', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        'rejected' => ['text-red-600', 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
    ];

    $activeSlotLabel = $campaignSlotFilter === 'all'
        ? 'All 13 Placements'
        : (\App\Modules\Advertising\Models\AdCampaign::SLOT_CATALOG[$campaignSlotFilter]['label'] ?? ucwords(str_replace('_', ' ', $campaignSlotFilter)));
@endphp

<div class="space-y-4">
    <!-- Futuristic Filter & Telemetry Console -->
    <div class="relative z-20 rounded-3xl bg-white/95 backdrop-blur-xl border border-slate-200/80 p-4 sm:p-5 shadow-[0_12px_34px_-16px_rgba(15,23,42,0.07)] space-y-4">
        <!-- Quick Status Dock -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex flex-wrap items-center gap-1.5 p-1 rounded-2xl bg-slate-100/85 border border-slate-200/70">
                @foreach([
                    'all' => ['All Campaigns', 'text-slate-400', 'M4 6h16M4 12h16M4 18h16'],
                    'active' => ['Active', 'text-[#F20B83]', 'M13 10V3L4 14h7v7l9-11h-7z'],
                    'scheduled' => ['Scheduled', 'text-violet-500', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'paused' => ['Paused', 'text-amber-500', 'M10 9v6m4-6v6'],
                    'draft' => ['Draft', 'text-slate-400', 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5'],
                    'expired' => ['Expired', 'text-rose-500', 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ] as $stKey => [$stLabel, $stIconColor, $stIconPath])
                    <button
                        type="button"
                        wire:click="$set('campaignStatusFilter', '{{ $stKey }}')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-150 cursor-pointer {{ $campaignStatusFilter === $stKey ? 'bg-slate-950 text-white shadow-sm' : 'text-slate-600 hover:text-slate-950 hover:bg-white/90' }}"
                    >
                        <svg class="w-3.5 h-3.5 shrink-0 {{ $campaignStatusFilter === $stKey && $stKey === 'all' ? 'text-[#F20B83]' : $stIconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stIconPath }}"/></svg>
                        <span>{{ $stLabel }}</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black tabular-nums {{ $campaignStatusFilter === $stKey ? 'bg-white/15 text-white' : 'bg-white text-slate-700 border border-slate-200/70' }}">
                            {{ number_format($statusCounts[$stKey] ?? 0) }}
                        </span>
                    </button>
                @endforeach
            </div>

            @if($hasActiveFilters)
                <button
                    type="button"
                    wire:click="resetCampaignFilters"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-extrabold bg-rose-50 hover:bg-rose-100 text-[#F20B83] border border-rose-200 transition cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Reset All</span>
                </button>
            @endif
        </div>

        <!-- Futuristic Search & Custom Dropdown Console -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2.5 items-center">
            <!-- Search Bar -->
            <div class="sm:col-span-2 lg:col-span-5 relative group">
                <svg class="w-4 h-4 text-slate-400 group-focus-within:text-[#F20B83] transition absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="campaignSearch"
                    class="w-full rounded-2xl bg-slate-50/90 hover:bg-white focus:bg-white border border-slate-200/90 focus:border-[#F20B83] focus:ring-4 focus:ring-[#F20B83]/10 text-xs font-semibold text-slate-900 pl-10 pr-8 py-2.5 focus:outline-none transition-all"
                    placeholder="Search campaigns, brands, affiliate URLs..."
                />
                @if(trim($campaignSearch) !== '')
                    <button type="button" wire:click="$set('campaignSearch', '')" aria-label="Clear campaign search" class="absolute right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-slate-200/80 hover:bg-rose-100 text-slate-500 hover:text-rose-600 flex items-center justify-center transition cursor-pointer">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            <!-- Custom Format Dropdown -->
            <div class="lg:col-span-2 relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                <button type="button" @click="open = !open" aria-label="Filter by format"
                    class="w-full flex items-center justify-between gap-2 px-3.5 py-2.5 rounded-2xl bg-slate-50/90 hover:bg-white border text-xs font-bold text-slate-800 transition-all cursor-pointer"
                    :class="open ? 'border-[#F20B83] bg-white ring-4 ring-[#F20B83]/10' : 'border-slate-200/90'">
                    <span class="flex items-center gap-2 min-w-0 truncate">
                        <svg class="w-3.5 h-3.5 shrink-0 {{ $typeLabels[$campaignTypeFilter][1] ?? 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $typeLabels[$campaignTypeFilter][2] ?? 'M4 6h16M4 12h16M4 18h16' }}"/></svg>
                        <span class="truncate">{{ $typeLabels[$campaignTypeFilter][0] ?? 'All Formats' }}</span>
                    </span>
                    <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                    class="absolute left-0 right-0 mt-2 z-50 rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200/90 shadow-[0_20px_50px_-12px_rgba(15,23,42,0.22)] p-1.5 space-y-0.5">
                    @foreach($typeLabels as $tKey => [$tText, $tIconColor, $tIconPath])
                        <button type="button" wire:click="$set('campaignTypeFilter', '{{ $tKey }}')" @click="open = false"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer {{ $campaignTypeFilter === $tKey ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                            <span class="flex items-center gap-2"><svg class="w-3.5 h-3.5 {{ $tIconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $tIconPath }}"/></svg><span>{{ $tText }}</span></span>
                            @if($campaignTypeFilter === $tKey) <svg class="w-3.5 h-3.5 text-[#F20B83]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Custom Status Dropdown (All 8 Statuses) -->
            <div class="lg:col-span-2 relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                <button type="button" @click="open = !open" aria-label="Filter by status"
                    class="w-full flex items-center justify-between gap-2 px-3.5 py-2.5 rounded-2xl bg-slate-50/90 hover:bg-white border text-xs font-bold text-slate-800 transition-all cursor-pointer"
                    :class="open ? 'border-[#F20B83] bg-white ring-4 ring-[#F20B83]/10' : 'border-slate-200/90'">
                    @php [$stClr, $stPath] = $statusIconMap[$campaignStatusFilter] ?? $statusIconMap['all']; @endphp
                    <span class="flex items-center gap-2 min-w-0 truncate">
                        <svg class="w-3.5 h-3.5 shrink-0 {{ $stClr }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stPath }}"/></svg>
                        <span class="truncate">{{ $campaignStatusFilter === 'all' ? 'All 8 Statuses' : ucwords(str_replace('_', ' ', $campaignStatusFilter)) }}</span>
                    </span>
                    <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                    class="absolute left-0 right-0 mt-2 z-50 max-h-64 overflow-y-auto rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200/90 shadow-[0_20px_50px_-12px_rgba(15,23,42,0.22)] p-1.5 space-y-0.5">
                    <button type="button" wire:click="$set('campaignStatusFilter', 'all')" @click="open = false"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer {{ $campaignStatusFilter === 'all' ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                        <span class="flex items-center gap-2"><svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg><span>All 8 Statuses</span></span>
                        @if($campaignStatusFilter === 'all') <svg class="w-3.5 h-3.5 text-[#F20B83]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> @endif
                    </button>
                    @foreach(\App\Modules\Advertising\Models\AdCampaign::STATUSES as $st)
                        @php [$rowIconClr, $rowIconPath] = $statusIconMap[$st] ?? $statusIconMap['all']; @endphp
                        <button type="button" wire:click="$set('campaignStatusFilter', '{{ $st }}')" @click="open = false"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition cursor-pointer {{ $campaignStatusFilter === $st ? 'bg-rose-50 text-[#F20B83] font-bold' : 'text-slate-700 hover:bg-slate-50' }}">
                            <span class="flex items-center gap-2"><svg class="w-3.5 h-3.5 {{ $rowIconClr }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $rowIconPath }}"/></svg><span>{{ ucwords(str_replace('_', ' ', $st)) }}</span></span>
                            @if($campaignStatusFilter === $st) <svg class="w-3.5 h-3.5 text-[#F20B83] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Custom Placement Dropdown -->
            <div class="lg:col-span-3 relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                <button type="button" @click="open = !open" aria-label="Filter by placement"
                    class="w-full flex items-center justify-between gap-2 px-3.5 py-2.5 rounded-2xl bg-slate-50/90 hover:bg-white border text-xs font-bold text-slate-800 transition-all cursor-pointer"
                    :class="open ? 'border-[#F20B83] bg-white ring-4 ring-[#F20B83]/10' : 'border-slate-200/90'">
                    <span class="flex items-center gap-2 min-w-0 truncate">
                        <svg class="w-3.5 h-3.5 shrink-0 {{ $campaignSlotFilter === 'all' ? 'text-slate-400' : 'text-[#F20B83]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/></svg>
                        <span class="truncate">{{ $activeSlotLabel }}</span>
                    </span>
                    <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                    class="absolute left-0 right-0 mt-2 z-50 max-h-72 overflow-y-auto rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200/90 shadow-[0_20px_50px_-12px_rgba(15,23,42,0.22)] p-1.5 space-y-0.5">
                    <button type="button" wire:click="$set('campaignSlotFilter', 'all')" @click="open = false"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer {{ $campaignSlotFilter === 'all' ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                        <span>All 13 Placements</span>
                        @if($campaignSlotFilter === 'all') <svg class="w-3.5 h-3.5 text-[#F20B83]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> @endif
                    </button>
                    @foreach(\App\Modules\Advertising\Models\AdCampaign::SLOTS as $s)
                        @php $slotName = \App\Modules\Advertising\Models\AdCampaign::SLOT_CATALOG[$s]['label'] ?? ucwords(str_replace('_', ' ', $s)); @endphp
                        <button type="button" wire:click="$set('campaignSlotFilter', '{{ $s }}')" @click="open = false"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition cursor-pointer {{ $campaignSlotFilter === $s ? 'bg-rose-50 text-[#F20B83] font-bold' : 'text-slate-700 hover:bg-slate-50' }}">
                            <span class="truncate">{{ $slotName }}</span>
                            @if($campaignSlotFilter === $s) <svg class="w-3.5 h-3.5 text-[#F20B83] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        @if($hasActiveFilters)
            <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100 text-[11px]">
                <span class="font-black uppercase tracking-wider text-slate-400 mr-1">Active Filters:</span>
                @if(trim($campaignSearch) !== '')
                    <button type="button" wire:click="$set('campaignSearch', '')" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-900 text-white font-bold cursor-pointer hover:bg-slate-800">
                        <span>Query: &ldquo;{{ Str::limit($campaignSearch, 24) }}&rdquo;</span>
                        <span class="text-rose-300">&times;</span>
                    </button>
                @endif
                @if($campaignTypeFilter !== 'all')
                    <button type="button" wire:click="$set('campaignTypeFilter', 'all')" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-900 border border-amber-200 font-bold cursor-pointer hover:bg-amber-100">
                        <span>Format: {{ $typeLabels[$campaignTypeFilter][0] ?? ucfirst($campaignTypeFilter) }}</span>
                        <span>&times;</span>
                    </button>
                @endif
                @if($campaignStatusFilter !== 'all')
                    <button type="button" wire:click="$set('campaignStatusFilter', 'all')" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-900 border border-emerald-200 font-bold cursor-pointer hover:bg-emerald-100">
                        <span>Status: {{ ucwords(str_replace('_', ' ', $campaignStatusFilter)) }}</span>
                        <span>&times;</span>
                    </button>
                @endif
                @if($campaignSlotFilter !== 'all')
                    <button type="button" wire:click="$set('campaignSlotFilter', 'all')" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-50 text-rose-900 border border-rose-200 font-bold cursor-pointer hover:bg-rose-100">
                        <span>Slot: {{ $activeSlotLabel }}</span>
                        <span>&times;</span>
                    </button>
                @endif
            </div>
        @endif

        <!-- Bulk Selection Action Bar -->
        @if(!empty($selectedCampaignIds))
            <div class="p-3 rounded-xl bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-xs font-bold">
                    <span class="px-2 py-0.5 rounded-md bg-[#F20B83] text-white font-black">{{ count($selectedCampaignIds) }}</span>
                    <span>{{ Str::plural('campaign', count($selectedCampaignIds)) }} selected</span>
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" wire:click="bulkUpdateCampaignStatus('active')" class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold transition cursor-pointer">Activate</button>
                    <button type="button" wire:click="bulkUpdateCampaignStatus('paused')" class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition cursor-pointer">Pause</button>
                    <button type="button" wire:click="bulkUpdateCampaignStatus('archived')" class="px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold transition cursor-pointer">Archive</button>
                    <button type="button" wire:click="bulkDeleteCampaigns" wire:confirm="Delete all selected campaigns?" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition cursor-pointer">Delete</button>
                    <button type="button" wire:click="$set('selectedCampaignIds', [])" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition cursor-pointer">Clear</button>
                </div>
            </div>
        @endif
    </div>

    <!-- Desktop / Tablet Enterprise Campaigns Table -->
    <div class="hidden md:block bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/90">
                    <tr>
                        <th class="pl-4 pr-2 py-3 text-left w-10">
                            <input
                                type="checkbox"
                                wire:click="toggleSelectAllCampaigns({{ json_encode($visibleIds) }})"
                                @checked($allVisibleSelected)
                                aria-label="Select all campaigns on page"
                                class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83] cursor-pointer"
                            />
                        </th>
                        <th class="{{ $th }}">Campaign &amp; Format</th>
                        <th class="{{ $th }}">Placements &amp; Targeting</th>
                        <th class="{{ $th }}">Schedule &amp; Priority</th>
                        <th class="{{ $th }}">Budget &amp; Caps</th>
                        <th class="{{ $th }}">Performance</th>
                        <th class="{{ $th }}">Status</th>
                        <th class="{{ $th }} text-right pr-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($campaigns as $camp)
                    @php
                        $creative = $camp->creatives->first();
                        $thumb = $creative?->resolvedImageUrl('desktop');
                        $statusStyles = [
                            'active' => ['pill' => 'bg-rose-50 text-[#F20B83] border-rose-200', 'icon' => 'text-[#F20B83]', 'path' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                            'scheduled' => ['pill' => 'bg-violet-50 text-violet-800 border-violet-200', 'icon' => 'text-violet-600', 'path' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                            'pending_approval' => ['pill' => 'bg-fuchsia-50 text-fuchsia-800 border-fuchsia-200', 'icon' => 'text-fuchsia-600', 'path' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                            'paused' => ['pill' => 'bg-amber-50 text-amber-800 border-amber-200', 'icon' => 'text-amber-600', 'path' => 'M10 9v6m4-6v6'],
                            'draft' => ['pill' => 'bg-slate-100 text-slate-700 border-slate-300', 'icon' => 'text-slate-500', 'path' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5'],
                            'expired' => ['pill' => 'bg-rose-50 text-rose-800 border-rose-200', 'icon' => 'text-rose-600', 'path' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                            'rejected' => ['pill' => 'bg-red-100 text-red-900 border-red-300', 'icon' => 'text-red-600', 'path' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
                            'archived' => ['pill' => 'bg-zinc-100 text-zinc-700 border-zinc-300', 'icon' => 'text-zinc-500', 'path' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8'],
                        ];
                        $stStyle = $statusStyles[$camp->status] ?? $statusStyles['draft'];
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition" wire:key="camp-row-{{ $camp->id }}">
                        <td class="pl-4 pr-2 py-3 align-middle">
                            <input
                                type="checkbox"
                                value="{{ $camp->id }}"
                                wire:model.live="selectedCampaignIds"
                                aria-label="Select {{ $camp->name }}"
                                class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83] cursor-pointer"
                            />
                        </td>
                        <td class="{{ $td }}">
                            <div class="flex items-center gap-3">
                                @if($thumb)
                                    <button type="button" wire:click="openPreviewCampaignModal({{ $camp->id }})" class="shrink-0 group cursor-pointer" title="Preview Creative">
                                        <img src="{{ $thumb }}" alt="{{ $camp->name }}" class="w-12 h-12 rounded-xl object-cover border border-slate-200 group-hover:ring-2 ring-[#F20B83] transition" />
                                    </button>
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-pink-50 to-purple-50 border border-pink-200 flex items-center justify-center text-[#F20B83] shrink-0">
                                        @if($camp->campaign_type === 'affiliate')
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        @endif
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-black text-slate-950 text-xs truncate max-w-[180px]">{{ $camp->name }}</span>
                                        <span class="px-1.5 py-0.5 rounded-md text-[9px] font-black uppercase {{ $camp->campaign_type === 'affiliate' ? 'bg-purple-100 text-purple-900' : ($camp->campaign_type === 'hybrid' ? 'bg-fuchsia-100 text-fuchsia-900' : 'bg-amber-100 text-amber-900') }}">
                                            {{ $camp->campaign_type }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] font-semibold text-slate-500 truncate max-w-[220px] mt-0.5">
                                        {{ $creative?->affiliate_brand_name ?: ($camp->advertiser_name ?: 'Direct Partner') }}
                                        @if($creative?->offer_badge)
                                            <span class="text-[#F20B83] font-black">· {{ $creative->offer_badge }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="{{ $td }}">
                            <div class="flex flex-wrap gap-1 max-w-[210px]">
                                @foreach($camp->placements->take(2) as $pl)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                        {{ \App\Modules\Advertising\Models\AdCampaign::SLOT_CATALOG[$pl->placement_slot]['label'] ?? str_replace('_', ' ', $pl->placement_slot) }}
                                    </span>
                                @endforeach
                                @if($camp->placements->count() > 2)
                                    <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-pink-50 text-[#F20B83] border border-pink-200">+{{ $camp->placements->count() - 2 }} more</span>
                                @endif
                            </div>
                            <div class="text-[10px] text-slate-500 font-semibold mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>
                                    @if($camp->targetingRules->isEmpty())
                                        Global (All Locations &amp; Devices)
                                    @else
                                        {{ $camp->targetingRules->count() }} Targeting {{ Str::plural('Rule', $camp->targetingRules->count()) }}
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td class="{{ $td }}">
                            <div class="text-[11px] font-bold text-slate-800 whitespace-nowrap">
                                {{ $camp->starts_at?->format('M j, Y') ?? 'Immediate' }} &rarr; {{ $camp->ends_at?->format('M j, Y') ?? 'Ongoing' }}
                            </div>
                            <div class="text-[10px] text-slate-500 font-bold mt-0.5">
                                Priority <span class="text-slate-900">{{ $camp->priority }}</span> · Weight {{ $camp->weight }}
                            </div>
                        </td>
                        <td class="{{ $td }}">
                            <div class="text-xs font-black text-slate-900">
                                {{ $camp->total_budget_cents ? '$' . number_format($camp->total_budget_cents / 100, 2) . ' max' : ($camp->price_cents ? '$' . number_format($camp->price_cents / 100, 2) : 'Unlimited') }}
                            </div>
                            <div class="text-[10px] text-slate-500 font-semibold">
                                Spent ${{ number_format($camp->spent_cents / 100, 2) }}@if($camp->frequency_cap_per_session) · {{ $camp->frequency_cap_per_session }}/sess @endif
                            </div>
                        </td>
                        <td class="{{ $td }}">
                            <div class="font-mono text-xs font-black text-slate-900">
                                {{ number_format($camp->total_impressions) }} <span class="text-slate-400 font-normal">/</span> {{ number_format($camp->total_clicks) }} <span class="text-slate-400 font-normal">/</span> <span class="text-emerald-700">{{ number_format($camp->total_conversions) }}</span>
                            </div>
                            <div class="text-[10px] font-bold text-slate-600 mt-0.5">
                                <span class="text-[#F20B83]">{{ $camp->clickThroughRate() }}% CTR</span> · ${{ number_format($camp->total_conversion_revenue_cents / 100, 2) }} rev
                            </div>
                        </td>
                        <td class="{{ $td }}">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase border {{ $stStyle['pill'] }}">
                                <svg class="w-3 h-3 shrink-0 {{ $stStyle['icon'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stStyle['path'] }}"/></svg>
                                <span>{{ str_replace('_', ' ', $camp->status) }}</span>
                            </span>
                        </td>
                        <td class="{{ $td }} text-right pr-4 whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                @if($camp->status === 'active')
                                    <button type="button" wire:click="updateCampaignStatus({{ $camp->id }}, 'paused')" title="Pause Campaign" class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6"/></svg>
                                    </button>
                                @else
                                    <button type="button" wire:click="updateCampaignStatus({{ $camp->id }}, 'active')" title="Activate Campaign" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z"/></svg>
                                    </button>
                                @endif
                                <button type="button" wire:click="openPreviewCampaignModal({{ $camp->id }})" title="Preview & Postback URL" class="p-1.5 rounded-lg bg-pink-50 hover:bg-pink-100 text-[#F20B83] border border-pink-200 transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                <button type="button" wire:click="openCampaignModal({{ $camp->id }})" title="Edit Campaign" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button type="button" wire:click="duplicateCampaign({{ $camp->id }})" title="Duplicate Campaign" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                                <button type="button" wire:click="confirmDeleteCampaign({{ $camp->id }})" title="Delete Campaign" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center bg-slate-50/60">
                            <div class="max-w-md mx-auto space-y-3">
                                <div class="w-12 h-12 rounded-2xl bg-pink-50 border border-pink-200 text-[#F20B83] flex items-center justify-center mx-auto">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                </div>
                                <h4 class="text-sm font-black text-slate-900">
                                    {{ $hasActiveFilters ? 'No Campaigns Match Your Filters' : 'No Enterprise Advertising Campaigns Yet' }}
                                </h4>
                                <p class="text-xs text-slate-500 leading-relaxed">
                                    {{ $hasActiveFilters ? 'Try clearing one or more active search, status, format, or placement filters.' : 'Launch responsive Banner Ads or Affiliate Offers with geo, category & device targeting, frequency capping, and S2S conversion postbacks.' }}
                                </p>
                                <div class="flex items-center justify-center gap-2 pt-1">
                                    @if($hasActiveFilters)
                                        <button type="button" wire:click="resetCampaignFilters" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs cursor-pointer">
                                            Reset Filters
                                        </button>
                                    @endif
                                    <button type="button" wire:click="openCampaignModal" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#F20B83] hover:bg-[#D00972] text-white font-bold text-xs shadow-xs cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        <span>Create Campaign</span>
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($campaigns, 'hasPages') && $campaigns->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>

    <!-- Mobile Responsive Card Feed -->
    <div class="md:hidden space-y-3">
        @forelse($campaigns as $camp)
            @php
                $creative = $camp->creatives->first();
                $thumb = $creative?->resolvedImageUrl('mobile');
            @endphp
            <div class="bg-white/95 backdrop-blur-md border border-slate-200/80 rounded-3xl p-4 shadow-[0_8px_24px_-12px_rgba(15,23,42,0.06)] space-y-3" wire:key="camp-card-{{ $camp->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <input
                            type="checkbox"
                            value="{{ $camp->id }}"
                            wire:model.live="selectedCampaignIds"
                            class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]"
                        />
                        @if($thumb)
                            <img src="{{ $thumb }}" alt="{{ $camp->name }}" class="w-11 h-11 rounded-2xl object-cover border border-slate-200 shrink-0" />
                        @endif
                        <div class="min-w-0">
                            <div class="font-black text-slate-950 text-sm truncate">{{ $camp->name }}</div>
                            <div class="text-[11px] font-semibold text-slate-500 truncate">{{ $creative?->affiliate_brand_name ?: ($camp->advertiser_name ?: 'Direct Partner') }} · {{ ucfirst($camp->campaign_type) }}</div>
                        </div>
                    </div>
                    @php [$mobStClr, $mobStPath] = $statusIconMap[$camp->status] ?? $statusIconMap['all']; @endphp
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-slate-100 text-slate-800 border border-slate-200 shrink-0">
                        <svg class="w-3 h-3 shrink-0 {{ $mobStClr }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $mobStPath }}"/></svg>
                        <span>{{ str_replace('_', ' ', $camp->status) }}</span>
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-2 p-3 rounded-2xl bg-slate-50/90 border border-slate-200/70 text-center text-xs">
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Impr.</div>
                        <div class="font-black text-slate-900 tabular-nums">{{ number_format($camp->total_impressions) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Clicks</div>
                        <div class="font-black text-slate-900 tabular-nums">{{ number_format($camp->total_clicks) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">CTR</div>
                        <div class="font-black text-[#F20B83] tabular-nums">{{ $camp->clickThroughRate() }}%</div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100">
                    @if($camp->status === 'active')
                        <button type="button" wire:click="updateCampaignStatus({{ $camp->id }}, 'paused')" class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 text-xs font-bold border border-amber-200 transition cursor-pointer">Pause</button>
                    @else
                        <button type="button" wire:click="updateCampaignStatus({{ $camp->id }}, 'active')" class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-900 text-xs font-bold border border-emerald-200 transition cursor-pointer">Activate</button>
                    @endif
                    <div class="flex items-center gap-1.5">
                        <button type="button" wire:click="openPreviewCampaignModal({{ $camp->id }})" class="px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-[#F20B83] text-xs font-bold border border-rose-200 transition cursor-pointer">Preview</button>
                        <button type="button" wire:click="openCampaignModal({{ $camp->id }})" class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold border border-slate-200 transition cursor-pointer">Edit</button>
                        <button type="button" wire:click="duplicateCampaign({{ $camp->id }})" class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold border border-slate-200 transition cursor-pointer">Clone</button>
                        <button type="button" wire:click="confirmDeleteCampaign({{ $camp->id }})" class="px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold border border-rose-200 transition cursor-pointer">Delete</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white/95 border border-slate-200/80 rounded-3xl p-8 text-center space-y-3 shadow-xs">
                <p class="text-sm font-black text-slate-900">
                    {{ $hasActiveFilters ? 'No Campaigns Match Filters' : 'No Campaigns Created Yet' }}
                </p>
                <div class="flex flex-wrap justify-center gap-2">
                    @if($hasActiveFilters)
                        <button type="button" wire:click="resetCampaignFilters" class="px-4 py-2 rounded-2xl bg-slate-950 text-white text-xs font-bold cursor-pointer">Reset Filters</button>
                    @endif
                    <button type="button" wire:click="openCampaignModal" class="px-4 py-2 rounded-2xl bg-gradient-to-r from-[#F20B83] to-rose-600 text-white text-xs font-bold cursor-pointer">Create Campaign</button>
                </div>
            </div>
        @endforelse
        @if(method_exists($campaigns, 'hasPages') && $campaigns->hasPages())
            <div class="pt-2">{{ $campaigns->links() }}</div>
        @endif
    </div>
</div>
