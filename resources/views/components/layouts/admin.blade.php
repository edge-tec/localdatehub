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
                <div class="flex items-center space-x-3 sm:w-64 shrink-0">
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
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                            style="width: min(400px, calc(100vw - 1.5rem)); max-width: 400px;"
                            class="fixed right-3 top-16 sm:top-auto sm:right-0 sm:absolute sm:mt-2.5 max-h-[480px] flex flex-col rounded-2xl bg-white border border-slate-200/90 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.18)] p-4 z-50 text-xs font-sans"
                        >
                            <!-- Dropdown Header -->
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 mb-3 border-b border-slate-100 shrink-0">
                                <div class="min-w-0">
                                    <div class="font-black text-slate-900 text-sm tracking-tight flex flex-wrap items-center gap-2">
                                        <span>Live Notifications</span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>LIVE</span>
                                        </span>
                                    </div>
                                    <div class="text-[10px] font-medium text-slate-400 mt-0.5">Real-time alerts &amp; pending customer requests</div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button 
                                        type="button" 
                                        @click="pollLatest()" 
                                        class="p-1.5 rounded-xl text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer flex items-center gap-1 text-[11px] font-bold" 
                                        title="Refresh notifications"
                                        aria-label="Refresh notifications"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="markAllSeen()" 
                                        class="px-2.5 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer text-[10px] font-bold flex items-center gap-1 border border-slate-200 whitespace-nowrap" 
                                        title="Mark all notifications as read"
                                    >
                                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        <span>Mark Read</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Tabs Grid: 100% Fully Visible, Never Cut Off, Overlapping, or Broken -->
                            <div class="grid grid-cols-3 sm:grid-cols-5 gap-1.5 pb-3 mb-3 border-b border-slate-100 w-full shrink-0">
                                <button 
                                    type="button" 
                                    @click="activeCategoryFilter = 'all'"
                                    class="w-full min-w-0 py-2 px-1 rounded-xl text-[10px] sm:text-[11px] font-black transition flex items-center justify-center gap-1 cursor-pointer shadow-2xs whitespace-nowrap overflow-hidden"
                                    :class="activeCategoryFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    title="All Notifications"
                                >
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                    </svg>
                                    <span>All</span>
                                    <span class="px-1.5 py-0.5 rounded-full text-[9px] min-w-[16px] text-center font-bold shrink-0" :class="activeCategoryFilter === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'" x-text="items.length"></span>
                                </button>

                                <button 
                                    type="button" 
                                    @click="activeCategoryFilter = 'id_verification'"
                                    class="w-full min-w-0 py-2 px-1 rounded-xl text-[10px] sm:text-[11px] font-black transition flex items-center justify-center gap-1 cursor-pointer shadow-2xs whitespace-nowrap overflow-hidden"
                                    :class="activeCategoryFilter === 'id_verification' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'"
                                    title="ID Verification Requests"
                                >
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                                        <circle cx="9" cy="10" r="2"></circle>
                                        <line x1="15" y1="8" x2="17" y2="8"></line>
                                        <line x1="15" y1="12" x2="17" y2="12"></line>
                                        <line x1="7" y1="16" x2="17" y2="16"></line>
                                    </svg>
                                    <span>IDs</span>
                                    <span class="px-1.5 py-0.5 rounded-full text-[9px] min-w-[16px] text-center font-bold shrink-0" :class="activeCategoryFilter === 'id_verification' ? 'bg-white text-blue-700' : 'bg-blue-200 text-blue-900'" x-text="counts.id_verifications"></span>
                                </button>

                                <button 
                                    type="button" 
                                    @click="activeCategoryFilter = 'listing_pending'"
                                    class="w-full min-w-0 py-2 px-1 rounded-xl text-[10px] sm:text-[11px] font-black transition flex items-center justify-center gap-1 cursor-pointer shadow-2xs whitespace-nowrap overflow-hidden"
                                    :class="activeCategoryFilter === 'listing_pending' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100'"
                                    title="Pending Classified Ads"
                                >
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <line x1="10" y1="9" x2="8" y2="9"></line>
                                    </svg>
                                    <span>Ads</span>
                                    <span class="px-1.5 py-0.5 rounded-full text-[9px] min-w-[16px] text-center font-bold shrink-0" :class="activeCategoryFilter === 'listing_pending' ? 'bg-white text-amber-700' : 'bg-amber-200 text-amber-900'" x-text="counts.pending_listings"></span>
                                </button>

                                <button 
                                    type="button" 
                                    @click="activeCategoryFilter = 'contact_message'"
                                    class="w-full min-w-0 py-2 px-1 rounded-xl text-[10px] sm:text-[11px] font-black transition flex items-center justify-center gap-1 cursor-pointer shadow-2xs whitespace-nowrap overflow-hidden"
                                    :class="activeCategoryFilter === 'contact_message' ? 'bg-pink-600 text-white' : 'bg-pink-50 text-pink-700 hover:bg-pink-100'"
                                    title="Messages & Inquiries"
                                >
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"></path>
                                    </svg>
                                    <span>Msgs</span>
                                    <span class="px-1.5 py-0.5 rounded-full text-[9px] min-w-[16px] text-center font-bold shrink-0" :class="activeCategoryFilter === 'contact_message' ? 'bg-white text-pink-700' : 'bg-pink-200 text-pink-900'" x-text="counts.messages"></span>
                                </button>

                                <button 
                                    type="button" 
                                    @click="activeCategoryFilter = 'abuse_report'"
                                    class="w-full min-w-0 py-2 px-1 rounded-xl text-[10px] sm:text-[11px] font-black transition flex items-center justify-center gap-1 cursor-pointer shadow-2xs whitespace-nowrap overflow-hidden"
                                    :class="activeCategoryFilter === 'abuse_report' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100'"
                                    title="Abuse & Content Reports"
                                >
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"></path>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <span class="hidden sm:inline">Reports</span>
                                    <span class="sm:hidden">Rep</span>
                                    <span class="px-1.5 py-0.5 rounded-full text-[9px] min-w-[16px] text-center font-bold shrink-0" :class="activeCategoryFilter === 'abuse_report' ? 'bg-white text-rose-700' : 'bg-rose-200 text-rose-900'" x-text="counts.reports"></span>
                                </button>
                            </div>

                            <!-- Notification Items List -->
                            <div class="space-y-2 flex-1 min-h-0 max-h-80 overflow-y-auto pr-1 no-scrollbar">
                                <template x-for="item in filteredItems()" :key="item.id">
                                    <a 
                                        :href="item.url" 
                                        class="flex items-start gap-3 p-3 rounded-2xl bg-white hover:bg-slate-50/80 border border-slate-100 hover:border-slate-200 shadow-2xs transition group cursor-pointer"
                                    >
                                        <!-- Category Tile with Crisp SVG -->
                                        <div 
                                            class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border transition group-hover:scale-105"
                                            :class="{
                                                'bg-blue-50 text-blue-600 border-blue-200/80': item.category === 'id_verification',
                                                'bg-amber-50 text-amber-600 border-amber-200/80': item.category === 'listing_pending',
                                                'bg-pink-50 text-[#F20B65] border-pink-200/80': item.category === 'contact_message',
                                                'bg-rose-50 text-rose-600 border-rose-200/80': item.category === 'abuse_report',
                                                'bg-indigo-50 text-indigo-600 border-indigo-200/80': item.category === 'system_log'
                                            }"
                                        >
                                            <template x-if="item.category === 'id_verification'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                                                    <circle cx="9" cy="10" r="2"></circle>
                                                    <line x1="15" y1="8" x2="17" y2="8"></line>
                                                    <line x1="15" y1="12" x2="17" y2="12"></line>
                                                    <line x1="7" y1="16" x2="17" y2="16"></line>
                                                </svg>
                                            </template>
                                            <template x-if="item.category === 'listing_pending'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                                                    <polyline points="14 2 14 8 20 8"></polyline>
                                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                                    <line x1="10" y1="9" x2="8" y2="9"></line>
                                                </svg>
                                            </template>
                                            <template x-if="item.category === 'contact_message'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"></path>
                                                </svg>
                                            </template>
                                            <template x-if="item.category === 'abuse_report'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"></path>
                                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                                </svg>
                                            </template>
                                            <template x-if="item.category === 'system_log'">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                                </svg>
                                            </template>
                                        </div>

                                        <!-- Content -->
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-1.5 mb-0.5">
                                                <div class="flex items-center gap-1.5 min-w-0">
                                                    <span 
                                                        class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md border"
                                                        :class="item.badge_color"
                                                        x-text="item.badge_label"
                                                    ></span>
                                                    <span class="font-bold text-slate-900 text-xs truncate" x-text="item.title"></span>
                                                </div>
                                                <span class="text-[10px] text-slate-400 font-semibold shrink-0" x-text="item.time_ago"></span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 line-clamp-1 leading-snug" x-text="item.snippet"></p>
                                        </div>

                                        <!-- Arrow Icon -->
                                        <div class="shrink-0 self-center text-slate-300 group-hover:text-slate-700 group-hover:translate-x-0.5 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </div>
                                    </a>
                                </template>

                                <!-- Beautiful Modern Empty State -->
                                <div x-show="filteredItems().length === 0" class="py-10 text-center space-y-3">
                                    <div class="w-13 h-13 rounded-3xl bg-gradient-to-tr from-emerald-50 to-teal-50 border border-emerald-200/70 text-emerald-600 flex items-center justify-center mx-auto shadow-2xs">
                                        <svg class="w-6 h-6 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-black text-slate-900">All Caught Up!</h4>
                                        <p class="text-[11px] text-slate-500 max-w-xs mx-auto mt-0.5">No pending verification requests, listings, or customer inquiries right now.</p>
                                    </div>
                                    <div class="pt-1">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-50 border border-slate-200 text-[10px] font-bold text-slate-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>Real-time monitoring active</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Dropdown Footer: Single Non-Duplicated Action to Full Audit Trail -->
                            <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs shrink-0">
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-medium">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Live Monitoring Active</span>
                                </div>
                                <a 
                                    href="{{ route('admin.dashboard', ['tab' => 'audit']) }}" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-bold transition shadow-2xs group"
                                    title="View Full System Audit Trail & Logs"
                                >
                                    <span>Audit Logs &amp; History</span>
                                    <svg class="w-3 h-3 text-slate-300 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                    </svg>
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
