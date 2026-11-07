<x-layouts.app title="{{ $listing->title }} — {{ $listing->city?->name }}, {{ $listing->state?->code }} | LocalAdZone">
    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Breadcrumbs Navigation -->
        <nav class="flex text-xs text-slate-400 space-x-2 items-center" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-white">Home</a>
            <span>/</span>
            <a href="{{ route('categories.index') }}" class="hover:text-white">Categories</a>
            @if($listing->category)
                <span>/</span>
                <a href="{{ route('categories.show', $listing->category->slug) }}" class="hover:text-white">{{ $listing->category->name }}</a>
            @endif
            <span>/</span>
            <span class="text-white font-medium line-clamp-1">{{ $listing->title }}</span>
        </nav>

        <!-- Status Notice if not active -->
        @if(!$listing->isActive())
            <div class="p-4 bg-amber-950/60 border border-amber-600/50 rounded-2xl text-amber-200 text-xs flex items-center justify-between shadow-lg">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span><strong>NON-PUBLIC STATUS:</strong> This listing has status <strong>{{ strtoupper(str_replace('_', ' ', $listing->status)) }}</strong>. It is not currently visible in public search.</span>
                </div>
                @can('update', $listing)
                    <a href="{{ route('listings.edit', $listing->id) }}" class="px-3 py-1 bg-amber-500 text-slate-950 font-bold rounded-lg text-xs hover:bg-amber-400 transition">
                        Edit Ad
                    </a>
                @endcan
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Main Content & Photos -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Header Card -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 space-y-4 shadow-xl">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-400">
                        <div class="flex items-center space-x-2">
                            <span class="px-2.5 py-0.5 rounded-full bg-rose-500/20 text-rose-400 font-bold text-xs border border-rose-500/30">
                                {{ $listing->category?->name }}
                            </span>
                            <span class="text-slate-300 font-medium">
                                {{ $listing->city?->name }}, {{ $listing->state?->code }}
                            </span>
                        </div>
                        <span>Posted {{ $listing->published_at?->diffForHumans() ?: $listing->created_at->diffForHumans() }}</span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight leading-tight">
                        {{ $listing->title }}
                    </h1>

                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-300 pt-2 border-t border-slate-800/80">
                        <span><strong>Age:</strong> {{ $listing->poster_age }}</span>
                        <span>&bull;</span>
                        <span><strong>Gender:</strong> {{ $listing->poster_gender }}</span>
                        <span>&bull;</span>
                        <span><strong>Seeking:</strong> <span class="capitalize">{{ str_replace('_', ' ', $listing->intent) }}</span></span>
                        <span>&bull;</span>
                        <span class="text-slate-400"><strong>Views:</strong> {{ $listing->view_count }}</span>
                    </div>
                </div>

                <!-- Photos Carousel / Gallery -->
                @if($listing->media->isNotEmpty())
                    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                            Verified Photos ({{ $listing->media->count() }})
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($listing->media as $photo)
                                <div class="rounded-2xl overflow-hidden border border-slate-800 bg-slate-950 aspect-video relative group">
                                    <img
                                        src="{{ asset('storage/' . $photo->file_path) }}"
                                        alt="{{ $listing->title }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
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

                <!-- Description -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-4">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                        Advertisement Details
                    </h2>
                    <div class="text-slate-200 text-sm leading-relaxed whitespace-pre-line">
                        {{ $listing->description }}
                    </div>
                </div>

                <!-- Dynamic Category Attributes Table -->
                @if($listing->attributes->isNotEmpty())
                    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                            Match Details & Preferences
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($listing->attributes as $attr)
                                <div class="p-3 bg-slate-950 border border-slate-800/80 rounded-xl text-xs">
                                    <span class="text-slate-400 block text-[11px] capitalize">{{ str_replace('_', ' ', $attr->attribute_code) }}</span>
                                    <span class="font-bold text-white block mt-0.5">
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
                <!-- Contact Options Card -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                        Contact Advertiser
                    </h2>

                    @if($listing->contact)
                        @if($listing->contact->allow_platform_messages)
                            <a
                                href="#reply"
                                class="w-full py-2.5 px-4 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs transition shadow-md flex items-center justify-center space-x-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                <span>Send Platform Message</span>
                            </a>
                        @endif

                        @if($listing->contact->allow_email_relay)
                            <div class="p-3 bg-slate-950 border border-slate-800 rounded-xl text-xs space-y-1">
                                <span class="text-slate-400 block text-[11px]">Anonymous Email Relay:</span>
                                <span class="font-mono text-slate-200 text-xs block break-all select-all">{{ $listing->contact->masked_relay_email }}</span>
                            </div>
                        @endif

                        @if($listing->contact->show_phone && $listing->contact->preferred_phone_encrypted)
                            <div class="p-3 bg-slate-950 border border-slate-800 rounded-xl text-xs space-y-1">
                                <span class="text-slate-400 block text-[11px]">Telephone / SMS:</span>
                                <span class="font-mono text-rose-300 font-bold text-xs block">{{ $listing->contact->preferred_phone_encrypted }}</span>
                            </div>
                        @endif

                        @if($listing->contact->contact_instructions)
                            <div class="p-3 bg-slate-950/60 border border-slate-800/80 rounded-xl text-xs text-slate-300">
                                <strong class="text-white block mb-0.5">Contact Instructions:</strong>
                                {{ $listing->contact->contact_instructions }}
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Location Fuzzing Card -->
                <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-3">
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider">
                        Location & Privacy Protection
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Location: <strong>{{ $listing->city?->name }}, {{ $listing->state?->code }}</strong>
                        @if($listing->location?->neighborhood)
                            <span class="block text-slate-300 mt-1">Area: {{ $listing->location->neighborhood }}</span>
                        @endif
                    </p>
                    <div class="p-3 bg-slate-950 border border-slate-800 rounded-xl text-[11px] text-slate-400 flex items-start space-x-2">
                        <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Coordinates are fuzzed by ~2-5 km to prevent residential triangulation.</span>
                    </div>
                </div>

                <!-- Trust & Safety Card -->
                <div class="bg-rose-950/30 border border-rose-900/50 rounded-3xl p-6 shadow-xl space-y-3">
                    <h3 class="text-xs font-bold text-rose-400 uppercase tracking-wider">
                        Safety First
                    </h3>
                    <ul class="text-[11px] text-rose-200/90 space-y-1.5 list-disc pl-4 leading-relaxed">
                        <li>Always meet in public locations for first dates.</li>
                        <li>Never wire funds, send deposits, or share banking info.</li>
                        <li>Commercial sexual services and prostitution are illegal and prohibited.</li>
                    </ul>
                    <a href="#report" class="text-xs text-rose-400 hover:text-rose-300 font-semibold block pt-2">
                        &bull; Report Suspicious Listing
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

