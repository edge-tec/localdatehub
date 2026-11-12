<x-layouts.app title="Manage {{ $country->name }} ({{ $country->code }}) Marketplace — LocalDateHub Admin">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" data-admin-country-show="{{ $country->code }}">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                    <a href="{{ route('admin.countries.index') }}" class="hover:text-[#F50057] font-semibold">&larr; All 5 Country Marketplaces</a>
                    <span>/</span>
                    <span class="font-bold text-slate-800">{{ $country->name }} ({{ $country->code }})</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                    <span>{{ $country->flag }}</span>
                    <span>{{ $country->name }} Marketplace Configuration</span>
                </h1>
            </div>

            <div class="flex items-center gap-2">
                <form action="{{ route('admin.countries.seed') }}" method="POST">
                    @csrf
                    <input type="hidden" name="country_code" value="{{ $country->code }}">
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition">
                        Refresh {{ $country->code }} Cities &amp; Areas
                    </button>
                </form>
                <a
                    href="{{ route('country.home', ['countrySlug' => strtolower($country->slug ?: $country->code)]) }}"
                    target="_blank"
                    class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:border-[#F50057] text-xs font-bold text-slate-700 transition"
                >
                    View Live /{{ strtolower($country->slug ?: $country->code) }} ↗
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- 1. Country Settings, Currency & SEO --}}
            <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200 p-6 shadow-xs space-y-5">
                <h2 class="text-base font-extrabold text-slate-900">
                    Marketplace Status, Currency &amp; SEO Settings
                </h2>

                <form action="{{ route('admin.countries.update', ['countryCode' => strtolower($country->code)]) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Country Name</label>
                            <input type="text" name="name" value="{{ old('name', $country->name) }}" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Currency Code (ISO)</label>
                            <input type="text" name="currency_code" value="{{ old('currency_code', $country->currency_code) }}" required maxlength="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm uppercase">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Currency Symbol</label>
                            <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $country->currency_symbol) }}" required maxlength="8" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Administrative Division Label</label>
                            <input type="text" name="division_label" value="{{ old('division_label', $country->division_label) }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Primary Timezone</label>
                            <input type="text" name="timezone" value="{{ old('timezone', $country->timezone) }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Country Homepage SEO Title</label>
                        <input type="text" name="seo_title" value="{{ old('seo_title', $country->seo_title) }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Country Homepage SEO Description</label>
                        <textarea name="seo_description" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('seo_description', $country->seo_description) }}</textarea>
                    </div>

                    <div class="flex flex-wrap items-center gap-6 pt-2">
                        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked($country->is_active) class="rounded border-slate-300 text-[#F50057]">
                            <span>Country Active</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-700">
                            <input type="hidden" name="is_marketplace_enabled" value="0">
                            <input type="checkbox" name="is_marketplace_enabled" value="1" @checked($country->is_marketplace_enabled) class="rounded border-slate-300 text-[#F50057]">
                            <span>Marketplace Publicly Enabled (/{{ strtolower($country->slug ?: $country->code) }})</span>
                        </label>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#F50057] hover:bg-[#d90858] text-white text-xs font-bold shadow-xs transition">
                            Save {{ $country->name }} Settings
                        </button>
                    </div>
                </form>
            </div>

            {{-- 2. Country Categories Toggle --}}
            <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-xs space-y-4">
                <h2 class="text-base font-extrabold text-slate-900">
                    Active Categories in {{ $country->name }}
                </h2>
                <p class="text-xs text-slate-500">
                    Enable or disable specific classified categories for the {{ $country->name }} marketplace.
                </p>

                <form action="{{ route('admin.countries.categories', ['countryCode' => strtolower($country->code)]) }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="max-h-72 overflow-y-auto space-y-2 pr-1">
                        @foreach($categories as $cat)
                            @php
                                $isCatEnabled = !in_array((int) $cat->id, $disabledCategoryIds, true);
                            @endphp
                            <label class="flex items-center justify-between gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs font-semibold text-slate-800">
                                <span>{{ $cat->name }}</span>
                                <input
                                    type="checkbox"
                                    name="enabled_category_ids[]"
                                    value="{{ $cat->id }}"
                                    @checked($isCatEnabled)
                                    class="rounded border-slate-300 text-[#F50057]"
                                >
                            </label>
                        @endforeach
                    </div>

                    <button type="submit" class="w-full px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition">
                        Update {{ $country->code }} Categories
                    </button>
                </form>
            </div>
        </div>

        {{-- 3. Add Administrative Area or City --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-xs space-y-4">
                <h2 class="text-base font-extrabold text-slate-900">
                    Add {{ $country->division_label }} in {{ $country->name }}
                </h2>
                <form action="{{ route('admin.countries.areas.store', ['countryCode' => strtolower($country->code)]) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @csrf
                    <input type="text" name="name" placeholder="{{ $country->division_label }} Name" required class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <input type="text" name="code" placeholder="Code (e.g. ENG, ON)" required maxlength="16" class="rounded-xl border border-slate-200 px-3 py-2 text-xs uppercase">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-[#F50057] text-white text-xs font-bold">
                        + Add {{ $country->division_label }}
                    </button>
                </form>
            </div>

            <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-xs space-y-4">
                <h2 class="text-base font-extrabold text-slate-900">
                    Add City in {{ $country->name }}
                </h2>
                <form action="{{ route('admin.countries.cities.store', ['countryCode' => strtolower($country->code)]) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @csrf
                    <select name="state_id" required class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                        <option value="">Select {{ $country->division_label }}</option>
                        @foreach($areas as $areaItem)
                            <option value="{{ $areaItem->id }}">{{ $areaItem->name }} ({{ $areaItem->code }})</option>
                        @endforeach
                    </select>
                    <input type="text" name="name" placeholder="City Name" required class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-[#F50057] text-white text-xs font-bold">
                        + Add City
                    </button>
                </form>
            </div>
        </div>

        {{-- 4. Administrative Areas Directory --}}
        <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-xs space-y-4">
            <h2 class="text-base font-extrabold text-slate-900">
                {{ $country->name }} {{ $country->division_label }}s ({{ $areas->count() }})
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
                @foreach($areas as $areaItem)
                    <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-3 text-xs">
                        <div class="font-bold text-slate-900 truncate">{{ $areaItem->name }} ({{ $areaItem->code }})</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            {{ (int) $areaItem->cities_count }} cities • {{ (int) $areaItem->listings_count }} ads
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.app>
