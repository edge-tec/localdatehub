<x-layouts.app title="Cryptocurrency Payments — Admin" description="Manage crypto transactions and listing boost payments.">
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Breadcrumbs -->
        <div class="flex items-center space-x-2 text-xs text-slate-500">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-900 transition">Admin Dashboard</a>
            <span>/</span>
            <span class="text-slate-900 font-bold">Crypto Transactions</span>
        </div>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Cryptocurrency Payments</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Real-time audit log of blockchain deposits and automated post boosts</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.crypto.gateways.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl transition">
                    Configure Gateways
                </a>
                <a href="{{ route('admin.crypto.plans.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl transition">
                    Boost Plans
                </a>
            </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Total Volume</span>
                <div class="text-xl font-black text-slate-900 mt-1">
                    ${{ number_format((float) $stats['total_volume_usd'], 2) }}
                </div>
                <span class="text-[10px] text-emerald-600 font-semibold block mt-0.5">Completed Crypto Sales</span>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Completed Payments</span>
                <div class="text-xl font-black text-emerald-600 mt-1">
                    {{ number_format($stats['completed_count']) }}
                </div>
                <span class="text-[10px] text-slate-500 font-medium block mt-0.5">Boosts Activated</span>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Pending / Confirming</span>
                <div class="text-xl font-black text-amber-500 mt-1">
                    {{ number_format($stats['pending_count']) }}
                </div>
                <span class="text-[10px] text-slate-500 font-medium block mt-0.5">Awaiting Mempool</span>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Underpaid Alerts</span>
                <div class="text-xl font-black text-rose-500 mt-1">
                    {{ number_format($stats['underpaid_count']) }}
                </div>
                <span class="text-[10px] text-slate-500 font-medium block mt-0.5">Requires Top-Up</span>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs">
            <form method="GET" action="{{ route('admin.crypto.payments.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <div class="sm:col-span-5">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Search Order ID, Tx Hash, User Email, or Listing..." 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold focus:bg-white focus:outline-none focus:border-[#F20B83]"
                    />
                </div>

                <div class="sm:col-span-3">
                    <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:bg-white focus:outline-none focus:border-[#F20B83]">
                        <option value="">All Statuses</option>
                        <option value="COMPLETED" {{ request('status') === 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                        <option value="CONFIRMING" {{ request('status') === 'CONFIRMING' ? 'selected' : '' }}>Confirming</option>
                        <option value="AWAITING_PAYMENT" {{ request('status') === 'AWAITING_PAYMENT' ? 'selected' : '' }}>Awaiting Payment</option>
                        <option value="UNDERPAID" {{ request('status') === 'UNDERPAID' ? 'selected' : '' }}>Underpaid</option>
                        <option value="EXPIRED" {{ request('status') === 'EXPIRED' ? 'selected' : '' }}>Expired</option>
                        <option value="FAILED" {{ request('status') === 'FAILED' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <select name="gateway_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold focus:bg-white focus:outline-none focus:border-[#F20B83]">
                        <option value="">All Gateways</option>
                        @foreach($gateways as $gw)
                            <option value="{{ $gw->id }}" {{ request('gateway_id') == $gw->id ? 'selected' : '' }}>{{ $gw->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2 flex items-center gap-2">
                    <button type="submit" class="w-full bg-[#F20B83] hover:bg-[#d90858] text-white py-2 px-3 rounded-xl text-xs font-bold transition">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'status', 'gateway_id']))
                        <a href="{{ route('admin.crypto.payments.index') }}" class="text-xs text-slate-500 hover:text-slate-800 px-2 py-2">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
            @if($payments->isEmpty())
                <div class="p-10 text-center text-xs text-slate-500">
                    No cryptocurrency transactions match the selected criteria.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200 text-[10px]">
                            <tr>
                                <th class="py-3 px-4">Order ID</th>
                                <th class="py-3 px-4">User</th>
                                <th class="py-3 px-4">Listing</th>
                                <th class="py-3 px-4">Gateway</th>
                                <th class="py-3 px-4">Crypto / Net</th>
                                <th class="py-3 px-4">Amount</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Confirms</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4 text-right">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach($payments as $p)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                                        <a href="{{ route('admin.crypto.payments.show', $p->id) }}" class="hover:text-[#F20B83]">
                                            {{ $p->internal_order_id }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-900">{{ $p->user?->username ?? 'N/A' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $p->user?->email }}</div>
                                    </td>
                                    <td class="py-3 px-4 max-w-[160px] truncate">
                                        @if($p->listing)
                                            <a href="{{ route('listings.show', $p->listing->slug) }}" target="_blank" class="font-semibold text-slate-800 hover:underline">
                                                {{ $p->listing->title }}
                                            </a>
                                        @else
                                            <span class="text-slate-400 italic">Deleted</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-800">
                                        {{ $p->gateway?->name ?? 'Unknown' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-bold text-slate-900">{{ $p->payment_currency }}</span>
                                        <span class="text-[10px] text-slate-400 block font-mono">{{ $p->payment_network }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-mono font-extrabold text-slate-900">{{ $p->expected_amount }}</div>
                                        <div class="text-[10px] text-slate-500">${{ number_format((float) $p->fiat_amount, 2) }}</div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        @if($p->isCompleted())
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-700">
                                                Completed
                                            </span>
                                        @elseif($p->isConfirming())
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-blue-100 text-blue-700">
                                                Confirming
                                            </span>
                                        @elseif($p->status === 'UNDERPAID')
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-700">
                                                Underpaid
                                            </span>
                                        @elseif($p->isExpired())
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-slate-100 text-slate-500">
                                                Expired
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-700">
                                                {{ $p->status }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 font-mono font-semibold text-slate-600">
                                        {{ $p->confirmations }} / {{ $p->required_confirmations }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                                        {{ $p->created_at->format('M d, H:i') }}
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <a href="{{ route('admin.crypto.payments.show', $p->id) }}" class="text-xs font-bold text-[#F20B83] hover:underline">
                                            Manage &rarr;
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

