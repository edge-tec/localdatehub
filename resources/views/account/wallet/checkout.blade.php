<x-layouts.app title="Buy Credits Checkout — LocalDateHub">
    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Breadcrumb & Title -->
        <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-500">
            <a href="{{ route('account.wallet.index') }}" class="hover:text-slate-800 transition">Credit Wallet</a>
            <span>/</span>
            <span class="text-slate-900">Purchase Checkout</span>
        </nav>

        <div class="flex items-center justify-between border-b border-slate-200 pb-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    Checkout: {{ $package->name }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                    Select your preferred cryptocurrency gateway, coin, and blockchain network.
                </p>
            </div>
            <div class="text-right">
                <span class="text-2xl sm:text-3xl font-black text-[#F20B83]">${{ number_format($package->price_usd, 2) }}</span>
                <span class="text-xs text-slate-500 block">USD</span>
            </div>
        </div>

        <form 
            action="{{ route('account.wallet.payment', $package) }}" 
            method="POST"
            x-data="{
                selectedGateway: {{ $gateways->first()?->id ?? 'null' }},
                selectedCurrency: 'USDT',
                selectedNetwork: 'TRC20',
                currencies: {{ Js::from($currencies) }},
                estimates: {{ Js::from($estimates ?? []) }},
                get currentNetworks() {
                    return this.currencies[this.selectedCurrency]?.networks || ['MAINNET'];
                }
            }"
            class="space-y-6"
        >
            @csrf

            <!-- Package Order Card -->
            <div class="bg-gradient-to-r from-pink-50/50 via-white to-slate-50 border border-pink-200/80 rounded-3xl p-6 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 rounded-2xl bg-[#F20B83]/10 text-[#F20B83] flex items-center justify-center text-2xl font-black shrink-0">
                        💳
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900">{{ $package->name }}</h3>
                        <p class="text-xs text-slate-600">
                            {{ number_format($package->credits) }} Base Credits
                            @if($package->bonus_credits > 0)
                                + <span class="text-emerald-600 font-bold">{{ number_format($package->bonus_credits) }} Bonus Credits</span>
                            @endif
                        </p>
                        <span class="text-xs font-bold text-emerald-600 mt-1 inline-block">
                            +{{ number_format($package->credits + $package->bonus_credits) }} Total Credits to Receive
                        </span>
                    </div>
                </div>

                <div class="text-right">
                    <span class="text-xs text-slate-500 block">Package Price (USD):</span>
                    <span class="text-2xl font-black text-slate-950">
                        ${{ number_format($package->price_usd, 2) }}
                    </span>
                    <div class="mt-1.5 px-3 py-1 rounded-xl bg-pink-50 border border-pink-200 text-[#F20B83] text-xs font-black inline-flex items-center gap-1.5 shadow-2xs">
                        <span>⚡ Est. Amount:</span>
                        <span class="font-mono text-xs" x-text="((typeof estimates[selectedCurrency] === 'object' && estimates[selectedCurrency] !== null ? estimates[selectedCurrency].crypto_amount : estimates[selectedCurrency]) || '1.00') + ' ' + selectedCurrency"></span>
                    </div>
                </div>
            </div>

            <!-- Step 1: Payment Provider Gateway Selection -->
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">1. Select Payment Provider</h3>
                        <p class="text-xs text-slate-500">Direct on-chain Trust Wallet settlements & verified in-house engine.</p>
                    </div>
                    <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200 flex items-center gap-1.5 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Zero 3rd-Party Fees · In-House Gateway
                    </span>
                </div>

                @if($gateways->count() === 1)
                    @php $gw = $gateways->first(); @endphp
                    <div class="rounded-2xl border-2 border-[#F20B83] bg-pink-50/25 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-[#F20B83] to-purple-600 text-white flex items-center justify-center font-black text-lg shadow-md shrink-0">
                                💎
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-sm text-slate-900">{{ $gw->name }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#F20B83] text-white">Default Gateway</span>
                                </div>
                                <p class="text-xs text-slate-600 mt-0.5">Automated on-chain payment processor connecting directly to your cold Trust Wallet. No intermediate APIs.</p>
                            </div>
                        </div>
                        <input type="hidden" name="gateway_id" value="{{ $gw->id }}">
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($gateways as $gw)
                            <label 
                                class="relative border rounded-2xl p-4 cursor-pointer transition flex flex-col justify-between"
                                :class="selectedGateway == {{ $gw->id }} ? 'border-[#F20B83] bg-pink-50/40 ring-2 ring-[#F20B83]/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
                            >
                                <input 
                                    type="radio" 
                                    name="gateway_id" 
                                    value="{{ $gw->id }}" 
                                    x-model="selectedGateway" 
                                    class="sr-only"
                                >
                                <div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-extrabold text-sm text-slate-900">{{ $gw->name }}</span>
                                        <template x-if="selectedGateway == {{ $gw->id }}">
                                            <span class="w-4 h-4 rounded-full bg-[#F20B83] text-white flex items-center justify-center text-[10px]">✓</span>
                                        </template>
                                    </div>
                                    <span class="text-[10px] text-slate-500 mt-1 block">{{ $gw->mode === 'sandbox' ? 'Test Sandbox' : 'Live Production' }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('gateway_id') <span class="text-xs text-rose-600 block">{{ $message }}</span> @enderror
            </div>

            <!-- Step 2: Coin & Blockchain Network Selection -->
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">2. Select Cryptocurrency &amp; Blockchain Network</h3>
                    <p class="text-xs text-slate-500">Pick coin and the exact network to avoid cross-chain payment issues.</p>
                </div>

                <!-- Currency Pills -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Cryptocurrency Coin:</label>
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                        @foreach($currencies as $symbol => $meta)
                            <button 
                                type="button" 
                                @click="selectedCurrency = '{{ $symbol }}'; selectedNetwork = currentNetworks[0];"
                                class="py-2.5 px-3 rounded-xl border text-xs font-black transition flex flex-col items-center justify-center"
                                :class="selectedCurrency === '{{ $symbol }}' ? 'border-[#F20B83] bg-[#F20B83] text-white shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white text-slate-800'"
                            >
                                <span>{{ $symbol }}</span>
                                <span class="text-[9px] font-normal opacity-80 truncate max-w-full">{{ $meta['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="currency" :value="selectedCurrency">
                </div>

                <!-- Network Selection -->
                <div class="pt-2">
                    <label class="block text-xs font-bold text-slate-700 mb-2">Select Blockchain Network:</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="net in currentNetworks" :key="net">
                            <button 
                                type="button" 
                                @click="selectedNetwork = net"
                                class="px-4 py-2 rounded-xl border text-xs font-bold transition flex items-center space-x-1.5"
                                :class="selectedNetwork === net ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 hover:border-slate-300 bg-white text-slate-700'"
                            >
                                <span x-text="net"></span>
                            </button>
                        </template>
                    </div>
                    <input type="hidden" name="network" :value="selectedNetwork">
                    <p class="text-[11px] text-amber-700 mt-2">
                        ⚠️ Please make sure your wallet transfers on the chosen network (<span class="font-bold font-mono" x-text="selectedNetwork"></span>).
                    </p>
                </div>
            </div>

            <!-- Submit Button & Disclaimer -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                <a 
                    href="{{ route('account.wallet.index') }}" 
                    class="text-xs font-bold text-slate-500 hover:text-slate-800"
                >
                    ← Cancel and return to wallet
                </a>

                <button 
                    type="submit" 
                    class="w-full sm:w-auto bg-[#F20B83] hover:bg-[#d90858] text-white px-10 py-3.5 rounded-full font-bold text-sm shadow-xl transition flex items-center justify-center space-x-2"
                >
                    <span>Generate Crypto Invoice</span>
                    <span>→</span>
                </button>
            </div>

        </form>

    </div>
</x-layouts.app>

