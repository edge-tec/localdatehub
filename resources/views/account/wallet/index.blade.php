<x-layouts.app title="My Credit Wallet — LocalDateHub">
    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        <!-- Top Header & Breadcrumb -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                    <a href="{{ route('account.dashboard') }}" class="hover:text-slate-800 transition">My Account</a>
                    <span>/</span>
                    <span class="text-slate-900">Credit Wallet</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    My Credit Wallet &amp; Posting Quotas
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                    Manage your posting credits, track immutable ledger transactions, and review your monthly posting allowance.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <a 
                    href="#packages-section" 
                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl bg-gradient-to-r from-[#F20B83] to-rose-600 hover:from-[#d90858] hover:to-rose-700 text-white px-4 py-2.5 text-xs font-bold shadow-sm hover:shadow-md transition"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/></svg>
                    <span>Add Credits</span>
                </a>
            </div>
        </div>

        <!-- 3-Column Balance & Quota Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- 1. Available Wallet Balance -->
            <div class="bg-gradient-to-br from-white via-pink-50/20 to-pink-50/40 border border-pink-200/80 rounded-3xl p-6 shadow-sm relative overflow-hidden flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-wider text-[#F20B83]">Available Balance</span>
                    <div class="w-9 h-9 rounded-xl bg-pink-100/80 text-[#F20B83] flex items-center justify-center shadow-2xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                </div>

                <div class="my-4">
                    <div class="text-4xl sm:text-5xl font-black text-slate-900 tracking-tight">
                        {{ number_format($wallet->balance) }}
                        <span class="text-base font-bold text-slate-500">Credits</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        1 Credit = 1 Micro-post unit. Credits never expire.
                    </p>
                </div>

                <div class="pt-4 border-t border-pink-200/50 flex items-center justify-between text-xs font-semibold text-slate-600">
                    <span>Total Purchased: <strong>{{ number_format($wallet->total_purchased) }}</strong></span>
                    <span>Total Spent: <strong>{{ number_format($wallet->total_spent) }}</strong></span>
                </div>
            </div>

            <!-- 2. Posting Quota & Allowance -->
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-500">Posting Allowance</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-slate-100 text-slate-700">
                        {{ ucfirst(str_replace('_', ' ', $quotaSummary['account_type'])) }}
                    </span>
                </div>

                <div class="my-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-600">Free Posts Remaining:</span>
                        <span class="text-base font-black text-emerald-600">
                            {{ $quotaSummary['is_unlimited'] ? 'Unlimited' : $quotaSummary['free_remaining'] . ' of ' . $quotaSummary['free_quota'] }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-600">Published This Month:</span>
                        <span class="text-xs font-bold text-slate-800">
                            {{ $quotaSummary['month_posts'] }} {{ $quotaSummary['monthly_limit'] ? '/ ' . $quotaSummary['monthly_limit'] : '' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-600">Published Today:</span>
                        <span class="text-xs font-bold text-slate-800">
                            {{ $quotaSummary['today_posts'] }} {{ $quotaSummary['daily_limit'] ? '/ ' . $quotaSummary['daily_limit'] : '' }}
                        </span>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Quota resets on 1st of month</span>
                    @if($quotaSummary['can_post'])
                        <span class="text-emerald-600 font-bold flex items-center gap-1">● Posting Active</span>
                    @else
                        <span class="text-rose-600 font-bold flex items-center gap-1">● Quota Reached</span>
                    @endif
                </div>
            </div>

            <!-- 3. Identity Verification & Priority -->
            @php
                $walletUserVerified = $user->hasVerifiedIdentity();
                $walletVerificationLabel = $user->latestVerification?->statusLabel() ?? 'Unverified';
            @endphp
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-500">Trust &amp; Verification</span>
                    @if($walletUserVerified)
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 flex items-center gap-1">
                            ✓ Verified
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-600">
                            {{ $walletVerificationLabel }}
                        </span>
                    @endif
                </div>

                <div class="my-4">
                    @if($walletUserVerified)
                        <div class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <span class="text-blue-600">★</span>
                            <span>Verified Poster Priority Active</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Your personal listings receive an organic search rank boost and display the blue Verified Badge across the site.
                        </p>
                    @else
                        <div class="text-sm font-bold text-slate-800">
                            Unlock Verified Ranking Boost
                        </div>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Verify your identity with ID &amp; selfie to enjoy higher free posting quotas, automated approval, and listing search priority.
                        </p>
                    @endif
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <a 
                        href="{{ route('account.verification.show') }}" 
                        class="inline-flex items-center justify-between w-full px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-pink-50 text-slate-800 hover:text-[#F20B83] border border-slate-200 hover:border-pink-200 text-xs font-bold transition"
                    >
                        <span>{{ $walletUserVerified ? 'Manage Identity Verification' : 'Verify My Account Now' }}</span>
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Credit Packages Section -->
        <div id="packages-section" class="space-y-4 pt-4">
            <div>
                <h2 class="text-xl font-black text-slate-900 tracking-tight">Purchase Credit Packages</h2>
                <p class="text-xs text-slate-600">
                    Instant automated balance replenishment via verified multi-network cryptocurrency checkout.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 pt-2">
                @foreach($packages as $pkg)
                    <div class="bg-white rounded-2xl border {{ $pkg->is_popular ? 'border-[#F20B83] shadow-md ring-2 ring-[#F20B83]/15' : 'border-slate-200/90 shadow-xs' }} p-6 flex flex-col justify-between relative transition-all duration-150 hover:shadow-md hover:-translate-y-0.5">
                        @if($pkg->is_popular)
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 whitespace-nowrap bg-gradient-to-r from-[#F20B83] to-rose-600 text-white text-[10px] font-extrabold uppercase tracking-wider px-3.5 py-1 rounded-full shadow-sm">
                                Most Popular
                            </div>
                        @endif

                        <div>
                            <h3 class="text-base font-extrabold text-slate-900">{{ $pkg->name }}</h3>
                            <div class="mt-2.5 flex items-baseline">
                                <span class="text-3xl font-black text-slate-900">${{ number_format($pkg->price_usd, 2) }}</span>
                                <span class="text-xs font-medium text-slate-500 ml-1">USD</span>
                            </div>

                            <div class="my-4 pt-4 border-t border-slate-100 space-y-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500">Base Credits</span>
                                    <strong class="text-slate-800">{{ number_format($pkg->credits) }}</strong>
                                </div>
                                @if($pkg->bonus_credits > 0)
                                    <div class="flex items-center justify-between text-emerald-600 font-bold">
                                        <span>Bonus Credits</span>
                                        <span>+{{ number_format($pkg->bonus_credits) }} Free</span>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                    <span class="text-slate-800 font-bold">Total Credits</span>
                                    <span class="text-sm font-black text-[#F20B83]">
                                        {{ number_format($pkg->credits + $pkg->bonus_credits) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <a 
                            href="{{ route('account.wallet.checkout', $pkg) }}" 
                            class="w-full mt-3 inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-xl py-2.5 px-4 font-bold text-xs transition-all duration-150 {{ $pkg->is_popular ? 'bg-gradient-to-r from-[#F20B83] to-rose-600 hover:from-[#d90858] hover:to-rose-700 text-white shadow-sm hover:shadow-md' : 'bg-slate-50 hover:bg-slate-900 text-slate-800 hover:text-white border border-slate-200 hover:border-slate-900 shadow-2xs' }}"
                        >
                            <span>Select Package</span>
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    </div>
                @endforeach
            </div>

            <!-- Custom Top-Up Bar (Min $10) -->
            <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="text-xs font-extrabold text-slate-900">Custom Credit Top-Up</div>
                    <p class="text-[11px] text-slate-500 mt-0.5">Need a specific amount? Enter any USD amount starting from $10.00 (10 Credits per $1).</p>
                </div>
                <form action="{{ route('account.wallet.topup-amount') }}" method="GET" class="flex items-center gap-2 shrink-0">
                    <input type="hidden" name="custom" value="1">
                    <div class="relative w-36 sm:w-44">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">$</span>
                        <input
                            type="number"
                            name="amount"
                            step="0.01"
                            min="10"
                            max="5000"
                            placeholder="10.00"
                            required
                            class="w-full pl-7 pr-3 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-900 focus:border-[#F20B83] outline-none"
                        >
                    </div>
                    <button type="submit" class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 text-xs font-bold shadow-2xs transition cursor-pointer">
                        <span>Top Up</span>
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Immutable Wallet Ledger History -->
        <div class="space-y-4 pt-4">
            <div>
                <h2 class="text-xl font-black text-slate-900 tracking-tight">Wallet Transaction Ledger</h2>
                <p class="text-xs text-slate-600">
                    Complete, immutable audit trail of all credit purchases, posting fees, promotions, and balance adjustments.
                </p>
            </div>

            <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-600">
                            <tr>
                                <th class="px-5 py-3.5">Reference / Date</th>
                                <th class="px-5 py-3.5">Transaction Type</th>
                                <th class="px-5 py-3.5">Description</th>
                                <th class="px-5 py-3.5 text-right">Credits</th>
                                <th class="px-5 py-3.5 text-right">Balance After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($transactions as $tx)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-5 py-4">
                                        <div class="font-mono font-bold text-slate-900 text-[11px]">{{ $tx->reference }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $tx->created_at->format('M d, Y · h:i A') }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        @php
                                            $badgeClasses = match($tx->type) {
                                                'CREDIT_PURCHASE' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'BONUS_CREDIT', 'PROMOTIONAL_CREDIT' => 'bg-purple-50 text-purple-700 border-purple-200',
                                                'POST_CREATION_DEBIT' => 'bg-slate-100 text-slate-700 border-slate-200',
                                                'POST_BOOST_DEBIT' => 'bg-pink-50 text-[#F20B83] border-pink-200',
                                                'REFUND', 'TRANSACTION_REVERSAL' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                'ADMIN_CREDIT' => 'bg-amber-50 text-amber-800 border-amber-200',
                                                'ADMIN_DEBIT' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                default => 'bg-slate-50 text-slate-600 border-slate-200',
                                            };
                                        @endphp
                                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold border {{ $badgeClasses }}">
                                            {{ str_replace('_', ' ', $tx->type) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 max-w-xs truncate">
                                        <div class="text-slate-800 truncate">{{ $tx->description }}</div>
                                        @if($tx->listing)
                                            <a href="{{ route('listings.show', $tx->listing->slug) }}" target="_blank" class="text-[10px] text-[#F20B83] hover:underline">
                                                Ad: {{ Str::limit($tx->listing->title, 30) }} ↗
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right font-black {{ $tx->amount > 0 ? 'text-emerald-600' : 'text-slate-900' }}">
                                        {{ $tx->amount > 0 ? '+' . number_format($tx->amount) : number_format($tx->amount) }}
                                    </td>
                                    <td class="px-5 py-4 text-right font-mono font-bold text-slate-800">
                                        {{ number_format($tx->balance_after) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-slate-400 italic">
                                        No wallet transactions recorded yet.
                                    </td>
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

    </div>
</x-layouts.app>

