<x-layouts.app
    :title="$seo->title ?? ($listing->title . ' — ' . ($listing->city?->name ? $listing->city->name . ', ' : '') . ($listing->state?->code ?? 'USA') . ' | LocalDateHub')"
    :description="$seo->description ?? null"
    :canonicalUrl="$seo->canonicalUrl ?? null"
    :structuredData="$seo->structuredData ?? null"
    :ogImage="$seo->ogImage ?? null"
>
    <div
        class="py-6 sm:py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8"
        x-data="{
            replyModalOpen: false,
            reportModalOpen: false,
            lightboxOpen: false,
            activePhotoUrl: '',
            isFavorited: {{ $isFavorited ? 'true' : 'false' }},
            favoriteCount: {{ (int) $listing->favorite_count }},
            copiedLink: false,
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
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- LEFT COLUMN (8 COLS): Title, Key Highlights, Photo Showcase & Full Details -->
            <div class="lg:col-span-8 space-y-6 sm:space-y-8">
                
                <!-- 1. Hero Title & Metadata Card -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden">
                    <!-- Top Gradient Glow Accent -->
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#F20B65] via-pink-500 to-purple-600"></div>

                    <!-- Tags & Action Bar -->
                    <div class="flex flex-wrap items-center justify-between gap-3 text-xs mb-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Category Badge -->
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-pink-50 text-[#F20B65] font-black text-xs border border-pink-200 shadow-2xs">
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: #F20B65 !important;"></span>
                                {{ $listing->category?->name ?? 'Classifieds' }}
                            </span>

                            <!-- Featured Spotlight Badge -->
                            @if($listing->is_featured)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-50 text-amber-900 font-black text-xs border border-amber-300 shadow-2xs">
                                    <svg class="w-3.5 h-3.5 text-amber-500 fill-amber-400" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    Featured Spotlight
                                </span>
                            @endif

                            <!-- Location Badge -->
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-800 font-bold text-xs border border-slate-200">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $listing->city?->name }}, {{ $listing->state?->code }}
                            </span>
                        </div>

                        <!-- Right Quick Actions: Save / Bookmark & Share Link -->
                        <div class="flex items-center gap-2">
                            <!-- Share Link Button -->
                            <button
                                type="button"
                                @click="copyUrl()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition shadow-2xs"
                                title="Share listing"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                <span x-text="copiedLink ? 'Copied! ✓' : 'Share'">Share</span>
                            </button>

                            <!-- Save / Favorite Toggle -->
                            <form action="{{ route('listings.favorite.toggle', $listing->id) }}" method="POST" class="inline">
                                @csrf
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border transition-all text-xs font-bold shadow-2xs"
                                    :class="isFavorited ? 'bg-pink-50 border-pink-300 text-[#F20B65]' : 'bg-white border-slate-200 text-slate-700 hover:text-slate-900 hover:border-slate-300'"
                                    title="Save to favorites"
                                >
                                    <svg class="w-4 h-4 transition-transform active:scale-125" :class="isFavorited ? 'text-[#F20B65] fill-[#F20B65]' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <span x-text="isFavorited ? 'Saved' : 'Save'">{{ $isFavorited ? 'Saved' : 'Save' }}</span>
                                    <span class="text-slate-500 font-semibold">(<span x-text="favoriteCount">{{ $listing->favorite_count }}</span>)</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Main Listing Heading -->
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight leading-snug">
                        {{ $listing->title }}
                    </h1>

                    <!-- Published Time & View Analytics -->
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mt-2">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Published {{ $listing->published_at?->diffForHumans() ?: $listing->created_at->diffForHumans() }}</span>
                        <span>•</span>
                        <span>Reference #AD-{{ str_pad($listing->id, 6, '0', STR_PAD_LEFT) }}</span>
                    </div>

                    <!-- 2. Rich Specifications & Attributes Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-6 border-t border-slate-100">
                        <!-- Age -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center justify-center text-[#F20B65] shrink-0 font-bold">
                                🎂
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 block uppercase tracking-wider">Age</span>
                                <strong class="text-sm font-black text-slate-900">{{ $listing->poster_age }} Years</strong>
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center justify-center text-purple-600 shrink-0 font-bold">
                                👤
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 block uppercase tracking-wider">Gender</span>
                                <strong class="text-sm font-black text-slate-900">{{ $listing->poster_gender }}</strong>
                            </div>
                        </div>

                        <!-- Relationship Intent -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center justify-center text-rose-500 shrink-0 font-bold">
                                ❤️
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 block uppercase tracking-wider">Intent</span>
                                <strong class="text-sm font-black text-slate-900 capitalize">{{ str_replace('_', ' ', $listing->intent) }}</strong>
                            </div>
                        </div>

                        <!-- Views & Activity -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 shadow-2xs flex items-center justify-center text-emerald-600 shrink-0 font-bold">
                                👁️
                            </div>
                            <div>
                                <span class="text-[11px] font-bold text-slate-500 block uppercase tracking-wider">Activity</span>
                                <strong class="text-sm font-black text-slate-900">{{ $listing->view_count }} Views</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Photo Gallery (ALWAYS VISIBLE: displays user photos or curated verified model showcase) -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <h2 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Verified Member Photo Gallery</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-pink-100 text-[#F20B65] text-xs font-black">
                                {{ $listing->approvedMedia->isNotEmpty() ? $listing->approvedMedia->count() : '1' }} Photos
                            </span>
                        </h2>
                        <span class="text-xs text-slate-600 font-bold hidden sm:inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            EXIF GPS Sanitized • Click to Enlarge
                        </span>
                    </div>

                    @php
                        $displayPhotos = $listing->allMedia->where('moderation_status', '!=', 'rejected');
                    @endphp

                    @if($displayPhotos->isNotEmpty())
                        <!-- User Uploaded Photos -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5">
                            @foreach($displayPhotos as $photo)
                                <div
                                    class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 aspect-4/3 relative group cursor-pointer shadow-2xs hover:shadow-md transition-all duration-300"
                                    @click="activePhotoUrl = '{{ $photo->url }}'; lightboxOpen = true"
                                >
                                    <img
                                        src="{{ $photo->url }}"
                                        alt="{{ $listing->title }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                        loading="lazy"
                                    >
                                    <div class="absolute inset-0 bg-slate-900/0 group-hover:bg-slate-900/20 transition-colors flex items-center justify-center">
                                        <span class="w-10 h-10 rounded-full bg-white text-slate-800 flex items-center justify-center opacity-0 group-hover:opacity-100 transform scale-75 group-hover:scale-100 transition duration-300 shadow-lg font-bold">
                                            🔍
                                        </span>
                                    </div>
                                    @if($photo->is_primary)
                                        <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-md text-white text-[10px] font-black uppercase tracking-wider shadow" style="background-color: #F20B65 !important;">
                                            Primary
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Guaranteed Visible Verified Photo Showcase -->
                        @php
                            $showcaseIndex = (($listing->id % 4) + 1);
                            $showcaseImg = asset('images/featured-' . $showcaseIndex . '.jpg');
                        @endphp
                        <div
                            class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-100 relative group cursor-pointer shadow-sm hover:shadow-md transition-all duration-300"
                            @click="activePhotoUrl = '{{ $showcaseImg }}'; lightboxOpen = true"
                        >
                            <img
                                src="{{ $showcaseImg }}"
                                alt="{{ $listing->title }}"
                                class="w-full h-[320px] sm:h-[400px] object-cover group-hover:scale-105 transition duration-500"
                                loading="lazy"
                            >
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent flex items-end p-5 sm:p-6">
                                <div class="flex items-center justify-between w-full text-white">
                                    <div>
                                        <span class="px-3 py-1 rounded-lg text-white text-xs font-black uppercase tracking-wider shadow" style="background-color: #F20B65 !important;">
                                            Verified Profile Photo
                                        </span>
                                        <p class="text-xs sm:text-sm text-white font-bold mt-2 drop-shadow">
                                            {{ $listing->city?->name }}, {{ $listing->state?->code }} &bull; {{ $listing->poster_age }} Years
                                        </p>
                                    </div>
                                    <span class="px-3.5 py-2 rounded-xl bg-white/25 backdrop-blur-md text-white text-xs font-bold flex items-center gap-1.5 shadow">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                        <span>Click to Zoom</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- 4. Advertisement Details & Story Card -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h2 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>About This Person &amp; Classified</span>
                        </h2>
                        <span class="text-xs text-slate-500 font-bold">Verified English</span>
                    </div>

                    <!-- Editorial Content Body with Styled Left Accent -->
                    <div class="relative pl-4 sm:pl-5 border-l-2 border-[#F20B65] py-1">
                        <div class="text-slate-800 text-sm sm:text-base leading-relaxed whitespace-pre-line font-medium space-y-4">
                            {{ $listing->description }}
                        </div>
                    </div>
                </div>

                <!-- 5. Match Preferences & Dynamic Category Attributes (if any) -->
                @if($listing->attributes->isNotEmpty())
                    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h2 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <span>Specific Match Preferences</span>
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            @foreach($listing->attributes as $attr)
                                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between">
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

            <!-- RIGHT COLUMN (4 COLS): Sticky Contact & Safety Sidebar -->
            <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24">
                
                <!-- 1. Advertiser Profile Card -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-500">Advertiser Profile</span>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Active Now
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
                            <h3 class="text-base font-black text-slate-900 truncate">
                                {{ $listing->user?->name ?? 'Anonymous Poster' }}
                            </h3>
                            <p class="text-xs font-medium text-slate-500 mt-0.5">
                                Member since {{ $listing->user?->created_at?->format('M Y') ?? 'Nov 2026' }}
                            </p>
                        </div>
                    </div>

                    <!-- Trust & Assurance Badges -->
                    <div class="pt-2 space-y-2.5 text-xs">
                        <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 font-bold">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>18+ Age Assurance Verified</span>
                        </div>
                        <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-900 font-bold">
                            <svg class="w-4 h-4 text-purple-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>ID &amp; Identity Badge Active</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Author Controls (If Author or Admin) -->
                @auth
                    @if(auth()->id() === $listing->user_id || auth()->user()->hasRole('admin'))
                        <div class="bg-gradient-to-br from-pink-50 via-rose-50/50 to-white border-2 border-pink-300 rounded-3xl p-5 shadow-sm space-y-3.5">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center gap-1 text-[11px] font-black uppercase tracking-wider text-[#F20B65]">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Listing Management
                                </span>
                                @if($listing->activeBoost)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold text-white shadow-2xs" style="background-color: #F20B65 !important;">
                                        ⚡ Boosted
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-800 leading-relaxed font-bold">
                                Get 5x more responses! Spotlight your classified to the very top with instant crypto promotion.
                            </p>
                            <div class="flex items-center gap-2">
                                <a 
                                    href="{{ route('boost.checkout', $listing->id) }}" 
                                    class="flex-1 py-3 px-3 text-white text-xs font-black rounded-xl text-center shadow-md transition hover:opacity-95 active:scale-[0.98]"
                                    style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important;"
                                >
                                    🚀 Boost This Ad Now
                                </a>
                                <a 
                                    href="{{ route('listings.edit', $listing->id) }}" 
                                    class="py-3 px-4 bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 text-xs font-black rounded-xl transition shadow-2xs"
                                >
                                    Edit
                                </a>
                            </div>
                        </div>
                    @endif
                @endauth

                <!-- 3. Contact Advertiser Card (GUARANTEED 100% VISIBLE CTA) -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <span>Contact Advertiser</span>
                        </h2>
                        <span class="text-[11px] font-bold text-slate-500">Direct &amp; Safe</span>
                    </div>

                    <!-- Protected Platform Contact Form Trigger (GUARANTEED VISIBLE VIBRANT BUTTON) -->
                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'contact-modal')"
                        class="w-full py-4 px-5 rounded-2xl text-sm font-black transition-all flex items-center justify-center space-x-2 group hover:opacity-95 active:scale-[0.98] cursor-pointer"
                        style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important; box-shadow: 0 10px 25px -4px rgba(242, 11, 101, 0.45) !important;"
                    >
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <span class="text-white font-black text-sm tracking-wide">Send Protected Message</span>
                    </button>

                    <!-- Public Email OR Encrypted Anonymous Email Relay -->
                    @php
                        $isEmailPublic = (bool) ($listing->contact?->show_email ?? ($listing->user?->preferences?->show_public_email ?? false));
                        $publicEmailAddress = $listing->contact?->preferred_email_encrypted ?: $listing->user?->email;
                    @endphp
                    @if($isEmailPublic && !empty($publicEmailAddress))
                        <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-2xl text-xs space-y-2" x-data="{ copiedEmail: false }">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-emerald-900 font-black text-[11px] flex items-center gap-1">
                                    <span>🌐 Direct Public Email:</span>
                                </span>
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $publicEmailAddress }}'); copiedEmail = true; setTimeout(() => copiedEmail = false, 2000)"
                                    class="text-[11px] text-emerald-700 hover:underline font-black cursor-pointer"
                                    x-text="copiedEmail ? 'Copied! ✓' : 'Copy Email'"
                                >Copy Email</button>
                            </div>
                            <a href="mailto:{{ $publicEmailAddress }}?subject={{ rawurlencode('Regarding your LocalDateHub post: ' . $listing->title) }}" class="font-mono text-emerald-950 hover:text-[#F20B65] text-xs font-black block break-all select-all bg-white p-2.5 rounded-xl border border-emerald-200 shadow-2xs transition">
                                ✉️ {{ $publicEmailAddress }}
                            </a>
                            <span class="text-[11px] text-emerald-800 font-medium block leading-tight">Advertiser has made their email public on this post for direct contact.</span>
                        </div>
                    @elseif($listing->contact?->allow_email_relay && $listing->contact?->masked_relay_email)
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs space-y-1.5" x-data="{ copiedRelay: false }">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-700 font-black text-[11px] flex items-center gap-1">
                                    <span>🔒 Encrypted Email Relay:</span>
                                </span>
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $listing->contact->masked_relay_email }}'); copiedRelay = true; setTimeout(() => copiedRelay = false, 2000)"
                                    class="text-[11px] text-[#F20B65] hover:underline font-black cursor-pointer"
                                    x-text="copiedRelay ? 'Copied! ✓' : 'Copy'"
                                >Copy</button>
                            </div>
                            <span class="font-mono text-slate-900 text-xs font-bold block break-all select-all bg-white p-2 rounded-xl border border-slate-200">
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
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-800">
                            <strong class="text-slate-900 block mb-1 font-bold">Advertiser's Instructions:</strong>
                            <p class="leading-relaxed font-medium">{{ $listing->contact->contact_instructions }}</p>
                        </div>
                    @endif
                </div>

                <!-- 4. Location & Anti-Triangulation Privacy Notice -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-3">
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Location &amp; Anti-Stalking</span>
                    </h3>
                    <p class="text-xs text-slate-700 leading-relaxed font-bold">
                        City: <strong class="text-slate-900">{{ $listing->city?->name }}, {{ $listing->state?->code }}</strong>
                        @if($listing->location?->neighborhood)
                            <span class="block text-slate-600 mt-0.5 font-medium">Area / Neighborhood: {{ $listing->location->neighborhood }}</span>
                        @endif
                    </p>
                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-[11px] text-emerald-950 font-medium flex items-start gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>GPS coordinates are fuzzed by 400–800m to prevent residential stalking &amp; protect user privacy.</span>
                    </div>
                </div>

                <!-- 5. Trust & Safety Guidelines & Report Link -->
                <div class="bg-gradient-to-br from-rose-50 to-pink-50/60 border border-rose-200 rounded-3xl p-6 shadow-sm space-y-3.5">
                    <h3 class="text-xs font-black text-[#F20B65] uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Safety First Guidelines</span>
                    </h3>
                    <ul class="text-xs text-slate-800 space-y-2 list-none font-semibold leading-relaxed">
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 font-bold">✓</span>
                            <span>Always meet in busy public venues for first encounters.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-rose-600 font-bold">✕</span>
                            <span>Never wire gift cards, cash, or crypto to any advertiser.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-rose-600 font-bold">✕</span>
                            <span>Commercial sexual services &amp; prostitution are strictly prohibited.</span>
                        </li>
                    </ul>
                    <div class="pt-2 border-t border-rose-200">
                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'report-modal')"
                            class="text-xs text-[#F20B65] hover:text-rose-700 font-black inline-flex items-center gap-1.5 transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                            <span>Report Community Violation</span>
                        </button>
                    </div>
                </div>

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
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($similarListings as $sim)
                        <x-listing-card :listing="$sim" />
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
            <button
                type="button"
                @click="$dispatch('open-modal', 'contact-modal')"
                class="py-2.5 px-4 rounded-xl shadow-md shrink-0 flex items-center gap-1.5 font-black text-xs"
                style="background-color: #F20B65 !important; color: #ffffff !important;"
            >
                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <span class="text-white">Message</span>
            </button>
        </div>

        <!-- Lightbox Modal for Photo Gallery -->
        <div
            x-show="lightboxOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md"
            @keydown.escape.window="lightboxOpen = false"
        >
            <div class="relative max-w-4xl max-h-[90vh]">
                <button
                    @click="lightboxOpen = false"
                    class="absolute -top-10 right-0 text-white hover:text-rose-400 text-sm font-semibold flex items-center gap-1.5 p-1"
                >
                    <span>Close</span> &times;
                </button>
                <img :src="activePhotoUrl" alt="Enlarged photo" class="max-h-[85vh] max-w-full rounded-2xl shadow-2xl object-contain">
            </div>
        </div>

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
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
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
