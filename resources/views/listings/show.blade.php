<x-layouts.app
    :title="$seo->title ?? ($listing->title . ' — ' . ($listing->city?->name ? $listing->city->name . ', ' : '') . ($listing->state?->code ?? 'USA') . ' | LocalDateHub')"
    :description="$seo->description ?? null"
    :canonicalUrl="$seo->canonicalUrl ?? null"
    :structuredData="$seo->structuredData ?? null"
    :ogImage="$seo->ogImage ?? null"
>
    @php
        $posterVerified = $listing->hasVerifiedPoster();
        $displayPhotos = $listing->allMedia->where('moderation_status', '!=', 'rejected')->values();
        $galleryCount = $displayPhotos->isNotEmpty() ? $displayPhotos->count() : 1;
        $showcaseIndex = (($listing->id % 4) + 1);
        $fallbackShowcaseImg = asset('images/featured-' . $showcaseIndex . '.jpg');
        $hasUploadedPhotos = $displayPhotos->isNotEmpty();
        $galleryItems = $hasUploadedPhotos
            ? $displayPhotos->map(fn ($p) => [
                'url' => $p->url,
                'thumb' => $p->thumbnail_url ?: $p->url,
                'isPrimary' => (bool) $p->is_primary,
            ])->values()->all()
            : [[
                'url' => $fallbackShowcaseImg,
                'thumb' => $fallbackShowcaseImg,
                'isPrimary' => true,
            ]];
    @endphp
    <div
        class="py-6 sm:py-10 pb-24 lg:pb-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8"
        x-data="{
            replyModalOpen: false,
            reportModalOpen: false,
            activePhotoIdx: 0,
            galleryPhotos: @js($galleryItems),
            fallbackPhotoUrl: @js($fallbackShowcaseImg),
            pressedOutsideLightbox: false,
            isFavorited: {{ $isFavorited ? 'true' : 'false' }},
            favoriteCount: {{ (int) $listing->favorite_count }},
            copiedLink: false,
            get activePhotoUrl() {
                return this.galleryPhotos[this.activePhotoIdx]?.url || this.fallbackPhotoUrl;
            },
            openLightbox(idx = null) {
                if (idx !== null) {
                    this.activePhotoIdx = idx;
                }
                const dlg = this.$refs.photoLightbox;
                if (dlg && !dlg.open) {
                    dlg.showModal();
                }
            },
            closeLightbox() {
                const dlg = this.$refs.photoLightbox;
                if (dlg && dlg.open) {
                    dlg.close();
                }
            },
            nextPhoto() {
                if (this.galleryPhotos.length > 1) {
                    this.activePhotoIdx = (this.activePhotoIdx + 1) % this.galleryPhotos.length;
                }
            },
            prevPhoto() {
                if (this.galleryPhotos.length > 1) {
                    this.activePhotoIdx = (this.activePhotoIdx - 1 + this.galleryPhotos.length) % this.galleryPhotos.length;
                }
            },
            isOutsideLightbox(e) {
                const dlg = this.$refs.photoLightbox;
                if (!dlg) return false;
                const r = dlg.getBoundingClientRect();
                return e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
            },
            copyUrl() {
                navigator.clipboard.writeText(window.location.href);
                this.copiedLink = true;
                setTimeout(() => this.copiedLink = false, 2500);
            }
        }"
    >
        <!-- Modern Breadcrumbs Navigation -->
        <nav class="flex items-center text-xs font-semibold text-slate-500 overflow-x-auto whitespace-nowrap py-1 scrollbar-none" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1.5 sm:space-x-2">
                <li class="inline-flex items-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-slate-700 hover:text-[#F20B65] transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Home</span>
                    </a>
                </li>
                <li><span class="text-slate-300">/</span></li>
                <li>
                    <a href="{{ route('categories.index') }}" class="text-slate-700 hover:text-[#F20B65] transition-colors">Categories</a>
                </li>
                @if($listing->category)
                    @if($listing->category->parent)
                        <li><span class="text-slate-300">/</span></li>
                        <li>
                            <a href="{{ route('categories.show', $listing->category->parent->slug) }}" class="text-slate-700 hover:text-[#F20B65] transition-colors">
                                {{ $listing->category->parent->name }}
                            </a>
                        </li>
                    @endif
                    <li><span class="text-slate-300">/</span></li>
                    <li>
                        <a href="{{ route('categories.show', $listing->category->slug) }}" class="text-slate-700 hover:text-[#F20B65] transition-colors">
                            {{ $listing->category->name }}
                        </a>
                    </li>
                @endif
                <li><span class="text-slate-300">/</span></li>
                <li class="text-slate-900 font-bold truncate max-w-[200px] sm:max-w-xs" aria-current="page">
                    {{ $listing->title }}
                </li>
            </ol>
        </nav>

        <!-- Flash Alerts -->
        @if(session('success'))
            <x-alert type="success" :dismissible="true">
                {{ session('success') }}
            </x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" :dismissible="true">
                {{ session('error') }}
            </x-alert>
        @endif
        @if($errors->any())
            <x-alert type="error" :dismissible="true">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <!-- Non-Public Moderation Notice -->
        @if(!$listing->isActive())
            <div class="p-4 sm:p-5 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-300 rounded-2xl text-amber-900 text-xs sm:text-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-amber-200/70 text-amber-900 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                    <div>
                        <strong class="font-bold text-amber-950">Status: {{ strtoupper(str_replace('_', ' ', $listing->status)) }}</strong>
                        <p class="text-xs text-amber-800 mt-0.5">This listing is currently in moderation review and hidden from public search.</p>
                    </div>
                </div>
                @can('update', $listing)
                    <a href="{{ route('listings.edit', $listing->id) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs transition shadow-sm shrink-0">
                        <span>Edit Listing</span>
                    </a>
                @endcan
            </div>
        @endif

        <!-- Main Layout: 2 Columns (8 Cols Main + 4 Cols Sticky Sidebar) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
            
            <!-- LEFT COLUMN (8 COLS): Title, Key Highlights, Photo Showcase & Full Details -->
            <div class="lg:col-span-8 min-w-0 space-y-6 sm:space-y-8">
                
                <!-- Listing Detail Showcase Advertisement Slot -->
                <x-ad-banner placement="listing_detail" :context="$adContext ?? []" variant="leaderboard" :limit="1" />

                <!-- 1. Hero Title & Metadata Card -->
                <div class="bg-white border border-slate-200/90 rounded-3xl p-5 sm:p-8 shadow-xs hover:shadow-md transition-shadow relative overflow-hidden">
                    <!-- Top Gradient Glow Accent -->
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#F20B65] via-rose-500 to-purple-600"></div>

                    <!-- Tags & Action Bar -->
                    <div class="flex flex-wrap items-center justify-between gap-3 text-xs mb-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Category Badge -->
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50/90 text-[#F20B65] font-extrabold text-xs border border-rose-200/80 shadow-2xs">
                                <svg class="w-3.5 h-3.5 text-[#F20B65] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                <span>{{ $listing->category?->name ?? 'Classifieds' }}</span>
                            </span>

                            <!-- Featured Spotlight Badge -->
                            @if($listing->is_featured)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-900 font-extrabold text-xs border border-amber-200/90 shadow-2xs">
                                    <svg class="w-3.5 h-3.5 text-amber-500 fill-amber-400 shrink-0" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <span>Featured Spotlight</span>
                                </span>
                            @endif

                            <!-- Location Badge -->
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100/90 text-slate-800 font-bold text-xs border border-slate-200/80">
                                <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>{{ $listing->city?->name }}, {{ $listing->state?->code }}</span>
                            </span>
                        </div>

                        <!-- Right Quick Actions: Save / Bookmark & Share Link -->
                        <div class="flex items-center gap-2">
                            <!-- Share Link Button -->
                            <button
                                type="button"
                                @click="copyUrl()"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-700 font-bold text-xs transition shadow-2xs cursor-pointer"
                                title="Share listing"
                            >
                                <svg x-show="!copiedLink" class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                <svg x-show="copiedLink" x-cloak class="w-3.5 h-3.5 text-[#F20B65] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span x-text="copiedLink ? 'Copied Link' : 'Share'">Share</span>
                            </button>

                            <!-- Save / Favorite Toggle -->
                            <form action="{{ route('listings.favorite.toggle', $listing->id) }}" method="POST" class="inline">
                                @csrf
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border transition-all text-xs font-bold shadow-2xs cursor-pointer"
                                    :class="isFavorited ? 'bg-pink-50 border-pink-300 text-[#F20B65]' : 'bg-white border-slate-200 text-slate-700 hover:text-slate-900 hover:border-slate-300'"
                                    title="Save to favorites"
                                >
                                    <svg class="w-4 h-4 transition-transform active:scale-125 shrink-0" :class="isFavorited ? 'text-[#F20B65] fill-[#F20B65]' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <span x-text="isFavorited ? 'Saved' : 'Save'">{{ $isFavorited ? 'Saved' : 'Save' }}</span>
                                    <span class="text-slate-500 font-semibold tabular-nums">(<span x-text="favoriteCount">{{ $listing->favorite_count }}</span>)</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Main Listing Heading -->
                    <h1 class="text-2xl sm:text-3xl lg:text-[2rem] font-black text-slate-900 tracking-tight leading-snug break-words">
                        {{ $listing->title }}
                    </h1>

                    <!-- Published Time & Reference -->
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500 mt-3">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Published {{ $listing->published_at?->diffForHumans() ?: $listing->created_at->diffForHumans() }}</span>
                        </span>
                        <span class="text-slate-300" aria-hidden="true">/</span>
                        <span class="font-mono text-[11px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md border border-slate-200/70">Ref #AD-{{ str_pad($listing->id, 6, '0', STR_PAD_LEFT) }}</span>
                    </div>

                    <!-- 2. Rich Specifications & Attributes Grid (Refined SVG Iconography) -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-6 border-t border-slate-100">
                        <!-- Age -->
                        <div class="p-3.5 rounded-2xl bg-slate-50/80 hover:bg-white border border-slate-200/80 hover:border-rose-200 hover:shadow-xs transition-all flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-50 to-pink-100/80 border border-rose-200/70 shadow-2xs flex items-center justify-center text-[#F20B65] shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-extrabold text-slate-400 block uppercase tracking-wider">Age</span>
                                <strong class="text-sm font-black text-slate-900 tabular-nums truncate block">{{ $listing->poster_age }} Years</strong>
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="p-3.5 rounded-2xl bg-slate-50/80 hover:bg-white border border-slate-200/80 hover:border-violet-200 hover:shadow-xs transition-all flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-50 to-purple-100/80 border border-violet-200/70 shadow-2xs flex items-center justify-center text-violet-600 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-extrabold text-slate-400 block uppercase tracking-wider">Gender</span>
                                <strong class="text-sm font-black text-slate-900 truncate block">{{ $listing->poster_gender }}</strong>
                            </div>
                        </div>

                        <!-- Relationship Intent -->
                        <div class="p-3.5 rounded-2xl bg-slate-50/80 hover:bg-white border border-slate-200/80 hover:border-pink-200 hover:shadow-xs transition-all flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-pink-50 to-rose-100/80 border border-pink-200/70 shadow-2xs flex items-center justify-center text-[#F20B65] shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-extrabold text-slate-400 block uppercase tracking-wider">Intent</span>
                                <strong class="text-sm font-black text-slate-900 capitalize truncate block">{{ str_replace('_', ' ', $listing->intent) }}</strong>
                            </div>
                        </div>

                        <!-- Views & Activity -->
                        <div class="p-3.5 rounded-2xl bg-slate-50/80 hover:bg-white border border-slate-200/80 hover:border-sky-200 hover:shadow-xs transition-all flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-sky-50 to-indigo-100/80 border border-sky-200/70 shadow-2xs flex items-center justify-center text-sky-600 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-extrabold text-slate-400 block uppercase tracking-wider">Activity</span>
                                <strong class="text-sm font-black text-slate-900 tabular-nums truncate block">{{ number_format($listing->view_count) }} Views</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Full-Size Uncropped Member Photo Gallery Showcase -->
                <div class="bg-white border border-slate-200/90 rounded-3xl p-5 sm:p-8 shadow-xs space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
                        <h2 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-rose-50 border border-rose-100 text-[#F20B65] flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                            <span>{{ $posterVerified ? 'Verified Member Photo Gallery' : 'Member Photo Gallery' }}</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-rose-50 border border-rose-200/80 text-[#F20B65] text-xs font-extrabold tabular-nums">
                                {{ $galleryCount }} {{ \Illuminate\Support\Str::plural('Photo', $galleryCount) }}
                            </span>
                        </h2>
                        <span class="text-[11px] text-slate-600 font-bold hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-50 border border-slate-200/80">
                            <svg class="w-3.5 h-3.5 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <span>EXIF GPS Sanitized &bull; Click to Enlarge</span>
                        </span>
                    </div>

                    <!-- Primary Full-Width Uncropped Image Stage (Eliminates small-thumb whitespace & cropping) -->
                    <div
                        class="relative w-full rounded-2xl overflow-hidden border border-slate-200 bg-slate-950 shadow-sm group cursor-zoom-in flex items-center justify-center min-h-[280px] sm:min-h-[420px] isolate"
                        @click="openLightbox(activePhotoIdx)"
                    >
                        <img
                            :src="activePhotoUrl"
                            src="{{ $galleryItems[0]['url'] }}"
                            alt="{{ $listing->title }}"
                            fetchpriority="high"
                            decoding="async"
                            onerror="this.onerror=null;this.src='{{ $fallbackShowcaseImg }}';"
                            class="w-full max-h-[620px] h-auto object-contain mx-auto block transition-transform duration-300 group-hover:scale-[1.01]"
                        >

                        <!-- Top Floating Badges -->
                        <div class="absolute top-3 left-3 right-3 flex items-center justify-between gap-2 pointer-events-none">
                            @if($hasUploadedPhotos)
                                <template x-if="galleryPhotos[activePhotoIdx]?.isPrimary">
                                    <span class="px-2.5 py-1 rounded-lg text-white text-[10px] font-black uppercase tracking-wider shadow-md" style="background-color: #F20B65 !important;">
                                        Primary Photo
                                    </span>
                                </template>
                                <template x-if="!galleryPhotos[activePhotoIdx]?.isPrimary">
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-900/75 backdrop-blur-md text-white text-[10px] font-bold uppercase tracking-wider shadow border border-white/15">
                                        Photo <span x-text="activePhotoIdx + 1">1</span> of <span x-text="galleryPhotos.length">{{ $galleryCount }}</span>
                                    </span>
                                </template>
                            @else
                                <span></span>
                            @endif

                            <button
                                type="button"
                                @click.stop="openLightbox(activePhotoIdx)"
                                class="pointer-events-auto inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900/75 hover:bg-slate-900/95 backdrop-blur-md text-white text-[11px] font-bold border border-white/20 shadow-md transition cursor-pointer"
                                aria-label="View full size photo"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                </svg>
                                <span>Full Size</span>
                            </button>
                        </div>

                        @if($hasUploadedPhotos && count($galleryItems) > 1)
                            <!-- Previous / Next Carousel Controls Inside Main Stage -->
                            <button
                                type="button"
                                @click.stop="prevPhoto()"
                                class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-slate-900/70 hover:bg-slate-900/95 text-white border border-white/20 shadow-lg flex items-center justify-center transition cursor-pointer"
                                aria-label="Previous photo"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button
                                type="button"
                                @click.stop="nextPhoto()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-slate-900/70 hover:bg-slate-900/95 text-white border border-white/20 shadow-lg flex items-center justify-center transition cursor-pointer"
                                aria-label="Next photo"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        @endif

                        @if(!$hasUploadedPhotos)
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/85 via-slate-950/35 to-transparent flex items-end p-5 sm:p-6 pointer-events-none">
                                <div class="flex flex-wrap items-center justify-between gap-2 w-full text-white">
                                    <div>
                                        <span class="px-3 py-1 rounded-lg text-white text-xs font-black uppercase tracking-wider shadow" style="background-color: #F20B65 !important;">
                                            {{ $posterVerified ? 'Verified Profile Photo' : 'Profile Photo Preview' }}
                                        </span>
                                        <p class="text-xs sm:text-sm text-white font-bold mt-2 drop-shadow">
                                            {{ $listing->city?->name }}, {{ $listing->state?->code }} &bull; {{ $listing->poster_age }} Years
                                        </p>
                                    </div>
                                    <span class="px-3.5 py-2 rounded-xl bg-white/20 backdrop-blur-md text-white text-xs font-bold inline-flex items-center gap-1.5 shadow border border-white/20">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                        <span>Click to Zoom</span>
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if($hasUploadedPhotos && count($galleryItems) > 1)
                        <!-- Multi-Photo Thumbnail Switcher Strip -->
                        <div class="grid grid-cols-3 sm:grid-cols-5 md:grid-cols-6 gap-2.5 pt-1">
                            @foreach($displayPhotos as $idx => $photo)
                                <button
                                    type="button"
                                    @click="activePhotoIdx = {{ $idx }}"
                                    class="relative aspect-square rounded-xl overflow-hidden bg-slate-950 border-2 transition-all cursor-pointer flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-[#F20B65]/40"
                                    :class="activePhotoIdx === {{ $idx }} ? 'border-[#F20B65] ring-2 ring-[#F20B65]/25 shadow-sm' : 'border-slate-200 opacity-75 hover:opacity-100 hover:border-slate-300'"
                                    aria-label="Select photo {{ $idx + 1 }}"
                                >
                                    <img
                                        src="{{ $photo->thumbnail_url ?: $photo->url }}"
                                        alt="{{ $listing->title }} photo {{ $idx + 1 }}"
                                        loading="lazy"
                                        fetchpriority="low"
                                        decoding="async"
                                        onerror="this.onerror=null;this.src='{{ $fallbackShowcaseImg }}';"
                                        class="w-full h-full object-contain block"
                                    >
                                    @if($photo->is_primary)
                                        <span class="absolute bottom-1 left-1 px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-[#F20B65] text-white shadow">Primary</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- 4. Advertisement Details & Story Card -->
                <div class="bg-white border border-slate-200/90 rounded-3xl p-5 sm:p-8 shadow-xs space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
                        <h2 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-rose-50 border border-rose-100 text-[#F20B65] flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            <span>About This Person &amp; Classified</span>
                        </h2>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200/80 text-[11px] text-slate-600 font-bold">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                            <span>Verified English</span>
                        </span>
                    </div>

                    <!-- Editorial Content Body with Styled Left Accent -->
                    <div class="relative pl-4 sm:pl-5 border-l-[3px] border-[#F20B65]/80 py-1">
                        <div class="text-slate-800 text-sm sm:text-[15px] leading-[1.75] whitespace-pre-line font-medium space-y-4">
                            {{ $listing->description }}
                        </div>
                    </div>
                </div>

                <!-- 5. Match Preferences & Dynamic Category Attributes (if any) -->
                @if($listing->attributes->isNotEmpty())
                    <div class="bg-white border border-slate-200/90 rounded-3xl p-5 sm:p-8 shadow-xs space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h2 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                                <span class="w-9 h-9 rounded-xl bg-violet-50 border border-violet-100 text-violet-600 flex items-center justify-center shrink-0">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                </span>
                                <span>Specific Match Preferences</span>
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            @foreach($listing->attributes as $attr)
                                <div class="p-3.5 bg-slate-50/80 border border-slate-200/80 rounded-2xl flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-slate-600 capitalize">{{ str_replace('_', ' ', $attr->attribute_code) }}:</span>
                                    <span class="font-bold text-slate-900 text-xs px-2.5 py-1 rounded-lg bg-white border border-slate-200 shadow-2xs">
                                        {{ $attr->selectedOption?->label ?: ($attr->value_text ?: $attr->value_number) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

            <!-- RIGHT COLUMN (4 COLS): Contact, Safety & Sidebar Ad Column -->
            <div class="lg:col-span-4 min-w-0 space-y-6">
                
                <!-- 1. Advertiser Profile Card -->
                <div class="bg-white border border-slate-200/90 rounded-3xl p-6 shadow-xs space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-500">Advertiser Profile</span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-[#F20B65] border border-rose-200/80">
                            <svg class="w-3.5 h-3.5 text-[#F20B65] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"/>
                            </svg>
                            <span>Active Now</span>
                        </span>
                    </div>

                    <!-- Profile Header with Vibrant Guaranteed Avatar -->
                    <div class="flex items-center gap-3.5">
                        <div 
                            class="w-14 h-14 rounded-2xl flex items-center justify-center font-black text-2xl shadow-md shrink-0 select-none"
                            style="background: linear-gradient(135deg, #F20B65 0%, #D90858 50%, #9333EA 100%) !important; color: #ffffff !important; border: 2px solid rgba(255, 255, 255, 0.4);"
                        >
                            {{ strtoupper(substr($listing->user?->name ?? 'M', 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-black text-slate-900 truncate flex items-center gap-1.5">
                                <span class="truncate">{{ $listing->user?->name ?? 'Anonymous Poster' }}</span>
                                @if($posterVerified)
                                    <x-verified-badge variant="icon" size="w-4 h-4" title="Blue Verified Badge — Identity Verified" />
                                @endif
                            </h3>
                            <p class="text-xs font-medium text-slate-500 mt-0.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Member since {{ $listing->user?->created_at?->format('M Y') ?? 'Nov 2026' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Trust & Assurance Badges -->
                    <div class="pt-1 space-y-2.5 text-xs">
                        @if($posterVerified)
                            <div class="flex items-center gap-2.5 p-3 rounded-xl bg-blue-50/90 border border-blue-200/90 text-blue-900 font-bold">
                                <x-verified-badge variant="icon" size="w-4 h-4" title="Identity Verified Member" />
                                <span>Blue Verified Identity Badge</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-600 font-semibold">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>Identity Not Yet Verified</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 2. Author Controls (If Author or Admin) -->
                @auth
                    @if(auth()->id() === $listing->user_id || auth()->user()->hasRole('admin'))
                        <div class="bg-gradient-to-br from-rose-50/90 via-pink-50/40 to-white border border-rose-200 rounded-3xl p-5 shadow-xs space-y-3.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-black uppercase tracking-wider text-[#F20B65]">
                                    <span class="w-6 h-6 rounded-lg bg-white border border-rose-200 flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </span>
                                    <span>Listing Management</span>
                                </span>
                                @if($listing->activeBoost)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold text-white shadow-2xs" style="background-color: #F20B65 !important;">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                                        <span>Boosted</span>
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-700 leading-relaxed font-semibold">
                                Get 5x more responses! Spotlight your classified to the very top with instant crypto promotion.
                            </p>
                            <div class="flex items-center gap-2">
                                <a 
                                    href="{{ route('boost.checkout', $listing->id) }}" 
                                    class="flex-1 py-3 px-3 text-white text-xs font-black rounded-xl shadow-md transition hover:opacity-95 active:scale-[0.98] inline-flex items-center justify-center gap-1.5 whitespace-nowrap"
                                    style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important;"
                                >
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span>Boost This Ad Now</span>
                                </a>
                                <a 
                                    href="{{ route('listings.edit', $listing->id) }}" 
                                    class="py-3 px-4 bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 text-xs font-black rounded-xl transition shadow-2xs inline-flex items-center gap-1"
                                >
                                    <span>Edit</span>
                                </a>
                            </div>
                        </div>
                    @endif
                @endauth

                <!-- 3. Contact Advertiser Card (GUARANTEED 100% VISIBLE CTA) -->
                <div id="contact-member" class="scroll-mt-24 bg-white border border-slate-200/90 rounded-3xl p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-rose-50 border border-rose-100 text-[#F20B65] flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </span>
                            <span>Contact Advertiser</span>
                        </h2>
                        <span class="text-[11px] font-bold text-slate-500">Direct &amp; Safe</span>
                    </div>

                    <!-- Protected Platform Contact Form Trigger -->
                    @auth
                        <a
                            href="{{ route('account.messages.index', ['listing' => $listing->id, 'user' => $listing->user_id]) }}"
                            class="w-full py-3.5 px-5 rounded-2xl text-sm font-black transition-all flex items-center justify-center space-x-2 group hover:opacity-95 active:scale-[0.98] cursor-pointer"
                            style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important; box-shadow: 0 10px 25px -4px rgba(242, 11, 101, 0.45) !important;"
                        >
                            <svg class="w-5 h-5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <span class="text-white font-black text-sm tracking-wide">Chat on Messenger</span>
                        </a>
                    @else
                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'contact-modal')"
                            class="w-full py-3.5 px-5 rounded-2xl text-sm font-black transition-all flex items-center justify-center space-x-2 group hover:opacity-95 active:scale-[0.98] cursor-pointer"
                            style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important; box-shadow: 0 10px 25px -4px rgba(242, 11, 101, 0.45) !important;"
                        >
                            <svg class="w-5 h-5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <span class="text-white font-black text-sm tracking-wide">Send Protected Message</span>
                        </button>
                    @endauth

                    <!-- Public Email OR Encrypted Anonymous Email Relay -->
                    @php
                        $isEmailPublic = (bool) ($listing->contact?->show_email ?? ($listing->user?->preferences?->show_public_email ?? false));
                        $publicEmailAddress = $listing->contact?->preferred_email_encrypted ?: $listing->user?->email;
                    @endphp
                    @if($isEmailPublic && !empty($publicEmailAddress))
                        <div class="p-3.5 bg-sky-50/70 border border-sky-200/80 rounded-2xl text-xs space-y-2" x-data="{ copiedEmail: false }">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sky-950 font-black text-[11px] flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                    <span>Direct Public Email:</span>
                                </span>
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $publicEmailAddress }}'); copiedEmail = true; setTimeout(() => copiedEmail = false, 2000)"
                                    class="text-[11px] text-sky-700 hover:underline font-black cursor-pointer"
                                    x-text="copiedEmail ? 'Copied!' : 'Copy Email'"
                                >Copy Email</button>
                            </div>
                            <a href="mailto:{{ $publicEmailAddress }}?subject={{ rawurlencode('Regarding your LocalDateHub post: ' . $listing->title) }}" class="font-mono text-slate-900 hover:text-[#F20B65] text-xs font-black flex items-center gap-2 break-all select-all bg-white p-2.5 rounded-xl border border-sky-200/80 shadow-2xs transition">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span>{{ $publicEmailAddress }}</span>
                            </a>
                            <span class="text-[11px] text-sky-900/80 font-medium block leading-tight">Advertiser has made their email public on this post for direct contact.</span>
                        </div>
                    @elseif($listing->contact?->allow_email_relay && $listing->contact?->masked_relay_email)
                        <div class="p-3.5 bg-slate-50/90 border border-slate-200/90 rounded-2xl text-xs space-y-2" x-data="{ copiedRelay: false }">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-slate-700 font-black text-[11px] flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    <span>Encrypted Email Relay:</span>
                                </span>
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $listing->contact->masked_relay_email }}'); copiedRelay = true; setTimeout(() => copiedRelay = false, 2000)"
                                    class="text-[11px] text-[#F20B65] hover:underline font-black cursor-pointer"
                                    x-text="copiedRelay ? 'Copied!' : 'Copy'"
                                >Copy</button>
                            </div>
                            <span class="font-mono text-slate-900 text-xs font-bold block break-all select-all bg-white p-2.5 rounded-xl border border-slate-200/90">
                                {{ $listing->contact->masked_relay_email }}
                            </span>
                            <span class="text-[11px] text-slate-500 font-medium block leading-tight">Personal email is encrypted &amp; masked to protect advertiser privacy.</span>
                        </div>
                    @endif

                    <!-- External Phone Visibility (Only if explicitly enabled by poster) -->
                    @if($listing->contact?->show_phone && $listing->contact?->preferred_phone_encrypted)
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs space-y-1">
                            <span class="text-slate-600 font-bold text-[11px]">Telephone / SMS:</span>
                            <a href="tel:{{ $listing->contact->preferred_phone_encrypted }}" class="font-mono text-[#F20B65] hover:underline font-bold text-sm block">
                                {{ $listing->contact->preferred_phone_encrypted }}
                            </a>
                        </div>
                    @endif

                    <!-- External Handle (e.g. Signal, Telegram) -->
                    @if($listing->contact?->external_handle_encrypted)
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs space-y-1">
                            <span class="text-slate-600 font-bold text-[11px]">External Messenger:</span>
                            <span class="font-mono text-purple-700 font-black text-xs block select-all">{{ $listing->contact->external_handle_encrypted }}</span>
                        </div>
                    @endif

                    <!-- Contact Instructions -->
                    @if($listing->contact?->contact_instructions)
                        <div class="p-3.5 bg-slate-50/90 border border-slate-200/90 rounded-2xl text-xs text-slate-800">
                            <strong class="text-slate-900 block mb-1 font-bold">Advertiser's Instructions:</strong>
                            <p class="leading-relaxed font-medium text-slate-600">{{ $listing->contact->contact_instructions }}</p>
                        </div>
                    @endif
                </div>

                <!-- 4. Location & Anti-Triangulation Privacy Notice -->
                <div class="bg-white border border-slate-200/90 rounded-3xl p-6 shadow-xs space-y-3">
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-sky-50 border border-sky-100 text-sky-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </span>
                        <span>Location &amp; Anti-Stalking</span>
                    </h3>
                    <p class="text-xs text-slate-700 leading-relaxed font-bold">
                        City: <strong class="text-slate-900">{{ $listing->city?->name }}, {{ $listing->state?->code }}</strong>
                        @if($listing->location?->neighborhood)
                            <span class="block text-slate-600 mt-0.5 font-medium">Area / Neighborhood: {{ $listing->location->neighborhood }}</span>
                        @endif
                    </p>
                    <div class="p-3 bg-sky-50/70 border border-sky-200/80 rounded-xl text-[11px] text-slate-700 font-medium flex items-start gap-2">
                        <svg class="w-4 h-4 text-sky-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>GPS coordinates are fuzzed by 400–800m to prevent residential stalking &amp; protect user privacy.</span>
                    </div>
                </div>

                <!-- 5. Trust & Safety Guidelines & Report Link -->
                <div class="bg-gradient-to-br from-rose-50/80 to-pink-50/40 border border-rose-200/90 rounded-3xl p-6 shadow-xs space-y-3.5">
                    <h3 class="text-xs font-black text-[#F20B65] uppercase tracking-wider flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-white border border-rose-200 text-[#F20B65] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </span>
                        <span>Safety First Guidelines</span>
                    </h3>
                    <ul class="text-xs text-slate-800 space-y-2.5 list-none font-semibold leading-relaxed">
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-sky-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg>
                            <span>Always meet in busy public venues for first encounters.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-[#F20B65] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Never wire gift cards, cash, or crypto to any advertiser.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-[#F20B65] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Commercial sexual services &amp; prostitution are strictly prohibited.</span>
                        </li>
                    </ul>
                    <div class="pt-2 border-t border-rose-200/80">
                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'report-modal')"
                            class="text-xs text-[#F20B65] hover:text-rose-700 font-black inline-flex items-center gap-1.5 transition cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                            <span>Report Community Violation</span>
                        </button>
                    </div>
                </div>

                <!-- 6. Sidebar Advertisement Slot -->
                <x-ad-banner placement="sidebar" :context="$adContext ?? []" variant="sidebar" :limit="2" />

            </div>

        </div>

        <!-- Similar Classifieds Section -->
        @if(isset($similarListings) && $similarListings->isNotEmpty())
            <div class="pt-12 border-t border-slate-200 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Similar Listings in {{ $listing->city?->name ?? 'this Area' }}
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-600 font-medium mt-1">
                            Browse other active personal advertisements in this category.
                        </p>
                    </div>
                    <a href="{{ route('categories.show', $listing->category?->slug ?? 'all') }}" class="text-xs font-black text-[#F20B65] hover:underline hidden sm:inline-block">
                        View All in {{ $listing->category?->name }} →
                    </a>
                </div>
                <div class="grid grid-cols-1 min-[420px]:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6">
                    @php
                        $relFeed = $relatedFeedItems ?? $similarListings->map(fn ($s) => ['type' => 'listing', 'listing' => $s]);
                    @endphp
                    @foreach($relFeed as $feedItem)
                        @if(($feedItem['type'] ?? 'listing') === 'ad')
                            <x-sponsored-listing-card :ad="$feedItem['ad']" />
                        @else
                            <x-listing-card :listing="$feedItem['listing']" />
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Floating Mobile Contact Bar (Ultimate Mobile UX) -->
        <div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 py-3 flex items-center justify-between gap-3 shadow-2xl">
            <div class="min-w-0 flex-1 flex items-center gap-2.5">
                <div 
                    class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm shrink-0"
                    style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important;"
                >
                    {{ strtoupper(substr($listing->user?->name ?? 'M', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-xs font-black text-slate-900 block truncate">{{ $listing->title }}</span>
                    <span class="text-[10px] text-slate-500 font-bold block truncate">{{ $listing->city?->name }}, {{ $listing->state?->code }}</span>
                </div>
            </div>
            @auth
                <a
                    href="{{ route('account.messages.index', ['listing' => $listing->id, 'user' => $listing->user_id]) }}"
                    class="py-2.5 px-4 rounded-xl shadow-md shrink-0 flex items-center gap-1.5 font-black text-xs"
                    style="background-color: #F20B65 !important; color: #ffffff !important;"
                >
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <span class="text-white">Messenger</span>
                </a>
            @else
                <button
                    type="button"
                    @click="$dispatch('open-modal', 'contact-modal')"
                    class="py-2.5 px-4 rounded-xl shadow-md shrink-0 flex items-center gap-1.5 font-black text-xs"
                    style="background-color: #F20B65 !important; color: #ffffff !important;"
                >
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <span class="text-white">Message</span>
                </button>
            @endauth
        </div>

        <!-- Native Top-Layer Lightbox Dialog for Full-Size Photo Inspection -->
        <dialog
            x-ref="photoLightbox"
            closedby="any"
            aria-label="Full size photo viewer"
            @pointerdown="pressedOutsideLightbox = isOutsideLightbox($event)"
            @pointerup="if (pressedOutsideLightbox && isOutsideLightbox($event)) closeLightbox()"
            @keydown.left.prevent="prevPhoto()"
            @keydown.right.prevent="nextPhoto()"
            class="m-auto max-w-5xl w-[calc(100vw-2rem)] bg-transparent p-0 border-0 outline-none overflow-visible"
        >
            <div class="relative rounded-2xl bg-slate-950/95 border border-white/15 shadow-2xl p-2 sm:p-3 flex flex-col items-center">
                <div class="w-full flex items-center justify-between gap-3 px-2 pb-2 text-white">
                    <span class="text-xs font-bold text-slate-300 tabular-nums" x-show="galleryPhotos.length > 1">
                        Photo <span x-text="activePhotoIdx + 1">1</span> of <span x-text="galleryPhotos.length">1</span>
                    </span>
                    <span class="text-xs font-bold text-slate-300" x-show="galleryPhotos.length <= 1">
                        Full Size Photo
                    </span>
                    <button
                        type="button"
                        autofocus
                        @click="closeLightbox()"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition cursor-pointer"
                        aria-label="Close full size photo viewer"
                    >
                        <span>Close</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="relative w-full flex items-center justify-center">
                    <button
                        type="button"
                        x-show="galleryPhotos.length > 1"
                        @click="prevPhoto()"
                        class="absolute left-2 sm:left-3 z-10 w-10 h-10 rounded-full bg-slate-900/80 hover:bg-slate-900 text-white border border-white/20 shadow-lg flex items-center justify-center transition cursor-pointer"
                        aria-label="Previous photo"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"/></svg>
                    </button>

                    <img
                        :src="activePhotoUrl"
                        alt="Full size view of {{ $listing->title }}"
                        class="max-h-[82dvh] w-auto max-w-full rounded-xl object-contain mx-auto block"
                    >

                    <button
                        type="button"
                        x-show="galleryPhotos.length > 1"
                        @click="nextPhoto()"
                        class="absolute right-2 sm:right-3 z-10 w-10 h-10 rounded-full bg-slate-900/80 hover:bg-slate-900 text-white border border-white/20 shadow-lg flex items-center justify-center transition cursor-pointer"
                        aria-label="Next photo"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </dialog>

        <!-- Protected Contact Form Modal -->
        <x-modal name="contact-modal" title="Send Protected Message to Advertiser">
            <form action="{{ route('listings.contact.submit', $listing->id) }}" method="POST" class="space-y-4">
                @csrf
                <!-- Anti-Spam Honeypot -->
                <input type="text" name="contact_hp" style="display:none;" tabindex="-1" autocomplete="off">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input
                        name="name"
                        label="Your Name / Handle"
                        placeholder="Alex"
                        :value="old('name', Auth::user()?->name)"
                        required
                    />
                    <x-input
                        name="email"
                        type="email"
                        label="Your Return Email"
                        placeholder="alex@example.com"
                        :value="old('email', Auth::user()?->email)"
                        required
                    />
                </div>

                <x-input
                    name="phone"
                    type="tel"
                    label="Phone Number (Optional)"
                    placeholder="+1 (555) 000-0000"
                    :value="old('phone')"
                />

                <div>
                    <label for="reply-message" class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">
                        Message Details <span class="text-[#F20B65]">*</span>
                    </label>
                    <textarea
                        id="reply-message"
                        name="message"
                        rows="5"
                        required
                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#F20B65] focus:bg-white transition"
                        placeholder="Introduce yourself and mention what interested you in their listing..."
                    >{{ old('message') }}</textarea>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-700 space-y-1">
                    <p class="font-bold text-slate-900 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Privacy Shield Active
                    </p>
                    <p class="text-[11px] text-slate-600 font-medium">Your message is encrypted before storage. The advertiser never sees your raw IP or location.</p>
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'contact-modal')">
                        Cancel
                    </x-button>
                    <button
                        type="submit"
                        class="px-5 py-2.5 rounded-xl font-black text-xs shadow-md transition hover:opacity-95"
                        style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important;"
                    >
                        Send Encrypted Message
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- Report Listing Modal -->
        <x-modal name="report-modal" title="Report Listing Violations">
            <form action="{{ route('listings.report.submit', $listing->id) }}" method="POST" class="space-y-4">
                @csrf
                <!-- Anti-Spam Honeypot -->
                <input type="text" name="report_hp" style="display:none;" tabindex="-1" autocomplete="off">

                <div>
                    <label for="report-reason" class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">
                        Violation Category <span class="text-[#F20B65]">*</span>
                    </label>
                    <select
                        id="report-reason"
                        name="report_reason"
                        required
                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 text-xs text-slate-900 focus:ring-2 focus:ring-[#F20B65]"
                    >
                        <option value="">Select Primary Violation Reason</option>
                        <option value="underage_minor">Underage or Minor-Related Content (Immediate Escalation)</option>
                        <option value="commercial_sex">Commercial Sex Work, Prostitution, or Escort (FOSTA-SESTA)</option>
                        <option value="non_consensual_content">Non-Consensual Imagery or Stolen Photos</option>
                        <option value="fraud_scam">Financial Scam, Wire Request, or Impersonation</option>
                        <option value="harassment">Harassment, Stalking, or Hate Speech</option>
                        <option value="other">Other Community Standards Violation</option>
                    </select>
                </div>

                <div>
                    <label for="report-details" class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5">
                        Explanation &amp; Context <span class="text-[#F20B65]">*</span>
                    </label>
                    <textarea
                        id="report-details"
                        name="report_details"
                        rows="4"
                        required
                        class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#F20B65]"
                        placeholder="Please describe why this listing violates community standards..."
                    >{{ old('report_details') }}</textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input
                        name="reporter_name"
                        label="Your Name (Optional)"
                        placeholder="Anonymous"
                        :value="old('reporter_name', Auth::user()?->name)"
                    />
                    <x-input
                        name="reporter_email"
                        type="email"
                        label="Your Email (Optional)"
                        placeholder="reporter@example.com"
                        :value="old('reporter_email', Auth::user()?->email)"
                    />
                </div>

                <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-950 font-medium">
                    <strong>Notice:</strong> Reports concerning minors or human trafficking are prioritized 24/7 and referred to law enforcement and NCMEC.
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'report-modal')">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="danger">
                        Submit Confidential Report
                    </x-button>
                </div>
            </form>
        </x-modal>
    </div>
</x-layouts.app>
