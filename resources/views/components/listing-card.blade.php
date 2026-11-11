@props([
    'listing',
    'variant' => 'auto',
    'eager' => false,
])

@php
    $cardPhoto = $listing->primaryPhoto
        ?: ($listing->relationLoaded('allMedia') ? $listing->allMedia->where('moderation_status', '!=', 'rejected')->first() : null)
        ?: ($listing->relationLoaded('media') ? $listing->media->first() : null);

    $photoCount = $listing->media_count
        ?? ($listing->relationLoaded('media')
            ? $listing->media->count()
            : ($listing->relationLoaded('approvedMedia')
                ? $listing->approvedMedia->count()
                : ($cardPhoto ? 1 : 0)));

    $isVerifiedPoster = (bool) $listing->hasVerifiedPoster();
    $isFeatured = $variant === 'featured' || ($variant === 'auto' && (bool) $listing->is_featured);
    $publishedAt = $listing->published_at ?: $listing->created_at;
    $isRecent = $variant === 'recent'
        || ($variant === 'auto' && !$isFeatured && $publishedAt && $publishedAt->greaterThanOrEqualTo(now()->subHours(24)));

    $locationLabel = collect([$listing->city?->name, $listing->state?->code ?? $listing->city?->state?->code])
        ->filter()
        ->implode(', ');

    $intentLabel = $listing->intent ? ucwords(str_replace('_', ' ', (string) $listing->intent)) : null;
    $categoryName = $listing->category?->name;
    $postedHuman = $listing->published_at?->diffForHumans() ?: ($listing->created_at?->diffForHumans() ?? 'Recently');

    // Resolve user's saved favorite IDs once per request (0 queries for guests, 1 query total per page for auth users)
    $favoritedMap = [];
    if (\Illuminate\Support\Facades\Auth::check() && app()->bound('request')) {
        $req = request();
        if (!$req->attributes->has('_ldh_user_favorite_ids')) {
            $ids = \Illuminate\Support\Facades\DB::table('listing_favorites')
                ->where('user_id', (int) \Illuminate\Support\Facades\Auth::id())
                ->pluck('listing_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $req->attributes->set('_ldh_user_favorite_ids', array_flip($ids));
        }
        $favoritedMap = (array) $req->attributes->get('_ldh_user_favorite_ids', []);
    }
    $isFavorited = isset($favoritedMap[(int) $listing->id]);

    $cardVariant = $isFeatured
        ? 'featured'
        : ($isVerifiedPoster ? 'verified' : ($isRecent ? 'recent' : 'standard'));

    $cardBorderClass = match ($cardVariant) {
        'featured' => 'border-[#F50057]/35 ring-1 ring-[#F50057]/10 hover:border-[#F50057]/60',
        'verified' => 'border-sky-200/90 hover:border-sky-300',
        default => 'border-[#E8ECF3] hover:border-[#F50057]/40',
    };
@endphp

<article
    class="ldh-listing-card group bg-white border {{ $cardBorderClass }} rounded-[16px] overflow-clip shadow-[0_4px_18px_rgba(20,30,50,0.06)] hover:shadow-[0_12px_28px_rgba(20,30,50,0.11)] hover:-translate-y-0.5 transition-all duration-300 flex flex-col justify-between h-full"
    data-listing-card="{{ $listing->id }}"
    data-card-variant="{{ $cardVariant }}"
>
    <div>
        {{-- Primary Visual Showcase (Consistent 4:5 Editorial Crop) --}}
        <div class="relative aspect-[4/5] w-full overflow-hidden bg-slate-100">
            @if($cardPhoto)
                <img
                    src="{{ $cardPhoto->thumbnail_url }}"
                    alt="{{ $listing->title }}"
                    loading="{{ $eager ? 'eager' : 'lazy' }}"
                    @if($eager) fetchpriority="high" @endif
                    decoding="async"
                    onerror="this.onerror=null;this.src='{{ asset('images/featured-1.jpg') }}';"
                    class="block w-full h-full object-cover object-center transition-transform duration-500 ease-out group-hover:scale-[1.04]"
                >
                <div class="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-slate-950/70 via-slate-950/20 to-transparent pointer-events-none"></div>
            @else
                <div class="w-full h-full flex flex-col items-center justify-center p-5 text-center bg-gradient-to-br from-rose-50/70 via-[#F7F8FC] to-slate-100">
                    <div class="w-12 h-12 rounded-2xl bg-white border border-[#E8ECF3] shadow-2xs flex items-center justify-center text-slate-400 mb-2.5">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-slate-600">Personal Classified</span>
                    <span class="text-[11px] text-slate-400 mt-0.5">No photo provided</span>
                </div>
            @endif

            {{-- Top-Left Trust & Spotlight Pills (Zero Collision Stack) --}}
            <div class="absolute top-3 left-3 flex flex-col items-start gap-1.5 max-w-[64%] pointer-events-none">
                @if($isFeatured)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-[#F50057] text-white shadow-sm">
                        <svg class="w-2.5 h-2.5 fill-current shrink-0" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <span>Featured</span>
                    </span>
                @elseif($isRecent)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-slate-900/80 text-white backdrop-blur-xs shadow-sm">
                        New
                    </span>
                @endif

                @if($isVerifiedPoster)
                    <x-verified-badge
                        variant="pill"
                        label="Verified"
                        class="!px-2.5 !py-0.5 !text-[10px] !gap-1 shadow-sm"
                        title="Identity Verified Member"
                    />
                @endif
            </div>

            {{-- Top-Right Multi-Photo Counter & Favorite Wishlist Toggle --}}
            <div class="absolute top-3 right-3 flex items-center gap-1.5 shrink-0">
                @if($photoCount > 1)
                    <span class="pointer-events-none inline-flex items-center gap-1 h-8 px-2.5 rounded-full text-[11px] font-bold bg-slate-950/70 text-white border border-white/15 backdrop-blur-xs">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ $photoCount }}</span>
                    </span>
                @endif

                <button
                    type="button"
                    x-data="{
                        favorited: {{ $isFavorited ? 'true' : 'false' }},
                        busy: false,
                        async toggle() {
                            if (this.busy) return;
                            this.busy = true;
                            const nextState = !this.favorited;
                            this.favorited = nextState;
                            try {
                                const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
                                const res = await fetch('{{ route('listings.favorite.toggle', $listing->id) }}', {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': token,
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                });
                                if (res.status === 401) {
                                    window.location.href = '{{ route('login') }}';
                                    return;
                                }
                                if (res.ok) {
                                    const data = await res.json();
                                    if (typeof data.favorited === 'boolean') {
                                        this.favorited = data.favorited;
                                    }
                                } else {
                                    this.favorited = !nextState;
                                }
                            } catch (e) {
                                this.favorited = !nextState;
                            } finally {
                                this.busy = false;
                            }
                        }
                    }"
                    @click.prevent.stop="toggle()"
                    :aria-pressed="favorited ? 'true' : 'false'"
                    aria-label="{{ $isFavorited ? 'Remove from favorites' : 'Save to favorites' }}"
                    title="Save to favorites"
                    class="w-9 h-9 rounded-full bg-white/95 hover:bg-white border border-slate-200/80 shadow-sm flex items-center justify-center transition-transform duration-150 hover:scale-105 active:scale-95 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#F50057] cursor-pointer"
                    :class="favorited ? 'text-[#F50057] border-rose-200' : 'text-slate-600 hover:text-[#F50057]'"
                >
                    <svg class="w-4 h-4 transition-transform duration-150" :class="favorited ? 'fill-[#F50057] scale-110' : 'fill-none'" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </button>
            </div>

            {{-- Bottom Image Location & Spotlight Strip --}}
            @if($locationLabel && $cardPhoto)
                <div class="absolute inset-x-3 bottom-2.5 flex items-center justify-between gap-2 pointer-events-none">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold text-white bg-slate-950/60 backdrop-blur-xs max-w-[85%] truncate">
                        <svg class="w-3.5 h-3.5 text-[#F50057] shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="truncate">{{ $locationLabel }}</span>
                    </span>
                </div>
            @endif
        </div>

        {{-- Listing Information Hierarchy --}}
        <div class="ldh-card-body p-4 space-y-2.5 min-w-0">
            {{-- Member Demographics & Category Pill --}}
            <div class="flex items-center justify-between gap-2 min-w-0">
                <div class="flex items-center gap-1.5 min-w-0 text-xs text-[#172033]">
                    <span class="font-bold truncate">
                        {{ $listing->poster_gender }}@if($listing->poster_age), Age {{ $listing->poster_age }}@endif
                    </span>
                    @if($isVerifiedPoster)
                        <x-verified-badge variant="icon" size="w-3.5 h-3.5" title="Identity Verified Member" />
                    @endif
                </div>
                @if($categoryName)
                    <span class="shrink-0 max-w-[48%] truncate px-2 py-0.5 rounded-md bg-[#F7F8FC] border border-[#E8ECF3] text-[10px] font-bold text-[#718096]">
                        {{ $categoryName }}
                    </span>
                @endif
            </div>

            {{-- Primary Listing Title (Accessible Stretched Card Link) --}}
            <h3 class="text-[15px] sm:text-base font-bold text-[#172033] group-hover:text-[#F50057] transition-colors line-clamp-2 leading-snug">
                <a href="{{ route('listings.show', $listing->slug) }}" class="ldh-card-link">
                    {{ $listing->title }}
                </a>
            </h3>

            {{-- Description Preview --}}
            @if(!empty($listing->description))
                <p class="text-xs sm:text-[13px] text-[#718096] font-normal line-clamp-2 leading-relaxed">
                    {{ $listing->description }}
                </p>
            @endif

            {{-- Location & Relationship Intent Meta Row --}}
            <div class="pt-1 flex items-center justify-between gap-2 text-[11px] text-[#718096] min-w-0">
                @if($locationLabel)
                    <span class="inline-flex items-center gap-1 font-semibold text-[#172033]/80 min-w-0 truncate">
                        <svg class="w-3.5 h-3.5 text-[#F50057] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="truncate">{{ $locationLabel }}</span>
                    </span>
                @else
                    <span></span>
                @endif

                @if($intentLabel)
                    <span class="shrink-0 font-semibold text-[#718096]">{{ $intentLabel }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Modern Card Footer: Timestamp + Independent Actions --}}
    <footer class="ldh-card-footer mt-auto px-4 py-3 bg-[#F7F8FC] border-t border-[#E8ECF3] flex items-center justify-between gap-2 text-xs">
        <span class="inline-flex items-center gap-1 font-medium text-[#718096] truncate min-w-0">
            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="truncate">{{ $postedHuman }}</span>
        </span>

        <div class="flex items-center gap-1.5 shrink-0">
            <a
                href="{{ route('listings.show', $listing->slug) }}#contact-member"
                class="ldh-card-contact-btn inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white hover:bg-rose-50 text-slate-600 hover:text-[#F50057] border border-[#E8ECF3] hover:border-rose-200 transition shadow-2xs"
                title="Contact Member"
                aria-label="Contact Member about {{ $listing->title }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </a>

            <a
                href="{{ route('listings.show', $listing->slug) }}"
                class="inline-flex items-center justify-center gap-1 min-h-[36px] px-3.5 py-1.5 rounded-xl bg-[#F50057] hover:bg-[#d90858] text-white font-bold text-xs shadow-xs transition-all"
            >
                <span>View Details<span class="sr-only"> for {{ $listing->title }}</span></span>
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </footer>
</article>
