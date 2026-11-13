<div class="min-h-screen bg-slate-50 text-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Enterprise HD Wallet &amp; Trust Treasury</h1>
                <p class="text-sm text-slate-500">Real-time verified blockchain &amp; ledger telemetry (HSM/MPC Custody: <span class="font-medium text-slate-700">{{ $custodyHealth['provider'] }}</span>)</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $outboundPaused ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ $outboundPaused ? 'Outbound Sweeps PAUSED' : 'Outbound Sweeps ACTIVE' }}
                </span>
                <button wire:click="toggleOutboundPause" class="px-4 py-2 rounded-lg text-xs font-semibold {{ $outboundPaused ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-red-600 text-white hover:bg-red-700' }}">
                    {{ $outboundPaused ? 'Resume Outbound Sweeps' : 'Emergency Pause Sweeps' }}
                </button>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 bg-white border border-slate-200 rounded-xl p-2">
            @foreach ([
                'overview' => 'Overview',
                'addresses' => 'HD Wallet Addresses',
                'networks' => 'Cryptocurrency Networks',
                'treasury' => 'Treasury Management',
                'signing' => 'Secure Signing',
                'sweeps' => 'Automatic Sweeps',
                'gas' => 'Gas & Sponsorship',
                'recovery' => 'Recovery Center',
                'audit' => 'Audit Logs',
            ] as $key => $label)
                <button wire:click="setTab('{{ $key }}')" class="px-3 py-1.5 rounded-lg text-sm font-medium {{ $activeTab === $key ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="text-xs font-semibold text-slate-500 uppercase">Confirmed Deposits</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $confirmedDepositsCount }}</div>
                <div class="text-xs text-slate-400 mt-1">Credited base units: {{ $reconciliation['summary']['total_credited_base_units'] }}</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="text-xs font-semibold text-slate-500 uppercase">Total Swept (Base Units)</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $reconciliation['summary']['total_swept_base_units'] }}</div>
                <div class="text-xs text-slate-400 mt-1">Pending Sweeps: {{ $pendingSweepsCount }}</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="text-xs font-semibold text-slate-500 uppercase">Active Deposit Addresses</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $activeAddressesCount }}</div>
                <div class="text-xs text-slate-400 mt-1">Networks: {{ $networks->where('is_enabled', true)->count() }} enabled</div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="text-xs font-semibold text-slate-500 uppercase">Recovery &amp; Alerts</div>
                <div class="mt-1 text-2xl font-bold {{ $failedOperationsCount > 0 ? 'text-amber-600' : 'text-emerald-600' }}">{{ $failedOperationsCount }}</div>
                <div class="text-xs text-slate-400 mt-1">Reconciliation: {{ $reconciliation['healthy'] ? 'Healthy' : count($reconciliation['alerts']) . ' Alerts' }}</div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5">
            @if ($activeTab === 'overview')
                <h2 class="text-base font-semibold text-slate-900 mb-3">Recent Deposits</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="text-xs uppercase text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="py-2 pr-4">ID</th>
                                <th class="py-2 pr-4">Network / Asset</th>
                                <th class="py-2 pr-4">Amount (Base Units)</th>
                                <th class="py-2 pr-4">TX Hash</th>
                                <th class="py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recentDeposits as $dep)
                                <tr>
                                    <td class="py-2 pr-4 font-mono">#{{ $dep->id }}</td>
                                    <td class="py-2 pr-4">{{ $dep->network?->network_code }} / {{ $dep->asset?->symbol }}</td>
                                    <td class="py-2 pr-4 font-mono">{{ $dep->amount_base_units }}</td>
                                    <td class="py-2 pr-4 font-mono text-xs">{{ \Illuminate\Support\Str::limit($dep->blockchainTransaction?->transaction_hash, 18) }}</td>
                                    <td class="py-2"><span class="px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">{{ $dep->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-center text-slate-400">No deposits recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($activeTab === 'addresses')
                <h2 class="text-base font-semibold text-slate-900 mb-3">HD Wallet Address Manager</h2>
                <div class="space-y-2">
                    @forelse ($walletAddresses as $wa)
                        <div class="border border-slate-200 rounded-lg p-3 flex items-center justify-between text-xs">
                            <div>
                                <div class="font-semibold text-sm">{{ $wa->network?->network_code }} / {{ $wa->asset?->symbol }} — {{ $wa->user?->email ?? 'User #' . $wa->user_id }}</div>
                                <div class="font-mono text-slate-600">{{ $wa->address }} ({{ $wa->derivation_path }})</div>
                            </div>
                            <span class="px-2 py-0.5 rounded font-semibold bg-emerald-100 text-emerald-700">{{ $wa->status }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-slate-400">No HD deposit addresses allocated yet.</div>
                    @endforelse
                </div>
            @elseif ($activeTab === 'signing')
                <h2 class="text-base font-semibold text-slate-900 mb-3">Isolated Signing Requests</h2>
                <div class="space-y-2">
                    @forelse ($signingRequests as $sr)
                        <div class="border border-slate-200 rounded-lg p-3 flex items-center justify-between text-xs">
                            <div>
                                <div class="font-semibold text-sm">{{ $sr->intent_type }} ({{ $sr->network?->network_code }}) — Provider: {{ $sr->custody_provider }}</div>
                                <div class="font-mono text-slate-500">To: {{ $sr->to_address }} | Amount: {{ $sr->amount_base_units }}</div>
                            </div>
                            <span class="px-2 py-0.5 rounded font-semibold bg-slate-100">{{ $sr->status }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-slate-400">No signing requests recorded yet.</div>
                    @endforelse
                </div>
            @elseif ($activeTab === 'networks')
                <h2 class="text-base font-semibold text-slate-900 mb-3">Cryptocurrency Networks</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($networks as $net)
                        <div class="border border-slate-200 rounded-lg p-3 flex items-center justify-between">
                            <div>
                                <div class="font-semibold">{{ $net->name }} ({{ $net->network_code }})</div>
                                <div class="text-xs text-slate-500">Type: {{ $net->network_type }} | Threshold: {{ $net->confirmation_threshold }} | Checkpoint: {{ $net->checkpoint?->last_processed_height ?? 0 }}</div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $net->is_enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $net->is_enabled ? 'Enabled' : 'Disabled' }}</span>
                        </div>
                    @endforeach
                </div>
            @elseif ($activeTab === 'treasury')
                <h2 class="text-base font-semibold text-slate-900 mb-3">Allowlisted Treasury Wallets</h2>
                <div class="space-y-2">
                    @forelse ($treasuryWallets as $tw)
                        <div class="border border-slate-200 rounded-lg p-3 flex items-center justify-between text-sm">
                            <div>
                                <div class="font-semibold">{{ $tw->network?->network_code }} — {{ $tw->custody_provider }}</div>
                                <div class="font-mono text-xs text-slate-600">{{ $tw->wallet_address }}</div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $tw->is_allowlisted ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $tw->is_allowlisted ? 'Allowlisted' : 'Blocked' }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-slate-400">No treasury wallets registered.</div>
                    @endforelse
                </div>
            @elseif ($activeTab === 'sweeps')
                <h2 class="text-base font-semibold text-slate-900 mb-3">Recent Sweep Transactions</h2>
                <div class="space-y-2">
                    @forelse ($recentSweeps as $sw)
                        <div class="border border-slate-200 rounded-lg p-3 flex items-center justify-between text-sm">
                            <div>
                                <div class="font-semibold">Sweep #{{ $sw->id }} ({{ $sw->network?->network_code }})</div>
                                <div class="font-mono text-xs text-slate-500">
                                    Amount: {{ $sw->amount_base_units }} |
                                    @if ($sw->explorer_url)
                                        <a href="{{ $sw->explorer_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 underline">{{ \Illuminate\Support\Str::limit($sw->transaction_hash, 18) }}</a>
                                    @else
                                        TX: {{ $sw->transaction_hash ?? 'pending' }}
                                    @endif
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-slate-100">{{ $sw->status }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-slate-400">No sweep transactions yet.</div>
                    @endforelse
                </div>
            @elseif ($activeTab === 'gas')
                <h2 class="text-base font-semibold text-slate-900 mb-3">Gas Reserves</h2>
                <div class="space-y-2">
                    @forelse ($gasReserves as $gr)
                        <div class="border border-slate-200 rounded-lg p-3 flex items-center justify-between text-sm">
                            <div>{{ $gr->network?->network_code }} ({{ $gr->asset?->symbol }}) — Available: <span class="font-mono">{{ $gr->available_balance_base_units }}</span></div>
                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $gr->status === 'OPTIMAL' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $gr->status }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-slate-400">No gas reserves configured.</div>
                    @endforelse
                </div>
            @elseif ($activeTab === 'recovery')
                <h2 class="text-base font-semibold text-slate-900 mb-3">Automatic Recovery Queue</h2>
                <div class="space-y-2">
                    @forelse ($recoveryItems as $ri)
                        <div class="border border-slate-200 rounded-lg p-3 flex items-center justify-between text-sm">
                            <div>{{ $ri->operation_type }} #{{ $ri->reference_id }} — <span class="font-mono text-xs">{{ $ri->failure_code }}</span> (Retries: {{ $ri->retry_count }})</div>
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">{{ $ri->status }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-slate-400">Recovery queue is empty.</div>
                    @endforelse
                </div>
            @elseif ($activeTab === 'audit')
                <h2 class="text-base font-semibold text-slate-900 mb-3">Immutable Audit Logs</h2>
                <div class="space-y-2">
                    @forelse ($auditLogs as $log)
                        <div class="border border-slate-200 rounded-lg p-3 flex items-center justify-between text-xs">
                            <div><span class="font-semibold">{{ $log->action }}</span> on {{ $log->resource_type }} #{{ $log->resource_id }}</div>
                            <div class="text-slate-400">{{ $log->created_at }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-400">No audit logs recorded yet.</div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</div>

