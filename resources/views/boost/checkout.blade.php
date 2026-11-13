<x-layouts.app title="Boost Your Ad — LocalDateHub" description="Promote your classified ad with cryptocurrency payment on LocalDateHub.">
    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Header Breadcrumbs -->
        <div class="flex items-center space-x-2 text-xs text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-slate-900 transition">Home</a>
            <span>/</span>
            <a href="{{ route('account.listings.index') }}" class="hover:text-slate-900 transition">My Listings</a>
            <span>/</span>
            <span class="text-slate-900 font-bold">Boost Listing</span>
        </div>

        <!-- Listing Banner -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <span class="text-[10px] uppercase tracking-wider font-extrabold text-[#F20B83] bg-pink-50 px-2 py-0.5 rounded">
                    Boosting Ad #{{ $listing->id }}
                </span>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">{{ $listing->title }}</h1>
                <p class="text-xs text-slate-500">
                    Category: <strong class="text-slate-700">{{ $listing->category?->name ?? 'Classified' }}</strong> • 
                    Location: <strong class="text-slate-700">{{ $listing->city?->name }}, {{ $listing->state?->code }}</strong>
                </p>
            </div>

            @if($activeBoost)
                <div class="bg-pink-50/80 border border-pink-200 rounded-xl p-3 shrink-0 text-right">
                    <span class="text-[11px] font-extrabold text-[#F20B83] flex items-center gap-1 justify-end">
                        <span class="w-2 h-2 rounded-full bg-[#F20B83] animate-ping"></span>
                        Currently Boosted
                    </span>
                    <span class="text-[10px] text-slate-600 block mt-0.5">
                        Active until {{ $activeBoost->expires_at->format('M d, Y H:i') }}
                    </span>
                    <span class="text-[10px] text-slate-400 block italic">Additional boosts will extend your time</span>
                </div>
            @endif
        </div>

        <!-- Interactive Checkout Form -->
        <form 
            action="{{ route('boost.create', $listing->id) }}" 
            method="POST" 
            x-data="{
                selectedPlanId: {{ $plans->first()?->id ?? 1 }},
                selectedPlanPrice: {{ $plans->first()?->price ?? 4.99 }},
                walletBalance: {{ (int) ($walletBalance ?? 0) }},
                get requiredCredits() {
                    return this.selectedPlanPrice > 0 ? Math.max(1, Math.round(this.selectedPlanPrice * 10)) : 0;
                },
                selectedGatewayId: {{ $gateways->first()?->id ?? 1 }},
                selectedCurrency: 'USDT',
                selectedNetwork: 'TRC20',
                availableNetworks: {
                    'BTC': [{'code': 'BTC', 'name': 'Bitcoin Mainnet', 'warning': 'Send only BTC via Bitcoin network.'}],
                    'ETH': [{'code': 'ERC20', 'name': 'Ethereum (ERC-20)', 'warning': 'Standard Ethereum network gas fees apply.'}],
                    'USDT': [
                        {'code': 'TRC20', 'name': 'Tron (TRC-20) - Low Fee', 'warning': 'Fastest confirmation & lowest fees.'},
                        {'code': 'BEP20', 'name': 'BNB Chain (BEP-20)', 'warning': 'Send only via BNB Smart Chain.'},
                        {'code': 'POLYGON', 'name': 'Polygon Network', 'warning': 'Ultra-low fees on Polygon.'},
                        {'code': 'ERC20', 'name': 'Ethereum (ERC-20)', 'warning': 'High gas fees may apply.'}
                    ],
                    'USDC': [
                        {'code': 'ERC20', 'name': 'Ethereum (ERC-20)', 'warning': 'Send only ERC-20 USDC.'},
                        {'code': 'POLYGON', 'name': 'Polygon Network', 'warning': 'Low fee on Polygon.'}
                    ],
                    'LTC': [{'code': 'LTC', 'name': 'Litecoin Mainnet', 'warning': 'Send only LTC via Litecoin network.'}],
                    'TRX': [{'code': 'TRON', 'name': 'TRON Network', 'warning': 'Send only TRX via TRON network.'}],
                    'BNB': [{'code': 'BEP20', 'name': 'BNB Smart Chain', 'warning': 'Send only BNB via BEP-20.'}]
                },
                get currentNetworks() {
                    return this.availableNetworks[this.selectedCurrency] || [{'code': 'Native', 'name': 'Default Network', 'warning': ''}];
                },
                setCurrency(curr) {
                    this.selectedCurrency = curr;
                    const nets = this.availableNetworks[curr];
                    if (nets && nets.length > 0) {
                        this.selectedNetwork = nets[0].code;
                    }
                }
            }"
            class="space-y-6"
        >
            @csrf

            <!-- Hidden Inputs -->
            <input type="hidden" name="boost_plan_id" :value="selectedPlanId">
            <input type="hidden" name="boost_plan_id" :value="selectedPlanId" form="wallet-credits-boost-form">
            <input type="hidden" name="gateway_id" :value="selectedGatewayId">
            <input type="hidden" name="currency" :value="selectedCurrency">
            <input type="hidden" name="network" :value="selectedNetwork">

            <!-- STEP 1: Select Boost Plan -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-pink-100 text-[#F20B83] text-xs font-black flex items-center justify-center">1</span>
                            Choose a Boost Plan
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Select how long you want your ad pinned and spotlighted</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach($plans as $plan)
                        <div 
                            @click="selectedPlanId = {{ $plan->id }}; selectedPlanPrice = {{ (float) $plan->price }};"
                            class="relative cursor-pointer rounded-xl border p-4 transition-all duration-150 flex flex-col justify-between"
                            :class="selectedPlanId === {{ $plan->id }} ? 'border-[#F20B83] ring-2 ring-[#F20B83]/20 bg-pink-50/20 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white'"
                        >
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-black text-slate-900">{{ $plan->name }}</span>
                                    <span class="text-sm font-black text-[#F20B83]">${{ number_format((float) $plan->price, 2) }}</span>
                                </div>
                                <p class="text-[11px] text-slate-500 leading-relaxed">{{ $plan->description }}</p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-600 font-semibold">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $plan->duration_days }} {{ Str::plural('Day', $plan->duration_days) }} Exposure
                                </span>
                                <span class="w-4 h-4 rounded-full border flex items-center justify-center" :class="selectedPlanId === {{ $plan->id }} ? 'border-[#F20B83] bg-[#F20B83]' : 'border-slate-300'">
                                    <span x-show="selectedPlanId === {{ $plan->id }}" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Instant Bump Using Wallet Credits -->
            <div class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-black uppercase tracking-wider text-emerald-900">Instant Bump with Wallet Credits</span>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-600 text-white text-[10px] font-bold">
                            Balance: {{ number_format((int) ($walletBalance ?? 0)) }} Credits
                        </span>
                    </div>
                    <p class="text-xs text-emerald-800">
                        Selected plan costs <strong x-text="requiredCredits"></strong> credits.
                        <span x-show="walletBalance >= requiredCredits">Click below to bump &amp; feature your ad immediately without waiting for blockchain confirmation.</span>
                        <span x-show="walletBalance < requiredCredits" class="text-amber-800 font-semibold">Insufficient credits for this plan — pay with Crypto below or top up your wallet.</span>
                    </p>
                </div>
                <div class="shrink-0">
                    <button
                        type="submit"
                        form="wallet-credits-boost-form"
                        x-show="walletBalance >= requiredCredits"
                        class="w-full sm:w-auto px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-sm transition cursor-pointer"
                    >
                        ⚡ Bump Now (<span x-text="requiredCredits"></span> Credits)
                    </button>
                    <a
                        href="{{ route('account.wallet.index') }}"
                        x-show="walletBalance < requiredCredits"
                        class="inline-block px-4 py-2.5 rounded-xl bg-white border border-emerald-300 text-emerald-800 hover:bg-emerald-100 text-xs font-bold transition"
                    >
                        + Top Up Credits
                    </a>
                </div>
            </div>

            <!-- STEP 2: Choose Payment Gateway -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-pink-100 text-[#F20B83] text-xs font-black flex items-center justify-center">2</span>
                            Payment Gateway
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Secure, non-custodial direct crypto settlement</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-extrabold flex items-center gap-1.5 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Zero 3rd-Party Middleman · 100% In-House
                    </span>
                </div>

                @if($gateways->count() === 1)
                    @php $gw = $gateways->first(); @endphp
                    <div class="rounded-2xl border-2 border-[#F20B83] bg-pink-50/20 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-[#F20B83] to-purple-600 text-white flex items-center justify-center font-black text-lg shadow-md shrink-0">
                                🛡️
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-black text-slate-900">{{ $gw->name }}</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#F20B83] text-white">Active</span>
                                </div>
                                <p class="text-xs text-slate-600 mt-0.5">Direct blockchain settlement to Trust Wallet with automated verification. No third-party API dependencies or processor delays.</p>
                            </div>
                        </div>
                        <input type="hidden" name="gateway_id" value="{{ $gw->id }}">
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5">
                        @forelse($gateways as $gw)
                            <div 
                                @click="selectedGatewayId = {{ $gw->id }}"
                                class="cursor-pointer rounded-xl border p-3 text-center transition-all flex flex-col items-center justify-center min-h-[75px]"
                                :class="selectedGatewayId === {{ $gw->id }} ? 'border-[#F20B83] ring-2 ring-[#F20B83]/20 bg-pink-50/20 font-bold text-[#F20B83]' : 'border-slate-200 hover:border-slate-300 bg-white text-slate-700'"
                            >
                                <span class="text-xs font-extrabold tracking-tight">{{ $gw->name }}</span>
                                <span class="text-[9px] uppercase tracking-wider text-slate-400 mt-0.5">Instant IPN</span>
                            </div>
                        @empty
                            <div class="col-span-full p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
                                No payment gateways are currently enabled by administrator. Please check back shortly.
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>

            <!-- STEP 3: Select Cryptocurrency & Blockchain Network -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-pink-100 text-[#F20B83] text-xs font-black flex items-center justify-center">3</span>
                            Select Cryptocurrency & Network
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Always verify the blockchain network before sending coins</p>
                    </div>
                </div>

                <!-- Currency Buttons Grid -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Cryptocurrency</label>
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                        @foreach($currencies as $symbol => $meta)
                            <button 
                                type="button" 
                                @click="setCurrency('{{ $symbol }}')"
                                class="p-2.5 rounded-xl border text-xs font-extrabold flex items-center justify-center gap-1.5 transition"
                                :class="selectedCurrency === '{{ $symbol }}' ? 'border-[#F20B83] bg-pink-50/30 text-[#F20B83] ring-1 ring-[#F20B83]' : 'border-slate-200 hover:border-slate-300 text-slate-700 bg-white'"
                            >
                                <span>{{ $symbol }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Network Selection Grid -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Blockchain Network <span class="text-[#F20B83]">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                        <template x-for="net in currentNetworks" :key="net.code">
                            <div 
                                @click="selectedNetwork = net.code"
                                class="cursor-pointer rounded-xl border p-3 transition"
                                :class="selectedNetwork === net.code ? 'border-[#F20B83] bg-pink-50/20 ring-2 ring-[#F20B83]/20 text-[#F20B83]' : 'border-slate-200 hover:border-slate-300 text-slate-700 bg-white'"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold" x-text="net.name"></span>
                                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-100 text-slate-600" x-text="net.code"></span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1" x-text="net.warning"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Order Summary & Checkout Button -->
            <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-2xl p-6 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Total Due</span>
                    <div class="text-3xl font-black text-white mt-0.5">
                        $<span x-text="selectedPlanPrice.toFixed(2)"></span> <span class="text-xs text-slate-400 font-normal">USD</span>
                    </div>
                    <p class="text-xs text-slate-300 mt-1">
                        Payable via <strong class="text-white" x-text="selectedCurrency"></strong> (<span class="text-pink-300" x-text="selectedNetwork"></span>)
                    </p>
                </div>

                <div class="w-full sm:w-auto">
                    <button 
                        type="submit" 
                        class="w-full sm:w-auto bg-[#F20B83] hover:bg-[#d90858] text-white px-8 py-3.5 rounded-xl text-sm font-black flex items-center justify-center gap-2 shadow-lg shadow-[#F20B83]/30 transition transform hover:scale-[1.02]"
                    >
                        <span>Generate Crypto Invoice</span>
                        <span>&rarr;</span>
                    </button>
                </div>
            </div>

        </form>

        <form id="wallet-credits-boost-form" action="{{ route('boost.pay-with-credits', $listing->id) }}" method="POST" class="hidden">
            @csrf
        </form>

    </div>
</x-layouts.app>

