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
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                            Search Personals & Classifieds
                        </h1>
                        <span class="text-[10px] uppercase font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-400 border border-slate-200">
                            Engine: {{ strtoupper($activeDriver) }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        Found <span class="font-bold text-slate-900">{{ $listings->total() }}</span> active {{ Str::plural('listing', $listings->total()) }} matching your criteria
                    </p>
                </div>

                <!-- Sort Control -->
                <form method="GET" action="{{ route('search.index') }}" class="flex items-center gap-2">
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
                    <label for="sort" class="text-xs text-slate-400 whitespace-nowrap">Sort by:</label>
                    <select id="sort" name="sort" onchange="this.form.submit()" class="bg-white border border-slate-200 text-xs text-slate-900 rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-rose-500">
                        <option value="newest" {{ $filter->sort === 'newest' ? 'selected' : '' }}>Newest Published</option>
                        <option value="updated" {{ $filter->sort === 'updated' ? 'selected' : '' }}>Recently Updated</option>
                        <option value="views" {{ $filter->sort === 'views' ? 'selected' : '' }}>Most Viewed</option>
                        <option value="oldest" {{ $filter->sort === 'oldest' ? 'selected' : '' }}>Oldest</option>
                    </select>
                </form>
            </div>

            <!-- Category Facet Pills -->
            @if(!empty($categoryCounts))
                <div class="mt-6 flex flex-wrap gap-2 items-center">
                    <span class="text-xs text-slate-400 font-medium mr-1">Filter Category:</span>
                    <a href="{{ route('search.index', array_merge($filter->toQueryArray(), ['category' => null, 'subcategory' => null])) }}"
                       class="px-2.5 py-1 rounded-lg text-xs font-medium transition {{ empty($filter->categorySlug) ? 'bg-rose-600 text-white font-semibold' : 'bg-white text-slate-600 hover:bg-slate-100' }}">
                        All ({{ array_sum($categoryCounts) }})
                    </a>
                    @foreach($categories as $cat)
                        @php $count = $categoryCounts[$cat->slug] ?? 0; @endphp
                        @if($count > 0 || $filter->categorySlug === $cat->slug)
                            <a href="{{ route('search.index', array_merge($filter->toQueryArray(), ['category' => $cat->slug, 'subcategory' => null])) }}"
                               class="px-2.5 py-1 rounded-lg text-xs font-medium transition flex items-center gap-1.5 {{ $filter->categorySlug === $cat->slug ? 'bg-rose-600 text-white font-semibold' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                                <span>{{ $cat->name }}</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter->categorySlug === $cat->slug ? 'bg-rose-700 text-white' : 'bg-slate-100 text-slate-400' }}">
                                    {{ $count }}
                                </span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            <!-- Transparent Ranking Information Banner -->
            <div class="mt-4 p-3 bg-white border border-slate-200 rounded-xl text-xs text-slate-400 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-700 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span><strong>Transparent Ranking Policy:</strong> Featured ads (City/Metro Spotlight) are positioned prominently at the top; results then strictly follow your selected sort order.</span>
                </div>
                @if($filter->featuredOnly)
                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-500/10 text-amber-700 border border-amber-500/20">
                        Featured Filter Active
                    </span>
                @endif
            </div>

            <div class="mt-8 grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Filter Sidebar -->
                <aside class="lg:col-span-1">
                    <form method="GET" action="{{ route('search.index') }}" class="bg-white border border-slate-200 rounded-2xl p-5 space-y-5 sticky top-20">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">Refine Search</h2>
                            <a href="{{ route('search.index') }}" class="text-xs text-rose-600 hover:text-rose-600">Reset All</a>
                        </div>

                        <!-- Keyword -->
                        <div>
                            <x-input
                                name="q"
                                label="Keyword / Topic"
                                placeholder="e.g. coffee, hiking, artist"
                                :value="$filter->query"
                            />
                        </div>

                        <!-- Category Hierarchy -->
                        <div>
                            <label for="category" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Category
                            </label>
                            <select id="category" name="category" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:ring-2 focus:ring-rose-500">
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
                        <div>
                            <x-input
                                name="subcategory"
                                label="Subcategory Slug"
                                placeholder="e.g. casual-dating"
                                :value="$filter->subcategorySlug"
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
                                    }
                                } catch(e) {}
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
                            <div>
                                <label for="state" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    US State
                                </label>
                                <select 
                                    id="state" 
                                    name="state" 
                                    x-model="selectedState" 
                                    @change="onStateChange($event)"
                                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-[#F20B83]/20 focus:border-[#F20B83] cursor-pointer"
                                >
                                    <option value="">Any State</option>
                                    @foreach($states as $st)
                                        <option value="{{ $st->code }}" {{ $filter->stateCode === $st->code ? 'selected' : '' }}>
                                            {{ $st->name }} ({{ $st->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Location: City with dynamic suggestions -->
                            <div class="relative" @click.outside="cityDropdownOpen = false">
                                <label for="city-input" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5 flex items-center justify-between">
                                    <span>City Name</span>
                                    <span x-show="loadingCities" class="text-[10px] text-[#F20B83] font-normal animate-pulse">Loading cities...</span>
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
                                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:border-[#F20B83] focus:ring-2 focus:ring-[#F20B83]/20 focus:outline-none transition"
                                        :class="{ 'opacity-60 cursor-not-allowed': !selectedState }"
                                        autocomplete="off"
                                    />
                                    <template x-if="cityInput">
                                        <button 
                                            type="button" 
                                            @click="cityInput = ''; selectedCity = '';" 
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs font-bold"
                                        >
                                            &times;
                                        </button>
                                    </template>
                                </div>

                                <!-- Dropdown List -->
                                <div 
                                    x-show="cityDropdownOpen && selectedState && cities.length > 0" 
                                    x-cloak
                                    class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl z-50 max-h-56 overflow-y-auto divide-y divide-slate-100 text-left p-1"
                                >
                                    <div class="p-1.5 flex items-center justify-between border-b border-slate-100 text-[10px] text-slate-500 font-bold bg-slate-50/70">
                                        <span x-text="cities.length + ' Cities in ' + selectedState"></span>
                                        <button type="button" @click="selectCity('')" class="text-[#F20B83] hover:underline">All Cities</button>
                                    </div>
                                    <template x-for="c in filteredCities" :key="c.id">
                                        <button 
                                            type="button" 
                                            @click="selectCity(c.name)" 
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-pink-50 hover:text-[#F20B83] rounded-lg flex items-center justify-between transition-colors"
                                            :class="{ 'bg-pink-50 text-[#F20B83] font-bold': selectedCity === c.name }"
                                        >
                                            <span x-text="c.name"></span>
                                            <template x-if="c.is_popular">
                                                <span class="text-[9px] px-1.5 py-0.5 bg-pink-100 text-[#F20B83] rounded font-bold uppercase">Popular</span>
                                            </template>
                                        </button>
                                    </template>
                                    <template x-if="filteredCities.length === 0">
                                        <div class="p-3 text-center text-xs text-slate-400 italic">No matching city in this state</div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Location: ZIP Code -->
                        <div>
                            <x-input
                                name="zip"
                                label="ZIP Code (5 Digits)"
                                placeholder="e.g. 78701, 90210"
                                :value="$filter->zipCode"
                            />
                        </div>

                        <!-- Radius -->
                        <div>
                            <label for="radius" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Distance Radius
                            </label>
                            <select id="radius" name="radius" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:ring-2 focus:ring-rose-500">
                                <option value="10" {{ $filter->radiusKm == 10 ? 'selected' : '' }}>Within 10 km</option>
                                <option value="25" {{ $filter->radiusKm == 25 ? 'selected' : '' }}>Within 25 km</option>
                                <option value="50" {{ $filter->radiusKm == 50 ? 'selected' : '' }}>Within 50 km</option>
                                <option value="100" {{ $filter->radiusKm == 100 ? 'selected' : '' }}>Within 100 km</option>
                                <option value="150" {{ $filter->radiusKm == 150 ? 'selected' : '' }}>Within 150 km</option>
                            </select>
                        </div>

                        <!-- Attribute: Relationship Intent -->
                        <div>
                            <label for="intent" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Relationship Intent
                            </label>
                            <select id="intent" name="attr[intent]" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:ring-2 focus:ring-rose-500">
                                <option value="">Any Intent</option>
                                <option value="dating" {{ ($filter->attributes['intent'] ?? '') === 'dating' ? 'selected' : '' }}>Dating & Romance</option>
                                <option value="activity_partner" {{ ($filter->attributes['intent'] ?? '') === 'activity_partner' ? 'selected' : '' }}>Activity Partner</option>
                                <option value="friendship" {{ ($filter->attributes['intent'] ?? '') === 'friendship' ? 'selected' : '' }}>Friendship & Social</option>
                                <option value="casual" {{ ($filter->attributes['intent'] ?? '') === 'casual' ? 'selected' : '' }}>Casual Connections</option>
                                <option value="long_term" {{ ($filter->attributes['intent'] ?? '') === 'long_term' ? 'selected' : '' }}>Long-term Relationship</option>
                            </select>
                        </div>

                        <!-- Published Date Timeframe -->
                        <div>
                            <label for="published_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Published Within
                            </label>
                            <select id="published_date" name="published_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:ring-2 focus:ring-rose-500">
                                <option value="all" {{ $filter->publishedDate === 'all' ? 'selected' : '' }}>All Time</option>
                                <option value="today" {{ $filter->publishedDate === 'today' ? 'selected' : '' }}>Today (Past 24 Hours)</option>
                                <option value="7d" {{ $filter->publishedDate === '7d' ? 'selected' : '' }}>Past 7 Days</option>
                                <option value="30d" {{ $filter->publishedDate === '30d' ? 'selected' : '' }}>Past 30 Days</option>
                            </select>
                        </div>

                        <!-- Age Range -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Poster Age Range
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" name="min_age" min="18" max="99" value="{{ $filter->minAge }}" placeholder="Min" class="bg-slate-50 border border-slate-200 text-xs text-slate-900 rounded-lg px-2.5 py-1.5">
                                <input type="number" name="max_age" min="18" max="99" value="{{ $filter->maxAge }}" placeholder="Max" class="bg-slate-50 border border-slate-200 text-xs text-slate-900 rounded-lg px-2.5 py-1.5">
                            </div>
                        </div>

                        <!-- Filter Checkboxes -->
                        <div class="space-y-2 pt-2 border-t border-slate-200">
                            <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                                <input type="checkbox" name="featured_only" value="1" {{ $filter->featuredOnly ? 'checked' : '' }} class="rounded border-slate-200 bg-slate-50 text-rose-600 focus:ring-rose-500">
                                <span class="font-medium text-amber-700">★ Featured Ads Only</span>
                            </label>

                            <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                                <input type="checkbox" name="with_photos" value="1" {{ $filter->withPhotosOnly ? 'checked' : '' }} class="rounded border-slate-200 bg-slate-50 text-rose-600 focus:ring-rose-500">
                                <span>With Photos Only</span>
                            </label>

                            <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                                <input type="checkbox" name="verified_only" value="1" {{ $filter->verifiedOnly ? 'checked' : '' }} class="rounded border-slate-200 bg-slate-50 text-rose-600 focus:ring-rose-500">
                                <span>Verified Posters Only</span>
                            </label>
                        </div>

                        <div class="pt-2">
                            <x-button type="submit" variant="primary" class="w-full py-2.5 text-xs">
                                Apply Search Filters
                            </x-button>
                        </div>
                    </form>
                </aside>

                <!-- Listing Cards Stream -->
                <main class="lg:col-span-3">
                    @if($listings->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            @foreach($listings as $listing)
                                <x-listing-card :listing="$listing" />
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
