@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16 space-y-10" data-choose-country-page>
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-rose-50 border border-rose-200 text-[#F50057] text-xs font-extrabold uppercase tracking-wider">
                🌐 Global Country Marketplace Selector
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Choose Your Country Marketplace
            </h1>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                LocalDateHub operates five dedicated country marketplaces, each with its own verified cities, administrative regions, local categories, and currency.
            </p>

            @if(!empty($detectedGeo['country_code']))
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700" data-detected-location>
                    <span>Detected Location: <strong>{{ $detectedGeo['country_name'] ?? $detectedGeo['country_code'] }} ({{ $detectedGeo['country_code'] }})</strong></span>
                    @if(!$detectedGeo['is_supported_marketplace'])
                        <span class="text-amber-700">• Please select one of our 5 supported country marketplaces below.</span>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($countries as $country)
                @php
                    $slug = strtolower($country->slug ?: $country->code);
                    $isSelected = strtoupper($selectedCountryCode ?? '') === strtoupper($country->code);
                @endphp
                <div
                    class="group rounded-3xl bg-white border {{ $isSelected ? 'border-[#F50057] ring-2 ring-[#F50057]/15' : 'border-slate-200/90 hover:border-[#F50057]/50' }} p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between"
                    data-country-card="{{ $country->code }}"
                >
                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="text-4xl leading-none">{{ $country->flag }}</span>
                                <div>
                                    <h2 class="text-xl font-extrabold text-slate-900 group-hover:text-[#F50057] transition">
                                        {{ $country->name }}
                                    </h2>
                                    <div class="text-xs font-semibold text-slate-500">
                                        ISO: {{ $country->code }} • /{{ $slug }}
                                    </div>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-extrabold">
                                {{ $country->currency_code }} ({{ $country->currency_symbol }})
                            </span>
                        </div>

                        <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 text-center">
                            <div class="rounded-xl bg-slate-50 p-2.5">
                                <div class="text-base font-extrabold text-slate-900">{{ number_format((int) $country->areas_count) }}</div>
                                <div class="text-[10px] font-semibold uppercase text-slate-500">{{ $country->division_label }}s</div>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-2.5">
                                <div class="text-base font-extrabold text-slate-900">{{ number_format((int) $country->cities_count) }}</div>
                                <div class="text-[10px] font-semibold uppercase text-slate-500">Cities</div>
                            </div>
                            <div class="rounded-xl bg-rose-50/70 p-2.5">
                                <div class="text-base font-extrabold text-[#F50057]">{{ number_format((int) $country->active_listings_count) }}</div>
                                <div class="text-[10px] font-semibold uppercase text-slate-600">Active Ads</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                        <a
                            href="{{ route('country.home', ['countrySlug' => $slug]) }}"
                            class="text-xs font-bold text-slate-600 hover:text-slate-900"
                        >
                            Preview /{{ $slug }}
                        </a>
                        <a
                            href="{{ route('country.switch', ['countrySlug' => $slug]) }}"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-[#F50057] hover:bg-[#d90858] text-white text-xs font-bold shadow-xs transition"
                            data-select-country="{{ $country->code }}"
                        >
                            <span>Enter {{ $country->name }}</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

