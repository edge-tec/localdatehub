<x-layouts.app title="Pricing & Listing Upgrades — LocalDateHub">
    <div
        class="py-12 bg-slate-50"
        x-data="{
            modalOpen: false,
            selectedPlan: { name: '', slug: '', price: '', amount: '', duration: '' },
            customAmount: '',
            selectedListingId: '{{ optional($userListings->first())->id ?? '' }}',
            openPlanModal(plan) {
                this.selectedPlan = plan;
                this.customAmount = plan.amount || '';
                this.modalOpen = true;
            }
        }"
    >
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Pricing & Upgrades']
            ]" />

            <div class="text-center max-w-3xl mx-auto mt-8">
                <x-badge variant="rose">Transparent Pricing</x-badge>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 mt-3 tracking-tight">
                    Simple, Transparent Listing Options
                </h1>
                <p class="mt-4 text-lg text-slate-600 leading-relaxed">
                    Always 100% free to post standard personal ads. Optional feature upgrades give your listing maximum visibility in your city and metro area.
                </p>

                @auth
                    <div class="mt-5 inline-flex flex-wrap items-center justify-center gap-3 px-4 py-2.5 rounded-2xl bg-white border border-slate-200 shadow-xs text-xs">
                        <span class="text-slate-600 font-semibold">
                            Your Wallet Balance:
                            <strong class="text-slate-900 font-extrabold">{{ number_format((int) ($wallet->balance ?? 0)) }} Credits</strong>
                        </span>
                        <a href="{{ route('account.wallet.index') }}" class="px-3 py-1 rounded-full bg-rose-600 hover:bg-rose-500 text-white font-bold transition">
                            + Top Up Wallet
                        </a>
                    </div>
                @endauth
            </div>

            <!-- Pricing Grid -->
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
                @forelse($plans as $plan)
                    <div class="relative rounded-2xl bg-white border @if($plan->price_cents > 0) border-rose-500/50 shadow-xl shadow-slate-200 @else border-slate-200 @endif p-8 flex flex-col justify-between">
                        @if($plan->slug === 'city-featured')
                            <div class="absolute -top-3.5 left-1/2 transform -translate-x-1/2">
                                <span class="bg-gradient-to-r from-rose-600 to-pink-600 text-white text-[11px] font-bold uppercase tracking-wider py-1 px-3 rounded-full shadow-md">
                                    Most Popular
                                </span>
                            </div>
                        @endif

                        <div>
                            <h3 class="text-xl font-bold text-slate-900">{{ $plan->name }}</h3>
                            <p class="mt-2 text-xs text-slate-400 min-h-[36px]">{{ $plan->description }}</p>

                            <div class="mt-6 flex items-baseline gap-1">
                                <span class="text-4xl font-extrabold text-slate-900">
                                    ${{ number_format($plan->price_cents / 100, 2) }}
                                </span>
                                <span class="text-xs text-slate-400 font-medium">
                                    / {{ $plan->duration_days }} days
                                </span>
                            </div>

                            <ul class="mt-6 space-y-3 text-xs text-slate-600">
                                @if(is_array($plan->features))
                                    @foreach($plan->features as $feature)
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-200 space-y-2.5">
                            @if($plan->price_cents === 0)
                                <a href="{{ route('listings.create') }}" class="block w-full py-2.5 text-center text-sm font-semibold rounded-xl transition duration-150 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200">
                                    Post Free Personal Ad
                                </a>
                            @else
                                <button
                                    type="button"
                                    @click="openPlanModal({
                                        name: @js($plan->name),
                                        slug: @js($plan->slug),
                                        price: @js('$' . number_format($plan->price_cents / 100, 2)),
                                        amount: @js(number_format($plan->price_cents / 100, 2, '.', '')),
                                        duration: @js($plan->duration_days . ' days')
                                    })"
                                    class="block w-full py-2.5 text-center text-sm font-semibold rounded-xl transition duration-150 bg-rose-600 hover:bg-rose-500 text-white shadow-lg shadow-rose-600/20 cursor-pointer"
                                >
                                    Select {{ $plan->name }}
                                </button>
                                <a href="{{ auth()->check() ? route('account.wallet.topup-amount', ['amount' => number_format($plan->price_cents / 100, 2, '.', ''), 'plan' => $plan->name]) : route('login') }}" class="block w-full py-2 text-center text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-xl transition">
                                    💳 Top Up ${{ number_format($plan->price_cents / 100, 2) }} Now &rarr;
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12">
                        <x-empty-state
                            title="No Pricing Plans Available"
                            description="Standard personal ads remain free to publish across all categories."
                            actionText="Post Free Ad"
                            :actionUrl="route('listings.create')"
                        />
                    </div>
                @endforelse
            </div>

            <!-- Wallet Credit Top-Up Packages Section -->
            @if(isset($creditPackages) && $creditPackages->count() > 0)
                <div class="mt-16 rounded-3xl bg-white border border-slate-200 p-6 sm:p-8 shadow-sm" id="wallet-topup-section">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-slate-100">
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-rose-50 border border-rose-200 text-rose-600 text-[10px] font-extrabold uppercase tracking-wider">
                                Instant Wallet Credits
                            </span>
                            <h2 class="text-2xl font-extrabold text-slate-900 mt-1">Wallet Top-Up Packages</h2>
                            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Add credits to your wallet to pay for Instant Bump to Top, Featured Highlights, and Spotlights anytime.</p>
                        </div>
                        <a href="{{ route('account.wallet.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shrink-0">
                            <span>Open My Wallet</span>
                            <span>&rarr;</span>
                        </a>
                    </div>

                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($creditPackages as $pkg)
                            <div class="rounded-2xl border {{ $pkg->is_popular ? 'border-rose-500 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} p-5 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 class="text-sm font-extrabold text-slate-900">{{ $pkg->name }}</h3>
                                        @if($pkg->bonus_credits > 0)
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-extrabold">
                                                +{{ number_format((int) $pkg->bonus_credits) }} Bonus
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-3 text-2xl font-black text-slate-900">
                                        ${{ number_format((float) $pkg->price_usd, 2) }}
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1">
                                        Adds <strong>{{ number_format((int) $pkg->total_credits) }}</strong> wallet credits
                                    </div>
                                </div>
                                <a
                                    href="{{ auth()->check() ? route('account.wallet.checkout', ['package' => $pkg->id]) : route('login') }}"
                                    class="mt-4 block w-full py-2 text-center rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition"
                                >
                                    Top Up ${{ number_format((float) $pkg->price_usd, 2) }} &rarr;
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Plan Selection & Top-Up Modal -->
            <div
                x-show="modalOpen"
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
                style="display: none;"
                @keydown.escape.window="modalOpen = false"
            >
                <div @click.outside="modalOpen = false" class="w-full max-w-xl rounded-3xl bg-white border border-slate-200 shadow-2xl overflow-hidden">
                    <div class="px-6 py-5 bg-gradient-to-r from-rose-600 to-pink-600 text-white flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-extrabold uppercase tracking-wider opacity-90">Selected Upgrade</div>
                            <h3 class="text-xl font-black" x-text="selectedPlan.name"></h3>
                            <p class="text-xs opacity-90 mt-0.5">
                                <span x-text="selectedPlan.price"></span> / <span x-text="selectedPlan.duration"></span>
                            </p>
                        </div>
                        <button type="button" @click="modalOpen = false" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white font-bold cursor-pointer">✕</button>
                    </div>

                    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                        @auth
                            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs font-bold text-slate-500 uppercase">Current Wallet Balance</div>
                                    <div class="text-2xl font-black text-slate-900">{{ number_format((int) ($wallet->balance ?? 0)) }} Credits</div>
                                </div>
                                <a href="{{ route('account.wallet.index') }}" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition">
                                    + Top Up Wallet
                                </a>
                            </div>

                            @if($creditPackages->count() > 0)
                                <div class="space-y-2.5">
                                    <div class="text-xs font-extrabold uppercase tracking-wider text-slate-700">Quick Top-Up Options</div>

                                    <a
                                        :href="'{{ route('account.wallet.topup-amount') }}?amount=' + encodeURIComponent(selectedPlan.amount) + '&plan=' + encodeURIComponent(selectedPlan.name)"
                                        class="w-full p-3.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white flex items-center justify-between gap-2 shadow-sm transition"
                                    >
                                        <div>
                                            <div class="text-xs font-extrabold">Top Up Exact Plan Amount</div>
                                            <div class="text-[11px] opacity-90" x-text="'Add ' + selectedPlan.price + ' for ' + selectedPlan.name"></div>
                                        </div>
                                        <span class="px-3 py-1 rounded-lg bg-white/20 text-xs font-black shrink-0" x-text="selectedPlan.price + ' →'"></span>
                                    </a>

                                    <form action="{{ route('account.wallet.topup-amount') }}" method="GET" class="flex items-center gap-2 pt-1">
                                        <input type="hidden" name="plan" :value="selectedPlan.name">
                                        <input type="hidden" name="custom" value="1">
                                        <div class="relative flex-1">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">$</span>
                                            <input
                                                type="number"
                                                name="amount"
                                                x-model="customAmount"
                                                step="0.01"
                                                min="10"
                                                max="5000"
                                                placeholder="Min $10.00 (e.g. 10.00)"
                                                class="w-full pl-7 pr-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-900 focus:border-rose-500 outline-none"
                                                required
                                            >
                                        </div>
                                        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition cursor-pointer shrink-0">
                                            Top Up Custom &rarr;
                                        </button>
                                    </form>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                                        @foreach($creditPackages as $pkg)
                                            <a
                                                href="{{ route('account.wallet.checkout', ['package' => $pkg->id]) }}"
                                                class="p-3 rounded-xl border border-slate-200 hover:border-rose-500 hover:bg-rose-50/40 transition flex items-center justify-between gap-2"
                                            >
                                                <div>
                                                    <div class="text-xs font-extrabold text-slate-900">{{ $pkg->name }}</div>
                                                    <div class="text-[11px] text-slate-500">Get {{ number_format((int) $pkg->total_credits) }} credits</div>
                                                </div>
                                                <span class="px-2.5 py-1 rounded-lg bg-rose-600 text-white text-xs font-bold shrink-0">
                                                    ${{ number_format((float) $pkg->price_usd, 2) }}
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($userListings->count() > 0)
                                <div class="space-y-2 pt-2 border-t border-slate-100">
                                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700">Apply Upgrade to Your Existing Ad</label>
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <select x-model="selectedListingId" class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-800">
                                            @foreach($userListings as $ul)
                                                <option value="{{ $ul->id }}">{{ \Illuminate\Support\Str::limit($ul->title, 45) }} ({{ ucfirst($ul->status) }})</option>
                                            @endforeach
                                        </select>
                                        <a
                                            :href="'/listings/' + selectedListingId + '/boost'"
                                            class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold text-center transition shrink-0"
                                        >
                                            Boost Selected Ad &rarr;
                                        </a>
                                    </div>
                                </div>
                            @endif

                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-3">
                                <span class="text-xs text-slate-500">Or create a brand new listing with this upgrade:</span>
                                <a :href="'{{ route('listings.create') }}?plan=' + encodeURIComponent(selectedPlan.slug)" class="px-4 py-2 rounded-xl border border-slate-200 hover:border-rose-500 text-xs font-bold text-slate-800 hover:text-rose-600 transition shrink-0">
                                    Post New Ad &rarr;
                                </a>
                            </div>
                        @else
                            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 text-xs text-slate-600 space-y-3">
                                <p class="font-semibold text-slate-800">Sign in to top up your wallet credits or apply this upgrade to your listing.</p>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold transition">Login to Top Up</a>
                                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-800 font-bold transition">Create Free Account</a>
                                </div>
                            </div>
                        @endauth
                    </div>
                </div>
            </div>

            <!-- Upgrade FAQ -->
            <div class="mt-16 max-w-3xl mx-auto">
                <h2 class="text-2xl font-bold text-slate-900 text-center mb-8">Frequently Asked Questions</h2>
                <div class="space-y-4">
                    <x-card>
                        <h4 class="text-base font-semibold text-slate-900">Are standard personal ads really free?</h4>
                        <p class="mt-1.5 text-xs text-slate-600 leading-relaxed">
                            Yes! Every registered user can publish standard personal classified ads with up to 4 photos at zero cost. There are no recurring surprise subscriptions.
                        </p>
                    </x-card>

                    <x-card>
                        <h4 class="text-base font-semibold text-slate-900">How does City Featured work?</h4>
                        <p class="mt-1.5 text-xs text-slate-600 leading-relaxed">
                            A City Featured listing is pinned at the top of your specific city category directory with a distinctive highlighted badge and border, generating up to 3x more views.
                        </p>
                    </x-card>

                    <x-card>
                        <h4 class="text-base font-semibold text-slate-900">Is my billing information private?</h4>
                        <p class="mt-1.5 text-xs text-slate-600 leading-relaxed">
                            All transactions are securely handled through PCI-DSS Level 1 compliant payment gateways. We never store raw credit card numbers on our servers.
                        </p>
                    </x-card>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

