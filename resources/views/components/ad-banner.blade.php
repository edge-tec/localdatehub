@props([
    'placement' => 'sidebar',
    'context' => [],
    'limit' => 1,
    'variant' => 'leaderboard',
    'ads' => null,
    'class' => '',
])

@php
    $resolvedAds = collect();
    if ($ads !== null) {
        $resolvedAds = collect($ads);
    } else {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('ad_campaigns')) {
                $resolvedAds = app(\App\Modules\Advertising\Services\AdDeliveryService::class)
                    ->getAdsForSlot($placement, is_array($context) ? $context : [], (int) $limit, true);
            }
        } catch (\Throwable $e) {
            $resolvedAds = collect();
        }
    }
    $eagerLoad = in_array($placement, [
        'homepage_top',
        'homepage_featured',
        'search_results',
        'listing_detail',
        'category_page',
        'location_page',
    ], true);
    $isSidebar = $variant === 'sidebar';
@endphp

@if($resolvedAds->isNotEmpty())
    <aside
        {{ $attributes->merge(['class' => 'ldh-ad-zone w-full ' . $class]) }}
        data-ad-placement="{{ $placement }}"
        aria-label="Sponsored advertisement"
    >
        <div class="{{ $isSidebar ? 'space-y-4' : 'space-y-3' }}">
            @foreach($resolvedAds as $ad)
                @php
                    $desktopImg = $ad['desktop_banner_url'] ?: $ad['image_url'] ?: $ad['thumbnail_url'];
                    $mobileImg = $ad['mobile_banner_url'] ?: $desktopImg;
                    $hasVisualBanner = !empty($desktopImg) || !empty($mobileImg);
                @endphp
                <div
                    class="rounded-2xl border border-slate-200/90 hover:border-slate-300 bg-white overflow-clip shadow-xs hover:shadow-md transition-all group"
                    data-ad-campaign-uuid="{{ $ad['campaign_uuid'] ?? '' }}"
                    data-ad-creative-id="{{ $ad['creative_id'] ?? '' }}"
                    data-ad-slot="{{ $placement }}"
                >
                    {{-- Standard IAB Sponsored Disclosure Header (Zero Dots, Clean Typography) --}}
                    <div class="flex items-center justify-between gap-2 px-3.5 py-2 bg-slate-50 border-b border-slate-100 text-[10px]">
                        <span class="inline-flex items-center gap-1.5 font-extrabold uppercase tracking-wider text-slate-600 shrink-0">
                            <span class="px-1.5 py-0.5 rounded bg-slate-200/80 text-slate-700 text-[9px] font-black tracking-wider">
                                {{ $ad['disclosure_label'] ?? 'Sponsored' }}
                            </span>
                        </span>
                        <div class="flex items-center gap-1.5 min-w-0">
                            @if(!empty($ad['offer_badge']))
                                <span class="px-1.5 py-0.5 rounded bg-[#F20B65] text-white font-black uppercase tracking-wide text-[9px] shrink-0">
                                    {{ $ad['offer_badge'] }}
                                </span>
                            @endif
                            <span class="text-slate-500 font-semibold truncate">{{ $ad['advertiser_name'] ?? 'Partner' }}</span>
                        </div>
                    </div>

                    @if(!empty($ad['html_snippet']))
                        <div class="p-2.5 flex items-center justify-center bg-white">
                            {!! $ad['html_snippet'] !!}
                        </div>
                    @elseif($hasVisualBanner)
                        <a
                            href="{{ $ad['click_url'] }}"
                            target="_blank"
                            rel="sponsored noopener noreferrer"
                            class="block w-full bg-slate-950"
                        >
                            @if($desktopImg)
                                <img
                                    src="{{ $desktopImg }}"
                                    alt="{{ $ad['alt_text'] ?? $ad['title'] }}"
                                    loading="{{ $eagerLoad ? 'eager' : 'lazy' }}"
                                    @if($eagerLoad) fetchpriority="high" @endif
                                    decoding="async"
                                    onerror="this.style.display='none';var fb=this.closest('.group')?.querySelector('.ldh-banner-fallback');if(fb){fb.classList.remove('hidden');}"
                                    class="{{ $mobileImg && $mobileImg !== $desktopImg ? 'hidden md:block' : 'block' }} w-full h-auto {{ $isSidebar ? 'max-h-[420px]' : 'max-h-[240px]' }} object-contain mx-auto transition duration-300 group-hover:opacity-95"
                                />
                            @endif
                            @if($mobileImg && $mobileImg !== $desktopImg)
                                <img
                                    src="{{ $mobileImg }}"
                                    alt="{{ $ad['alt_text'] ?? $ad['title'] }}"
                                    loading="{{ $eagerLoad ? 'eager' : 'lazy' }}"
                                    @if($eagerLoad) fetchpriority="high" @endif
                                    decoding="async"
                                    onerror="this.style.display='none';"
                                    class="block md:hidden w-full h-auto max-h-[220px] object-contain mx-auto"
                                />
                            @endif
                        </a>

                        @if(!empty($ad['title']) || !empty($ad['description']) || !empty($ad['cta_text']))
                            <div class="px-3.5 py-2.5 bg-white border-t border-slate-100 {{ $isSidebar ? 'flex flex-col gap-2.5' : 'flex flex-col sm:flex-row sm:items-center justify-between gap-2.5' }}">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ $ad['click_url'] }}" target="_blank" rel="sponsored noopener noreferrer" class="text-xs sm:text-sm font-black text-slate-900 hover:text-[#F20B65] transition block truncate">
                                        {{ $ad['title'] }}
                                    </a>
                                    @if(!empty($ad['description']))
                                        <p class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">{{ $ad['description'] }}</p>
                                    @endif
                                    <span class="ldh-banner-fallback hidden text-[10px] font-bold text-slate-700">Sponsored Partner Offer</span>
                                </div>
                                <a
                                    href="{{ $ad['click_url'] }}"
                                    target="_blank"
                                    rel="sponsored noopener noreferrer"
                                    class="shrink-0 inline-flex items-center justify-center {{ $isSidebar ? 'w-full py-2' : 'px-3.5 py-1.5' }} px-3.5 rounded-xl bg-[#F20B65] hover:bg-[#d90858] text-white font-black text-xs shadow-2xs transition"
                                >
                                    {{ $ad['cta_text'] ?? 'Visit Site' }} &rarr;
                                </a>
                            </div>
                        @endif
                    @else
                        {{-- Text / Affiliate Native Unit --}}
                        <div class="p-4 flex {{ $isSidebar ? 'flex-col gap-3' : 'flex-col sm:flex-row sm:items-center justify-between gap-3' }} bg-gradient-to-r from-slate-50 via-white to-rose-50/40">
                            <div class="min-w-0 flex-1 space-y-1">
                                <a href="{{ $ad['click_url'] }}" target="_blank" rel="sponsored noopener noreferrer" class="text-sm font-black text-slate-900 hover:text-[#F20B65] transition block">
                                    {{ $ad['title'] }}
                                </a>
                                @if(!empty($ad['description']))
                                    <p class="text-xs text-slate-600 line-clamp-2">{{ $ad['description'] }}</p>
                                @endif
                            </div>
                            <a
                                href="{{ $ad['click_url'] }}"
                                target="_blank"
                                rel="sponsored noopener noreferrer"
                                class="shrink-0 inline-flex items-center justify-center {{ $isSidebar ? 'w-full' : '' }} px-4 py-2 rounded-xl bg-[#F20B65] hover:bg-[#d90858] text-white font-black text-xs shadow-sm transition"
                            >
                                {{ $ad['cta_text'] ?? 'Claim Offer' }} &rarr;
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </aside>
@endif
