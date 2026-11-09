<x-layouts.app title="Transaction #{{ $payment->internal_order_id }} — Admin" description="View cryptocurrency payment details, blockchain tx, and webhook events.">
    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Breadcrumbs -->
        <div class="flex items-center space-x-2 text-xs text-slate-500">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-900 transition">Admin Dashboard</a>
            <span>/</span>
            <a href="{{ route('admin.crypto.payments.index') }}" class="hover:text-slate-900 transition">Crypto Payments</a>
            <span>/</span>
            <span class="text-slate-900 font-bold">#{{ $payment->internal_order_id }}</span>
        </div>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Internal Order ID</span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight font-mono">{{ $payment->internal_order_id }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <form action="{{ route('admin.crypto.payments.sync', $payment->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl transition flex items-center gap-1.5">
                        <span>↻ Check Status with Provider</span>
                    </button>
                </form>

                <a href="{{ route('boost.invoice', $payment->internal_order_id) }}" target="_blank" class="px-4 py-2 bg-[#F20B83] hover:bg-[#d90858] text-white text-xs font-bold rounded-xl transition shadow-xs">
                    View Public Invoice Screen &rarr;
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold">
                ✓ {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Left: Transaction Summary -->
            <div class="md:col-span-2 space-y-6">
                <!-- Payment Overview -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
                    <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-2">
                        Payment Breakdown
                    </h2>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Status</span>
                            <span class="font-extrabold text-sm text-[#F20B83]">{{ $payment->status }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Cryptocurrency</span>
                            <span class="font-bold text-slate-900">{{ $payment->payment_currency }}</span>
                            <span class="text-[10px] text-slate-400 block font-mono">Net: {{ $payment->payment_network }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Payment Gateway</span>
                            <span class="font-bold text-slate-900">{{ $payment->gateway?->name ?? 'Unknown' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Expected Amount</span>
                            <span class="font-mono font-bold text-slate-900">{{ $payment->expected_amount }}</span>
                            <span class="text-[10px] text-slate-400 block">≈ ${{ number_format((float) $payment->fiat_amount, 2) }} USD</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Received Amount</span>
                            <span class="font-mono font-bold text-slate-900">{{ $payment->received_amount }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Confirmations</span>
                            <span class="font-mono font-bold text-slate-900">{{ $payment->confirmations }} / {{ $payment->required_confirmations }}</span>
                        </div>
                    </div>

                    @if($payment->payment_address)
                        <div class="pt-3 border-t border-slate-100 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Deposit Address</span>
                            <div class="font-mono text-xs font-bold text-slate-800 bg-slate-50 p-2.5 rounded-xl border border-slate-200 select-all break-all">
                                {{ $payment->payment_address }}
                            </div>
                        </div>
                    @endif

                    @if($payment->transaction_hash)
                        <div class="pt-3 border-t border-slate-100 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Transaction Hash</span>
                            <div class="font-mono text-xs text-slate-800 bg-slate-50 p-2.5 rounded-xl border border-slate-200 break-all flex items-center justify-between">
                                <span>{{ $payment->transaction_hash }}</span>
                                @if($payment->getExplorerUrl())
                                    <a href="{{ $payment->getExplorerUrl() }}" target="_blank" class="text-xs font-bold text-blue-600 hover:underline shrink-0 ml-2">
                                        View on Explorer &rarr;
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Webhook Events History -->
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
                    <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-2">
                        Inbound Webhook Events Audit Trail ({{ $webhookEvents->count() }})
                    </h2>

                    @if($webhookEvents->isEmpty())
                        <p class="text-xs text-slate-400 italic">No webhook calls received yet for this transaction.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($webhookEvents as $ev)
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-200 text-xs space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-slate-800">
                                            Event: <code class="text-[#F20B83]">{{ $ev->event_type }}</code>
                                        </span>
                                        <span class="text-[10px] text-slate-400">{{ $ev->created_at->format('M d, H:i:s') }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[10px]">
                                        <span>Signature: 
                                            @if($ev->signature_verified)
                                                <strong class="text-emerald-600">✓ Verified</strong>
                                            @else
                                                <strong class="text-rose-600">✕ Invalid</strong>
                                            @endif
                                        </span>
                                        <span>•</span>
                                        <span>Status: <strong class="text-slate-800 uppercase">{{ $ev->processing_status }}</strong></span>
                                    </div>
                                    @if($ev->error_message)
                                        <p class="text-[11px] text-rose-600 font-semibold">{{ $ev->error_message }}</p>
                                    @endif
                                    <details class="text-[10px] text-slate-500 pt-1">
                                        <summary class="cursor-pointer font-bold hover:underline">View Raw JSON Payload</summary>
                                        <pre class="bg-slate-900 text-slate-100 p-2 rounded-lg mt-1 overflow-x-auto text-[10px]">{{ json_encode($ev->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right: Associated Listing & Post Boost Record -->
            <div class="space-y-6">
                <!-- Boost Activation Details -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-3">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-2">
                        Post Boost Status
                    </h3>

                    @if($payment->boost)
                        <div class="space-y-2 text-xs">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-700">
                                Boost Activated
                            </span>
                            <div class="pt-2">
                                <span class="text-slate-400 block text-[10px]">Active Duration</span>
                                <span class="font-bold text-slate-900">
                                    {{ $payment->boost->starts_at->format('M d, Y') }} &rarr; {{ $payment->boost->expires_at->format('M d, Y') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">Selected Plan</span>
                                <span class="font-bold text-slate-800">{{ $payment->boostPlan?->name }}</span>
                            </div>
                        </div>
                    @else
                        <div class="text-xs text-slate-500">
                            No boost active yet. Boost will activate automatically once verified payment reaches required confirmations.
                        </div>
                    @endif
                </div>

                <!-- Listing Preview -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-2 text-xs">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-2">
                        Target Listing
                    </h3>

                    @if($payment->listing)
                        <div class="space-y-1">
                            <a href="{{ route('listings.show', $payment->listing->slug) }}" target="_blank" class="font-bold text-slate-900 hover:text-[#F20B83] text-sm block">
                                {{ $payment->listing->title }}
                            </a>
                            <p class="text-slate-500 text-[11px]">
                                {{ $payment->listing->city?->name }}, {{ $payment->listing->state?->code }}
                            </p>
                        </div>
                    @else
                        <p class="text-slate-400 italic">Target listing has been deleted.</p>
                    @endif
                </div>

                <!-- Customer Info -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-2 text-xs">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-2">
                        Customer Account
                    </h3>

                    @if($payment->user)
                        <div>
                            <div class="font-bold text-slate-900">{{ $payment->user->username }}</div>
                            <div class="text-slate-500">{{ $payment->user->email }}</div>
                            <span class="text-[10px] text-slate-400 block mt-1">User ID: #{{ $payment->user->id }}</span>
                        </div>
                    @else
                        <span class="text-slate-400 italic">Guest / Deleted User</span>
                    @endif
                </div>
            </div>

        </div>

    </div>
</x-layouts.app>

