<x-layouts.app title="{{ $listing->title }} — {{ $listing->city?->name }}, {{ $listing->state?->code }} | LocalDateHub">
    <div
        class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8"
        x-data="{
            replyModalOpen: false,
            reportModalOpen: false,
            lightboxOpen: false,
            activePhotoUrl: '',
            isFavorited: {{ $isFavorited ? 'true' : 'false' }},
            favoriteCount: {{ (int) $listing->favorite_count }}
        }"
    >
        <!-- Breadcrumbs Navigation -->
        <nav class="flex text-xs text-slate-400 space-x-2 items-center" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-slate-900 transition">Home</a>
            <span>/</span>
            <a href="{{ route('categories.index') }}" class="hover:text-slate-900 transition">Categories</a>
            @if($listing->category)
                @if($listing->category->parent)
                    <span>/</span>
                    <a href="{{ route('categories.show', $listing->category->parent->slug) }}" class="hover:text-slate-900 transition">{{ $listing->category->parent->name }}</a>
                @endif
                <span>/</span>
                <a href="{{ route('categories.show', $listing->category->slug) }}" class="hover:text-slate-900 transition">{{ $listing->category->name }}</a>
            @endif
            <span>/</span>
            <span class="text-slate-900 font-medium line-clamp-1">{{ $listing->title }}</span>
        </nav>

        <!-- Flash Notifications -->
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

        <!-- Status Notice if not active -->
        @if(!$listing->isActive())
            <div class="p-4 bg-amber-50 border border-amber-600/50 rounded-2xl text-amber-700 text-xs flex items-center justify-between shadow-lg">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-5 h-5 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span><strong>NON-PUBLIC STATUS:</strong> This listing has status <strong>{{ strtoupper(str_replace('_', ' ', $listing->status)) }}</strong>. It is not currently visible in public search.</span>
                </div>
                @can('update', $listing)
                    <a href="{{ route('listings.edit', $listing->id) }}" class="px-3 py-1 bg-amber-500 text-slate-950 font-bold rounded-lg text-xs hover:bg-amber-400 transition">
                        Edit Ad
                    </a>
                @endcan
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left 2 Cols: Main Content & Photos -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Header Card -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 space-y-5 shadow-xl">
                    <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-3 py-1 rounded-full bg-rose-50 text-[#F20B83] font-black text-xs border border-rose-200 shadow-2xs">
                                {{ $listing->category?->name }}
                            </span>
                            @if($listing->is_featured)
                                <span class="px-3 py-1 rounded-full bg-amber-50 text-amber-900 font-black text-xs border border-amber-200 shadow-2xs flex items-center gap-1">
                                    ★ Featured Spotlight
                                </span>
                            @endif
                            <span class="text-slate-800 font-bold text-xs">
                                {{ $listing->city?->name }}, {{ $listing->state?->code }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span>Posted {{ $listing->published_at?->diffForHumans() ?: $listing->created_at->diffForHumans() }}</span>
                            <!-- Save / Favorite Button -->
                            <form action="{{ route('listings.favorite.toggle', $listing->id) }}" method="POST" class="inline">
                                @csrf
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border transition text-xs font-semibold"
                                    :class="isFavorited ? 'bg-[#F20B83]/10 border-[#F20B83]/40 text-[#F20B83]' : 'bg-slate-100 border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-200'"
                                    title="Save to favorites"
                                >
                                    <svg class="w-4 h-4" :fill="isFavorited ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <span x-text="isFavorited ? 'Saved' : 'Save'">{{ $isFavorited ? 'Saved' : 'Save' }}</span>
                                    (<span x-text="favoriteCount">{{ $listing->favorite_count }}</span>)
                                </button>
                            </form>
                        </div>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        {{ $listing->title }}
                    </h1>

                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-600 pt-3 border-t border-slate-200">
                        <span><strong>Age:</strong> {{ $listing->poster_age }}</span>
                        <span>&bull;</span>
                        <span><strong>Gender:</strong> {{ $listing->poster_gender }}</span>
                        <span>&bull;</span>
                        <span><strong>Intent:</strong> <span class="capitalize">{{ str_replace('_', ' ', $listing->intent) }}</span></span>
                        <span>&bull;</span>
                        <span class="text-slate-400"><strong>Views:</strong> {{ $listing->view_count }}</span>
                        <span>&bull;</span>
                        <span class="text-slate-400"><strong>Replies:</strong> {{ $listing->reply_count }}</span>
                    </div>
                </div>

                <!-- Verified Photos Gallery -->
                @if($listing->approvedMedia->isNotEmpty())
                    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Verified Photos ({{ $listing->approvedMedia->count() }})</span>
                            </h2>
                            <span class="text-[11px] text-slate-500">EXIF Sanitized • Click to expand</span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($listing->approvedMedia as $photo)
                                <div
                                    class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 aspect-video relative group cursor-pointer"
                                    @click="activePhotoUrl = '{{ asset('storage/' . $photo->file_path) }}'; lightboxOpen = true"
                                >
                                    <img
                                        src="{{ asset('storage/' . $photo->file_path) }}"
                                        alt="{{ $listing->title }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                        loading="lazy"
                                    >
                                    @if($photo->is_primary)
                                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded bg-rose-600 text-white text-[10px] font-bold shadow">
                                            Primary Photo
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Advertisement Details -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xl space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                        Advertisement Details
                    </h2>
                    <div class="text-slate-800 text-sm leading-relaxed whitespace-pre-line font-normal">
                        {{ $listing->description }}
                    </div>
                </div>

                <!-- Match Preferences & Dynamic Attributes -->
                @if($listing->attributes->isNotEmpty())
                    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl space-y-4">
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                            Match Details & Preferences
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($listing->attributes as $attr)
                                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                                    <span class="text-slate-400 block text-[11px] capitalize">{{ str_replace('_', ' ', $attr->attribute_code) }}</span>
                                    <span class="font-bold text-slate-900 block mt-0.5">
                                        {{ $attr->selectedOption?->label ?: ($attr->value_text ?: $attr->value_number) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Right Col: Contact & Safety Sidebar -->
            <div class="space-y-6">
                <!-- Advertiser Identity Card -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                        Advertiser Profile
                    </h2>

                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-600 to-pink-500 flex items-center justify-center text-white font-bold text-lg shadow-md">
                            {{ strtoupper(substr($listing->user?->name ?? 'A', 0, 1)) }}
                        </div>
                        <div>
                            <span class="text-base font-bold text-slate-900 block">{{ $listing->user?->name ?? 'Anonymous Poster' }}</span>
                            <span class="text-xs text-slate-400">Member since {{ $listing->user?->created_at?->format('M Y') ?? 'Recently' }}</span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-200 space-y-2 text-xs">
                        <div class="flex items-center gap-2 text-emerald-700">
                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-medium text-slate-800">18+ Age Verified</span>
                        </div>
                        @if($listing->is_verified_poster)
                            <div class="flex items-center gap-2 text-purple-700">
                                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-medium text-slate-800">Verified Identity Badge</span>
                            </div>
                        @endif
                    </div>
                </div>

                @auth
                    @if(auth()->id() === $listing->user_id || auth()->user()->hasRole('admin'))
                        <div class="bg-gradient-to-br from-pink-50 to-white border-2 border-[#F20B83]/30 rounded-3xl p-5 shadow-sm space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase tracking-wider text-[#F20B83]">Author Controls</span>
                                @if($listing->activeBoost)
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-pink-100 text-[#F20B83]">
                                        Boosted until {{ $listing->activeBoost->expires_at->format('M d') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-700 font-medium leading-relaxed">
                                Want 5x more views? Spotlight your ad to the top of search results with instant crypto activation.
                            </p>
                            <div class="flex items-center gap-2">
                                <a 
                                    href="{{ route('boost.checkout', $listing->id) }}" 
                                    class="flex-1 py-2.5 px-3 bg-[#F20B83] hover:bg-[#d90858] text-white text-xs font-black rounded-xl text-center shadow-md shadow-[#F20B83]/20 transition"
                                >
                                    🚀 Boost This Ad Now
                                </a>
                                <a 
                                    href="{{ route('listings.edit', $listing->id) }}" 
                                    class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl transition"
                                >
                                    Edit
                                </a>
                            </div>
                        </div>
                    @endif
                @endauth

                <!-- Contact Options Card -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                        Contact Advertiser
                    </h2>

                    <!-- Protected Platform Contact Form Trigger -->
                    @if(!$listing->contact || $listing->contact->allow_platform_messages)
                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'contact-modal')"
                            class="w-full py-3 px-4 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs transition shadow-lg shadow-rose-600/20 flex items-center justify-center space-x-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <span>Send Protected Message</span>
                        </button>
                    @endif

                    <!-- Masked Anonymous Email Relay -->
                    @if($listing->contact?->allow_email_relay && $listing->contact?->masked_relay_email)
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-1">
                            <span class="text-slate-400 block text-[11px] font-semibold">Anonymous Email Relay:</span>
                            <span class="font-mono text-slate-800 text-xs block break-all select-all">{{ $listing->contact->masked_relay_email }}</span>
                            <span class="text-[10px] text-slate-500 block">Personal email address is masked to protect advertiser privacy.</span>
                        </div>
                    @endif

                    <!-- External Phone Visibility (Only if explicitly enabled by poster) -->
                    @if($listing->contact?->show_phone && $listing->contact?->preferred_phone_encrypted)
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-1">
                            <span class="text-slate-400 block text-[11px] font-semibold">Telephone / SMS:</span>
                            <a href="tel:{{ $listing->contact->preferred_phone_encrypted }}" class="font-mono text-rose-600 hover:underline font-bold text-xs block">
                                {{ $listing->contact->preferred_phone_encrypted }}
                            </a>
                        </div>
                    @endif

                    <!-- External Handle (e.g. Signal, Telegram) -->
                    @if($listing->contact?->external_handle_encrypted)
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-1">
                            <span class="text-slate-400 block text-[11px] font-semibold">External Messenger Handle:</span>
                            <span class="font-mono text-purple-700 font-bold text-xs block select-all">{{ $listing->contact->external_handle_encrypted }}</span>
                        </div>
                    @endif

                    <!-- Contact Instructions -->
                    @if($listing->contact?->contact_instructions)
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600">
                            <strong class="text-slate-900 block mb-0.5">Advertiser's Instructions:</strong>
                            {{ $listing->contact->contact_instructions }}
                        </div>
                    @endif
                </div>

                <!-- Location & Anti-Triangulation Card -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl space-y-3">
                    <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Location & Privacy Protection
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Location: <strong>{{ $listing->city?->name }}, {{ $listing->state?->code }}</strong>
                        @if($listing->location?->neighborhood)
                            <span class="block text-slate-600 mt-1">Area: {{ $listing->location->neighborhood }}</span>
                        @endif
                    </p>
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-[11px] text-slate-400 flex items-start space-x-2">
                        <svg class="w-4 h-4 text-emerald-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>GPS coordinates are fuzzed by 300–800m to prevent residential stalking.</span>
                    </div>
                </div>

                <!-- Trust & Safety Card & Report Link -->
                <div class="bg-rose-50 border border-rose-200 rounded-3xl p-6 shadow-xl space-y-3">
                    <h3 class="text-xs font-bold text-rose-600 uppercase tracking-wider">
                        Safety First
                    </h3>
                    <ul class="text-[11px] text-rose-600/90 space-y-1.5 list-disc pl-4 leading-relaxed">
                        <li>Always meet in public locations for first dates.</li>
                        <li>Never wire funds, gift cards, or crypto transfers.</li>
                        <li>Commercial sexual services and prostitution are illegal and prohibited.</li>
                    </ul>
                    <div class="pt-2">
                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'report-modal')"
                            class="text-xs text-rose-600 hover:text-rose-600 font-semibold flex items-center gap-1"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Report Listing Violations</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Similar Listings Section -->
        @if(isset($similarListings) && $similarListings->isNotEmpty())
            <div class="pt-10 border-t border-slate-200 space-y-6">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Similar Listings in {{ $listing->city?->name ?? 'this Area' }}</h2>
                    <p class="text-xs text-slate-400 mt-1">Explore other personal classifieds matching this category.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($similarListings as $sim)
                        <x-listing-card :listing="$sim" />
                    @endforeach
                </div>
            </div>
        @endif

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
                    class="absolute -top-10 right-0 text-white hover:text-rose-600 text-sm font-semibold flex items-center gap-1"
                >
                    <span>Close</span> &times;
                </button>
                <img :src="activePhotoUrl" alt="Enlarged photo" class="max-h-[85vh] max-w-full rounded-2xl shadow-2xl object-contain">
            </div>
        </div>

        <!-- Protected Contact Form Modal -->
        <x-modal name="contact-modal" title="Send Protected Message">
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
                    <label for="reply-message" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                        Message Details <span class="text-rose-600">*</span>
                    </label>
                    <textarea
                        id="reply-message"
                        name="message"
                        rows="5"
                        required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500"
                        placeholder="Introduce yourself and mention what interested you in their listing..."
                    >{{ old('message') }}</textarea>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-[11px] text-slate-400 space-y-1">
                    <p><strong>Privacy Shield:</strong> Your message is encrypted with AES-256 before storage. The advertiser never sees your personal data until you choose to reply directly.</p>
                    <p class="text-rose-600">Solicitations for commercial sex, financial wire requests, and harassment are strictly prohibited.</p>
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'contact-modal')">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary">
                        Send Encrypted Message
                    </x-button>
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
                    <label for="report-reason" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                        Violation Category <span class="text-rose-600">*</span>
                    </label>
                    <select
                        id="report-reason"
                        name="report_reason"
                        required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:ring-2 focus:ring-rose-500"
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
                    <label for="report-details" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                        Explanation & Context <span class="text-rose-600">*</span>
                    </label>
                    <textarea
                        id="report-details"
                        name="report_details"
                        rows="4"
                        required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500"
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

                <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-[11px] text-amber-700">
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
