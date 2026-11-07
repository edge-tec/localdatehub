@props(['listing'])

<article class="group bg-slate-900/80 border border-slate-800 hover:border-slate-700 rounded-2xl overflow-hidden transition duration-200 flex flex-col justify-between shadow-lg hover:shadow-2xl hover:shadow-rose-950/20">
    <div>
        <!-- Photo Container -->
        <div class="relative aspect-video bg-slate-950 overflow-hidden">
            @if($listing->primaryPhoto)
                <img
                    src="{{ asset('storage/' . ($listing->primaryPhoto->thumbnail_path ?: $listing->primaryPhoto->file_path)) }}"
                    alt="{{ $listing->title }}"
                    loading="lazy"
                    class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                >
            @else
                <div class="w-full h-full flex flex-col items-center justify-center text-slate-700 bg-slate-950">
                    <svg class="w-10 h-10 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-[11px] font-medium text-slate-500">Text-Only Classified</span>
                </div>
            @endif

            <!-- Top Badges Overlay -->
            <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between pointer-events-none">
                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-900/90 text-rose-300 border border-slate-700/80 backdrop-blur-md">
                    {{ $listing->category?->name ?: 'Personal' }}
                </span>
                @if($listing->is_featured)
                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-gradient-to-r from-amber-500 to-rose-500 text-white shadow">
                        Featured
                    </span>
                @endif
            </div>

            <!-- Distance / City Overlay -->
            <div class="absolute bottom-2 left-2.5 right-2.5 flex items-center justify-between text-[11px] text-white/90 drop-shadow pointer-events-none">
                <span class="font-semibold flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-rose-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                    <span>{{ $listing->city?->name }}, {{ $listing->state?->code }}</span>
                </span>
                @if($listing->has_photos)
                    <span class="flex items-center gap-1 text-[10px] bg-slate-950/80 px-1.5 py-0.5 rounded-md text-slate-300 border border-slate-800">
                        <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>{{ $listing->media->count() }}</span>
                    </span>
                @endif
            </div>
        </div>

        <!-- Body Details -->
        <div class="p-4 space-y-2.5">
            <div class="flex items-center space-x-2 text-xs text-slate-400">
                <span class="font-bold text-white">{{ $listing->poster_gender }}, Age {{ $listing->poster_age }}</span>
                <span>&bull;</span>
                <span class="capitalize text-slate-300">{{ str_replace('_', ' ', $listing->intent) }}</span>
            </div>

            <h3 class="text-sm sm:text-base font-bold text-white group-hover:text-rose-400 transition line-clamp-1 leading-snug">
                <a href="{{ route('listings.show', $listing->slug) }}" class="focus:outline-none focus:underline">
                    {{ $listing->title }}
                </a>
            </h3>

            <p class="text-xs text-slate-300 line-clamp-2 leading-relaxed">
                {{ $listing->description }}
            </p>
        </div>
    </div>

    <!-- Footer Stats / CTA -->
    <div class="px-4 py-3 bg-slate-950/60 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
        <span>{{ $listing->published_at?->diffForHumans() ?: $listing->created_at->diffForHumans() }}</span>
        <a
            href="{{ route('listings.show', $listing->slug) }}"
            class="text-rose-400 hover:text-rose-300 font-semibold flex items-center space-x-1"
        >
            <span>View Ad</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>
</article>
