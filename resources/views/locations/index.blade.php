<x-layouts.app title="United States Personal Classifieds Directory — All 50 States & DC">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Hero Header -->
        <div class="text-center max-w-3xl mx-auto mb-10">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-600 border border-rose-500/20 mb-3">
                USA Geographic Directory
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 tracking-tight">
                Browse Adult Personal Connections by Location
            </h1>
            <p class="text-base text-slate-400 mt-3">
                Discover genuine adult personal classifieds across all 50 US States, Washington DC, and metropolitan regions.
            </p>

            <!-- Search Autocomplete Widget -->
            <div class="mt-8 max-w-xl mx-auto">
                <livewire:location-autocomplete />
            </div>
        </div>

        <!-- Quick Links: Popular Metros -->
        <div class="mb-12 p-6 rounded-2xl bg-white border border-slate-200">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.316.492-.633.986-.95 1.48C8.583 5.485 7.8 6.74 7.02 8.012c-.287.468-.58 1.05-.66 1.62-.08.57.065 1.155.43 1.61.733.91 1.954 1.15 2.92.57l.29-.17c.07.45.19.89.37 1.31.39.9 1.06 1.63 1.91 2.06.85.43 1.83.5 2.76.2 1.86-.6 3.06-2.3 3.06-4.24 0-1.89-1.2-3.66-3.1-4.24-.76-.23-1.57-.22-2.32.03.11-.7.3-1.39.57-2.05.27-.67.62-1.3 1.05-1.88a1 1 0 00-.38-1.45l-.65-.36z" clip-rule="evenodd" />
                </svg>
                Popular Metropolitan Areas
            </h2>
            <div class="flex flex-wrap gap-2.5">
                @php
                    $popularMetros = [
                        ['state' => 'NY', 'city' => 'new-york-city', 'label' => 'New York City, NY'],
                        ['state' => 'CA', 'city' => 'los-angeles', 'label' => 'Los Angeles, CA'],
                        ['state' => 'IL', 'city' => 'chicago', 'label' => 'Chicago, IL'],
                        ['state' => 'TX', 'city' => 'houston', 'label' => 'Houston, TX'],
                        ['state' => 'TX', 'city' => 'austin', 'label' => 'Austin, TX'],
                        ['state' => 'TX', 'city' => 'dallas', 'label' => 'Dallas / DFW, TX'],
                        ['state' => 'FL', 'city' => 'miami', 'label' => 'Miami / SoBe, FL'],
                        ['state' => 'WA', 'city' => 'seattle', 'label' => 'Seattle, WA'],
                        ['state' => 'CA', 'city' => 'san-francisco', 'label' => 'San Francisco Bay Area, CA'],
                        ['state' => 'GA', 'city' => 'atlanta', 'label' => 'Atlanta, GA'],
                        ['state' => 'NV', 'city' => 'las-vegas', 'label' => 'Las Vegas, NV'],
                        ['state' => 'CO', 'city' => 'denver', 'label' => 'Denver, CO'],
                        ['state' => 'DC', 'city' => 'washington-dc', 'label' => 'Washington, DC'],
                        ['state' => 'MA', 'city' => 'boston', 'label' => 'Boston, MA'],
                    ];
                @endphp
                @foreach($popularMetros as $m)
                    <a href="{{ route('locations.city', ['stateCode' => $m['state'], 'citySlug' => $m['city']]) }}"
                        class="px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600 hover:text-slate-900 hover:border-rose-500/50 hover:bg-slate-100 transition shadow-sm">
                        {{ $m['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- States Grid -->
        <div>
            <h2 class="text-xl font-bold text-slate-900 mb-6">All 50 US States & District of Columbia</h2>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach($states as $st)
                    <a href="{{ route('locations.state', ['stateCode' => $st->code]) }}"
                        class="group p-4 rounded-xl bg-white border border-slate-200 hover:border-rose-500/50 hover:bg-slate-850 transition flex flex-col justify-between shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-rose-600 group-hover:text-rose-600 font-mono">{{ $st->code }}</span>
                            <span class="text-[11px] text-slate-500">{{ $st->cities_count }} cities</span>
                        </div>
                        <div class="text-sm font-semibold text-slate-800 group-hover:text-white truncate">
                            {{ $st->name }}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>

