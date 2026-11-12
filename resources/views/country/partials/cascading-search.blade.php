@php
    $activeSlug = strtolower($countrySlug ?? ($country->slug ?? $country->code ?? 'gb'));
    $divisionLabel = $country->division_label ?? 'State / Region';
    $initialAreaSlug = $filters['area'] ?? ($selectedArea->slug ?? ($area->slug ?? ''));
    $initialCityId = (string) ($filters['city_id'] ?? ($city->id ?? ''));
    $initialCategory = (string) ($filters['category'] ?? ($activeCategory->slug ?? ''));
    $initialKeyword = (string) ($filters['q'] ?? '');
    $initialSort = (string) ($filters['sort'] ?? 'latest');
    $initialMinPrice = (string) ($filters['min_price'] ?? '');
    $initialMaxPrice = (string) ($filters['max_price'] ?? '');
@endphp

<div
    x-data="{
        countryCode: '{{ $country->code }}',
        countrySlug: '{{ $activeSlug }}',
        selectedArea: '{{ $initialAreaSlug }}',
        selectedCityId: '{{ $initialCityId }}',
        cities: @js(($areaCities ?? collect())->map(fn($c) => ['id' => (string) $c->id, 'name' => $c->name, 'slug' => $c->slug])->values()),
        loadingCities: false,
        async onAreaChange() {
            this.selectedCityId = '';
            if (!this.selectedArea) {
                this.cities = [];
                return;
            }
            this.loadingCities = true;
            try {
                const res = await fetch(`/api/locations/countries/${this.countryCode}/areas/${this.selectedArea}/cities`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const payload = await res.json();
                    this.cities = (payload.data || []).map(c => ({
                        id: String(c.id),
                        name: c.name,
                        slug: c.slug
                    }));
                }
            } catch (e) {
                console.error('Failed loading cities:', e);
            } finally {
                this.loadingCities = false;
            }
        }
    }"
    x-init="if (selectedArea && cities.length === 0) { onAreaChange().then(() => { selectedCityId = '{{ $initialCityId }}'; }); }"
    class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-6 shadow-sm"
    data-country-search-form
>
    <form action="{{ route('country.search', ['countrySlug' => $activeSlug]) }}" method="GET" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            {{-- Keyword Search --}}
            <div class="lg:col-span-2">
                <label for="country-search-q" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">
                    Search in {{ $country->name }}
                </label>
                <input
                    id="country-search-q"
                    type="text"
                    name="q"
                    value="{{ $initialKeyword }}"
                    placeholder="Search {{ $country->name }} listings, keywords, neighbourhoods..."
                    class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3.5 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-[#F50057] focus:outline-none"
                >
            </div>

            {{-- Administrative Area Dropdown (State / Province / Region / County / Nation) --}}
            <div>
                <label for="country-search-area" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">
                    {{ $divisionLabel }}
                </label>
                <select
                    id="country-search-area"
                    name="area"
                    x-model="selectedArea"
                    @change="onAreaChange()"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-[#F50057] focus:outline-none"
                >
                    <option value="">All {{ $country->name }} Areas</option>
                    @foreach(($allAreas ?? $areas ?? collect()) as $areaItem)
                        <option value="{{ $areaItem->slug }}">
                            {{ $areaItem->name }} ({{ $areaItem->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Dynamic Cascading City Dropdown --}}
            <div>
                <label for="country-search-city" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">
                    City / Town
                </label>
                <select
                    id="country-search-city"
                    name="city_id"
                    x-model="selectedCityId"
                    :disabled="loadingCities"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-[#F50057] focus:outline-none disabled:opacity-60"
                >
                    <option value="" x-text="loadingCities ? 'Loading cities...' : (selectedArea ? 'All Cities in Area' : 'Select Area or City')">All Cities</option>
                    <template x-for="cityItem in cities" :key="cityItem.id">
                        <option :value="cityItem.id" x-text="cityItem.name" :selected="cityItem.id === selectedCityId"></option>
                    </template>
                </select>
            </div>

            {{-- Category Dropdown --}}
            <div>
                <label for="country-search-category" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">
                    Category
                </label>
                <select
                    id="country-search-category"
                    name="category"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-[#F50057] focus:outline-none"
                >
                    <option value="">All Categories</option>
                    @foreach(($categories ?? collect()) as $cat)
                        <option value="{{ $cat->slug }}" @selected($initialCategory === $cat->slug)>
                            {{ $cat->name }} ({{ (int) ($cat->country_listings_count ?? 0) }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Secondary Row: Currency Price Filter, Sort & Submit --}}
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="font-bold text-slate-600">
                    Price ({{ $country->currency_code }} {{ $country->currency_symbol }}):
                </span>
                <input
                    type="number"
                    name="min_price"
                    value="{{ $initialMinPrice }}"
                    min="0"
                    step="1"
                    placeholder="Min {{ $country->currency_symbol }}"
                    class="w-24 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs text-slate-800"
                >
                <span class="text-slate-400">–</span>
                <input
                    type="number"
                    name="max_price"
                    value="{{ $initialMaxPrice }}"
                    min="0"
                    step="1"
                    placeholder="Max {{ $country->currency_symbol }}"
                    class="w-24 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs text-slate-800"
                >

                <label for="country-search-sort" class="font-bold text-slate-600 ml-2">Sort:</label>
                <select
                    id="country-search-sort"
                    name="sort"
                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-800"
                >
                    <option value="latest" @selected($initialSort === 'latest')>Latest First</option>
                    <option value="featured" @selected($initialSort === 'featured')>Featured First</option>
                    <option value="popular" @selected($initialSort === 'popular')>Most Viewed</option>
                    <option value="price_asc" @selected($initialSort === 'price_asc')>Price: Low to High ({{ $country->currency_symbol }})</option>
                    <option value="price_desc" @selected($initialSort === 'price_desc')>Price: High to Low ({{ $country->currency_symbol }})</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="{{ route('country.home', ['countrySlug' => $activeSlug]) }}"
                    class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-600 transition"
                >
                    Reset
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-[#F50057] hover:bg-[#d90858] text-white text-xs sm:text-sm font-bold shadow-xs transition"
                >
                    <span>Search {{ $country->name }}</span>
                </button>
            </div>
        </div>
    </form>
</div>

