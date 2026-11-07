<x-layouts.app title="LocalDateHub — Private Local Dating & Personal Connections">
    <!-- Hero Section -->
    <section class="relative overflow-hidden py-16 sm:py-24 bg-gradient-to-b from-slate-900 to-slate-950 border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <x-badge variant="rose">Privacy-First Adult Dating & Personals</x-badge>
            
            <h1 class="mt-6 text-4xl sm:text-6xl font-extrabold tracking-tight text-white max-w-4xl mx-auto leading-tight">
                Connect with Local Adults <span class="bg-gradient-to-r from-rose-500 via-pink-500 to-amber-400 bg-clip-text text-transparent">Safely & Privately</span>
            </h1>
            
            <p class="mt-6 text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed">
                Discover verified adult singles, date partners, and local meetups in your area. Anti-triangulation location security, zero EXIF tracking, and 18+ verified members.
            </p>

            <!-- Search Bar Mockup / Real Filter Controls -->
            <div class="mt-10 max-w-3xl mx-auto bg-slate-900/90 border border-slate-700/80 rounded-2xl p-4 shadow-2xl backdrop-blur-md">
                <form action="#browse" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="text-left">
                        <label class="block text-xs font-medium text-slate-400 mb-1">Looking for</label>
                        <select name="category" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none">
                            @foreach($categories as $category)
                                <option value="{{ $category->slug }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="text-left">
                        <label class="block text-xs font-medium text-slate-400 mb-1">Near City</label>
                        <input type="text" name="city" value="Austin, TX" placeholder="Enter city name..." class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none">
                    </div>

                    <div class="text-left">
                        <label class="block text-xs font-medium text-slate-400 mb-1">Radius</label>
                        <select name="radius" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none">
                            <option value="10">Within 10 km</option>
                            <option value="25" selected>Within 25 km</option>
                            <option value="50">Within 50 km</option>
                            <option value="100">Within 100 km</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <x-button type="submit" variant="primary" class="w-full py-2.5">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Search Hub
                        </x-button>
                    </div>
                </form>
            </div>

            <!-- Stats Ribbon -->
            <div class="mt-12 flex flex-wrap justify-center items-center gap-8 text-xs text-slate-400 font-medium">
                <span class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>100% 18+ Age Verified</span>
                </span>
                <span class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-rose-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>Anti-Triangulation Location Fuzzing</span>
                </span>
                <span class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-purple-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>Automated Photo EXIF Stripping</span>
                </span>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section id="categories" class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-10">
            <div>
                <x-badge variant="rose">Categories</x-badge>
                <h2 class="text-2xl sm:text-3xl font-bold text-white mt-2">Explore Personal Classifications</h2>
                <p class="text-sm text-slate-400 mt-1">Browse community-moderated dating and social categories.</p>
            </div>
            <a href="#view-all" class="text-sm font-semibold text-rose-400 hover:text-rose-300 transition mt-4 sm:mt-0">View All Categories →</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($categories as $category)
                <x-card class="group cursor-pointer">
                    <div class="flex items-start justify-between">
                        <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 group-hover:bg-rose-600 group-hover:text-white transition duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-mono text-slate-500">#{{ $category->displayOrder }}</span>
                    </div>
                    <h3 class="text-base font-bold text-white mt-4 group-hover:text-rose-400 transition">{{ $category->name }}</h3>
                    <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">{{ $category->description }}</p>
                    <div class="mt-4 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                        <span>Local ads available</span>
                        <span class="text-rose-400 font-medium group-hover:translate-x-1 transition duration-150">Browse →</span>
                    </div>
                </x-card>
            @endforeach
        </div>
    </section>

    <!-- Trust & Safety Standards Section -->
    <section id="safety-standards" class="py-16 bg-slate-900/40 border-y border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <x-badge variant="rose">Trust & Community Protection</x-badge>
                <h2 class="text-2xl sm:text-3xl font-bold text-white mt-3">Engineered for User Privacy & Physical Safety</h2>
                <p class="text-sm text-slate-400 mt-2">
                    LocalDateHub is committed to an adult dating experience free from harassment, exploitation, and privacy violations.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <x-card>
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-white">Location Anti-Triangulation</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Exact GPS coordinates are never returned to clients. Distances are jittered by 300–800m and bucketed into coarse bands to prevent physical stalking.
                    </p>
                </x-card>

                <x-card>
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-white">Automated EXIF Sanitization</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        All uploaded photos are re-encoded on our server to eliminate camera identifiers, embedded GPS location tags, and timestamps.
                    </p>
                </x-card>

                <x-card>
                    <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-white">Zero Commercial Solicitation</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Strict automated keyword filtering and human moderation proactively remove commercial sex work, escorts, and financial fraud.
                    </p>
                </x-card>
            </div>
        </div>
    </section>

    <!-- Reactive Livewire Diagnostic Section -->
    <section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-8">
            <x-badge variant="info">Live Reactivity</x-badge>
            <h2 class="text-2xl font-bold text-white mt-2">Architecture & Health Monitoring</h2>
            <p class="text-xs text-slate-400 mt-1">Livewire 4 + Alpine.js real-time status component.</p>
        </div>

        <div class="max-w-2xl mx-auto">
            @livewire('system-status-widget')
        </div>
    </section>
</x-layouts.app>
