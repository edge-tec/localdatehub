<x-layouts.app title="Crypto Payment Invoice — {{ $payment->internal_order_id }}" description="Complete your cryptocurrency payment for LocalDateHub post boost.">
    <div 
        class="py-10 max-w-2xl mx-auto px-4 sm:px-6 space-y-6"
        x-data="{
            status: '{{ $payment->status }}',
            confirmations: {{ $payment->confirmations }},
            requiredConfirmations: {{ $payment->required_confirmations }},
            isCompleted: {{ $payment->isCompleted() ? 'true' : 'false' }},
            isExpired: {{ $payment->isExpired() ? 'true' : 'false' }},
            copiedAddress: false,
            copiedAmount: false,
            secondsLeft: Math.max(0, Math.floor(({{ $payment->expires_at->timestamp }} - Date.now() / 1000))),

            copyAddress(text) {
                navigator.clipboard.writeText(text);
                this.copiedAddress = true;
                setTimeout(() => this.copiedAddress = false, 2500);
            },

            copyAmount(text) {
                navigator.clipboard.writeText(text);
                this.copiedAmount = true;
                setTimeout(() => this.copiedAmount = false, 2500);
            },

            formatTime(secs) {
                const m = Math.floor(secs / 60);
                const s = secs % 60;
                return m + ':' + (s < 10 ? '0' : '') + s;
            },

            init() {
                // Countdown timer
                const timer = setInterval(() => {
                    if (this.secondsLeft > 0) {
                        this.secondsLeft--;
                    } else if (!this.isCompleted) {
                        this.isExpired = true;
                        this.status = 'EXPIRED';
                        clearInterval(timer);
                    }
                }, 1000);

                // Polling payment status every 4 seconds
                const poller = setInterval(async () => {
                    if (this.isCompleted || this.isExpired) {
                        clearInterval(poller);
                        return;
                    }

                    try {
                        const res = await fetch('{{ route('boost.status', $payment->internal_order_id) }}');
                        const data = await res.json();
                        if (data.status) {
                            this.status = data.status;
                            this.confirmations = data.confirmations;
                            if (data.is_completed) {
                                this.isCompleted = true;
                                clearInterval(poller);
                                setTimeout(() => {
                                    window.location.href = data.redirect_url || '{{ route('account.listings.index', ['boost_success' => 1]) }}';
                                }, 2000);
                            }
                        }
                    } catch(e) {}
                }, 4000);
            }
        }"
    >
        <!-- Header -->
        <div class="text-center space-y-2">
            <span class="text-[10px] font-black uppercase tracking-wider text-white bg-[#F20B83] px-3 py-1 rounded-full shadow-xs">
                Crypto Checkout • Order #{{ $payment->internal_order_id }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Complete Your Payment
            </h1>
            <p class="text-xs sm:text-sm text-slate-500">
                Send the exact amount to the deposit address below to activate your post boost.
            </p>
        </div>

        <!-- Success Banner when payment completes -->
        <div x-show="isCompleted" x-cloak class="bg-emerald-50 border border-emerald-300 rounded-2xl p-6 text-center space-y-2 shadow-sm animate-pulse">
            <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto text-xl font-black">
                ✓
            </div>
            <h2 class="text-lg font-black text-emerald-900">Payment Confirmed & Boost Activated!</h2>
            <p class="text-xs text-emerald-700">Your post is now elevated and spotlighted. Redirecting to your dashboard...</p>
        </div>

        <!-- Invoice Card -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">

            <!-- Status Indicator Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span 
                        class="w-3 h-3 rounded-full"
                        :class="{
                            'bg-amber-400 animate-ping': status === 'AWAITING_PAYMENT' || status === 'PENDING',
                            'bg-blue-500 animate-pulse': status === 'CONFIRMING',
                            'bg-emerald-500': isCompleted,
                            'bg-rose-500': isExpired || status === 'FAILED'
                        }"
                    ></span>
                    <span class="text-xs font-black uppercase tracking-wider text-slate-800" x-text="status.replace('_', ' ')"></span>
                </div>

                <!-- Expiration Countdown -->
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Time Remaining</span>
                    <span class="text-xs font-mono font-black text-[#F20B83]" x-text="formatTime(secondsLeft)"></span>
                </div>
            </div>

            <!-- Amount Due -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-center relative">
                <span class="text-[10px] uppercase tracking-wider font-extrabold text-slate-400">Total Amount Due</span>
                <div class="text-2xl sm:text-3xl font-black text-slate-900 mt-1 flex items-center justify-center gap-2">
                    <span>{{ $payment->expected_amount }}</span>
                    <span class="text-sm font-extrabold text-[#F20B83]">{{ $payment->payment_currency }}</span>
                </div>
                <div class="text-xs text-slate-500 mt-0.5">
                    ≈ ${{ number_format((float) $payment->fiat_amount, 2) }} {{ $payment->fiat_currency }}
                </div>

                <button 
                    type="button" 
                    @click="copyAmount('{{ $payment->expected_amount }}')"
                    class="mt-2 text-[11px] font-bold text-[#F20B83] hover:underline flex items-center justify-center gap-1 mx-auto"
                >
                    <span x-text="copiedAmount ? '✓ Copied Amount!' : 'Copy Exact Amount'"></span>
                </button>
            </div>

            <!-- QR Code Container -->
            <div class="flex flex-col items-center justify-center p-4 bg-white border border-slate-100 rounded-2xl">
                @if($payment->payment_address)
                    <div class="p-3 bg-white border border-slate-200 rounded-2xl shadow-sm">
                        <!-- High resolution dynamically generated QR code (Direct address compatible with Binance, Bybit, Trust Wallet) -->
                        <img 
                            src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($payment->payment_address) }}&margin=0" 
                            alt="Payment QR Code" 
                            class="w-40 h-40 sm:w-48 sm:h-48 rounded-lg"
                        />
                    </div>
                    <span class="text-[11px] text-slate-500 mt-2 font-bold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Scan with Binance, Bybit, Trust Wallet or any crypto app
                    </span>
                @elseif($payment->payment_url)
                    <div class="p-6 text-center space-y-3">
                        <p class="text-xs text-slate-600">This gateway uses a hosted checkout portal.</p>
                        <a 
                            href="{{ $payment->payment_url }}" 
                            target="_blank" 
                            class="inline-flex items-center gap-2 bg-[#F20B83] text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-md hover:bg-[#d90858] transition"
                        >
                            <span>Open {{ $gateway->name }} Checkout</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                @endif
            </div>

            <!-- Deposit Address with Click-to-Copy -->
            @if($payment->payment_address)
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-extrabold uppercase tracking-wider text-slate-700">
                            {{ $payment->payment_currency }} Deposit Address
                        </label>
                        <span class="text-[10px] font-bold text-slate-400">Network: <strong class="text-slate-800">{{ $payment->payment_network }}</strong></span>
                    </div>

                    <div class="relative flex items-center">
                        <input 
                            type="text" 
                            readonly 
                            value="{{ $payment->payment_address }}" 
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-900 pr-24 select-all focus:outline-none"
                        />
                        <button 
                            type="button" 
                            @click="copyAddress('{{ $payment->payment_address }}')"
                            class="absolute right-1.5 top-1.5 bottom-1.5 px-3 bg-[#F20B83] hover:bg-[#d90858] text-white text-[11px] font-extrabold rounded-lg transition flex items-center gap-1 shadow-xs"
                        >
                            <span x-text="copiedAddress ? '✓ Copied' : 'Copy'"></span>
                        </button>
                    </div>

                    <!-- Critical Network Warning Banner -->
                    <div class="p-3 bg-pink-50/70 border border-pink-200/80 rounded-xl flex items-start gap-2 mt-2">
                        <svg class="w-4 h-4 text-[#F20B83] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="text-[11px] text-slate-700 leading-tight">
                            <strong>Notice:</strong> Please only send <strong>{{ $payment->payment_currency }}</strong> via the <strong>{{ $payment->payment_network }}</strong> network. Sending via an incorrect network may result in loss of funds.
                        </p>
                    </div>
                </div>
            @endif

            <!-- Confirmation Progress Indicator -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Confirmations: <strong class="text-slate-900" x-text="confirmations + ' / ' + requiredConfirmations"></strong></span>
                </div>
                <span>Gateway: <strong class="text-slate-800">{{ $gateway->name }}</strong></span>
            </div>

        </div>

        <!-- Back to My Listings -->
        <div class="text-center pt-2">
            <a href="{{ route('account.listings.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 transition">
                &larr; Back to My Listings
            </a>
        </div>

    </div>
</x-layouts.app>

