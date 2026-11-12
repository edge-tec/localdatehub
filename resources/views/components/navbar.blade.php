@php
    $navCategories = \App\Modules\Categories\Models\Category::where('is_active', true)->whereNull('parent_id')->orderBy('name')->take(12)->get();
    $navStates = \App\Modules\Locations\Models\State::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'slug']);
    $navCities = \App\Modules\Locations\Models\City::with('state:id,code,name')
        ->where('is_active', true)
        ->orderByDesc('is_popular')
        ->orderBy('name')
        ->take(60)
        ->get(['id', 'state_id', 'name', 'slug', 'is_popular']);

    $navLogoUrl = '';
    $navMobileLogoUrl = '';
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
            $navBrand = \App\Modules\Admin\Models\SystemSetting::whereIn('key', [
                'site_logo_url',
                'site_mobile_logo_url',
            ])->pluck('value', 'key');
            $navLogoUrl = $navBrand['site_logo_url'] ?? '';
            $navMobileLogoUrl = $navBrand['site_mobile_logo_url'] ?? '';
        }
    } catch (\Throwable $e) {
    }
    $hasCustomNavLogo = !empty($navLogoUrl) && !in_array($navLogoUrl, ['/images/logo.png', '/images/logo.svg'], true);
    $hasCustomMobileLogo = !empty($navMobileLogoUrl) && !in_array($navMobileLogoUrl, ['/images/logo-mobile.png', '/images/logo-mobile.svg'], true);

    $navUser = Auth::user();
    $navUnreadMessages = 0;
    $navUnreadNotifs = 0;
    $navRecentMessages = collect();
    $navRecentNotifs = collect();
    $navIsAdmin = false;
    $navAccountRole = 'Member';
    if ($navUser) {
        $navIsAdmin = (bool) ($navUser->is_admin || in_array($navUser->role, ['super_admin', 'admin', 'moderator', 'support', 'finance_admin'], true));
        $navAccountRole = $navIsAdmin
            ? ucwords(str_replace('_', ' ', (string) ($navUser->role ?: 'Administrator')))
            : ($navUser->is_identity_verified ? 'Verified Member' : 'Member');
        try {
            $navUnreadMessages = \App\Modules\Contact\Models\ContactMessage::where('recipient_id', $navUser->id)->where('is_read', false)->count();
            $navUnreadNotifs = \App\Modules\Notifications\Models\NotificationLog::where('user_id', $navUser->id)->where('is_read', false)->count();
            $navRecentMessages = \App\Modules\Contact\Models\ContactMessage::with(['listing:id,title,slug', 'sender:id,name'])->where('recipient_id', $navUser->id)->latest('id')->take(4)->get();
            $navRecentNotifs = \App\Modules\Notifications\Models\NotificationLog::where('user_id', $navUser->id)->latest('id')->take(4)->get();
        } catch (\Throwable $e) {
        }
    }
@endphp

<style>
    :root {
        --ldh-pink: #F50057;
        --ldh-pink-hover: #D8004D;
        --ldh-navy: #0F172A;
        --ldh-nav-radius: 14px;
        --ldh-dropdown-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.16), 0 0 0 1px rgba(226, 232, 240, 0.8);
    }
</style>

<header
    x-data="{
        scrolled: false,
        mobileMenuOpen: false,
        activeDropdown: null,
        stateQuery: '',
        cityQuery: '',
        toggle(name) {
            this.activeDropdown = (this.activeDropdown === name) ? null : name;
        },
        closeAll() {
            this.activeDropdown = null;
            this.mobileMenuOpen = false;
        }
    }"
    @scroll.window="scrolled = window.scrollY > 8"
    @keydown.escape.window="closeAll()"
    :class="scrolled ? 'bg-white/95 backdrop-blur-md shadow-[0_8px_30px_rgba(15,23,42,0.08)] border-slate-200/90' : 'bg-white border-slate-200/80 shadow-2xs'"
    class="sticky top-0 z-50 border-b transition-all duration-200"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-[68px] sm:h-[72px] gap-3">
            <!-- Brand Logo Section -->
            <div class="flex items-center shrink-0">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 group focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F50057]/40 rounded-xl py-1">
                    @if($hasCustomNavLogo || $hasCustomMobileLogo)
                        @if($hasCustomMobileLogo)
                            <img src="{{ asset($navMobileLogoUrl) }}" alt="LocalDateHub" class="h-9 w-9 object-contain sm:hidden transition-transform duration-200 group-hover:scale-105" onerror="this.style.display='none'">
                        @endif
                        <img src="{{ asset($hasCustomNavLogo ? $navLogoUrl : $navMobileLogoUrl) }}" alt="LocalDateHub" class="h-10 max-w-[190px] object-contain transition-transform duration-200 group-hover:scale-[1.03] {{ $hasCustomMobileLogo ? 'hidden sm:block' : '' }}" onerror="this.style.display='none'">
                    @else
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-rose-50 to-pink-100/80 border border-pink-200/70 flex items-center justify-center text-[#F50057] shadow-2xs transition-all duration-200 group-hover:scale-105 group-hover:shadow-md group-hover:shadow-pink-500/15">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                            </svg>
                        </div>
                        <div class="leading-none">
                            <div class="text-xl sm:text-[22px] font-black tracking-tight text-slate-900 flex items-center">
                                LocalDate<span class="text-[#F50057]">Hub</span>
                            </div>
                            <div class="text-[10px] font-semibold text-slate-400 tracking-[0.14em] uppercase mt-1">
                                Connect • Meet • Enjoy
                            </div>
                        </div>
                    @endif
                </a>
            </div>

            <!-- Center: Horizontal Desktop Navigation -->
            <nav class="hidden lg:flex items-center gap-1 text-[13px] font-semibold text-slate-700" aria-label="Main navigation">
                <!-- Home -->
                <a
                    href="{{ route('home') }}"
                    class="px-3.5 py-2 rounded-xl flex items-center gap-1.5 transition-all duration-150 {{ request()->routeIs('home') ? 'bg-[#F50057] text-white shadow-sm shadow-[#F50057]/25' : 'text-slate-700 hover:text-[#F50057] hover:bg-rose-50/70' }}"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span>Home</span>
                </a>

                <!-- Browse Ads Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'browse') activeDropdown = null">
                    <button
                        type="button"
                        @click="toggle('browse')"
                        :aria-expanded="activeDropdown === 'browse'"
                        aria-haspopup="true"
                        class="px-3 py-2 rounded-xl flex items-center gap-1.5 transition-all duration-150 cursor-pointer {{ request()->routeIs('categories.*') ? 'bg-rose-50 text-[#F50057]' : 'text-slate-700 hover:text-[#F50057] hover:bg-rose-50/70' }}"
                    >
                        <svg class="w-4 h-4 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect width="7" height="7" x="3" y="3" rx="1.5"/>
                            <rect width="7" height="7" x="14" y="3" rx="1.5"/>
                            <rect width="7" height="7" x="14" y="14" rx="1.5"/>
                            <rect width="7" height="7" x="3" y="14" rx="1.5"/>
                        </svg>
                        <span>Browse Ads</span>
                        <svg class="w-3.5 h-3.5 opacity-60 transition-transform duration-150" :class="{ 'rotate-180': activeDropdown === 'browse' }" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    <div
                        x-show="activeDropdown === 'browse'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1.5 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                        style="box-shadow: var(--ldh-dropdown-shadow)"
                        class="absolute left-0 mt-2.5 w-64 rounded-2xl bg-white/95 backdrop-blur-md p-2 z-50"
                    >
                        <div class="px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Classified Categories</div>
                        <div class="max-h-72 overflow-y-auto space-y-0.5">
                            @foreach($navCategories as $cat)
                                <a href="{{ route('categories.show', $cat->slug) }}" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:text-[#F50057] hover:bg-rose-50/80 transition">
                                    <span>{{ $cat->name }}</span>
                                    <svg class="w-3.5 h-3.5 opacity-40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                                </a>
                            @endforeach
                        </div>
                        <div class="border-t border-slate-100 mt-1.5 pt-1.5">
                            <a href="{{ route('categories.index') }}" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold text-[#F50057] hover:bg-rose-50 transition">
                                <span>View All Categories</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- States Searchable Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'states') activeDropdown = null">
                    <button
                        type="button"
                        @click="toggle('states'); $nextTick(() => $refs.stateSearchInput?.focus())"
                        :aria-expanded="activeDropdown === 'states'"
                        aria-haspopup="true"
                        class="px-3 py-2 rounded-xl flex items-center gap-1.5 transition-all duration-150 cursor-pointer {{ request()->routeIs('locations.state*') ? 'bg-rose-50 text-[#F50057]' : 'text-slate-700 hover:text-[#F50057] hover:bg-rose-50/70' }}"
                    >
                        <svg class="w-4 h-4 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
                            <line x1="9" x2="9" y1="3" y2="18"/>
                            <line x1="15" x2="15" y1="6" y2="21"/>
                        </svg>
                        <span>States</span>
                        <svg class="w-3.5 h-3.5 opacity-60 transition-transform duration-150" :class="{ 'rotate-180': activeDropdown === 'states' }" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    <div
                        x-show="activeDropdown === 'states'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1.5 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                        style="box-shadow: var(--ldh-dropdown-shadow)"
                        class="absolute left-0 mt-2.5 w-80 rounded-2xl bg-white/95 backdrop-blur-md p-3 z-50"
                    >
                        <div class="relative mb-2.5">
                            <input
                                x-ref="stateSearchInput"
                                x-model="stateQuery"
                                type="text"
                                placeholder="Filter states alphabetically..."
                                class="w-full pl-8 pr-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#F50057] focus:bg-white"
                            >
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>

                        <div class="max-h-64 overflow-y-auto grid grid-cols-2 gap-1 pr-1">
                            @foreach($navStates as $st)
                                <a
                                    href="{{ route('locations.state', strtolower($st->code)) }}"
                                    x-show="stateQuery.trim() === '' || '{{ strtolower($st->name . ' ' . $st->code) }}'.includes(stateQuery.toLowerCase().trim())"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 hover:text-[#F50057] hover:bg-rose-50/80 transition truncate flex items-center justify-between gap-1"
                                >
                                    <span class="truncate">{{ $st->name }}</span>
                                    <span class="text-[10px] font-bold text-slate-400">{{ $st->code }}</span>
                                </a>
                            @endforeach
                        </div>

                        <div class="border-t border-slate-100 mt-2 pt-2">
                            <a href="{{ route('locations.index') }}" class="flex items-center justify-between px-2 py-1 text-xs font-bold text-[#F50057] hover:underline">
                                <span>Browse All States &amp; Regions</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Cities Searchable Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'cities') activeDropdown = null">
                    <button
                        type="button"
                        @click="toggle('cities'); $nextTick(() => $refs.citySearchInput?.focus())"
                        :aria-expanded="activeDropdown === 'cities'"
                        aria-haspopup="true"
                        class="px-3 py-2 rounded-xl flex items-center gap-1.5 transition-all duration-150 cursor-pointer {{ request()->routeIs('locations.city') ? 'bg-rose-50 text-[#F50057]' : 'text-slate-700 hover:text-[#F50057] hover:bg-rose-50/70' }}"
                    >
                        <svg class="w-4 h-4 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span>Cities</span>
                        <svg class="w-3.5 h-3.5 opacity-60 transition-transform duration-150" :class="{ 'rotate-180': activeDropdown === 'cities' }" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    <div
                        x-show="activeDropdown === 'cities'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1.5 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                        style="box-shadow: var(--ldh-dropdown-shadow)"
                        class="absolute left-0 mt-2.5 w-72 rounded-2xl bg-white/95 backdrop-blur-md p-3 z-50"
                    >
                        <div class="relative mb-2.5">
                            <input
                                x-ref="citySearchInput"
                                x-model="cityQuery"
                                type="text"
                                placeholder="Search popular cities..."
                                class="w-full pl-8 pr-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#F50057] focus:bg-white"
                            >
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>

                        <div class="max-h-64 overflow-y-auto space-y-0.5 pr-1">
                            @foreach($navCities as $ct)
                                <a
                                    href="{{ route('locations.city', ['stateCode' => strtolower($ct->state?->code ?? 'ca'), 'citySlug' => $ct->slug]) }}"
                                    x-show="cityQuery.trim() === '' || '{{ strtolower($ct->name . ' ' . ($ct->state?->code ?? '') . ' ' . ($ct->state?->name ?? '')) }}'.includes(cityQuery.toLowerCase().trim())"
                                    class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 hover:text-[#F50057] hover:bg-rose-50/80 transition"
                                >
                                    <span class="truncate">{{ $ct->name }}, {{ $ct->state?->code }}</span>
                                    @if($ct->is_popular)
                                        <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-rose-50 text-[#F50057]">Popular</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>

                        <div class="border-t border-slate-100 mt-2 pt-2">
                            <a href="{{ route('locations.index') }}" class="flex items-center justify-between px-2 py-1 text-xs font-bold text-[#F50057] hover:underline">
                                <span>Explore All Cities</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- About -->
                <a
                    href="{{ route('pages.about') }}"
                    class="px-3 py-2 rounded-xl flex items-center gap-1.5 transition-all duration-150 {{ request()->routeIs('pages.about') ? 'bg-rose-50 text-[#F50057]' : 'text-slate-700 hover:text-[#F50057] hover:bg-rose-50/70' }}"
                >
                    <svg class="w-4 h-4 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>
                    <span>About</span>
                </a>

                <!-- Contact -->
                <a
                    href="{{ route('pages.contact') }}"
                    class="px-3 py-2 rounded-xl flex items-center gap-1.5 transition-all duration-150 {{ request()->routeIs('pages.contact') ? 'bg-rose-50 text-[#F50057]' : 'text-slate-700 hover:text-[#F50057] hover:bg-rose-50/70' }}"
                >
                    <svg class="w-4 h-4 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect width="20" height="16" x="2" y="4" rx="2"/>
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                    </svg>
                    <span>Contact</span>
                </a>
            </nav>

            <!-- Right: Account Controls, Messaging, Notifications & Post Ad (Desktop) -->
            <div class="hidden lg:flex items-center gap-2.5 shrink-0">
                @guest
                    <a href="{{ route('login') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-slate-800 text-xs font-bold transition flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" x2="3" y1="12" y2="12"/>
                        </svg>
                        <span>Sign In</span>
                    </a>

                    <a href="{{ route('register') }}" class="px-3.5 py-2 rounded-xl border border-pink-200 bg-rose-50/70 hover:bg-rose-100/70 text-[#F50057] text-xs font-bold transition flex items-center gap-1.5">
                        <span>Create Account</span>
                    </a>
                @else
                    <!-- Messages Dropdown Preview -->
                    <div class="relative" @click.away="if(activeDropdown === 'messages') activeDropdown = null">
                        <button
                            type="button"
                            @click="toggle('messages')"
                            :aria-expanded="activeDropdown === 'messages'"
                            aria-label="Messages"
                            title="Messages"
                            class="w-10 h-10 rounded-xl border border-slate-200/90 bg-white hover:bg-rose-50/70 hover:border-pink-200 flex items-center justify-center text-slate-600 hover:text-[#F50057] transition relative cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>
                            </svg>
                            @if($navUnreadMessages > 0)
                                <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-[#F50057] text-white text-[10px] font-black flex items-center justify-center shadow-sm ring-2 ring-white">
                                    {{ $navUnreadMessages > 99 ? '99+' : $navUnreadMessages }}
                                </span>
                            @endif
                        </button>

                        <div
                            x-show="activeDropdown === 'messages'"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1.5 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                            style="box-shadow: var(--ldh-dropdown-shadow)"
                            class="absolute right-0 mt-2.5 w-80 rounded-2xl bg-white/95 backdrop-blur-md p-3.5 z-50 text-xs"
                        >
                            <div class="flex items-center justify-between pb-2.5 mb-2.5 border-b border-slate-100">
                                <span class="font-black text-slate-900">Messages</span>
                                <a href="{{ route('account.messages.index') }}" class="text-[11px] font-bold text-[#F50057] hover:underline">Open Messenger &rarr;</a>
                            </div>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @forelse($navRecentMessages as $msg)
                                    <a href="{{ route('account.messages.index') }}" class="block p-2.5 rounded-xl bg-slate-50/90 hover:bg-rose-50/70 border border-slate-100 transition">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-bold text-slate-900 truncate">{{ $msg->sender?->name ?: ($msg->sender_name ?: 'Member Inquiry') }}</span>
                                            @if(!$msg->is_read)
                                                <span class="w-2 h-2 rounded-full bg-[#F50057] shrink-0"></span>
                                            @endif
                                        </div>
                                        @if($msg->listing)
                                            <div class="text-[10px] font-semibold text-[#F50057] truncate mt-0.5">{{ $msg->listing->title }}</div>
                                        @endif
                                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $msg->created_at?->diffForHumans() }}</div>
                                    </a>
                                @empty
                                    <div class="py-6 text-center text-slate-400 font-semibold">No recent messages.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Dropdown -->
                    <div
                        class="relative"
                        x-data="{
                            unreadNotifs: {{ $navUnreadNotifs }},
                            seenNow: {{ $navUnreadNotifs === 0 ? 'true' : 'false' }},
                            markSeen() {
                                if (this.unreadNotifs > 0) {
                                    this.unreadNotifs = 0;
                                    this.seenNow = true;
                                    fetch('{{ route('notifications.mark-seen') }}', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                        body: JSON.stringify({ scope: 'user' })
                                    });
                                }
                            }
                        }"
                        @click.away="if(activeDropdown === 'notifications') activeDropdown = null"
                    >
                        <button
                            type="button"
                            @click="toggle('notifications'); if (activeDropdown === 'notifications') { markSeen(); }"
                            :aria-expanded="activeDropdown === 'notifications'"
                            aria-label="Notifications"
                            title="Notifications"
                            class="w-10 h-10 rounded-xl border border-slate-200/90 bg-white hover:bg-rose-50/70 hover:border-pink-200 flex items-center justify-center text-slate-600 hover:text-[#F50057] transition relative cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                            </svg>
                            <span x-show="unreadNotifs > 0" x-cloak x-text="unreadNotifs > 99 ? '99+' : unreadNotifs" class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-[#F50057] text-white text-[10px] font-black flex items-center justify-center shadow-sm ring-2 ring-white">
                                {{ $navUnreadNotifs }}
                            </span>
                        </button>

                        <div
                            x-show="activeDropdown === 'notifications'"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1.5 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                            style="box-shadow: var(--ldh-dropdown-shadow)"
                            class="absolute right-0 mt-2.5 w-80 rounded-2xl bg-white/95 backdrop-blur-md p-3.5 z-50 text-xs"
                        >
                            <div class="flex items-center justify-between pb-2.5 mb-2.5 border-b border-slate-100">
                                <span class="font-black text-slate-900 flex items-center gap-1.5">
                                    <span>Notifications</span>
                                    <span x-show="seenNow" class="px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">Caught up</span>
                                </span>
                                <a href="{{ route('account.dashboard', ['tab' => 'notifications']) }}" class="text-[11px] font-bold text-[#F50057] hover:underline">All Alerts &rarr;</a>
                            </div>

                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @forelse($navRecentNotifs as $notif)
                                    <a href="{{ route('account.dashboard', ['tab' => 'notifications', 'inspect_notification' => $notif->id]) }}" class="block p-2.5 rounded-xl bg-slate-50/90 hover:bg-rose-50/70 border border-slate-100 transition">
                                        <div class="font-bold text-slate-900 truncate">{{ $notif->subject ?: 'Platform Notification' }}</div>
                                        @if($notif->message)
                                            <div class="text-[11px] text-slate-600 truncate mt-0.5">{{ $notif->message }}</div>
                                        @endif
                                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $notif->sent_at?->diffForHumans() ?? $notif->created_at?->diffForHumans() }}</div>
                                    </a>
                                @empty
                                    <div class="py-6 text-center text-slate-400 font-semibold">No new notifications.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Premium User Account Dropdown -->
                    <div class="relative" @click.away="if(activeDropdown === 'account') activeDropdown = null">
                        <button
                            type="button"
                            @click="toggle('account')"
                            :aria-expanded="activeDropdown === 'account'"
                            aria-haspopup="true"
                            class="border border-slate-200/90 bg-white hover:bg-slate-50 hover:border-slate-300 pl-2 pr-3 py-1.5 rounded-full text-xs transition shadow-2xs flex items-center gap-2.5 cursor-pointer group"
                        >
                            <span class="w-7 h-7 rounded-full bg-gradient-to-br from-rose-100 to-pink-200 text-[#F50057] flex items-center justify-center font-black text-xs shrink-0 overflow-hidden ring-2 ring-white">
                                @if($navUser->avatar_url)
                                    <img src="{{ $navUser->avatar_url }}" alt="{{ $navUser->name }}" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($navUser->name ?: 'U', 0, 1)) }}
                                @endif
                            </span>
                            <div class="text-left leading-tight max-w-[125px]">
                                <div class="font-bold text-slate-900 truncate">{{ $navUser->profile?->display_name ?: ($navUser->name ?: 'Account') }}</div>
                                <div class="text-[10px] font-semibold text-slate-400 truncate">{{ $navAccountRole }}</div>
                            </div>
                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-150" :class="{ 'rotate-180': activeDropdown === 'account' }" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div
                            x-show="activeDropdown === 'account'"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1.5 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                            style="box-shadow: var(--ldh-dropdown-shadow)"
                            class="absolute right-0 mt-2.5 w-60 rounded-2xl bg-white/95 backdrop-blur-md py-2 z-50 text-xs"
                        >
                            <div class="px-4 py-2.5 border-b border-slate-100">
                                <div class="font-bold text-slate-900 truncate">{{ $navUser->profile?->display_name ?: $navUser->name }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ $navUser->email }}</div>
                            </div>

                            <div class="py-1">
                                <a href="{{ route('account.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:text-[#F50057] hover:bg-rose-50/70 font-semibold transition">
                                    <span>My Dashboard</span>
                                </a>
                                <a href="{{ route('account.dashboard', ['tab' => 'profile']) }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:text-[#F50057] hover:bg-rose-50/70 font-semibold transition">
                                    <span>My Profile</span>
                                </a>
                                <a href="{{ route('account.dashboard', ['tab' => 'listings']) }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:text-[#F50057] hover:bg-rose-50/70 font-semibold transition">
                                    <span>My Advertisements</span>
                                </a>
                                <a href="{{ route('account.dashboard', ['tab' => 'favorites']) }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:text-[#F50057] hover:bg-rose-50/70 font-semibold transition">
                                    <span>Saved Ads</span>
                                </a>
                                <a href="{{ route('account.messages.index') }}" class="flex items-center justify-between px-4 py-2 text-slate-700 hover:text-[#F50057] hover:bg-rose-50/70 font-semibold transition">
                                    <span>Messages</span>
                                    @if($navUnreadMessages > 0)
                                        <span class="px-1.5 py-0.5 rounded-full bg-rose-100 text-[#F50057] text-[10px] font-black">{{ $navUnreadMessages }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('account.dashboard', ['tab' => 'security']) }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:text-[#F50057] hover:bg-rose-50/70 font-semibold transition">
                                    <span>Account Settings</span>
                                </a>
                            </div>

                            @if($navIsAdmin)
                                <div class="border-t border-slate-100 my-1 pt-1">
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-[#F50057] hover:bg-rose-50/70 font-bold transition">
                                        <span>Admin Dashboard</span>
                                    </a>
                                    <a href="{{ route('admin.live-visitors') }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:text-[#F50057] hover:bg-rose-50/70 font-semibold transition">
                                        <span>Live Visitors Monitor</span>
                                    </a>
                                </div>
                            @endif

                            <div class="border-t border-slate-100 mt-1 pt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-rose-600 hover:bg-rose-50 font-bold transition cursor-pointer">
                                        <span>Sign Out</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endguest

                <!-- Primary CTA: Post Ad -->
                <a
                    href="{{ route('listings.create') }}"
                    class="bg-gradient-to-r from-[#F50057] to-[#E1004F] hover:from-[#E1004F] hover:to-[#C80046] text-white px-4 py-2 rounded-full flex items-center gap-1.5 text-xs font-extrabold shadow-md shadow-[#F50057]/25 hover:shadow-lg hover:shadow-[#F50057]/30 hover:scale-[1.03] active:scale-[0.98] transition-all duration-150"
                >
                    <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                    </svg>
                    <span>Post Ad</span>
                </a>
            </div>

            <!-- Mobile & Tablet Right Actions -->
            <div class="flex items-center gap-2 lg:hidden">
                @auth
                    <a href="{{ route('account.messages.index') }}" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-700 hover:text-[#F50057] transition relative" aria-label="Messages">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                        @if($navUnreadMessages > 0)
                            <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-[#F50057] text-white text-[9px] font-black flex items-center justify-center">
                                {{ $navUnreadMessages > 99 ? '99+' : $navUnreadMessages }}
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('account.dashboard', ['tab' => 'notifications']) }}" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-700 hover:text-[#F50057] transition relative" aria-label="Notifications">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        @if($navUnreadNotifs > 0)
                            <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-[#F50057] text-white text-[9px] font-black flex items-center justify-center">
                                {{ $navUnreadNotifs > 99 ? '99+' : $navUnreadNotifs }}
                            </span>
                        @endif
                    </a>
                @endauth

                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    :aria-expanded="mobileMenuOpen"
                    aria-label="Toggle navigation menu"
                    class="w-10 h-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-800 flex items-center justify-center transition cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Slide-Out Navigation Drawer -->
    <div x-show="mobileMenuOpen" x-cloak class="fixed inset-0 z-50 lg:hidden">
        <div
            x-show="mobileMenuOpen"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileMenuOpen = false"
            class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs"
        ></div>

        <aside
            x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-full max-w-sm bg-white shadow-2xl flex flex-col overflow-y-auto"
        >
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <span class="text-base font-black text-slate-900">LocalDate<span class="text-[#F50057]">Hub</span></span>
                <button type="button" @click="mobileMenuOpen = false" class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center" aria-label="Close menu">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-4 space-y-4 flex-1">
                <!-- Search -->
                <form method="GET" action="{{ route('search.index') }}" class="relative">
                    <input type="text" name="q" placeholder="Search personals, cities, categories..." class="w-full pl-9 pr-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-800 focus:outline-none focus:border-[#F50057] focus:bg-white">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </form>

                <!-- Prominent Post Ad CTA -->
                <a href="{{ route('listings.create') }}" class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-[#F50057] to-[#E1004F] text-white font-black text-sm flex items-center justify-center gap-2 shadow-lg shadow-[#F50057]/25">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    <span>Post Ad</span>
                </a>

                <!-- User Account Card -->
                @guest
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('login') }}" class="py-2.5 px-3 rounded-xl border border-slate-200 text-slate-800 font-bold text-xs text-center">Sign In</a>
                        <a href="{{ route('register') }}" class="py-2.5 px-3 rounded-xl bg-rose-50 border border-pink-200 text-[#F50057] font-bold text-xs text-center">Create Account</a>
                    </div>
                @else
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-full bg-rose-100 text-[#F50057] flex items-center justify-center font-black text-sm shrink-0 overflow-hidden">
                                @if($navUser->avatar_url)
                                    <img src="{{ $navUser->avatar_url }}" alt="{{ $navUser->name }}" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($navUser->name ?: 'U', 0, 1)) }}
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-black text-slate-900 truncate">{{ $navUser->profile?->display_name ?: $navUser->name }}</div>
                                <div class="text-[11px] font-semibold text-slate-500 truncate">{{ $navAccountRole }}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs font-bold">
                            <a href="{{ route('account.dashboard') }}" class="py-2 px-3 rounded-xl bg-white border border-slate-200 text-slate-700 text-center">My Dashboard</a>
                            <a href="{{ route('account.messages.index') }}" class="py-2 px-3 rounded-xl bg-white border border-slate-200 text-slate-700 text-center">Messages</a>
                            <a href="{{ route('account.dashboard', ['tab' => 'listings']) }}" class="py-2 px-3 rounded-xl bg-white border border-slate-200 text-slate-700 text-center">My Ads</a>
                            <a href="{{ route('account.dashboard', ['tab' => 'favorites']) }}" class="py-2 px-3 rounded-xl bg-white border border-slate-200 text-slate-700 text-center">Saved Ads</a>
                        </div>
                        @if($navIsAdmin)
                            <a href="{{ route('admin.dashboard') }}" class="block py-2 px-3 rounded-xl bg-rose-50 border border-pink-200 text-[#F50057] font-bold text-xs text-center">Admin Panel</a>
                        @endif
                    </div>
                @endguest

                <!-- Mobile Navigation Links -->
                <div class="space-y-1 text-xs font-bold text-slate-800">
                    <a href="{{ route('home') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-rose-50/70"><span>Home</span><span>&rarr;</span></a>
                    <a href="{{ route('categories.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-rose-50/70"><span>Browse Ads</span><span>&rarr;</span></a>
                    <a href="{{ route('locations.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-rose-50/70"><span>States &amp; Cities</span><span>&rarr;</span></a>
                    <a href="{{ route('search.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-rose-50/70"><span>Search Personals</span><span>&rarr;</span></a>
                    <a href="{{ route('pages.about') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-rose-50/70"><span>About</span><span>&rarr;</span></a>
                    <a href="{{ route('pages.contact') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-rose-50/70"><span>Contact</span><span>&rarr;</span></a>
                </div>

                @auth
                    <form method="POST" action="{{ route('logout') }}" class="pt-2 border-t border-slate-100">
                        @csrf
                        <button type="submit" class="w-full py-2.5 rounded-xl text-rose-600 hover:bg-rose-50 font-bold text-xs text-center">Sign Out</button>
                    </form>
                @endauth
            </div>
        </aside>
    </div>
</header>
