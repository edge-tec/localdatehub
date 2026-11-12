<x-layouts.app title="Six-Country Marketplace Management — LocalDateHub Admin">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" data-admin-countries-index>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-[#F50057] border border-rose-500/20 uppercase tracking-wider">
                        Multi-Country Control
                    </span>
                    <span class="text-xs text-slate-500">United States (Primary) • UK • Canada • Australia • Ireland • New Zealand</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Six-Country Marketplace Management
                </h1>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <form action="{{ route('admin.countries.seed') }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-xs transition"
                    >
                        Sync / Seed 6-Country Geography
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($countries as $country)
                @php
                    $slug = strtolower($country->slug ?: $country->code);
                    $visitors = (int) ($visitorCounts[$country->code] ?? 0);
                    $isPrimary = strtoupper($country->code) === 'US';
                @endphp
                <div class="rounded-2xl bg-white border {{ $isPrimary ? 'border-[#F50057]/50 ring-1 ring-[#F50057]/20' : 'border-slate-200/90' }} p-6 shadow-xs flex flex-col justify-between space-y-5" data-admin-country-card="{{ $country->code }}">
                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl">{{ $country->flag }}</span>
                                <div>
                                    <h2 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                                        <span>{{ $country->name }} ({{ $country->code }})</span>
                                        @if($isPrimary)
                                            <span class="px-2 py-0.5 rounded-full bg-rose-50 border border-rose-200 text-[#F50057] text-[10px] font-black uppercase">Primary</span>
                                        @endif
                                    </h2>
                                    <div class="text-xs text-slate-500">
                                        Route: <code class="font-bold text-[#F50057]">/{{ $slug }}</code> • {{ $country->currency_code }} ({{ $country->currency_symbol }})
                                    </div>
                                </div>
                            </div>

                            @if($country->is_active && $country->is_marketplace_enabled)
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold">
                                    ENABLED
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-slate-600 text-[11px] font-bold">
                                    DISABLED
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-3 gap-2 text-center">
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

                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="rounded-lg border border-slate-100 p-2">
                                <span class="block font-bold text-slate-800">{{ number_format((int) $country->pending_listings_count) }}</span>
                                <span class="text-[10px] text-slate-500">Pending</span>
                            </div>
                            <div class="rounded-lg border border-slate-100 p-2">
                                <span class="block font-bold text-slate-800">{{ number_format((int) $country->featured_listings_count) }}</span>
                                <span class="text-[10px] text-slate-500">Featured</span>
                            </div>
                            <div class="rounded-lg border border-slate-100 p-2">
                                <span class="block font-bold text-slate-800">{{ number_format($visitors) }}</span>
                                <span class="text-[10px] text-slate-500">Geo Visitors</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
                        <a
                            href="{{ route('country.home', ['countrySlug' => $slug]) }}"
                            target="_blank"
                            class="text-xs font-bold text-slate-600 hover:text-[#F50057]"
                        >
                            Open /{{ $slug }} ↗
                        </a>
                        <a
                            href="{{ route('admin.countries.show', ['countryCode' => strtolower($country->code)]) }}"
                            class="px-4 py-2 rounded-xl bg-[#F50057] hover:bg-[#d90858] text-white text-xs font-bold transition"
                        >
                            Manage {{ $country->code }} Marketplace &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>

