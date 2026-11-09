<x-layouts.app title="Deposit Invoice #{{ $payment->internal_order_id }} — LocalDateHub">
    <div 
        class="py-8 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
        x-data="{
            status: '{{ $payment->status }}',
            isCompleted: {{ $payment->isCompleted() ? 'true' : 'false' }},
            isExpired: {{ $payment->isExpired() ? 'true' : 'false' }},
            copiedAddress: false,
            copiedAmount: false,
            checkingStatus: false,
            pollInterval: null,
            timerInterval: null,
            remainingMinutes: 30,
            remainingSeconds: 0,
            expiresAt: {{ $payment->expires_at?->timestamp ? $payment->expires_at->timestamp * 1000 : 'Date.now() + 1800000' }},

            updateCountdown() {
                const now = Date.now();
                const diff = Math.max(0, this.expiresAt - now);
                if (diff <= 0) {
                    this.isExpired = true;
                    this.status = 'EXPIRED';
                    clearInterval(this.timerInterval);
                    return;
                }
                this.remainingMinutes = Math.floor(diff / 60000);
                this.remainingSeconds = Math.floor((diff % 60000) / 1000);
            },

            copyText(text, type) {
                navigator.clipboard.writeText(text);
                if (type === 'address') {
                    this.copiedAddress = true;
                    setTimeout(() => this.copiedAddress = false, 2000);
                } else {
                    this.copiedAmount = true;
                    setTimeout(() => this.copiedAmount = false, 2000);
                }
            },

            async checkStatus(manual = false) {
                if (this.isCompleted || this.isExpired) return;
                if (manual) this.checkingStatus = true;
                try {
                    const res = await fetch('{{ route('account.wallet.status', $payment) }}');
                    const data = await res.json();
                    this.status = data.status;
                    this.isCompleted = data.is_completed;
                    this.isExpired = data.is_expired;

                    if (this.isCompleted) {
                        clearInterval(this.pollInterval);
                        clearInterval(this.timerInterval);
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 2000);
                    }
                } catch (e) {
                    console.error('Polling error', e);
                } finally {
                    if (manual) setTimeout(() => this.checkingStatus = false, 600);
                }
            },

            init() {
                this.updateCountdown();
                if (!this.isCompleted && !this.isExpired) {
                    this.pollInterval = setInterval(() => this.checkStatus(), 4000);
                    this.timerInterval = setInterval(() => this.updateCountdown(), 1000);
                }
            }
        }"
    >

        <!-- Breadcrumb -->
        <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-500">
            <a href="{{ route('account.wallet.index') }}" class="hover:text-slate-800 transition">Credit Wallet</a>
            <span>/</span>
            <span class="text-slate-900">Payment Invoice</span>
        </nav>

        <!-- Success Banner upon Completion -->
        <template x-if="isCompleted">
            <div class="bg-emerald-500 text-white rounded-3xl p-6 shadow-xl flex items-center space-x-4 animate-bounce">
                <div class="text-3xl">🎉</div>
                <div>
                    <h3 class="text-lg font-black">Payment Confirmed!</h3>
                    <p class="text-xs text-emerald-100">Credits have been added to your wallet. Redirecting to your wallet overview...</p>
                </div>
            </div>
        </template>

        <!-- Main Invoice Card -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <!-- Header with Order Reference and Status Badge -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Crypto Deposit Invoice</span>
                    <h1 class="text-xl font-mono font-bold text-slate-900">{{ $payment->internal_order_id }}</h1>
                </div>

                <div>
                    <template x-if="status === 'COMPLETED'">
                        <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-700">✓ COMPLETED</span>
                    </template>
                    <template x-if="status === 'CONFIRMING'">
                        <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-blue-100 text-blue-700 animate-pulse">CONFIRMING...</span>
                    </template>
                    <template x-if="status === 'AWAITING_PAYMENT' || status === 'PENDING'">
                        <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                            <span>Awaiting Payment</span>
                        </span>
                    </template>
                    <template x-if="status === 'EXPIRED'">
                        <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-slate-100 text-slate-600">EXPIRED</span>
                    </template>
                </div>
            </div>

            <!-- Item Summary -->
            <div class="bg-slate-50 rounded-2xl p-4 flex items-center justify-between text-xs">
                <div>
                    <span class="text-slate-500 block">Item:</span>
                    <strong class="text-sm font-bold text-slate-900">{{ $payment->creditPackage?->name }}</strong>
                    <span class="text-emerald-600 font-bold block text-[11px]">
                        +{{ number_format(($payment->creditPackage?->credits ?? 0) + ($payment->creditPackage?->bonus_credits ?? 0)) }} Credits
                    </span>
                </div>
                <div class="text-right">
                    <span class="text-slate-500 block">Fiat Total:</span>
                    <strong class="text-base font-black text-slate-900">${{ number_format($payment->fiat_amount, 2) }} USD</strong>
                </div>
            </div>

            <!-- QR Code Section -->
            <div class="flex flex-col items-center justify-center p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
                <div class="w-48 h-48 bg-white p-3 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-center">
                    <img 
                        src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($payment->payment_address) }}" 
                        alt="Deposit QR Code"
                        class="w-full h-full object-contain"
                    />
                </div>
                <p class="text-[11px] text-slate-600 font-bold mt-2 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Scan with Binance, Bybit, Trust Wallet or any exchange
                </p>
            </div>

            <!-- Address & Amount Copy Boxes -->
            <div class="space-y-3">
                <!-- Amount -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Send Exact Crypto Amount:</label>
                    <div class="flex items-center space-x-2">
                        <div class="flex-grow bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 font-mono text-sm font-black text-slate-900 select-all">
                            {{ $payment->expected_amount }} {{ $payment->payment_currency }}
                        </div>
                        <button 
                            type="button" 
                            @click="copyText('{{ $payment->expected_amount }}', 'amount')"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700 transition shrink-0"
                            x-text="copiedAmount ? 'Copied! ✓' : 'Copy'"
                        >
                            Copy
                        </button>
                    </div>
                </div>

                <!-- Address -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Send to Address (<span class="text-[#F20B83] font-bold">{{ $payment->payment_network }} Network Only</span>):
                    </label>
                    <div class="flex items-center space-x-2">
                        <div class="flex-grow bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 font-mono text-xs font-bold text-slate-900 break-all select-all">
                            {{ $payment->payment_address }}
                        </div>
                        <button 
                            type="button" 
                            @click="copyText('{{ $payment->payment_address }}', 'address')"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700 transition shrink-0"
                            x-text="copiedAddress ? 'Copied! ✓' : 'Copy'"
                        >
                            Copy
                        </button>
                    </div>
                </div>
            </div>

            <!-- Live Status Polling Banner & Manual Check -->
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="font-semibold text-slate-700">Listening for blockchain confirmation...</span>
                    <span class="text-slate-400 font-mono text-[11px]">(Expires in: <strong class="text-rose-600" x-text="`${remainingMinutes}m ${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}s`"></strong>)</span>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button 
                        type="button" 
                        @click="checkStatus(true)" 
                        :disabled="checkingStatus"
                        class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white font-bold text-xs flex items-center gap-1.5 transition shadow-xs"
                    >
                        <span x-show="!checkingStatus">⚡ Check Status Now</span>
                        <span x-show="checkingStatus">Verifying On-Chain...</span>
                    </button>
                    <a href="{{ route('account.wallet.index') }}" class="font-bold text-[#F20B83] hover:underline px-2 py-1">
                        Back to Wallet
                    </a>
                </div>
            </div>
        </div>

    </div>
</x-layouts.app>

