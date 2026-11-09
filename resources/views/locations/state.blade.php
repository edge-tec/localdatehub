<x-layouts.app :title="$seoTitle" :description="$seoDescription" :canonicalUrl="$canonicalUrl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <!-- Breadcrumbs -->
        <x-breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'USA Directory', 'url' => route('locations.index')],
            ['label' => $state->name]
        ]" />

        <!-- State Header -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-xl">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-3 py-1 rounded-lg bg-rose-50 text-[#F20B83] border border-rose-200 font-mono font-black text-sm">
                            {{ $state->code }}
                        </span>
                        <span class="text-xs font-bold text-slate-700">United States State Directory</span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-black text-slate-950 tracking-tight">
                        {{ $state->name }} Adult Personal Classifieds
                    </h1>
                    <p class="text-sm font-semibold text-slate-700 mt-2 max-w-2xl">
                        Select a city below to view local dating connections, personal ads, and singles looking for genuine connections in {{ $state->name }}.
                    </p>
                </div>

                <div class="w-full md:w-72 shrink-0">
                    <livewire:location-autocomplete :placeholder="'Search in ' . $state->code . '...'" />
                </div>
            </div>
        </div>

        <!-- Popular Cities -->
        @php
            $popularCities = $cities->where('is_popular', true);
        @endphp
        @if($popularCities->isNotEmpty())
            <div>
                <h2 class="text-lg font-black text-slate-950 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#F20B83]" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.316.492-.633.986-.95 1.48C8.583 5.485 7.8 6.74 7.02 8.012c-.287.468-.58 1.05-.66 1.62-.08.57.065 1.155.43 1.61.733.91 1.954 1.15 2.92.57l.29-.17c.07.45.19.89.37 1.31.39.9 1.06 1.63 1.91 2.06.85.43 1.83.5 2.76.2 1.86-.6 3.06-2.3 3.06-4.24 0-1.89-1.2-3.66-3.1-4.24-.76-.23-1.57-.22-2.32.03.11-.7.3-1.39.57-2.05.27-.67.62-1.3 1.05-1.88a1 1 0 00-.38-1.45l-.65-.36z" clip-rule="evenodd" />
                    </svg>
                    Top Metropolitan Areas in {{ $state->name }}
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($popularCities as $city)
                        <a href="{{ route('locations.city', ['stateCode' => $state->code, 'citySlug' => $city->slug]) }}"
                            class="group p-5 rounded-2xl bg-white border border-slate-200 hover:border-[#F20B83] hover:bg-rose-50/40 transition shadow-sm hover:shadow-md">
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-base font-black text-slate-950 group-hover:text-[#F20B83] transition">{{ $city->name }}</h3>
                                <span class="text-xs font-black text-[#F20B83]">→</span>
                            </div>
                            <p class="text-xs font-semibold text-slate-700">
                                @if($city->population)
                                    Pop. {{ number_format($city->population) }} •
                                @endif
                                {{ $city->timezone }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- All Cities Directory -->
        <div>
            <h2 class="text-lg font-black text-slate-950 mb-4">Cities in {{ $state->name }}</h2>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                @foreach($cities as $city)
                    <a href="{{ route('locations.city', ['stateCode' => $state->code, 'citySlug' => $city->slug]) }}"
                        class="p-3.5 rounded-xl bg-white border border-slate-200 hover:border-[#F20B83] hover:bg-rose-50/40 transition text-xs font-bold text-slate-800 hover:text-slate-950 flex items-center justify-between shadow-2xs">
                        <span class="truncate">{{ $city->name }}</span>
                        <span class="text-xs font-bold text-[#F20B83]">→</span>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Counties Section -->
        @if($counties->isNotEmpty())
            <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-md">
                <h2 class="text-sm font-black uppercase tracking-wider text-slate-800 mb-4">
                    Supported Counties in {{ $state->name }}
                </h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($counties as $co)
                        <span class="px-3 py-1 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-800 font-mono font-semibold">
                            {{ $co->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>

