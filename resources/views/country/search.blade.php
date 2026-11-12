@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-8" data-country-search-page="{{ $country->code }}">
        {{-- Breadcrumbs --}}
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold text-slate-500 flex-wrap">
            <a href="{{ route('country.home', ['countrySlug' => $countrySlug]) }}" class="hover:text-[#F50057]">
                {{ $country->flag }} {{ $country->name }}
            </a>
            <span>/</span>
            <span class="text-slate-900 font-bold">Search {{ $country->name }} Marketplace</span>
        </nav>

        <section class="space-y-2">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Search {{ $country->name }} Classifieds &amp; Local Dating
            </h1>
            <p class="text-xs sm:text-sm text-slate-600">
                Filter verified listings exclusively within {{ $country->name }} ({{ $country->code }}) • Prices in {{ $country->currency_code }} ({{ $country->currency_symbol }})
            </p>
        </section>

        {{-- Cascading Search Bar --}}
        @include('country.partials.cascading-search', [
            'allAreas' => $areas,
            'areaCities' => $areaCities,
            'filters' => $filters,
        ])

        {{-- Results Stream --}}
        <section class="space-y-4" data-country-search-results>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-base sm:text-lg font-extrabold text-slate-900">
                    {{ number_format((int) $listings->total()) }} {{ Str::plural('Listing', $listings->total()) }} Found in {{ $country->name }}
                </h2>
                <span class="text-xs font-semibold text-slate-500">
                    Currency: {{ $country->currency_code }} ({{ $country->currency_symbol }})
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
                        No listings in {{ $country->name }} matched your current filters.
                    </p>
                    <p class="text-xs text-slate-500">
                        <a href="{{ route('country.search', ['countrySlug' => $countrySlug]) }}" class="text-[#F50057] font-bold hover:underline">
                            Clear filters and browse all {{ $country->name }} listings &rarr;
                        </a>
                    </p>
                </div>
            @endif
        </section>
    </div>
@endsection

