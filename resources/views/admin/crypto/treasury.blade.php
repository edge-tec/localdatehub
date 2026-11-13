<x-layouts.app title="Admin HD Treasury Control Panel — LocalDateHub">
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-2xl font-black text-slate-900">HD Wallet &amp; Treasury Control Panel</h1>
                <p class="text-xs text-slate-600 mt-1">
                    Environment Mode:
                    <span class="font-bold {{ $testnetOnly ? 'text-amber-700' : 'text-emerald-700' }}">
                        {{ $testnetOnly ? 'TESTNET ONLY (Staging Safe Mode)' : 'MAINNET ENABLED' }}
                    </span>
                    • Signer:
                    <span class="font-bold {{ ($signerHealth['ok'] ?? false) ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ ($signerHealth['ok'] ?? false) ? 'HEALTHY' : ($signerHealth['message'] ?? 'UNAVAILABLE') }}
                    </span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if($outboundPaused)
                    <form method="POST" action="{{ route('admin.crypto.treasury.resume') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer">
                            Resume Outbound Sweeps
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.crypto.treasury.pause') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold cursor-pointer">
                            Emergency Pause Sweeps
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">{{ session('error') }}</div>
        @endif

        <!-- KPIs -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <div class="text-xs font-bold text-slate-500 uppercase">Total Deposits</div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ $totalDeposits }}</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <div class="text-xs font-bold text-indigo-600 uppercase">Pending Deposits</div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ $pendingDeposits }}</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <div class="text-xs font-bold text-emerald-600 uppercase">Credited Deposits</div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ $confirmedDeposits }}</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <div class="text-xs font-bold text-rose-600 uppercase">Recovery Backlog</div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ $failedItems }}</div>
            </div>
        </div>

        <!-- Networks & Confirmations -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 font-black text-slate-900 text-sm">Blockchain Networks &amp; Confirmations</div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Network</th>
                            <th class="px-4 py-3 text-left">Chain ID</th>
                            <th class="px-4 py-3 text-left">Checkpoint</th>
                            <th class="px-4 py-3 text-left">Confirmations</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($networks as $net)
                            <tr>
                                <td class="px-4 py-3 font-bold text-slate-900">
                                    {{ $net->name }} ({{ $net->network_code }})
                                    <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded {{ $net->is_testnet ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800' }}">
                                        {{ $net->is_testnet ? 'TESTNET' : 'MAINNET' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono">{{ $net->chain_id ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono">{{ $net->checkpoint?->last_processed_height ?? '0' }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.crypto.treasury.network.threshold', $net->id) }}" class="flex items-center gap-1.5">
                                        @csrf
                                        <input type="number" name="confirmation_threshold" min="1" max="256" value="{{ $net->confirmation_threshold }}" class="w-16 rounded border border-slate-300 px-2 py-1 text-xs" />
                                        <button type="submit" class="px-2 py-1 rounded bg-slate-800 text-white text-[11px] font-bold cursor-pointer">Save</button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 font-bold {{ $net->is_enabled ? 'text-emerald-700' : 'text-slate-400' }}">
                                    {{ $net->is_enabled ? 'ENABLED' : 'DISABLED' }}
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.crypto.treasury.network.toggle', $net->id) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 rounded-lg border border-slate-300 hover:border-indigo-400 text-xs font-bold cursor-pointer">
                                            {{ $net->is_enabled ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Treasury Wallets & Sweeps -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 font-black text-slate-900 text-sm">Allowlisted Treasury Wallets</div>
                <div class="divide-y divide-slate-100 text-xs">
                    @forelse($treasuryWallets as $tw)
                        <div class="px-6 py-3 flex items-center justify-between gap-3">
                            <div>
                                <div class="font-bold text-slate-900">{{ $tw->network?->network_code }} ({{ $tw->custody_provider }})</div>
                                <div class="font-mono text-[11px] text-slate-600 break-all">{{ $tw->wallet_address }}</div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $tw->is_allowlisted ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                {{ $tw->is_allowlisted ? 'ALLOWLISTED' : 'BLOCKED' }}
                            </span>
                        </div>
                    @empty
                        <div class="px-6 py-6 text-slate-500 text-center">No treasury wallets configured.</div>
                    @endforelse
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 font-black text-slate-900 text-sm">Recent Sweep Operations</div>
                <div class="divide-y divide-slate-100 text-xs">
                    @forelse($sweeps as $sw)
                        <div class="px-6 py-3 flex items-center justify-between gap-3">
                            <div>
                                <div class="font-bold text-slate-900">#{{ $sw->id }} • {{ $sw->network?->network_code }} • {{ $sw->status }}</div>
                                <div class="font-mono text-[11px] text-slate-500">
                                    {{ $sw->transaction_hash ? substr($sw->transaction_hash, 0, 14).'...' : ($sw->last_error_code ?? 'Pending broadcast') }}
                                </div>
                            </div>
                            @if($sw->status !== 'SWEPT')
                                <form method="POST" action="{{ route('admin.crypto.treasury.sweep.retry', $sw->id) }}">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded bg-indigo-600 text-white text-[11px] font-bold cursor-pointer">Retry</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="px-6 py-6 text-slate-500 text-center">No sweep transactions yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
