<x-layouts.app title="Advanced Classified Search — LocalDateHub">
    <div class="py-10 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Advanced Search']
            ]" />

            <!-- Page Header -->
            <div class="mt-6 flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-200">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                            Search Personals &amp; Classifieds
                        </h1>
                        <span class="text-[11px] uppercase font-mono px-2.5 py-0.5 rounded-full bg-rose-50 text-[#F20B65] border border-rose-200 font-bold">
                            Live Database
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 font-semibold mt-1">
                        Found <span class="font-black text-[#F20B65]">{{ $listings->total() }}</span> active {{ Str::plural('listing', $listings->total()) }} matching your criteria
                    </p>
                </div>

                <!-- Sort Control -->
                <form method="GET" action="{{ route('search.index') }}" class="flex items-center gap-2.5">
                    @foreach($filter->toQueryArray() as $key => $val)
                        @if($key !== 'sort')
                            @if(is_array($val))
                                @foreach($val as $subKey => $subVal)
                                    <input type="hidden" name="attr[{{ $subKey }}]" value="{{ $subVal }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                            @endif
                        @endif
                    @endforeach
                    <label for="sort" class="text-xs font-bold text-slate-600 whitespace-nowrap">Sort by:</label>
                    <div class="relative">
                        <select id="sort" name="sort" onchange="this.form.submit()" class="bg-white border-2 border-slate-200 hover:border-slate-300 text-xs font-bold text-slate-800 rounded-xl px-3.5 py-2 pr-8 focus:ring-2 focus:ring-[#F20B65]/20 focus:border-[#F20B65] outline-none shadow-2xs cursor-pointer transition">
                            <option value="newest" {{ $filter->sort === 'newest' ? 'selected' : '' }}>Newest Published</option>
                            <option value="updated" {{ $filter->sort === 'updated' ? 'selected' : '' }}>Recently Updated</option>
                            <option value="views" {{ $filter->sort === 'views' ? 'selected' : '' }}>Most Viewed</option>
                            <option value="oldest" {{ $filter->sort === 'oldest' ? 'selected' : '' }}>Oldest</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Category Facet Pills with Modern SVG & High Contrast -->
            @if(!empty($categoryCounts))
                <div class="mt-6 flex flex-wrap gap-2 items-center">
                    <span class="text-xs font-extrabold text-slate-700 uppercase tracking-wider mr-1">Category:</span>
                    <a href="{{ route('search.index', array_merge($filter->toQueryArray(), ['category' => null, 'subcategory' => null])) }}"
                       class="px-3.5 py-1.5 rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-2xs {{ empty($filter->categorySlug) ? 'bg-[#F20B65] text-white shadow-md shadow-[#F20B65]/20' : 'bg-white text-slate-700 hover:text-slate-950 hover:bg-slate-100 border border-slate-200' }}">
                        <span>All</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] {{ empty($filter->categorySlug) ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600 font-bold' }}">
                            {{ array_sum($categoryCounts) }}
                        </span>
                    </a>
                    @foreach($categories as $cat)
                        @php $count = $categoryCounts[$cat->slug] ?? 0; @endphp
                        @if($count > 0 || $filter->categorySlug === $cat->slug)
                            <a href="{{ route('search.index', array_merge($filter->toQueryArray(), ['category' => $cat->slug, 'subcategory' => null])) }}"
                               class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-2xs {{ $filter->categorySlug === $cat->slug ? 'bg-[#F20B65] text-white font-black shadow-md shadow-[#F20B65]/20' : 'bg-white text-slate-700 hover:text-[#F20B65] hover:bg-rose-50/60 border border-slate-200' }}">
                                <span>{{ $cat->name }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $filter->categorySlug === $cat->slug ? 'bg-white/20 text-white font-black' : 'bg-slate-100 text-slate-500 font-semibold' }}">
                                    {{ $count }}
                                </span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            <!-- Transparent Ranking Information Banner -->
            <div class="mt-4 p-3.5 bg-gradient-to-r from-amber-50/80 via-white to-pink-50/30 border border-amber-200/90 rounded-2xl text-xs text-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-black shrink-0 text-xs shadow-2xs">
                        ★
                    </span>
                    <span class="font-medium text-slate-700">
                        <strong class="font-extrabold text-slate-900">Transparent Ranking:</strong> Featured ads (City &amp; Metro Spotlight) are positioned prominently at top; results then strictly follow your sort order.
                    </span>
                </div>
                @if($filter->featuredOnly)
                    <span class="px-3 py-1 rounded-lg text-[11px] font-black bg-amber-500 text-white shadow-2xs shrink-0 self-start sm:self-auto flex items-center gap-1">
                        <span>★</span>
                        <span>Featured Filter Active</span>
                    </span>
                @endif
            </div>

            <div class="mt-8 grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Filter Sidebar -->
                <aside class="lg:col-span-1 space-y-6">
                    <form method="GET" action="{{ route('search.index') }}" class="bg-white border-2 border-slate-200/90 rounded-3xl p-5 sm:p-6 space-y-5 shadow-sm">
                        <!-- Sidebar Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-xl bg-pink-50 text-[#F20B65] flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                    </svg>
                                </span>
                                <h2 class="text-sm font-black uppercase tracking-wider text-slate-900">Refine Search</h2>
                            </div>
                            <a href="{{ route('search.index') }}" class="text-xs font-bold text-slate-500 hover:text-[#F20B65] transition flex items-center gap-1 bg-slate-100 hover:bg-rose-50 px-2.5 py-1 rounded-lg">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span>Reset</span>
                            </a>
                        </div>

                        <!-- Keyword / Topic -->
                        <div class="space-y-1.5">
                            <label for="q" class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span>Keyword / Topic</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    id="q"
                                    name="q"
                                    value="{{ $filter->query }}"
                                    placeholder="e.g. coffee, hiking, artist"
                                    class="w-full bg-slate-50 border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 placeholder-slate-400 focus:bg-white focus:border-[#F20B65] focus:ring-2 focus:ring-[#F20B65]/20 focus:outline-none transition shadow-2xs"
                                />
                            </div>
                        </div>

                        <!-- Category Hierarchy -->
                        <div class="space-y-1.5">
                            <label for="category" class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span>Category</span>
                            </label>
                            <select id="category" name="category" class="w-full bg-slate-50 border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:bg-white focus:border-[#F20B65] focus:ring-2 focus:ring-[#F20B65]/20 focus:outline-none transition shadow-2xs cursor-pointer">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->slug }}" {{ $filter->categorySlug === $cat->slug ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                    @if($cat->children && $cat->children->count())
                                        @foreach($cat->children as $child)
                                            <option value="{{ $child->slug }}" {{ $filter->categorySlug === $child->slug ? 'selected' : '' }}>
                                                &nbsp;&nbsp;— {{ $child->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <!-- Specific Subcategory -->
                        <div class="space-y-1.5">
                            <label for="subcategory" class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <span>Subcategory Slug</span>
                            </label>
                            <input
                                type="text"
                                id="subcategory"
                                name="subcategory"
                                value="{{ $filter->subcategorySlug }}"
                                placeholder="e.g. casual-dating"
                                class="w-full bg-slate-50 border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 placeholder-slate-400 focus:bg-white focus:border-[#F20B65] focus:ring-2 focus:ring-[#F20B65]/20 focus:outline-none transition shadow-2xs"
                            />
                        </div>

                        <!-- Location: State & Cascading City Dropdown -->
                        <div x-data="{
                            selectedState: '{{ $filter->stateCode ?? '' }}',
                            selectedCity: '{{ $filter->cityName ?? '' }}',
                            cityInput: '{{ $filter->cityName ?? '' }}',
                            cities: [],
                            cityDropdownOpen: false,
                            loadingCities: false,
                            async onStateChange(event) {
                                const st = event.target.value;
                                this.selectedState = st;
                                this.selectedCity = '';
                                this.cityInput = '';
                                this.cities = [];
                                this.cityDropdownOpen = false;
                                if (!st) return;
                                this.loadingCities = true;
                                try {
                                    const res = await fetch(`/api/locations/states/${st.toLowerCase()}/cities`);
                                    const json = await res.json();
                                    if (json.success && json.data && json.data.cities) {
                                        this.cities = json.data.cities;
                                    } else {
                                        const fb = await fetch('/data/us-cities.json');
                                        const fbMap = await fb.json();
                                        if (fbMap && fbMap[st.toLowerCase()]) {
                                            this.cities = fbMap[st.toLowerCase()];
                                        }
                                    }
                                } catch(e) {
                                    try {
                                        const fb = await fetch('/data/us-cities.json');
                                        const fbMap = await fb.json();
                                        if (fbMap && fbMap[st.toLowerCase()]) {
                                            this.cities = fbMap[st.toLowerCase()];
                                        }
                                    } catch(err) {}
                                }
                                this.loadingCities = false;
                            },
                            get filteredCities() {
                                let list = this.cities;
                                if (this.cityInput && this.cityInput.trim().length > 0) {
                                    const q = this.cityInput.toLowerCase().trim();
                                    list = this.cities.filter(c => c.name.toLowerCase().includes(q));
                                }
                                return list.slice(0, 60);
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
                                        this.selectedCity = '';
                                        this.cityInput = '';
                                    }
                                }
                            },
                            init() {
                                if (this.selectedState) {
                                    this.onStateChange({ target: { value: this.selectedState } }).then(() => {
                                        if ('{{ $filter->cityName ?? '' }}') {
                                            this.selectedCity = '{{ $filter->cityName ?? '' }}';
                                            this.cityInput = '{{ $filter->cityName ?? '' }}';
                                        }
                                    });
                                }
                            }
                        }" class="space-y-4">
                            <!-- US State -->
                            <div class="space-y-1.5">
                                <label for="state" class="block text-xs font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>US State</span>
                                </label>
                                <select 
                                    id="state" 
                                    name="state" 
                                    x-model="selectedState" 
                                    @change="onStateChange($event)"
                                    class="w-full bg-slate-50 border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:bg-white focus:border-[#F20B65] focus:ring-2 focus:ring-[#F20B65]/20 focus:outline-none transition shadow-2xs cursor-pointer"
                                >
                                    <option value="">Any State</option>
                                    @foreach($states as $st)
                                        <option value="{{ $st->code }}" {{ $filter->stateCode === $st->code ? 'selected' : '' }}>
                                            {{ $st->name }} ({{ $st->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Location: City with dynamic cascading suggestions -->
                            <div class="relative space-y-1.5" @click.outside="cityDropdownOpen = false">
                                <label for="city-input" class="block text-xs font-extrabold uppercase tracking-wider text-slate-800 flex items-center justify-between">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span>City Name</span>
                                    </span>
                                    <span x-show="loadingCities" class="text-[10px] text-[#F20B65] font-extrabold animate-pulse">Loading cities...</span>
                                </label>

                                <input type="hidden" name="city" :value="selectedCity">

                                <div class="relative">
                                    <input
                                        id="city-input"
                                        type="text"
                                        x-model="cityInput"
                                        @click="if(selectedState) { cityDropdownOpen = true; }"
                                        @input="selectedCity = cityInput; if(selectedState) { cityDropdownOpen = true; }"
                                        :disabled="!selectedState"
                                        :placeholder="!selectedState ? 'Select a State first' : (loadingCities ? 'Loading cities...' : 'Search city in ' + selectedState + '...')"
                                        class="w-full bg-slate-50 border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 placeholder-slate-400 focus:bg-white focus:border-[#F20B65] focus:ring-2 focus:ring-[#F20B65]/20 focus:outline-none transition shadow-2xs"
                                        :class="{ 'opacity-60 cursor-not-allowed bg-slate-100': !selectedState }"
                                        autocomplete="off"
                                    />
                                    <template x-if="cityInput">
                                        <button 
                                            type="button" 
                                            @click="cityInput = ''; selectedCity = '';" 
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 text-sm font-black p-1"
                                        >
                                            &times;
                                        </button>
                                    </template>
                                </div>

                                <!-- Dropdown List -->
                                <div 
                                    x-show="cityDropdownOpen && selectedState && cities.length > 0" 
                                    x-cloak
                                    class="absolute left-0 right-0 top-full mt-1.5 bg-white border-2 border-slate-200 rounded-2xl shadow-xl z-50 max-h-56 overflow-y-auto divide-y divide-slate-100 text-left p-1.5"
                                >
                                    <div class="p-2 flex items-center justify-between border-b border-slate-100 text-[11px] text-slate-600 font-extrabold bg-slate-50/80 rounded-t-xl">
                                        <span x-text="cities.length + ' Cities in ' + selectedState"></span>
                                        <button type="button" @click="selectCity('')" class="text-[#F20B65] hover:underline font-bold">All Cities</button>
                                    </div>
                                    <template x-for="c in filteredCities" :key="c.id">
                                        <button 
                                            type="button" 
                                            @click="selectCity(c.name)" 
                                            class="w-full text-left px-3.5 py-2 text-xs font-semibold text-slate-800 hover:bg-rose-50 hover:text-[#F20B65] rounded-xl flex items-center justify-between transition"
                                            :class="{ 'bg-rose-50 text-[#F20B65] font-black': selectedCity === c.name }"
                                        >
                                            <span x-text="c.name"></span>
                                            <template x-if="c.is_popular">
                                                <span class="text-[9px] px-2 py-0.5 bg-pink-100 text-[#F20B65] rounded-full font-black uppercase tracking-wider">Popular</span>
                                            </template>
                                        </button>
                                    </template>
                                    <template x-if="filteredCities.length === 0">
                                        <div class="p-4 text-center text-xs text-slate-500 font-semibold italic">No matching cities found</div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Location: ZIP Code -->
                        <div class="space-y-1.5">
                            <label for="zip" class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>ZIP Code (5 Digits)</span>
                            </label>
                            <input
                                type="text"
                                id="zip"
                                name="zip"
                                value="{{ $filter->zipCode }}"
                                placeholder="e.g. 78701, 90210"
                                class="w-full bg-slate-50 border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 placeholder-slate-400 focus:bg-white focus:border-[#F20B65] focus:ring-2 focus:ring-[#F20B65]/20 focus:outline-none transition shadow-2xs"
                            />
                        </div>

                        <!-- Radius -->
                        <div class="space-y-1.5">
                            <label for="radius" class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                                <span>Distance Radius</span>
                            </label>
                            <select id="radius" name="radius" class="w-full bg-slate-50 border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:bg-white focus:border-[#F20B65] focus:ring-2 focus:ring-[#F20B65]/20 focus:outline-none transition shadow-2xs cursor-pointer">
                                <option value="10" {{ $filter->radiusKm == 10 ? 'selected' : '' }}>Within 10 km (6 miles)</option>
                                <option value="25" {{ $filter->radiusKm == 25 ? 'selected' : '' }}>Within 25 km (15 miles)</option>
                                <option value="50" {{ $filter->radiusKm == 50 ? 'selected' : '' }}>Within 50 km (30 miles)</option>
                                <option value="100" {{ $filter->radiusKm == 100 ? 'selected' : '' }}>Within 100 km (60 miles)</option>
                                <option value="150" {{ $filter->radiusKm == 150 ? 'selected' : '' }}>Within 150 km (90 miles)</option>
                            </select>
                        </div>

                        <!-- Attribute: Relationship Intent -->
                        <div class="space-y-1.5">
                            <label for="intent" class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                <span>Relationship Intent</span>
                            </label>
                            <select id="intent" name="attr[intent]" class="w-full bg-slate-50 border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:bg-white focus:border-[#F20B65] focus:ring-2 focus:ring-[#F20B65]/20 focus:outline-none transition shadow-2xs cursor-pointer">
                                <option value="">Any Intent</option>
                                <option value="dating" {{ ($filter->attributes['intent'] ?? '') === 'dating' ? 'selected' : '' }}>Dating &amp; Romance</option>
                                <option value="activity_partner" {{ ($filter->attributes['intent'] ?? '') === 'activity_partner' ? 'selected' : '' }}>Activity Partner</option>
                                <option value="friendship" {{ ($filter->attributes['intent'] ?? '') === 'friendship' ? 'selected' : '' }}>Friendship &amp; Social</option>
                                <option value="casual" {{ ($filter->attributes['intent'] ?? '') === 'casual' ? 'selected' : '' }}>Casual Connections</option>
                                <option value="long_term" {{ ($filter->attributes['intent'] ?? '') === 'long_term' ? 'selected' : '' }}>Long-term Relationship</option>
                            </select>
                        </div>

                        <!-- Published Date Timeframe -->
                        <div class="space-y-1.5">
                            <label for="published_date" class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Published Within</span>
                            </label>
                            <select id="published_date" name="published_date" class="w-full bg-slate-50 border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:bg-white focus:border-[#F20B65] focus:ring-2 focus:ring-[#F20B65]/20 focus:outline-none transition shadow-2xs cursor-pointer">
                                <option value="all" {{ $filter->publishedDate === 'all' ? 'selected' : '' }}>All Time</option>
                                <option value="today" {{ $filter->publishedDate === 'today' ? 'selected' : '' }}>Today (Past 24 Hours)</option>
                                <option value="7d" {{ $filter->publishedDate === '7d' ? 'selected' : '' }}>Past 7 Days</option>
                                <option value="30d" {{ $filter->publishedDate === '30d' ? 'selected' : '' }}>Past 30 Days</option>
                            </select>
                        </div>

                        <!-- Age Range -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>Poster Age Range</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <input type="number" name="min_age" min="18" max="99" value="{{ $filter->minAge }}" placeholder="Min (18)" class="w-full bg-slate-50 border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 placeholder-slate-400 focus:bg-white focus:border-[#F20B65] focus:ring-2 focus:ring-[#F20B65]/20 focus:outline-none transition shadow-2xs">
                                </div>
                                <div>
                                    <input type="number" name="max_age" min="18" max="99" value="{{ $filter->maxAge }}" placeholder="Max (99)" class="w-full bg-slate-50 border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 placeholder-slate-400 focus:bg-white focus:border-[#F20B65] focus:ring-2 focus:ring-[#F20B65]/20 focus:outline-none transition shadow-2xs">
                                </div>
                            </div>
                        </div>

                        <!-- Modern Interactive Filter Checkboxes -->
                        <div class="space-y-2.5 pt-3 border-t border-slate-200">
                            <!-- Featured Only -->
                            <label class="flex items-center gap-3 p-3 rounded-xl border-2 transition cursor-pointer {{ $filter->featuredOnly ? 'bg-amber-50/90 border-amber-300 text-amber-900' : 'bg-slate-50/70 border-slate-200 hover:border-amber-200 text-slate-800' }}">
                                <input type="checkbox" name="featured_only" value="1" {{ $filter->featuredOnly ? 'checked' : '' }} class="w-4 h-4 rounded-md border-slate-300 text-amber-500 focus:ring-amber-400">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-black text-xs">★</span>
                                    <span class="text-xs font-black">Featured Ads Only</span>
                                </div>
                            </label>

                            <!-- With Photos Only -->
                            <label class="flex items-center gap-3 p-3 rounded-xl border-2 transition cursor-pointer {{ $filter->withPhotosOnly ? 'bg-blue-50/90 border-blue-300 text-blue-900' : 'bg-slate-50/70 border-slate-200 hover:border-blue-200 text-slate-800' }}">
                                <input type="checkbox" name="with_photos" value="1" {{ $filter->withPhotosOnly ? 'checked' : '' }} class="w-4 h-4 rounded-md border-slate-300 text-blue-600 focus:ring-blue-400">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </span>
                                    <span class="text-xs font-bold">With Photos Only</span>
                                </div>
                            </label>

                            <!-- Verified Posters Only -->
                            <label class="flex items-center gap-3 p-3 rounded-xl border-2 transition cursor-pointer {{ $filter->verifiedOnly ? 'bg-emerald-50/90 border-emerald-300 text-emerald-900' : 'bg-slate-50/70 border-slate-200 hover:border-emerald-200 text-slate-800' }}">
                                <input type="checkbox" name="verified_only" value="1" {{ $filter->verifiedOnly ? 'checked' : '' }} class="w-4 h-4 rounded-md border-slate-300 text-emerald-600 focus:ring-emerald-400">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    </span>
                                    <span class="text-xs font-bold">Verified Posters Only</span>
                                </div>
                            </label>
                        </div>

                        <!-- High Visibility CTA Button -->
                        <div class="pt-3">
                            <button 
                                type="submit" 
                                style="background-color: #F20B65 !important; color: #ffffff !important;" 
                                class="w-full py-3.5 px-4 rounded-2xl font-black text-sm uppercase tracking-wider flex items-center justify-center gap-2 shadow-lg shadow-[#F20B65]/30 hover:opacity-95 active:scale-[0.98] transition-all cursor-pointer"
                            >
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span>Apply Search Filters</span>
                            </button>
                        </div>
                    </form>

                    <!-- Sidebar Promotional Ad Banner -->
                    <div class="w-full">
                        <x-ad-banner placement="sidebar" :context="$adContext ?? []" variant="sidebar" :limit="2" />
                    </div>
                </aside>

                <!-- Listing Cards Stream -->
                <main class="lg:col-span-3 space-y-5">
                    <x-ad-banner placement="search_results" :context="$adContext ?? []" variant="leaderboard" :limit="1" />

                    @if($listings->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            @php
                                $srFeed = $searchFeedItems ?? $listings->map(fn ($l) => ['type' => 'listing', 'listing' => $l]);
                            @endphp
                            @foreach($srFeed as $feedItem)
                                @if(($feedItem['type'] ?? 'listing') === 'ad')
                                    <x-sponsored-listing-card :ad="$feedItem['ad']" />
                                @else
                                    <x-listing-card :listing="$feedItem['listing']" />
                                @endif
                            @endforeach
                        </div>

                        <!-- Pagination Links -->
                        <div class="mt-8">
                            {{ $listings->links() }}
                        </div>
                    @else
                        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center">
                            <x-empty-state
                                title="No Matching Classifieds"
                                description="No active listings match your selected search criteria. Try broadening your distance radius, removing specific filters, or checking nearby cities."
                                actionText="Reset All Filters"
                                :actionUrl="route('search.index')"
                            />
                        </div>
                    @endif
                </main>
            </div>
        </div>
    </div>
</x-layouts.app>
