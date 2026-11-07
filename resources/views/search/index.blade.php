<x-layouts.app title="Search Personals & Classifieds — LocalDateHub">
    <div class="py-10 bg-slate-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Search Classifieds']
            ]" />

            <!-- Page Header -->
            <div class="mt-6 flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-800">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Search Personals & Connections
                    </h1>
                    <p class="text-xs text-slate-400 mt-1">
                        Found <span class="font-bold text-white">{{ $listings->total() }}</span> active {{ Str::plural('listing', $listings->total()) }} matching your criteria
                    </p>
                </div>

                <!-- Sort Control -->
                <form method="GET" action="{{ route('search.index') }}" class="flex items-center gap-2">
                    @foreach(request()->except('sort', 'page') as $key => $val)
                        @if(is_array($val))
                            @foreach($val as $subVal)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $subVal }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                        @endif
                    @endforeach
                    <label for="sort" class="text-xs text-slate-400 whitespace-nowrap">Sort by:</label>
                    <select id="sort" name="sort" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 text-xs text-white rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-rose-500">
                        <option value="latest" {{ $filter->sort === 'latest' ? 'selected' : '' }}>Newest Published</option>
                        <option value="views" {{ $filter->sort === 'views' ? 'selected' : '' }}>Most Viewed</option>
                        <option value="oldest" {{ $filter->sort === 'oldest' ? 'selected' : '' }}>Oldest</option>
                    </select>
                </form>
            </div>

            <div class="mt-8 grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Filter Sidebar -->
                <aside class="lg:col-span-1">
                    <form method="GET" action="{{ route('search.index') }}" class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-6">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200">Refine Search</h2>
                            <a href="{{ route('search.index') }}" class="text-xs text-rose-400 hover:text-rose-300">Reset</a>
                        </div>

                        <!-- Keywords -->
                        <div>
                            <x-input
                                name="q"
                                label="Keyword / Topic"
                                placeholder="e.g. coffee, hiking, artist"
                                :value="$filter->query"
                            />
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                                Category
                            </label>
                            <select id="category" name="category" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500">
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

                        <!-- Location State -->
                        <div>
                            <label for="state" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                                US State
                            </label>
                            <select id="state" name="state" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500">
                                <option value="">Any State</option>
                                @foreach($states as $st)
                                    <option value="{{ $st->code }}" {{ $filter->stateCode === $st->code ? 'selected' : '' }}>
                                        {{ $st->name }} ({{ $st->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- City Name -->
                        <div>
                            <x-input
                                name="city"
                                label="City Name"
                                placeholder="e.g. Austin, Seattle, Miami"
                                :value="$filter->cityName"
                            />
                        </div>

                        <!-- Radius -->
                        <div>
                            <label for="radius" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                                Distance Radius
                            </label>
                            <select id="radius" name="radius" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500">
                                <option value="10" {{ $filter->radiusKm == 10 ? 'selected' : '' }}>Within 10 km</option>
                                <option value="25" {{ $filter->radiusKm == 25 ? 'selected' : '' }}>Within 25 km</option>
                                <option value="50" {{ $filter->radiusKm == 50 ? 'selected' : '' }}>Within 50 km</option>
                                <option value="100" {{ $filter->radiusKm == 100 ? 'selected' : '' }}>Within 100 km</option>
                            </select>
                        </div>

                        <!-- Age Range -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                                Age Range
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" name="min_age" min="18" max="99" value="{{ $filter->minAge }}" placeholder="Min" class="bg-slate-950 border border-slate-700 text-xs text-white rounded-lg px-2.5 py-1.5">
                                <input type="number" name="max_age" min="18" max="99" value="{{ $filter->maxAge }}" placeholder="Max" class="bg-slate-950 border border-slate-700 text-xs text-white rounded-lg px-2.5 py-1.5">
                            </div>
                        </div>

                        <!-- Checkbox Flags -->
                        <div class="space-y-2 pt-2 border-t border-slate-800">
                            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                                <input type="checkbox" name="with_photos" value="1" {{ $filter->withPhotosOnly ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-950 text-rose-600 focus:ring-rose-500">
                                <span>With Photos Only</span>
                            </label>

                            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                                <input type="checkbox" name="verified_only" value="1" {{ $filter->verifiedOnly ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-950 text-rose-600 focus:ring-rose-500">
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

                        <!-- Pagination -->
                        <div class="mt-8">
                            {{ $listings->links() }}
                        </div>
                    @else
                        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-12 text-center">
                            <x-empty-state
                                title="No Classifieds Found"
                                description="We couldn't find any listings matching your active filters. Try expanding your search radius, selecting all categories, or clearing keyword terms."
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
