<x-layouts.app title="Administrative Location Management — LocalDateHub">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Admin Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-700 border border-amber-500/20 uppercase tracking-wider">Admin Control</span>
                    <span class="text-xs text-slate-500">US Geographic Location Management</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Manage Cities, ZIPs & Aliases
                </h1>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.locations.history') }}" class="text-xs text-slate-600 hover:text-slate-900 px-3 py-2 rounded-lg bg-slate-100 border border-slate-200 transition">
                    Audit Import History
                </a>
                <a href="{{ route('locations.index') }}" target="_blank" class="text-xs text-slate-400 hover:text-slate-900 px-3 py-2 rounded-lg bg-white border border-slate-200 transition">
                    View Public Directory ↗
                </a>
            </div>
        </div>

        <!-- Filters & Search Toolbar -->
        <div class="p-4 rounded-xl bg-white border border-slate-200 mb-6">
            <form method="GET" action="{{ route('admin.locations.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div class="sm:col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by city name, slug, or alias..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-900 placeholder-slate-500 focus:outline-none focus:border-rose-500">
                </div>

                <div>
                    <select name="state" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-900 focus:outline-none focus:border-rose-500">
                        <option value="">All States ({{ $states->count() }})</option>
                        @foreach($states as $st)
                            <option value="{{ $st->code }}" @selected(request('state') === $st->code)>
                                {{ $st->name }} ({{ $st->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full bg-rose-600 hover:bg-rose-500 text-white font-semibold py-2 px-4 rounded-lg text-xs transition">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'state', 'popular']))
                        <a href="{{ route('admin.locations.index') }}" class="py-2 px-3 text-xs text-slate-400 hover:text-slate-900 bg-slate-100 rounded-lg transition">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Cities Table -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3">City & State</th>
                            <th class="px-5 py-3">Coordinates (WGS84)</th>
                            <th class="px-5 py-3">Population</th>
                            <th class="px-5 py-3">ZIPs & Aliases</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60">
                        @forelse($cities as $city)
                            <tr class="hover:bg-slate-100 transition">
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900 text-sm">{{ $city->name }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ $city->state?->name }} ({{ $city->state?->code }}) • slug: <span class="font-mono text-slate-500">{{ $city->slug }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 font-mono text-[11px]">
                                    @if($city->latitude && $city->longitude)
                                        <span class="text-slate-600">{{ number_format($city->latitude, 4) }}, {{ number_format($city->longitude, 4) }}</span>
                                    @else
                                        <span class="text-rose-600">Missing coordinates</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    {{ $city->population ? number_format($city->population) : '—' }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="px-2 py-0.5 rounded text-[10px] bg-slate-50 border border-slate-200 font-mono text-slate-400">
                                            {{ $city->zip_codes_count }} ZIPs
                                        </span>
                                        @foreach($city->aliases as $a)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] bg-pink-500/10 text-pink-600 border border-pink-500/20" title="{{ $a->alias_type }}">
                                                {{ $a->alias }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-1.5">
                                        @if($city->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-500/10 text-rose-600 border border-rose-500/20">
                                                Inactive
                                            </span>
                                        @endif
                                        @if($city->is_popular)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-700 border border-amber-500/20">
                                                Popular
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-right space-x-2">
                                    <a href="{{ route('locations.city', ['stateCode' => $city->state->code, 'citySlug' => $city->slug]) }}" target="_blank"
                                        class="text-xs text-rose-600 hover:text-rose-600 hover:underline">
                                        View Page
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-500 text-xs">
                                    No cities found matching query.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-slate-200">
                {{ $cities->links() }}
            </div>
        </div>
    </div>
</x-layouts.app>

