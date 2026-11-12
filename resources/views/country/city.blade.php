@extends('layouts.app')

@section('content')
    @include('country.partials.switcher-bar')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-8" data-country-city-page="{{ $city->slug }}">
        {{-- Breadcrumbs --}}
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold text-slate-500 flex-wrap">
            <a href="{{ route('country.home', ['countrySlug' => $countrySlug]) }}" class="hover:text-[#F50057]">
                {{ $country->flag }} {{ $country->name }}
            </a>
            <span>/</span>
            <a href="{{ route('country.area', ['countrySlug' => $countrySlug, 'areaSlug' => $area->slug]) }}" class="hover:text-[#F50057]">
                {{ $area->name }}
            </a>
            <span>/</span>
            @if($activeCategory)
                <a href="{{ route('country.city', ['countrySlug' => $countrySlug, 'areaSlug' => $area->slug, 'citySlug' => $city->slug]) }}" class="hover:text-[#F50057]">
                    {{ $city->name }}
                </a>
                <span>/</span>
                <span class="text-slate-900 font-bold">{{ $activeCategory->name }}</span>
            @else
                <span class="text-slate-900 font-bold">{{ $city->name }}</span>
            @endif
        </nav>

        {{-- City Hero Header --}}
        <section class="rounded-3xl bg-white border border-slate-200/90 p-6 sm:p-8 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-2 max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-[#F50057] text-xs font-bold">
                    <span>{{ $country->flag }}</span>
                    <span>{{ strtoupper($city->name) }}, {{ strtoupper($area->name) }} ({{ $country->code }})</span>
                    <span>•</span>
                    <span>{{ $country->currency_code }} ({{ $country->currency_symbol }})</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    @if($activeCategory)
                        {{ $activeCategory->name }} in {{ $city->name }}, {{ $area->name }} ({{ $country->name }})
                    @else
                        {{ $city->name }}, {{ $area->name }} Classifieds &amp; Local Dating
                    @endif
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Browse verified local ads and personal connections in {{ $city->name }}, {{ $area->name }}, {{ $country->name }}. All listings in {{ $city->name }} use {{ $country->currency_code }} ({{ $country->currency_symbol }}).
                </p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-center">
                    <div class="text-xl font-extrabold text-[#F50057]">{{ number_format((int) $listings->total()) }}</div>
                    <div class="text-[11px] font-semibold text-slate-600 uppercase">Active Ads in {{ $city->name }}</div>
                </div>
            </div>
        </section>

        {{-- Category Filter Pills for this City --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            <a
                href="{{ route('country.city', ['countrySlug' => $countrySlug, 'areaSlug' => $area->slug, 'citySlug' => $city->slug]) }}"
                class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold border transition {{ !$activeCategory ? 'bg-[#F50057] border-[#F50057] text-white' : 'bg-white border-slate-200 text-slate-700 hover:border-[#F50057]' }}"
            >
                All {{ $city->name }} Categories
            </a>
            @foreach($categories as $cat)
                <a
                    href="{{ route('country.city.category', ['countrySlug' => $countrySlug, 'areaSlug' => $area->slug, 'citySlug' => $city->slug, 'categorySlug' => $cat->slug]) }}"
                    class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold border transition {{ ($activeCategory && $activeCategory->id === $cat->id) ? 'bg-[#F50057] border-[#F50057] text-white' : 'bg-white border-slate-200 text-slate-700 hover:border-[#F50057]' }}"
                >
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>

        {{-- Cascading Search Bar pre-populated with Area + City --}}
        @include('country.partials.cascading-search', [
            'allAreas' => collect([$area]),
            'selectedArea' => $area,
            'areaCities' => $area->cities,
            'filters' => array_merge($filters ?? [], [
                'area' => $area->slug,
                'city_id' => $city->id,
            ]),
        ])

        {{-- City Listings Grid --}}
        <section class="space-y-4" data-city-listings>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900">
                    Verified Listings in {{ $city->name }}, {{ $area->name }}
                </h2>
                <span class="text-xs font-semibold text-slate-500">
                    {{ $listings->total() }} {{ Str::plural('result', $listings->total()) }} • {{ $country->currency_code }} ({{ $country->currency_symbol }})
                </span>
            </div>

            @if($listings->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                    @foreach($listings as $listing)
                        <x-listing-card :listing="$listing" />
                    @endforeach
                </div>

                <div class="pt-4">
                    {{ $listings->links() }}
                </div>
            @else
                <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center space-y-2">
                    <p class="text-sm font-bold text-slate-800">
                        No active listings in {{ $city->name }}, {{ $area->name }} matching your filter.
                    </p>
                    <p class="text-xs text-slate-500">
                        <a href="{{ route('country.area', ['countrySlug' => $countrySlug, 'areaSlug' => $area->slug]) }}" class="text-[#F50057] font-bold hover:underline">
                            Browse all listings in {{ $area->name }} &rarr;
                        </a>
                    </p>
                </div>
            @endif
        </section>

        {{-- Other Cities in this Area --}}
        @if($area->cities->count() > 1)
            <section class="space-y-3 pt-4 border-t border-slate-200/80">
                <h2 class="text-base font-extrabold text-slate-900">
                    More Cities in {{ $area->name }}, {{ $country->name }}
                </h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($area->cities->where('id', '!=', $city->id)->take(18) as $otherCity)
                        <a
                            href="{{ route('country.city', ['countrySlug' => $countrySlug, 'areaSlug' => $area->slug, 'citySlug' => $otherCity->slug]) }}"
                            class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:border-[#F50057] text-xs font-semibold text-slate-700 hover:text-[#F50057] transition"
                        >
                            {{ $otherCity->name }} ({{ (int) $otherCity->active_listings_count }})
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
