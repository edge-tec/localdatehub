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
    $eagerLoad = in_array($placement, ['homepage_top', 'homepage_featured'], true);
@endphp

@if($resolvedAds->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'ldh-ad-zone ' . $class]) }} data-ad-placement="{{ $placement }}">
        <div class="{{ $variant === 'sidebar' ? 'space-y-4' : 'space-y-3' }}">
            @foreach($resolvedAds as $ad)
                @php
                    $desktopImg = $ad['desktop_banner_url'] ?: $ad['image_url'] ?: $ad['thumbnail_url'];
                    $mobileImg = $ad['mobile_banner_url'] ?: $desktopImg;
                    $hasVisualBanner = !empty($desktopImg) || !empty($mobileImg);
                @endphp
                <div
                    class="relative rounded-2xl border border-amber-200/90 bg-white overflow-hidden shadow-xs hover:shadow-md transition-all group"
                    data-ad-campaign-uuid="{{ $ad['campaign_uuid'] ?? '' }}"
                    data-ad-creative-id="{{ $ad['creative_id'] ?? '' }}"
                    data-ad-slot="{{ $placement }}"
                >
                    {{-- Clear Promotional Disclosure Pill --}}
                    <div class="flex items-center justify-between px-3 py-1.5 bg-amber-50/90 border-b border-amber-200/70 text-[10px]">
                        <span class="inline-flex items-center gap-1.5 font-black uppercase tracking-wider text-amber-900">
                            <svg class="w-3 h-3 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            <span>{{ $ad['disclosure_label'] ?? 'Sponsored' }}</span>
                        </span>
                        <div class="flex items-center gap-1.5">
                            @if(!empty($ad['offer_badge']))
                                <span class="px-1.5 py-0.5 rounded bg-[#F20B65] text-white font-black uppercase tracking-wide text-[9px]">
                                    {{ $ad['offer_badge'] }}
                                </span>
                            @endif
                            <span class="text-slate-600 font-semibold truncate max-w-[160px]">{{ $ad['advertiser_name'] ?? 'Partner' }}</span>
                        </div>
                    </div>

                    @if(!empty($ad['html_snippet']))
                        <div class="p-2 flex items-center justify-center overflow-hidden">
                            {!! $ad['html_snippet'] !!}
                        </div>
                    @elseif($hasVisualBanner)
                        <a
                            href="{{ $ad['click_url'] }}"
                            target="_blank"
                            rel="sponsored noopener noreferrer"
                            class="block relative bg-slate-950/95 overflow-hidden"
                        >
                            @if($desktopImg)
                                <img
                                    src="{{ $desktopImg }}"
                                    alt="{{ $ad['alt_text'] ?? $ad['title'] }}"
                                    loading="{{ $eagerLoad ? 'eager' : 'lazy' }}"
                                    decoding="async"
                                    onerror="this.style.display='none';var fb=this.closest('a')?.nextElementSibling?.querySelector('.ldh-banner-fallback');if(fb){fb.classList.remove('hidden');}"
                                    class="{{ $mobileImg && $mobileImg !== $desktopImg ? 'hidden md:block' : 'block' }} w-full h-auto {{ $variant === 'sidebar' ? 'max-h-[420px]' : 'max-h-[220px]' }} object-contain mx-auto transition duration-300 group-hover:scale-[1.01]"
                                />
                            @endif
                            @if($mobileImg && $mobileImg !== $desktopImg)
                                <img
                                    src="{{ $mobileImg }}"
                                    alt="{{ $ad['alt_text'] ?? $ad['title'] }}"
                                    loading="{{ $eagerLoad ? 'eager' : 'lazy' }}"
                                    decoding="async"
                                    onerror="this.style.display='none';"
                                    class="block md:hidden w-full h-auto max-h-[200px] object-contain mx-auto"
                                />
                            @endif
                        </a>

                        @if(!empty($ad['title']) || !empty($ad['description']) || !empty($ad['cta_text']))
                            <div class="px-3.5 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white">
                                <div class="min-w-0">
                                    <a href="{{ $ad['click_url'] }}" target="_blank" rel="sponsored noopener noreferrer" class="text-xs sm:text-sm font-black text-slate-900 hover:text-[#F20B65] transition block truncate">
                                        {{ $ad['title'] }}
                                    </a>
                                    @if(!empty($ad['description']))
                                        <p class="text-[11px] text-slate-600 line-clamp-1 mt-0.5">{{ $ad['description'] }}</p>
                                    @endif
                                    <span class="ldh-banner-fallback hidden text-[10px] font-bold text-amber-800">Sponsored Partner Offer</span>
                                </div>
                                <a
                                    href="{{ $ad['click_url'] }}"
                                    target="_blank"
                                    rel="sponsored noopener noreferrer"
                                    class="shrink-0 inline-flex items-center justify-center px-3.5 py-1.5 rounded-xl bg-[#F20B65] hover:bg-[#d90858] text-white font-black text-xs shadow-2xs transition"
                                >
                                    {{ $ad['cta_text'] ?? 'Learn More' }} &rarr;
                                </a>
                            </div>
                        @endif
                    @else
                        {{-- Text / Affiliate Native Strip --}}
                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-r from-amber-50/40 via-white to-pink-50/40">
                            <div class="min-w-0 space-y-1">
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
                                class="shrink-0 inline-flex items-center justify-center px-4 py-2 rounded-xl bg-[#F20B65] hover:bg-[#d90858] text-white font-black text-xs shadow-sm transition"
                            >
                                {{ $ad['cta_text'] ?? 'Claim Offer' }} &rarr;
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
