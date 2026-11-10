@props(['listing'])

<article class="group bg-white border border-slate-200 hover:border-slate-300 rounded-2xl overflow-hidden transition-all duration-200 flex flex-col justify-between shadow-xs hover:shadow-lg hover:-translate-y-0.5">
    <div>
        <!-- Photo Container -->
        <div class="relative aspect-video bg-slate-50 overflow-hidden">
            @php
                $cardPhoto = $listing->primaryPhoto ?: $listing->allMedia->first();
            @endphp
            @if($cardPhoto)
                <img
                    src="{{ $cardPhoto->url }}"
                    alt="{{ $listing->title }}"
                    loading="lazy"
                    class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                >
                <!-- Photo Bottom Scrim for Readability -->
                <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-slate-950/80 via-slate-950/30 to-transparent pointer-events-none"></div>
            @else
                <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 bg-slate-100/60">
                    <svg class="w-10 h-10 mb-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-[11px] font-semibold text-slate-500">Text-Only Classified</span>
                </div>
            @endif

            <!-- Top Badges Overlay -->
            <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between pointer-events-none">
                <div class="flex items-center space-x-1.5">
                    <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-black bg-white/95 text-[#F20B65] border border-slate-200/80 shadow-xs backdrop-blur-xs">
                        {{ $listing->category?->name ?: 'Personal' }}
                    </span>
                    @if($listing->is_verified_poster)
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-blue-600 text-white shadow-xs backdrop-blur-xs flex items-center gap-1" title="Identity Verified Poster">
                            <svg class="w-3 h-3 text-white fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span>Verified</span>
                        </span>
                    @endif
                </div>
                @if($listing->is_featured)
                    <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-black bg-[#F20B65] text-white shadow-xs">
                        ★ Featured
                    </span>
                @endif
            </div>

            <!-- Distance / City Overlay -->
            <div class="absolute bottom-2 left-2.5 right-2.5 flex items-center justify-between text-[11px] {{ $listing->primaryPhoto ? 'text-white' : 'text-slate-700' }} pointer-events-none">
                <span class="font-bold flex items-center gap-1 {{ $listing->primaryPhoto ? 'drop-shadow-sm' : '' }}">
                    <svg class="w-3.5 h-3.5 text-[#F20B65] shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                    <span>{{ $listing->city?->name }}, {{ $listing->state?->code }}</span>
                </span>
                @if($listing->has_photos)
                    <span class="flex items-center gap-1 text-[10px] font-bold {{ $listing->primaryPhoto ? 'bg-black/60 text-white border-white/20' : 'bg-slate-200 text-slate-800 border-slate-300' }} px-2 py-0.5 rounded-lg border backdrop-blur-xs">
                        <svg class="w-3 h-3 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>{{ $listing->media->count() }}</span>
                    </span>
                @endif
            </div>
        </div>

        <!-- Body Details -->
        <div class="p-4 space-y-2.5">
            <div class="flex items-center space-x-2 text-xs text-slate-500 font-semibold">
                <span class="font-extrabold text-slate-900">{{ $listing->poster_gender }}, Age {{ $listing->poster_age }}</span>
                <span>&bull;</span>
                <span class="capitalize text-slate-600 font-medium">{{ str_replace('_', ' ', $listing->intent) }}</span>
            </div>

            <h3 class="text-sm sm:text-base font-extrabold text-slate-900 group-hover:text-[#F20B65] transition line-clamp-1 leading-snug">
                <a href="{{ route('listings.show', $listing->slug) }}" class="focus:outline-none focus:underline">
                    {{ $listing->title }}
                </a>
            </h3>

            <p class="text-xs text-slate-600 font-normal line-clamp-2 leading-relaxed">
                {{ $listing->description }}
            </p>
        </div>
    </div>

    <!-- Footer Stats / CTA -->
    <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs">
        <span class="font-semibold text-slate-500">{{ $listing->published_at?->diffForHumans() ?: $listing->created_at->diffForHumans() }}</span>
        <a
            href="{{ route('listings.show', $listing->slug) }}"
            class="px-3 py-1.5 rounded-xl bg-pink-50 hover:bg-[#F20B65] text-[#F20B65] hover:text-white font-black text-xs flex items-center space-x-1 transition-all shadow-2xs group-hover:bg-[#F20B65] group-hover:text-white"
        >
            <span>View Ad</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>
</article>

