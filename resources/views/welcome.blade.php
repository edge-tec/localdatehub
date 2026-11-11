<x-layouts.app
    :title="$seo->title ?? 'LocalDateHub — Your Local Adult Classifieds & Dating Community'"
    :description="$seo->description ?? null"
    :canonicalUrl="$seo->canonicalUrl ?? null"
    :structuredData="$seo->structuredData ?? null"
>

    <!-- ======================================================================= -->
    <!-- 1. HERO SECTION (Clean White with Search Bar & Trust Badges) -->
    <section class="bg-gradient-to-b from-pink-50/70 via-white to-slate-50 border-b border-slate-200 relative z-30 py-8 sm:py-12 lg:py-16">
        <!-- Ambient Background Glow (contained so it does not clip floating dropdowns) -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-0 right-1/4 w-96 h-96 bg-[#F20B65]/10 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                
                <!-- Left Column: Attractive Model / Offer Photograph -->
                <div class="lg:col-span-5 flex justify-center lg:justify-start">
                    <div class="relative w-full max-w-md lg:max-w-none group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-[#F20B65]/30 to-purple-600/30 rounded-2xl blur-lg opacity-40 group-hover:opacity-70 transition duration-500"></div>
                        @if(!empty($heroOfferLink))
                            <a
                                href="{{ $heroOfferLink }}"
                                @if(!empty($heroOfferNewTab)) target="_blank" rel="noopener noreferrer sponsored" @endif
                                aria-label="{{ $heroOfferAlt ?? 'LocalDateHub Featured Offer' }}"
                                class="relative block rounded-2xl overflow-hidden shadow-xl border border-slate-200 cursor-pointer"
                            >
                                <img
                                    src="{{ $heroImageUrl ?? asset('images/hero-model.jpg') }}"
                                    alt="{{ $heroOfferAlt ?? 'LocalDateHub Dating Lifestyle' }}"
                                    onerror="this.onerror=null;this.src='{{ asset('images/hero-model.jpg') }}';"
                                    class="w-full h-[320px] sm:h-[380px] lg:h-[420px] object-cover transition duration-300 group-hover:scale-[1.01]"
                                />
                            </a>
                        @else
                            <img
                                src="{{ $heroImageUrl ?? asset('images/hero-model.jpg') }}"
                                alt="{{ $heroOfferAlt ?? 'LocalDateHub Dating Lifestyle' }}"
                                onerror="this.onerror=null;this.src='{{ asset('images/hero-model.jpg') }}';"
                                class="relative w-full h-[320px] sm:h-[380px] lg:h-[420px] object-cover rounded-2xl shadow-xl border border-slate-200"
                            />
                        @endif
                    </div>
                </div>

                <!-- Right Column: Brand Heading, Search Bar & Trust Badges -->
                <div class="lg:col-span-7 text-center lg:text-left">
                    <!-- Premium Glassmorphism Eyebrow Badge (Zero Dot, High-End Vector Icon, Fully Responsive) -->
                    <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-gradient-to-r from-rose-50 via-pink-50 to-purple-50/60 border border-rose-200/90 shadow-xs hover:shadow-md transition-all duration-300 mb-4 group cursor-default">
                        <span class="w-5 h-5 rounded-full bg-[#F20B65] text-white flex items-center justify-center shrink-0 shadow-2xs group-hover:scale-110 transition-transform">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <span class="text-xs sm:text-xs font-black text-[#F20B65] tracking-tight">Privacy-First Adult Dating &amp; Personals</span>
                        <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-white text-[#F20B65] border border-rose-200 shadow-2xs">
                            100% Discreet
                        </span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight leading-none">
                        LocalDate<span class="text-[#F20B65]">Hub</span>
                    </h1>
                    <p class="text-lg sm:text-2xl font-bold text-slate-800 mt-2 sm:mt-3">
                        Your Local Adult Classifieds & Dating Community
                    </p>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1 sm:mt-2 font-normal">
                        Find someone special near you – discreet, easy and free!
                    </p>

                    <!-- Search Bar Panel (White pill box matching reference image) -->
                    <div 
                        x-data="{
                            selectedState: '{{ strtolower(request('state', '')) }}',
                            selectedCity: '{{ request('city', '') }}',
                            cityInput: '{{ request('city', '') }}',
                            cities: [],
                            cache: {},
                            loadingCities: false,
                            cityDropdownOpen: false,

                            async onStateChange(event) {
                                const stateCode = event.target.value;
                                this.selectedState = stateCode;
                                this.selectedCity = '';
                                this.cityInput = '';
                                this.cities = [];

                                if (!stateCode) {
                                    this.cityDropdownOpen = false;
                                    return;
                                }

                                if (this.cache[stateCode]) {
                                    this.cities = this.cache[stateCode];
                                    this.cityDropdownOpen = true;
                                    return;
                                }

                                this.loadingCities = true;
                                try {
                                    const res = await fetch(`/api/locations/states/${stateCode.toLowerCase()}/cities`);
                                    const json = await res.json();
                                    if (json.success && json.data && json.data.cities) {
                                        this.cities = json.data.cities;
                                        this.cache[stateCode] = json.data.cities;
                                        this.cityDropdownOpen = true;
                                    } else {
                                        // Fallback to static static city database if API endpoint response fails
                                        const fallbackRes = await fetch('/data/us-cities.json');
                                        const fallbackMap = await fallbackRes.json();
                                        if (fallbackMap && fallbackMap[stateCode.toLowerCase()]) {
                                            this.cities = fallbackMap[stateCode.toLowerCase()];
                                            this.cache[stateCode] = this.cities;
                                            this.cityDropdownOpen = true;
                                        }
                                    }
                                } catch (e) {
                                    try {
                                        const fallbackRes = await fetch('/data/us-cities.json');
                                        const fallbackMap = await fallbackRes.json();
                                        if (fallbackMap && fallbackMap[stateCode.toLowerCase()]) {
                                            this.cities = fallbackMap[stateCode.toLowerCase()];
                                            this.cache[stateCode] = this.cities;
                                            this.cityDropdownOpen = true;
                                        }
                                    } catch (err) {
                                        console.error('Failed to load cities for state', stateCode, e, err);
                                    }
                                } finally {
                                    this.loadingCities = false;
                                }
                            },

                            get filteredCities() {
                                let list = this.cities;
                                if (this.cityInput && this.cityInput.trim().length > 0) {
                                    const q = this.cityInput.toLowerCase().trim();
                                    list = this.cities.filter(c => c.name.toLowerCase().includes(q));
                                }
                                return list.slice(0, 80);
                            },

                            get totalMatches() {
                                if (!this.cityInput || !this.cityInput.trim()) return this.cities.length;
                                const q = this.cityInput.toLowerCase().trim();
                                return this.cities.filter(c => c.name.toLowerCase().includes(q)).length;
                            },

                            selectCity(name) {
                                this.selectedCity = name;
                                this.cityInput = name;
                                this.cityDropdownOpen = false;
                            },

                            validateCity() {
                                if (!this.selectedState) {
                                    this.selectedCity = '';
                                    this.cityInput = '';
                                    return;
                                }
                                if (!this.cityInput || !this.cityInput.trim()) {
                                    this.selectedCity = '';
                                    this.cityInput = '';
                                    return;
                                }
                                const q = this.cityInput.trim().toLowerCase();
                                const exact = this.cities.find(c => c.name.toLowerCase() === q);
                                if (exact) {
                                    this.selectedCity = exact.name;
                                    this.cityInput = exact.name;
                                } else {
                                    const match = this.cities.find(c => c.name.toLowerCase().startsWith(q));
                                    if (match) {
                                        this.selectedCity = match.name;
                                        this.cityInput = match.name;
                                    } else {
                                        // City does not belong to selected state - clear to prevent invalid cross-state search
                                        this.selectedCity = '';
                                        this.cityInput = '';
                                    }
                                }
                            },

                            init() {
                                if (this.selectedState) {
                                    this.onStateChange({ target: { value: this.selectedState } });
                                    if ('{{ request('city', '') }}') {
                                        this.selectedCity = '{{ request('city', '') }}';
                                        this.cityInput = '{{ request('city', '') }}';
                                        this.cityDropdownOpen = false;
                                    }
                                }
                            }
                        }"
                        class="mt-6 sm:mt-8 bg-white rounded-2xl p-2 sm:p-2.5 shadow-2xl border border-white/20 relative z-30"
                    >
                        <form action="{{ route('search.index') }}" method="GET" @submit="validateCity()" class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                            
                            <!-- Select Category -->
                            <div class="sm:col-span-4 relative flex items-center px-3 py-1.5 sm:border-r border-slate-200">
                                <svg class="w-4 h-4 text-slate-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <select name="category" class="w-full bg-transparent text-xs font-semibold text-slate-800 focus:outline-none cursor-pointer py-1 truncate">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Select State -->
                            <div class="sm:col-span-3 relative flex items-center px-3 py-1.5 sm:border-r border-slate-200">
                                <svg class="w-4 h-4 text-slate-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <select 
                                    name="state" 
                                    x-model="selectedState" 
                                    @change="onStateChange($event)"
                                    class="w-full bg-transparent text-xs font-semibold text-slate-800 focus:outline-none cursor-pointer py-1 truncate"
                                >
                                    <option value="">Select State</option>
                                    @foreach($allStates as $st)
                                        <option value="{{ strtolower($st->code) }}">{{ $st->name }} ({{ $st->code }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Select City with Automatic City Suggestion Dropdown -->
                            <div 
                                class="sm:col-span-3 relative flex items-center px-3 py-1.5 cursor-pointer"
                                @click.outside="cityDropdownOpen = false"
                                @keydown.escape.window="cityDropdownOpen = false"
                                @click="if(selectedState) { cityDropdownOpen = !cityDropdownOpen; }"
                            >
                                <svg class="w-4 h-4 text-slate-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                
                                <!-- Hidden input for form submission -->
                                <input type="hidden" name="city" :value="selectedCity">

                                <!-- Interactive City Input Display -->
                                <div class="relative w-full">
                                    <input 
                                        type="text" 
                                        x-model="cityInput" 
                                        @click.stop="if(selectedState) { cityDropdownOpen = true; }"
                                        @input="selectedCity = cityInput; if(selectedState) { cityDropdownOpen = true; }"
                                        :disabled="!selectedState"
                                        :placeholder="loadingCities ? 'Loading cities...' : (!selectedState ? 'Select State first' : 'Select City in State...')" 
                                        class="w-full bg-transparent text-xs font-semibold text-slate-800 placeholder-slate-400 focus:outline-none py-1 pr-6 truncate cursor-pointer"
                                        :class="{ 'cursor-not-allowed opacity-60': !selectedState }"
                                        autocomplete="off"
                                    />

                                    <!-- Indicator icon / loading spinner -->
                                    <div class="absolute right-0 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                        <template x-if="loadingCities">
                                            <svg class="animate-spin h-3.5 w-3.5 text-[#F20B65]" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </template>
                                        <template x-if="!loadingCities">
                                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180 text-[#F20B65]': cityDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </template>
                                    </div>
                                </div>

                                <!-- Floating City Suggestion Dropdown List (Responsive Width & Never Clipped) -->
                                <div 
                                    x-show="cityDropdownOpen" 
                                    x-cloak 
                                    @click.stop
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 translate-y-2 scale-98"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-2 scale-98"
                                    class="absolute left-0 right-0 sm:left-auto sm:right-0 top-full mt-2 w-full sm:w-[380px] max-w-[calc(100vw-2rem)] sm:max-w-[420px] bg-white rounded-2xl shadow-2xl border border-slate-200/90 z-50 text-left flex flex-col overflow-hidden"
                                    style="box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.22), 0 0 0 1px rgba(0, 0, 0, 0.05);"
                                >
                                    <!-- Prompt if no state selected yet -->
                                    <template x-if="!selectedState">
                                        <div class="p-4 text-center">
                                            <div class="text-2xl mb-1">📍</div>
                                            <div class="text-xs font-bold text-slate-800">Please select a State first</div>
                                            <div class="text-[11px] text-slate-500 mt-0.5">Cities will automatically load for your chosen state.</div>
                                        </div>
                                    </template>

                                    <!-- When state is selected and cities are loaded -->
                                    <template x-if="selectedState && cities.length > 0">
                                        <div class="flex flex-col">
                                            <!-- STICKY TOP HEADER: Search bar & All Cities shortcut (Never scrolls away!) -->
                                            <div class="sticky top-0 bg-white border-b border-slate-100 p-2.5 z-20 space-y-2 bg-gradient-to-b from-white to-slate-50/70">
                                                <div class="flex items-center justify-between px-1">
                                                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-700" x-text="cities.length + ' Cities Available'"></span>
                                                    <button 
                                                        type="button" 
                                                        @click="selectCity('')"
                                                        class="text-[11px] font-bold text-[#F20B65] hover:underline flex items-center gap-1"
                                                    >
                                                        <span>All Cities in State</span>
                                                        <span class="text-slate-400 font-normal">↺</span>
                                                    </button>
                                                </div>

                                                <!-- Quick Search Box inside dropdown -->
                                                <div class="relative">
                                                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                    <input 
                                                        type="text" 
                                                        x-model="cityInput" 
                                                        placeholder="Type city name to filter..." 
                                                        class="w-full bg-white border border-slate-200 rounded-xl pl-8 pr-7 py-1.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#F20B65] focus:ring-1 focus:ring-[#F20B65] transition"
                                                        autocomplete="off"
                                                    />
                                                    <template x-if="cityInput">
                                                        <button 
                                                            type="button" 
                                                            @click="cityInput = ''; selectedCity = '';" 
                                                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs font-bold p-0.5"
                                                        >
                                                            &times;
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- SCROLLABLE LIST OF CITIES -->
                                            <div class="max-h-60 sm:max-h-72 overflow-y-auto divide-y divide-slate-100/80 p-1.5" style="overscroll-behavior: contain; -webkit-overflow-scrolling: touch;">
                                                <template x-for="c in filteredCities" :key="c.id">
                                                    <button 
                                                        type="button" 
                                                        @click="selectCity(c.name)"
                                                        class="w-full text-left px-3 py-2 text-xs text-slate-700 hover:bg-pink-50 hover:text-[#F20B65] rounded-xl font-semibold flex items-center justify-between transition-colors"
                                                        :class="{ 'bg-pink-50/80 text-[#F20B65] font-bold ring-1 ring-[#F20B65]/30': selectedCity === c.name }"
                                                    >
                                                        <span x-text="c.name"></span>
                                                        <template x-if="c.is_popular">
                                                            <span class="text-[9px] px-2 py-0.5 rounded-full bg-pink-100 text-[#F20B65] font-extrabold uppercase tracking-wide">Popular</span>
                                                        </template>
                                                    </button>
                                                </template>

                                                <!-- If filtered query has no match -->
                                                <template x-if="filteredCities.length === 0">
                                                    <div class="px-4 py-6 text-center text-xs text-slate-500 italic">
                                                        No city matches "<span x-text="cityInput" class="font-bold text-slate-700"></span>"
                                                    </div>
                                                </template>
                                            </div>

                                            <!-- FOOTER: Showing X of Total matches -->
                                            <div class="px-3 py-1.5 text-[10px] text-slate-500 bg-slate-50 border-t border-slate-100 flex items-center justify-between font-medium">
                                                <span>Showing <strong x-text="filteredCities.length"></strong> of <strong x-text="totalMatches"></strong></span>
                                                <template x-if="totalMatches > filteredCities.length">
                                                    <span class="text-slate-400 italic">Type to narrow list</span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- If no cities exist for this state in DB -->
                                    <template x-if="selectedState && !loadingCities && cities.length === 0">
                                        <div class="px-4 py-6 text-center text-xs text-slate-500 italic">
                                            No cities found for this state
                                        </div>
                                    </template>
                                </div>

                            </div>

                            <!-- Search Button (Bright Pink) -->
                            <div class="sm:col-span-2">
                                <button type="submit" class="w-full bg-[#F20B65] hover:bg-[#d90858] text-white py-2.5 px-4 rounded-xl text-xs font-extrabold flex items-center justify-center gap-1.5 shadow-md shadow-[#F20B65]/30 transition transform hover:scale-[1.02]">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <span>Search</span>
                                </button>
                            </div>

                        </form>
                    </div>

                    <!-- Four Trust Badges Underneath Search Bar -->
                    <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs font-semibold text-slate-700">
                        <div class="flex items-center justify-center lg:justify-start space-x-2">
                            <div class="w-6 h-6 rounded-md bg-[#F20B65]/10 flex items-center justify-center text-[#F20B65]">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span>Safe &amp; Discreet</span>
                        </div>

                        <div class="flex items-center justify-center lg:justify-start space-x-2">
                            <div class="w-6 h-6 rounded-md bg-[#F20B65]/10 flex items-center justify-center text-[#F20B65]">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span>Your Privacy Matters</span>
                        </div>

                        <div class="flex items-center justify-center lg:justify-start space-x-2">
                            <div class="w-6 h-6 rounded-md bg-[#F20B65]/10 flex items-center justify-center text-[#F20B65]">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                </svg>
                            </div>
                            <span>Local Connections</span>
                        </div>

                        <div class="flex items-center justify-center lg:justify-start space-x-2">
                            <div class="w-6 h-6 rounded-md bg-[#F20B65]/10 flex items-center justify-center text-[#F20B65]">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span>Free to Join</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- ======================================================================= -->
    <!-- 2. CATEGORY NAVIGATION ROW (Clean White Cards with Pink Icons)          -->
    <!-- ======================================================================= -->
    <section class="bg-white border-b border-slate-200 py-6 sm:py-8 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-3.5">
                <span class="text-xs font-bold text-[#737784] uppercase tracking-wider">Explore Personal Classifications</span>
                <div class="hidden sm:flex items-center gap-2 text-[11px] font-bold text-slate-700">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>100% 18+ Age Verified</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 text-rose-800 border border-rose-200 shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-[#F20B65]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Anti-Triangulation Location Fuzzing</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-50 text-purple-900 border border-purple-200 shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Automated Photo EXIF Stripping</span>
                    </span>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-10 gap-2.5 sm:gap-3">
                @foreach($homepageCategories as $catCard)
                    <a href="{{ $catCard['url'] }}" class="bg-white rounded-2xl border-2 border-slate-200/90 hover:border-[#F20B65] p-3 text-center shadow-2xs hover:shadow-lg transition-all duration-300 group flex flex-col items-center justify-center min-h-[105px]">
                        <!-- Modern Natural Vector Icon Matching Exact Category Desires -->
                        <div class="w-10 h-10 mb-2 rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center text-[#F20B65] group-hover:bg-[#F20B65] group-hover:text-white group-hover:scale-105 transition-all duration-300 shadow-2xs">
                            @if($catCard['slug'] === 'men-seeking-women')
                                <!-- 1. Men Seeking Women: Man + Heart + Woman -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <circle cx="6" cy="6" r="2.5" />
                                    <path stroke-linecap="round" d="M2.5 17v-3a3 3 0 013-3h1a3 3 0 013 3v3" />
                                    <path fill="currentColor" stroke="none" d="M12 9.2c-.8-1.2-2.3-1.4-3.1-.3-.6.8-.4 2 .8 3.1l2.3 2.1 2.3-2.1c1.2-1.1 1.4-2.3.8-3.1-.8-1.1-2.3-.9-3.1.3z" />
                                    <circle cx="18" cy="6" r="2.5" />
                                    <path stroke-linecap="round" d="M14.5 17l1.5-6h4l1.5 6" />
                                </svg>
                            @elseif($catCard['slug'] === 'men-seeking-men')
                                <!-- 2. Men Seeking Men: Two Men Duo -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <circle cx="7" cy="6" r="2.5" />
                                    <path stroke-linecap="round" d="M3.5 18v-3a3 3 0 013-3h1a3 3 0 013 3v3" />
                                    <circle cx="17" cy="6" r="2.5" />
                                    <path stroke-linecap="round" d="M13.5 18v-3a3 3 0 013-3h1a3 3 0 013 3v3" />
                                    <path stroke-linecap="round" stroke-width="2" d="M10.5 14h3" />
                                </svg>
                            @elseif($catCard['slug'] === 'men-seeking-couples')
                                <!-- 3. Men Seeking Couples: Man + Plus + Couple -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <circle cx="5" cy="7" r="2" />
                                    <path stroke-linecap="round" d="M2.5 17v-2a2.5 2.5 0 012.5-2.5h0a2.5 2.5 0 012.5 2.5v2" />
                                    <path stroke-linecap="round" stroke-width="2.5" d="M9 13.5h2m-1-1v2" />
                                    <circle cx="14.5" cy="7" r="1.8" />
                                    <circle cx="19.5" cy="7" r="1.8" />
                                    <path stroke-linecap="round" d="M13 17v-2a1.8 1.8 0 011.8-1.8h3.4A1.8 1.8 0 0120 15v2" />
                                </svg>
                            @elseif($catCard['slug'] === 'women-seeking-men')
                                <!-- 4. Women Seeking Men: Woman + Sparkle + Man -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <circle cx="6" cy="6" r="2.5" />
                                    <path stroke-linecap="round" d="M3 17l1.5-6h3L9 17" />
                                    <path fill="currentColor" stroke="none" d="M12 8.5c-.7-1-1.9-1.2-2.6-.3-.5.7-.3 1.7.7 2.6l1.9 1.8 1.9-1.8c1-.9 1.2-1.9.7-2.6-.7-.9-1.9-.7-2.6.3z" />
                                    <circle cx="18" cy="6" r="2.5" />
                                    <path stroke-linecap="round" d="M14.5 17v-3a3 3 0 013-3h1a3 3 0 013 3v3" />
                                </svg>
                            @elseif($catCard['slug'] === 'women-seeking-women')
                                <!-- 5. Women Seeking Women: Two Women Duo -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <circle cx="7" cy="6" r="2.5" />
                                    <path stroke-linecap="round" d="M4 18l1.5-6h3L10 18" />
                                    <circle cx="17" cy="6" r="2.5" />
                                    <path stroke-linecap="round" d="M14 18l1.5-6h3l1.5 6" />
                                    <path fill="currentColor" stroke="none" d="M12 3.8c-.5-.7-1.4-.9-1.9-.2-.4.5-.2 1.3.5 1.9l1.4 1.3 1.4-1.3c.7-.6.9-1.4.5-1.9-.5-.7-1.4-.5-1.9.2z" />
                                </svg>
                            @elseif($catCard['slug'] === 'women-seeking-couples')
                                <!-- 6. Women Seeking Couples: Woman + Plus + Couple -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <circle cx="5" cy="7" r="2" />
                                    <path stroke-linecap="round" d="M2.5 17l1.2-4.5h2.6l1.2 4.5" />
                                    <path stroke-linecap="round" stroke-width="2.5" d="M9 13.5h2m-1-1v2" />
                                    <circle cx="14.5" cy="7" r="1.8" />
                                    <circle cx="19.5" cy="7" r="1.8" />
                                    <path stroke-linecap="round" d="M13 17v-2a1.8 1.8 0 011.8-1.8h3.4A1.8 1.8 0 0120 15v2" />
                                </svg>
                            @elseif($catCard['slug'] === 'couples-seeking-men')
                                <!-- 7. Couples Seeking Men: Couple -> Man -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <circle cx="5" cy="7" r="1.8" />
                                    <circle cx="10" cy="7" r="1.8" />
                                    <path stroke-linecap="round" d="M3.5 17v-2a1.8 1.8 0 011.8-1.8h4.4a1.8 1.8 0 011.8 1.8v2" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13.5h2.5m-1-1.5l1.5 1.5-1.5 1.5" />
                                    <circle cx="19.5" cy="7" r="2" />
                                    <path stroke-linecap="round" d="M17 17v-2a2.5 2.5 0 012.5-2.5h0a2.5 2.5 0 012.5 2.5v2" />
                                </svg>
                            @elseif($catCard['slug'] === 'couples-seeking-women')
                                <!-- 8. Couples Seeking Women: Couple -> Woman -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <circle cx="5" cy="7" r="1.8" />
                                    <circle cx="10" cy="7" r="1.8" />
                                    <path stroke-linecap="round" d="M3.5 17v-2a1.8 1.8 0 011.8-1.8h4.4a1.8 1.8 0 011.8 1.8v2" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13.5h2.5m-1-1.5l1.5 1.5-1.5 1.5" />
                                    <circle cx="19.5" cy="7" r="2" />
                                    <path stroke-linecap="round" d="M17 17l1.2-4.5h2.6l1.2 4.5" />
                                </svg>
                            @elseif($catCard['slug'] === 'couples-seeking-couples')
                                <!-- 9. Couples Seeking Couples: Double Couples Connected -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <circle cx="4.5" cy="7" r="1.6" />
                                    <circle cx="8.5" cy="7" r="1.6" />
                                    <path stroke-linecap="round" d="M3 17v-2a1.5 1.5 0 011.5-1.5h4A1.5 1.5 0 0110 15v2" />
                                    <path fill="currentColor" stroke="none" d="M12 9.5c-.6-.8-1.6-1-2.2-.2-.4.6-.3 1.4.6 2.2l1.6 1.5 1.6-1.5c.9-.8 1-1.6.6-2.2-.6-.8-1.6-.6-2.2.2z" />
                                    <circle cx="15.5" cy="7" r="1.6" />
                                    <circle cx="19.5" cy="7" r="1.6" />
                                    <path stroke-linecap="round" d="M14 17v-2a1.5 1.5 0 011.5-1.5h4a1.5 1.5 0 011.5 1.5v2" />
                                </svg>
                            @else
                                <!-- Romance & Desire Default Icon -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            @endif
                        </div>
                        <div class="text-[11px] font-black text-slate-800 group-hover:text-[#F20B65] transition leading-tight">
                            {{ $catCard['title'] }}
                        </div>
                        @if(!empty($catCard['subtitle']))
                            <div class="text-[10px] font-bold text-slate-500 mt-0.5">
                                {{ $catCard['subtitle'] }}
                            </div>
                        @endif
                    </a>
                @endforeach

                <!-- 10th Card: More Categories -->
                <a href="{{ route('categories.index') }}" class="bg-white rounded-2xl border-2 border-slate-200/90 hover:border-[#F20B65] p-3 text-center shadow-2xs hover:shadow-lg transition-all duration-300 group flex flex-col items-center justify-center min-h-[105px]">
                    <div class="w-10 h-10 mb-2 rounded-2xl bg-pink-50 border border-pink-100 flex items-center justify-center text-[#F20B65] group-hover:bg-[#F20B65] group-hover:text-white group-hover:scale-105 transition-all duration-300 shadow-2xs">
                        <svg class="w-5 h-5 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </div>
                    <div class="text-[11px] font-black text-slate-800 group-hover:text-[#F20B65] transition leading-tight flex items-center justify-center gap-0.5">
                        <span>More</span>
                    </div>
                    <div class="text-[10px] font-bold text-[#F20B65] mt-0.5 flex items-center justify-center">
                        <span>Categories ›</span>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Homepage Top Advertisement Slot -->
    <div class="bg-[#F8F7FA] pt-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-ad-banner placement="homepage_top" variant="leaderboard" :limit="1" />
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- 3. MAIN CONTENT: FEATURED LISTINGS + PROMOTIONAL SIDEBAR                -->
    <!-- ======================================================================= -->
    <section class="bg-[#F8F7FA] py-8 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Left/Center Main Column: Featured Listings & Popular Locations -->
                <div class="lg:col-span-9 space-y-10">
                    
                    <!-- A. Featured Listings Section -->
                    <div>
                        <!-- Section Header -->
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center space-x-2.5">
                                <span class="text-[#F20B65]">
                                    <svg class="w-6 h-6 fill-current" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.316.492-.474.966-.547 1.341C9.28 4.298 9 5.093 9 6.25c0 1.545.485 2.527.97 3.327.245.405.496.753.682 1.05.187.297.284.502.284.623 0 .285-.11.482-.24.62-.128.136-.296.2-.496.2-.206 0-.376-.063-.504-.202-.132-.143-.243-.342-.243-.628 0-.135.097-.34.284-.637.186-.297.437-.645.682-1.05.485-.8 1.08-1.782 1.08-3.327 0-1.157-.28-1.952-.576-2.864-.073-.375-.231-.849-.547-1.341a4.954 4.954 0 00-.822-.88 1 1 0 00-1.45.385C7.227 4.14 7 5.56 7 7c0 4.418 3.582 8 8 8s8-3.582 8-8c0-1.44-.227-2.86-1.605-4.447z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <h2 class="text-xl sm:text-2xl font-black text-[#171923]">Featured Listings</h2>
                            </div>
                            <a href="{{ route('search.index', ['featured' => 1]) }}" class="text-xs font-bold text-[#F20B65] hover:text-[#d90858] flex items-center gap-1 transition">
                                <span>View All Featured</span>
                                <span>→</span>
                            </a>
                        </div>

                        <!-- 4 Featured Cards Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            @forelse($featuredListings as $listing)
                                <div class="bg-white rounded-xl border border-[#E8E4EC] overflow-hidden shadow-xs hover:shadow-xl transition-all duration-300 flex flex-col group">
                                    <!-- Image Thumbnail: Hardware-Safe Uncropped Photo Frame -->
                                    <div class="relative h-72 sm:h-80 overflow-hidden bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 flex items-center justify-center isolate">
                                        <a href="{{ route('listings.show', $listing->slug) }}" class="w-full h-full flex items-center justify-center relative block">
                                            <img 
                                                src="{{ $listing->primary_thumbnail_url }}" 
                                                alt="{{ $listing->title }}" 
                                                loading="lazy"
                                                decoding="async"
                                                onerror="this.onerror=null;this.src='{{ asset('images/featured-1.jpg') }}';"
                                                class="max-h-full max-w-full w-auto h-auto object-contain mx-auto block"
                                            />
                                        </a>
                                        <!-- Featured Badge (Pink Pill) -->
                                        <span class="absolute top-2.5 left-2.5 bg-[#F20B65] text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded-full shadow-md tracking-wide pointer-events-none">
                                            Featured
                                        </span>
                                    </div>

                                    <!-- Card Content -->
                                    <div class="p-3 flex-1 flex flex-col justify-between">
                                        <div>
                                            <a href="{{ route('listings.show', $listing->slug) }}" class="block text-xs font-bold text-[#171923] group-hover:text-[#F20B65] transition truncate">
                                                {{ $listing->title }}
                                            </a>
                                            <div class="text-[11px] font-medium text-[#737784] mt-0.5 truncate">
                                                {{ $listing->city?->name ?? 'New York' }}, {{ $listing->city?->state?->code ?? 'NY' }}
                                            </div>
                                            <!-- Age • Height Demographics -->
                                            <div class="text-[11px] font-bold text-slate-700 mt-1">
                                                {{ $listing->poster_age ?? 28 }} • {{ $listing->poster_age > 30 ? "5'7\"" : "5'6\"" }}
                                            </div>
                                        </div>

                                        <!-- Card Footer: Views + View Ad Button -->
                                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between">
                                            <div class="flex items-center text-[11px] font-medium text-[#737784] space-x-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>{{ ($listing->view_count ?? 0) >= 1000 ? number_format(($listing->view_count ?? 0) / 1000, 1) . 'k' : number_format($listing->view_count ?? 0) }}</span>
                                            </div>

                                            <a href="{{ route('listings.show', $listing->slug) }}" class="bg-[#F20B65] hover:bg-[#d90858] text-white text-[11px] font-bold px-3 py-1 rounded-md flex items-center gap-1 shadow-xs transition">
                                                <span>View Ad</span>
                                                <span>→</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-4 bg-white rounded-xl p-8 text-center border border-slate-200 text-slate-500">
                                    No featured listings found in this region.
                                </div>
                            @endforelse

                        </div>

                        @if(isset($homepageFeaturedAds) && $homepageFeaturedAds->isNotEmpty())
                            @php
                                $featuredBanners = $homepageFeaturedAds->filter(fn ($ad) => ($ad['format'] ?? 'banner') === 'banner' && (!empty($ad['desktop_banner_url']) || !empty($ad['mobile_banner_url']) || !empty($ad['html_snippet'])));
                                $featuredCards = $homepageFeaturedAds->reject(fn ($ad) => ($ad['format'] ?? 'banner') === 'banner' && (!empty($ad['desktop_banner_url']) || !empty($ad['mobile_banner_url']) || !empty($ad['html_snippet'])));
                            @endphp
                            @if($featuredCards->isNotEmpty())
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mt-5">
                                    @foreach($featuredCards as $featuredAd)
                                        <x-sponsored-listing-card :ad="$featuredAd" />
                                    @endforeach
                                </div>
                            @endif
                            @if($featuredBanners->isNotEmpty())
                                <x-ad-banner placement="homepage_featured" :ads="$featuredBanners" variant="leaderboard" class="mt-5" />
                            @endif
                        @endif
                    </div>

                    <!-- B. Popular Locations Section -->
                    <div>
                        <!-- Section Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <span class="text-[#F20B65]">
                                    <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <h2 class="text-xl sm:text-2xl font-black text-[#171923]">Popular Locations</h2>
                            </div>
                            <a href="{{ route('locations.index') }}" class="text-xs font-bold text-[#F20B65] hover:text-[#d90858] flex items-center gap-1 transition">
                                <span>View All States</span>
                                <span>→</span>
                            </a>
                        </div>

                        <!-- Responsive State Grid with Full Names & High Contrast -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                            @foreach($popularStates as $st)
                                <a href="{{ route('locations.state', strtolower($st->code)) }}" class="bg-white rounded-2xl border-2 border-slate-200/90 hover:border-[#F20B65] p-3 shadow-2xs hover:shadow-md transition-all group flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-xl bg-pink-50 text-[#F20B65] font-black text-xs flex items-center justify-center shrink-0 border border-pink-100 group-hover:bg-[#F20B65] group-hover:text-white transition shadow-2xs">
                                        {{ $st->code }}
                                    </span>
                                    <div class="min-w-0 text-left">
                                        <div class="text-xs font-black text-slate-800 group-hover:text-[#F20B65] transition leading-tight truncate">
                                            {{ $st->name }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-bold mt-0.5 flex items-center gap-1">
                                            <svg class="w-3 h-3 text-[#F20B65]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                            <span>United States</span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>

                </div>

                <!-- Right Column: Promotional Sidebar Cards -->
                <div class="lg:col-span-3 space-y-5">
                    
                    <!-- 1. Join Our Community Card -->
                    <div class="bg-white text-slate-900 rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
                        <!-- Decorative ambient glow -->
                        <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-[#F20B65]/10 rounded-full blur-2xl"></div>

                        <h3 class="text-lg font-black text-slate-900">
                            Join Our Community
                        </h3>
                        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                            Create your free account today and start connecting locally!
                        </p>

                        <a href="{{ route('register') }}" class="mt-4 w-full bg-[#F20B65] hover:bg-[#d90858] text-white py-2.5 px-4 rounded-xl text-xs font-extrabold flex items-center justify-center gap-2 shadow-md shadow-[#F20B65]/20 transition transform hover:scale-[1.02]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <span>Sign Up Free</span>
                        </a>
                    </div>

                    <!-- 2. Sunset Romance Promotional Banner -->
                    <div class="rounded-2xl overflow-hidden shadow-sm border border-slate-200 relative group">
                        <a href="{{ route('register') }}" class="block relative">
                            <img 
                                src="{{ asset('images/promo-banner.jpg') }}" 
                                alt="Find Your Perfect Match" 
                                class="w-full h-auto object-cover group-hover:scale-105 transition duration-300"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent flex flex-col justify-end p-4">
                                <div class="text-xl font-bold font-serif italic text-pink-600 drop-shadow-md">
                                    Find Your Perfect Match
                                </div>
                                <div class="text-xs font-bold text-white flex items-center gap-1 mt-1">
                                    <svg class="w-3.5 h-3.5 text-[#F20B65] fill-current" viewBox="0 0 24 24">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                    <span>LocalDate<span class="text-[#F20B65]">Hub</span></span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- 3. Safety & 18+ Verification Badge Widget -->
                    <div class="bg-white rounded-2xl p-4 border border-[#E8E4EC] shadow-xs">
                        <div class="flex items-center space-x-2 text-xs font-extrabold text-[#171923]">
                            <svg class="w-4 h-4 text-emerald-500 fill-current" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>100% 18+ Verified Platform</span>
                        </div>
                        <p class="text-[11px] text-[#737784] mt-1.5 leading-relaxed">
                            All personal listings undergo strict compliance screening. Zero tolerance for commercial prostitution or non-consensual content.
                        </p>
                        <a href="{{ route('pages.safety') }}" class="text-[11px] font-bold text-[#F20B65] hover:underline mt-2 inline-block">
                            Read Safety Guidelines →
                        </a>
                    </div>

                    <!-- 4. Dynamic Enterprise Sidebar Advertisement(s) -->
                    <x-ad-banner placement="sidebar" variant="sidebar" :limit="2" />

                </div>
            </div>
        </div>
    </section>

    <!-- ======================================================================= -->
    <!-- 4. LATEST LISTINGS & POPULAR CITIES SECTION                              -->
    <!-- ======================================================================= -->
    <section class="bg-white border-t border-[#E8E4EC] py-10 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-black text-[#171923]">Latest Local Listings</h2>
                    <p class="text-xs text-[#737784] mt-1">Recently posted personal advertisements in your area</p>
                </div>
                <a href="{{ route('search.index') }}" class="text-xs font-bold text-[#F20B65] hover:text-[#d90858] flex items-center gap-1 transition">
                    <span>Browse All Listings</span>
                    <span>→</span>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @php
                    $feedCollection = $latestListingsFeed ?? $latestListings->map(fn ($l) => ['type' => 'listing', 'listing' => $l]);
                @endphp
                @foreach($feedCollection as $feedItem)
                    @if(($feedItem['type'] ?? 'listing') === 'ad')
                        <x-sponsored-listing-card :ad="$feedItem['ad']" />
                    @else
                        @php $listing = $feedItem['listing']; @endphp
                        <div class="bg-white rounded-xl border border-[#E8E4EC] overflow-hidden shadow-xs hover:shadow-lg transition-all flex flex-col group">
                            <!-- Image Container: Hardware-Safe Uncropped Photo Frame -->
                            <div class="relative h-72 sm:h-80 overflow-hidden bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 flex items-center justify-center isolate">
                                <a href="{{ route('listings.show', $listing->slug) }}" class="w-full h-full flex items-center justify-center relative block">
                                    <img 
                                        src="{{ $listing->primary_thumbnail_url }}" 
                                        alt="{{ $listing->title }}" 
                                        loading="lazy"
                                        decoding="async"
                                        onerror="this.onerror=null;this.src='{{ asset('images/featured-1.jpg') }}';"
                                        class="max-h-full max-w-full w-auto h-auto object-contain mx-auto block"
                                    />
                                </a>
                                <span class="absolute bottom-2 left-2 bg-black/75 text-white text-[10px] font-semibold px-2 py-0.5 rounded shadow pointer-events-none">
                                    {{ $listing->city?->name ?? 'Austin' }}, {{ $listing->city?->state?->code ?? 'TX' }}
                                </span>
                            </div>
                            <div class="p-3.5 flex-1 flex flex-col justify-between">
                                <div>
                                    <a href="{{ route('listings.show', $listing->slug) }}" class="block text-xs font-bold text-[#171923] group-hover:text-[#F20B65] transition line-clamp-1">
                                        {{ $listing->title }}
                                    </a>
                                    <p class="text-[11px] text-[#737784] mt-1 line-clamp-2 leading-relaxed">
                                        {{ Str::limit($listing->description, 75) }}
                                    </p>
                                </div>
                                <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                                    <span class="text-[10px] text-slate-400 font-medium">
                                        {{ $listing->published_at?->diffForHumans() ?? 'Just now' }}
                                    </span>
                                    <a href="{{ route('listings.show', $listing->slug) }}" class="text-[11px] font-bold text-[#F20B65] hover:underline">
                                        View Ad →
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <!-- ======================================================================= -->
    <!-- 5. HOW LOCALDATEHUB WORKS (3 Simple Steps)                               -->
    <!-- ======================================================================= -->
    <section class="bg-[#F8F7FA] border-t border-[#E8E4EC] py-12 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl sm:text-3xl font-black text-[#171923]">How LocalDateHub Works</h2>
            <p class="text-xs sm:text-sm text-[#737784] max-w-xl mx-auto mt-2">
                Discreet, verified adult classifieds designed to connect singles and couples locally with complete privacy.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-10">
                <!-- Step 1 -->
                <div class="bg-white rounded-2xl p-6 border border-[#E8E4EC] shadow-xs text-center flex flex-col items-center">
                    <div class="w-12 h-12 rounded-2xl bg-[#F20B65]/10 text-[#F20B65] flex items-center justify-center font-black text-lg mb-4">
                        1
                    </div>
                    <h3 class="text-base font-bold text-[#171923]">Browse Categories</h3>
                    <p class="text-xs text-[#737784] mt-2 leading-relaxed">
                        Explore specific classified categories matching your mutual desires—from dating to lifestyle personal connections.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="bg-white rounded-2xl p-6 border border-[#E8E4EC] shadow-xs text-center flex flex-col items-center">
                    <div class="w-12 h-12 rounded-2xl bg-[#F20B65]/10 text-[#F20B65] flex items-center justify-center font-black text-lg mb-4">
                        2
                    </div>
                    <h3 class="text-base font-bold text-[#171923]">Find Local Listings</h3>
                    <p class="text-xs text-[#737784] mt-2 leading-relaxed">
                        Filter ads by US state, major city, and verified age brackets. Location privacy protects exact coordinates.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="bg-white rounded-2xl p-6 border border-[#E8E4EC] shadow-xs text-center flex flex-col items-center">
                    <div class="w-12 h-12 rounded-2xl bg-[#F20B65]/10 text-[#F20B65] flex items-center justify-center font-black text-lg mb-4">
                        3
                    </div>
                    <h3 class="text-base font-bold text-[#171923]">Connect Respectfully</h3>
                    <p class="text-xs text-[#737784] mt-2 leading-relaxed">
                        Reach out using our protected contact form with end-to-end encrypted messaging shielding your personal email address.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================================================= -->
    <!-- 6. FINAL CALL TO ACTION                                                 -->
    <!-- ======================================================================= -->
    <section class="bg-gradient-to-r from-[#17131A] via-[#2D1623] to-[#17131A] text-white py-12 sm:py-16 border-t border-slate-200">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="text-3xl sm:text-4xl font-black">
                Ready to Discover Local Connections?
            </h2>
            <p class="text-sm text-slate-600 mt-3 max-w-lg mx-auto">
                Join thousands of verified adults across the United States. Free registration, zero spam, and discrete personal ads.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <a href="{{ route('search.index') }}" class="bg-white hover:bg-slate-100 text-[#171923] px-6 py-3 rounded-xl text-xs font-extrabold shadow-lg transition">
                    Browse Listings
                </a>
                <a href="{{ route('listings.create') }}" class="bg-[#F20B65] hover:bg-[#d90858] text-white px-6 py-3 rounded-xl text-xs font-extrabold shadow-lg shadow-[#F20B65]/30 transition">
                    Create Your Listing
                </a>
            </div>
        </div>
    </section>

</x-layouts.app>
