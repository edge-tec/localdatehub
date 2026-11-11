@props([
    'placement' => 'sidebar',
    'context' => [],
    'limit' => 1,
    'variant' => 'leaderboard',
    'class' => '',
])

@php
    $ads = collect();
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('ad_campaigns')) {
            $ads = app(\App\Modules\Advertising\Services\AdDeliveryService::class)
                ->getAdsForSlot($placement, is_array($context) ? $context : [], (int) $limit, true);
        }
    } catch (\Throwable $e) {
        $ads = collect();
    }
@endphp

@if($ads->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'ldh-ad-zone ' . $class]) }} data-ad-placement="{{ $placement }}">
        <div class="{{ $variant === 'sidebar' ? 'space-y-4' : 'space-y-3' }}">
            @foreach($ads as $ad)
                <div class="relative rounded-2xl border border-amber-200/90 bg-white overflow-hidden shadow-xs hover:shadow-md transition-all group">
                    {{-- Clear Promotional Disclosure Pill --}}
                    <div class="flex items-center justify-between px-3 py-1.5 bg-amber-50/90 border-b border-amber-200/70 text-[10px]">
                        <span class="inline-flex items-center gap-1 font-black uppercase tracking-wider text-amber-900">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            {{ $ad['disclosure_label'] ?? 'Sponsored' }}
                        </span>
                        <div class="flex items-center gap-1.5">
                            @if(!empty($ad['offer_badge']))
                                <span class="px-1.5 py-0.5 rounded bg-[#F20B65] text-white font-black uppercase tracking-wide text-[9px]">
                                    {{ $ad['offer_badge'] }}
                                </span>
                            @endif
                            <span class="text-slate-500 font-semibold truncate max-w-[140px]">{{ $ad['advertiser_name'] ?? 'Partner' }}</span>
                        </div>
                    </div>

                    @if(!empty($ad['html_snippet']))
                        <div class="p-2 flex items-center justify-center overflow-hidden">
                            {!! $ad['html_snippet'] !!}
                        </div>
                    @elseif(!empty($ad['desktop_banner_url']) || !empty($ad['mobile_banner_url']) || !empty($ad['image_url']))
                        <a
                            href="{{ $ad['click_url'] }}"
                            target="_blank"
                            rel="sponsored noopener noreferrer"
                            class="block relative bg-slate-950/95 overflow-hidden"
                        >
                            @php
                                $desktopImg = $ad['desktop_banner_url'] ?: $ad['image_url'];
                                $mobileImg = $ad['mobile_banner_url'] ?: $desktopImg;
                            @endphp
                            @if($desktopImg)
                                <img
                                    src="{{ $desktopImg }}"
                                    alt="{{ $ad['alt_text'] ?? $ad['title'] }}"
                                    loading="lazy"
                                    class="{{ $mobileImg && $mobileImg !== $desktopImg ? 'hidden md:block' : 'block' }} w-full {{ $variant === 'sidebar' ? 'max-h-[420px]' : 'max-h-[160px]' }} object-contain mx-auto transition duration-300 group-hover:scale-[1.01]"
                                />
                            @endif
                            @if($mobileImg && $mobileImg !== $desktopImg)
                                <img
                                    src="{{ $mobileImg }}"
                                    alt="{{ $ad['alt_text'] ?? $ad['title'] }}"
                                    loading="lazy"
                                    class="block md:hidden w-full max-h-[140px] object-contain mx-auto"
                                />
                            @endif
                        </a>

                        @if(!empty($ad['title']) || !empty($ad['description']))
                            <div class="px-3.5 py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white">
                                <div class="min-w-0">
                                    <a href="{{ $ad['click_url'] }}" target="_blank" rel="sponsored noopener noreferrer" class="text-xs sm:text-sm font-black text-slate-900 hover:text-[#F20B65] transition block truncate">
                                        {{ $ad['title'] }}
                                    </a>
                                    @if(!empty($ad['description']))
                                        <p class="text-[11px] text-slate-600 line-clamp-1 mt-0.5">{{ $ad['description'] }}</p>
                                    @endif
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

