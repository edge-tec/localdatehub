<x-layouts.app title="Credit Wallet & Packages Management — Admin">
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8" x-data="{ activeTab: 'packages', editModal: false, editPkg: { id: null, name: '', credits: 0, bonus_credits: 0, price_usd: '0.00', display_order: 1, is_popular: false, is_active: true } }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-800 transition">Admin Dashboard</a>
                    <span>/</span>
                    <span class="text-slate-900">Wallet &amp; Packages</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    Credit Wallet &amp; Ledger Management
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                    Manage credit purchase bundles, review system-wide financial transactions, and perform authorized manual credit adjustments.
                </p>
            </div>

            <!-- Tab Switcher -->
            <div class="flex items-center space-x-2 bg-slate-100 p-1 rounded-2xl text-xs font-bold text-slate-700">
                <button 
                    type="button" 
                    @click="activeTab = 'packages'" 
                    class="px-4 py-2 rounded-xl transition"
                    :class="activeTab === 'packages' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800'"
                >
                    Credit Packages
                </button>
                <button 
                    type="button" 
                    @click="activeTab = 'adjustment'" 
                    class="px-4 py-2 rounded-xl transition"
                    :class="activeTab === 'adjustment' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800'"
                >
                    Manual Adjustment
                </button>
                <button 
                    type="button" 
                    @click="activeTab = 'ledger'" 
                    class="px-4 py-2 rounded-xl transition"
                    :class="activeTab === 'ledger' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800'"
                >
                    Global Ledger
                </button>
            </div>
        </div>

        <!-- 4-Stat Overview Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Wallets</span>
                <span class="text-2xl font-black text-slate-900 mt-1 block">{{ number_format($stats['total_wallets']) }}</span>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Circulating Credits</span>
                <span class="text-2xl font-black text-[#F20B83] mt-1 block">{{ number_format($stats['total_circulating_credits']) }}</span>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Purchased Credits</span>
                <span class="text-2xl font-black text-emerald-600 mt-1 block">{{ number_format($stats['total_purchased_credits']) }}</span>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Credits Spent on Ads</span>
                <span class="text-2xl font-black text-slate-700 mt-1 block">{{ number_format($stats['total_spent_credits']) }}</span>
            </div>
        </div>

        <!-- TAB 1: Credit Packages Management -->
        <div x-show="activeTab === 'packages'" class="space-y-6">
            <!-- Create Package Form -->
            <form action="{{ route('admin.wallet.packages.store') }}" method="POST" class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
                @csrf
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-base font-black text-slate-900">Create New Credit Package</h2>
                    <p class="text-xs text-slate-500">Configure bundles offered to posters on checkout.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-6 gap-3 text-xs">
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Package Name *:</label>
                        <input type="text" name="name" placeholder="e.g. Starter Pack" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Credits *:</label>
                        <input type="number" name="credits" placeholder="e.g. 50" min="1" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Bonus Credits:</label>
                        <input type="number" name="bonus_credits" value="0" min="0" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Price (USD) *:</label>
                        <input type="number" step="0.01" name="price_usd" placeholder="e.g. 4.99" min="0.5" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Display Order:</label>
                        <input type="number" name="display_order" value="1" min="1" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex items-center space-x-4 text-xs font-bold text-slate-700">
                        <label class="flex items-center space-x-1.5 cursor-pointer">
                            <input type="checkbox" name="is_popular" value="1" class="rounded text-[#F20B83]">
                            <span>Mark as 'Popular'</span>
                        </label>
                        <label class="flex items-center space-x-1.5 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded text-[#F20B83]">
                            <span>Active in Checkout</span>
                        </label>
                    </div>

                    <button type="submit" class="bg-[#F20B83] hover:bg-[#d90858] text-white px-6 py-2 rounded-xl text-xs font-bold transition">
                        Create Package
                    </button>
                </div>
            </form>

            <!-- Packages Table -->
            <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-600">
                            <tr>
                                <th class="px-5 py-3">Order</th>
                                <th class="px-5 py-3">Package Name</th>
                                <th class="px-5 py-3">Base Credits</th>
                                <th class="px-5 py-3">Bonus</th>
                                <th class="px-5 py-3">Price (USD)</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @foreach($packages as $pkg)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-5 py-3.5 font-mono text-slate-400">{{ $pkg->display_order }}</td>
                                    <td class="px-5 py-3.5 font-bold text-slate-900">
                                        {{ $pkg->name }}
                                        @if($pkg->is_popular)
                                            <span class="ml-2 px-2 py-0.5 rounded-full text-[9px] font-black bg-pink-100 text-[#F20B83]">Popular</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 font-black text-slate-800">{{ number_format($pkg->credits) }}</td>
                                    <td class="px-5 py-3.5 text-emerald-600 font-bold">+{{ number_format($pkg->bonus_credits) }}</td>
                                    <td class="px-5 py-3.5 font-bold text-slate-900">${{ number_format($pkg->price_usd, 2) }}</td>
                                    <td class="px-5 py-3.5">
                                        <form action="{{ route('admin.wallet.packages.toggle', $pkg) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black transition {{ $pkg->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-700 hover:bg-slate-300' }}" title="Click to {{ $pkg->is_active ? 'Turn Off / Hide from Checkout' : 'Turn On / Enable for Checkout' }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $pkg->is_active ? 'bg-emerald-600' : 'bg-slate-500' }}"></span>
                                                {{ $pkg->is_active ? 'Active (Turn Off)' : 'Disabled (Turn On)' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-5 py-3.5 text-right space-x-2">
                                        <button 
                                            type="button" 
                                            @click="editPkg = { id: {{ $pkg->id }}, name: '{{ addslashes($pkg->name) }}', credits: {{ $pkg->credits }}, bonus_credits: {{ $pkg->bonus_credits }}, price_usd: '{{ $pkg->price_usd }}', display_order: {{ $pkg->display_order }}, is_popular: {{ $pkg->is_popular ? 'true' : 'false' }}, is_active: {{ $pkg->is_active ? 'true' : 'false' }} }; editModal = true;"
                                            class="text-indigo-600 hover:underline font-bold text-[11px]"
                                        >
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.wallet.packages.delete', $pkg) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this package?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:underline font-bold text-[11px]">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: Manual Credit/Debit Adjustment -->
        <div x-show="activeTab === 'adjustment'" class="space-y-6">
            <form action="{{ route('admin.wallet.adjustment') }}" method="POST" class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6 max-w-2xl">
                @csrf
                <div>
                    <h2 class="text-lg font-black text-slate-900">Authorized Manual Balance Adjustment</h2>
                    <p class="text-xs text-slate-500">Every manual credit or debit is permanently audited with administrator credentials and mandatory justification.</p>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">User ID *:</label>
                        <input type="number" name="user_id" placeholder="Enter target user ID" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 font-bold text-slate-900">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Adjustment Action *:</label>
                        <select name="action" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 font-bold text-slate-900">
                            <option value="credit">Credit Balance (Add Credits to User)</option>
                            <option value="debit">Debit Balance (Deduct Credits from User)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Credit Amount *:</label>
                        <input type="number" name="amount" placeholder="e.g. 50" min="1" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 font-bold text-slate-900">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Reason / Justification *:</label>
                        <textarea name="reason" rows="3" placeholder="Provide reason for adjustment (e.g. customer support goodwill, payment reconciliation, promotional grant)" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs"></textarea>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-8 py-2.5 rounded-full text-xs font-bold transition">
                        Execute Adjustment
                    </button>
                </div>
            </form>
        </div>

        <!-- TAB 3: Global Wallet Transaction Ledger -->
        <div x-show="activeTab === 'ledger'" class="space-y-6">
            <!-- Filter Bar -->
            <form action="{{ route('admin.wallet.index') }}" method="GET" class="bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs flex flex-wrap items-center gap-3 text-xs">
                <input type="hidden" name="tab" value="ledger">
                <div class="flex-grow max-w-sm">
                    <input type="text" name="user_search" value="{{ request('user_search') }}" placeholder="Search by name, email or username..." class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs">
                </div>
                <div>
                    <select name="type" class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold">
                        <option value="">All Transaction Types</option>
                        <option value="CREDIT_PURCHASE" {{ request('type') === 'CREDIT_PURCHASE' ? 'selected' : '' }}>CREDIT_PURCHASE</option>
                        <option value="POST_CREATION_DEBIT" {{ request('type') === 'POST_CREATION_DEBIT' ? 'selected' : '' }}>POST_CREATION_DEBIT</option>
                        <option value="POST_BOOST_DEBIT" {{ request('type') === 'POST_BOOST_DEBIT' ? 'selected' : '' }}>POST_BOOST_DEBIT</option>
                        <option value="REFUND" {{ request('type') === 'REFUND' ? 'selected' : '' }}>REFUND</option>
                        <option value="ADMIN_CREDIT" {{ request('type') === 'ADMIN_CREDIT' ? 'selected' : '' }}>ADMIN_CREDIT</option>
                        <option value="ADMIN_DEBIT" {{ request('type') === 'ADMIN_DEBIT' ? 'selected' : '' }}>ADMIN_DEBIT</option>
                    </select>
                </div>
                <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-bold">Filter</button>
                @if(request()->hasAny(['user_search', 'type']))
                    <a href="{{ route('admin.wallet.index') }}" class="text-xs text-slate-500 hover:text-slate-800">Clear</a>
                @endif
            </form>

            <!-- Ledger Table -->
            <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-600">
                            <tr>
                                <th class="px-5 py-3">Reference / Date</th>
                                <th class="px-5 py-3">User</th>
                                <th class="px-5 py-3">Type</th>
                                <th class="px-5 py-3">Description</th>
                                <th class="px-5 py-3 text-right">Amount</th>
                                <th class="px-5 py-3 text-right">Balance After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($transactions as $tx)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-5 py-3.5">
                                        <div class="font-mono font-bold text-slate-900 text-[11px]">{{ $tx->reference }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $tx->created_at->format('M d, Y · h:i A') }}</div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-slate-900">{{ $tx->user?->name }}</div>
                                        <div class="text-[10px] text-slate-400">ID: {{ $tx->user_id }} · {{ $tx->user?->email }}</div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $tx->amount > 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                            {{ str_replace('_', ' ', $tx->type) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 max-w-xs truncate">
                                        {{ $tx->description }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-black {{ $tx->amount > 0 ? 'text-emerald-600' : 'text-slate-900' }}">
                                        {{ $tx->amount > 0 ? '+' . number_format($tx->amount) : number_format($tx->amount) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-800">
                                        {{ number_format($tx->balance_after) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-slate-400 italic">No transactions found matching criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div class="p-4 border-t border-slate-100">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Edit Package Modal -->
        <div 
            x-show="editModal" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
            role="dialog"
            aria-modal="true"
        >
            <div 
                @click.away="editModal = false"
                class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-5"
            >
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-black text-slate-900">Edit Credit Package</h3>
                    <button type="button" @click="editModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                </div>

                <form :action="'{{ url('admin/wallet/packages') }}/' + editPkg.id" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Package Name *:</label>
                        <input type="text" name="name" x-model="editPkg.name" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Base Credits *:</label>
                            <input type="number" name="credits" x-model="editPkg.credits" min="1" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900 font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Bonus Credits (Free):</label>
                            <input type="number" name="bonus_credits" x-model="editPkg.bonus_credits" min="0" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900 font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Price (USD) *:</label>
                            <input type="number" step="0.01" min="0.5" name="price_usd" x-model="editPkg.price_usd" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900 font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Display Order *:</label>
                            <input type="number" name="display_order" x-model="editPkg.display_order" min="1" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900 font-mono">
                        </div>
                    </div>

                    <div class="space-y-2 pt-2 border-t border-slate-100 font-bold text-slate-700">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="is_popular" value="1" x-model="editPkg.is_popular" class="rounded text-[#F20B83]">
                            <span>Mark as 'Most Popular'</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="editPkg.is_active" class="rounded text-[#F20B83]">
                            <span>Active in Checkout (Visible to users)</span>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="editModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                            Cancel
                        </button>
                        <button type="submit" class="bg-[#F20B83] hover:bg-[#d90858] text-white px-6 py-2 rounded-xl text-xs font-bold transition shadow-xs">
                            Update Package
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.app>

