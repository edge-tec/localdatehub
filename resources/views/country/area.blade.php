@extends('layouts.app')

@section('content')
    @include('country.partials.switcher-bar')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-8" data-country-area-page="{{ $area->code }}">
        {{-- Breadcrumbs --}}
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold text-slate-500 flex-wrap">
            <a href="{{ route('country.home', ['countrySlug' => $countrySlug]) }}" class="hover:text-[#F50057]">
                {{ $country->flag }} {{ $country->name }}
            </a>
            @if($area->parent)
                <span>/</span>
                <a href="{{ route('country.area', ['countrySlug' => $countrySlug, 'areaSlug' => $area->parent->slug]) }}" class="hover:text-[#F50057]">
                    {{ $area->parent->name }}
                </a>
            @endif
            <span>/</span>
            <span class="text-slate-900 font-bold">{{ $area->name }}</span>
        </nav>

        {{-- Area Hero Header --}}
        <section class="rounded-3xl bg-white border border-slate-200/90 p-6 sm:p-8 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-2 max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-[#F50057] text-xs font-bold">
                    <span>{{ $country->flag }}</span>
                    <span>{{ strtoupper($area->administrative_level ?? $country->division_label) }} IN {{ strtoupper($country->name) }}</span>
                    <span>•</span>
                    <span>{{ $country->currency_code }} ({{ $country->currency_symbol }})</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    {{ $area->name }}, {{ $country->name }} Classifieds &amp; Local Dating
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Browse {{ number_format((int) $area->active_listings_count) }} verified local classifieds across {{ $area->cities->count() }} cities in {{ $area->name }}, {{ $country->name }}.
                </p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <div class="rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3 text-center">
                    <div class="text-xl font-extrabold text-slate-900">{{ number_format((int) $area->cities->count()) }}</div>
                    <div class="text-[11px] font-semibold text-slate-500 uppercase">Cities</div>
                </div>
                <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-center">
                    <div class="text-xl font-extrabold text-[#F50057]">{{ number_format((int) $area->active_listings_count) }}</div>
                    <div class="text-[11px] font-semibold text-slate-600 uppercase">Active Ads</div>
                </div>
            </div>
        </section>

        {{-- Cascading Search Bar pre-populated with this Area --}}
        @include('country.partials.cascading-search', [
            'selectedArea' => $area,
            'areaCities' => $area->cities,
            'filters' => array_merge($filters ?? [], ['area' => $area->slug]),
        ])

        {{-- Child Counties (e.g., when viewing an Irish Province like Leinster or Munster) --}}
        @if($area->children && $area->children->isNotEmpty())
            <section class="space-y-3" data-area-children>
                <h2 class="text-lg font-extrabold text-slate-900">
                    Counties in {{ $area->name }} ({{ $country->name }})
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
                    @foreach($area->children as $childArea)
                        <a
                            href="{{ route('country.area', ['countrySlug' => $countrySlug, 'areaSlug' => $childArea->slug]) }}"
                            class="rounded-xl bg-white border border-slate-200 hover:border-[#F50057]/50 p-3 text-xs font-bold text-slate-800 hover:text-[#F50057] transition flex items-center justify-between"
                        >
                            <span class="truncate">{{ $childArea->name }}</span>
                            <span>&rarr;</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Cities in this Administrative Area --}}
        @if($area->cities->isNotEmpty())
            <section class="space-y-3" data-area-cities>
                <h2 class="text-lg font-extrabold text-slate-900">
                    Cities &amp; Towns in {{ $area->name }}, {{ $country->name }}
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
                    @foreach($area->cities as $cityItem)
                        <a
                            href="{{ route('country.city', ['countrySlug' => $countrySlug, 'areaSlug' => $area->slug, 'citySlug' => $cityItem->slug]) }}"
                            class="group rounded-xl bg-white border border-slate-200/90 hover:border-[#F50057]/50 p-3 shadow-2xs transition flex items-center justify-between gap-2"
                        >
                            <span class="text-xs sm:text-sm font-bold text-slate-800 group-hover:text-[#F50057] truncate">
                                {{ $cityItem->name }}
                            </span>
                            <span class="shrink-0 px-2 py-0.5 rounded-full bg-slate-100 group-hover:bg-rose-50 text-[11px] font-bold text-slate-600 group-hover:text-[#F50057]">
                                {{ (int) $cityItem->active_listings_count }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Active Listings in this Area --}}
        <section class="space-y-4" data-area-listings>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-lg sm:text-xl font-extrabold text-slate-900">
                    Active Listings in {{ $area->name }}, {{ $country->name }}
                </h2>
                <span class="text-xs font-semibold text-slate-500">
                    Showing {{ $listings->total() }} {{ Str::plural('listing', $listings->total()) }} in {{ $country->currency_code }} ({{ $country->currency_symbol }})
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
                        No active listings found in {{ $area->name }}, {{ $country->name }}.
                    </p>
                    <p class="text-xs text-slate-500">
                        Explore other {{ strtolower($country->division_label) }}s in {{ $country->name }} below.
                    </p>
                </div>
            @endif
        </section>
    </div>
@endsection

