<x-layouts.app title="My Payment History — LocalDateHub" description="View your cryptocurrency payments and post promotion transactions on LocalDateHub.">
    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Breadcrumbs -->
        <div class="flex items-center space-x-2 text-xs text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-slate-900 transition">Home</a>
            <span>/</span>
            <a href="{{ route('account.profile') }}" class="hover:text-slate-900 transition">My Account</a>
            <span>/</span>
            <span class="text-slate-900 font-bold">Payment History</span>
        </div>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Cryptocurrency Payment History</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Audit log of your listing boosts and crypto transactions</p>
            </div>
            <a href="{{ route('account.listings.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-800 transition self-start">
                &larr; Manage Listings
            </a>
        </div>

        <!-- Payments Table -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
            @if($payments->isEmpty())
                <div class="p-12 text-center space-y-3">
                    <div class="w-12 h-12 rounded-full bg-pink-50 text-[#F20B83] text-xl font-black flex items-center justify-center mx-auto">
                        ₿
                    </div>
                    <h3 class="text-base font-bold text-slate-800">No payment records yet</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">
                        When you boost your classified listings using cryptocurrency, your invoices and blockchain confirmations will appear here.
                    </p>
                    <a href="{{ route('account.listings.index') }}" class="inline-block mt-2 bg-[#F20B83] text-white text-xs font-bold px-5 py-2.5 rounded-xl hover:bg-[#d90858] transition shadow-xs">
                        View My Listings to Boost
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200 text-[10px]">
                            <tr>
                                <th class="py-3 px-4">Order ID</th>
                                <th class="py-3 px-4">Listing</th>
                                <th class="py-3 px-4">Boost Plan</th>
                                <th class="py-3 px-4">Crypto / Network</th>
                                <th class="py-3 px-4">Amount</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Tx Hash</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4 text-right">Invoice</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach($payments as $p)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3.5 px-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                                        {{ $p->internal_order_id }}
                                    </td>
                                    <td class="py-3.5 px-4 max-w-[180px] truncate">
                                        @if($p->listing)
                                            <a href="{{ route('listings.show', $p->listing->slug) }}" class="font-bold text-slate-900 hover:text-[#F20B83] transition">
                                                {{ $p->listing->title }}
                                            </a>
                                        @else
                                            <span class="text-slate-400 italic">Deleted listing</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 font-semibold text-slate-800">
                                        {{ $p->boostPlan?->name ?? 'Custom Boost' }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900">{{ $p->payment_currency }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $p->payment_network }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-mono font-extrabold text-slate-900">{{ $p->expected_amount }}</div>
                                        <div class="text-[10px] text-slate-500">${{ number_format((float) $p->fiat_amount, 2) }} USD</div>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @if($p->isCompleted())
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-700">
                                                ✓ Completed
                                            </span>
                                        @elseif($p->isConfirming())
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-blue-100 text-blue-700">
                                                Confirming ({{ $p->confirmations }})
                                            </span>
                                        @elseif($p->isExpired())
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-slate-100 text-slate-500">
                                                Expired
                                            </span>
                                        @elseif($p->status === 'UNDERPAID')
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-700">
                                                Underpaid
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-pink-100 text-[#F20B83]">
                                                {{ $p->status }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-[11px] max-w-[120px] truncate">
                                        @if($p->transaction_hash)
                                            @if($p->getExplorerUrl())
                                                <a href="{{ $p->getExplorerUrl() }}" target="_blank" class="text-blue-600 hover:underline">
                                                    {{ Str::limit($p->transaction_hash, 12) }}
                                                </a>
                                            @else
                                                <span title="{{ $p->transaction_hash }}">{{ Str::limit($p->transaction_hash, 10) }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                        {{ $p->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <a href="{{ route('boost.invoice', $p->internal_order_id) }}" class="text-[11px] font-bold text-[#F20B83] hover:underline">
                                            View &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($payments->hasPages())
                    <div class="p-4 border-t border-slate-100">
                        {{ $payments->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</x-layouts.app>

