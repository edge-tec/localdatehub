@php
    $navCategories = \App\Modules\Categories\Models\Category::where('is_active', true)->whereNull('parent_id')->take(8)->get();
    $navStates = \App\Modules\Locations\Models\State::where('is_active', true)->orWhere('status', 'active')->orderBy('name')->take(12)->get();
    $navCities = \App\Modules\Locations\Models\City::with('state')->where('is_major', true)->take(10)->get();
@endphp

<header class="bg-[#17131A] border-b border-[#2A2330] sticky top-0 z-50 shadow-md" x-data="{ mobileMenuOpen: false, browseOpen: false, statesOpen: false, citiesOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <!-- Left: Logo & Tagline -->
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                    <!-- Pink Heart Icon -->
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-[#F20B65] transition group-hover:scale-105">
                        <svg class="w-9 h-9 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-extrabold tracking-tight text-white flex items-center leading-none">
                            LocalDate<span class="text-[#F20B65]">Hub</span>
                        </div>
                        <div class="text-[11px] font-medium text-slate-400 tracking-wider mt-1">
                            Connect • Meet • Enjoy
                        </div>
                    </div>
                </a>
            </div>

            <!-- Center: Navigation Links -->
            <nav class="hidden lg:flex items-center space-x-1 font-medium text-sm text-slate-200">
                <!-- Home Button (Pink Pill) -->
                <a href="{{ route('home') }}" class="bg-[#F20B65] text-white px-3.5 py-1.5 rounded-md flex items-center gap-1.5 shadow-sm hover:bg-[#d90858] transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                    <span>Home</span>
                </a>

                <!-- Browse Ads Dropdown -->
                <div class="relative" @click.away="browseOpen = false">
                    <button @click="browseOpen = !browseOpen; statesOpen = false; citiesOpen = false" class="px-3 py-2 rounded-md hover:text-white transition flex items-center gap-1">
                        <span>Browse Ads</span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': browseOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="browseOpen" x-cloak x-transition class="absolute left-0 mt-2 w-56 rounded-xl bg-[#1C1720] border border-[#2E2636] shadow-2xl py-2 z-50">
                        @foreach($navCategories as $cat)
                            <a href="{{ route('categories.show', $cat->slug) }}" class="block px-4 py-2 text-xs text-slate-300 hover:text-white hover:bg-[#F20B65]/15 transition">
                                {{ $cat->name }}
                            </a>
                        @endforeach
                        <div class="border-t border-[#2E2636] my-1"></div>
                        <a href="{{ route('categories.index') }}" class="block px-4 py-2 text-xs font-semibold text-[#F20B65] hover:underline">
                            View All Categories →
                        </a>
                    </div>
                </div>

                <!-- States Dropdown -->
                <div class="relative" @click.away="statesOpen = false">
                    <button @click="statesOpen = !statesOpen; browseOpen = false; citiesOpen = false" class="px-3 py-2 rounded-md hover:text-white transition flex items-center gap-1">
                        <span>States</span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': statesOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="statesOpen" x-cloak x-transition class="absolute left-0 mt-2 w-64 rounded-xl bg-[#1C1720] border border-[#2E2636] shadow-2xl py-2 z-50 grid grid-cols-2 gap-1 p-2">
                        @foreach($navStates as $st)
                            <a href="{{ route('locations.state', strtolower($st->code)) }}" class="px-2 py-1.5 text-xs text-slate-300 hover:text-white hover:bg-[#F20B65]/15 rounded transition">
                                {{ $st->name }}
                            </a>
                        @endforeach
                        <div class="col-span-2 border-t border-[#2E2636] mt-1 pt-1.5">
                            <a href="{{ route('locations.index') }}" class="block px-2 text-xs font-semibold text-[#F20B65] hover:underline">
                                View All 50 States →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Cities Dropdown -->
                <div class="relative" @click.away="citiesOpen = false">
                    <button @click="citiesOpen = !citiesOpen; browseOpen = false; statesOpen = false" class="px-3 py-2 rounded-md hover:text-white transition flex items-center gap-1">
                        <span>Cities</span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': citiesOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="citiesOpen" x-cloak x-transition class="absolute left-0 mt-2 w-56 rounded-xl bg-[#1C1720] border border-[#2E2636] shadow-2xl py-2 z-50">
                        @foreach($navCities as $ct)
                            <a href="{{ route('locations.city', ['stateCode' => strtolower($ct->state?->code ?? 'ca'), 'citySlug' => $ct->slug]) }}" class="block px-4 py-2 text-xs text-slate-300 hover:text-white hover:bg-[#F20B65]/15 transition">
                                {{ $ct->name }}, {{ $ct->state?->code }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <a href="{{ route('pages.about') }}" class="px-3 py-2 rounded-md hover:text-white transition">About</a>
                <a href="{{ route('pages.contact') }}" class="px-3 py-2 rounded-md hover:text-white transition">Contact</a>
            </nav>

            <!-- Right: Login & Post Ad -->
            <div class="hidden sm:flex items-center space-x-3">
                @guest
                    <!-- Login Button -->
                    <a href="{{ route('login') }}" class="border border-[#382E40] bg-[#221C26] hover:bg-[#2B2330] hover:border-slate-500 text-white px-3.5 py-1.5 rounded-md flex items-center gap-1.5 text-xs font-semibold transition">
                        <svg class="w-3.5 h-3.5 text-slate-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                        <span>Login</span>
                    </a>
                @else
                    <a href="{{ route('account.dashboard') }}" class="border border-[#382E40] bg-[#221C26] hover:bg-[#2B2330] text-white px-3 py-1.5 rounded-md text-xs font-semibold transition">
                        Dashboard
                    </a>
                @endguest

                <!-- Post Ad Button (Bright Pink) -->
                <a href="{{ route('listings.create') }}" class="bg-[#F20B65] hover:bg-[#d90858] text-white px-4 py-1.5 rounded-md flex items-center gap-1.5 text-xs font-bold shadow-md shadow-[#F20B65]/20 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Post Ad</span>
                </a>
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="flex items-center lg:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-slate-300 hover:text-white p-2 rounded-md focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Menu -->
    <div x-show="mobileMenuOpen" x-cloak class="lg:hidden bg-[#17131A] border-t border-[#2A2330] px-4 pt-3 pb-6 space-y-2">
        <a href="{{ route('home') }}" class="block px-3 py-2 rounded-md text-sm font-semibold bg-[#F20B65] text-white">Home</a>
        <a href="{{ route('categories.index') }}" class="block px-3 py-2 rounded-md text-sm text-slate-200 hover:bg-[#2A2330]">Browse Categories</a>
        <a href="{{ route('locations.index') }}" class="block px-3 py-2 rounded-md text-sm text-slate-200 hover:bg-[#2A2330]">All US States</a>
        <a href="{{ route('search.index') }}" class="block px-3 py-2 rounded-md text-sm text-slate-200 hover:bg-[#2A2330]">Search Personals</a>
        <a href="{{ route('pages.about') }}" class="block px-3 py-2 rounded-md text-sm text-slate-200 hover:bg-[#2A2330]">About</a>
        <a href="{{ route('pages.contact') }}" class="block px-3 py-2 rounded-md text-sm text-slate-200 hover:bg-[#2A2330]">Contact</a>
        <div class="pt-3 border-t border-[#2A2330] flex items-center space-x-3">
            @guest
                <a href="{{ route('login') }}" class="flex-1 text-center py-2 rounded-md border border-[#382E40] text-sm text-white">Login</a>
                <a href="{{ route('register') }}" class="flex-1 text-center py-2 rounded-md bg-[#F20B65] text-sm font-bold text-white">Register</a>
            @else
                <a href="{{ route('account.dashboard') }}" class="flex-1 text-center py-2 rounded-md bg-[#221C26] text-sm text-white">Dashboard</a>
            @endguest
            <a href="{{ route('listings.create') }}" class="flex-1 text-center py-2 rounded-md bg-[#F20B65] text-sm font-bold text-white">+ Post Ad</a>
        </div>
    </div>
</header>
