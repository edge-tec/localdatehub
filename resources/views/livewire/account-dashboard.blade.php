<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
    <!-- Breadcrumbs -->
    <x-breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'User Account Dashboard']
    ]" />

    <!-- Dashboard Header & Top Bar -->
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xl flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="flex items-center gap-4 sm:gap-5 min-w-0">
            <!-- User Avatar Circle/Square -->
            <div class="w-16 h-16 sm:w-18 sm:h-18 rounded-2xl bg-rose-50 border-2 flex items-center justify-center font-black text-2xl sm:text-3xl shadow-xs shrink-0 select-none overflow-hidden" style="color: #F20B65 !important; border-color: #F20B65 !important;">
                @if(Auth::user()->profile?->avatar_path)
                    <img src="{{ asset('storage/' . Auth::user()->profile->avatar_path) }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr(Auth::user()->profile?->display_name ?: (Auth::user()->name ?: 'U'), 0, 1)) }}
                @endif
            </div>

            <div class="space-y-1.5 min-w-0">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-950 tracking-tight truncate">
                        {{ Auth::user()->profile?->display_name ?: (Auth::user()->name ?: 'Valued Member') }}
                    </h1>

                    @if(Auth::user()->is_age_verified)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span>18+ Verified</span>
                        </span>
                    @else
                        <button wire:click="setTab('age_assurance')" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-50 text-amber-900 border border-amber-300 hover:bg-amber-100 transition shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Verify Age (18+)</span>
                        </button>
                    @endif

                    @if($activeSubscription)
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-purple-50 text-purple-900 border border-purple-300 shadow-2xs">
                            ★ {{ $activeSubscription->plan?->name ?: 'Premium Member' }}
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-2.5 text-xs font-semibold text-slate-700 flex-wrap">
                    <span>Member since {{ Auth::user()->created_at->format('M Y') }}</span>
                    <span class="text-slate-300">&bull;</span>
                    <span>Status: <strong class="capitalize text-emerald-800 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md font-black">{{ Auth::user()->account_status }}</strong></span>
                    <span class="text-slate-300">&bull;</span>
                    <span class="text-slate-600 font-mono font-medium truncate max-w-xs">{{ Auth::user()->email }}</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions, Wallet Balance & Controls -->
        <div class="flex items-center gap-3 shrink-0 flex-wrap sm:flex-nowrap">
            <!-- Prominent Credit Balance & Load Balance Card -->
            <div class="flex items-center gap-2.5 bg-white border-2 border-emerald-400 rounded-2xl px-3.5 py-2 shadow-xs">
                <span class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black shadow-2xs shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </span>
                <div class="leading-tight mr-1">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 block">Balance</span>
                    <span class="text-sm sm:text-base font-black text-slate-900 leading-none">
                        {{ number_format(Auth::user()?->getWalletBalance() ?? 0) }} <span class="text-xs text-emerald-600 font-bold">Credits</span>
                    </span>
                </div>
                <div class="h-6 w-px bg-slate-200"></div>
                <a
                    href="{{ route('account.wallet.index') }}#packages-section"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-black text-white transition flex items-center gap-1 shrink-0 shadow-sm hover:opacity-95"
                    style="background-color: #F20B65 !important; color: #ffffff !important;"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span class="text-white font-bold" style="color: #ffffff !important;">Load Balance</span>
                </a>
            </div>

            <!-- Post Ad Button -->
            <a
                href="{{ route('listings.create') }}"
                class="px-4 py-2.5 rounded-2xl text-xs font-black transition shadow-sm hover:opacity-95 flex items-center gap-1.5"
                style="background-color: #F20B65 !important; color: #ffffff !important;"
            >
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span class="text-white font-bold" style="color: #ffffff !important;">+ Post Ad</span>
            </a>

            <!-- Boost Button -->
            <button
                wire:click="openPromoteModal"
                class="px-3.5 py-2.5 bg-purple-50 hover:bg-purple-100 text-purple-900 border border-purple-300 font-black rounded-2xl text-xs transition flex items-center gap-1.5 shadow-2xs hover:shadow-xs cursor-pointer"
            >
                <svg class="w-3.5 h-3.5 text-purple-600" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                <span>Boost</span>
            </button>

            <!-- Logout Button -->
            <form method="POST" action="{{ route('logout') }}" class="inline-block">
                @csrf
                <button
                    type="submit"
                    class="px-3.5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 hover:text-rose-900 border border-rose-200 font-black rounded-2xl text-xs transition flex items-center gap-1.5 shadow-2xs hover:shadow-xs cursor-pointer"
                    title="Sign Out of Your Account"
                >
                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <x-alert type="success" :dismissible="true">
            {{ session('success') }}
        </x-alert>
    @endif
    @if(session('error'))
        <x-alert type="error" :dismissible="true">
            {{ session('error') }}
        </x-alert>
    @endif

    <!-- Main Navigation Tabs Bar (100% High-Contrast, ALL OPTIONS 100% VISIBLE & FAIL-SAFE DUAL ENGINE) -->
    <div class="bg-white border border-slate-200 rounded-3xl p-3 sm:p-4 shadow-sm flex flex-wrap items-center gap-2 sm:gap-2.5 text-xs font-bold">
        <a
            href="{{ route('account.dashboard', ['tab' => 'overview']) }}"
            wire:click.prevent="setTab('overview')"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 shrink-0 cursor-pointer select-none {{ $tab === 'overview' ? 'font-black shadow-xs' : 'bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shadow-2xs' }}"
            @if($tab === 'overview') style="background-color: #fff1f2 !important; color: #F20B65 !important; border: 2px solid #F20B65 !important;" @endif
        >
            <svg class="w-4 h-4" @if($tab === 'overview') style="color: #F20B65 !important;" @else class="text-slate-500" @endif fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span>Overview</span>
        </a>

        <a
            href="{{ route('account.dashboard', ['tab' => 'listings']) }}"
            wire:click.prevent="setTab('listings')"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 shrink-0 cursor-pointer select-none {{ in_array($tab, ['listings', 'active', 'pending', 'rejected', 'expired', 'suspended']) ? 'font-black shadow-xs' : 'bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shadow-2xs' }}"
            @if(in_array($tab, ['listings', 'active', 'pending', 'rejected', 'expired', 'suspended'])) style="background-color: #fff1f2 !important; color: #F20B65 !important; border: 2px solid #F20B65 !important;" @endif
        >
            <svg class="w-4 h-4" @if(in_array($tab, ['listings', 'active', 'pending', 'rejected', 'expired', 'suspended'])) style="color: #F20B65 !important;" @else class="text-slate-500" @endif fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            <span>My Listings</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-black" @if(in_array($tab, ['listings', 'active', 'pending', 'rejected', 'expired', 'suspended'])) style="background-color: #fce7f3 !important; color: #F20B65 !important;" @else class="bg-slate-200 text-slate-800" @endif>
                {{ $totalListings }}
            </span>
        </a>

        <a
            href="{{ route('account.dashboard', ['tab' => 'saved']) }}"
            wire:click.prevent="setTab('saved')"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 shrink-0 cursor-pointer select-none {{ $tab === 'saved' ? 'font-black shadow-xs' : 'bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shadow-2xs' }}"
            @if($tab === 'saved') style="background-color: #fff1f2 !important; color: #F20B65 !important; border: 2px solid #F20B65 !important;" @endif
        >
            <svg class="w-4 h-4" @if($tab === 'saved') style="color: #F20B65 !important;" @else class="text-slate-500" @endif fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <span>Saved Listings</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-black" @if($tab === 'saved') style="background-color: #fce7f3 !important; color: #F20B65 !important;" @else class="bg-slate-200 text-slate-800" @endif>
                {{ $savedListingsCount }}
            </span>
        </a>

        <a
            href="{{ route('account.dashboard', ['tab' => 'messages']) }}"
            wire:click.prevent="setTab('messages')"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 shrink-0 cursor-pointer select-none {{ $tab === 'messages' ? 'font-black shadow-xs' : 'bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shadow-2xs' }}"
            @if($tab === 'messages') style="background-color: #fff1f2 !important; color: #F20B65 !important; border: 2px solid #F20B65 !important;" @endif
        >
            <svg class="w-4 h-4" @if($tab === 'messages') style="color: #F20B65 !important;" @else class="text-slate-500" @endif fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            <span>Messages</span>
            @if($unreadInquiriesCount > 0)
                <span class="px-2 py-0.5 rounded-full text-[11px] font-black bg-rose-600 text-white animate-pulse">
                    {{ $unreadInquiriesCount }} new
                </span>
            @endif
        </a>

        <a
            href="{{ route('account.wallet.index') }}"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shrink-0 shadow-2xs"
        >
            <svg class="w-4 h-4" style="color: #F20B65 !important;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <span>Credit Wallet</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-black bg-emerald-50 text-emerald-800 border border-emerald-300">
                {{ number_format(auth()->user()?->getWalletBalance() ?? 0) }}
            </span>
        </a>

        <a
            href="{{ route('account.verification.show') }}"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shrink-0 shadow-2xs"
        >
            <svg class="w-4 h-4 {{ auth()->user()?->is_identity_verified ? 'text-blue-600' : 'text-slate-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>Verification</span>
            @if(auth()->user()?->is_identity_verified)
                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-[10px] font-black">✓ VERIFIED</span>
            @endif
        </a>

        <a
            href="{{ route('account.dashboard', ['tab' => 'analytics']) }}"
            wire:click.prevent="setTab('analytics')"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 shrink-0 cursor-pointer select-none {{ $tab === 'analytics' ? 'font-black shadow-xs' : 'bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shadow-2xs' }}"
            @if($tab === 'analytics') style="background-color: #fff1f2 !important; color: #F20B65 !important; border: 2px solid #F20B65 !important;" @endif
        >
            <svg class="w-4 h-4" @if($tab === 'analytics') style="color: #F20B65 !important;" @else class="text-slate-500" @endif fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Analytics</span>
        </a>

        <a
            href="{{ route('account.dashboard', ['tab' => 'promotions']) }}"
            wire:click.prevent="setTab('promotions')"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 shrink-0 cursor-pointer select-none {{ $tab === 'promotions' ? 'font-black shadow-xs' : 'bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shadow-2xs' }}"
            @if($tab === 'promotions') style="background-color: #fff1f2 !important; color: #F20B65 !important; border: 2px solid #F20B65 !important;" @endif
        >
            <svg class="w-4 h-4" @if($tab === 'promotions') style="color: #F20B65 !important;" @else class="text-slate-500" @endif fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            <span>Promotions</span>
        </a>

        <a
            href="{{ route('account.dashboard', ['tab' => 'subscription']) }}"
            wire:click.prevent="setTab('subscription')"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 shrink-0 cursor-pointer select-none {{ $tab === 'subscription' ? 'font-black shadow-xs' : 'bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shadow-2xs' }}"
            @if($tab === 'subscription') style="background-color: #fff1f2 !important; color: #F20B65 !important; border: 2px solid #F20B65 !important;" @endif
        >
            <svg class="w-4 h-4" @if($tab === 'subscription') style="color: #F20B65 !important;" @else class="text-slate-500" @endif fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <span>Subscription</span>
        </a>

        <a
            href="{{ route('account.dashboard', ['tab' => 'billing']) }}"
            wire:click.prevent="setTab('billing')"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 shrink-0 cursor-pointer select-none {{ $tab === 'billing' ? 'font-black shadow-xs' : 'bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shadow-2xs' }}"
            @if($tab === 'billing') style="background-color: #fff1f2 !important; color: #F20B65 !important; border: 2px solid #F20B65 !important;" @endif
        >
            <svg class="w-4 h-4" @if($tab === 'billing') style="color: #F20B65 !important;" @else class="text-slate-500" @endif fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/></svg>
            <span>Billing</span>
        </a>

        <a
            href="{{ route('account.dashboard', ['tab' => 'profile']) }}"
            wire:click.prevent="setTab('profile')"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 shrink-0 cursor-pointer select-none {{ $tab === 'profile' ? 'font-black shadow-xs' : 'bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shadow-2xs' }}"
            @if($tab === 'profile') style="background-color: #fff1f2 !important; color: #F20B65 !important; border: 2px solid #F20B65 !important;" @endif
        >
            <svg class="w-4 h-4" @if($tab === 'profile') style="color: #F20B65 !important;" @else class="text-slate-500" @endif fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Profile</span>
        </a>

        <a
            href="{{ route('account.dashboard', ['tab' => 'privacy']) }}"
            wire:click.prevent="setTab('privacy')"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 shrink-0 cursor-pointer select-none {{ $tab === 'privacy' ? 'font-black shadow-xs' : 'bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shadow-2xs' }}"
            @if($tab === 'privacy') style="background-color: #fff1f2 !important; color: #F20B65 !important; border: 2px solid #F20B65 !important;" @endif
        >
            <svg class="w-4 h-4" @if($tab === 'privacy') style="color: #F20B65 !important;" @else class="text-slate-500" @endif fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span>Privacy</span>
        </a>

        <a
            href="{{ route('account.dashboard', ['tab' => 'age_assurance']) }}"
            wire:click.prevent="setTab('age_assurance')"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 shrink-0 cursor-pointer select-none {{ $tab === 'age_assurance' ? 'font-black shadow-xs' : 'bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shadow-2xs' }}"
            @if($tab === 'age_assurance') style="background-color: #fff1f2 !important; color: #F20B65 !important; border: 2px solid #F20B65 !important;" @endif
        >
            <svg class="w-4 h-4" @if($tab === 'age_assurance') style="color: #F20B65 !important;" @else class="text-slate-500" @endif fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span>18+ Assurance</span>
        </a>

        <a
            href="{{ route('account.dashboard', ['tab' => 'security']) }}"
            wire:click.prevent="setTab('security')"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 shrink-0 cursor-pointer select-none {{ $tab === 'security' ? 'font-black shadow-xs' : 'bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shadow-2xs' }}"
            @if($tab === 'security') style="background-color: #fff1f2 !important; color: #F20B65 !important; border: 2px solid #F20B65 !important;" @endif
        >
            <svg class="w-4 h-4" @if($tab === 'security') style="color: #F20B65 !important;" @else class="text-slate-500" @endif fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            <span>Security</span>
        </a>

        <a
            href="{{ route('account.dashboard', ['tab' => 'blocked_users']) }}"
            wire:click.prevent="setTab('blocked_users')"
            class="px-3.5 sm:px-4 py-2.5 rounded-2xl transition flex items-center gap-2 shrink-0 cursor-pointer select-none {{ $tab === 'blocked_users' ? 'font-black shadow-xs' : 'bg-slate-50 text-slate-800 hover:text-slate-950 hover:bg-slate-100 border border-slate-200 font-bold shadow-2xs' }}"
            @if($tab === 'blocked_users') style="background-color: #fff1f2 !important; color: #F20B65 !important; border: 2px solid #F20B65 !important;" @endif
        >
            <svg class="w-4 h-4" @if($tab === 'blocked_users') style="color: #F20B65 !important;" @else class="text-slate-500" @endif fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <span>Blocked Users</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-black" @if($tab === 'blocked_users') style="background-color: #fce7f3 !important; color: #F20B65 !important;" @else class="bg-slate-200 text-slate-800" @endif>
                {{ count($blockedUsers) }}
            </span>
        </a>
    </div>

    <!-- TAB 1: OVERVIEW SECTION -->
    @if($tab === 'overview')
        <div class="space-y-8">
            <!-- Key Metric Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-white border border-slate-200 p-5 rounded-3xl shadow-md hover:shadow-lg transition">
                    <span class="text-xs text-slate-700 block font-black uppercase tracking-wider">Active Ads</span>
                    <span class="text-3xl font-black text-emerald-800 mt-1.5 block">{{ $activeListingsCount }}</span>
                    <button wire:click="setTab('listings'); $set('listingStatus', 'active')" class="text-xs font-extrabold text-[#F20B83] hover:underline mt-2 inline-flex items-center gap-1">View Active &rarr;</button>
                </div>

                <div class="bg-white border border-slate-200 p-5 rounded-3xl shadow-md hover:shadow-lg transition">
                    <span class="text-xs text-slate-700 block font-black uppercase tracking-wider">Pending Review</span>
                    <span class="text-3xl font-black text-amber-800 mt-1.5 block">{{ $pendingListingsCount }}</span>
                    <button wire:click="setTab('listings'); $set('listingStatus', 'pending_review')" class="text-xs font-extrabold text-amber-800 hover:underline mt-2 inline-flex items-center gap-1">Under Review &rarr;</button>
                </div>

                <div class="bg-white border border-slate-200 p-5 rounded-3xl shadow-md hover:shadow-lg transition">
                    <span class="text-xs text-slate-700 block font-black uppercase tracking-wider">Needs Renewal</span>
                    <span class="text-3xl font-black text-rose-800 mt-1.5 block">{{ $expiredListingsCount }}</span>
                    <button wire:click="setTab('listings'); $set('listingStatus', 'expired')" class="text-xs font-extrabold text-rose-800 hover:underline mt-2 inline-flex items-center gap-1">Renew Now &rarr;</button>
                </div>

                <div class="bg-white border border-slate-200 p-5 rounded-3xl shadow-md hover:shadow-lg transition">
                    <span class="text-xs text-slate-700 block font-black uppercase tracking-wider">Unread Messages</span>
                    <span class="text-3xl font-black text-slate-950 mt-1.5 block">{{ $unreadInquiriesCount }}</span>
                    <button wire:click="setTab('messages')" class="text-xs font-extrabold text-[#F20B83] hover:underline mt-2 inline-flex items-center gap-1">Open Inbox &rarr;</button>
                </div>

                <div class="bg-white border border-slate-200 p-5 rounded-3xl shadow-md hover:shadow-lg transition">
                    <span class="text-xs text-slate-700 block font-black uppercase tracking-wider">Total Ad Views</span>
                    <span class="text-3xl font-black text-purple-900 mt-1.5 block">{{ number_format($totalViews) }}</span>
                    <button wire:click="setTab('analytics')" class="text-xs font-extrabold text-purple-900 hover:underline mt-2 inline-flex items-center gap-1">Stats &rarr;</button>
                </div>

                <div class="bg-white border border-slate-200 p-5 rounded-3xl shadow-md hover:shadow-lg transition">
                    <span class="text-xs text-slate-700 block font-black uppercase tracking-wider">Favorites</span>
                    <span class="text-3xl font-black text-[#F20B83] mt-1.5 block">{{ $totalFavorites }}</span>
                    <span class="text-xs font-bold text-slate-700 mt-2 block">{{ $totalReplies }} Inquiries</span>
                </div>
            </div>

            <!-- Recent Listings & Inquiries Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Classifieds Card -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h2 class="text-base font-black text-slate-950 flex items-center gap-2">
                            <span>My Recent Classifieds</span>
                            <span class="text-xs font-bold text-slate-600">({{ $recentListings->count() }})</span>
                        </h2>
                        <button wire:click="setTab('listings')" class="text-xs text-[#F20B83] hover:underline font-black">View All &rarr;</button>
                    </div>

                    @if($recentListings->isNotEmpty())
                        <div class="divide-y divide-slate-100">
                            @foreach($recentListings as $ad)
                                <div class="py-3.5 flex items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <a href="{{ route('listings.show', $ad->slug) }}" class="w-13 h-13 rounded-xl overflow-hidden bg-slate-100 shrink-0 border border-slate-200 block shadow-2xs hover:opacity-90 transition">
                                            @php $photo = $ad->primaryPhoto ?: $ad->allMedia->first(); @endphp
                                            @if($photo)
                                                <img src="{{ $photo->url }}" alt="{{ $ad->title }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 bg-slate-50">
                                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                            @endif
                                        </a>
                                        <div class="min-w-0">
                                            <a href="{{ route('listings.show', $ad->slug) }}" class="font-extrabold text-slate-950 hover:text-[#F20B65] truncate block text-sm">
                                                {{ $ad->title }}
                                            </a>
                                            <span class="text-slate-600 font-semibold text-xs mt-0.5 block">
                                                {{ $ad->category?->name }} &bull; {{ $ad->city?->name }}, {{ $ad->city?->state?->code }} &bull; <strong class="text-slate-900">{{ $ad->view_count }}</strong> Views
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase
                                            @if($ad->status === 'active') bg-emerald-50 text-emerald-800 border border-emerald-300
                                            @elseif($ad->status === 'pending_review') bg-amber-50 text-amber-900 border border-amber-300
                                            @elseif($ad->status === 'rejected') bg-rose-50 text-rose-900 border border-rose-300
                                            @else bg-slate-100 text-slate-700 border border-slate-200 @endif
                                        ">
                                            {{ str_replace('_', ' ', $ad->status) }}
                                        </span>
                                        <a href="{{ route('listings.edit', $ad->id) }}" class="text-slate-600 hover:text-slate-950 p-1.5 rounded-lg hover:bg-slate-100 transition" title="Edit listing">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-xs font-semibold text-slate-700 space-y-3">
                            <p>You haven't posted any classified advertisements yet.</p>
                            <a href="{{ route('listings.create') }}" class="px-4 py-2 bg-gradient-to-r from-rose-600 to-[#F20B83] text-white rounded-xl inline-block font-black shadow-xs">Post First Ad</a>
                        </div>
                    @endif
                </div>

                <!-- Recent Messages Card -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h2 class="text-base font-black text-slate-950 flex items-center gap-2">
                            <span>Recent Inquiries</span>
                            @if($unreadInquiriesCount > 0)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-50 text-rose-800 border border-rose-300">{{ $unreadInquiriesCount }} new</span>
                            @endif
                        </h2>
                        <button wire:click="setTab('messages')" class="text-xs text-[#F20B83] hover:underline font-black">Inbox &rarr;</button>
                    </div>

                    @if($recentInquiries->isNotEmpty())
                        <div class="divide-y divide-slate-100">
                            @foreach($recentInquiries as $msg)
                                <div class="py-3.5 flex items-center justify-between gap-3 text-xs">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            @if(!$msg->is_read)
                                                <span class="w-2 h-2 rounded-full bg-[#F20B83] animate-pulse"></span>
                                            @endif
                                            <span class="font-black text-slate-950 text-sm">{{ $msg->sender_name }}</span>
                                            <span class="text-slate-500 font-medium text-xs">&bull; {{ $msg->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-slate-700 font-semibold text-xs truncate mt-0.5">
                                            Re: {{ $msg->listing?->title ?? 'General Inquiry' }}
                                        </p>
                                    </div>
                                    <button
                                        wire:click="inspectMessage({{ $msg->id }})"
                                        class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-black text-[#F20B83] shrink-0 transition"
                                    >
                                        Read
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-xs font-semibold text-slate-700 leading-relaxed">
                            No inquiries received yet. When members contact you, messages will safely appear here.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 2: MY LISTINGS (Covers Sections 2, 4, 5, 6, 7, 8) -->
    @if(in_array($tab, ['listings', 'active', 'pending', 'rejected', 'expired', 'suspended']))
        <div class="space-y-6">
            <!-- Filter & Search Controls -->
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Status Filter Tabs (100% High Contrast, No White Text) -->
                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                        <button
                            wire:click="$set('listingStatus', 'all')"
                            class="px-3.5 py-1.5 rounded-xl transition {{ $listingStatus === 'all' ? 'bg-rose-50 text-[#F20B83] border-2 border-[#F20B83] font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:text-slate-950 hover:bg-slate-200 border border-slate-200 font-bold' }}"
                        >
                            All ({{ $totalListings }})
                        </button>
                        <button
                            wire:click="$set('listingStatus', 'active')"
                            class="px-3.5 py-1.5 rounded-xl transition {{ $listingStatus === 'active' ? 'bg-emerald-50 text-emerald-800 border-2 border-emerald-500 font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:text-slate-950 hover:bg-slate-200 border border-slate-200 font-bold' }}"
                        >
                            Active ({{ $activeListingsCount }})
                        </button>
                        <button
                            wire:click="$set('listingStatus', 'pending_review')"
                            class="px-3.5 py-1.5 rounded-xl transition {{ $listingStatus === 'pending_review' ? 'bg-amber-50 text-amber-900 border-2 border-amber-500 font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:text-slate-950 hover:bg-slate-200 border border-slate-200 font-bold' }}"
                        >
                            Pending Review ({{ $pendingListingsCount }})
                        </button>
                        <button
                            wire:click="$set('listingStatus', 'rejected')"
                            class="px-3.5 py-1.5 rounded-xl transition {{ $listingStatus === 'rejected' ? 'bg-rose-50 text-rose-900 border-2 border-rose-500 font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:text-slate-950 hover:bg-slate-200 border border-slate-200 font-bold' }}"
                        >
                            Rejected ({{ $rejectedListingsCount }})
                        </button>
                        <button
                            wire:click="$set('listingStatus', 'expired')"
                            class="px-3.5 py-1.5 rounded-xl transition {{ $listingStatus === 'expired' ? 'bg-purple-50 text-purple-900 border-2 border-purple-500 font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:text-slate-950 hover:bg-slate-200 border border-slate-200 font-bold' }}"
                        >
                            Expired ({{ $expiredListingsCount }})
                        </button>
                        <button
                            wire:click="$set('listingStatus', 'suspended')"
                            class="px-3.5 py-1.5 rounded-xl transition {{ $listingStatus === 'suspended' ? 'bg-red-50 text-red-900 border-2 border-red-500 font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:text-slate-950 hover:bg-slate-200 border border-slate-200 font-bold' }}"
                        >
                            Suspended ({{ $suspendedListingsCount }})
                        </button>
                    </div>

                    <a href="{{ route('listings.create') }}" class="px-4 py-2 bg-gradient-to-r from-rose-600 to-[#F20B83] text-white font-black rounded-xl text-xs transition shadow-xs hover:shadow-md self-start md:self-auto shrink-0 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        <span>+ New Ad</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-slate-100">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="listingSearch"
                        placeholder="Search your listings by title..."
                        class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-[#F20B83]"
                    >

                    <select
                        wire:model.live="listingCategory"
                        class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 font-semibold focus:ring-2 focus:ring-[#F20B83]"
                    >
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>

                    <select
                        wire:model.live="listingSort"
                        class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 font-semibold focus:ring-2 focus:ring-[#F20B83]"
                    >
                        <option value="newest">Sort: Newest First</option>
                        <option value="views">Sort: Most Views</option>
                        <option value="replies">Sort: Most Inquiries</option>
                    </select>
                </div>

                <!-- Bulk Actions Toolbar -->
                @if(!empty($selectedListings))
                    <div class="p-3.5 bg-rose-50 border-2 border-rose-200 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-3 shadow-xs animate-fadeIn">
                        <span class="font-black text-xs text-rose-950">{{ count($selectedListings) }} listings selected</span>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button wire:click="bulkResumeListings" class="px-3 py-1.5 bg-white border border-rose-200 text-emerald-800 font-bold text-xs rounded-xl hover:bg-emerald-50 transition shadow-2xs">Activate</button>
                            <button wire:click="bulkPauseListings" class="px-3 py-1.5 bg-white border border-rose-200 text-slate-800 font-bold text-xs rounded-xl hover:bg-slate-100 transition shadow-2xs">Pause</button>
                            <button wire:click="bulkRenewListings" class="px-3 py-1.5 bg-white border border-rose-200 text-purple-900 font-bold text-xs rounded-xl hover:bg-purple-50 transition shadow-2xs">Renew (+30d)</button>
                            <button wire:click="bulkDeleteListings" wire:confirm="Are you sure you want to delete all selected listings?" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-black text-xs rounded-xl transition shadow-2xs">Delete</button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Listings Stream -->
            @if($myListings->isNotEmpty())
                <div class="bg-white border border-slate-200 rounded-3xl divide-y divide-slate-100 overflow-hidden shadow-xl">
                    @foreach($myListings as $listing)
                        <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/70 transition {{ $listing->status === 'active' ? 'border-l-4 border-l-emerald-500' : '' }}">
                            <div class="flex items-start sm:items-center gap-4 min-w-0 flex-1">
                                <input
                                    type="checkbox"
                                    value="{{ $listing->id }}"
                                    wire:model.live="selectedListings"
                                    class="mt-1 sm:mt-0 rounded bg-slate-50 border-slate-300 text-[#F20B65] focus:ring-[#F20B65]"
                                >
                                <a href="{{ route('listings.show', $listing->slug) }}" class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden bg-slate-100 shrink-0 border border-slate-200 block shadow-2xs hover:opacity-90 transition relative group">
                                    @php $photo = $listing->primaryPhoto ?: $listing->allMedia->first(); @endphp
                                    @if($photo)
                                        <img src="{{ $photo->url }}" alt="{{ $listing->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    @else
                                        <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 bg-slate-50 text-[10px] font-bold">
                                            <svg class="w-6 h-6 mb-0.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span>No Photo</span>
                                        </div>
                                    @endif
                                </a>
                                <div class="space-y-1.5 min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase
                                            @if($listing->status === 'active') bg-emerald-50 text-emerald-800 border border-emerald-300
                                            @elseif($listing->status === 'pending_review') bg-amber-50 text-amber-900 border border-amber-300
                                            @elseif($listing->status === 'rejected') bg-rose-50 text-rose-900 border border-rose-300
                                            @elseif($listing->status === 'expired') bg-purple-50 text-purple-900 border border-purple-300
                                            @elseif($listing->status === 'suspended') bg-red-50 text-red-900 border border-red-300
                                            @else bg-slate-100 text-slate-700 border border-slate-200 @endif
                                        ">
                                            {{ str_replace('_', ' ', $listing->status) }}
                                        </span>
                                        @if($listing->is_featured)
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-50 text-amber-900 border border-amber-300">
                                                ★ Featured Spotlight
                                            </span>
                                        @endif
                                        <span class="text-xs text-slate-600 font-semibold">{{ $listing->category?->name }} &bull; {{ $listing->city?->name }}, {{ $listing->city?->state?->code }}</span>
                                    </div>

                                    <h3 class="text-base font-black text-slate-950 truncate">
                                        <a href="{{ route('listings.show', $listing->slug) }}" class="hover:text-[#F20B83] transition">{{ $listing->title }}</a>
                                    </h3>

                                    <!-- Rejection or Suspension Details Notice -->
                                    @if($listing->status === 'rejected' && $listing->rejection_reason)
                                        <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-300 text-xs font-bold text-rose-900">
                                            <strong>Moderation Notice:</strong> {{ $listing->rejection_reason }}
                                        </div>
                                    @endif

                                    <div class="flex items-center gap-3 text-xs text-slate-700 font-semibold flex-wrap">
                                        <span>Views: <strong class="text-slate-950 font-black">{{ $listing->view_count }}</strong></span>
                                        <span>&bull;</span>
                                        <span>Replies: <strong class="text-slate-950 font-black">{{ $listing->reply_count }}</strong></span>
                                        <span>&bull;</span>
                                        <span>Favorites: <strong class="text-slate-950 font-black">{{ $listing->favorite_count }}</strong></span>
                                        <span>&bull;</span>
                                        <span>Expires: <strong class="text-slate-950 font-black">{{ $listing->expires_at?->format('M d, Y') ?? 'Never' }}</strong></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                @if($listing->status === 'active')
                                    <button wire:click="pauseListing({{ $listing->id }})" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs text-slate-800 font-bold transition shadow-2xs">
                                        Pause
                                    </button>
                                @elseif($listing->status === 'paused')
                                    <button wire:click="resumeListing({{ $listing->id }})" class="px-3.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-xs text-emerald-800 border border-emerald-300 font-black transition shadow-2xs">
                                        Resume
                                    </button>
                                @endif

                                @if($listing->status === 'expired')
                                    <button wire:click="renewListing({{ $listing->id }})" class="px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-xs text-rose-800 border border-rose-300 font-black transition shadow-2xs">
                                        Renew Ad
                                    </button>
                                @endif

                                @if(!$listing->is_featured && $listing->status === 'active')
                                    <button wire:click="openPromoteModal({{ $listing->id }})" class="px-3.5 py-1.5 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-900 border border-purple-300 text-xs font-black transition shadow-2xs">
                                        Promote
                                    </button>
                                @endif

                                <a href="{{ route('listings.edit', $listing->id) }}" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs text-slate-800 font-bold transition shadow-2xs">
                                    Edit
                                </a>

                                <button
                                    wire:click="confirmDeleteListing({{ $listing->id }})"
                                    class="p-2 rounded-xl text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition"
                                    title="Delete ad"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4">
                    {{ $myListings->links() }}
                </div>
            @else
                <x-card class="text-center py-12">
                    <x-empty-state
                        title="No Listings Found"
                        description="There are currently no classified listings matching your active filters."
                        actionText="Create a Classified Ad"
                        :actionUrl="route('listings.create')"
                    />
                </x-card>
            @endif
        </div>
    @endif

    <!-- TAB 3: SAVED LISTINGS (Section 9) -->
    @if($tab === 'saved')
        <div class="space-y-6">
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Saved Personal Listings</h2>
                    <p class="text-xs text-slate-700 font-semibold mt-1">Classified ads you have bookmarked for quick reference.</p>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="savedSearch"
                    placeholder="Search saved ads..."
                    class="bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-950 font-bold placeholder-slate-400 focus:ring-2 focus:ring-[#F20B83] w-full sm:w-64"
                >
            </div>

            @if($savedListings->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($savedListings as $listing)
                        <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-md hover:shadow-lg transition flex flex-col justify-between">
                            <div class="p-5 space-y-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="px-2.5 py-0.5 rounded-full bg-rose-50 text-[#F20B83] border border-rose-200 font-black text-[10px]">
                                        {{ $listing->category?->name }}
                                    </span>
                                    <span class="text-slate-600 font-semibold text-xs">{{ $listing->city?->name }}, {{ $listing->city?->state?->code }}</span>
                                </div>
                                <h3 class="text-base font-black text-slate-950 leading-snug line-clamp-2">
                                    <a href="{{ route('listings.show', $listing->slug) }}" class="hover:text-[#F20B83] transition">{{ $listing->title }}</a>
                                </h3>
                                <p class="text-xs text-slate-700 font-normal line-clamp-2 leading-relaxed">
                                    {{ $listing->description }}
                                </p>
                            </div>
                            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
                                <a href="{{ route('listings.show', $listing->slug) }}" class="font-extrabold text-[#F20B83] hover:underline">
                                    View Ad &rarr;
                                </a>
                                <button
                                    wire:click="removeFavorite({{ $listing->id }})"
                                    class="text-xs text-slate-600 hover:text-rose-600 font-bold transition"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4">
                    {{ $savedListings->links() }}
                </div>
            @else
                <x-card class="text-center py-12">
                    <x-empty-state
                        title="No Saved Listings"
                        description="You haven't bookmarked any advertisements yet. Click the heart icon on any listing to save it here."
                        actionText="Explore Classifieds"
                        :actionUrl="route('categories.index')"
                    />
                </x-card>
            @endif
        </div>
    @endif

    <!-- TAB 4: ADVANCED ENTERPRISE CONTACT & MESSAGING CENTER (Section 10) -->
    @if($tab === 'messages')
        <div class="space-y-6">
            <!-- Header & Control Bar -->
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center text-[#F20B83]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-black text-slate-950 tracking-tight">Messaging & Inquiry Center</h2>
                                <p class="text-xs text-slate-700 font-semibold mt-0.5">Secure, encrypted communications for your personal classified ads.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Folder Toggle: Inbox vs Sent -->
                    <div class="inline-flex p-1 bg-slate-100 rounded-2xl border border-slate-200 self-start sm:self-auto">
                        <button
                            wire:click="$set('messageFolder', 'inbox')"
                            class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center gap-2 {{ $messageFolder === 'inbox' ? 'bg-white text-slate-950 shadow-xs' : 'text-slate-700 hover:text-slate-950' }}"
                        >
                            <svg class="w-4 h-4 {{ $messageFolder === 'inbox' ? 'text-[#F20B83]' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                            <span>Inbox (Received)</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-black {{ $totalUnreadCount > 0 ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-slate-200 text-slate-800' }}">
                                {{ $totalInboxCount }}
                            </span>
                        </button>
                        <button
                            wire:click="$set('messageFolder', 'sent')"
                            class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center gap-2 {{ $messageFolder === 'sent' ? 'bg-white text-slate-950 shadow-xs' : 'text-slate-700 hover:text-slate-950' }}"
                        >
                            <svg class="w-4 h-4 {{ $messageFolder === 'sent' ? 'text-[#F20B83]' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            <span>Sent Messages</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-black bg-slate-200 text-slate-800">
                                {{ $totalSentCount }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Filters & Search Toolbar -->
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                    <!-- Left: Status Pills (Only relevant for Inbox) -->
                    @if($messageFolder === 'inbox')
                        <div class="flex items-center gap-2 flex-wrap text-xs font-bold">
                            <button
                                wire:click="$set('messageFilter', 'all')"
                                class="px-3.5 py-1.5 rounded-xl transition {{ $messageFilter === 'all' ? 'bg-rose-50 text-[#F20B83] border-2 border-[#F20B83] font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:text-slate-950 hover:bg-slate-200 border border-slate-200 font-bold' }}"
                            >
                                All ({{ $totalInboxCount }})
                            </button>
                            <button
                                wire:click="$set('messageFilter', 'unread')"
                                class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $messageFilter === 'unread' ? 'bg-rose-50 text-[#F20B83] border-2 border-[#F20B83] font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:text-slate-950 hover:bg-slate-200 border border-slate-200 font-bold' }}"
                            >
                                @if($totalUnreadCount > 0)
                                    <span class="w-2 h-2 rounded-full bg-[#F20B83]"></span>
                                @endif
                                <span>Unread ({{ $totalUnreadCount }})</span>
                            </button>
                            <button
                                wire:click="$set('messageFilter', 'replied')"
                                class="px-3.5 py-1.5 rounded-xl transition {{ $messageFilter === 'replied' ? 'bg-emerald-50 text-emerald-800 border-2 border-emerald-500 font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:text-slate-950 hover:bg-slate-200 border border-slate-200 font-bold' }}"
                            >
                                Replied ({{ $totalRepliedCount }})
                            </button>
                            <button
                                wire:click="$set('messageFilter', 'flagged')"
                                class="px-3.5 py-1.5 rounded-xl transition {{ $messageFilter === 'flagged' ? 'bg-amber-50 text-amber-900 border-2 border-amber-500 font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:text-slate-950 hover:bg-slate-200 border border-slate-200 font-bold' }}"
                            >
                                Flagged ({{ $totalFlaggedCount }})
                            </button>
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
                            <svg class="w-4 h-4 text-[#F20B83]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Showing inquiries and replies you have initiated.</span>
                        </div>
                    @endif

                    <!-- Right: Listing Filter & Search Box -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                        <!-- Filter by Specific Ad -->
                        @if($userListingsForMessages->isNotEmpty())
                            <select
                                wire:model.live="messageListingFilter"
                                class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 font-medium focus:ring-2 focus:ring-[#F20B83] focus:border-transparent outline-none max-w-xs"
                            >
                                <option value="">Filter by Classified Ad (All)</option>
                                @foreach($userListingsForMessages as $uAd)
                                    <option value="{{ $uAd->id }}">{{ Str::limit($uAd->title, 35) }}</option>
                                @endforeach
                            </select>
                        @endif

                        <!-- Search Box -->
                        <div class="relative w-full sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="messageSearch"
                                placeholder="Search by name, subject..."
                                class="bg-slate-50 border border-slate-200 rounded-xl pl-8.5 pr-8 py-2 text-xs text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-[#F20B83] focus:border-transparent outline-none w-full"
                            >
                            @if(!empty($messageSearch))
                                <button wire:click="$set('messageSearch', '')" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Multi-Select Bulk Actions Toolbar (Inbox only) -->
                @if($messageFolder === 'inbox' && $messages->isNotEmpty())
                    <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-slate-50/70 -mx-6 -mb-6 p-4 rounded-b-3xl">
                        <div class="flex items-center gap-3">
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    wire:click="toggleSelectAllMessages"
                                    {{ $selectAllMessages ? 'checked' : '' }}
                                    class="w-4 h-4 text-[#F20B83] rounded border-slate-300 focus:ring-[#F20B83]"
                                >
                                <span class="text-xs font-bold text-slate-700">Select All on Page</span>
                            </label>
                            @if(count($selectedMessages) > 0)
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-[#F20B83]/10 text-[#F20B83]">
                                    {{ count($selectedMessages) }} selected
                                </span>
                            @endif
                        </div>

                        @if(count($selectedMessages) > 0)
                            <div class="flex items-center gap-2 flex-wrap animate-fadeIn">
                                <button
                                    wire:click="bulkMarkRead"
                                    class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-xs font-bold text-slate-700 shadow-2xs transition flex items-center gap-1.5"
                                >
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Mark Read</span>
                                </button>
                                <button
                                    wire:click="bulkMarkUnread"
                                    class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-xs font-bold text-slate-700 shadow-2xs transition flex items-center gap-1.5"
                                >
                                    <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Mark Unread</span>
                                </button>
                                <button
                                    wire:click="bulkDeleteMessages"
                                    wire:confirm="Are you sure you want to delete {{ count($selectedMessages) }} selected inquiries?"
                                    class="px-3 py-1.5 rounded-xl bg-rose-50 border border-rose-200 hover:bg-rose-100 text-xs font-bold text-rose-700 transition flex items-center gap-1.5"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Delete</span>
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Messages List Table / Cards -->
            @if($messages->isNotEmpty())
                <div class="bg-white border border-slate-200 rounded-3xl divide-y divide-slate-100 overflow-hidden shadow-xl">
                    @foreach($messages as $msg)
                        <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/70 transition {{ !$msg->is_read && $messageFolder === 'inbox' ? 'bg-rose-50/20' : 'bg-white' }}">
                            <!-- Left: Checkbox, Avatar & Details -->
                            <div class="flex items-start sm:items-center gap-4 min-w-0">
                                @if($messageFolder === 'inbox')
                                    <div class="pt-1 sm:pt-0 shrink-0">
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedMessages"
                                            value="{{ $msg->id }}"
                                            class="w-4 h-4 text-[#F20B83] rounded border-slate-300 focus:ring-[#F20B83]"
                                        >
                                    </div>
                                @endif

                                <!-- Avatar -->
                                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr {{ !$msg->is_read && $messageFolder === 'inbox' ? 'from-[#F20B83] to-rose-400 text-white shadow-md' : 'from-slate-100 to-slate-200 text-slate-700' }} flex items-center justify-center font-extrabold text-base shrink-0 select-none">
                                    {{ strtoupper(substr($messageFolder === 'sent' ? ($msg->recipient?->name ?? 'M') : $msg->sender_name, 0, 1)) }}
                                </div>

                                <!-- Inquiry Content Summary -->
                                <div class="space-y-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if($messageFolder === 'inbox' && !$msg->is_read)
                                            <span class="w-2 h-2 rounded-full bg-[#F20B83] animate-pulse"></span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500/10 text-[#F20B83] border border-rose-500/20">NEW</span>
                                        @endif

                                        @if($msg->is_replied)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                                <span>REPLIED</span>
                                            </span>
                                        @endif

                                        @if($msg->is_flagged)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                FLAGGED SPAM
                                            </span>
                                        @endif

                                        <span class="font-extrabold text-slate-900 text-sm">
                                            @if($messageFolder === 'sent')
                                                To: {{ $msg->recipient?->name ?? 'Classified Advertiser' }}
                                            @else
                                                {{ $msg->sender_name }}
                                            @endif
                                        </span>

                                        <span class="text-slate-400 text-xs">&bull; {{ $msg->created_at->diffForHumans() }}</span>
                                    </div>

                                    <!-- Ad reference & message snippet -->
                                    <div class="flex items-center gap-1.5 text-xs text-slate-600 truncate">
                                        @if($msg->listing)
                                            <span class="font-medium text-slate-400">Re:</span>
                                            <a
                                                href="{{ route('listings.show', $msg->listing->slug) }}"
                                                target="_blank"
                                                class="font-bold text-[#F20B83] hover:underline inline-flex items-center gap-1 truncate"
                                            >
                                                <span>{{ $msg->listing->title }}</span>
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            </a>
                                            <span class="text-slate-300">|</span>
                                        @endif
                                        <span class="text-slate-600 truncate">{{ Str::limit($msg->message_encrypted, 90) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Action Buttons -->
                            <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                <button
                                    wire:click="inspectMessage({{ $msg->id }})"
                                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-[#F20B83] hover:from-rose-600 hover:to-pink-600 text-white font-bold text-xs shadow-xs hover:shadow-md transition flex items-center gap-1.5"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>Read & Reply</span>
                                </button>

                                @if($messageFolder === 'inbox')
                                    @if($msg->is_read)
                                        <button
                                            wire:click="markMessageUnread({{ $msg->id }})"
                                            title="Mark as unread"
                                            class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5"/></svg>
                                        </button>
                                    @endif

                                    <button
                                        wire:click="flagMessage({{ $msg->id }})"
                                        title="{{ $msg->is_flagged ? 'Unflag Spam' : 'Flag as Spam' }}"
                                        class="p-2 rounded-xl {{ $msg->is_flagged ? 'text-amber-600 bg-amber-50' : 'text-slate-400 hover:text-amber-600 hover:bg-slate-100' }} transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                                    </button>
                                @endif

                                <button
                                    wire:click="deleteMessage({{ $msg->id }})"
                                    wire:confirm="Permanently delete this message?"
                                    title="Delete inquiry"
                                    class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="pt-4">
                    {{ $messages->links() }}
                </div>
            @else
                <!-- Smart Empty States -->
                <div class="bg-white border border-slate-200 rounded-3xl p-12 text-center shadow-xl space-y-5">
                    <div class="w-20 h-20 rounded-3xl bg-gradient-to-tr from-pink-50 to-rose-100 border border-rose-200/60 mx-auto flex items-center justify-center text-[#F20B83] shadow-inner">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>

                    @if(!empty($messageSearch) || !empty($messageListingFilter) || ($messageFolder === 'inbox' && $messageFilter !== 'all'))
                        <div class="space-y-2">
                            <h3 class="text-lg font-bold text-slate-900">No Messages Found</h3>
                            <p class="text-xs text-slate-500 max-w-md mx-auto">
                                No inquiries match your current search keywords or applied filter criteria.
                            </p>
                            <div class="pt-3">
                                <button
                                    wire:click="$set('messageFilter', 'all'); $set('messageSearch', ''); $set('messageListingFilter', null);"
                                    class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 transition"
                                >
                                    Reset Filters & Show All
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="space-y-2">
                            <h3 class="text-lg font-bold text-slate-900">
                                {{ $messageFolder === 'sent' ? 'No Sent Inquiries Yet' : 'Your Inbox is Clean' }}
                            </h3>
                            <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                                @if($messageFolder === 'sent')
                                    You have not contacted any classified advertisers yet. Browse local ads to initiate private inquiries.
                                @else
                                    When interested buyers and members respond to your personal advertisements, their verified inquiries will appear here safely.
                                @endif
                            </p>
                        </div>

                        <div class="pt-2 flex items-center justify-center gap-3 flex-wrap">
                            <a
                                href="{{ route('listings.create') }}"
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-rose-500 to-[#F20B83] text-white font-bold text-xs shadow-md hover:shadow-lg transition flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Post New Personal Ad</span>
                            </a>
                            <button
                                wire:click="setTab('listings')"
                                class="px-5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs shadow-2xs transition"
                            >
                                View My Active Ads
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 5: ANALYTICS & METRICS (Section 11) -->
    @if($tab === 'analytics')
        <div class="space-y-8">
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Listing Performance & Analytics</h2>
                    <p class="text-xs text-slate-700 font-semibold mt-1">Aggregate views, inquiries, and engagement across your personal advertisements.</p>
                </div>
            </div>

            <!-- Aggregated KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="p-6 bg-white border border-slate-200 rounded-3xl shadow-md">
                    <span class="text-xs text-slate-700 uppercase font-black tracking-wider">Total Ad Impressions</span>
                    <span class="text-3xl font-black text-slate-950 block mt-2">{{ number_format($totalViews) }}</span>
                </div>
                <div class="p-6 bg-white border border-slate-200 rounded-3xl shadow-md">
                    <span class="text-xs text-slate-700 uppercase font-black tracking-wider">Inquiries Received</span>
                    <span class="text-3xl font-black text-emerald-800 block mt-2">{{ number_format($totalReplies) }}</span>
                </div>
                <div class="p-6 bg-white border border-slate-200 rounded-3xl shadow-md">
                    <span class="text-xs text-slate-700 uppercase font-black tracking-wider">Favorites Saved</span>
                    <span class="text-3xl font-black text-[#F20B83] block mt-2">{{ number_format($totalFavorites) }}</span>
                </div>
                <div class="p-6 bg-white border border-slate-200 rounded-3xl shadow-md">
                    <span class="text-xs text-slate-700 uppercase font-black tracking-wider">Conversion Rate</span>
                    <span class="text-3xl font-black text-purple-900 block mt-2">
                        {{ $totalViews > 0 ? round(($totalReplies / $totalViews) * 100, 1) : 0 }}%
                    </span>
                </div>
            </div>

            <!-- Per-Listing Breakdown -->
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl space-y-4">
                <h3 class="text-base font-black text-slate-950">Performance by Classified Advertisement</h3>
                @if($analyticsListings->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700">
                            <thead class="bg-slate-50 text-slate-800 uppercase text-[10px] font-black border-b border-slate-200">
                                <tr>
                                    <th class="p-3">Listing Title</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3 text-right">Views</th>
                                    <th class="p-3 text-right">Replies</th>
                                    <th class="p-3 text-right">Favorites</th>
                                    <th class="p-3 text-right">Rate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($analyticsListings as $ad)
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="p-3 font-extrabold text-slate-950">{{ Str::limit($ad->title, 40) }}</td>
                                        <td class="p-3 capitalize font-bold">{{ $ad->status }}</td>
                                        <td class="p-3 text-right font-mono font-bold text-slate-900">{{ $ad->view_count }}</td>
                                        <td class="p-3 text-right font-mono font-bold text-emerald-800">{{ $ad->reply_count }}</td>
                                        <td class="p-3 text-right font-mono font-bold text-[#F20B83]">{{ $ad->favorite_count }}</td>
                                        <td class="p-3 text-right font-mono font-black text-emerald-800">
                                            {{ $ad->view_count > 0 ? round(($ad->reply_count / $ad->view_count) * 100, 1) : 0 }}%
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-xs text-slate-500 py-6 text-center">No active listings available for analytics.</p>
                @endif
            </div>
        </div>
    @endif

    <!-- TAB 6: PROMOTIONS & FEATURED LISTINGS (Section 12) -->
    @if($tab === 'promotions')
        <div class="space-y-8">
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Featured Promotions & Upgrades</h2>
                    <p class="text-xs text-slate-700 font-semibold mt-1">Boost ad prominence with pinned top-of-category and city spotlights.</p>
                </div>
                <button wire:click="openPromoteModal" class="px-5 py-2.5 bg-purple-50 hover:bg-purple-100 text-purple-900 border-2 border-purple-300 font-black rounded-xl text-xs transition shadow-xs flex items-center gap-1.5">
                    ★ Promote Active Ad
                </button>
            </div>

            <!-- Active Promotions Table -->
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl space-y-4">
                <h3 class="text-base font-black text-slate-950">Your Active & Historical Promotions</h3>
                @if($activePromotions->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach($activePromotions as $promo)
                            <div class="py-3 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-extrabold text-slate-950 block text-sm">{{ $promo->listing?->title }}</span>
                                    <span class="text-slate-600 font-semibold text-xs mt-0.5 block">
                                        Placement: <strong class="capitalize text-slate-900">{{ str_replace('_', ' ', $promo->placement) }}</strong> &bull;
                                        Expires: {{ $promo->ends_at->format('M d, Y') }}
                                    </span>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase {{ $promo->is_active ? 'bg-amber-50 text-amber-900 border border-amber-300' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $promo->is_active ? 'Active Spotlight' : 'Ended' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-600 font-semibold py-4 text-center">You have no active featured promotions.</p>
                @endif
            </div>

            <!-- Available Promotion Packages -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($pricingPlans as $plan)
                    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-md hover:shadow-xl transition flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-purple-50 text-purple-900 border border-purple-200 uppercase">
                                {{ $plan->plan_type }}
                            </span>
                            <h4 class="text-lg font-black text-slate-950">{{ $plan->name }}</h4>
                            <div class="text-2xl font-black text-slate-950">
                                ${{ number_format($plan->price_cents / 100, 2) }}
                                <span class="text-xs font-bold text-slate-600">/ {{ $plan->duration_days }} days</span>
                            </div>
                            <p class="text-xs text-slate-700 font-medium leading-relaxed">{{ $plan->description }}</p>
                        </div>

                        <button
                            wire:click="openPromoteModal"
                            class="w-full py-2.5 bg-rose-50 hover:bg-rose-100 text-[#F20B83] border border-rose-300 font-black text-xs rounded-xl transition"
                        >
                            Select Upgrade
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- TAB 7: SUBSCRIPTION (Section 13) -->
    @if($tab === 'subscription')
        <div class="max-w-4xl space-y-6">
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
                <h2 class="text-xl font-bold text-slate-900">Membership & Subscription Plan</h2>

                @if($activeSubscription)
                    <div class="p-6 rounded-2xl bg-purple-50 border border-purple-500/40 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-purple-700 tracking-wider">Current Active Plan</span>
                                <h3 class="text-2xl font-black text-slate-900 mt-0.5">{{ $activeSubscription->plan?->name }}</h3>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-700 border border-emerald-500/30 uppercase">
                                Active
                            </span>
                        </div>
                        <div class="text-xs text-slate-600 space-y-1">
                            <p>Starts: {{ $activeSubscription->starts_at->format('M d, Y') }}</p>
                            <p>Renews / Expires: {{ $activeSubscription->ends_at?->format('M d, Y') ?? 'Auto-renewing' }}</p>
                            <p>Gateway: <strong class="uppercase font-mono">{{ $activeSubscription->gateway }}</strong></p>
                        </div>
                        <div class="pt-2">
                            <button
                                wire:click="cancelSubscription({{ $activeSubscription->id }})"
                                wire:confirm="Are you sure you want to cancel your subscription? Benefits will expire at the end of the current cycle."
                                class="px-4 py-2 bg-rose-600/20 text-rose-600 border border-rose-500/30 hover:bg-rose-600/30 text-xs font-bold rounded-xl transition"
                            >
                                Cancel Subscription
                            </button>
                        </div>
                    </div>
                @else
                    <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 text-center space-y-4">
                        <h3 class="text-base font-bold text-slate-900">Standard Free Membership</h3>
                        <p class="text-xs text-slate-400 max-w-md mx-auto">
                            You are currently on the free standard tier. Upgrade to Verified Plus for trusted age assurance badges and included monthly featured ad bumps.
                        </p>
                        @foreach($pricingPlans->where('plan_type', 'membership') as $membershipPlan)
                            <button
                                wire:click="subscribeToPlan({{ $membershipPlan->id }})"
                                class="px-6 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs transition"
                            >
                                Upgrade to {{ $membershipPlan->name }} (${{ number_format($membershipPlan->price_cents / 100, 2) }}/mo)
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- TAB 8: BILLING HISTORY (Section 14) -->
    @if($tab === 'billing')
        <div class="space-y-6">
            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Billing & Payment History</h2>
                    <p class="text-xs text-slate-700 font-semibold mt-1">Itemized ledger of all promotional ad placements and subscription receipts.</p>
                </div>
            </div>

            @if($billingTransactions->isNotEmpty())
                <div class="bg-white border border-slate-200 rounded-3xl divide-y divide-slate-100 overflow-hidden shadow-xl">
                    <div class="p-4 bg-slate-50 grid grid-cols-4 sm:grid-cols-5 text-slate-800 uppercase text-[10px] font-black border-b border-slate-200">
                        <span>Date / Ref</span>
                        <span>Item / Description</span>
                        <span class="text-right">Amount</span>
                        <span class="text-center">Status</span>
                        <span class="text-right hidden sm:block">Method</span>
                    </div>
                    @foreach($billingTransactions as $txn)
                        <div class="p-4 grid grid-cols-4 sm:grid-cols-5 items-center text-xs text-slate-700 hover:bg-slate-50/70 transition">
                            <div>
                                <span class="font-black text-slate-950 block">{{ $txn->created_at->format('M d, Y') }}</span>
                                <span class="font-mono text-[10px] text-slate-600 font-semibold">{{ $txn->reference_id }}</span>
                            </div>
                            <div class="truncate">
                                <span class="font-black text-slate-900">{{ $txn->plan?->name ?: 'Listing Upgrade' }}</span>
                                @if($txn->listing)
                                    <span class="block text-[11px] text-slate-600 font-medium truncate">{{ $txn->listing->title }}</span>
                                @endif
                            </div>
                            <div class="text-right font-mono font-black text-slate-950">
                                ${{ number_format($txn->amount_cents / 100, 2) }}
                            </div>
                            <div class="text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase
                                    @if($txn->status === 'completed') bg-emerald-50 text-emerald-800 border border-emerald-300
                                    @elseif($txn->status === 'pending') bg-amber-50 text-amber-900 border border-amber-300
                                    @else bg-rose-50 text-rose-900 border border-rose-300 @endif
                                ">
                                    {{ $txn->status }}
                                </span>
                            </div>
                            <div class="text-right flex items-center justify-end space-x-2">
                                <span class="uppercase font-mono text-[10px] text-slate-700 font-bold hidden sm:inline">
                                    {{ $txn->payment_method }} ({{ $txn->gateway }})
                                </span>
                                @if($txn->invoice)
                                    <a href="{{ route('account.invoices.show', $txn->invoice) }}" target="_blank" class="inline-flex items-center px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-[#F20B83] text-xs font-black border border-slate-200 transition-colors shadow-2xs">
                                        Invoice
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4">
                    {{ $billingTransactions->links() }}
                </div>
            @else
                <x-card class="text-center py-12">
                    <x-empty-state
                        title="No Billing Transactions"
                        description="You haven't made any purchases or promotion upgrades yet."
                    />
                </x-card>
            @endif
        </div>
    @endif

    <!-- TAB 9: PROFILE SETTINGS (Section 15) -->
    @if($tab === 'profile')
        <div class="max-w-3xl space-y-6">
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
                <h2 class="text-xl font-black text-slate-950">Public Profile Settings</h2>

                <form wire:submit="saveProfile" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1">
                                Display Name / Handle <span class="text-rose-600">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="displayName"
                                required
                                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-950 font-bold placeholder-slate-400 focus:ring-2 focus:ring-[#F20B83]"
                            >
                            @error('displayName') <span class="text-rose-600 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1">
                                Gender Identity
                            </label>
                            <input
                                type="text"
                                wire:model="gender"
                                placeholder="e.g. Female, Male, Non-binary"
                                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-950 font-bold placeholder-slate-400 focus:ring-2 focus:ring-[#F20B83]"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1">
                            Seeking / Match Intent
                        </label>
                        <input
                            type="text"
                            wire:model="seeking"
                            placeholder="e.g. Men for dating, Activity partner, Casual coffee"
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-950 font-bold placeholder-slate-400 focus:ring-2 focus:ring-[#F20B83]"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1">
                            Bio & About You
                        </label>
                        <textarea
                            wire:model="bio"
                            rows="4"
                            placeholder="Share a little bit about who you are..."
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-950 font-semibold placeholder-slate-400 focus:ring-2 focus:ring-[#F20B83]"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1">City</label>
                            <input type="text" wire:model="city" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-950 font-bold focus:ring-2 focus:ring-[#F20B83]">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1">State Code</label>
                            <input type="text" wire:model="stateCode" placeholder="CA, NY, TX..." class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-950 font-bold focus:ring-2 focus:ring-[#F20B83]">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1">ZIP Code</label>
                            <input type="text" wire:model="zipCode" placeholder="90210..." class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-950 font-bold focus:ring-2 focus:ring-[#F20B83]">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-rose-600 to-[#F20B83] text-white font-black rounded-xl text-xs transition shadow-xs hover:shadow-md">
                            Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- TAB 10: PRIVACY SETTINGS (Section 16) -->
    @if($tab === 'privacy')
        <div class="max-w-3xl space-y-6">
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Privacy & Geolocation Controls</h2>
                    <p class="text-xs text-slate-700 font-semibold mt-1">Configure your location visibility and incoming communication filters.</p>
                </div>

                <form wire:submit="savePrivacySettings" class="space-y-5">
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <span class="text-xs font-black text-slate-950 block">Anti-Triangulation Location Fuzzing</span>
                            <span class="text-xs text-slate-700 font-medium block">Hide exact neighborhood and apply 300–800m GPS jitter to all your ads.</span>
                        </div>
                        <input type="checkbox" wire:model="hideLocationDetails" class="rounded bg-white border-slate-300 text-[#F20B83] focus:ring-[#F20B83] w-5 h-5">
                    </div>

                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <span class="text-xs font-black text-slate-950 block">Hide Exact Age</span>
                            <span class="text-xs text-slate-700 font-medium block">Show only the "18+ Verified" badge without displaying your numerical birth year.</span>
                        </div>
                        <input type="checkbox" wire:model="hideAge" class="rounded bg-white border-slate-300 text-[#F20B83] focus:ring-[#F20B83] w-5 h-5">
                    </div>

                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <span class="text-xs font-black text-slate-950 block">Allow Platform Contact Messages</span>
                            <span class="text-xs text-slate-700 font-medium block">Enable the protected encrypted contact form on all your listings.</span>
                        </div>
                        <input type="checkbox" wire:model="allowContactMessages" class="rounded bg-white border-slate-300 text-[#F20B83] focus:ring-[#F20B83] w-5 h-5">
                    </div>

                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <span class="text-xs font-black text-slate-950 block">Require 18+ Age Verified Senders</span>
                            <span class="text-xs text-slate-700 font-medium block">Only accept contact inquiries from members who have completed age assurance.</span>
                        </div>
                        <input type="checkbox" wire:model="requireVerifiedSenders" class="rounded bg-white border-slate-300 text-[#F20B83] focus:ring-[#F20B83] w-5 h-5">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-rose-600 to-[#F20B83] text-white font-black rounded-xl text-xs transition shadow-xs hover:shadow-md">
                            Save Privacy Preferences
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- TAB 11: AGE ASSURANCE STATUS (Section 17) -->
    @if($tab === 'age_assurance')
        <div class="max-w-3xl space-y-6">
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
                <div>
                    <h2 class="text-xl font-black text-slate-950">18+ Adult Age Assurance & Compliance</h2>
                    <p class="text-xs text-slate-700 font-semibold mt-1">LocalDateHub complies strictly with state and federal legal age verification mandates.</p>
                </div>

                @if(Auth::user()->is_age_verified)
                    <div class="p-5 rounded-2xl bg-emerald-50 border border-emerald-300 space-y-2 text-xs">
                        <div class="flex items-center gap-2 text-emerald-800 font-black text-sm">
                            <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span>18+ Age Assurance Active</span>
                        </div>
                        <p class="text-slate-700 font-medium">Your account is fully verified. Your personal advertisements feature the 18+ Age Verified trust badge.</p>
                    </div>
                @else
                    <form wire:submit="submitAgeAssurance" class="space-y-4">
                        <div class="p-4 bg-amber-50 border border-amber-300 rounded-2xl text-xs text-amber-900 space-y-1">
                            <strong class="font-black text-amber-950">Legal Notice:</strong>
                            <p class="font-medium">You must be at least 18 years of age. All personal classified advertisements are moderated for adult legal compliance.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1">
                                Your Date of Birth <span class="text-rose-600">*</span>
                            </label>
                            <input
                                type="date"
                                wire:model="declaredBirthdate"
                                required
                                class="bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-950 font-bold focus:ring-2 focus:ring-[#F20B83]"
                            >
                            @error('declaredBirthdate') <span class="text-rose-600 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-start gap-3 pt-2">
                            <input
                                type="checkbox"
                                wire:model="ageAffirmation"
                                id="ageAffirmation"
                                required
                                class="mt-0.5 rounded bg-slate-50 border-slate-300 text-[#F20B83] focus:ring-[#F20B83]"
                            >
                            <label for="ageAffirmation" class="text-xs text-slate-600">
                                I solemnly affirm under penalty of perjury that I am at least 18 years old and legally permitted to access adult personal connections in my jurisdiction.
                            </label>
                        </div>
                        @error('ageAffirmation') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror

                        <div class="pt-2">
                            <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs transition">
                                Affirm Adult Age & Complete Verification
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <!-- TAB 12: SECURITY SETTINGS (Section 18) -->
    @if($tab === 'security')
        <div class="max-w-3xl space-y-6">
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
                <h2 class="text-xl font-black text-slate-950">Password & Account Security</h2>

                <form wire:submit="updatePassword" class="space-y-4">
                    <div>
                        <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1">Current Password</label>
                        <input type="password" wire:model="currentPassword" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-950 font-bold focus:ring-2 focus:ring-[#F20B83]">
                        @error('currentPassword') <span class="text-rose-600 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1">New Password</label>
                            <input type="password" wire:model="newPassword" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-950 font-bold focus:ring-2 focus:ring-[#F20B83]">
                            @error('newPassword') <span class="text-rose-600 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1">Confirm New Password</label>
                            <input type="password" wire:model="newPasswordConfirmation" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-950 font-bold focus:ring-2 focus:ring-[#F20B83]">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-rose-600 to-[#F20B83] text-white font-black rounded-xl text-xs transition shadow-xs hover:shadow-md">
                            Update Password
                        </button>
                    </div>
                </form>

                <div class="pt-6 border-t border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-black text-slate-950">Two-Factor Authentication (2FA)</h3>
                            <p class="text-xs text-slate-700 font-medium">TOTP Authenticator app protection (Google Authenticator, Authy).</p>
                        </div>
                        <a href="{{ route('account.security') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs text-slate-900 font-black border border-slate-200 shadow-2xs transition">
                            Configure 2FA &rarr;
                        </a>
                    </div>
                </div>

                <!-- Danger Zone: Account Deletion (Section 20) -->
                <div class="pt-6 border-t border-rose-200 space-y-3">
                    <h3 class="text-sm font-black text-rose-700 uppercase tracking-wider">Danger Zone</h3>
                    <p class="text-xs text-slate-700 font-medium leading-relaxed">Permanently delete your account and purge all active advertisements according to CCPA and GDPR regulations.</p>
                    <button
                        wire:click="confirmAccountDeletion"
                        class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-800 border-2 border-rose-300 font-black rounded-xl text-xs transition shadow-2xs"
                    >
                        Delete My Account
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 13: BLOCKED USERS (Section 19) -->
    @if($tab === 'blocked_users')
        <div class="max-w-4xl space-y-6">
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Blocked Users</h2>
                    <p class="text-xs text-slate-700 font-semibold mt-1">Blocked accounts cannot view your personal contact handles or submit inquiries to your classified ads.</p>
                </div>

                <!-- Add Block Form -->
                <form wire:submit="blockUser" class="p-5 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                    <h3 class="text-xs font-black text-slate-950 uppercase tracking-wider">Block a Member</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <input
                                type="text"
                                wire:model="blockUsername"
                                placeholder="Member username or email..."
                                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-950 font-bold placeholder-slate-400 focus:ring-2 focus:ring-[#F20B83]"
                            >
                            @error('blockUsername') <span class="text-rose-600 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <input
                                type="text"
                                wire:model="blockReason"
                                placeholder="Reason (e.g. Harassment, Spam)..."
                                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-950 font-bold placeholder-slate-400 focus:ring-2 focus:ring-[#F20B83]"
                            >
                        </div>
                    </div>
                    <button type="submit" class="px-5 py-2 bg-rose-50 hover:bg-rose-100 text-[#F20B83] border border-rose-300 font-black text-xs rounded-xl transition shadow-2xs">
                        Block User
                    </button>
                </form>

                <!-- Blocked List -->
                @if(count($blockedUsers) > 0)
                    <div class="divide-y divide-slate-100">
                        @foreach($blockedUsers as $block)
                            <div class="py-3.5 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-black text-slate-950 block text-sm">{{ $block->blocked?->name ?? 'User #' . $block->blocked_id }}</span>
                                    <span class="text-slate-600 font-semibold text-xs">Blocked on {{ $block->created_at?->format('M d, Y') }} &bull; {{ $block->reason }}</span>
                                </div>
                                <button
                                    wire:click="unblockUser({{ $block->id }})"
                                    class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs text-slate-800 font-bold transition shadow-2xs"
                                >
                                    Unblock
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-500 py-4 text-center">Your blocked users list is currently empty.</p>
                @endif
            </div>
        </div>
    @endif

    <!-- MODAL 1: ADVANCED INSPECT & DIRECT TWO-WAY REPLY MODAL -->
    @if($showMessageModal && $inspectedMessage)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fadeIn overflow-y-auto">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full my-8 shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
                <!-- Modal Top Header -->
                <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between gap-4 bg-slate-50/70">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-500 to-[#F20B83] text-white flex items-center justify-center font-extrabold text-lg shadow-md shrink-0">
                            {{ strtoupper(substr($inspectedMessage->sender_name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-base font-extrabold text-slate-900">{{ $inspectedMessage->sender_name }}</h3>
                                @if(!$inspectedMessage->is_read)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500/10 text-[#F20B83] border border-rose-500/20">NEW</span>
                                @endif
                                @if($inspectedMessage->is_replied)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">REPLIED</span>
                                @endif
                                @if($inspectedMessage->is_flagged)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">FLAGGED</span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-400 mt-0.5 block">Received {{ $inspectedMessage->created_at->format('M d, Y @ h:i A') }} ({{ $inspectedMessage->created_at->diffForHumans() }})</span>
                        </div>
                    </div>
                    <button
                        wire:click="closeMessageModal"
                        class="w-9 h-9 rounded-xl text-slate-400 hover:text-slate-800 hover:bg-slate-200/60 flex items-center justify-center transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Scrollable Content -->
                <div class="p-5 sm:p-6 overflow-y-auto space-y-5 flex-1">
                    <!-- Flash Message Feedback inside Modal -->
                    @if(session()->has('success'))
                        <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-2 text-xs font-bold text-emerald-800 animate-fadeIn">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    <!-- Regarding Classified Ad Card -->
                    @if($inspectedMessage->listing)
                        <div class="p-4 bg-rose-50/40 border border-rose-100 rounded-2xl flex items-center justify-between gap-4">
                            <div class="space-y-0.5 min-w-0">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Regarding Personal Classified Ad</span>
                                <h4 class="text-sm font-extrabold text-slate-900 truncate">{{ $inspectedMessage->listing->title }}</h4>
                                <span class="text-xs text-slate-500 block">
                                    {{ $inspectedMessage->listing->city?->name }}, {{ $inspectedMessage->listing->city?->state?->code }}
                                </span>
                            </div>
                            <a
                                href="{{ route('listings.show', $inspectedMessage->listing->slug) }}"
                                target="_blank"
                                class="px-3.5 py-1.5 rounded-xl bg-white border border-rose-200 hover:bg-rose-50 text-xs font-bold text-[#F20B83] shadow-2xs transition flex items-center gap-1.5 shrink-0"
                            >
                                <span>View Ad</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </div>
                    @endif

                    <!-- Contact Details Badges -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-2xl flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Sender Email</span>
                                <span class="font-mono text-slate-800 font-semibold text-xs truncate block" title="{{ $inspectedMessage->sender_email_encrypted }}">
                                    {{ $inspectedMessage->sender_email_encrypted }}
                                </span>
                            </div>
                            <button
                                type="button"
                                onclick="navigator.clipboard.writeText('{{ $inspectedMessage->sender_email_encrypted }}'); alert('Email address copied to clipboard!');"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition"
                                title="Copy Email"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </div>

                        <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-2xl flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Contact Phone</span>
                                <span class="font-mono text-slate-800 font-semibold text-xs truncate block">
                                    {{ $inspectedMessage->sender_phone_encrypted ?: 'Not Provided' }}
                                </span>
                            </div>
                            @if($inspectedMessage->sender_phone_encrypted)
                                <button
                                    type="button"
                                    onclick="navigator.clipboard.writeText('{{ $inspectedMessage->sender_phone_encrypted }}'); alert('Phone number copied to clipboard!');"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition"
                                    title="Copy Phone"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Safety & Scam Alert Banner -->
                    <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-2.5 text-xs text-amber-900 leading-relaxed">
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div>
                            <span class="font-extrabold text-amber-950 block">LocalDateHub Trust & Safety Warning</span>
                            Never send money, wire transfers, CashApp, or gift cards to unverified strangers. If a user asks for remote deposits or suspicious links, flag them immediately.
                        </div>
                    </div>

                    <!-- Conversation Thread / Messages Timeline -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Conversation History</span>
                            <span class="text-[11px] text-slate-400 font-medium">Encrypted on LocalDateHub</span>
                        </div>

                        <div class="space-y-3 max-h-72 overflow-y-auto p-3.5 bg-slate-50/60 rounded-2xl border border-slate-200/80">
                            <!-- Initial Message -->
                            <div class="flex flex-col items-start space-y-1 max-w-[85%]">
                                <div class="flex items-center gap-2 text-[10px] text-slate-400 font-semibold px-1">
                                    <span>{{ $inspectedMessage->sender_name }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $inspectedMessage->created_at->format('M d, g:i A') }}</span>
                                </div>
                                <div class="p-4 bg-white border border-slate-200 rounded-2xl rounded-tl-xs text-xs text-slate-800 whitespace-pre-line leading-relaxed shadow-2xs">
                                    {{ $inspectedMessage->message_encrypted }}
                                </div>
                            </div>

                            <!-- Conversation Thread Replies -->
                            @if(!empty($conversationReplies))
                                @foreach($conversationReplies as $reply)
                                    @php
                                        $isMe = is_array($reply) ? ($reply['is_me'] ?? false) : (($reply->sender_id === Auth::id()) || ($reply->sender_email_encrypted === Auth::user()?->email));
                                        $senderName = is_array($reply) ? ($reply['sender_name'] ?? 'User') : $reply->sender_name;
                                        $createdAtFormatted = is_array($reply) ? ($reply['created_at_formatted'] ?? '') : ($reply->created_at ? $reply->created_at->format('M d, g:i A') : '');
                                        $replyMessage = is_array($reply) ? ($reply['message'] ?? '') : $reply->message_encrypted;
                                    @endphp
                                    <div class="flex flex-col {{ $isMe ? 'items-end ml-auto' : 'items-start' }} space-y-1 max-w-[85%] animate-fadeIn">
                                        <div class="flex items-center gap-2 text-[10px] text-slate-400 font-semibold px-1">
                                            <span>{{ $isMe ? 'You (Owner)' : $senderName }}</span>
                                            <span>&bull;</span>
                                            <span>{{ $createdAtFormatted }}</span>
                                        </div>
                                        <div class="p-4 {{ $isMe ? 'bg-gradient-to-r from-rose-500 to-[#F20B83] text-white rounded-tr-xs shadow-xs' : 'bg-white border border-slate-200 text-slate-800 rounded-tl-xs shadow-2xs' }} rounded-2xl text-xs whitespace-pre-line leading-relaxed">
                                            {{ $replyMessage }}
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <!-- Direct Two-Way In-App Reply Composer -->
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-extrabold text-slate-800 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-[#F20B83]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                <span>Send Direct Protected Reply</span>
                            </label>
                            <span class="text-[11px] text-slate-400">Delivered directly inside LocalDateHub</span>
                        </div>

                        <!-- Quick Template Chips -->
                        <div class="space-y-1.5">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Quick Replies:</span>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <button
                                    type="button"
                                    wire:click="insertQuickReply('Hi, thanks for reaching out! Yes, this listing is still active and available.')"
                                    class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 hover:border-[#F20B83] hover:text-[#F20B83] text-[11px] font-semibold text-slate-600 transition"
                                >
                                    + Still available
                                </button>
                                <button
                                    type="button"
                                    wire:click="insertQuickReply('When are you available to chat or meet up in a public setting?')"
                                    class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 hover:border-[#F20B83] hover:text-[#F20B83] text-[11px] font-semibold text-slate-600 transition"
                                >
                                    + When can we chat?
                                </button>
                                <button
                                    type="button"
                                    wire:click="insertQuickReply('Thanks for your message! Please let me know what questions you have.')"
                                    class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 hover:border-[#F20B83] hover:text-[#F20B83] text-[11px] font-semibold text-slate-600 transition"
                                >
                                    + Any questions?
                                </button>
                                <button
                                    type="button"
                                    wire:click="insertQuickReply('Please verify your profile on LocalDateHub before we coordinate further.')"
                                    class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 hover:border-[#F20B83] hover:text-[#F20B83] text-[11px] font-semibold text-slate-600 transition"
                                >
                                    + Verify profile first
                                </button>
                            </div>
                        </div>

                        <!-- Reply Textarea -->
                        <div>
                            <textarea
                                wire:model="replyMessageContent"
                                rows="3"
                                placeholder="Type your reply here... (Your personal email remains protected)"
                                class="w-full bg-white border border-slate-200 rounded-xl p-3 text-xs text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-[#F20B83] focus:border-transparent outline-none transition resize-none"
                            ></textarea>
                            @error('replyMessageContent')
                                <p class="text-xs font-bold text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Reply Button -->
                        <div class="flex justify-end">
                            <button
                                wire:click="sendDirectReply"
                                wire:loading.attr="disabled"
                                class="px-5 py-2 rounded-xl bg-gradient-to-r from-rose-500 to-[#F20B83] hover:from-rose-600 hover:to-pink-600 text-white font-bold text-xs shadow-xs hover:shadow-md transition flex items-center gap-2 disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="sendDirectReply">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                </span>
                                <span wire:loading wire:target="sendDirectReply" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span>Send Direct Reply</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Bottom Controls / Actions -->
                <div class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/70 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                    <!-- Left: Email Client fallback -->
                    <div class="flex items-center gap-2">
                        <a
                            href="mailto:{{ $inspectedMessage->sender_email_encrypted }}?subject=Re: {{ urlencode($inspectedMessage->subject ?? 'Your Inquiry on LocalDateHub') }}"
                            class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-xs font-bold text-slate-700 shadow-2xs transition flex items-center gap-1.5"
                            title="Open in your default mail application"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>Open Mail Client</span>
                        </a>

                        @if($inspectedMessage->is_read)
                            <button
                                wire:click="markMessageUnread({{ $inspectedMessage->id }})"
                                class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-xs font-bold text-slate-700 shadow-2xs transition flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Mark Unread</span>
                            </button>
                        @endif
                    </div>

                    <!-- Right: Safety, Block, and Delete actions -->
                    <div class="flex items-center gap-2 justify-end flex-wrap">
                        <button
                            wire:click="flagMessage({{ $inspectedMessage->id }})"
                            class="px-3 py-1.5 rounded-xl {{ $inspectedMessage->is_flagged ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-white border border-slate-200 hover:bg-amber-50 text-slate-700 hover:text-amber-700' }} text-xs font-bold shadow-2xs transition flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                            <span>{{ $inspectedMessage->is_flagged ? 'Flagged Spam' : 'Flag Spam' }}</span>
                        </button>

                        <button
                            wire:click="blockSenderFromMessage({{ $inspectedMessage->id }})"
                            wire:confirm="Are you sure you want to block this sender? They will not be able to contact you about any of your listings."
                            class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-xs font-bold text-slate-700 shadow-2xs transition flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            <span>Block Sender</span>
                        </button>

                        <button
                            wire:click="deleteMessage({{ $inspectedMessage->id }})"
                            wire:confirm="Permanently delete this message?"
                            class="px-3 py-1.5 rounded-xl bg-rose-50 border border-rose-200 hover:bg-rose-100 text-xs font-bold text-rose-700 transition flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 2: PROMOTE LISTING MODAL -->
    @if($showPromoteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fadeIn">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>★ Promote Classified Listing</span>
                    </h3>
                    <button wire:click="closePromoteModal" class="text-slate-400 hover:text-slate-900 text-lg font-bold p-1 rounded-lg hover:bg-slate-100 transition">&times;</button>
                </div>

                <form wire:submit="activatePromotion" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            Select Listing to Feature <span class="text-rose-600">*</span>
                        </label>
                        <select wire:model="promoteListingId" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900">
                            <option value="">Choose an active ad...</option>
                            @foreach($userActiveListings as $actAd)
                                <option value="{{ $actAd->id }}">{{ $actAd->title }}</option>
                            @endforeach
                        </select>
                        @error('promoteListingId') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            Promotion Package <span class="text-rose-600">*</span>
                        </label>
                        <select wire:model="selectedPlanId" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900">
                            @foreach($pricingPlans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} — ${{ number_format($plan->price_cents / 100, 2) }} ({{ $plan->duration_days }} days)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            Placement Priority <span class="text-rose-600">*</span>
                        </label>
                        <select wire:model="selectedPlacement" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900">
                            <option value="category_top">Top of Category & Search</option>
                            <option value="city_top">City Spotlight Banner</option>
                            <option value="homepage">Homepage Spotlight Showcase</option>
                        </select>
                    </div>

                    <div class="p-3 bg-purple-500/10 border border-purple-500/30 rounded-xl text-[11px] text-purple-700">
                        Featured ads receive highlighted borders, prioritized search indexing, and 3x average visitor engagement.
                    </div>

                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" wire:click="closePromoteModal" class="px-4 py-2 bg-slate-100 text-slate-600 text-xs font-semibold rounded-xl">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl shadow-lg">Confirm & Activate Spotlight</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 3: CONFIRM DELETE LISTING -->
    @if($showDeleteListingModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-fadeIn">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-sm w-full p-6 space-y-4 shadow-2xl">
                <h3 class="text-lg font-black text-slate-950">Delete Listing?</h3>
                <p class="text-xs font-semibold text-slate-700 leading-relaxed">
                    Are you sure you want to permanently delete this classified advertisement? This action cannot be undone.
                </p>
                <div class="pt-2 flex justify-end gap-3">
                    <button wire:click="$set('showDeleteListingModal', false)" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl transition shadow-2xs">Cancel</button>
                    <button wire:click="deleteListing" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-black text-xs rounded-xl shadow-xs transition">Delete Ad</button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 4: CONFIRM ACCOUNT DELETION -->
    @if($showDeleteAccountModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-fadeIn">
            <div class="bg-white border border-rose-200 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <h3 class="text-lg font-black text-rose-700">Permanently Delete Account?</h3>
                <p class="text-xs font-semibold text-slate-700 leading-relaxed">
                    This action is immediate and irreversible. All your active classified advertisements, saved favorites, uploaded photos, and message archives will be permanently purged in accordance with privacy laws.
                </p>

                <div class="space-y-1">
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider">Confirm Current Password:</label>
                    <input
                        type="password"
                        wire:model="deleteAccountPassword"
                        placeholder="Enter your account password..."
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-950 font-bold placeholder-slate-400 focus:ring-2 focus:ring-rose-500"
                    >
                    @error('deleteAccountPassword') <span class="text-rose-600 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button wire:click="$set('showDeleteAccountModal', false)" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold rounded-xl transition shadow-2xs">Cancel</button>
                    <button wire:click="deleteAccount" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-black text-xs rounded-xl shadow-xs transition">Permanently Delete My Account</button>
                </div>
            </div>
        </div>
    @endif
</div>

