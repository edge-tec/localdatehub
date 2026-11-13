<x-layouts.app title="Buy Credits Checkout — LocalDateHub">
    <div class="bg-slate-50/70 min-h-screen py-8 sm:py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Breadcrumb & Header -->
            <div class="space-y-3 border-b border-slate-200/90 pb-5">
                <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                    <a href="{{ route('account.wallet.index') }}" class="hover:text-[#F20B65] transition-colors flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        Credit Wallet
                    </a>
                    <span class="text-slate-300">/</span>
                    <span class="text-[#F20B65]">Purchase Checkout</span>
                </nav>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                            Checkout: {{ $package->name }}
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-600 mt-1">
                            Select your preferred cryptocurrency gateway, coin, and blockchain network.
                        </p>
                    </div>
                    <div class="sm:text-right bg-white sm:bg-transparent px-4 py-2.5 sm:p-0 rounded-xl border sm:border-0 border-slate-200 inline-flex sm:block items-baseline gap-2 self-start sm:self-auto">
                        <span class="text-2xl sm:text-3xl font-black bg-gradient-to-r from-[#F20B65] to-orange-500 bg-clip-text text-transparent">
                            ${{ number_format($package->price_usd, 2) }}
                        </span>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 sm:block">USD</span>
                    </div>
                </div>
            </div>

            <form
                action="{{ route('account.wallet.payment', $package) }}"
                method="POST"
                x-data="{
                    selectedGateway: {{ $gateways->first()?->id ?? 'null' }},
                    selectedCurrency: 'USDT',
                    selectedNetwork: 'TRC20',
                    submitting: false,
                    currencies: {{ Js::from($currencies) }},
                    estimates: {{ Js::from($estimates ?? []) }},
                    get currentNetworks() {
                        return this.currencies[this.selectedCurrency]?.networks || ['MAINNET'];
                    }
                }"
                @submit="submitting = true"
                class="space-y-6"
            >
                @csrf

                <!-- Package Order Summary Card -->
                <div class="relative overflow-hidden bg-white border border-pink-200/80 rounded-2xl p-5 sm:p-6 shadow-xs">
                    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-[#F20B65] via-pink-500 to-orange-500"></div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
                        <div class="flex items-start sm:items-center gap-4">
                            <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-[#F20B65] to-orange-500 text-white flex items-center justify-center shadow-md shadow-pink-500/20 shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <h2 class="text-lg font-black text-slate-900">{{ $package->name }}</h2>
                                <p class="text-xs text-slate-600 font-medium">
                                    {{ number_format($package->credits) }} Base Credits
                                    @if($package->bonus_credits > 0)
                                        + <span class="text-emerald-600 font-bold">{{ number_format($package->bonus_credits) }} Bonus Credits</span>
                                    @endif
                                </p>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    +{{ number_format($package->credits + $package->bonus_credits) }} Total Credits to Receive
                                </span>
                            </div>
                        </div>

                        <div class="sm:text-right border-t sm:border-t-0 border-slate-100 pt-3 sm:pt-0">
                            <span class="text-[11px] font-semibold text-slate-500 block">Package Price (USD)</span>
                            <span class="text-2xl font-black text-slate-950 block">
                                ${{ number_format($package->price_usd, 2) }}
                            </span>
                            <div class="mt-1.5 px-3 py-1 rounded-xl bg-pink-50 border border-pink-200 text-[#F20B65] text-xs font-bold inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-orange-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span>Est. Amount:</span>
                                <span class="font-mono font-black" x-text="((typeof estimates[selectedCurrency] === 'object' && estimates[selectedCurrency] !== null ? estimates[selectedCurrency].crypto_amount : estimates[selectedCurrency]) || '1.00') + ' ' + selectedCurrency"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Payment Provider Gateway Selection -->
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#F20B65] to-orange-500 text-white text-xs font-black flex items-center justify-center shrink-0 mt-0.5">1</span>
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">Select Payment Provider</h3>
                                <p class="text-xs text-slate-500">Direct on-chain Trust Wallet settlements &amp; verified in-house engine.</p>
                            </div>
                        </div>
                        <span class="self-start sm:self-auto text-[11px] font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200 inline-flex items-center gap-1.5 shrink-0">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Zero 3rd-Party Fees &middot; In-House Gateway
                        </span>
                    </div>

                    @if($gateways->count() === 1)
                        @php $gw = $gateways->first(); @endphp
                        <div class="rounded-xl border-2 border-[#F20B65] bg-pink-50/35 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#F20B65] to-orange-500 text-white flex items-center justify-center shadow-md shadow-pink-500/20 shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-extrabold text-sm text-slate-900">{{ $gw->name }}</span>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-[#F20B65] text-white">Default Gateway</span>
                                    </div>
                                    <p class="text-xs text-slate-600 mt-1">Automated on-chain payment processor connecting directly to your cold Trust Wallet. No intermediate APIs.</p>
                                </div>
                            </div>
                            <input type="hidden" name="gateway_id" value="{{ $gw->id }}">
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($gateways as $gw)
                                <label
                                    class="relative border rounded-xl p-4 cursor-pointer transition flex flex-col justify-between"
                                    :class="selectedGateway == {{ $gw->id }} ? 'border-[#F20B65] bg-pink-50/40 ring-2 ring-[#F20B65]/20' : 'border-slate-200 hover:border-pink-300 bg-white'"
                                >
                                    <input
                                        type="radio"
                                        name="gateway_id"
                                        value="{{ $gw->id }}"
                                        x-model="selectedGateway"
                                        class="sr-only"
                                    >
                                    <div>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-extrabold text-sm text-slate-900">{{ $gw->name }}</span>
                                            <template x-if="selectedGateway == {{ $gw->id }}">
                                                <span class="w-5 h-5 rounded-full bg-[#F20B65] text-white flex items-center justify-center shrink-0">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                </span>
                                            </template>
                                        </div>
                                        <span class="text-[11px] text-slate-500 mt-1 block">{{ $gw->mode === 'sandbox' ? 'Test Sandbox' : 'Live Production' }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('gateway_id') <span class="text-xs text-rose-600 font-semibold block">{{ $message }}</span> @enderror
                </div>

                <!-- Step 2: Coin & Blockchain Network Selection -->
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
                    <div class="flex items-start gap-3">
                        <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#F20B65] to-orange-500 text-white text-xs font-black flex items-center justify-center shrink-0 mt-0.5">2</span>
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">Select Cryptocurrency &amp; Blockchain Network</h3>
                            <p class="text-xs text-slate-500">Pick coin and the exact network to avoid cross-chain payment issues.</p>
                        </div>
                    </div>

                    <!-- Currency Cards -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2.5">Cryptocurrency Coin:</label>
                        @php
                            $coinStyles = [
                                'BTC'  => ['bg' => 'bg-amber-500', 'ring' => 'ring-amber-500/20', 'glyph' => '₿'],
                                'ETH'  => ['bg' => 'bg-slate-800', 'ring' => 'ring-slate-500/20', 'glyph' => 'Ξ'],
                                'USDT' => ['bg' => 'bg-emerald-600', 'ring' => 'ring-emerald-500/20', 'glyph' => '₮'],
                                'USDC' => ['bg' => 'bg-sky-600', 'ring' => 'ring-sky-500/20', 'glyph' => '$'],
                                'LTC'  => ['bg' => 'bg-slate-600', 'ring' => 'ring-slate-500/20', 'glyph' => 'Ł'],
                                'TRX'  => ['bg' => 'bg-rose-600', 'ring' => 'ring-rose-500/20', 'glyph' => 'T'],
                                'BNB'  => ['bg' => 'bg-yellow-500', 'ring' => 'ring-yellow-500/20', 'glyph' => 'B'],
                            ];
                        @endphp
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                            @foreach($currencies as $symbol => $meta)
                                @php
                                    $style = $coinStyles[$symbol] ?? ['bg' => 'bg-[#F20B65]', 'ring' => 'ring-pink-500/20', 'glyph' => substr($symbol, 0, 1)];
                                @endphp
                                <button
                                    type="button"
                                    @click="selectedCurrency = '{{ $symbol }}'; selectedNetwork = currentNetworks[0];"
                                    class="relative p-3 rounded-xl border transition-all flex items-center gap-3 text-left cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#F20B65]"
                                    :class="selectedCurrency === '{{ $symbol }}'
                                        ? 'border-[#F20B65] bg-pink-50/70 ring-2 ring-pink-500/15 shadow-xs'
                                        : 'border-slate-200 hover:border-pink-300 hover:bg-slate-50/70 bg-white'"
                                >
                                    <span class="w-9 h-9 rounded-full {{ $style['bg'] }} text-white font-black text-sm flex items-center justify-center shrink-0 shadow-2xs">
                                        {{ $style['glyph'] }}
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-xs font-black text-slate-900">{{ $symbol }}</span>
                                        <span class="block text-[11px] font-medium text-slate-500 truncate">{{ $meta['name'] }}</span>
                                    </span>
                                    <span
                                        class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0 transition-colors"
                                        :class="selectedCurrency === '{{ $symbol }}' ? 'border-[#F20B65] bg-[#F20B65] text-white' : 'border-slate-300 bg-white'"
                                    >
                                        <svg x-show="selectedCurrency === '{{ $symbol }}'" class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        <input type="hidden" name="currency" :value="selectedCurrency">
                    </div>

                    <!-- Network Selection -->
                    <div class="pt-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2.5">Select Blockchain Network:</label>
                        <div class="flex flex-wrap gap-2.5">
                            <template x-for="net in currentNetworks" :key="net">
                                <button
                                    type="button"
                                    @click="selectedNetwork = net"
                                    class="px-4 py-2.5 rounded-xl border text-xs font-bold transition-all inline-flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-slate-900/20"
                                    :class="selectedNetwork === net
                                        ? 'border-slate-900 bg-slate-900 text-white shadow-xs'
                                        : 'border-slate-200 hover:border-slate-300 bg-white text-slate-700'"
                                >
                                    <svg class="w-3.5 h-3.5 shrink-0" :class="selectedNetwork === net ? 'text-orange-400' : 'text-slate-400'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                    <span x-text="net"></span>
                                </button>
                            </template>
                        </div>
                        <input type="hidden" name="network" :value="selectedNetwork">

                        <div class="mt-3 p-3 rounded-xl bg-amber-50/80 border border-amber-200/90 flex items-center gap-2.5 text-xs text-amber-900">
                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>
                                Please make sure your wallet transfers on the chosen network (<strong class="font-mono" x-text="selectedNetwork"></strong>).
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button & Cancel -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-4 pt-2">
                    <a
                        href="{{ route('account.wallet.index') }}"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-[#F20B65] transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Cancel and return to wallet</span>
                    </a>

                    <button
                        type="submit"
                        :disabled="submitting"
                        :class="submitting ? 'opacity-75 cursor-not-allowed' : 'hover:opacity-95 active:scale-[0.99]'"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 bg-gradient-to-r from-[#F20B65] via-pink-500 to-orange-500 text-white px-8 py-3.5 rounded-xl font-bold text-sm shadow-md shadow-pink-500/25 transition-all cursor-pointer focus:outline-none focus:ring-4 focus:ring-pink-500/25"
                    >
                        <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="submitting ? 'Generating Invoice...' : 'Generate Crypto Invoice'"></span>
                        <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>

            </form>

        </div>
    </div>
</x-layouts.app>
