@extends('layouts.app')

@section('content')
    @include('country.partials.switcher-bar')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-10" data-country-marketplace="{{ $country->code }}">
        {{-- 1. Country Hero Header & Local Statistics --}}
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-[#1E1B3A] to-rose-950 text-white p-6 sm:p-10 shadow-lg">
            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="space-y-3 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-xs font-bold tracking-wide">
                        <span class="text-base">{{ $country->flag }}</span>
                        <span>OFFICIAL {{ strtoupper($country->name) }} MARKETPLACE</span>
                        <span class="text-rose-300">•</span>
                        <span class="text-emerald-300">{{ $country->currency_code }} ({{ $country->currency_symbol }})</span>
                    </div>

                    <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                        {{ $country->name }} Classifieds &amp; Local Dating Marketplace
                    </h1>

                    <p class="text-sm sm:text-base text-slate-200/90 leading-relaxed">
                        Browse verified local personals, community classifieds, and connections across all {{ $stats['total_areas'] }} {{ strtolower($country->division_label ?: 'regions') }}s and {{ $stats['total_cities'] }} cities in {{ $country->name }}.
                        @if($country->code === 'IE')
                            Dedicated exclusively to the 26 counties of the Republic of Ireland ({{ $country->currency_code }} {{ $country->currency_symbol }}).
                        @elseif($country->code === 'GB')
                            Covering England, Scotland, Wales, and Northern Ireland ({{ $country->currency_code }} {{ $country->currency_symbol }}).
                        @endif
                    </p>

                    <div class="pt-2 flex flex-wrap items-center gap-3">
                        <a
                            href="{{ route('country.search', ['countrySlug' => $countrySlug]) }}"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#F50057] hover:bg-[#d90858] text-white text-sm font-bold shadow-sm transition"
                        >
                            <span>Browse All {{ $country->name }} Listings</span>
                        </a>
                        @auth
                            <a
                                href="{{ route('listings.create', ['country' => $country->code]) }}"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 border border-white/20 text-white text-sm font-bold transition"
                            >
                                <span>+ Post {{ $country->name }} Ad ({{ $country->currency_symbol }})</span>
                            </a>
                        @else
                            <a
                                href="{{ route('login') }}"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 border border-white/20 text-white text-sm font-bold transition"
                            >
                                <span>Post Free Ad in {{ $country->name }}</span>
                            </a>
                        @endauth
                    </div>
                </div>

                {{-- 7. Local Country Statistics Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-2 gap-3 shrink-0 lg:w-80" data-country-stats>
                    <div class="rounded-2xl bg-white/10 border border-white/15 p-4 backdrop-blur-xs">
                        <div class="text-2xl font-extrabold text-white">{{ number_format($stats['active_listings']) }}</div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-300 mt-0.5">{{ $country->code }} Active Ads</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 border border-white/15 p-4 backdrop-blur-xs">
                        <div class="text-2xl font-extrabold text-emerald-300">{{ $stats['currency_symbol'] }} {{ $stats['currency_code'] }}</div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-300 mt-0.5">Local Currency</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 border border-white/15 p-4 backdrop-blur-xs">
                        <div class="text-2xl font-extrabold text-white">{{ number_format($stats['total_areas']) }}</div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-300 mt-0.5">{{ $country->division_label }}s</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 border border-white/15 p-4 backdrop-blur-xs">
                        <div class="text-2xl font-extrabold text-white">{{ number_format($stats['total_cities']) }}</div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-300 mt-0.5">Verified Cities</div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 2. Country-Specific Cascading Search Bar --}}
        <section aria-label="{{ $country->name }} Marketplace Search">
            @include('country.partials.cascading-search', [
                'areaCities' => $popularCities,
                'filters' => [],
            ])
        </section>

        {{-- 3. Popular Cities in Selected Country --}}
        <section class="space-y-4" data-country-popular-cities>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-slate-900">
                        Popular Cities in {{ $country->name }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500">
                        Explore high-activity metropolitan hubs and towns across {{ $country->name }}
                    </p>
                </div>
                <a href="#browse-by-area" class="text-xs font-bold text-[#F50057] hover:underline">
                    View all {{ $stats['total_cities'] }} cities &rarr;
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-8 gap-2.5">
                @foreach($popularCities as $popCity)
                    @if($popCity->state)
                        <a
                            href="{{ route('country.city', ['countrySlug' => $countrySlug, 'areaSlug' => $popCity->state->slug, 'citySlug' => $popCity->slug]) }}"
                            class="group rounded-xl bg-white border border-slate-200/90 hover:border-[#F50057]/50 p-3 shadow-2xs hover:shadow-sm transition flex flex-col justify-between"
                        >
                            <div>
                                <div class="text-sm font-bold text-slate-900 group-hover:text-[#F50057] truncate">
                                    {{ $popCity->name }}
                                </div>
                                <div class="text-[11px] text-slate-500 truncate">
                                    {{ $popCity->state->name }}
                                </div>
                            </div>
                            <div class="mt-2 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                <span class="font-semibold text-slate-600">{{ number_format((int) $popCity->active_listings_count) }} ads</span>
                                <span class="text-[#F50057] font-bold">&rarr;</span>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        </section>

        {{-- 4. Active Categories with Country-Specific Listing Counts --}}
        <section class="space-y-4" data-country-categories>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-slate-900">
                        {{ $country->name }} Marketplace Categories
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500">
                        Category counts reflect active listings strictly within {{ $country->name }} ({{ $country->code }})
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($categories as $cat)
                    <div class="rounded-2xl bg-white border border-slate-200/90 p-4 shadow-2xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <a
                                    href="{{ route('country.search', ['countrySlug' => $countrySlug, 'category' => $cat->slug]) }}"
                                    class="text-base font-extrabold text-slate-900 hover:text-[#F50057] transition"
                                >
                                    {{ $cat->name }}
                                </a>
                                <span class="px-2.5 py-0.5 rounded-full bg-rose-50 text-[#F50057] border border-rose-200 text-xs font-extrabold">
                                    {{ number_format((int) $cat->country_listings_count) }}
                                </span>
                            </div>

                            @if($cat->children->isNotEmpty())
                                <ul class="space-y-1 mt-2">
                                    @foreach($cat->children->take(5) as $subcat)
                                        <li class="flex items-center justify-between text-xs">
                                            <a
                                                href="{{ route('country.search', ['countrySlug' => $countrySlug, 'category' => $subcat->slug]) }}"
                                                class="text-slate-600 hover:text-[#F50057] truncate"
                                            >
                                                {{ $subcat->name }}
                                            </a>
                                            <span class="text-slate-400 font-semibold">{{ (int) $subcat->country_listings_count }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-slate-100">
                            <a
                                href="{{ route('country.search', ['countrySlug' => $countrySlug, 'category' => $cat->slug]) }}"
                                class="text-xs font-bold text-[#F50057] hover:underline"
                            >
                                Explore {{ $cat->name }} in {{ $country->name }} &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- 5. Featured Listings from Selected Country --}}
        <section class="space-y-4" data-country-featured-listings>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-slate-900">
                        Featured Listings in {{ $country->name }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500">
                        Highlighted verified listings across {{ $country->name }} • Priced in {{ $country->currency_code }} ({{ $country->currency_symbol }})
                    </p>
                </div>
                <a
                    href="{{ route('country.search', ['countrySlug' => $countrySlug, 'sort' => 'featured']) }}"
                    class="text-xs font-bold text-[#F50057] hover:underline"
                >
                    See all featured &rarr;
                </a>
            </div>

            @if($featuredListings->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($featuredListings as $idx => $listing)
                        <x-listing-card :listing="$listing" variant="featured" :eager="$idx < 3" />
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl bg-white border border-slate-200/80 p-8 text-center">
                    <p class="text-sm font-semibold text-slate-700">
                        No featured listings have been posted in {{ $country->name }} yet.
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        Be the first to feature your listing across {{ $country->name }} ({{ $country->currency_code }} {{ $country->currency_symbol }}).
                    </p>
                </div>
            @endif
        </section>

        {{-- 6. Latest Listings from Selected Country --}}
        <section class="space-y-4" data-country-latest-listings>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-slate-900">
                        Latest Listings in {{ $country->name }}
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500">
                        Real-time stream of approved ads posted in {{ $country->name }}
                    </p>
                </div>
                <a
                    href="{{ route('country.search', ['countrySlug' => $countrySlug]) }}"
                    class="text-xs font-bold text-[#F50057] hover:underline"
                >
                    View all {{ number_format($stats['active_listings']) }} listings &rarr;
                </a>
            </div>

            @if($latestListings->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                    @foreach($latestListings as $listing)
                        <x-listing-card :listing="$listing" />
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl bg-white border border-slate-200/80 p-8 text-center">
                    <p class="text-sm font-semibold text-slate-700">
                        No active listings in {{ $country->name }} right now.
                    </p>
                </div>
            @endif
        </section>

        {{-- 8 & 9. Browse by State / Province / Region / County & City Directory --}}
        <section id="browse-by-area" class="space-y-4" data-country-areas-directory>
            <div>
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900">
                    Browse {{ $country->name }} by {{ $country->division_label }} &amp; City
                </h2>
                <p class="text-xs sm:text-sm text-slate-500">
                    Select any {{ strtolower($country->division_label ?: 'area') }} in {{ $country->name }} to view local cities and classified ads
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5">
                @foreach($areas as $areaItem)
                    <a
                        href="{{ route('country.area', ['countrySlug' => $countrySlug, 'areaSlug' => $areaItem->slug]) }}"
                        class="group rounded-2xl bg-white border border-slate-200/90 hover:border-[#F50057]/50 p-4 shadow-2xs hover:shadow-sm transition flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <div class="text-sm font-extrabold text-slate-900 group-hover:text-[#F50057] truncate">
                                {{ $areaItem->name }}
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                                <span class="uppercase font-semibold">{{ $areaItem->administrative_level ?? $country->division_label }}</span>
                                @if($areaItem->parent)
                                    <span>• {{ $areaItem->parent->name }}</span>
                                @endif
                                <span>• {{ (int) $areaItem->cities_count }} cities</span>
                            </div>
                        </div>
                        <span class="shrink-0 px-2.5 py-1 rounded-full bg-slate-100 group-hover:bg-rose-50 text-xs font-bold text-slate-700 group-hover:text-[#F50057]">
                            {{ number_format((int) $areaItem->active_listings_count) }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- 10. Country-Specific Footer & Sitemap Links --}}
        <section class="rounded-2xl bg-white border border-slate-200/90 p-5 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-600" data-country-footer>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-slate-900">{{ $country->flag }} {{ $country->name }} Marketplace ({{ $country->code }})</span>
                <span>•</span>
                <span>Currency: <strong>{{ $country->currency_code }} ({{ $country->currency_symbol }})</strong></span>
                <span>•</span>
                <span>Timezone: {{ $country->timezone }}</span>
            </div>
            <div class="flex items-center gap-4 flex-wrap">
                <a href="{{ route('country.search', ['countrySlug' => $countrySlug]) }}" class="font-semibold text-[#F50057] hover:underline">
                    {{ $country->name }} Search
                </a>
                <a href="{{ route('country.sitemap', ['countrySlug' => $countrySlug]) }}" class="font-semibold text-slate-700 hover:text-[#F50057] hover:underline">
                    {{ $country->code }} XML Sitemap
                </a>
                <a href="{{ route('country.choose') }}" class="font-semibold text-slate-700 hover:text-[#F50057] hover:underline">
                    Change Country Marketplace
                </a>
            </div>
        </section>
    </div>
@endsection

