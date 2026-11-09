<x-layouts.app title="Crypto Payment Gateways — Admin" description="Manage cryptocurrency payment gateway integrations on LocalDateHub.">
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Breadcrumbs -->
        <div class="flex items-center space-x-2 text-xs text-slate-500">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-900 transition">Admin Dashboard</a>
            <span>/</span>
            <span class="text-slate-900 font-bold">Crypto Gateways</span>
        </div>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Cryptocurrency Payment Gateways</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Configure merchant API keys, webhook secrets, sandbox mode, and currency networks</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.crypto.plans.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl transition">
                    Manage Boost Plans
                </a>
                <a href="{{ route('admin.crypto.payments.index') }}" class="px-3.5 py-2 bg-[#F20B83] hover:bg-[#d90858] text-white text-xs font-bold rounded-xl transition shadow-xs">
                    View Transactions
                </a>
            </div>
        </div>

        <!-- Alert messages -->
        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold">
                ✓ {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold">
                ✕ {{ session('error') }}
            </div>
        @endif

        <!-- Gateways Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($gateways as $gw)
                <div class="bg-white border rounded-2xl p-5 shadow-xs flex flex-col justify-between transition {{ $gw->is_enabled ? 'border-slate-200' : 'border-slate-200/60 opacity-80' }}">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-base font-black text-slate-900">{{ $gw->name }}</h3>
                                <span class="text-[10px] font-mono text-slate-400">{{ $gw->provider }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                @if($gw->is_enabled)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-700">
                                        Enabled
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-slate-100 text-slate-500">
                                        Disabled
                                    </span>
                                @endif
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase {{ $gw->environment === 'live' ? 'bg-amber-100 text-amber-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ $gw->environment }}
                                </span>
                            </div>
                        </div>

                        <!-- Webhook Endpoint URL -->
                        <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block">Webhook Callback URL</span>
                            <div class="text-[11px] font-mono text-slate-700 truncate select-all">
                                {{ url('/webhooks/crypto/' . $gw->slug) }}
                            </div>
                        </div>

                        <!-- Currencies Supported -->
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Supported Currencies</span>
                            <div class="flex flex-wrap gap-1">
                                @foreach(array_slice($gw->supported_currencies ?? [], 0, 7) as $curr)
                                    <span class="px-1.5 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] font-extrabold">
                                        {{ $curr }}
                                    </span>
                                @endforeach
                                @if(count($gw->supported_currencies ?? []) > 7)
                                    <span class="px-1.5 py-0.5 bg-slate-50 text-slate-400 rounded text-[10px]">
                                        +{{ count($gw->supported_currencies) - 7 }} more
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Last test status -->
                        @if($gw->last_tested_at)
                            <div class="text-[10px] text-slate-500 pt-1">
                                Last Tested: {{ $gw->last_tested_at->diffForHumans() }} • 
                                <span class="{{ $gw->last_test_status === 'success' ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold' }}">
                                    {{ ucfirst($gw->last_test_status) }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <form action="{{ route('admin.crypto.gateways.test', $gw->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg transition">
                                Test Connection
                            </button>
                        </form>

                        <a href="{{ route('admin.crypto.gateways.edit', $gw->id) }}" class="text-xs font-extrabold text-[#F20B83] hover:underline flex items-center gap-1">
                            Configure &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</x-layouts.app>

