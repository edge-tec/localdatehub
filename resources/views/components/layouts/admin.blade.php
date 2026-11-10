@php
    $adminFaviconUrl = '/favicon.ico';
    $adminLogoUrl = '';
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
            $adminBrandSettings = \App\Modules\Admin\Models\SystemSetting::whereIn('key', [
                'site_favicon_url',
                'site_logo_url',
            ])->pluck('value', 'key');
            if (!empty($adminBrandSettings['site_favicon_url'])) {
                $adminFaviconUrl = $adminBrandSettings['site_favicon_url'];
            }
            if (!empty($adminBrandSettings['site_logo_url'])) {
                $adminLogoUrl = $adminBrandSettings['site_logo_url'];
            }
        }
    } catch (\Throwable $e) {
    }
    $hasCustomAdminLogo = !empty($adminLogoUrl) && !in_array($adminLogoUrl, ['/images/logo.png', '/images/logo.svg'], true);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F8FAFC] text-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin Dashboard' }} — LocalDateHub</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset($adminFaviconUrl) }}">
    <link rel="shortcut icon" href="{{ asset($adminFaviconUrl) }}">
    <link rel="apple-touch-icon" href="{{ asset($adminFaviconUrl) }}">
    <style>[x-cloak] { display: none !important; }</style>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full flex flex-col font-sans selection:bg-[#F20B83] selection:text-white bg-[#F8FAFC] text-slate-900">

    <!-- Administrator Impersonation Active Banner -->
    @if(session()->has('impersonator_admin_id'))
        <div class="bg-gradient-to-r from-amber-600 via-orange-600 to-amber-700 text-white px-4 py-2.5 text-xs font-semibold shadow-lg sticky top-0 z-[100] border-b border-amber-400/30">
            <div class="max-w-[1720px] mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-center sm:text-left">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-white text-amber-900 uppercase tracking-wider animate-pulse">
                        ⚡ Impersonation Active
                    </span>
                    <span>
                        Staff Impersonation: Logged in as <strong class="underline decoration-white/60">{{ auth()->user()->name }}</strong> ({{ strtoupper(str_replace('_', ' ', auth()->user()->role)) }}). Original Administrator: <strong>{{ session('impersonator_admin_name', 'Administrator') }}</strong>.
                    </span>
                </div>
                <a href="{{ route('admin.impersonate.stop') }}" class="inline-flex items-center gap-1.5 px-3 py-1 bg-white hover:bg-amber-50 text-amber-900 font-extrabold rounded-lg text-xs shadow-sm transition shrink-0">
                    <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    <span>Exit &amp; Return to My Admin</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Top Administrative Navigation Bar Matching Pure White Theme -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-xs" x-data="{ userMenuOpen: false, langOpen: false, notificationsOpen: false }">
        <div class="max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-18">
                
                <!-- Left: Logo & Tagline -->
                <div class="flex items-center space-x-3 w-64 shrink-0">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-2.5 group">
                        @if($hasCustomAdminLogo)
                            <img src="{{ asset($adminLogoUrl) }}" alt="LocalDateHub" class="h-10 max-w-[180px] object-contain" onerror="this.style.display='none'">
                        @else
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-[#F20B83] group-hover:scale-105 transition">
                                <svg class="w-8 h-8 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xl font-black text-slate-900 tracking-tight leading-none">
                                    LocalDate<span class="text-[#F20B83]">Hub</span>
                                </div>
                                <div class="text-[10px] font-semibold text-slate-500 tracking-wider mt-0.5">
                                    Connect • Meet • Enjoy
                                </div>
                            </div>
                        @endif
                    </a>
                </div>

                <!-- Center: Global Search Bar -->
                <div class="flex-1 max-w-md mx-4 hidden md:block">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            placeholder="Search anything..." 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] transition"
                        />
                    </div>
                </div>

                <!-- Right: Notifications, Language & Admin Profile -->
                <div class="flex items-center space-x-4">
                    
                    <!-- Notification Bell -->
                    @php
                        $adminPendingId = 0;
                        $adminPendingListings = 0;
                        $adminUnreadReports = 0;
                        $adminUnreadMessages = 0;
                        $adminUnreadLogs = 0;
                        $adminTotalUnread = 0;
                        $adminInitialItems = [];
                        try {
                            $adminPendingId = \App\Modules\Verification\Models\IdentityVerification::where('status', 'pending')->count();
                            $adminPendingListings = \App\Modules\Listings\Models\Listing::whereIn('status', ['pending', 'under_review'])->count();
                            $adminUnreadReports = \App\Modules\Moderation\Models\ContentReport::where('is_read', false)->count();
                            $adminUnreadMessages = \App\Modules\Contact\Models\ContactMessage::where(function ($q) {
                                $q->where('is_flagged', true)
                                  ->orWhere('recipient_id', auth()->id())
                                  ->orWhereNull('listing_id');
                            })->where('is_read', false)->count();
                            $adminUnreadLogs = \App\Modules\Notifications\Models\NotificationLog::where('is_read', false)
                                ->where(function ($q) {
                                    $q->where('recipient_target', 'admin')
                                      ->orWhere('user_id', auth()->id())
                                      ->orWhereNull('user_id');
                                })->count();
                            $adminTotalUnread = $adminPendingId + $adminPendingListings + $adminUnreadReports + $adminUnreadMessages + $adminUnreadLogs;

                            $feedResponse = app(\App\Http\Controllers\Admin\AdminDashboardController::class)->notificationFeed(request());
                            $feedData = json_decode($feedResponse->getContent(), true);
                            if (!empty($feedData['items'])) {
                                $adminInitialItems = $feedData['items'];
                            }
                        } catch (\Throwable $e) {
                        }
                    @endphp
                    <div class="relative" 
                        x-data="{ 
                            unreadCount: {{ $adminTotalUnread }}, 
                            allMarkedRead: {{ $adminTotalUnread === 0 ? 'true' : 'false' }}, 
                            items: {{ Js::from($adminInitialItems) }},
                            counts: {
                                id_verifications: {{ $adminPendingId }},
                                pending_listings: {{ $adminPendingListings }},
                                reports: {{ $adminUnreadReports }},
                                messages: {{ $adminUnreadMessages }},
                                system_logs: {{ $adminUnreadLogs }}
                            },
                            notificationsOpen: false,
                            activeCategoryFilter: 'all',
                            pollTimer: null,
                            init() {
                                this.pollTimer = setInterval(() => {
                                    this.pollLatest();
                                }, 12000);
                            },
                            pollLatest() {
                                fetch('{{ route('admin.notifications.feed') }}')
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data && data.ok) {
                                            this.unreadCount = data.total_unread;
                                            this.counts = data.counts;
                                            if (data.items) {
                                                this.items = data.items;
                                            }
                                            if (this.unreadCount > 0) {
                                                this.allMarkedRead = false;
                                            }
                                        }
                                    })
                                    .catch(() => {});
                            },
                            markAllSeen() { 
                                if (this.unreadCount > 0) { 
                                    this.allMarkedRead = true; 
                                    fetch('{{ route('notifications.mark-seen') }}', { 
                                        method: 'POST', 
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, 
                                        body: JSON.stringify({ scope: 'admin' }) 
                                    }).then(() => {
                                        this.pollLatest();
                                    }); 
                                } 
                            },
                            filteredItems() {
                                if (this.activeCategoryFilter === 'all') return this.items;
                                return this.items.filter(item => item.category === this.activeCategoryFilter);
                            }
                        }" 
                        @click.away="notificationsOpen = false"
                    >
                        <button 
                            @click="notificationsOpen = !notificationsOpen; if (notificationsOpen) { markAllSeen(); }" 
                            class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 hover:text-slate-900 transition relative cursor-pointer" 
                            title="Admin Real-Time Notifications &amp; Alerts"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span 
                                x-show="unreadCount > 0" 
                                x-cloak 
                                x-text="unreadCount > 99 ? '99+' : unreadCount" 
                                class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-[#F20B65] text-white text-[10px] font-black flex items-center justify-center shadow-xs animate-pulse"
                            >
                                {{ $adminTotalUnread }}
                            </span>
                        </button>

                        <div 
                            x-show="notificationsOpen" 
                            x-cloak 
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="absolute right-0 mt-2 w-96 max-w-[calc(100vw-24px)] rounded-2xl bg-white border border-slate-200 shadow-2xl p-3.5 z-50 text-xs"
                        >
                            <!-- Dropdown Header -->
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                                <div class="font-black text-slate-900 flex items-center gap-1.5">
                                    <span>Real-Time Admin Alerts</span>
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping" title="Live Polling Active"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button 
                                        type="button" 
                                        @click="pollLatest()" 
                                        class="text-[10px] text-slate-400 hover:text-slate-700 font-bold transition" 
                                        title="Refresh notifications"
                                    >
                                        ↻ Refresh
                                    </button>
                                    <span class="text-slate-200">|</span>
                                    <a href="{{ route('admin.dashboard', ['tab' => 'notifications']) }}" class="text-[11px] font-bold text-[#F20B65] hover:underline">
                                        All Logs &rarr;
                                    </a>
                                </div>
                            </div>

                            <!-- Filter Pills -->
                            <div class="flex items-center gap-1 overflow-x-auto pb-2 mb-2 border-b border-slate-100 no-scrollbar">
                                <button 
                                    type="button" 
                                    @click="activeCategoryFilter = 'all'"
                                    class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition whitespace-nowrap"
                                    :class="activeCategoryFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                >
                                    All (<span x-text="items.length"></span>)
                                </button>
                                <button 
                                    type="button" 
                                    @click="activeCategoryFilter = 'id_verification'"
                                    class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition whitespace-nowrap flex items-center gap-1"
                                    :class="activeCategoryFilter === 'id_verification' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'"
                                >
                                    <span>🆔 IDs</span>
                                    <span class="px-1 py-0.2 rounded-full text-[9px]" :class="activeCategoryFilter === 'id_verification' ? 'bg-white text-blue-700' : 'bg-blue-200 text-blue-900'" x-text="counts.id_verifications"></span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="activeCategoryFilter = 'listing_pending'"
                                    class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition whitespace-nowrap flex items-center gap-1"
                                    :class="activeCategoryFilter === 'listing_pending' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100'"
                                >
                                    <span>📝 Ads</span>
                                    <span class="px-1 py-0.2 rounded-full text-[9px]" :class="activeCategoryFilter === 'listing_pending' ? 'bg-white text-amber-700' : 'bg-amber-200 text-amber-900'" x-text="counts.pending_listings"></span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="activeCategoryFilter = 'contact_message'"
                                    class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition whitespace-nowrap flex items-center gap-1"
                                    :class="activeCategoryFilter === 'contact_message' ? 'bg-pink-600 text-white' : 'bg-pink-50 text-pink-700 hover:bg-pink-100'"
                                >
                                    <span>💬 Msgs</span>
                                    <span class="px-1 py-0.2 rounded-full text-[9px]" :class="activeCategoryFilter === 'contact_message' ? 'bg-white text-pink-700' : 'bg-pink-200 text-pink-900'" x-text="counts.messages"></span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="activeCategoryFilter = 'abuse_report'"
                                    class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition whitespace-nowrap flex items-center gap-1"
                                    :class="activeCategoryFilter === 'abuse_report' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100'"
                                >
                                    <span>🚨 Reports</span>
                                    <span class="px-1 py-0.2 rounded-full text-[9px]" :class="activeCategoryFilter === 'abuse_report' ? 'bg-white text-rose-700' : 'bg-rose-200 text-rose-900'" x-text="counts.reports"></span>
                                </button>
                            </div>

                            <!-- Notification Items List -->
                            <div class="space-y-1.5 max-h-80 overflow-y-auto pr-0.5">
                                <template x-for="item in filteredItems()" :key="item.id">
                                    <a 
                                        :href="item.url" 
                                        class="block p-2.5 rounded-xl hover:bg-slate-50 border border-slate-100 hover:border-slate-200 transition group"
                                    >
                                        <div class="flex items-center justify-between gap-2 mb-0.5">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <span class="text-xs" x-text="item.icon"></span>
                                                <span 
                                                    class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded border"
                                                    :class="item.badge_color"
                                                    x-text="item.badge_label"
                                                ></span>
                                                <span class="font-bold text-slate-900 text-[11px] truncate" x-text="item.title"></span>
                                            </div>
                                            <span class="text-[10px] text-slate-400 shrink-0 font-medium" x-text="item.time_ago"></span>
                                        </div>
                                        <div class="text-[11px] text-slate-600 truncate pl-4.5" x-text="item.snippet"></div>
                                    </a>
                                </template>

                                <div x-show="filteredItems().length === 0" class="py-8 text-center text-slate-400 font-semibold space-y-1">
                                    <div class="text-lg">✨</div>
                                    <div class="text-xs text-slate-500">No active alerts in this category</div>
                                    <div class="text-[10px] text-slate-400">All submissions are currently addressed</div>
                                </div>
                            </div>

                            <!-- Dropdown Footer Quick Links -->
                            <div class="mt-2.5 pt-2 border-t border-slate-100 grid grid-cols-3 gap-1.5 text-center text-[10px] font-bold">
                                <a href="{{ route('admin.verifications.index') }}" class="p-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition">
                                    🆔 ID Queue ↗
                                </a>
                                <a href="{{ route('admin.dashboard', ['tab' => 'listings']) }}" class="p-1.5 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 transition">
                                    📝 Pending Ads ↗
                                </a>
                                <a href="{{ route('admin.dashboard', ['tab' => 'moderation_messages']) }}" class="p-1.5 rounded-lg bg-pink-50 text-[#F20B65] hover:bg-pink-100 transition">
                                    💬 Messages ↗
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Link to Public Site -->
                    <a href="{{ url('/') }}" target="_blank" class="hidden sm:inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-xs font-bold text-slate-700 hover:text-slate-900 transition">
                        <span>🌐</span>
                        <span>Live Site ↗</span>
                    </a>

                    <!-- Admin Profile Avatar & Role -->
                    <div class="relative" @click.away="userMenuOpen = false">
                        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center space-x-2.5 p-1 rounded-xl hover:bg-slate-100 transition">
                            @if(auth()->user()?->avatar_url)
                                <img 
                                    src="{{ auth()->user()->avatar_url }}" 
                                    alt="{{ auth()->user()?->name ?? 'Admin' }}" 
                                    class="w-8 h-8 rounded-full object-cover border-2 border-[#F20B83]"
                                />
                            @else
                                <div class="w-8 h-8 rounded-full bg-rose-50 border-2 border-[#F20B83] text-[#F20B83] flex items-center justify-center font-black text-xs">
                                    {{ strtoupper(substr(auth()->user()?->name ?: 'A', 0, 1)) }}
                                </div>
                            @endif
                            <div class="text-left hidden sm:block">
                                <div class="text-xs font-bold text-slate-900 flex items-center gap-1">
                                    <span>{{ auth()->user()?->name ?? 'Super Admin' }}</span>
                                    <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </button>

                        <div x-show="userMenuOpen" x-cloak class="absolute right-0 mt-2 w-56 rounded-2xl bg-white border border-slate-200 shadow-2xl py-1.5 z-50 text-xs">
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 font-medium text-slate-700 hover:bg-pink-50 hover:text-[#F20B83]">Admin Console</a>
                            <a href="{{ route('admin.verifications.index') }}" class="block px-4 py-2 font-medium text-slate-700 hover:bg-pink-50 hover:text-[#F20B83]">ID Verifications Queue ↗</a>
                            <a href="{{ url('/') }}" target="_blank" class="block px-4 py-2 font-medium text-slate-700 hover:bg-pink-50 hover:text-[#F20B83]">View Public Site ↗</a>
                            <div class="border-t border-slate-100 my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-rose-600 hover:bg-rose-50 font-bold">Sign Out</button>
                            </form>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </header>

    <!-- Main Admin Workspace Container -->
    <main class="flex-grow max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-8 w-full py-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
