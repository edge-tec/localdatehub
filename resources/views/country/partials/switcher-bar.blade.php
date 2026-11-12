@php
    $marketplaces = $supportedCountries ?? collect();
    $activeCode = strtoupper($country->code ?? '');
    $activeSlug = strtolower($countrySlug ?? ($country->slug ?? $country->code ?? 'gb'));
@endphp

<div class="bg-slate-900 text-white border-b border-slate-800" data-country-switcher-bar>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2.5 flex-wrap">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-500/20 border border-rose-400/30 text-rose-200 font-bold">
                <span>{{ $country->flag ?? '🌐' }}</span>
                <span>{{ $country->name }} Marketplace ({{ $country->code }})</span>
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-800 border border-slate-700 text-emerald-300 font-semibold" data-country-currency>
                Currency: {{ $country->currency_code }} ({{ $country->currency_symbol }})
            </span>
            <span class="hidden md:inline text-slate-400">
                Showing verified local listings exclusively in {{ $country->name }}
            </span>
        </div>

        <nav aria-label="Switch Country Marketplace" class="flex items-center gap-1.5 flex-wrap">
            <span class="text-slate-400 font-medium mr-1 hidden sm:inline">Switch Country:</span>
            @foreach($marketplaces as $mp)
                @php
                    $mpSlug = strtolower($mp->slug ?: $mp->code);
                    $isCurrent = strtoupper($mp->code) === $activeCode;
                @endphp
                <a
                    href="{{ route('country.switch', ['countrySlug' => $mpSlug]) }}"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg font-semibold transition {{ $isCurrent ? 'bg-[#F50057] text-white shadow-xs' : 'bg-slate-800/90 hover:bg-slate-700 text-slate-200' }}"
                    data-country-switch-link="{{ $mp->code }}"
                    @if($isCurrent) aria-current="page" @endif
                >
                    <span>{{ $mp->flag }}</span>
                    <span>{{ $mp->code }}</span>
                </a>
            @endforeach
            <a
                href="{{ route('country.choose') }}"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-800/60 hover:bg-slate-700 text-slate-300 font-medium transition"
            >
                All Marketplaces
            </a>
        </nav>
    </div>
</div>
