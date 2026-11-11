@props(['ad'])

<article
    class="ldh-listing-card group bg-white border border-[#E8ECF3] hover:border-[#F50057]/45 rounded-[16px] overflow-clip shadow-[0_4px_18px_rgba(20,30,50,0.06)] hover:shadow-[0_12px_28px_rgba(20,30,50,0.11)] hover:-translate-y-0.5 transition-all duration-300 flex flex-col justify-between h-full"
    data-ad-card="{{ $ad['campaign_id'] ?? $ad['legacy_id'] ?? 'ad' }}"
    data-ad-campaign-uuid="{{ $ad['campaign_uuid'] ?? '' }}"
    data-ad-creative-id="{{ $ad['creative_id'] ?? '' }}"
    data-ad-slot="{{ $ad['slot'] ?? '' }}"
>
    <div>
        {{-- Creative Media Showcase (Hardware-Safe Full Uncut Responsive Image, 4:5 Alignment) --}}
        <div class="relative aspect-[4/5] w-full overflow-hidden bg-slate-950 flex items-center justify-center isolate">
            @php
                $desktopCardImg = $ad['desktop_banner_url'] ?: ($ad['thumbnail_url'] ?: ($ad['image_url'] ?: $ad['mobile_banner_url']));
                $mobileCardImg = $ad['mobile_banner_url'] ?: $desktopCardImg;
                $displayImage = $desktopCardImg ?: $mobileCardImg;
            @endphp

            @if(!empty($ad['html_snippet']))
                <div class="w-full h-full flex items-center justify-center p-3 bg-white overflow-hidden">
                    {!! $ad['html_snippet'] !!}
                </div>
            @elseif($displayImage)
                <a href="{{ $ad['click_url'] }}" target="_blank" rel="sponsored noopener noreferrer" class="w-full h-full flex items-center justify-center relative block">
                    @if($desktopCardImg)
                        <img
                            src="{{ $desktopCardImg }}"
                            alt="{{ $ad['alt_text'] ?? $ad['title'] }}"
                            loading="lazy"
                            decoding="async"
                            onerror="this.onerror=null;this.src='{{ asset('images/featured-1.jpg') }}';"
                            class="{{ $mobileCardImg && $mobileCardImg !== $desktopCardImg ? 'hidden md:block' : 'block' }} max-h-full max-w-full w-auto h-auto object-contain mx-auto"
                        />
                    @endif
                    @if($mobileCardImg && $mobileCardImg !== $desktopCardImg)
                        <img
                            src="{{ $mobileCardImg }}"
                            alt="{{ $ad['alt_text'] ?? $ad['title'] }}"
                            loading="lazy"
                            decoding="async"
                            onerror="this.onerror=null;this.src='{{ asset('images/featured-1.jpg') }}';"
                            class="block md:hidden max-h-full max-w-full w-auto h-auto object-contain mx-auto"
                        />
                    @endif
                </a>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent pointer-events-none"></div>
            @else
                <a href="{{ $ad['click_url'] }}" target="_blank" rel="sponsored noopener noreferrer" class="w-full h-full flex flex-col items-center justify-center p-6 text-center bg-gradient-to-br from-slate-50 via-white to-rose-50">
                    <span class="px-3 py-1 rounded-full bg-slate-200/80 text-slate-800 border border-slate-300 text-[10px] font-black uppercase tracking-wider mb-3">
                        {{ $ad['disclosure_label'] ?? 'Sponsored' }}
                    </span>
                    <span class="text-base font-black text-slate-900 line-clamp-2">{{ $ad['title'] }}</span>
                    <span class="text-xs font-semibold text-slate-600 mt-1">{{ $ad['advertiser_name'] ?? 'Partner Offer' }}</span>
                </a>
            @endif

            {{-- Top Promotional Disclosure Badges (High Contrast IAB Compliant) --}}
            <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between gap-2 z-20 pointer-events-none">
                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-slate-900/85 text-white shadow-sm border border-white/15 backdrop-blur-xs">
                    {{ $ad['disclosure_label'] ?? 'Sponsored' }}
                </span>

                @if(!empty($ad['offer_badge']))
                    <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-black bg-[#F20B65] text-white shadow-sm">
                        {{ $ad['offer_badge'] }}
                    </span>
                @endif
            </div>

            {{-- Bottom Advertiser Brand Pill --}}
            <div class="absolute bottom-2 left-2.5 right-2.5 flex items-center justify-between text-[11px] text-white z-20 pointer-events-none">
                <span class="font-bold bg-black/65 px-2.5 py-0.5 rounded-lg border border-white/15 backdrop-blur-xs truncate max-w-[75%]">
                    {{ $ad['advertiser_name'] ?? 'Featured Partner' }}
                </span>
                @if(!empty($ad['offer_expires_at']))
                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-rose-600/90 text-white">
                        Ends {{ \Illuminate\Support\Carbon::parse($ad['offer_expires_at'])->format('M j') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Body Details --}}
        <div class="ldh-card-body p-4 space-y-2 min-w-0">
            <div class="flex items-center gap-2 min-w-0 text-[11px] font-bold text-[#F50057]">
                <span class="uppercase tracking-wider shrink-0">{{ ($ad['format'] ?? 'banner') === 'affiliate' ? 'Partner Offer' : 'Promoted Ad' }}</span>
                <span class="text-slate-300">/</span>
                <span class="text-[#718096] truncate min-w-0">{{ $ad['advertiser_name'] ?? 'Partner' }}</span>
            </div>

            <h3 class="text-[15px] sm:text-base font-bold text-[#172033] group-hover:text-[#F50057] transition-colors line-clamp-2 leading-snug">
                <a href="{{ $ad['click_url'] }}" target="_blank" rel="sponsored noopener noreferrer" class="ldh-card-link">
                    {{ $ad['title'] }}
                </a>
            </h3>

            @if(!empty($ad['description']))
                <p class="text-xs sm:text-[13px] text-[#718096] font-normal line-clamp-2 leading-relaxed">
                    {{ $ad['description'] }}
                </p>
            @endif
        </div>
    </div>

    {{-- Footer CTA --}}
    <footer class="ldh-card-footer mt-auto px-4 py-3 bg-[#F7F8FC] border-t border-[#E8ECF3] flex items-center justify-between gap-2 text-xs">
        <span class="font-bold text-[#718096] text-[11px] uppercase tracking-wider truncate">{{ $ad['disclosure_label'] ?? 'Sponsored' }}</span>
        <a
            href="{{ $ad['click_url'] }}"
            target="_blank"
            rel="sponsored noopener noreferrer"
            class="shrink-0 min-h-[36px] px-3.5 py-1.5 rounded-xl bg-[#F50057] hover:bg-[#d90858] text-white font-bold text-xs inline-flex items-center space-x-1 transition-all shadow-xs"
        >
            <span>{{ $ad['cta_text'] ?? 'Visit Offer' }}</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
    </footer>
</article>
