<x-layouts.app title="{{ $city->name }}, {{ $state->code }} Adult Personal Classifieds — Local Dating">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumbs -->
        <nav class="flex items-center space-x-2 text-xs text-slate-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-white transition">Home</a>
            <span>/</span>
            <a href="{{ route('locations.index') }}" class="hover:text-white transition">Locations</a>
            <span>/</span>
            <a href="{{ route('locations.state', ['stateCode' => $state->code]) }}" class="hover:text-white transition">{{ $state->name }}</a>
            <span>/</span>
            <span class="text-slate-200 font-semibold">{{ $city->name }}</span>
        </nav>

        <!-- City Hero Section -->
        <div class="bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 border border-slate-800 rounded-2xl p-6 sm:p-10 mb-8 shadow-xl">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2.5 mb-2">
                        <span class="px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                            {{ $state->code }}
                        </span>
                        @if($city->county)
                            <span class="text-xs text-slate-400 font-medium">
                                {{ $city->county->name }}
                            </span>
                        @endif
                        @if($city->timezone)
                            <span class="text-[11px] text-slate-500">
                                • {{ $city->timezone }}
                            </span>
                        @endif
                    </div>

                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight">
                        {{ $city->name }}, {{ $state->code }}
                    </h1>
                    <p class="text-sm text-slate-400 mt-2 max-w-2xl">
                        Discover genuine adult personal classifieds, casual encounters, and dating connections in {{ $city->name }}. Strictly 18+ adult community.
                    </p>

                    <!-- Aliases & Neighborhoods -->
                    @if($city->aliases->isNotEmpty())
                        <div class="mt-4 flex flex-wrap items-center gap-1.5">
                            <span class="text-xs text-slate-500 mr-1">Also known as:</span>
                            @foreach($city->aliases as $alias)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-950 text-slate-300 border border-slate-800">
                                    {{ $alias->alias }}
                                    <span class="text-[9px] text-slate-500 ml-1">({{ $alias->alias_type }})</span>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <!-- Covered ZIP codes -->
                    @if($city->zipCodes->isNotEmpty())
                        <div class="mt-2 flex flex-wrap items-center gap-1">
                            <span class="text-xs text-slate-500 mr-1">ZIP Codes:</span>
                            @foreach($city->zipCodes as $zip)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-950 text-slate-400 border border-slate-800">
                                    {{ $zip->zip_code }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex flex-col sm:flex-row lg:flex-col gap-3 shrink-0">
                    <a href="#post-ad"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white font-semibold text-sm shadow-lg shadow-rose-600/25 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Post Ad in {{ $city->name }}
                    </a>

                    <div class="w-full sm:w-72">
                        <livewire:location-autocomplete :placeholder="'Search other cities...'" />
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Main Column: Listings -->
            <div class="lg:col-span-3 space-y-6">
                <!-- Category Filter Pills -->
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white tracking-tight">
                        Personal Listings in {{ $city->name }}
                        <span class="text-xs font-normal text-slate-400 ml-2">({{ $listings->total() }} active)</span>
                    </h2>
                </div>

                @if($listings->isEmpty())
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-10 text-center space-y-4">
                        <div class="w-12 h-12 rounded-full bg-rose-500/10 text-rose-400 mx-auto flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-white">Be the First to Post in {{ $city->name }}</h3>
                        <p class="text-xs text-slate-400 max-w-md mx-auto">
                            There are currently no active personal classified ads in {{ $city->name }}. Post your personal connection or browse nearby cities below!
                        </p>
                        <a href="#post-ad"
                            class="inline-flex items-center px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition">
                            Create First Personal Ad
                        </a>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($listings as $ad)
                            <div class="p-5 rounded-xl bg-slate-900 border border-slate-800 hover:border-slate-700 transition flex flex-col sm:flex-row gap-4">
                                @if($ad->primaryPhoto)
                                    <div class="w-full sm:w-32 h-32 rounded-lg bg-slate-950 overflow-hidden shrink-0 border border-slate-800">
                                        <img src="{{ Storage::url($ad->primaryPhoto->file_path) }}" alt="{{ $ad->title }}" class="w-full h-full object-cover">
                                    </div>
                                @endif
                                <div class="flex-1 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                                {{ $ad->category->name }}
                                            </span>
                                            <span class="text-xs text-slate-400">
                                                Age {{ $ad->poster_age }}
                                                @if($ad->poster_gender)
                                                    • {{ $ad->poster_gender }}
                                                @endif
                                            </span>
                                            <span class="text-[11px] text-slate-500 ml-auto">
                                                {{ $ad->published_at ? $ad->published_at->diffForHumans() : 'Recently' }}
                                            </span>
                                        </div>
                                        <h3 class="text-base font-bold text-white hover:text-rose-400 transition">
                                            {{ $ad->title }}
                                        </h3>
                                        <p class="text-xs text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                                            {{ $ad->description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="pt-4">
                            {{ $listings->links() }}
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar Column: Nearby Cities & Quick Info -->
            <div class="space-y-6">
                <!-- Nearby Cities (Radius Search Proof) -->
                @if($nearbyCities->isNotEmpty())
                    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-white mb-1 flex items-center gap-2">
                            <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Adjacent & Nearby Cities
                        </h3>
                        <p class="text-[11px] text-slate-400 mb-4">Calculated via Haversine radius engine (&lt; 40 miles)</p>

                        <div class="space-y-2">
                            @foreach($nearbyCities as $near)
                                <a href="{{ route('locations.city', ['stateCode' => $near->state->code, 'citySlug' => $near->slug]) }}"
                                    class="group p-2.5 rounded-lg bg-slate-950 border border-slate-800/80 hover:border-rose-500/40 hover:bg-slate-850 flex items-center justify-between text-xs transition">
                                    <span class="font-medium text-slate-300 group-hover:text-white truncate">
                                        {{ $near->name }}, {{ $near->state->code }}
                                    </span>
                                    <span class="text-[11px] font-mono text-rose-400/90 shrink-0 ml-2">
                                        {{ $near->distance_miles }} mi
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Safety Callout -->
                <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/60 text-xs text-slate-400 space-y-2 leading-relaxed">
                    <h4 class="font-bold text-white">Trust & Safety in {{ $city->name }}</h4>
                    <p>
                        All listings in {{ $city->name }} are subject to 100% human and automated compliance review. Prostitution, commercial solicitation, and minor exploitation are strictly prohibited.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

