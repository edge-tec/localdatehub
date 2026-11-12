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
                LocalDateHub operates six dedicated country marketplaces, each with its own verified cities, administrative regions, local categories, and currency.
            </p>

            @if(!empty($detectedGeo['country_code']))
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700" data-detected-location>
                    <span>Detected Location: <strong>{{ $detectedGeo['country_name'] ?? $detectedGeo['country_code'] }} ({{ $detectedGeo['country_code'] }})</strong></span>
                    @if(!$detectedGeo['is_supported_marketplace'])
                        <span class="text-amber-700">• Please select one of our 6 supported country marketplaces below.</span>
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
                            <button
                                type="button"
                                onclick="window.ldhBrowseCountryLocations('{{ $country->code }}')"
                                class="rounded-xl bg-slate-50 hover:bg-rose-50 p-2.5 transition cursor-pointer"
                            >
                                <div class="text-base font-extrabold text-slate-900">{{ number_format((int) $country->areas_count) }}</div>
                                <div class="text-[10px] font-semibold uppercase text-slate-500">{{ \Illuminate\Support\Str::plural($country->division_label) }}</div>
                            </button>
                            <button
                                type="button"
                                onclick="window.ldhBrowseCountryLocations('{{ $country->code }}')"
                                class="rounded-xl bg-slate-50 hover:bg-rose-50 p-2.5 transition cursor-pointer"
                            >
                                <div class="text-base font-extrabold text-slate-900">{{ number_format((int) $country->cities_count) }}</div>
                                <div class="text-[10px] font-semibold uppercase text-slate-500">Cities</div>
                            </button>
                            <div class="rounded-xl bg-rose-50/70 p-2.5">
                                <div class="text-base font-extrabold text-[#F50057]">{{ number_format((int) $country->active_listings_count) }}</div>
                                <div class="text-[10px] font-semibold uppercase text-slate-600">Active Ads</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
                        <button
                            type="button"
                            onclick="window.ldhBrowseCountryLocations('{{ $country->code }}')"
                            class="text-xs font-bold text-slate-600 hover:text-[#F50057] transition cursor-pointer"
                        >
                            Regions &amp; Cities &darr;
                        </button>
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

        {{-- Interactive All Regions & Cities Directory --}}
        <section id="ldh-locations-directory" class="rounded-3xl bg-white border border-slate-200/90 shadow-sm p-6 sm:p-8 space-y-6" data-locations-directory>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">
                        All Regions &amp; Cities Directory
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Select a country below to browse every verified state, province, county, or region and its cities.
                    </p>
                </div>
                <div class="w-full sm:w-72">
                    <input
                        type="search"
                        id="ldh-directory-filter"
                        placeholder="Filter regions or cities..."
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#F50057] focus:ring-2 focus:ring-[#F50057]/15 outline-none"
                    />
                </div>
            </div>

            <div class="flex flex-wrap gap-2" id="ldh-directory-tabs">
                @foreach($countries as $country)
                    <button
                        type="button"
                        data-dir-country="{{ $country->code }}"
                        onclick="window.ldhBrowseCountryLocations('{{ $country->code }}', false)"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border text-xs font-bold transition cursor-pointer {{ $loop->first ? 'bg-[#F50057] text-white border-[#F50057]' : 'bg-slate-50 text-slate-700 border-slate-200 hover:border-[#F50057]/50' }}"
                    >
                        <span>{{ $country->flag }}</span>
                        <span>{{ $country->name }}</span>
                        <span class="opacity-80">({{ number_format((int) $country->areas_count) }})</span>
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-5 rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500" id="ldh-areas-heading">Regions / States</h3>
                        <span class="text-xs font-bold text-slate-500" id="ldh-areas-count"></span>
                    </div>
                    <div id="ldh-areas-list" class="max-h-96 overflow-y-auto space-y-1.5 pr-1"></div>
                </div>

                <div class="lg:col-span-7 rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
                    <div class="flex items-center justify-between mb-3 gap-2">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500" id="ldh-cities-heading">Cities</h3>
                        <a id="ldh-area-open-link" href="#" class="hidden text-xs font-bold text-[#F50057] hover:underline">Open Region Page &rarr;</a>
                    </div>
                    <div id="ldh-cities-list" class="max-h-96 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 pr-1"></div>
                </div>
            </div>
        </section>
    </div>

    <script>
        (function () {
            const areasListEl = document.getElementById('ldh-areas-list');
            const citiesListEl = document.getElementById('ldh-cities-list');
            const areasHeadingEl = document.getElementById('ldh-areas-heading');
            const areasCountEl = document.getElementById('ldh-areas-count');
            const citiesHeadingEl = document.getElementById('ldh-cities-heading');
            const areaOpenLinkEl = document.getElementById('ldh-area-open-link');
            const filterInputEl = document.getElementById('ldh-directory-filter');
            const directorySectionEl = document.getElementById('ldh-locations-directory');

            let activeCountryCode = @json(optional($countries->first())->code ?? 'US');
            let cachedAreas = [];
            let cachedCities = [];
            let activeAreaSlug = null;

            function escapeHtml(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function updateTabStyles(code) {
                document.querySelectorAll('[data-dir-country]').forEach(btn => {
                    const isActive = btn.getAttribute('data-dir-country') === code;
                    btn.className = 'inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border text-xs font-bold transition cursor-pointer ' + (
                        isActive
                            ? 'bg-[#F50057] text-white border-[#F50057]'
                            : 'bg-slate-50 text-slate-700 border-slate-200 hover:border-[#F50057]/50'
                    );
                });
            }

            function renderAreas() {
                const q = (filterInputEl.value || '').trim().toLowerCase();
                const filtered = q
                    ? cachedAreas.filter(a => a.name.toLowerCase().includes(q) || (a.code || '').toLowerCase().includes(q))
                    : cachedAreas;

                areasCountEl.textContent = filtered.length + ' total';

                if (!filtered.length) {
                    areasListEl.innerHTML = '<div class="text-xs text-slate-500 p-3">No matching regions found.</div>';
                    return;
                }

                areasListEl.innerHTML = filtered.map(area => {
                    const isSelected = area.slug === activeAreaSlug;
                    return `<button type="button" data-area-slug="${escapeHtml(area.slug)}" class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-left text-xs font-bold transition cursor-pointer ${isSelected ? 'bg-[#F50057] text-white' : 'bg-white text-slate-700 border border-slate-200/80 hover:border-[#F50057]/50'}">
                        <span>${escapeHtml(area.name)}</span>
                        <span class="${isSelected ? 'text-white/90' : 'text-slate-400'}">${area.cities_count} cities</span>
                    </button>`;
                }).join('');

                areasListEl.querySelectorAll('[data-area-slug]').forEach(btn => {
                    btn.addEventListener('click', () => loadCities(activeCountryCode, btn.getAttribute('data-area-slug')));
                });
            }

            function renderCities() {
                const q = (filterInputEl.value || '').trim().toLowerCase();
                const filtered = q
                    ? cachedCities.filter(c => c.name.toLowerCase().includes(q))
                    : cachedCities;

                if (!filtered.length) {
                    citiesListEl.innerHTML = '<div class="col-span-full text-xs text-slate-500 p-3">No matching cities found.</div>';
                    return;
                }

                citiesListEl.innerHTML = filtered.map(city => `
                    <a href="${escapeHtml(city.url)}" class="flex items-center justify-between px-3 py-2 rounded-xl bg-white border border-slate-200/80 hover:border-[#F50057]/50 hover:text-[#F50057] text-xs font-semibold text-slate-700 transition">
                        <span class="truncate">${escapeHtml(city.name)}</span>
                        <span class="text-[10px] text-slate-400 ml-2 shrink-0">&rarr;</span>
                    </a>
                `).join('');
            }

            async function loadCities(countryCode, areaSlug) {
                activeAreaSlug = areaSlug;
                renderAreas();
                citiesListEl.innerHTML = '<div class="col-span-full text-xs text-slate-500 p-3">Loading cities...</div>';

                try {
                    const res = await fetch(`/api/locations/countries/${encodeURIComponent(countryCode)}/areas/${encodeURIComponent(areaSlug)}/cities`);
                    const json = await res.json();
                    cachedCities = Array.isArray(json.data) ? json.data : [];
                    const areaName = json.area?.name || areaSlug;
                    citiesHeadingEl.textContent = `${areaName} Cities (${cachedCities.length})`;
                    const countrySlug = json.country?.slug || countryCode.toLowerCase();
                    areaOpenLinkEl.href = `/${countrySlug}/${areaSlug}`;
                    areaOpenLinkEl.classList.remove('hidden');
                    renderCities();
                } catch (e) {
                    citiesListEl.innerHTML = '<div class="col-span-full text-xs text-rose-600 p-3">Unable to load cities.</div>';
                }
            }

            window.ldhBrowseCountryLocations = async function (countryCode, shouldScroll = true) {
                activeCountryCode = countryCode;
                updateTabStyles(countryCode);
                if (shouldScroll && directorySectionEl) {
                    directorySectionEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                areasListEl.innerHTML = '<div class="text-xs text-slate-500 p-3">Loading regions...</div>';
                citiesListEl.innerHTML = '<div class="col-span-full text-xs text-slate-500 p-3">Select a region to view cities...</div>';
                areaOpenLinkEl.classList.add('hidden');

                try {
                    const res = await fetch(`/api/locations/countries/${encodeURIComponent(countryCode)}/areas`);
                    const json = await res.json();
                    cachedAreas = Array.isArray(json.data) ? json.data : [];
                    const label = json.country?.division_label || 'Region';
                    areasHeadingEl.textContent = `${json.country?.name || countryCode} — ${label}s`;

                    if (cachedAreas.length) {
                        await loadCities(countryCode, cachedAreas[0].slug);
                    } else {
                        renderAreas();
                    }
                } catch (e) {
                    areasListEl.innerHTML = '<div class="text-xs text-rose-600 p-3">Unable to load regions.</div>';
                }
            };

            filterInputEl?.addEventListener('input', () => {
                renderAreas();
                renderCities();
            });

            if (activeCountryCode) {
                window.ldhBrowseCountryLocations(activeCountryCode, false);
            }
        })();
    </script>
@endsection

