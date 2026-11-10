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

            <!-- Right: Account Controls, Login, Register & Post Ad -->
            <div class="hidden sm:flex items-center space-x-2.5">
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
                    @php
                        $navUser = Auth::user();
                        $navUnreadMessages = \App\Modules\Contact\Models\ContactMessage::where('recipient_id', $navUser->id)->where('is_read', false)->count();
                        $navUnreadNotifs = \App\Modules\Notifications\Models\NotificationLog::where('user_id', $navUser->id)->where('is_read', false)->count();
                        $navTotalUnread = $navUnreadMessages + $navUnreadNotifs;
                        $navRecentMessages = \App\Modules\Contact\Models\ContactMessage::with('listing')->where('recipient_id', $navUser->id)->whereNull('parent_message_id')->latest('id')->take(3)->get();
                        $navRecentNotifs = \App\Modules\Notifications\Models\NotificationLog::where('user_id', $navUser->id)->latest('id')->take(3)->get();
                    @endphp

                    <!-- User Notification & Message Bell -->
                    <div class="relative" x-data="{ userBellOpen: false, unreadTotal: {{ $navTotalUnread }}, seenNow: {{ $navTotalUnread === 0 ? 'true' : 'false' }}, markSeen() { if (this.unreadTotal > 0) { this.unreadTotal = 0; this.seenNow = true; fetch('{{ route('notifications.mark-seen') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ scope: 'user' }) }); } } }" @click.away="userBellOpen = false">
                        <button
                            @click="userBellOpen = !userBellOpen; if (userBellOpen) { markSeen(); }"
                            class="w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-600 hover:text-[#F20B65] transition relative shadow-xs"
                            title="Messages & Notifications"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span x-show="unreadTotal > 0" x-cloak x-text="unreadTotal > 99 ? '99+' : unreadTotal" class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-[#F20B65] text-white text-[10px] font-black flex items-center justify-center shadow">
                                {{ $navTotalUnread }}
                            </span>
                        </button>

                        <div x-show="userBellOpen" x-cloak class="absolute right-0 mt-2 w-80 rounded-2xl bg-white border border-slate-200 shadow-2xl p-3.5 z-50 text-xs">
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                                <span class="font-black text-slate-900 flex items-center gap-1.5">
                                    <span>Messages & Alerts</span>
                                    <span x-show="seenNow" class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black">✓✓ Seen</span>
                                </span>
                                <a href="{{ route('account.dashboard', ['tab' => 'notifications']) }}" class="text-[11px] font-bold text-[#F20B65] hover:underline">All Alerts &rarr;</a>
                            </div>

                            <div class="space-y-2 max-h-72 overflow-y-auto pr-0.5">
                                @foreach($navRecentMessages as $msg)
                                    <a href="{{ route('account.dashboard', ['tab' => 'messages', 'inspect_message' => $msg->id]) }}" class="block p-2.5 rounded-xl border transition {{ $msg->is_read ? 'bg-slate-50 border-slate-200 hover:bg-slate-100' : 'bg-rose-50/70 border-rose-200 hover:bg-rose-50' }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-bold text-slate-900 truncate">💬 {{ $msg->sender_name ?: 'Member Inquiry' }}</span>
                                            @if($msg->is_read)
                                                <span class="text-[10px] font-black text-emerald-700 shrink-0">✓✓ Seen</span>
                                            @else
                                                <span class="text-[10px] font-black text-[#F20B65] shrink-0">● Unread</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-600 truncate mt-0.5">{{ $msg->message }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $msg->created_at?->diffForHumans() }}</div>
                                    </a>
                                @endforeach

                                @foreach($navRecentNotifs as $notif)
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
                                @endforeach

                                @if($navRecentMessages->isEmpty() && $navRecentNotifs->isEmpty())
                                    <div class="py-6 text-center text-slate-400 font-semibold">
                                        No messages or notifications yet.
                                    </div>
                                @endif
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

            <!-- Mobile Hamburger Button -->
            <div class="flex items-center lg:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-slate-700 hover:text-slate-900 p-2 rounded-md focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Menu -->
    <div x-show="mobileMenuOpen" x-cloak class="lg:hidden bg-white border-t border-slate-200 px-4 pt-3 pb-6 space-y-2">
        <a href="{{ route('home') }}" class="block px-3 py-2 rounded-md text-sm font-semibold bg-[#F20B65] text-white">Home</a>
        <a href="{{ route('categories.index') }}" class="block px-3 py-2 rounded-md text-sm text-slate-700 hover:bg-slate-50">Browse Categories</a>
        <a href="{{ route('locations.index') }}" class="block px-3 py-2 rounded-md text-sm text-slate-700 hover:bg-slate-50">All US States</a>
        <a href="{{ route('search.index') }}" class="block px-3 py-2 rounded-md text-sm text-slate-700 hover:bg-slate-50">Search Personals</a>
        <a href="{{ route('pages.about') }}" class="block px-3 py-2 rounded-md text-sm text-slate-700 hover:bg-slate-50">About</a>
        <a href="{{ route('pages.contact') }}" class="block px-3 py-2 rounded-md text-sm text-slate-700 hover:bg-slate-50">Contact</a>
        <div class="pt-3 border-t border-slate-200 flex flex-col gap-2">
            @guest
                <div class="flex items-center space-x-2">
                    <a href="{{ route('login') }}" class="flex-1 text-center py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 bg-white">Login</a>
                    <a href="{{ route('register') }}" class="flex-1 text-center py-2.5 rounded-xl bg-rose-50 text-[#F20B65] border border-rose-200 text-xs font-bold">Create Account</a>
                </div>
                <div class="text-center pt-1">
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-slate-500 hover:text-[#F20B65]">Forgot Password?</a>
                </div>
            @else
                <div class="flex items-center space-x-2">
                    <a href="{{ route('account.dashboard') }}" class="flex-1 text-center py-2.5 rounded-xl bg-white border border-slate-300 text-xs font-bold text-slate-700">Account Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full text-center py-2.5 rounded-xl bg-rose-50 text-rose-600 border border-rose-200 text-xs font-bold">Logout</button>
                    </form>
                </div>
            @endguest
            <a href="{{ route('listings.create') }}" class="w-full text-center py-2.5 rounded-xl bg-[#F20B65] text-xs font-bold text-white shadow-md shadow-[#F20B65]/20">+ Post Free Classified Ad</a>
        </div>
    </div>
</header>
