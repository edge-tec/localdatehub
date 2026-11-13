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
        <!-- Unified Modern SVG Icon Sprite (Referenced via <use> for Crisp Zero-Request Rendering) -->
        <svg width="0" height="0" aria-hidden="true" class="absolute w-0 h-0 overflow-hidden pointer-events-none">
            <symbol id="ldh-icon-user-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/>
            </symbol>
            <symbol id="ldh-icon-calendar-days" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/>
            </symbol>
            <symbol id="ldh-icon-shield-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>
            </symbol>
            <symbol id="ldh-icon-shield-alert" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M12 8v4"/><path d="M12 16h.01"/>
            </symbol>
            <symbol id="ldh-icon-sliders" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/>
            </symbol>
            <symbol id="ldh-icon-zap" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>
            </symbol>
            <symbol id="ldh-icon-square-pen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z"/>
            </symbol>
            <symbol id="ldh-icon-message-circle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="M8 12h.01"/><path d="M12 12h.01"/><path d="M16 12h.01"/>
            </symbol>
            <symbol id="ldh-icon-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </symbol>
            <symbol id="ldh-icon-globe" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>
            </symbol>
            <symbol id="ldh-icon-mail" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </symbol>
            <symbol id="ldh-icon-copy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
            </symbol>
            <symbol id="ldh-icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 6 9 17l-5-5"/>
            </symbol>
            <symbol id="ldh-icon-phone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </symbol>
            <symbol id="ldh-icon-at-sign" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/>
            </symbol>
            <symbol id="ldh-icon-quote" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8"/><path d="M8 13h5"/>
            </symbol>
            <symbol id="ldh-icon-map-pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>
            </symbol>
            <symbol id="ldh-icon-radar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19.07 4.93A10 10 0 0 0 6.99 3.34"/><path d="M4 6h.01"/><path d="M2.29 9.62A10 10 0 1 0 21.31 8.35"/><path d="M16.24 7.76A6 6 0 1 0 8.23 16.67"/><path d="M12 18h.01"/><path d="M17.99 11.66A6 6 0 0 1 15.77 16.67"/><circle cx="12" cy="12" r="2"/><path d="m13.41 10.59 5.66-5.66"/>
            </symbol>
            <symbol id="ldh-icon-compass" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>
            </symbol>
            <symbol id="ldh-icon-flag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" x2="4" y1="22" y2="15"/>
            </symbol>
            <symbol id="ldh-icon-arrow-up-right" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M7 7h10v10"/><path d="M7 17 17 7"/>
            </symbol>
            <symbol id="ldh-icon-layers" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>
            </symbol>
            <symbol id="ldh-icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>
            </symbol>
            <symbol id="ldh-icon-check-circle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>
            </symbol>
            <symbol id="ldh-icon-ban" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>
            </symbol>
            <symbol id="ldh-icon-sparkles" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>
            </symbol>
            <symbol id="ldh-icon-dashboard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>
            </symbol>
        </svg>
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

            <!-- RIGHT COLUMN (4 COLS): Sticky Container-Aware Profile, Contact, Location & Safety Sidebar -->
            @php
                $posterUser = $listing->user;
                $posterName = $posterUser?->name ?? 'Anonymous Poster';
                $posterInitial = strtoupper(substr($posterName, 0, 1));
                $posterAvatarUrl = $posterUser?->avatar_url;
                $posterIsOnline = $posterUser?->isOnline() ?? false;
                $posterAdsCount = max(1, (int) ($posterActiveListingsCount ?? 1));
                $isListingOwner = auth()->check() && (auth()->id() === $listing->user_id || auth()->user()->hasRole('admin'));
                $cityBrowseUrl = ($listing->city?->slug && $listing->state?->code)
                    ? route('locations.city', ['stateCode' => strtolower($listing->state->code), 'citySlug' => $listing->city->slug])
                    : null;
            @endphp
            <aside class="lg:col-span-4 lg:sticky lg:top-24 min-w-0 space-y-5 ldh-sidebar" aria-label="Advertiser details and safety controls">

                <!-- 1. Advertiser Profile Card -->
                <article class="ldh-sidebar-card bg-white border border-slate-200/90 rounded-3xl p-5 sm:p-6 shadow-[0_12px_32px_-16px_rgba(15,23,42,0.14)] hover:shadow-[0_18px_38px_-16px_rgba(15,23,42,0.18)] transition-shadow space-y-5 relative overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#F20B65] via-rose-500 to-purple-600" aria-hidden="true"></div>

                    <header class="flex flex-wrap items-center justify-between gap-2 pb-3.5 border-b border-slate-100">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-rose-50 to-pink-100/80 border border-rose-200/70 text-[#F20B65] flex items-center justify-center shrink-0 shadow-2xs">
                                <svg class="w-4 h-4" aria-hidden="true"><use href="#ldh-icon-user-check"/></svg>
                            </span>
                            <span class="ldh-card-heading text-xs font-black uppercase tracking-wider text-slate-700">Advertiser Profile</span>
                        </div>

                        @if($posterIsOnline)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border bg-emerald-50 text-emerald-700 border-emerald-200/80">
                                <span class="relative flex h-2 w-2 shrink-0" aria-hidden="true">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span>Active Now</span>
                            </span>
                        @endif
                    </header>

                    <!-- Member Identity & Avatar Row -->
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="relative shrink-0">
                            @if($posterAvatarUrl)
                                <img
                                    src="{{ $posterAvatarUrl }}"
                                    alt="{{ $posterName }}"
                                    loading="lazy"
                                    decoding="async"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    class="w-14 h-14 rounded-2xl object-cover ring-2 ring-rose-100 shadow-md block"
                                >
                                <div
                                    style="display:none; background: linear-gradient(135deg, #F20B65 0%, #D90858 52%, #9333EA 100%) !important; color: #ffffff !important;"
                                    class="w-14 h-14 rounded-2xl items-center justify-center font-black text-2xl shadow-md select-none ring-2 ring-white"
                                    aria-hidden="true"
                                >
                                    {{ $posterInitial }}
                                </div>
                            @else
                                <div
                                    class="w-14 h-14 rounded-2xl flex items-center justify-center font-black text-2xl shadow-md select-none ring-2 ring-rose-100"
                                    style="background: linear-gradient(135deg, #F20B65 0%, #D90858 52%, #9333EA 100%) !important; color: #ffffff !important;"
                                    aria-hidden="true"
                                >
                                    {{ $posterInitial }}
                                </div>
                            @endif
                            @if($posterIsOnline)
                                <span
                                    class="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full border-2 border-white shadow-xs bg-emerald-500"
                                    title="Active Now"
                                    aria-hidden="true"
                                ></span>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1 space-y-1">
                            <h3 class="ldh-card-heading text-base sm:text-[17px] font-black text-slate-900 flex items-center gap-1.5 min-w-0">
                                <span class="truncate">{{ $posterName }}</span>
                                @if($posterVerified)
                                    <x-verified-badge variant="icon" size="w-4 h-4" title="Blue Verified Badge — Identity Verified" />
                                @endif
                            </h3>
                            <p class="text-xs font-semibold text-slate-500 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" aria-hidden="true"><use href="#ldh-icon-calendar-days"/></svg>
                                <span>Member since {{ $posterUser?->created_at?->format('M Y') ?? 'Nov 2026' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Compact Activity & Trust Metrics Grid -->
                    <div class="grid grid-cols-2 gap-2.5 text-xs">
                        <div class="flex items-center gap-2.5 p-2.5 rounded-2xl bg-slate-50/90 border border-slate-200/75 min-w-0">
                            <span class="w-8 h-8 rounded-xl bg-white border border-slate-200/80 text-[#F20B65] flex items-center justify-center shrink-0 shadow-2xs">
                                <svg class="w-4 h-4" aria-hidden="true"><use href="#ldh-icon-layers"/></svg>
                            </span>
                            <div class="min-w-0">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">Active Ads</span>
                                <strong class="text-xs sm:text-sm font-black text-slate-900 tabular-nums block">{{ $posterAdsCount }} {{ \Illuminate\Support\Str::plural('Post', $posterAdsCount) }}</strong>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 p-2.5 rounded-2xl bg-slate-50/90 border border-slate-200/75 min-w-0">
                            <span class="w-8 h-8 rounded-xl bg-white border border-slate-200/80 text-sky-600 flex items-center justify-center shrink-0 shadow-2xs">
                                <svg class="w-4 h-4" aria-hidden="true"><use href="#ldh-icon-eye"/></svg>
                            </span>
                            <div class="min-w-0">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">Post Views</span>
                                <strong class="text-xs sm:text-sm font-black text-slate-900 tabular-nums block">{{ number_format($listing->view_count) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Strict Identity Verification Status -->
                    <div class="text-xs">
                        @if($posterVerified)
                            <div class="flex items-center gap-3 p-3.5 rounded-2xl bg-gradient-to-r from-blue-50 to-sky-50/80 border border-blue-200/90 text-blue-950 shadow-2xs">
                                <span class="w-9 h-9 rounded-xl bg-white border border-blue-200/80 flex items-center justify-center shrink-0 shadow-2xs">
                                    <x-verified-badge variant="icon" size="w-5 h-5" title="Identity Verified Member" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <strong class="block text-xs font-black text-blue-950">Blue Verified Identity Badge</strong>
                                    <span class="block text-[11px] font-medium text-blue-800/85 mt-0.5">Official government ID reviewed &amp; approved</span>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center justify-between gap-2.5 p-3.5 rounded-2xl bg-slate-50/90 border border-slate-200/90 text-slate-700">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-500 flex items-center justify-center shrink-0 shadow-2xs">
                                        <svg class="w-4 h-4" aria-hidden="true"><use href="#ldh-icon-shield-alert"/></svg>
                                    </span>
                                    <div class="min-w-0">
                                        <strong class="block text-xs font-bold text-slate-800">Identity Not Yet Verified</strong>
                                        <span class="block text-[11px] font-medium text-slate-500">ID badge requires admin ID approval</span>
                                    </div>
                                </div>
                                @if(auth()->check() && auth()->id() === $listing->user_id)
                                    <a
                                        href="{{ route('account.verification.show') }}"
                                        class="ldh-touch-target inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-white hover:bg-blue-50 border border-slate-200 hover:border-blue-300 text-blue-700 text-[11px] font-black transition shrink-0"
                                    >
                                        <span>Verify</span>
                                        <svg class="w-3 h-3" aria-hidden="true"><use href="#ldh-icon-arrow-up-right"/></svg>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </article>

                <!-- 2. Author Controls (Owner or Admin Only) -->
                @if($isListingOwner)
                    <article class="ldh-sidebar-card bg-gradient-to-br from-rose-50/95 via-pink-50/50 to-white border border-rose-200/90 rounded-3xl p-5 sm:p-6 shadow-[0_12px_28px_-16px_rgba(242,11,101,0.22)] space-y-4">
                        <header class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-rose-200/60">
                            <span class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-wider text-[#F20B65]">
                                <span class="w-8 h-8 rounded-xl bg-white border border-rose-200/80 text-[#F20B65] flex items-center justify-center shrink-0 shadow-2xs">
                                    <svg class="w-4 h-4" aria-hidden="true"><use href="#ldh-icon-sliders"/></svg>
                                </span>
                                <span class="ldh-card-heading">Listing Management</span>
                            </span>
                            @if($listing->activeBoost)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-extrabold text-white shadow-2xs" style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important;">
                                    <svg class="w-3 h-3" aria-hidden="true"><use href="#ldh-icon-sparkles"/></svg>
                                    <span>Boosted</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-white border border-rose-200/80 text-rose-700">
                                    <svg class="w-3 h-3" aria-hidden="true"><use href="#ldh-icon-check-circle"/></svg>
                                    <span>Owner Tools</span>
                                </span>
                            @endif
                        </header>

                        <p class="ldh-card-prose text-xs text-slate-700 leading-relaxed font-semibold">
                            Get 5x more responses! Spotlight your classified to the very top with instant crypto promotion.
                        </p>

                        <footer class="flex flex-wrap items-stretch gap-2.5">
                            <a
                                href="{{ route('boost.checkout', $listing->id) }}"
                                class="ldh-touch-target flex-1 min-w-[175px] py-2.5 px-4 text-white text-xs font-black rounded-2xl shadow-md transition hover:opacity-95 active:scale-[0.99] inline-flex items-center justify-center gap-2"
                                style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important;"
                            >
                                <svg class="w-4 h-4 shrink-0" aria-hidden="true"><use href="#ldh-icon-zap"/></svg>
                                <span>Boost This Ad Now</span>
                            </a>
                            <a
                                href="{{ route('listings.edit', $listing->id) }}"
                                class="ldh-touch-target py-2.5 px-3.5 bg-white hover:bg-slate-50 border border-slate-200 hover:border-slate-300 text-slate-800 text-xs font-black rounded-2xl transition shadow-2xs inline-flex items-center justify-center gap-1.5 shrink-0"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-600 shrink-0" aria-hidden="true"><use href="#ldh-icon-square-pen"/></svg>
                                <span>Edit</span>
                            </a>
                            <a
                                href="{{ route('account.dashboard', ['tab' => 'listings']) }}"
                                class="ldh-touch-target py-2.5 px-3 bg-white hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-slate-700 hover:text-[#F20B65] text-xs font-black rounded-2xl transition shadow-2xs inline-flex items-center justify-center gap-1.5 shrink-0"
                                title="Open My Listings Dashboard"
                                aria-label="Open My Listings Dashboard"
                            >
                                <svg class="w-3.5 h-3.5 shrink-0" aria-hidden="true"><use href="#ldh-icon-dashboard"/></svg>
                            </a>
                        </footer>
                    </article>
                @endif

                <!-- 3. Contact Advertiser Card (GUARANTEED 100% VISIBLE CTA) -->
                <article id="contact-member" class="ldh-sidebar-card scroll-mt-24 bg-white border border-slate-200/90 rounded-3xl p-5 sm:p-6 shadow-[0_12px_32px_-16px_rgba(15,23,42,0.14)] space-y-4">
                    <header class="flex flex-wrap items-center justify-between gap-2 pb-3.5 border-b border-slate-100">
                        <h2 class="ldh-card-heading text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-rose-50 to-pink-100/80 border border-rose-200/70 text-[#F20B65] flex items-center justify-center shrink-0 shadow-2xs">
                                <svg class="w-4 h-4" aria-hidden="true"><use href="#ldh-icon-message-circle"/></svg>
                            </span>
                            <span>Contact Advertiser</span>
                        </h2>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200/80 text-emerald-700 text-[11px] font-bold">
                            <svg class="w-3 h-3 shrink-0" aria-hidden="true"><use href="#ldh-icon-shield-check"/></svg>
                            <span>Direct &amp; Safe</span>
                        </span>
                    </header>

                    <!-- Primary Protected Platform Messenger / Contact Modal CTA -->
                    @auth
                        <a
                            href="{{ route('account.messages.index', ['listing' => $listing->id, 'user' => $listing->user_id]) }}"
                            class="ldh-touch-target w-full py-3.5 px-5 rounded-2xl text-sm font-black transition-all flex items-center justify-between gap-3 group hover:opacity-95 active:scale-[0.99] cursor-pointer"
                            style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important; box-shadow: 0 12px 26px -6px rgba(242, 11, 101, 0.45) !important;"
                        >
                            <span class="flex items-center gap-2.5 min-w-0">
                                <span class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-white" aria-hidden="true"><use href="#ldh-icon-message-circle"/></svg>
                                </span>
                                <span class="text-left min-w-0">
                                    <span class="text-white font-black text-sm tracking-wide block leading-tight">Chat on Messenger</span>
                                    <span class="text-rose-100 text-[11px] font-semibold block leading-tight mt-0.5">Instant encrypted platform chat</span>
                                </span>
                            </span>
                            <svg class="w-4 h-4 text-white/90 shrink-0 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" aria-hidden="true"><use href="#ldh-icon-arrow-up-right"/></svg>
                        </a>
                    @else
                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'contact-modal')"
                            class="ldh-touch-target w-full py-3.5 px-5 rounded-2xl text-sm font-black transition-all flex items-center justify-between gap-3 group hover:opacity-95 active:scale-[0.99] cursor-pointer"
                            style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important; box-shadow: 0 12px 26px -6px rgba(242, 11, 101, 0.45) !important;"
                        >
                            <span class="flex items-center gap-2.5 min-w-0">
                                <span class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-white" aria-hidden="true"><use href="#ldh-icon-message-circle"/></svg>
                                </span>
                                <span class="text-left min-w-0">
                                    <span class="text-white font-black text-sm tracking-wide block leading-tight">Send Protected Message</span>
                                    <span class="text-rose-100 text-[11px] font-semibold block leading-tight mt-0.5">No account needed &bull; IP hidden</span>
                                </span>
                            </span>
                            <svg class="w-4 h-4 text-white/90 shrink-0 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" aria-hidden="true"><use href="#ldh-icon-arrow-up-right"/></svg>
                        </button>
                    @endauth

                    <!-- Public Email OR Encrypted Anonymous Email Relay -->
                    @php
                        $isEmailPublic = (bool) ($listing->contact?->show_email ?? ($listing->user?->preferences?->show_public_email ?? false));
                        $publicEmailAddress = $listing->contact?->preferred_email_encrypted ?: $listing->user?->email;
                    @endphp
                    @if($isEmailPublic && !empty($publicEmailAddress))
                        <div class="p-3.5 bg-sky-50/75 border border-sky-200/90 rounded-2xl text-xs space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sky-950 font-black text-[11px] flex items-center gap-1.5 min-w-0">
                                    <span class="w-6 h-6 rounded-lg bg-white border border-sky-200 text-sky-600 flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5" aria-hidden="true"><use href="#ldh-icon-globe"/></svg>
                                    </span>
                                    <span>Direct Public Email:</span>
                                </span>
                                <span class="ldh-copy-wrap shrink-0">
                                    <button
                                        type="button"
                                        class="ldh-copy-btn ldh-touch-target inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white hover:bg-sky-100/70 border border-sky-200 text-sky-700 text-[11px] font-black transition cursor-pointer"
                                        command="--copy"
                                        commandfor="ldh-public-email"
                                        aria-label="Copy direct public email address"
                                    >
                                        <svg class="ldh-icon-copy w-3.5 h-3.5 shrink-0" aria-hidden="true"><use href="#ldh-icon-copy"/></svg>
                                        <svg class="ldh-icon-check w-3.5 h-3.5 shrink-0" aria-hidden="true"><use href="#ldh-icon-check"/></svg>
                                        <span>Copy Email</span>
                                    </button>
                                    <span class="ldh-copy-tip" role="status"></span>
                                </span>
                            </div>
                            <a
                                href="mailto:{{ $publicEmailAddress }}?subject={{ rawurlencode('Regarding your LocalDateHub post: ' . $listing->title) }}"
                                class="font-mono text-slate-900 hover:text-[#F20B65] text-xs font-black flex items-center gap-2 break-all bg-white p-2.5 rounded-xl border border-sky-200/80 shadow-2xs transition"
                            >
                                <svg class="w-4 h-4 text-sky-600 shrink-0" aria-hidden="true"><use href="#ldh-icon-mail"/></svg>
                                <span id="ldh-public-email" class="select-all">{{ $publicEmailAddress }}</span>
                            </a>
                            <p class="ldh-card-prose text-[11px] text-sky-900/85 font-medium leading-snug">Advertiser has made their email public on this post for direct contact.</p>
                        </div>
                    @elseif($listing->contact?->allow_email_relay && $listing->contact?->masked_relay_email)
                        <div class="p-3.5 bg-slate-50/90 border border-slate-200/90 rounded-2xl text-xs space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-slate-800 font-black text-[11px] flex items-center gap-1.5 min-w-0">
                                    <span class="w-6 h-6 rounded-lg bg-white border border-slate-200 text-[#F20B65] flex items-center justify-center shrink-0 shadow-2xs">
                                        <svg class="w-3.5 h-3.5" aria-hidden="true"><use href="#ldh-icon-lock"/></svg>
                                    </span>
                                    <span>Encrypted Email Relay:</span>
                                </span>
                                <span class="ldh-copy-wrap shrink-0">
                                    <button
                                        type="button"
                                        class="ldh-copy-btn ldh-touch-target inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-[#F20B65] text-[11px] font-black transition cursor-pointer shadow-2xs"
                                        command="--copy"
                                        commandfor="ldh-relay-email"
                                        aria-label="Copy encrypted relay email address"
                                    >
                                        <svg class="ldh-icon-copy w-3.5 h-3.5 shrink-0" aria-hidden="true"><use href="#ldh-icon-copy"/></svg>
                                        <svg class="ldh-icon-check w-3.5 h-3.5 shrink-0" aria-hidden="true"><use href="#ldh-icon-check"/></svg>
                                        <span>Copy</span>
                                    </button>
                                    <span class="ldh-copy-tip" role="status"></span>
                                </span>
                            </div>
                            <button
                                type="button"
                                onclick="window.openLdhRelayModal && window.openLdhRelayModal()"
                                class="w-full text-left flex items-center justify-between gap-2 bg-white hover:border-rose-300 p-2.5 rounded-xl border border-slate-200/90 shadow-2xs transition group cursor-pointer"
                                title="Send encrypted email via LocalDateHub Relay"
                            >
                                <span class="flex items-center gap-2 min-w-0">
                                    <svg class="w-4 h-4 text-slate-400 group-hover:text-[#F20B65] shrink-0 transition-colors" aria-hidden="true"><use href="#ldh-icon-mail"/></svg>
                                    <code id="ldh-relay-email" class="font-mono text-slate-900 text-xs font-bold break-all select-all">{{ $listing->contact->masked_relay_email }}</code>
                                </span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 text-[#F20B65] text-[10px] font-extrabold shrink-0">
                                    <span>Compose</span>
                                    <svg class="w-3 h-3" aria-hidden="true"><use href="#ldh-icon-arrow-up-right"/></svg>
                                </span>
                            </button>
                            <p class="ldh-card-prose text-[11px] text-slate-500 font-medium leading-snug">Personal email is encrypted &amp; masked to protect advertiser privacy.</p>
                        </div>
                    @endif

                    <!-- External Phone Visibility (Only if explicitly enabled by poster) -->
                    @if($listing->contact?->show_phone && $listing->contact?->preferred_phone_encrypted)
                        <div class="p-3.5 bg-slate-50/90 border border-slate-200/90 rounded-2xl text-xs space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-slate-700 font-black text-[11px] flex items-center gap-1.5">
                                    <span class="w-6 h-6 rounded-lg bg-white border border-slate-200 text-emerald-600 flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5" aria-hidden="true"><use href="#ldh-icon-phone"/></svg>
                                    </span>
                                    <span>Telephone / SMS:</span>
                                </span>
                                <span class="ldh-copy-wrap shrink-0">
                                    <button
                                        type="button"
                                        class="ldh-copy-btn ldh-touch-target inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-black transition cursor-pointer"
                                        command="--copy"
                                        commandfor="ldh-phone-number"
                                        aria-label="Copy telephone number"
                                    >
                                        <svg class="ldh-icon-copy w-3.5 h-3.5 shrink-0" aria-hidden="true"><use href="#ldh-icon-copy"/></svg>
                                        <svg class="ldh-icon-check w-3.5 h-3.5 shrink-0" aria-hidden="true"><use href="#ldh-icon-check"/></svg>
                                        <span>Copy</span>
                                    </button>
                                    <span class="ldh-copy-tip" role="status"></span>
                                </span>
                            </div>
                            <a href="tel:{{ $listing->contact->preferred_phone_encrypted }}" id="ldh-phone-number" class="font-mono text-[#F20B65] hover:underline font-bold text-sm block bg-white p-2.5 rounded-xl border border-slate-200/90">
                                {{ $listing->contact->preferred_phone_encrypted }}
                            </a>
                        </div>
                    @endif

                    <!-- External Handle (e.g. Signal, Telegram) -->
                    @if($listing->contact?->external_handle_encrypted)
                        <div class="p-3.5 bg-slate-50/90 border border-slate-200/90 rounded-2xl text-xs space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-slate-700 font-black text-[11px] flex items-center gap-1.5">
                                    <span class="w-6 h-6 rounded-lg bg-white border border-slate-200 text-purple-600 flex items-center justify-center shrink-0">
                                        <svg class="w-3.5 h-3.5" aria-hidden="true"><use href="#ldh-icon-at-sign"/></svg>
                                    </span>
                                    <span>External Messenger:</span>
                                </span>
                                <span class="ldh-copy-wrap shrink-0">
                                    <button
                                        type="button"
                                        class="ldh-copy-btn ldh-touch-target inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white hover:bg-purple-50 border border-slate-200 text-purple-700 text-[11px] font-black transition cursor-pointer"
                                        command="--copy"
                                        commandfor="ldh-external-handle"
                                        aria-label="Copy external messenger handle"
                                    >
                                        <svg class="ldh-icon-copy w-3.5 h-3.5 shrink-0" aria-hidden="true"><use href="#ldh-icon-copy"/></svg>
                                        <svg class="ldh-icon-check w-3.5 h-3.5 shrink-0" aria-hidden="true"><use href="#ldh-icon-check"/></svg>
                                        <span>Copy</span>
                                    </button>
                                    <span class="ldh-copy-tip" role="status"></span>
                                </span>
                            </div>
                            <code id="ldh-external-handle" class="font-mono text-purple-700 font-black text-xs block select-all bg-white p-2.5 rounded-xl border border-slate-200/90 break-all">{{ $listing->contact->external_handle_encrypted }}</code>
                        </div>
                    @endif

                    <!-- Contact Instructions -->
                    @if($listing->contact?->contact_instructions)
                        <div class="p-3.5 bg-gradient-to-br from-slate-50 to-slate-100/60 border border-slate-200/90 rounded-2xl text-xs text-slate-800 space-y-1.5">
                            <strong class="text-slate-900 font-black flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#F20B65] shrink-0" aria-hidden="true"><use href="#ldh-icon-quote"/></svg>
                                <span>Advertiser's Instructions:</span>
                            </strong>
                            <p class="ldh-card-prose leading-relaxed font-medium text-slate-600">{{ $listing->contact->contact_instructions }}</p>
                        </div>
                    @endif
                </article>

                <!-- 4. Location & Anti-Triangulation Privacy Notice -->
                <article class="ldh-sidebar-card bg-white border border-slate-200/90 rounded-3xl p-5 sm:p-6 shadow-[0_12px_32px_-16px_rgba(15,23,42,0.12)] space-y-3.5">
                    <header class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100">
                        <h3 class="ldh-card-heading text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-sky-50 to-indigo-100/80 border border-sky-200/70 text-sky-600 flex items-center justify-center shrink-0 shadow-2xs">
                                <svg class="w-4 h-4" aria-hidden="true"><use href="#ldh-icon-radar"/></svg>
                            </span>
                            <span>Location &amp; Anti-Stalking</span>
                        </h3>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-sky-50 border border-sky-200/80 text-sky-700 text-[10px] font-extrabold uppercase tracking-wider">Fuzzed</span>
                    </header>

                    <div class="space-y-2 text-xs">
                        <div class="flex items-center gap-2.5 p-2.5 rounded-2xl bg-slate-50/90 border border-slate-200/75">
                            <span class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-sky-600 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" aria-hidden="true"><use href="#ldh-icon-map-pin"/></svg>
                            </span>
                            <p class="text-slate-700 font-bold leading-snug">
                                City: <strong class="text-slate-950 font-black">{{ $listing->city?->name }}, {{ $listing->state?->code }}</strong>
                            </p>
                        </div>

                        @if($listing->location?->neighborhood)
                            <div class="flex items-center gap-2.5 p-2.5 rounded-2xl bg-slate-50/90 border border-slate-200/75">
                                <span class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-indigo-600 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5" aria-hidden="true"><use href="#ldh-icon-compass"/></svg>
                                </span>
                                <p class="text-slate-600 font-medium leading-snug">
                                    Area / Neighborhood: <strong class="text-slate-900 font-bold">{{ $listing->location->neighborhood }}</strong>
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="p-3.5 bg-gradient-to-br from-sky-50/90 to-indigo-50/50 border border-sky-200/80 rounded-2xl text-[11px] text-slate-700 font-medium flex items-start gap-2.5">
                        <span class="w-6 h-6 rounded-lg bg-white border border-sky-200 text-sky-600 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" aria-hidden="true"><use href="#ldh-icon-lock"/></svg>
                        </span>
                        <span class="ldh-card-prose leading-relaxed">GPS coordinates are fuzzed by 400–800m to prevent residential stalking &amp; protect user privacy.</span>
                    </div>

                    @if($cityBrowseUrl)
                        <footer class="pt-1">
                            <a
                                href="{{ $cityBrowseUrl }}"
                                class="ldh-touch-target w-full inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-slate-50 hover:bg-sky-50 border border-slate-200 hover:border-sky-200 text-slate-700 hover:text-sky-800 text-xs font-black transition"
                            >
                                <span>Browse More in {{ $listing->city->name }}</span>
                                <svg class="w-3.5 h-3.5" aria-hidden="true"><use href="#ldh-icon-arrow-up-right"/></svg>
                            </a>
                        </footer>
                    @endif
                </article>

                <!-- 5. Trust & Safety Guidelines & Report Link -->
                <article class="ldh-sidebar-card bg-gradient-to-br from-rose-50/85 via-pink-50/40 to-white border border-rose-200/90 rounded-3xl p-5 sm:p-6 shadow-[0_12px_32px_-16px_rgba(242,11,101,0.14)] space-y-4">
                    <header class="flex items-center justify-between gap-2 pb-3 border-b border-rose-200/60">
                        <h3 class="ldh-card-heading text-xs font-black text-[#F20B65] uppercase tracking-wider flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-white border border-rose-200 text-[#F20B65] flex items-center justify-center shrink-0 shadow-2xs">
                                <svg class="w-4 h-4" aria-hidden="true"><use href="#ldh-icon-shield-check"/></svg>
                            </span>
                            <span>Safety First Guidelines</span>
                        </h3>
                        <span class="px-2 py-0.5 rounded-full bg-white border border-rose-200 text-[#F20B65] text-[10px] font-black uppercase tracking-wider">18+ Safe</span>
                    </header>

                    <ul class="text-xs text-slate-800 space-y-2.5 list-none font-semibold leading-relaxed">
                        <li class="flex items-start gap-2.5 p-2.5 rounded-2xl bg-white/80 border border-rose-100">
                            <span class="w-6 h-6 rounded-lg bg-emerald-50 border border-emerald-200/80 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5" aria-hidden="true"><use href="#ldh-icon-check-circle"/></svg>
                            </span>
                            <span class="ldh-card-prose">Always meet in busy public venues for first encounters.</span>
                        </li>
                        <li class="flex items-start gap-2.5 p-2.5 rounded-2xl bg-white/80 border border-rose-100">
                            <span class="w-6 h-6 rounded-lg bg-amber-50 border border-amber-200/80 text-amber-600 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5" aria-hidden="true"><use href="#ldh-icon-shield-alert"/></svg>
                            </span>
                            <span class="ldh-card-prose">Never wire gift cards, cash, or crypto to any advertiser.</span>
                        </li>
                        <li class="flex items-start gap-2.5 p-2.5 rounded-2xl bg-white/80 border border-rose-100">
                            <span class="w-6 h-6 rounded-lg bg-rose-50 border border-rose-200/80 text-[#F20B65] flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5" aria-hidden="true"><use href="#ldh-icon-ban"/></svg>
                            </span>
                            <span class="ldh-card-prose">Commercial sexual services &amp; prostitution are strictly prohibited.</span>
                        </li>
                    </ul>

                    <footer class="pt-1 border-t border-rose-200/70">
                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'report-modal')"
                            class="ldh-touch-target w-full py-2.5 px-3 rounded-2xl bg-white hover:bg-rose-50 border border-rose-200/90 text-xs text-[#F20B65] hover:text-rose-700 font-black inline-flex items-center justify-center gap-2 transition shadow-2xs cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 shrink-0" aria-hidden="true"><use href="#ldh-icon-flag"/></svg>
                            <span>Report Community Violation</span>
                        </button>
                    </footer>
                </article>

                <!-- 6. Sidebar Advertisement Slot -->
                <x-ad-banner placement="sidebar" :context="$adContext ?? []" variant="sidebar" :limit="2" />

            </aside>

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

        <!-- Floating Mobile Contact Bar (Safe-Area Padded Touch Bar) -->
        <div class="ldh-mobile-bar lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 pt-3 pb-3 flex items-center justify-between gap-3 shadow-2xl">
            <div class="min-w-0 flex-1 flex items-center gap-2.5">
                <div class="relative shrink-0">
                    @if($posterAvatarUrl)
                        <img
                            src="{{ $posterAvatarUrl }}"
                            alt="{{ $posterName }}"
                            loading="lazy"
                            decoding="async"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            class="w-10 h-10 rounded-xl object-cover shadow-xs block"
                        >
                        <div
                            style="display:none; background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important;"
                            class="w-10 h-10 rounded-xl items-center justify-center font-black text-sm"
                            aria-hidden="true"
                        >
                            {{ $posterInitial }}
                        </div>
                    @else
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm"
                            style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important;"
                            aria-hidden="true"
                        >
                            {{ $posterInitial }}
                        </div>
                    @endif
                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white {{ $posterIsOnline ? 'bg-emerald-500' : 'bg-slate-400' }}" aria-hidden="true"></span>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-xs font-black text-slate-900 block truncate">{{ $listing->title }}</span>
                    <span class="text-[10px] text-slate-500 font-bold block truncate">{{ $listing->city?->name }}, {{ $listing->state?->code }}</span>
                </div>
            </div>
            @auth
                <a
                    href="{{ route('account.messages.index', ['listing' => $listing->id, 'user' => $listing->user_id]) }}"
                    class="ldh-touch-target py-2.5 px-4 rounded-xl shadow-md shrink-0 inline-flex items-center gap-1.5 font-black text-xs"
                    style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important;"
                >
                    <svg class="w-4 h-4 text-white shrink-0" aria-hidden="true"><use href="#ldh-icon-message-circle"/></svg>
                    <span class="text-white">Messenger</span>
                </a>
            @else
                <button
                    type="button"
                    @click="$dispatch('open-modal', 'contact-modal')"
                    class="ldh-touch-target py-2.5 px-4 rounded-xl shadow-md shrink-0 inline-flex items-center gap-1.5 font-black text-xs cursor-pointer"
                    style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important;"
                >
                    <svg class="w-4 h-4 text-white shrink-0" aria-hidden="true"><use href="#ldh-icon-message-circle"/></svg>
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

                <x-turnstile action="listing_contact" />

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
            <form
                action="{{ route('listings.report.submit', $listing->id) }}"
                method="POST"
                class="space-y-4"
                x-data="{
                    reason: @js(old('report_reason', '')),
                    details: @js(old('report_details', '')),
                    dropdownOpen: false,
                    submitting: false,
                    hasReportErrors: @js($errors->hasAny(['report_reason', 'report_details', 'reporter_name', 'reporter_email', 'cf-turnstile-response'])),
                    categories: [
                        { value: 'underage_minor', title: 'Underage or Minor-Related Content', desc: 'Immediate 24/7 escalation to law enforcement', badge: 'Critical', color: 'rose', icon: 'shield' },
                        { value: 'commercial_sex', title: 'Commercial Sex, Prostitution or Escort', desc: 'Strict FOSTA-SESTA policy violation', badge: 'Critical', color: 'rose', icon: 'ban' },
                        { value: 'non_consensual_content', title: 'Non-Consensual or Stolen Photos', desc: 'Unauthorized private or stolen imagery', badge: 'High', color: 'amber', icon: 'eye' },
                        { value: 'fraud_scam', title: 'Financial Scam, Wire or Impersonation', desc: 'Money request, catfish, or fake profile', badge: 'High', color: 'amber', icon: 'alert' },
                        { value: 'harassment', title: 'Harassment, Stalking or Hate Speech', desc: 'Abusive, threatening, or hateful conduct', badge: 'Safety', color: 'indigo', icon: 'chat' },
                        { value: 'other', title: 'Other Community Standards Violation', desc: 'Spam, duplicate, or general rule breach', badge: 'General', color: 'slate', icon: 'flag' }
                    ],
                    selectedCategory() {
                        return this.categories.find(c => c.value === this.reason) || null;
                    }
                }"
                x-init="if (hasReportErrors) { $nextTick(() => $dispatch('open-modal', 'report-modal')); }"
                @submit="if (!reason || details.trim().length < 10 || submitting) { $event.preventDefault(); return; } setTimeout(() => { submitting = true; }, 0);"
            >
                @csrf
                <!-- Anti-Spam Honeypot -->
                <input type="text" name="report_hp" style="display:none;" tabindex="-1" autocomplete="off">
                <input type="hidden" name="report_reason" x-model="reason">

                <!-- Trust & Safety Header Banner -->
                <div class="flex items-start gap-3 p-3.5 rounded-2xl bg-gradient-to-r from-rose-50 via-pink-50/70 to-amber-50/50 border border-rose-200/70">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#F20B65] via-rose-600 to-orange-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-rose-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                    <div class="text-xs leading-relaxed">
                        <p class="font-extrabold text-slate-900">Confidential Trust &amp; Safety Report</p>
                        <p class="text-slate-600 mt-0.5">Reports involving minors or trafficking are escalated 24/7 to law enforcement &amp; NCMEC. Your identity stays private.</p>
                    </div>
                </div>

                <!-- Step 1: Advanced Custom Dropdown with Icons & Severity Badges -->
                <div class="relative" @click.outside="dropdownOpen = false" @keydown.escape.window="dropdownOpen = false">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-[11px] font-extrabold text-slate-800 uppercase tracking-wider">
                            1. Violation Category <span class="text-[#F20B65]">*</span>
                        </label>
                        <span x-show="!reason" class="text-[11px] font-semibold text-rose-500">Select a reason</span>
                    </div>

                    <button
                        type="button"
                        @click="dropdownOpen = !dropdownOpen"
                        :class="dropdownOpen ? 'border-[#F20B65] ring-2 ring-[#F20B65]/20 bg-white' : 'border-slate-200 bg-slate-50/80 hover:bg-white hover:border-slate-300'"
                        class="w-full rounded-xl border px-3.5 py-2.5 text-left transition flex items-center justify-between gap-3"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <template x-if="!selectedCategory()">
                                <span class="w-7 h-7 rounded-lg bg-slate-200/70 text-slate-500 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" />
                                    </svg>
                                </span>
                            </template>
                            <template x-if="selectedCategory()">
                                <span
                                    :class="{
                                        'bg-rose-100 text-rose-600': selectedCategory().color === 'rose',
                                        'bg-amber-100 text-amber-600': selectedCategory().color === 'amber',
                                        'bg-indigo-100 text-indigo-600': selectedCategory().color === 'indigo',
                                        'bg-slate-200 text-slate-600': selectedCategory().color === 'slate'
                                    }"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                </span>
                            </template>

                            <div class="min-w-0">
                                <div
                                    class="text-xs font-bold truncate"
                                    :class="selectedCategory() ? 'text-slate-900' : 'text-slate-500'"
                                    x-text="selectedCategory() ? selectedCategory().title : 'Select Primary Violation Reason...'"
                                ></div>
                                <div
                                    x-show="selectedCategory()"
                                    class="text-[11px] text-slate-500 truncate"
                                    x-text="selectedCategory() ? selectedCategory().desc : ''"
                                ></div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <template x-if="selectedCategory()">
                                <span
                                    :class="{
                                        'bg-rose-100 text-rose-700': selectedCategory().color === 'rose',
                                        'bg-amber-100 text-amber-800': selectedCategory().color === 'amber',
                                        'bg-indigo-100 text-indigo-700': selectedCategory().color === 'indigo',
                                        'bg-slate-200 text-slate-700': selectedCategory().color === 'slate'
                                    }"
                                    class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wide"
                                    x-text="selectedCategory().badge"
                                ></span>
                            </template>
                            <svg
                                class="w-4 h-4 text-slate-400 transition-transform duration-150"
                                :class="dropdownOpen ? 'rotate-180 text-[#F20B65]' : ''"
                                fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </button>

                    <!-- Floating Dropdown Options Menu -->
                    <div
                        x-show="dropdownOpen"
                        x-transition:enter="transition ease-out duration-120"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        style="display: none;"
                        class="absolute left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl shadow-slate-900/10 z-50 overflow-hidden divide-y divide-slate-100 max-h-64 overflow-y-auto"
                    >
                        <template x-for="cat in categories" :key="cat.value">
                            <button
                                type="button"
                                @click="reason = cat.value; dropdownOpen = false"
                                :class="reason === cat.value ? 'bg-pink-50/70' : 'hover:bg-slate-50'"
                                class="w-full px-3.5 py-2.5 text-left flex items-center justify-between gap-3 transition"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span
                                        :class="{
                                            'bg-rose-100 text-rose-600': cat.color === 'rose',
                                            'bg-amber-100 text-amber-600': cat.color === 'amber',
                                            'bg-indigo-100 text-indigo-600': cat.color === 'indigo',
                                            'bg-slate-100 text-slate-600': cat.color === 'slate'
                                        }"
                                        class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                                    >
                                        <template x-if="cat.icon === 'shield'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286z" /></svg>
                                        </template>
                                        <template x-if="cat.icon === 'ban'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        </template>
                                        <template x-if="cat.icon === 'eye'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                        </template>
                                        <template x-if="cat.icon === 'alert'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </template>
                                        <template x-if="cat.icon === 'chat'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                        </template>
                                        <template x-if="cat.icon === 'flag'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0l2.77-.693a9 9 0 016.208.682l.108.054a9 9 0 006.086.71l3.114-.732a48.524 48.524 0 01-.005-10.499l-3.11.732a9 9 0 01-6.085-.711l-.108-.054a9 9 0 00-6.208-.682L3 4.5M3 15V4.5" /></svg>
                                        </template>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-slate-900 truncate" x-text="cat.title"></div>
                                        <div class="text-[11px] text-slate-500 truncate" x-text="cat.desc"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span
                                        :class="{
                                            'bg-rose-100 text-rose-700': cat.color === 'rose',
                                            'bg-amber-100 text-amber-800': cat.color === 'amber',
                                            'bg-indigo-100 text-indigo-700': cat.color === 'indigo',
                                            'bg-slate-100 text-slate-600': cat.color === 'slate'
                                        }"
                                        class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wide"
                                        x-text="cat.badge"
                                    ></span>
                                    <svg x-show="reason === cat.value" class="w-4 h-4 text-[#F20B65]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </div>
                            </button>
                        </template>
                    </div>

                    @error('report_reason')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Step 2: Explanation with Live Character Counter -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="report-details" class="text-[11px] font-extrabold text-slate-800 uppercase tracking-wider">
                            2. Explanation &amp; Context <span class="text-[#F20B65]">*</span>
                        </label>
                        <span
                            class="text-[11px] font-semibold"
                            :class="details.trim().length >= 10 ? 'text-emerald-600' : 'text-amber-600'"
                            x-text="details.trim().length < 10 ? (10 - details.trim().length) + ' more chars needed (min 10)' : details.length + ' / 3000 chars'"
                        ></span>
                    </div>
                    <textarea
                        id="report-details"
                        name="report_details"
                        x-model="details"
                        rows="3"
                        minlength="10"
                        maxlength="3000"
                        required
                        class="w-full bg-slate-50/80 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B65] focus:ring-2 focus:ring-[#F20B65]/20 transition"
                        placeholder="Please describe what happened or which policy this listing violates (minimum 10 characters)..."
                    ></textarea>
                    @error('report_details')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Step 3: Optional Contact Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
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

                <!-- Turnstile Security Check -->
                <div class="rounded-xl bg-slate-50/70 border border-slate-200/80 p-2.5">
                    <x-turnstile action="listing_report" />
                    @error('cf-turnstile-response')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Footer Actions -->
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-3">
                    <span class="text-[11px] text-slate-400 hidden sm:inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        Protected &amp; encrypted submission
                    </span>
                    <div class="flex items-center gap-2.5 ml-auto">
                        <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'report-modal')">
                            Cancel
                        </x-button>
                        <button
                            type="submit"
                            :disabled="!reason || details.trim().length < 10"
                            :class="{
                                'opacity-50 cursor-not-allowed': !reason || details.trim().length < 10,
                                'opacity-80 pointer-events-none': submitting,
                                'hover:opacity-95 shadow-md shadow-rose-500/20': reason && details.trim().length >= 10 && !submitting
                            }"
                            class="px-5 py-2.5 rounded-xl font-extrabold text-xs text-white transition inline-flex items-center gap-2"
                            style="background: linear-gradient(135deg, #E11D48 0%, #BE123C 100%) !important;"
                        >
                            <svg x-show="!submitting" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                            <span x-text="submitting ? 'Submitting...' : 'Submit Confidential Report'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </x-modal>
    </div>

    @once
        <script>
            (() => {
                if (window.__ldhCopyInitialized) return;
                window.__ldhCopyInitialized = true;

                const timers = new WeakMap();
                let lastMessage = '';

                function legacyCopy(text) {
                    const field = Object.assign(document.createElement('textarea'), { value: text });
                    field.setAttribute('readonly', '');
                    field.style.cssText = 'position:fixed;inset-block-start:0;opacity:0';
                    document.body.append(field);
                    field.select();
                    try {
                        return document.execCommand('copy');
                    } finally {
                        field.remove();
                    }
                }

                function report(button, ok) {
                    const tip = button.nextElementSibling;
                    if (!tip) return;
                    clearTimeout(timers.get(button));
                    button.removeAttribute('data-copied');
                    button.removeAttribute('data-failed');
                    let message = ok ? 'Copied!' : 'Copy failed';
                    if (message === lastMessage) message += '\u00A0';
                    lastMessage = message;
                    tip.textContent = '';
                    void button.offsetWidth;
                    button.setAttribute(ok ? 'data-copied' : 'data-failed', '');
                    requestAnimationFrame(() => {
                        tip.textContent = message;
                    });
                    timers.set(button, setTimeout(() => {
                        button.removeAttribute('data-copied');
                        button.removeAttribute('data-failed');
                        tip.textContent = '';
                    }, 2000));
                }

                async function copyFromTarget(button, target) {
                    if (!button || !target) return;
                    const text = target.matches('input, textarea')
                        ? target.value.trim()
                        : target.innerText.trim();
                    if (!text) return;
                    try {
                        if (!navigator.clipboard?.writeText) {
                            throw new Error('Clipboard unavailable');
                        }
                        await navigator.clipboard.writeText(text);
                        report(button, true);
                    } catch {
                        report(button, legacyCopy(text));
                    }
                }

                document.addEventListener('command', (event) => {
                    if (event.command === '--copy') {
                        copyFromTarget(event.source, event.target);
                    }
                }, true);

                if (!('CommandEvent' in window)) {
                    document.addEventListener('click', (event) => {
                        const button = event.target.closest('button[command="--copy"]');
                        if (!button) return;
                        const target = document.getElementById(button.getAttribute('commandfor'));
                        if (target) copyFromTarget(button, target);
                    });
                }
            })();
        </script>
    @endonce

    @if($listing->contact?->allow_email_relay && $listing->contact?->masked_relay_email && !$listing->contact?->show_email)
        <div id="ldh-relay-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="ldh-relay-modal-title">
            <div class="w-full max-w-lg rounded-2xl bg-white border border-slate-200 shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 bg-slate-900 text-white">
                    <div>
                        <h3 id="ldh-relay-modal-title" class="text-sm font-black tracking-tight">Send Encrypted Email</h3>
                        <p class="text-[11px] text-slate-300 font-mono mt-0.5">{{ $listing->contact->masked_relay_email }}</p>
                    </div>
                    <button type="button" onclick="window.closeLdhRelayModal && window.closeLdhRelayModal()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center cursor-pointer" aria-label="Close email relay modal">
                        ✕
                    </button>
                </div>

                <form id="ldh-relay-form" class="p-5 space-y-3.5 text-xs" enctype="multipart/form-data">
                    @csrf
                    <div id="ldh-relay-alert" class="hidden rounded-xl p-3 font-semibold"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Your Name</label>
                            <input type="text" name="sender_name" required maxlength="100" value="{{ auth()->user()?->name }}" placeholder="Your name" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-[#F50057]">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Your Email</label>
                            <input type="email" name="sender_email" required maxlength="190" value="{{ auth()->user()?->email }}" placeholder="you@example.com" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-[#F50057]">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Subject</label>
                        <input type="text" name="subject" required maxlength="180" value="Inquiry: {{ $listing->title }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-[#F50057]">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Message</label>
                        <textarea name="message" rows="4" required minlength="10" maxlength="4000" placeholder="Write your private message..." class="w-full px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-[#F50057]"></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Optional Attachments <span class="text-slate-400 font-normal">(Max 3 files, 5MB each: JPG, PNG, WEBP, PDF, TXT)</span></label>
                        <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" class="w-full text-[11px] text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-rose-50 file:text-[#F50057] file:font-bold">
                    </div>

                    <div class="pt-2 flex items-center justify-between gap-3">
                        <span class="text-[11px] text-slate-500">Protected by 2-way encrypted relay.</span>
                        <button type="submit" id="ldh-relay-submit" class="px-4 py-2.5 rounded-xl bg-[#F50057] hover:bg-[#D8004D] text-white font-extrabold cursor-pointer transition">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            (function () {
                const modal = document.getElementById('ldh-relay-modal');
                const form = document.getElementById('ldh-relay-form');
                const alertBox = document.getElementById('ldh-relay-alert');
                const submitBtn = document.getElementById('ldh-relay-submit');

                window.openLdhRelayModal = function () {
                    if (!modal) return;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                };

                window.closeLdhRelayModal = function () {
                    if (!modal) return;
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                };

                modal?.addEventListener('click', function (e) {
                    if (e.target === modal) window.closeLdhRelayModal();
                });

                form?.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    if (!submitBtn || !alertBox) return;

                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Sending...';
                    alertBox.className = 'hidden rounded-xl p-3 font-semibold';

                    try {
                        const res = await fetch(@json(route('listings.email-relay.send', $listing->id)), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: new FormData(form)
                        });
                        const data = await res.json();

                        alertBox.classList.remove('hidden');
                        if (res.ok && data.ok) {
                            alertBox.classList.add('bg-emerald-50', 'text-emerald-800', 'border', 'border-emerald-200');
                            alertBox.textContent = data.message || 'Your message has been securely sent through LocalDateHub. Your personal email address remains private.';
                            form.reset();
                        } else {
                            const firstErr = data.errors ? Object.values(data.errors).flat()[0] : null;
                            alertBox.classList.add('bg-rose-50', 'text-rose-800', 'border', 'border-rose-200');
                            alertBox.textContent = firstErr || data.message || 'Unable to send relay message.';
                        }
                    } catch (err) {
                        alertBox.classList.remove('hidden');
                        alertBox.classList.add('bg-rose-50', 'text-rose-800', 'border', 'border-rose-200');
                        alertBox.textContent = 'Network error while sending encrypted relay message.';
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Send Message';
                    }
                });
            })();
        </script>
    @endif
</x-layouts.app>
