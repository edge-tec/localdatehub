@php
    $navCategories = \App\Modules\Categories\Models\Category::where('is_active', true)->whereNull('parent_id')->take(8)->get();
    $navStates = \App\Modules\Locations\Models\State::where('is_active', true)->orderBy('name')->take(12)->get();
    $navCities = \App\Modules\Locations\Models\City::with('state')->where('is_popular', true)->take(10)->get();

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
    if ($navUser) {
        try {
            $navUnreadMessages = \App\Modules\Contact\Models\ContactMessage::where('recipient_id', $navUser->id)->where('is_read', false)->count();
            $navUnreadNotifs = \App\Modules\Notifications\Models\NotificationLog::where('user_id', $navUser->id)->where('is_read', false)->count();
            $navRecentMessages = \App\Modules\Contact\Models\ContactMessage::with('listing')->where('recipient_id', $navUser->id)->latest('id')->take(3)->get();
            $navRecentNotifs = \App\Modules\Notifications\Models\NotificationLog::where('user_id', $navUser->id)->latest('id')->take(3)->get();
        } catch (\Throwable $e) {
        }
    }
@endphp

<header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-xs" x-data="{ mobileMenuOpen: false, browseOpen: false, statesOpen: false, citiesOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <!-- Left: Logo & Tagline -->
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                    @if($hasCustomNavLogo || $hasCustomMobileLogo)
                        @if($hasCustomMobileLogo)
                            <img src="{{ asset($navMobileLogoUrl) }}" alt="LocalDateHub" class="h-10 w-10 object-contain sm:hidden" onerror="this.style.display='none'">
                        @endif
                        <img src="{{ asset($hasCustomNavLogo ? $navLogoUrl : $navMobileLogoUrl) }}" alt="LocalDateHub" class="h-11 max-w-[200px] object-contain {{ $hasCustomMobileLogo ? 'hidden sm:block' : '' }}" onerror="this.style.display='none'">
                    @else
                        <!-- Pink Heart Icon -->
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-[#F20B65] transition group-hover:scale-105">
                            <svg class="w-9 h-9 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black tracking-tight text-slate-900 flex items-center leading-none">
                                LocalDate<span class="text-[#F20B65]">Hub</span>
                            </div>
                            <div class="text-[11px] font-medium text-slate-500 tracking-wider mt-1">
                                Connect • Meet • Enjoy
                            </div>
                        </div>
                    @endif
                </a>
            </div>

            <!-- Center: Navigation Links -->
            <nav class="hidden lg:flex items-center space-x-1 font-semibold text-sm text-slate-700">
                <!-- Home Button (Pink Pill) -->
                <a href="{{ route('home') }}" class="bg-[#F20B65] text-white px-3.5 py-1.5 rounded-md flex items-center gap-1.5 shadow-xs hover:bg-[#d90858] transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                    </svg>
                    <span>Home</span>
                </a>

                <!-- Browse Ads Dropdown -->
                <div class="relative" @click.away="browseOpen = false">
                    <button @click="browseOpen = !browseOpen; statesOpen = false; citiesOpen = false" class="px-3 py-2 rounded-md hover:text-[#F20B65] transition flex items-center gap-1">
                        <span>Browse Ads</span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': browseOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="browseOpen" x-cloak x-transition class="absolute left-0 mt-2 w-56 rounded-xl bg-white border border-slate-200 shadow-xl py-2 z-50">
                        @foreach($navCategories as $cat)
                            <a href="{{ route('categories.show', $cat->slug) }}" class="block px-4 py-2 text-xs text-slate-700 hover:text-[#F20B65] hover:bg-pink-50 transition">
                                {{ $cat->name }}
                            </a>
                        @endforeach
                        <div class="border-t border-slate-100 my-1"></div>
                        <a href="{{ route('categories.index') }}" class="block px-4 py-2 text-xs font-semibold text-[#F20B65] hover:underline">
                            View All Categories →
                        </a>
                    </div>
                </div>

                <!-- States Dropdown -->
                <div class="relative" @click.away="statesOpen = false">
                    <button @click="statesOpen = !statesOpen; browseOpen = false; citiesOpen = false" class="px-3 py-2 rounded-md hover:text-[#F20B65] transition flex items-center gap-1">
                        <span>States</span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': statesOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="statesOpen" x-cloak x-transition class="absolute left-0 mt-2 w-64 rounded-xl bg-white border border-slate-200 shadow-xl py-2 z-50 grid grid-cols-2 gap-1 p-2">
                        @foreach($navStates as $st)
                            <a href="{{ route('locations.state', strtolower($st->code)) }}" class="px-2 py-1.5 text-xs text-slate-700 hover:text-[#F20B65] hover:bg-pink-50 rounded transition">
                                {{ $st->name }}
                            </a>
                        @endforeach
                        <div class="col-span-2 border-t border-slate-100 mt-1 pt-1.5">
                            <a href="{{ route('locations.index') }}" class="block px-2 text-xs font-semibold text-[#F20B65] hover:underline">
                                View All 50 States →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Cities Dropdown -->
                <div class="relative" @click.away="citiesOpen = false">
                    <button @click="citiesOpen = !citiesOpen; browseOpen = false; statesOpen = false" class="px-3 py-2 rounded-md hover:text-[#F20B65] transition flex items-center gap-1">
                        <span>Cities</span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': citiesOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="citiesOpen" x-cloak x-transition class="absolute left-0 mt-2 w-56 rounded-xl bg-white border border-slate-200 shadow-xl py-2 z-50">
                        @foreach($navCities as $ct)
                            <a href="{{ route('locations.city', ['stateCode' => strtolower($ct->state?->code ?? 'ca'), 'citySlug' => $ct->slug]) }}" class="block px-4 py-2 text-xs text-slate-700 hover:text-[#F20B65] hover:bg-pink-50 transition">
                                {{ $ct->name }}, {{ $ct->state?->code }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <a href="{{ route('pages.about') }}" class="px-3 py-2 rounded-md hover:text-[#F20B65] transition">About</a>
                <a href="{{ route('pages.contact') }}" class="px-3 py-2 rounded-md hover:text-[#F20B65] transition">Contact</a>
            </nav>

            <!-- Right: Account Controls, Login, Register & Post Ad (Desktop Only) -->
            <div class="hidden lg:flex items-center space-x-2.5">
                @guest
                    <!-- Create Account Button -->
                    <a href="{{ route('register') }}" class="border border-[#F20B65]/40 hover:border-[#F20B65] bg-rose-50/60 hover:bg-rose-50 text-[#F20B65] px-3.5 py-1.5 rounded-xl flex items-center gap-1.5 text-xs font-bold transition shadow-2xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span>Create Account</span>
                    </a>

                    <!-- Login Button -->
                    <a href="{{ route('login') }}" class="border border-slate-300 bg-white hover:bg-slate-50 hover:border-slate-400 text-slate-800 px-3.5 py-1.5 rounded-xl flex items-center gap-1.5 text-xs font-bold transition shadow-xs">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                        <span>Login</span>
                    </a>
                @else
                    <!-- Dedicated User Messenger Icon Button -->
                    <a 
                        href="{{ route('account.messages.index') }}" 
                        class="w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-600 hover:text-[#F20B65] transition relative shadow-xs group"
                        title="Messenger"
                    >
                        <svg class="w-4 h-4 fill-current text-slate-600 group-hover:text-[#F20B65] transition" viewBox="0 0 24 24">
                            <path d="M12 2C6.477 2 2 6.145 2 11.258c0 2.91 1.455 5.518 3.736 7.208V22l3.418-1.874c.91.252 1.872.388 2.846.388 5.523 0 10-4.145 10-9.258C22 6.145 17.523 2 12 2zm1.05 12.443l-2.625-2.8-5.125 2.8 5.637-5.986 2.688 2.8 5.062-2.8-5.637 5.986z"/>
                        </svg>
                        @if($navUnreadMessages > 0)
                            <span class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-[#F20B65] text-white text-[10px] font-black flex items-center justify-center shadow">
                                {{ $navUnreadMessages > 99 ? '99+' : $navUnreadMessages }}
                            </span>
                        @endif
                    </a>

                    <!-- User Notifications Bell -->
                    <div class="relative" x-data="{ userBellOpen: false, unreadNotifs: {{ $navUnreadNotifs }}, seenNow: {{ $navUnreadNotifs === 0 ? 'true' : 'false' }}, markSeen() { if (this.unreadNotifs > 0) { this.unreadNotifs = 0; this.seenNow = true; fetch('{{ route('notifications.mark-seen') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ scope: 'user' }) }); } } }" @click.away="userBellOpen = false">
                        <button
                            @click="userBellOpen = !userBellOpen; if (userBellOpen) { markSeen(); }"
                            class="w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-600 hover:text-[#F20B65] transition relative shadow-xs"
                            title="Notifications"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span x-show="unreadNotifs > 0" x-cloak x-text="unreadNotifs > 99 ? '99+' : unreadNotifs" class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-[#F20B65] text-white text-[10px] font-black flex items-center justify-center shadow">
                                {{ $navUnreadNotifs }}
                            </span>
                        </button>

                        <div x-show="userBellOpen" x-cloak class="absolute right-0 mt-2 w-80 rounded-2xl bg-white border border-slate-200 shadow-2xl p-3.5 z-50 text-xs">
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                                <span class="font-black text-slate-900 flex items-center gap-1.5">
                                    <span>Notifications & Alerts</span>
                                    <span x-show="seenNow" class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black">✓✓ Seen</span>
                                </span>
                                <a href="{{ route('account.dashboard', ['tab' => 'notifications']) }}" class="text-[11px] font-bold text-[#F20B65] hover:underline">All Alerts &rarr;</a>
                            </div>

                            <div class="space-y-2 max-h-72 overflow-y-auto pr-0.5">
                                @forelse($navRecentNotifs as $notif)
                                    <a href="{{ route('account.dashboard', ['tab' => 'notifications', 'inspect_notification' => $notif->id]) }}" class="block p-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 transition">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-bold text-slate-900 truncate">🔔 {{ $notif->subject ?: 'Platform Notification' }}</span>
                                            <span class="text-[10px] font-black text-emerald-700 shrink-0" x-show="seenNow || {{ $notif->is_read ? 'true' : 'false' }}">✓✓ Seen</span>
                                        </div>
                                        @if($notif->message)
                                            <div class="text-[11px] text-slate-600 truncate mt-0.5">{{ $notif->message }}</div>
                                        @endif
                                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $notif->sent_at?->diffForHumans() ?? $notif->created_at?->diffForHumans() }}</div>
                                    </a>
                                @empty
                                    <div class="py-6 text-center text-slate-400 font-semibold">
                                        No new notifications.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Authenticated User Menu Dropdown -->
                    <div class="relative" x-data="{ userMenuOpen: false }" @click.away="userMenuOpen = false">
                        <button 
                            @click="userMenuOpen = !userMenuOpen"
                            class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-800 px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-2 group"
                        >
                            <span class="w-6 h-6 rounded-full bg-rose-100 text-[#F20B65] flex items-center justify-center font-black text-xs shrink-0 overflow-hidden">
                                @if(Auth::user()->avatar_url)
                                    <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr(Auth::user()->name ?: 'U', 0, 1)) }}
                                @endif
                            </span>
                            <span class="max-w-[120px] truncate text-slate-800 group-hover:text-slate-950 font-bold">
                                {{ Auth::user()->profile?->display_name ?: (Auth::user()->name ?: 'Account') }}
                            </span>
                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="{ 'rotate-180': userMenuOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div 
                            x-show="userMenuOpen" 
                            x-cloak 
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                            class="absolute right-0 mt-2 w-52 rounded-2xl bg-white border border-slate-200 shadow-xl py-2 z-50 text-xs"
                        >
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-[11px] text-slate-500 font-medium">Signed in as</p>
                                <p class="font-bold text-slate-900 truncate">{{ Auth::user()->email }}</p>
                            </div>

                            <a href="{{ route('account.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:text-[#F20B65] hover:bg-rose-50/70 font-semibold transition">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                <span>Dashboard</span>
                            </a>

                            <a href="{{ route('account.messages.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:text-[#F20B65] hover:bg-rose-50/70 font-semibold transition">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                                <span>Messenger</span>
                            </a>

                            <a href="{{ route('account.dashboard', ['tab' => 'listings']) }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:text-[#F20B65] hover:bg-rose-50/70 font-semibold transition">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <span>My Listings</span>
                            </a>

                            <a href="{{ route('account.dashboard', ['tab' => 'security']) }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:text-[#F20B65] hover:bg-rose-50/70 font-semibold transition">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                <span>Change Password</span>
                            </a>

                            @if(Auth::user()->is_admin ?? false)
                                <div class="border-t border-slate-100 my-1"></div>
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-rose-600 hover:bg-rose-50/70 font-bold transition">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <span>Admin Panel</span>
                                </a>
                            @endif

                            <div class="border-t border-slate-100 my-1"></div>

                            <!-- Logout Form & Trigger -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-rose-600 hover:bg-rose-50 font-bold transition">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endguest

                <!-- Post Ad Button (Bright Pink) -->
                <a href="{{ route('listings.create') }}" class="bg-[#F20B65] hover:bg-[#d90858] text-white px-4 py-1.5 rounded-xl flex items-center gap-1.5 text-xs font-bold shadow-md shadow-[#F20B65]/20 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Post Ad</span>
                </a>
            </div>

            <!-- Mobile & Tablet Header Actions (Clean, Zero Duplication) -->
            <div class="flex items-center gap-2 lg:hidden">
                @auth
                    <a href="{{ route('account.messages.index') }}" class="w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-700 hover:text-[#F20B65] transition relative shadow-2xs" title="Messenger">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M12 2C6.477 2 2 6.145 2 11.258c0 2.91 1.455 5.518 3.736 7.208V22l3.418-1.874c.91.252 1.872.388 2.846.388 5.523 0 10-4.145 10-9.258C22 6.145 17.523 2 12 2zm1.05 12.443l-2.625-2.8-5.125 2.8 5.637-5.986 2.688 2.8 5.062-2.8-5.637 5.986z"/>
                        </svg>
                        @if(($navUnreadMessages ?? 0) > 0)
                            <span class="absolute -top-1 -right-1 min-w-[15px] h-4 px-1 rounded-full bg-[#F20B65] text-white text-[9px] font-black flex items-center justify-center shadow">
                                {{ $navUnreadMessages > 99 ? '99+' : $navUnreadMessages }}
                            </span>
                        @endif
                    </a>
                @endauth

                <button 
                    @click="mobileMenuOpen = !mobileMenuOpen" 
                    class="w-10 h-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-800 flex items-center justify-center transition shadow-2xs focus:outline-none"
                    aria-label="Toggle navigation menu"
                >
                    <svg class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-90': mobileMenuOpen }" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Menu (Slide-down & Fully Responsive, Zero Duplication) -->
    <div 
        x-show="mobileMenuOpen" 
        x-cloak 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="lg:hidden bg-white border-t border-slate-200 px-4 py-5 shadow-2xl space-y-4 max-h-[85vh] overflow-y-auto"
    >
        <!-- 1. Quick Search Box -->
        <form method="GET" action="{{ route('search.index') }}" class="relative w-full">
            <input 
                type="text" 
                name="q" 
                placeholder="Search personals, cities, categories..." 
                class="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#F20B65] focus:bg-white transition"
            />
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
        </form>

        <!-- 2. Account Status Card (Guest or Member - Appears Only Once) -->
        @guest
            <div class="p-4 rounded-2xl bg-gradient-to-br from-rose-50/70 via-pink-50/40 to-slate-50 border border-pink-100 shadow-2xs">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <div class="text-xs font-black text-slate-900">Welcome to LocalDateHub</div>
                        <div class="text-[11px] font-medium text-slate-500">Connect with local verified members</div>
                    </div>
                    <span class="w-7 h-7 rounded-full bg-pink-100 text-[#F20B65] flex items-center justify-center text-xs font-black">
                        ♥
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('login') }}" class="py-2.5 px-3 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-bold text-xs text-center transition shadow-2xs flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                        <span>Login</span>
                    </a>
                    <a href="{{ route('register') }}" class="py-2.5 px-3 rounded-xl bg-[#F20B65] hover:bg-[#d90858] text-white font-bold text-xs text-center transition shadow-xs flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span>Create Account</span>
                    </a>
                </div>
                <div class="text-center mt-2.5">
                    <a href="{{ route('password.request') }}" class="text-[11px] font-semibold text-slate-400 hover:text-[#F20B65] transition">Forgot your password?</a>
                </div>
            </div>
        @else
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 shadow-2xs">
                <div class="flex items-center gap-3 mb-3">
                    <span class="w-10 h-10 rounded-full bg-rose-100 text-[#F20B65] flex items-center justify-center font-black text-sm shrink-0 overflow-hidden border border-rose-200">
                        @if(Auth::user()->avatar_url)
                            <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(substr(Auth::user()->name ?: 'U', 0, 1)) }}
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-black text-slate-900 truncate">
                            {{ Auth::user()->profile?->display_name ?: (Auth::user()->name ?: 'Member') }}
                        </div>
                        <div class="text-[11px] font-medium text-slate-500 truncate">
                            {{ Auth::user()->email }}
                        </div>
                    </div>
                    @if(Auth::user()->is_admin ?? false)
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-purple-100 text-purple-700">Admin</span>
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs font-bold">
                    <a href="{{ route('account.dashboard') }}" class="p-2.5 rounded-xl bg-white border border-slate-200 hover:border-slate-300 text-slate-700 flex items-center gap-2 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('account.messages.index') }}" class="p-2.5 rounded-xl bg-white border border-slate-200 hover:border-slate-300 text-slate-700 flex items-center justify-between transition">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#F20B65]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.145 2 11.258c0 2.91 1.455 5.518 3.736 7.208V22l3.418-1.874c.91.252 1.872.388 2.846.388 5.523 0 10-4.145 10-9.258C22 6.145 17.523 2 12 2zm1.05 12.443l-2.625-2.8-5.125 2.8 5.637-5.986 2.688 2.8 5.062-2.8-5.637 5.986z"/></svg>
                            <span>Messages</span>
                        </span>
                        @if(($navUnreadMessages ?? 0) > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black bg-[#F20B65] text-white">
                                {{ $navUnreadMessages }}
                            </span>
                        @endif
                    </a>
                </div>
            </div>
        @endguest

        <!-- 3. Navigation Links List (With Modern Vector Icons & Accordions) -->
        <div class="space-y-1">
            <!-- Home -->
            <a href="{{ route('home') }}" class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-slate-50 transition group">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-pink-50 text-[#F20B65] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                    </span>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-[#F20B65] transition">Home</div>
                        <div class="text-[10px] text-slate-500 font-medium">Explore local advertisements</div>
                    </div>
                </div>
                <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>

            <!-- Browse Categories (With Smooth Expandable Accordion) -->
            <div x-data="{ openCat: false }" class="rounded-2xl transition" :class="openCat ? 'bg-slate-50/80 p-1.5' : ''">
                <button @click="openCat = !openCat" type="button" class="w-full flex items-center justify-between p-2.5 rounded-2xl hover:bg-slate-50 transition text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                        </span>
                        <div>
                            <div class="text-xs font-black text-slate-900">Browse Categories</div>
                            <div class="text-[10px] text-slate-500 font-medium">Personal, Dating &amp; Community</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="openCat ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="openCat" x-cloak class="pt-2 pl-12 pr-2 space-y-1.5 pb-2">
                    @foreach($navCategories as $cat)
                        <a href="{{ route('categories.show', $cat->slug) }}" class="block text-xs font-semibold text-slate-600 hover:text-[#F20B65] py-1 transition">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                    <a href="{{ route('categories.index') }}" class="block text-xs font-bold text-[#F20B65] hover:underline pt-1">
                        View All Categories →
                    </a>
                </div>
            </div>

            <!-- Locations / States (With Smooth Expandable Accordion) -->
            <div x-data="{ openStates: false }" class="rounded-2xl transition" :class="openStates ? 'bg-slate-50/80 p-1.5' : ''">
                <button @click="openStates = !openStates" type="button" class="w-full flex items-center justify-between p-2.5 rounded-2xl hover:bg-slate-50 transition text-left cursor-pointer">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        </span>
                        <div>
                            <div class="text-xs font-black text-slate-900">US States &amp; Cities</div>
                            <div class="text-[10px] text-slate-500 font-medium">Browse ads across 50 US States</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="openStates ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="openStates" x-cloak class="pt-2 pl-12 pr-2 pb-2">
                    <div class="grid grid-cols-2 gap-1.5 mb-2">
                        @foreach($navStates->take(8) as $st)
                            <a href="{{ route('locations.state', strtolower($st->code)) }}" class="px-2 py-1 text-xs text-slate-600 hover:text-[#F20B65] hover:bg-pink-50 rounded transition">
                                {{ $st->name }}
                            </a>
                        @endforeach
                    </div>
                    <a href="{{ route('locations.index') }}" class="block text-xs font-bold text-[#F20B65] hover:underline pt-1">
                        View All 50 States &amp; Cities →
                    </a>
                </div>
            </div>

            <!-- Search Personals -->
            <a href="{{ route('search.index') }}" class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-slate-50 transition group">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    </span>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-emerald-600 transition">Search Personals</div>
                        <div class="text-[10px] text-slate-500 font-medium">Filter by age, photo, distance</div>
                    </div>
                </div>
                <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>

            <!-- Safety Standards -->
            <a href="{{ route('pages.safety') }}" class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-slate-50 transition group">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                    </span>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-amber-600 transition">Safety &amp; Compliance</div>
                        <div class="text-[10px] text-slate-500 font-medium">100% 18+ verified platform standards</div>
                    </div>
                </div>
                <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>

            <!-- Contact & Support -->
            <a href="{{ route('pages.contact') }}" class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-slate-50 transition group">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                    </span>
                    <div>
                        <div class="text-xs font-black text-slate-900 group-hover:text-indigo-600 transition">Help &amp; Support</div>
                        <div class="text-[10px] text-slate-500 font-medium">24/7 dedicated support &amp; inquiries</div>
                    </div>
                </div>
                <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        </div>

        @auth
            <!-- Sign Out (Authenticated) -->
            <div class="pt-2 border-t border-slate-100">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-rose-600 hover:bg-rose-50 font-bold text-xs transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Sign Out of Account</span>
                    </button>
                </form>
            </div>
        @endauth

        <!-- 4. Prominent Post Ad Primary CTA (Shown Exactly Once) -->
        <div class="pt-2">
            <a 
                href="{{ route('listings.create') }}" 
                class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-[#F20B65] to-[#d90858] hover:from-[#d90858] hover:to-[#be074e] text-white font-black text-sm text-center shadow-lg shadow-[#F20B65]/25 flex items-center justify-center gap-2 transition transform active:scale-98"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Post Free Classified Ad</span>
            </a>
        </div>
    </div>
</header>
