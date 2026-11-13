<x-layouts.app title="Multi-Chain Crypto Wallet — LocalDateHub">
    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                    <a href="{{ route('account.dashboard') }}" class="hover:text-indigo-600 transition">My Account</a>
                    <span>/</span>
                    <a href="{{ route('account.wallet.index') }}" class="hover:text-indigo-600 transition">Credit Wallet</a>
                    <span>/</span>
                    <span class="text-slate-900">Crypto Wallet</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    Multi-Chain Crypto Wallet
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                    Generate your dedicated HD deposit address, monitor on-chain confirmations, and track ledger balances.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('account.wallet.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:border-indigo-300 hover:text-indigo-700 text-xs font-bold transition">
                    Credit Packages
                </a>
            </div>
        </div>

        <!-- Overview Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-white border border-indigo-100 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Available Balances</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700">{{ count($balances) }} Asset(s)</span>
                </div>
                @if(count($balances) === 0)
                    <div class="text-2xl font-black text-slate-900">0.00</div>
                    <p class="text-xs text-slate-500 mt-1">No confirmed crypto deposits credited yet.</p>
                @else
                    <div class="space-y-2">
                        @foreach($balances as $bal)
                            @php
                                $dec = max(0, (int) $bal['decimals']);
                                $raw = (string) $bal['balance_base_units'];
                                $formatted = $dec > 0
                                    ? rtrim(rtrim(bcdiv($raw, bcpow('10', (string) $dec, 0), min(8, $dec)), '0'), '.') ?: '0'
                                    : $raw;
                            @endphp
                            <div class="flex items-baseline justify-between">
                                <span class="text-lg font-black text-slate-900">{{ $formatted }} {{ $bal['symbol'] }}</span>
                                <span class="text-[11px] font-semibold text-slate-500">{{ $bal['network_code'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white border border-violet-100 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-violet-600">Pending Deposits</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-violet-50 text-violet-700">Live Tracking</span>
                </div>
                <div class="text-3xl font-black text-slate-900">{{ $pendingDepositsCount }}</div>
                <p class="text-xs text-slate-500 mt-1">Awaiting required network confirmations before ledger credit.</p>
            </div>

            <div class="bg-white border border-blue-100 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Supported Networks</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700">Active</span>
                </div>
                <div class="text-3xl font-black text-slate-900">{{ $networks->count() }}</div>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $networks->pluck('network_code')->implode(', ') ?: 'No networks currently enabled' }}
                </p>
            </div>
        </div>

        <!-- Deposit Interface -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900">Deposit Cryptocurrency</h2>
                    <p class="text-xs text-slate-500">Select a network and asset to view or generate your dedicated HD deposit address.</p>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-medium leading-relaxed">
                <strong>Important Security Notice:</strong> Only send the selected cryptocurrency using the selected network. Deposits sent through unsupported networks or incorrect token contracts may be permanently lost.
            </div>

            @if($networks->isEmpty())
                <div class="p-6 rounded-xl bg-slate-50 border border-slate-200 text-center text-sm text-slate-600">
                    No blockchain networks are currently enabled. Please contact support or an administrator.
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div class="space-y-4">
                        <div>
                            <label for="hd-network-select" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">1. Select Blockchain Network</label>
                            <select id="hd-network-select" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($networks as $net)
                                    <option value="{{ $net->network_code }}"
                                        data-confirmations="{{ $net->confirmation_threshold }}"
                                        data-testnet="{{ $net->is_testnet ? '1' : '0' }}"
                                        data-explorer="{{ $net->explorer_base_url }}">
                                        {{ $net->name }} ({{ $net->network_code }}) {{ $net->is_testnet ? '[Testnet]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="hd-asset-select" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">2. Select Cryptocurrency</label>
                            <select id="hd-asset-select" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->symbol }}"
                                        data-network="{{ $asset->network->network_code }}"
                                        data-decimals="{{ $asset->decimals }}"
                                        data-min="{{ $asset->minimum_deposit }}"
                                        data-contract="{{ $asset->contract_address ?? '' }}">
                                        {{ $asset->symbol }} — {{ $asset->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                            <div>
                                <div class="text-slate-500 font-semibold">Required Confirmations</div>
                                <div id="hd-meta-confs" class="text-slate-900 font-black mt-0.5">—</div>
                            </div>
                            <div>
                                <div class="text-slate-500 font-semibold">Minimum Deposit</div>
                                <div id="hd-meta-min" class="text-slate-900 font-black mt-0.5">—</div>
                            </div>
                        </div>

                        <button id="hd-generate-btn" type="button" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-sm font-bold shadow-sm transition cursor-pointer">
                            Show / Generate Deposit Address
                        </button>

                        <div id="hd-status-msg" class="text-xs font-medium text-slate-600 hidden"></div>
                    </div>

                    <div id="hd-address-panel" class="p-5 rounded-2xl border border-indigo-100 bg-indigo-50/40 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-700">Assigned Deposit Address</span>
                            <span id="hd-badge-net" class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-white text-indigo-700 border border-indigo-200">Select asset</span>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-4">
                            <div class="w-32 h-32 rounded-xl bg-white border border-slate-200 flex items-center justify-center p-2 shrink-0">
                                <img id="hd-qr-img" src="" alt="Deposit QR" class="w-full h-full object-contain hidden" />
                                <span id="hd-qr-placeholder" class="text-[11px] text-slate-400 text-center">QR Code</span>
                            </div>

                            <div class="flex-1 w-full space-y-2.5">
                                <div class="p-3 rounded-xl bg-white border border-slate-200 font-mono text-xs text-slate-900 break-all" id="hd-address-text">
                                    Click "Show / Generate Deposit Address" to load your address.
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button id="hd-copy-btn" type="button" disabled class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white text-xs font-bold transition cursor-pointer">
                                        Copy Address
                                    </button>
                                    <button id="hd-refresh-btn" type="button" disabled class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-300 hover:border-indigo-400 disabled:opacity-40 text-slate-700 text-xs font-bold transition cursor-pointer">
                                        Scan for Deposits
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-indigo-100 space-y-2">
                            <label for="hd-tx-hash" class="block text-[11px] font-bold text-slate-600 uppercase">Optional: Verify Specific Transaction Hash</label>
                            <div class="flex gap-2">
                                <input id="hd-tx-hash" type="text" placeholder="0x..." class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-mono text-slate-900" />
                                <button id="hd-verify-tx-btn" type="button" disabled class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white text-xs font-bold transition cursor-pointer">
                                    Verify Tx
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Deposit History -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-black text-slate-900">Deposit History</h2>
                <span class="text-xs font-semibold text-slate-500">Showing latest {{ $deposits->count() }} record(s)</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-slate-600 uppercase font-bold tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Asset / Network</th>
                            <th class="px-4 py-3 text-left">Amount</th>
                            <th class="px-4 py-3 text-left">Confirmations</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Tx Hash</th>
                            <th class="px-4 py-3 text-left">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($deposits as $dep)
                            @php
                                $dec = max(0, (int) ($dep->asset?->decimals ?? 18));
                                $amt = $dec > 0
                                    ? rtrim(rtrim(bcdiv((string) $dep->amount_base_units, bcpow('10', (string) $dec, 0), min(8, $dec)), '0'), '.') ?: '0'
                                    : (string) $dep->amount_base_units;
                                $txHash = $dep->blockchainTransaction?->transaction_hash;
                                $explorerBase = rtrim((string) ($dep->network?->explorer_base_url ?? ''), '/');
                                $confs = (int) ($dep->blockchainTransaction?->confirmation_count ?? 0);
                                $reqConfs = (int) ($dep->network?->confirmation_threshold ?? 1);
                            @endphp
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3 font-bold text-slate-900">#{{ $dep->id }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900">{{ $dep->asset?->symbol }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $dep->network?->network_code }}</div>
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-slate-900">{{ $amt }} {{ $dep->asset?->symbol }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-700">{{ $confs }} / {{ $reqConfs }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold
                                        @if(in_array($dep->status, ['CREDITED', 'SWEEP_QUEUED', 'SWEEPING', 'SWEPT'])) bg-emerald-50 text-emerald-700 border border-emerald-200
                                        @elseif(in_array($dep->status, ['FAILED', 'REORGED', 'RECOVERY_REQUIRED'])) bg-rose-50 text-rose-700 border border-rose-200
                                        @else bg-indigo-50 text-indigo-700 border border-indigo-200 @endif">
                                        {{ $dep->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono">
                                    @if($txHash)
                                        @if($explorerBase)
                                            <a href="{{ $explorerBase }}/tx/{{ $txHash }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline">
                                                {{ substr($txHash, 0, 10) }}...{{ substr($txHash, -6) }}
                                            </a>
                                        @else
                                            {{ substr($txHash, 0, 10) }}...{{ substr($txHash, -6) }}
                                        @endif
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $dep->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">No crypto deposits recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($deposits->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $deposits->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        (function () {
            const netSelect = document.getElementById('hd-network-select');
            const assetSelect = document.getElementById('hd-asset-select');
            if (!netSelect || !assetSelect) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const confsEl = document.getElementById('hd-meta-confs');
            const minEl = document.getElementById('hd-meta-min');
            const genBtn = document.getElementById('hd-generate-btn');
            const copyBtn = document.getElementById('hd-copy-btn');
            const refreshBtn = document.getElementById('hd-refresh-btn');
            const verifyBtn = document.getElementById('hd-verify-tx-btn');
            const txInput = document.getElementById('hd-tx-hash');
            const addrEl = document.getElementById('hd-address-text');
            const badgeEl = document.getElementById('hd-badge-net');
            const qrImg = document.getElementById('hd-qr-img');
            const qrPlaceholder = document.getElementById('hd-qr-placeholder');
            const statusMsg = document.getElementById('hd-status-msg');

            let currentAddress = '';

            function formatUnits(raw, decimals) {
                if (!raw || raw === '0') return '0';
                const s = String(raw).padStart(decimals + 1, '0');
                const intPart = s.slice(0, -decimals) || '0';
                const fracPart = s.slice(-decimals).replace(/0+$/, '');
                return fracPart ? `${intPart}.${fracPart}` : intPart;
            }

            function syncAssets() {
                const netCode = netSelect.value;
                const netOpt = netSelect.options[netSelect.selectedIndex];
                confsEl.textContent = `${netOpt.dataset.confirmations || '1'} confirmation(s)`;

                let firstVisible = -1;
                Array.from(assetSelect.options).forEach((opt, idx) => {
                    const match = opt.dataset.network === netCode;
                    opt.hidden = !match;
                    opt.disabled = !match;
                    if (match && firstVisible === -1) firstVisible = idx;
                });

                if (firstVisible !== -1 && assetSelect.options[assetSelect.selectedIndex]?.disabled) {
                    assetSelect.selectedIndex = firstVisible;
                }
                syncMinDeposit();
            }

            function syncMinDeposit() {
                const opt = assetSelect.options[assetSelect.selectedIndex];
                if (!opt) return;
                const dec = parseInt(opt.dataset.decimals || '18', 10);
                minEl.textContent = `${formatUnits(opt.dataset.min || '0', dec)} ${opt.value}`;
            }

            function showMsg(text, isError = false) {
                statusMsg.textContent = text;
                statusMsg.className = `text-xs font-semibold ${isError ? 'text-rose-600' : 'text-emerald-700'}`;
                statusMsg.classList.remove('hidden');
            }

            async function generateAddress() {
                genBtn.disabled = true;
                genBtn.textContent = 'Loading Address...';
                try {
                    const res = await fetch('/api/v1/wallet/deposit/address', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({
                            network_code: netSelect.value,
                            asset_symbol: assetSelect.value
                        })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Unable to generate deposit address');

                    currentAddress = data.address;
                    addrEl.textContent = currentAddress;
                    badgeEl.textContent = `${data.network_code} • ${data.asset_symbol}`;
                    qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(currentAddress)}`;
                    qrImg.classList.remove('hidden');
                    qrPlaceholder.classList.add('hidden');
                    copyBtn.disabled = false;
                    refreshBtn.disabled = false;
                    verifyBtn.disabled = false;
                    showMsg('Deposit address ready. Send only ' + data.asset_symbol + ' on ' + data.network_code + '.');
                } catch (err) {
                    showMsg(err.message, true);
                } finally {
                    genBtn.disabled = false;
                    genBtn.textContent = 'Show / Generate Deposit Address';
                }
            }

            async function refreshDeposit(txHash = null) {
                try {
                    const res = await fetch('/api/v1/wallet/deposit/refresh', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({
                            network_code: netSelect.value,
                            asset_symbol: assetSelect.value,
                            tx_hash: txHash
                        })
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Refresh failed');
                    showMsg(`Scan queued (${data.dispatched_count} transaction(s) dispatched for verification). Reloading shortly...`);
                    if (data.dispatched_count > 0) setTimeout(() => window.location.reload(), 2500);
                } catch (err) {
                    showMsg(err.message, true);
                }
            }

            netSelect.addEventListener('change', syncAssets);
            assetSelect.addEventListener('change', syncMinDeposit);
            genBtn.addEventListener('click', generateAddress);
            copyBtn.addEventListener('click', async () => {
                if (!currentAddress) return;
                await navigator.clipboard.writeText(currentAddress);
                showMsg('Address copied to clipboard.');
            });
            refreshBtn.addEventListener('click', () => refreshDeposit(null));
            verifyBtn.addEventListener('click', () => {
                const h = (txInput.value || '').trim();
                if (!h) return showMsg('Please paste a transaction hash first.', true);
                refreshDeposit(h);
            });

            syncAssets();
        })();
    </script>
</x-layouts.app>
