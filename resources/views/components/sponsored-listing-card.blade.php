@props(['ad'])

<article class="group bg-white border-2 border-amber-200/90 hover:border-[#F20B65]/60 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between relative" data-ad-card="{{ $ad['campaign_id'] ?? $ad['legacy_id'] ?? 'ad' }}">
    <div>
        {{-- Creative Media Showcase (Hardware-Safe Full Uncut Image) --}}
        <div class="relative h-72 sm:h-80 w-full overflow-hidden bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 flex items-center justify-center isolate">
            @php
                $displayImage = $ad['image_url'] ?? $ad['desktop_banner_url'] ?? $ad['thumbnail_url'] ?? $ad['mobile_banner_url'] ?? null;
            @endphp

            @if(!empty($ad['html_snippet']))
                <div class="w-full h-full flex items-center justify-center p-3 bg-white overflow-hidden">
                    {!! $ad['html_snippet'] !!}
                </div>
            @elseif($displayImage)
                <a href="{{ $ad['click_url'] }}" target="_blank" rel="sponsored noopener noreferrer" class="w-full h-full flex items-center justify-center relative block">
                    <img
                        src="{{ $displayImage }}"
                        alt="{{ $ad['alt_text'] ?? $ad['title'] }}"
                        loading="lazy"
                        decoding="async"
                        onerror="this.onerror=null;this.src='{{ asset('images/featured-1.jpg') }}';"
                        class="max-h-full max-w-full w-auto h-auto object-contain mx-auto block"
                    />
                </a>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/65 via-transparent to-transparent pointer-events-none"></div>
            @else
                <a href="{{ $ad['click_url'] }}" target="_blank" rel="sponsored noopener noreferrer" class="w-full h-full flex flex-col items-center justify-center p-6 text-center bg-gradient-to-br from-amber-50 via-white to-pink-50">
                    <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-black uppercase tracking-wider mb-3">
                        {{ $ad['disclosure_label'] ?? 'Sponsored' }}
                    </span>
                    <span class="text-base font-black text-slate-900 line-clamp-2">{{ $ad['title'] }}</span>
                    <span class="text-xs font-semibold text-slate-600 mt-1">{{ $ad['advertiser_name'] ?? 'Partner Offer' }}</span>
                </a>
            @endif

            {{-- Top Promotional Disclosure Badges (Always Unmistakably Labeled) --}}
            <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between gap-2 z-20 pointer-events-none">
                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-amber-400 text-slate-950 shadow-sm border border-amber-300">
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
        <div class="p-4 space-y-2">
            <div class="flex items-center gap-2 text-[11px] font-bold text-amber-800">
                <span class="uppercase tracking-wider">{{ $ad['format'] === 'affiliate' ? 'Partner Offer' : 'Promoted Ad' }}</span>
                <span>&bull;</span>
                <span class="text-slate-500 truncate">{{ $ad['advertiser_name'] ?? 'Partner' }}</span>
            </div>

            <h3 class="text-sm sm:text-base font-extrabold text-slate-900 group-hover:text-[#F20B65] transition line-clamp-1 leading-snug">
                <a href="{{ $ad['click_url'] }}" target="_blank" rel="sponsored noopener noreferrer" class="focus:outline-none focus:underline">
                    {{ $ad['title'] }}
                </a>
            </h3>

            @if(!empty($ad['description']))
                <p class="text-xs text-slate-600 font-normal line-clamp-2 leading-relaxed">
                    {{ $ad['description'] }}
                </p>
            @endif
        </div>
    </div>

    {{-- Footer CTA --}}
    <div class="px-4 py-3 bg-amber-50/50 border-t border-amber-200/70 flex items-center justify-between text-xs">
        <span class="font-bold text-amber-900 text-[11px]">{{ $ad['disclosure_label'] ?? 'Sponsored' }}</span>
        <a
            href="{{ $ad['click_url'] }}"
            target="_blank"
            rel="sponsored noopener noreferrer"
            class="px-3.5 py-1.5 rounded-xl bg-[#F20B65] hover:bg-[#d90858] text-white font-black text-xs flex items-center space-x-1 transition-all shadow-xs"
        >
            <span>{{ $ad['cta_text'] ?? 'Visit Offer' }}</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
    </div>
</article>

