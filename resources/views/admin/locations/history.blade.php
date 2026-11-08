<x-layouts.app title="Location Dataset Import Version History — Admin">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Admin Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-700 border border-amber-500/20 uppercase tracking-wider">Admin Audit</span>
                    <a href="{{ route('admin.locations.index') }}" class="text-xs text-slate-400 hover:text-slate-900 transition">← Back to Cities</a>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Dataset Import Version History
                </h1>
                <p class="text-xs text-slate-400 mt-1">Audit trail for official US Census, USGS GNIS, and USPS location syncs.</p>
            </div>

            <form method="POST" action="{{ route('admin.locations.reimport') }}" class="flex items-center gap-2">
                @csrf
                <button type="submit" class="bg-rose-600 hover:bg-rose-500 text-white font-semibold py-2 px-4 rounded-xl text-xs transition shadow-md shadow-rose-600/20">
                    Sync / Re-Import Dataset
                </button>
            </form>
        </div>

        <!-- History Table -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3">Timestamp</th>
                            <th class="px-5 py-3">Dataset & Version</th>
                            <th class="px-5 py-3">Records Processed</th>
                            <th class="px-5 py-3">Execution Time</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Notes & Checksum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60">
                        @forelse($histories as $hist)
                            <tr class="hover:bg-slate-100 transition">
                                <td class="px-5 py-3.5 whitespace-nowrap text-slate-400 font-mono text-[11px]">
                                    {{ $hist->created_at->format('M d, Y H:i:s T') }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="font-bold text-slate-900">{{ $hist->dataset_name }}</span>
                                    <span class="text-[10px] text-slate-500 font-mono ml-1.5">v{{ $hist->version }}</span>
                                </td>
                                <td class="px-5 py-3.5 font-mono text-slate-800">
                                    {{ number_format($hist->records_processed) }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-400 font-mono">
                                    {{ $hist->duration_ms }} ms
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20">
                                        {{ ucfirst($hist->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-400">
                                    <div>{{ $hist->notes ?? '—' }}</div>
                                    @if($hist->checksum)
                                        <div class="font-mono text-[10px] text-slate-500 truncate max-w-xs">
                                            hash: {{ $hist->checksum }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-500 text-xs">
                                    No import history records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-slate-200">
                {{ $histories->links() }}
            </div>
        </div>
    </div>
</x-layouts.app>

