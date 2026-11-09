<div class="flex flex-col lg:flex-row gap-6">
    {{-- ================= Sidebar Navigation (Matching Pure White Theme) ================= --}}
    <aside class="lg:w-64 shrink-0" aria-label="Administrative modules"
        x-data="{
            adminProfileOpen: false,
            expandedMenus: {
                listings: {{ in_array($tab, ['listings', 'moderation', 'featured']) ? 'true' : 'false' }},
                categories: {{ in_array($tab, ['categories', 'attributes']) ? 'true' : 'false' }},
                locations: {{ in_array($tab, ['locations']) ? 'true' : 'false' }},
                seo: {{ in_array($tab, ['seo', 'cms', 'ads']) ? 'true' : 'false' }}
            },
            toggleMenu(name) {
                this.expandedMenus[name] = !this.expandedMenus[name];
            }
        }">
        <nav class="bg-white border border-slate-200 rounded-2xl p-3.5 lg:sticky lg:top-24 space-y-1 shadow-sm">
            
            <!-- Admin Profile Card with Dropdown -->
            <div class="relative mb-3">
                <button type="button" @click="adminProfileOpen = !adminProfileOpen"
                    class="w-full flex items-center space-x-3 p-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 transition text-left cursor-pointer group focus:outline-none focus:ring-2 focus:ring-[#F20B65]/30">
                    <div class="relative shrink-0">
                        <img src="{{ asset('images/admin-avatar.jpg') }}" alt="{{ auth()->user()?->name ?? 'Admin' }}" class="w-10 h-10 rounded-full object-cover border-2 border-[#F20B65]" />
                        <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-slate-900 truncate flex items-center justify-between">
                            <span class="truncate">{{ auth()->user()?->name ?? 'Admin' }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-700 transition transform duration-150" :class="{ 'rotate-180': adminProfileOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                        <div class="text-[10px] text-emerald-600 font-semibold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>{{ auth()->user() ? strtoupper(str_replace('_', ' ', auth()->user()->role)) : 'Online' }}</span>
                        </div>
                    </div>
                </button>

                <!-- Profile Dropdown Menu -->
                <div x-show="adminProfileOpen" @click.away="adminProfileOpen = false" x-cloak
                    class="absolute left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl py-1.5 z-40 text-xs font-semibold">
                    <div class="px-3 py-2 border-b border-slate-100 bg-slate-50/50">
                        <div class="font-bold text-slate-900 truncate">{{ auth()->user()?->name ?? 'Administrator' }}</div>
                        <div class="text-[11px] text-slate-500 font-mono truncate">{{ auth()->user()?->email }}</div>
                    </div>
                    <a href="{{ route('account.profile') }}" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>Edit Profile</span>
                    </a>
                    <a href="{{ route('account.security') }}" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        <span>Change Password &amp; 2FA</span>
                    </a>
                    <a href="{{ route('admin.dashboard', ['tab' => 'settings']) }}" wire:click.prevent="setTab('settings')" @click="adminProfileOpen = false"
                        class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>System Settings</span>
                    </a>
                    <a href="{{ route('admin.dashboard', ['tab' => 'audit']) }}" wire:click.prevent="setTab('audit')" @click="adminProfileOpen = false"
                        class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Audit Logs</span>
                    </a>
                    <div class="border-t border-slate-100 my-1"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left flex items-center gap-2 px-3 py-2 text-rose-600 hover:bg-rose-50 font-bold transition">
                            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            <span>Sign Out</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sidebar Menu Items -->
            @php
                $pendingQueueCount = \App\Modules\Listings\Models\Listing::where('status', 'pending_review')->count();
                $pendingAppealsCount = \App\Modules\Moderation\Models\AppealRequest::whereIn('status', ['pending', 'under_review'])->count();
                $openReportsCount = \App\Modules\Moderation\Models\ContentReport::where('status', 'open')->count();
                $pendingVerificationBadge = \App\Modules\Accounts\Models\AgeAssurance::where('status', 'pending')->count();
                $failedNotificationsBadge = \App\Modules\Notifications\Models\NotificationLog::where('status', 'failed')->count();
                $totalModerationBadge = $pendingQueueCount + $pendingAppealsCount;

                $menuItems = [
                    [
                        'key' => 'dashboard',
                        'label' => 'Dashboard',
                        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                    ],
                    [
                        'key' => 'users',
                        'label' => 'Users',
                        'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                    ],
                    [
                        'key' => 'listings',
                        'label' => 'Listings',
                        'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16',
                        'has_submenu' => true,
                        'subitems' => [
                            ['key' => 'listings', 'label' => 'All Listings'],
                            ['key' => 'moderation', 'label' => 'Review Queue', 'badge' => $pendingQueueCount > 0 ? $pendingQueueCount : null],
                            ['key' => 'featured', 'label' => 'Featured Ads'],
                        ],
                    ],
                    [
                        'key' => 'categories',
                        'label' => 'Categories',
                        'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                        'has_submenu' => true,
                        'subitems' => [
                            ['key' => 'categories', 'label' => 'All Categories'],
                            ['key' => 'attributes', 'label' => 'Custom Fields'],
                        ],
                    ],
                    [
                        'key' => 'locations',
                        'label' => 'Locations',
                        'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z',
                        'has_submenu' => true,
                        'subitems' => [
                            ['key' => 'locations', 'label' => 'States & Cities'],
                            ['url' => route('admin.locations.index'), 'label' => 'City & ZIP Manager ↗'],
                        ],
                    ],
                    [
                        'key' => 'moderation',
                        'label' => 'Moderation',
                        'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                        'badge' => $totalModerationBadge > 0 ? $totalModerationBadge : null,
                    ],
                    [
                        'key' => 'reports',
                        'label' => 'Reports',
                        'icon' => 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9',
                        'badge' => $openReportsCount > 0 ? $openReportsCount : null,
                    ],
                    [
                        'key' => 'payments',
                        'label' => 'Payments',
                        'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
                    ],
                    [
                        'key' => 'subscriptions',
                        'label' => 'Subscriptions',
                        'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
                    ],
                    [
                        'key' => 'seo',
                        'label' => 'SEO & Marketing',
                        'icon' => 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z',
                        'has_submenu' => true,
                        'subitems' => [
                            ['key' => 'seo', 'label' => 'SEO Meta & Sitemaps'],
                            ['key' => 'cms', 'label' => 'CMS Legal Pages'],
                            ['key' => 'ads', 'label' => 'Advertising Slots'],
                        ],
                    ],
                    [
                        'key' => 'verification',
                        'label' => 'Age Verification',
                        'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                        'badge' => $pendingVerificationBadge > 0 ? $pendingVerificationBadge : null,
                    ],
                    [
                        'key' => 'notifications',
                        'label' => 'Notifications',
                        'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                        'badge' => $failedNotificationsBadge > 0 ? $failedNotificationsBadge : null,
                    ],
                    [
                        'key' => 'cms',
                        'label' => 'CMS Pages',
                        'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    ],
                    [
                        'key' => 'ads',
                        'label' => 'Advertising',
                        'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
                    ],
                    [
                        'key' => 'staff',
                        'label' => 'Staff Management',
                        'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                    ],
                    [
                        'key' => 'settings',
                        'label' => 'Settings',
                        'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
                    ],
                    [
                        'key' => 'audit',
                        'label' => 'Audit Logs',
                        'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    ],
                ];
            @endphp

            @foreach($menuItems as $item)
                @php
                    $key = $item['key'];
                    $label = $item['label'];
                    $path = $item['icon'];
                    $badgeCount = $item['badge'] ?? null;
                    $hasSubmenu = $item['has_submenu'] ?? false;
                    $subitems = $item['subitems'] ?? [];
                    $isActive = ($tab === $key);
                    $isSubActive = false;
                    if ($hasSubmenu) {
                        foreach ($subitems as $s) {
                            if (($s['key'] ?? '') === $tab) {
                                $isSubActive = true;
                                break;
                            }
                        }
                    }
                    $isGroupActive = $isActive || $isSubActive;
                @endphp

                <div class="space-y-0.5" wire:key="nav-group-{{ $key }}">
                    <div class="flex items-center gap-1">
                        <a href="{{ route('admin.dashboard', ['tab' => $key]) }}"
                            wire:click.prevent="setTab('{{ $key }}')"
                            class="flex-1 flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 cursor-pointer group {{ $isActive ? 'text-white shadow-md' : 'text-slate-700 hover:text-slate-950 hover:bg-slate-100' }}"
                            @if($isActive) style="background: linear-gradient(135deg, #F20B65 0%, #D90858 100%) !important; color: #ffffff !important;" @endif>
                            
                            <div class="flex items-center space-x-2.5 truncate">
                                <svg class="w-4 h-4 shrink-0 {{ $isActive ? 'text-white' : 'text-slate-500 group-hover:text-slate-800' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}" />
                                </svg>
                                <span class="truncate tracking-tight">{{ $label }}</span>
                            </div>

                            @if($badgeCount)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $isActive ? 'bg-white text-[#F20B65]' : 'bg-[#EF4444] text-white' }} shadow-xs">
                                    {{ $badgeCount }}
                                </span>
                            @endif
                        </a>

                        @if($hasSubmenu)
                            <button type="button" @click="toggleMenu('{{ $key }}')"
                                class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                title="Toggle {{ $label }} sub-options">
                                <svg class="w-3.5 h-3.5 transition transform duration-150" :class="{ 'rotate-180': expandedMenus['{{ $key }}'] }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                        @endif
                    </div>

                    @if($hasSubmenu)
                        <div x-show="expandedMenus['{{ $key }}']" x-cloak class="pl-8 pr-1 py-1 space-y-1">
                            @foreach($subitems as $sub)
                                @php
                                    $subKey = $sub['key'] ?? null;
                                    $subUrl = $sub['url'] ?? ($subKey ? route('admin.dashboard', ['tab' => $subKey]) : '#');
                                    $subBadge = $sub['badge'] ?? null;
                                    $subIsActive = ($subKey && $tab === $subKey);
                                @endphp
                                <a href="{{ $subUrl }}"
                                    @if($subKey) wire:click.prevent="setTab('{{ $subKey }}')" @endif
                                    class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-[11px] font-semibold transition {{ $subIsActive ? 'bg-[#F20B65]/10 text-[#F20B65] font-black' : 'text-slate-600 hover:text-slate-950 hover:bg-slate-50' }}">
                                    <span>{{ $sub['label'] }}</span>
                                    @if($subBadge)
                                        <span class="px-1.5 py-0.5 rounded-full text-[9px] font-black bg-[#EF4444] text-white">
                                            {{ $subBadge }}
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            <!-- View Website Button (Pink Outline) -->
            <div class="pt-4 mt-4 border-t border-slate-200">
                <a href="{{ route('home') }}" target="_blank" class="w-full flex items-center justify-center space-x-2 px-3 py-2.5 rounded-xl border border-[#F20B65] text-[#F20B65] hover:bg-[#F20B65] hover:text-white transition font-bold text-xs shadow-2xs">
                    <span>View Website</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                </a>
            </div>
        </nav>
    </aside>

    {{-- ================= Main Administrative Workspace ================= --}}
    <section class="flex-1 min-w-0">
        @if(session()->has('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800 font-medium" role="status">{{ session('success') }}</div>
        @endif
        @if(session()->has('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-800 font-medium" role="alert">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-800 font-medium flex items-center justify-between shadow-xs" role="alert">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
                <button type="button" wire:click="clearErrors" class="text-red-700 hover:text-red-950 font-black text-sm px-1.5" title="Dismiss error">&times;</button>
            </div>
        @endif

        @php
            $card = 'bg-white border border-slate-200 rounded-2xl shadow-xs text-slate-800';
            $th = 'px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-200';
            $td = 'px-3 py-2.5 text-xs text-slate-700 align-top border-b border-slate-100';
            $btn = 'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold transition';
            $input = 'w-full rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 px-3 py-2 focus:bg-white focus:border-[#F20B83] focus:ring-[#F20B83]';
            $badge = fn ($s) => match ($s) {
                'active', 'approved', 'completed', 'resolved', 'granted', 'published', true => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                'pending', 'pending_review', 'open', 'investigating', 'under_review', 'past_due' => 'bg-amber-50 text-amber-700 border border-amber-200',
                'suspended', 'rejected', 'failed', 'refunded', 'canceled', 'dismissed', false => 'bg-rose-50 text-rose-700 border border-rose-200',
                default => 'bg-slate-100 text-slate-700 border border-slate-200',
            };
        @endphp

        @if(!$tabAllowed)
            <div class="{{ $card }} p-8 text-center text-slate-600">Access denied for this module.</div>
        @else

        {{-- Impersonation Active Banner --}}
        @if(session()->has('impersonator_admin_id'))
            <div class="bg-amber-400 text-slate-950 px-4 py-3 rounded-2xl mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-md border-2 border-amber-500">
                <div class="flex items-center space-x-3">
                    <span class="p-2 bg-amber-500 rounded-xl text-lg">⚠️</span>
                    <div>
                        <div class="text-xs uppercase tracking-wider font-black text-amber-950">Active Direct Login Session (Impersonation)</div>
                        <div class="text-sm font-bold text-slate-950">
                            Logged in as <span class="underline decoration-2">{{ auth()->user()->name }}</span> ({{ strtoupper(str_replace('_', ' ', auth()->user()->role)) }}). Authorized by <strong>{{ session('impersonator_admin_name') }}</strong>.
                        </div>
                    </div>
                </div>
                <button wire:click="stopImpersonation" class="px-4 py-2 bg-slate-950 hover:bg-slate-800 text-white rounded-xl text-xs font-black shadow transition flex items-center space-x-1.5 whitespace-nowrap">
                    <span>Exit & Return to Admin Account</span>
                    <span>✕</span>
                </button>
            </div>
        @endif

        {{-- ================= 0. DASHBOARD (Matching Reference Screenshot) ================= --}}
        @if($tab === 'dashboard')
            <div wire:poll.10s class="space-y-6">
                <!-- Welcome Title & Live Traffic Badge -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Admin Dashboard</h1>
                        <p class="text-xs text-slate-500 mt-0.5">Welcome back, Super Admin! Real-time live platform telemetry and visitor tracking.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 shadow-xs w-max">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </span>
                            <span>{{ $metrics['activeVisitorsNow'] }} Visitors Online Now · Auto-updating (10s)</span>
                        </div>
                        <button type="button" wire:click="$refresh" class="p-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="Refresh Telemetry Now">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Row 1: 4 Stat Cards + Promotional Banner (100% Real Database Metrics & Clickable Modules) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Stat 1: Total Users -->
                    <div class="bg-white rounded-2xl p-4 text-[#171923] shadow-xs border border-slate-200 hover:border-[#F20B65] hover:shadow-md transition flex flex-col justify-between group">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('admin.dashboard', ['tab' => 'users']) }}" wire:click.prevent="setTab('users')" class="text-xs font-bold text-slate-500 group-hover:text-[#F20B65] transition flex items-center gap-1">
                                <span>Total Users <span class="sr-only">Total Accounts</span></span>
                                <span class="opacity-0 group-hover:opacity-100 transition text-[10px]">→</span>
                            </a>
                            <div class="w-8 h-8 rounded-full bg-[#F20B65]/10 text-[#F20B65] flex items-center justify-center">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="{{ route('admin.dashboard', ['tab' => 'users']) }}" wire:click.prevent="setTab('users')" class="text-2xl font-black text-[#171923] hover:text-[#F20B65] transition block">
                                {{ number_format($metrics['totalUsers']) }}
                            </a>
                            <div class="text-[11px] font-bold text-emerald-600 flex items-center gap-1 mt-0.5">
                                <span>↗ +{{ $metrics['userGrowth'] }}% this week</span>
                            </div>
                            <div class="text-[10px] text-slate-400 font-semibold mt-1 flex items-center gap-1.5">
                                <button type="button" wire:click="setUserStatusFilter('active')" class="hover:text-emerald-700 hover:underline cursor-pointer">
                                    {{ number_format($metrics['activeUsers']) }} active
                                </button>
                                <span>·</span>
                                <button type="button" wire:click="setUserStatusFilter('suspended')" class="hover:text-rose-600 hover:underline cursor-pointer">
                                    {{ number_format($metrics['suspendedUsers']) }} suspended
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stat 2: Total Listings -->
                    <div class="bg-white rounded-2xl p-4 text-[#171923] shadow-xs border border-slate-200 hover:border-purple-500 hover:shadow-md transition flex flex-col justify-between group">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('admin.dashboard', ['tab' => 'listings']) }}" wire:click.prevent="setTab('listings')" class="text-xs font-bold text-slate-500 group-hover:text-purple-600 transition flex items-center gap-1">
                                <span>Total Listings</span>
                                <span class="opacity-0 group-hover:opacity-100 transition text-[10px]">→</span>
                            </a>
                            <div class="w-8 h-8 rounded-full bg-purple-500/10 text-purple-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="{{ route('admin.dashboard', ['tab' => 'listings']) }}" wire:click.prevent="setTab('listings')" class="text-2xl font-black text-[#171923] hover:text-purple-600 transition block">
                                {{ number_format($metrics['totalListings']) }}
                            </a>
                            <div class="text-[11px] font-bold text-emerald-600 flex items-center gap-1 mt-0.5">
                                <span>↗ +{{ $metrics['listingGrowth'] }}% this week</span>
                            </div>
                            <div class="text-[10px] text-slate-400 font-semibold mt-1 flex items-center gap-1.5">
                                <button type="button" wire:click="setListingStatusFilter('active')" class="hover:text-emerald-700 hover:underline cursor-pointer">
                                    {{ number_format($metrics['publishedListings']) }} active
                                </button>
                                <span>·</span>
                                <button type="button" wire:click="setTab('moderation')" class="hover:text-amber-700 hover:underline cursor-pointer">
                                    {{ number_format($metrics['pendingListings']) }} pending
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stat 3: Total Views & Real-Time Traffic -->
                    <div class="bg-white rounded-2xl p-4 text-[#171923] shadow-xs border border-slate-200 hover:border-teal-500 hover:shadow-md transition flex flex-col justify-between group">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('admin.dashboard', ['tab' => 'analytics']) }}" wire:click.prevent="setTab('analytics')" class="text-xs font-bold text-slate-500 group-hover:text-teal-600 transition flex items-center gap-1">
                                <span>Total Views</span>
                                <span class="opacity-0 group-hover:opacity-100 transition text-[10px]">→</span>
                            </a>
                            <div class="w-8 h-8 rounded-full bg-teal-500/10 text-teal-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="{{ route('admin.dashboard', ['tab' => 'analytics']) }}" wire:click.prevent="setTab('analytics')" class="text-2xl font-black text-[#171923] hover:text-teal-600 transition block">
                                {{ $metrics['totalViews'] >= 1000000 ? number_format($metrics['totalViews'] / 1000000, 1) . 'M' : ($metrics['totalViews'] >= 1000 ? number_format($metrics['totalViews'] / 1000, 1) . 'k' : number_format($metrics['totalViews'])) }}
                            </a>
                            <div class="text-[11px] font-bold text-emerald-600 flex items-center gap-1 mt-0.5">
                                <span>↗ +{{ $metrics['viewsGrowth'] }}% traffic</span>
                            </div>
                            <a href="{{ route('admin.dashboard', ['tab' => 'analytics']) }}" wire:click.prevent="setTab('analytics')" class="text-[10px] text-teal-600 hover:underline font-semibold mt-1 block">
                                {{ number_format($metrics['todayViews']) }} page views today
                            </a>
                        </div>
                    </div>

                    <!-- Stat 4: Revenue (Fiat + Web3 Crypto) -->
                    <div class="bg-white rounded-2xl p-4 text-[#171923] shadow-xs border border-slate-200 hover:border-amber-500 hover:shadow-md transition flex flex-col justify-between group">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('admin.dashboard', ['tab' => 'payments']) }}" wire:click.prevent="setTab('payments')" class="text-xs font-bold text-slate-500 group-hover:text-amber-600 transition flex items-center gap-1">
                                <span>Revenue</span>
                                <span class="opacity-0 group-hover:opacity-100 transition text-[10px]">→</span>
                            </a>
                            <div class="w-8 h-8 rounded-full bg-amber-500/10 text-amber-600 flex items-center justify-center">
                                <span class="font-extrabold text-sm">$</span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="{{ route('admin.dashboard', ['tab' => 'payments']) }}" wire:click.prevent="setTab('payments')" class="text-2xl font-black text-[#171923] hover:text-amber-600 transition block">
                                ${{ number_format($metrics['totalRevenue'], 2) }}
                            </a>
                            <div class="text-[11px] font-bold text-emerald-600 flex items-center gap-1 mt-0.5">
                                <span>↗ +{{ $metrics['revenueGrowth'] }}% growth</span>
                            </div>
                            <div class="text-[10px] text-slate-400 font-semibold mt-1">
                                Fiat &amp; Web3 crypto settled
                            </div>
                        </div>
                    </div>

                    <!-- Stat 5: Promotional Banner Card -->
                    <div class="rounded-2xl overflow-hidden shadow-xs relative min-h-[110px] group border border-slate-200">
                        <img src="{{ asset('images/admin-banner.jpg') }}" alt="Promo Banner" class="w-full h-full object-cover absolute inset-0 group-hover:scale-105 transition duration-300" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent p-3.5 flex flex-col justify-end">
                            <div class="text-xs font-black text-white">LocalDate<span class="text-[#F20B65]">Hub</span></div>
                            <a href="{{ route('admin.dashboard', ['tab' => 'ads']) }}" wire:click.prevent="setTab('ads')"
                                class="mt-1 bg-[#F20B65] hover:bg-[#d90858] text-white text-[10px] font-bold px-2.5 py-1 rounded-md flex items-center gap-1 w-max shadow transition cursor-pointer">
                                <span>Manage Banner</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Website Analytics, Listing Status Donut, Quick Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    
                    <!-- Website Analytics Chart (100% Real-Time Dynamic Weekly Telemetry) -->
                    <div class="lg:col-span-6 {{ $card }} p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-sm font-extrabold text-slate-900">Website Analytics</h2>
                                <p class="text-[11px] text-slate-500">Weekly traffic and inquiry metrics</p>
                            </div>
                            <div class="flex items-center space-x-3 text-[11px] font-semibold text-slate-600">
                                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#F20B65]"></span>Views ({{ number_format($chart['totalWeeklyViews']) }})</span>
                                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#8B35F6]"></span>Contacts ({{ number_format($chart['totalWeeklyContacts']) }})</span>
                                <a href="{{ route('admin.dashboard', ['tab' => 'analytics']) }}" wire:click.prevent="setTab('analytics')" class="text-[11px] font-bold text-[#F20B65] hover:underline flex items-center gap-0.5 ml-2">
                                    <span>Full Report</span>
                                    <span>→</span>
                                </a>
                            </div>
                        </div>

                        <!-- Active Tooltip Badge -->
                        <div class="inline-flex items-center space-x-2 px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200 text-xs font-bold text-slate-900 mb-2">
                            <span>{{ number_format($chart['totalWeeklyViews']) }} Weekly Page Views</span>
                            <span class="text-emerald-600 font-extrabold text-[10px]">↗ +{{ $metrics['viewsGrowth'] }}% trend</span>
                        </div>

                        <!-- Responsive Dynamic SVG Area Chart Generated from Database Telemetry -->
                        <div class="h-44 w-full relative">
                            <svg viewBox="0 0 500 160" class="w-full h-full overflow-visible">
                                <defs>
                                    <linearGradient id="viewsGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#F20B65" stop-opacity="0.3" />
                                        <stop offset="100%" stop-color="#F20B65" stop-opacity="0.0" />
                                    </linearGradient>
                                    <linearGradient id="contactsGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#8B35F6" stop-opacity="0.2" />
                                        <stop offset="100%" stop-color="#8B35F6" stop-opacity="0.0" />
                                    </linearGradient>
                                </defs>
                                <!-- Grid Lines -->
                                <line x1="0" y1="30" x2="500" y2="30" stroke="#E2E8F0" stroke-dasharray="3,3" />
                                <line x1="0" y1="80" x2="500" y2="80" stroke="#E2E8F0" stroke-dasharray="3,3" />
                                <line x1="0" y1="130" x2="500" y2="130" stroke="#E2E8F0" />

                                <!-- Area Path Views -->
                                <path d="{{ $chart['viewsAreaD'] }}" fill="url(#viewsGrad)" />
                                <path d="{{ $chart['viewsLineD'] }}" fill="none" stroke="#F20B65" stroke-width="3" />

                                <!-- Area Path Contacts -->
                                <path d="{{ $chart['contactsAreaD'] }}" fill="url(#contactsGrad)" />
                                <path d="{{ $chart['contactsLineD'] }}" fill="none" stroke="#8B35F6" stroke-width="2.5" stroke-dasharray="4,2" />

                                <!-- Peak highlight dot -->
                                <circle cx="{{ $chart['peakX'] }}" cy="{{ $chart['peakY'] }}" r="4.5" fill="#F20B65" stroke="#FFFFFF" stroke-width="2" />
                            </svg>
                        </div>
                        <div class="flex justify-between text-[10px] font-bold text-slate-500 mt-1 px-1">
                            @foreach($chart['days'] as $day)
                                <span>{{ $day }}</span>
                            @endforeach
                        </div>
                    </div>

                    <!-- Listing Status Donut Chart (Dynamic Database Calculation & Interactive Filters) -->
                    <div class="lg:col-span-3 {{ $card }} p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h2 class="text-sm font-extrabold text-slate-900">Listing Status</h2>
                                <a href="{{ route('admin.dashboard', ['tab' => 'listings']) }}" wire:click.prevent="setTab('listings')" class="text-[10px] font-bold text-[#F20B65] hover:underline">
                                    All Ads →
                                </a>
                            </div>
                            <div class="relative w-36 h-36 mx-auto my-3 flex items-center justify-center">
                                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                    <!-- Background Circle -->
                                    <path class="text-slate-100" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <!-- Published (Teal/Emerald) -->
                                    <path class="text-emerald-500 transition-all duration-500" stroke-width="4.5" stroke-dasharray="{{ $statusCounts['pubPct'] }}, 100" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <!-- Pending (Amber) -->
                                    <path class="text-amber-500 transition-all duration-500" stroke-width="4.5" stroke-dasharray="{{ $statusCounts['penPct'] }}, 100" stroke-dashoffset="-{{ $statusCounts['pubPct'] }}" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <!-- Rejected (Pink) -->
                                    <path class="text-[#F20B65] transition-all duration-500" stroke-width="4.5" stroke-dasharray="{{ $statusCounts['rejPct'] }}, 100" stroke-dashoffset="-{{ ($statusCounts['pubPct'] + $statusCounts['penPct']) }}" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                </svg>
                                <a href="{{ route('admin.dashboard', ['tab' => 'listings']) }}" wire:click.prevent="setListingStatusFilter('all')" class="absolute text-center group cursor-pointer" title="View All Listings">
                                    <div class="text-xs font-black text-slate-900 group-hover:text-[#F20B65] transition">{{ number_format($statusCounts['total']) }}</div>
                                    <div class="text-[9px] font-bold text-slate-400 group-hover:text-[#F20B65] transition uppercase">Total</div>
                                </a>
                            </div>
                        </div>

                        <!-- Legend with Interactive Filter Clicking -->
                        <div class="space-y-1 text-[11px] font-medium pt-2 border-t border-slate-200">
                            <a href="{{ route('admin.dashboard', ['tab' => 'listings']) }}" wire:click.prevent="setListingStatusFilter('active')"
                                class="flex justify-between items-center p-1.5 rounded-lg hover:bg-slate-50 transition cursor-pointer group">
                                <span class="flex items-center gap-1.5 text-slate-700 group-hover:text-emerald-700 font-semibold"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Published</span>
                                <span class="font-bold text-slate-900 group-hover:text-emerald-700">{{ $statusCounts['pubPct'] }}% ({{ number_format($statusCounts['published']) }})</span>
                            </a>
                            <a href="{{ route('admin.dashboard', ['tab' => 'moderation']) }}" wire:click.prevent="setTab('moderation')"
                                class="flex justify-between items-center p-1.5 rounded-lg hover:bg-slate-50 transition cursor-pointer group">
                                <span class="flex items-center gap-1.5 text-slate-700 group-hover:text-amber-700 font-semibold"><span class="w-2 h-2 rounded-full bg-amber-500"></span>Pending</span>
                                <span class="font-bold text-slate-900 group-hover:text-amber-700">{{ $statusCounts['penPct'] }}% ({{ number_format($statusCounts['pending']) }})</span>
                            </a>
                            <a href="{{ route('admin.dashboard', ['tab' => 'listings']) }}" wire:click.prevent="setListingStatusFilter('rejected')"
                                class="flex justify-between items-center p-1.5 rounded-lg hover:bg-slate-50 transition cursor-pointer group">
                                <span class="flex items-center gap-1.5 text-slate-700 group-hover:text-rose-700 font-semibold"><span class="w-2 h-2 rounded-full bg-[#F20B65]"></span>Rejected</span>
                                <span class="font-bold text-slate-900 group-hover:text-rose-700">{{ $statusCounts['rejPct'] }}% ({{ number_format($statusCounts['rejected']) }})</span>
                            </a>
                        </div>
                    </div>

                    <!-- Quick Actions Panel (100% Functional Routing & Working Accordion) -->
                    <div class="lg:col-span-3 {{ $card }} p-5 flex flex-col justify-between" x-data="{ quickOpen: true }">
                        <div>
                            <div class="flex items-center justify-between mb-3 cursor-pointer" @click="quickOpen = !quickOpen">
                                <h2 class="text-sm font-extrabold text-slate-900">Quick Actions</h2>
                                <button type="button" class="text-slate-400 hover:text-slate-700 transition" :class="{ 'rotate-180': !quickOpen }">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                            </div>
                            <div class="space-y-2" x-show="quickOpen" x-collapse>
                                <a href="{{ route('listings.create') }}" target="_blank" class="w-full bg-pink-50 border border-pink-200 hover:bg-[#F20B65] text-[#F20B65] hover:text-white p-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition shadow-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>Add New Listing</span>
                                </a>
                                <a href="{{ route('admin.dashboard', ['tab' => 'users']) }}" wire:click.prevent="setTab('users')"
                                    class="w-full bg-slate-50 hover:bg-slate-100 text-slate-700 hover:text-slate-900 p-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition border border-slate-200 cursor-pointer">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z"/></svg>
                                    <span>Manage Users</span>
                                </a>
                                <a href="{{ route('admin.dashboard', ['tab' => 'reports']) }}" wire:click.prevent="setTab('reports')"
                                    class="w-full bg-slate-50 hover:bg-slate-100 text-slate-700 hover:text-slate-900 p-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition border border-slate-200 cursor-pointer">
                                    <svg class="w-4 h-4 text-[#F20B65]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span>View Reports</span>
                                </a>
                                <a href="{{ route('admin.dashboard', ['tab' => 'payments']) }}" wire:click.prevent="setTab('payments')"
                                    class="w-full bg-slate-50 hover:bg-slate-100 text-slate-700 hover:text-slate-900 p-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition border border-slate-200 cursor-pointer">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    <span>Manage Payments</span>
                                </a>
                                <a href="{{ route('admin.dashboard', ['tab' => 'seo']) }}" wire:click.prevent="setTab('seo')"
                                    class="w-full bg-slate-50 hover:bg-slate-100 text-slate-700 hover:text-slate-900 p-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition border border-slate-200 cursor-pointer">
                                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>SEO Settings</span>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Row 3: Recent Listings (4 Cards) + Real-Time Location & Country Telemetry -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    
                    <!-- Recent Listings (8 cols) -->
                    <div class="lg:col-span-8 {{ $card }} p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-sm font-extrabold text-slate-900">Recent Listings</h2>
                                <p class="text-[11px] text-slate-500">Latest active classified advertisements</p>
                            </div>
                            <a href="{{ route('admin.dashboard', ['tab' => 'listings']) }}" wire:click.prevent="setTab('listings')"
                                class="text-xs font-bold text-[#F20B65] hover:underline flex items-center gap-1 cursor-pointer">
                                <span>View All Listings</span>
                                <span>→</span>
                            </a>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            @forelse($recentListings as $rListing)
                                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden flex flex-col justify-between group shadow-xs hover:border-[#F20B65] hover:shadow-md transition">
                                    <div class="relative h-28 bg-slate-100 overflow-hidden">
                                        @if($rListing->primary_photo_url)
                                            <a href="{{ route('listings.show', $rListing->slug) }}" target="_blank" class="block w-full h-full">
                                                <img src="{{ $rListing->primary_photo_url }}" alt="{{ $rListing->title }}" class="w-full h-full object-cover group-hover:scale-105 transition" />
                                            </a>
                                        @else
                                            <a href="{{ route('listings.show', $rListing->slug) }}" target="_blank" class="w-full h-full flex flex-col items-center justify-center bg-slate-100 text-slate-400">
                                                <svg class="w-8 h-8 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <span class="text-[9px] font-bold text-slate-400 mt-1">LocalDateHub</span>
                                            </a>
                                        @endif
                                        <button type="button" wire:click="setListingCategoryFilter({{ $rListing->category_id ?? 0 }})"
                                            class="absolute top-1.5 left-1.5 bg-[#F20B65] hover:bg-[#d90858] text-white text-[9px] font-extrabold px-2 py-0.5 rounded-full shadow-xs cursor-pointer transition"
                                            title="Filter listings by {{ $rListing->category?->name }}">
                                            {{ $rListing->category?->name ?? 'Featured' }}
                                        </button>
                                    </div>
                                    <div class="p-2.5 flex-1 flex flex-col justify-between">
                                        <div>
                                            <a href="{{ route('listings.show', $rListing->slug) }}" target="_blank" class="text-[11px] font-bold text-slate-900 hover:text-[#F20B65] transition truncate block" title="{{ $rListing->title }}">
                                                {{ $rListing->title }}
                                            </a>
                                            <div class="text-[10px] text-slate-500 mt-0.5 truncate">{{ $rListing->city?->name ?? 'City' }}, {{ $rListing->city?->state?->code ?? 'US' }}</div>
                                        </div>
                                        <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px]">
                                            <span class="text-slate-500 flex items-center gap-0.5 font-medium">👁 {{ number_format($rListing->view_count ?? 0) }}</span>
                                            <div class="flex items-center gap-1">
                                                <button type="button" wire:click="openViewListingModal({{ $rListing->id }})" class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-[9px] font-bold px-1.5 py-0.5 rounded transition" title="Moderate/Inspect ad">
                                                    Manage
                                                </button>
                                                <a href="{{ route('listings.show', $rListing->slug) }}" target="_blank" class="bg-[#F20B65] hover:bg-[#d90858] text-white text-[9px] font-bold px-2 py-0.5 rounded flex items-center gap-0.5 shadow-2xs">
                                                    <span>View Ad</span>
                                                    <span>→</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-4 p-8 text-center text-xs text-slate-400 font-semibold bg-slate-50 rounded-xl border border-dashed border-slate-200">
                                    No classified listings posted yet. Click "+ Add New Listing" above to publish the first ad.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Top Locations & Real-Time Country Telemetry (4 cols) -->
                    <div class="lg:col-span-4 {{ $card }} p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h2 class="text-sm font-extrabold text-slate-900">Traffic &amp; Locations</h2>
                                    <p class="text-[10px] text-slate-500">Real-time visitor origins</p>
                                </div>
                                <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg border border-slate-200">
                                    <button type="button" wire:click="setDashboardLocationMode('countries')" class="px-2.5 py-1 rounded-md text-[10px] font-bold transition {{ $dashboardLocationMode === 'countries' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900' }}">
                                        🌍 Countries
                                    </button>
                                    <button type="button" wire:click="setDashboardLocationMode('states')" class="px-2.5 py-1 rounded-md text-[10px] font-bold transition {{ $dashboardLocationMode === 'states' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900' }}">
                                        📍 States
                                    </button>
                                </div>
                            </div>

                            @if($dashboardLocationMode === 'countries')
                                <!-- Real-Time Country Tracking -->
                                <div class="space-y-2">
                                    @forelse($topCountries as $idx => $c)
                                        <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-200 text-xs hover:bg-slate-100 transition">
                                            <div class="flex items-center space-x-2.5 truncate">
                                                <span class="w-5 h-5 rounded-full bg-pink-100 text-[#F20B65] text-[10px] font-extrabold flex items-center justify-center shrink-0">
                                                    {{ $idx + 1 }}
                                                </span>
                                                <span class="text-base shrink-0">{{ \App\Models\PlatformTrafficLog::countryCodeToFlag($c->country_code) }}</span>
                                                <span class="font-bold text-slate-900 truncate">{{ $c->country_name }}</span>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span class="text-slate-900 font-extrabold text-[11px]">{{ number_format($c->hits) }} hits</span>
                                                <span class="block text-[9px] text-slate-500 font-medium">{{ number_format($c->unique_visitors) }} unique</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center py-6 text-xs text-slate-400">Recording visitor country traffic...</div>
                                    @endforelse
                                </div>
                            @else
                                <!-- State Listings Distribution -->
                                <div class="space-y-2">
                                    @forelse($topLocations as $idx => $st)
                                        <a href="{{ route('admin.dashboard', ['tab' => 'locations']) }}" wire:click.prevent="setTab('locations')"
                                            class="flex items-center justify-between p-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs transition cursor-pointer group">
                                            <div class="flex items-center space-x-2.5 truncate">
                                                <span class="w-5 h-5 rounded-full bg-pink-100 text-[#F20B65] text-[10px] font-extrabold flex items-center justify-center shrink-0">
                                                    {{ $idx + 1 }}
                                                </span>
                                                <span class="text-[#F20B65] shrink-0">
                                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                                </span>
                                                <span class="font-bold text-slate-900 group-hover:text-[#F20B65] transition truncate">{{ $st->name }}</span>
                                            </div>
                                            <span class="text-slate-700 font-black text-[11px] shrink-0">{{ number_format($st->listings_count) }} {{ \Illuminate\Support\Str::plural('ad', $st->listings_count) }}</span>
                                        </a>
                                    @empty
                                        <div class="text-center py-6 text-xs text-slate-400">No state location data available.</div>
                                    @endforelse
                                </div>
                            @endif
                        </div>

                        <div class="pt-3 mt-2 border-t border-slate-100 flex justify-end">
                            <a href="{{ route('admin.dashboard', ['tab' => 'locations']) }}" wire:click.prevent="setTab('locations')"
                                class="text-xs font-bold text-[#F20B65] hover:underline flex items-center gap-1 cursor-pointer">
                                <span>Manage All Locations</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>

                </div>

                <!-- Row 4: Real-Time Live Visitor Stream, System Health & Audit Activities -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    
                    <!-- Live Real-Time Visitor Stream (4 cols) -->
                    <div class="lg:col-span-4 {{ $card }} p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h2 class="text-sm font-extrabold text-slate-900 flex items-center gap-1.5">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                        </span>
                                        <span>Live Visitor Stream</span>
                                    </h2>
                                    <p class="text-[10px] text-slate-500">Incoming requests real-time telemetry</p>
                                </div>
                                <span class="text-[10px] font-black text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md">LIVE</span>
                            </div>

                            <div class="space-y-2 text-xs">
                                @forelse($liveVisitors as $lv)
                                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-100 text-slate-700 hover:bg-slate-100 transition">
                                        <div class="flex items-center gap-2 truncate">
                                            <span class="text-sm">{{ \App\Models\PlatformTrafficLog::countryCodeToFlag($lv->country_code) }}</span>
                                            <span class="font-bold text-slate-900 truncate max-w-[130px]" title="{{ $lv->path }}">{{ $lv->path }}</span>
                                            <span class="text-[9px] text-slate-400 font-semibold">{{ $lv->device_type }} · {{ $lv->browser }}</span>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-[9px] text-slate-500 font-bold block">{{ $lv->created_at->diffForHumans(short: true) }}</span>
                                            @if($lv->response_time_ms)
                                                <span class="text-[8px] font-mono text-emerald-600 font-black">⚡{{ $lv->response_time_ms }}ms</span>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-6 text-xs text-slate-400">Listening for live incoming requests...</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="pt-3 mt-2 border-t border-slate-100 flex justify-end">
                            <a href="{{ route('admin.dashboard', ['tab' => 'analytics']) }}" wire:click.prevent="setTab('analytics')" class="text-xs font-bold text-[#F20B65] hover:underline flex items-center gap-1 cursor-pointer">
                                <span>Full Traffic Logs</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>

                    <!-- System Health with 4 Radial Progress Gauges (4 cols) -->
                    <div class="lg:col-span-4 {{ $card }} p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-sm font-extrabold text-slate-900">System Health</h2>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">Optimal</span>
                            </div>
                            <div class="grid grid-cols-4 gap-2 text-center mb-4">
                                <!-- Server Gauge -->
                                <div>
                                    <div class="relative w-12 h-12 mx-auto">
                                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                            <path class="text-slate-100" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                            <path class="text-emerald-500" stroke-width="3" stroke-dasharray="98, 100" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        </svg>
                                        <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-slate-900">98%</span>
                                    </div>
                                    <div class="text-[10px] font-bold text-slate-500 mt-1">Server</div>
                                </div>

                                <!-- Database Gauge -->
                                <div>
                                    <div class="relative w-12 h-12 mx-auto">
                                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                            <path class="text-slate-100" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                            <path class="text-emerald-500" stroke-width="3" stroke-dasharray="100, 100" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        </svg>
                                        <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-slate-900">100%</span>
                                    </div>
                                    <div class="text-[10px] font-bold text-slate-500 mt-1">Database</div>
                                </div>

                                <!-- Queue Gauge -->
                                <div>
                                    <div class="relative w-12 h-12 mx-auto">
                                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                            <path class="text-slate-100" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                            <path class="text-emerald-500" stroke-width="3" stroke-dasharray="99, 100" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        </svg>
                                        <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-slate-900">99%</span>
                                    </div>
                                    <div class="text-[10px] font-bold text-slate-500 mt-1">Queue</div>
                                </div>

                                <!-- Cache Gauge -->
                                <div>
                                    <div class="relative w-12 h-12 mx-auto">
                                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                            <path class="text-slate-100" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                            <path class="text-emerald-500" stroke-width="3" stroke-dasharray="97, 100" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        </svg>
                                        <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-slate-900">97%</span>
                                    </div>
                                    <div class="text-[10px] font-bold text-slate-500 mt-1">Cache</div>
                                </div>
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-center flex items-center justify-center gap-1.5 text-xs font-bold text-emerald-700">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>All Systems Operational (PHP {{ $health['php'] }} · Laravel {{ $health['laravel'] }})</span>
                        </div>
                    </div>

                    <!-- Recent Real Audit Activities (4 cols) -->
                    <div class="lg:col-span-4 {{ $card }} p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="text-sm font-extrabold text-slate-900">Recent Audit Trail</h2>
                                <a href="{{ route('admin.dashboard', ['tab' => 'audit']) }}" wire:click.prevent="setTab('audit')"
                                    class="text-xs font-bold text-[#F20B65] hover:underline flex items-center gap-1 cursor-pointer">
                                    <span>View Logs</span>
                                    <span>→</span>
                                </a>
                            </div>

                            <div class="space-y-2 text-xs">
                                @forelse($recentAudit as $log)
                                    <div wire:click="inspectAuditLog({{ $log->id }})"
                                        class="flex items-center justify-between p-2 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-100 text-slate-700 cursor-pointer transition group">
                                        <div class="flex items-center gap-2 truncate">
                                            <span class="w-2 h-2 rounded-full bg-[#F20B65] shrink-0"></span>
                                            <div class="truncate">
                                                <span class="font-bold text-slate-900 group-hover:text-[#F20B65] transition">{{ $log->admin?->name ?? 'System' }}</span>
                                                <span class="text-[11px] text-slate-600 block truncate">{{ $log->action }}</span>
                                            </div>
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-bold shrink-0">{{ $log->created_at->diffForHumans(short: true) }}</span>
                                    </div>
                                @empty
                                    <div class="text-center py-6 text-xs text-slate-400">No recent administrative audit events.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="pt-3 mt-2 border-t border-slate-100 flex justify-end">
                            <a href="{{ route('admin.dashboard', ['tab' => 'audit']) }}" wire:click.prevent="setTab('audit')" class="text-xs font-bold text-[#F20B65] hover:underline flex items-center gap-1 cursor-pointer">
                                <span>Security Audit Logs</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        @endif

        {{-- ================= 17. ANALYTICS ================= --}}
        @if($tab === 'analytics')
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                @foreach([
                    ['Views (30d)', number_format($analytics['views30'])],
                    ['Contact Clicks (30d)', number_format($analytics['contacts30'])],
                    ['Favorites (30d)', number_format($analytics['favorites30'])],
                    ['New Users (30d)', number_format($analytics['newUsers30'])],
                    ['New Listings (30d)', number_format($analytics['newListings30'])],
                    ['Revenue (30d)', '$' . number_format($analytics['revenue30'], 2)],
                    ['Refunds (30d)', '$' . number_format($analytics['refunds30'], 2)],
                ] as [$label, $value])
                    <div class="{{ $card }} p-4">
                        <div class="text-[11px] uppercase tracking-wider text-slate-400">{{ $label }}</div>
                        <div class="mt-1 text-xl font-black text-slate-900">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                <div class="{{ $card }} p-5 md:col-span-2 overflow-x-auto">
                    <h2 class="font-bold text-slate-900 mb-3">Top Listings by Views</h2>
                    <table class="min-w-full"><thead><tr><th class="{{ $th }}">Title</th><th class="{{ $th }}">Views</th><th class="{{ $th }}">Replies</th><th class="{{ $th }}">Favs</th></tr></thead>
                        <tbody>@forelse($topListings as $l)<tr class="border-t border-slate-200"><td class="{{ $td }}">{{ $l->title }}</td><td class="{{ $td }}">{{ number_format($l->view_count) }}</td><td class="{{ $td }}">{{ $l->reply_count }}</td><td class="{{ $td }}">{{ $l->favorite_count }}</td></tr>@empty<tr><td class="{{ $td }}" colspan="4">No listings yet.</td></tr>@endforelse</tbody></table>
                </div>
                <div class="{{ $card }} p-5">
                    <h2 class="font-bold text-slate-900 mb-3">Listings by Status</h2>
                    @forelse($listingsByStatus as $status => $total)
                        <div class="flex justify-between text-sm py-1"><span>{{ str_replace('_', ' ', $status) }}</span><span class="font-semibold">{{ $total }}</span></div>
                    @empty <p class="text-sm text-slate-400">No data.</p> @endforelse
                    <h2 class="font-bold text-slate-900 mt-5 mb-3">Top Categories</h2>
                    @foreach($topCategories as $c)
                        <div class="flex justify-between text-sm py-1"><span>{{ $c->name }}</span><span class="font-semibold">{{ $c->listings_count }}</span></div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ================= 1. USERS ================= --}}
        @if($tab === 'users')
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                <div>
                    <h2 class="text-xl font-black text-slate-950">User Management</h2>
                    <p class="text-xs font-semibold text-slate-600">Inspect, create, edit, audit, and regulate system user accounts.</p>
                </div>
                <button
                    type="button"
                    wire:click="openCreateUserModal"
                    class="{{ $btn }} bg-[#F20B83] hover:bg-[#d00970] text-white shadow-md font-black flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    <span>Create User</span>
                </button>
            </div>

            <div class="{{ $card }} p-4 mb-4 grid md:grid-cols-3 gap-3">
                <input type="search" wire:model.live.debounce.400ms="userSearch" placeholder="Search name or email…" class="{{ $input }}" aria-label="Search users">
                <select wire:model.live="userStatusFilter" class="{{ $input }}" aria-label="Status filter">
                    <option value="all">All statuses</option><option value="active">Active</option><option value="suspended">Suspended</option><option value="pending_verification">Pending verification</option><option value="closed">Closed</option>
                </select>
                <select wire:model.live="userRoleFilter" class="{{ $input }}" aria-label="Role filter">
                    <option value="all">All roles</option>
                    @foreach($assignableRoles as $slug => $name)<option value="{{ $slug }}">{{ $name }}</option>@endforeach
                </select>
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="{{ $th }}">User</th>
                            <th class="{{ $th }}">Role</th>
                            <th class="{{ $th }}">Status</th>
                            <th class="{{ $th }}">18+</th>
                            <th class="{{ $th }}">Wallet</th>
                            <th class="{{ $th }}">Joined</th>
                            <th class="{{ $th }}">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($users as $u)
                        <tr class="border-t border-slate-200 hover:bg-slate-50/60 transition" wire:key="user-{{ $u->id }}">
                            <td class="{{ $td }}">
                                <div class="font-black text-slate-950">{{ $u->name }}</div>
                                <div class="text-xs font-semibold text-slate-600 font-mono">{{ $u->email }}</div>
                            </td>
                            <td class="{{ $td }}">
                                <span class="capitalize text-xs font-bold text-slate-800">{{ str_replace('_', ' ', $u->role) }}</span>
                            </td>
                            <td class="{{ $td }}">
                                <span class="px-2 py-0.5 rounded border text-xs font-black {{ $badge($u->account_status) }}">
                                    {{ $u->account_status }}
                                </span>
                            </td>
                            <td class="{{ $td }}">
                                @if($u->is_age_verified)
                                    <span class="text-emerald-700 font-black text-xs inline-flex items-center gap-0.5">✔ 18+</span>
                                @else
                                    <span class="text-slate-400 font-semibold text-xs">—</span>
                                @endif
                            </td>
                            <td class="{{ $td }}">
                                <span class="text-xs font-mono font-bold text-slate-800">
                                    {{ $u->wallet?->balance ?? 0 }} <span class="text-[10px] text-slate-500">cr</span>
                                </span>
                            </td>
                            <td class="{{ $td }} text-xs font-semibold text-slate-600 whitespace-nowrap">
                                {{ $u->created_at?->format('M j, Y') }}
                            </td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                <!-- View Details -->
                                <button
                                    wire:click="openViewUserModal({{ $u->id }})"
                                    class="{{ $btn }} bg-sky-50 hover:bg-sky-100 text-sky-800 border border-sky-200 font-bold"
                                    title="View Full Profile Details"
                                >
                                    View
                                </button>

                                <!-- Edit Details -->
                                <button
                                    wire:click="openEditUserModal({{ $u->id }})"
                                    class="{{ $btn }} bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200 font-bold"
                                    title="Edit User Details & Credits"
                                >
                                    Edit
                                </button>

                                <!-- Direct Login As User (No Password Needed) -->
                                @if($u->id !== auth()->id() && ($u->role !== 'super_admin' || $this->isSuperAdmin()))
                                    <button
                                        wire:click="directLoginAsUser({{ $u->id }})"
                                        wire:confirm="Log in directly as {{ $u->name }} ({{ $u->email }}) without password? You can return to admin at any time."
                                        class="{{ $btn }} bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 font-extrabold"
                                        title="Log in directly to this user's account without credentials"
                                    >
                                        <svg class="w-3 h-3 text-emerald-600 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                        Login
                                    </button>
                                @endif

                                <!-- Suspend / Reinstate -->
                                @if($this->userCan('users.suspend') && $u->id !== auth()->id())
                                    @if($u->account_status === 'suspended')
                                        <button wire:click="unsuspendUser({{ $u->id }})" wire:confirm="Reinstate this account?" class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white font-bold">Reinstate</button>
                                    @else
                                        <button wire:click="openSuspendUserModal({{ $u->id }})" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white font-bold">Suspend</button>
                                    @endif
                                @endif

                                <!-- Role -->
                                @if($this->userCan('staff.manage') && $u->id !== auth()->id())
                                    <button wire:click="openAssignRoleModal({{ $u->id }})" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900 font-bold">Role</button>
                                @endif

                                <!-- Delete -->
                                @if($u->id !== auth()->id() && $u->id !== 1 && $u->role !== 'super_admin')
                                    <button
                                        wire:click="openDeleteUserModal({{ $u->id }})"
                                        class="{{ $btn }} bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 font-bold"
                                        title="Delete User"
                                    >
                                        Delete
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="{{ $td }} text-center text-slate-400">No users match the filters.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $users->links() }}</div>
            </div>
        @endif

        {{-- ================= 2. LISTINGS ================= --}}
        @if($tab === 'listings')
            <div class="{{ $card }} p-4 mb-4 grid md:grid-cols-3 gap-3">
                <input type="search" wire:model.live.debounce.400ms="listingSearch" placeholder="Search title…" class="{{ $input }}" aria-label="Search listings">
                <select wire:model.live="listingStatusFilter" class="{{ $input }}" aria-label="Listing status">
                    <option value="all">All statuses</option>
                    @foreach(['draft','pending_review','active','paused','expired','rejected','suspended','archived'] as $s)<option value="{{ $s }}">{{ str_replace('_', ' ', $s) }}</option>@endforeach
                </select>
                <select wire:model.live="listingCategoryFilter" class="{{ $input }}" aria-label="Category">
                    <option value="">All categories</option>
                    @foreach($categoryOptions as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="flex gap-2 mb-3">
                @if($this->userCan('listings.approve'))<button wire:click="bulkApproveListings" class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white">Approve selected ({{ count($selectedListingIds) }})</button>@endif
                @if($this->userCan('listings.suspend'))<button wire:click="bulkSuspendListings" wire:confirm="Suspend all selected listings?" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Suspend selected</button>@endif
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}"><span class="sr-only">Select</span></th><th class="{{ $th }}">Listing</th><th class="{{ $th }}">Category / City</th><th class="{{ $th }}">Owner</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($listings as $l)
                        <tr class="border-t border-slate-100 hover:bg-slate-50/60 transition" wire:key="listing-{{ $l->id }}">
                            <td class="{{ $td }}"><input type="checkbox" value="{{ $l->id }}" wire:model.live="selectedListingIds" class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]" aria-label="Select listing {{ $l->id }}"></td>
                            <td class="{{ $td }}">
                                <a href="{{ route('listings.show', $l->slug) }}" target="_blank" class="font-bold text-slate-950 hover:text-[#F20B83]">
                                    {{ $l->title }}
                                </a>
                                <div class="text-xs text-slate-500">#{{ $l->id }} · {{ $l->created_at?->format('M j, Y') }}</div>
                            </td>
                            <td class="{{ $td }}">
                                <span class="font-semibold text-slate-900">{{ $l->category?->name }}</span>
                                <div class="text-xs text-slate-500">{{ $l->city?->name }}</div>
                            </td>
                            <td class="{{ $td }}">
                                @if($l->user_id)
                                    <button
                                        type="button"
                                        wire:click="openViewUserModal({{ $l->user_id }})"
                                        class="text-left group focus:outline-none block"
                                        title="View full user details for {{ $l->user?->name }}"
                                    >
                                        <div class="font-black text-slate-950 group-hover:text-[#F20B83] transition flex items-center gap-1.5">
                                            <span>{{ $l->user?->name ?? 'Unknown' }}</span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#F20B83] transition shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </div>
                                        <div class="text-[11px] font-mono text-slate-500 group-hover:text-slate-800 truncate max-w-[160px]">
                                            {{ $l->user?->email }}
                                        </div>
                                        <div class="text-[10px] font-bold text-sky-700 group-hover:underline">
                                            View Owner Profile &rarr;
                                        </div>
                                    </button>
                                @else
                                    <span class="text-slate-400 italic text-xs">Unassigned</span>
                                @endif
                            </td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs font-black {{ $badge($l->status) }}">{{ str_replace('_', ' ', $l->status) }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                <!-- View Details -->
                                <button
                                    type="button"
                                    wire:click="openViewListingModal({{ $l->id }})"
                                    class="{{ $btn }} bg-sky-50 hover:bg-sky-100 text-sky-900 border border-sky-300 font-bold"
                                    title="View Full Listing Details"
                                >
                                    View
                                </button>

                                <!-- Edit Details -->
                                <button
                                    type="button"
                                    wire:click="openEditListingModal({{ $l->id }})"
                                    class="{{ $btn }} bg-indigo-50 hover:bg-indigo-100 text-indigo-900 border border-indigo-300 font-bold"
                                    title="Edit Listing Details"
                                >
                                    Edit
                                </button>

                                @if($this->userCan('listings.approve') && $l->status !== 'active')<button wire:click="approveListing({{ $l->id }})" class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white font-bold">Approve</button>@endif
                                @if($this->userCan('listings.reject') && $l->status !== 'rejected')<button wire:click="openRejectListingModal({{ $l->id }})" class="{{ $btn }} bg-amber-700 hover:bg-amber-600 text-white font-bold">Reject</button>@endif
                                @if($this->userCan('listings.suspend') && $l->status !== 'suspended')<button wire:click="suspendListing({{ $l->id }})" wire:confirm="Suspend this listing?" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white font-bold">Suspend</button>@endif
                                @if($this->userCan('listings.delete'))
                                    <button
                                        type="button"
                                        wire:click="openDeleteListingModal({{ $l->id }})"
                                        class="{{ $btn }} bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-300 font-bold"
                                        title="Delete Listing"
                                    >
                                        Delete
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No listings found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $listings->links() }}</div>
            </div>
        @endif

        {{-- ================= 6. MODERATION ================= --}}
        @if($tab === 'moderation')
            <!-- Trust & Safety Executive Header -->
            <div class="mb-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-slate-950 flex items-center gap-2">
                        <span>Trust &amp; Safety Moderation Center</span>
                    </h2>
                    <p class="text-xs font-semibold text-slate-600 mt-0.5">
                        Inspect pending ads, audit suspension appeals, enforce community standards, and handle safety reports.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('admin.moderation.messages.queue') }}"
                        class="px-3 py-1.5 text-xs font-bold text-[#F20B83] bg-pink-50 hover:bg-pink-100 border border-pink-200 rounded-xl transition inline-flex items-center gap-1.5 shadow-xs"
                    >
                        <span>Message Safety Queue</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Live Trust & Safety KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
                <!-- Pending Queue -->
                <button
                    type="button"
                    wire:click="setModerationSubTab('queue')"
                    class="p-4 rounded-2xl border text-left transition {{ $moderationSubTab === 'queue' ? 'bg-amber-50/70 border-amber-300 ring-2 ring-amber-400/40 shadow-sm' : 'bg-white border-slate-200 hover:border-slate-300' }}"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase tracking-wider text-amber-800">Pending Review</span>
                        <span class="w-2 h-2 rounded-full {{ $moderationStats['pending_queue_count'] > 0 ? 'bg-amber-500 animate-pulse' : 'bg-slate-300' }}"></span>
                    </div>
                    <div class="text-2xl font-black text-slate-950 mt-1.5">
                        {{ number_format($moderationStats['pending_queue_count']) }}
                    </div>
                    <div class="text-[11px] font-semibold text-slate-500 mt-0.5">
                        Awaiting editorial approval
                    </div>
                </button>

                <!-- Appeals -->
                <button
                    type="button"
                    wire:click="setModerationSubTab('appeals')"
                    class="p-4 rounded-2xl border text-left transition {{ $moderationSubTab === 'appeals' ? 'bg-indigo-50/70 border-indigo-300 ring-2 ring-indigo-400/40 shadow-sm' : 'bg-white border-slate-200 hover:border-slate-300' }}"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase tracking-wider text-indigo-800">Suspension Appeals</span>
                        <span class="w-2 h-2 rounded-full {{ $moderationStats['appeals_pending_count'] > 0 ? 'bg-indigo-500 animate-pulse' : 'bg-slate-300' }}"></span>
                    </div>
                    <div class="text-2xl font-black text-slate-950 mt-1.5">
                        {{ number_format($moderationStats['appeals_pending_count']) }}
                    </div>
                    <div class="text-[11px] font-semibold text-slate-500 mt-0.5">
                        User reinstatement requests
                    </div>
                </button>

                <!-- Reports -->
                <button
                    type="button"
                    wire:click="setModerationSubTab('reports')"
                    class="p-4 rounded-2xl border text-left transition {{ $moderationSubTab === 'reports' ? 'bg-rose-50/70 border-rose-300 ring-2 ring-rose-400/40 shadow-sm' : 'bg-white border-slate-200 hover:border-slate-300' }}"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase tracking-wider text-rose-800">Safety Reports</span>
                        <span class="w-2 h-2 rounded-full {{ $moderationStats['open_reports_count'] > 0 ? 'bg-rose-500 animate-pulse' : 'bg-slate-300' }}"></span>
                    </div>
                    <div class="text-2xl font-black text-slate-950 mt-1.5">
                        {{ number_format($moderationStats['open_reports_count']) }}
                    </div>
                    <div class="text-[11px] font-semibold text-slate-500 mt-0.5">
                        Active community flags
                    </div>
                </button>

                <!-- Quarantined / Suspended -->
                <button
                    type="button"
                    wire:click="setModerationSubTab('automated')"
                    class="p-4 rounded-2xl border text-left transition {{ $moderationSubTab === 'automated' ? 'bg-purple-50/70 border-purple-300 ring-2 ring-purple-400/40 shadow-sm' : 'bg-white border-slate-200 hover:border-slate-300' }}"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black uppercase tracking-wider text-purple-800">Quarantined Content</span>
                        <span class="w-2 h-2 rounded-full {{ $moderationStats['suspended_listings_count'] > 0 ? 'bg-purple-500' : 'bg-slate-300' }}"></span>
                    </div>
                    <div class="text-2xl font-black text-slate-950 mt-1.5">
                        {{ number_format($moderationStats['suspended_listings_count']) }}
                    </div>
                    <div class="text-[11px] font-semibold text-slate-500 mt-0.5">
                        Restricted / suspended items
                    </div>
                </button>
            </div>

            <!-- Segmented Sub-Tab Switcher -->
            <div class="flex flex-wrap gap-2 mb-4 border-b border-slate-200 pb-3">
                <button
                    type="button"
                    wire:click="setModerationSubTab('queue')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 {{ $moderationSubTab === 'queue' ? 'bg-[#F20B83] text-white shadow-sm' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200' }}"
                >
                    <span>Pending Review Queue</span>
                    @if($moderationStats['pending_queue_count'] > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $moderationSubTab === 'queue' ? 'bg-white/25 text-white' : 'bg-amber-100 text-amber-900' }}">
                            {{ $moderationStats['pending_queue_count'] }}
                        </span>
                    @endif
                </button>

                <button
                    type="button"
                    wire:click="setModerationSubTab('appeals')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 {{ $moderationSubTab === 'appeals' ? 'bg-[#F20B83] text-white shadow-sm' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200' }}"
                >
                    <span>Suspension Appeals</span>
                    @if($moderationStats['appeals_pending_count'] > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $moderationSubTab === 'appeals' ? 'bg-white/25 text-white' : 'bg-indigo-100 text-indigo-900' }}">
                            {{ $moderationStats['appeals_pending_count'] }}
                        </span>
                    @endif
                </button>

                <button
                    type="button"
                    wire:click="setModerationSubTab('reports')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 {{ $moderationSubTab === 'reports' ? 'bg-[#F20B83] text-white shadow-sm' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200' }}"
                >
                    <span>Safety Reports & Abuse</span>
                    @if($moderationStats['open_reports_count'] > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $moderationSubTab === 'reports' ? 'bg-white/25 text-white' : 'bg-rose-100 text-rose-900' }}">
                            {{ $moderationStats['open_reports_count'] }}
                        </span>
                    @endif
                </button>

                <button
                    type="button"
                    wire:click="setModerationSubTab('automated')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 {{ $moderationSubTab === 'automated' ? 'bg-[#F20B83] text-white shadow-sm' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200' }}"
                >
                    <span>Automated AI & Rule Flags</span>
                </button>

                <button
                    type="button"
                    wire:click="setModerationSubTab('history')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 {{ $moderationSubTab === 'history' ? 'bg-[#F20B83] text-white shadow-sm' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200' }}"
                >
                    <span>Moderation Audit Trail</span>
                </button>
            </div>

            <!-- Unified Moderation Search & Filter Toolbar -->
            <div class="{{ $card }} p-3.5 mb-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <input
                        type="search"
                        wire:model.live.debounce.400ms="moderationSearch"
                        placeholder="Search title, keywords, user name, or email…"
                        class="{{ $input }}"
                        aria-label="Search moderation queue"
                    >
                </div>
                <div>
                    <select wire:model.live="moderationCategoryFilter" class="{{ $input }}" aria-label="Category filter">
                        <option value="">All Categories</option>
                        @foreach($categoryOptions as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    @if($moderationSubTab === 'reports')
                        <select wire:model.live="moderationRiskFilter" class="{{ $input }}" aria-label="Risk filter">
                            <option value="all">All Risk Levels</option>
                            <option value="critical">Critical Urgency</option>
                            <option value="high">High Risk</option>
                            <option value="medium">Medium Risk</option>
                            <option value="low">Low Risk</option>
                        </select>
                    @endif
                    @if($moderationSearch || $moderationCategoryFilter || $moderationRiskFilter !== 'all')
                        <button
                            type="button"
                            wire:click="resetModerationFilters"
                            class="px-3 py-2 text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition whitespace-nowrap"
                        >
                            Reset
                        </button>
                    @endif
                </div>
            </div>

            {{-- ================= SUB-TAB 1: PENDING REVIEW QUEUE ================= --}}
            @if($moderationSubTab === 'queue')
                @if(count($selectedQueueListingIds) > 0)
                    <div class="p-3 mb-3 bg-pink-50 border border-pink-200 rounded-2xl flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs font-black text-pink-900">
                            {{ count($selectedQueueListingIds) }} listing(s) selected
                        </span>
                        <div class="flex items-center gap-2">
                            @if($this->userCan('listings.approve'))
                                <button
                                    type="button"
                                    wire:click="bulkApproveQueue"
                                    class="{{ $btn }} bg-emerald-600 hover:bg-emerald-500 text-white font-bold"
                                >
                                    Approve Selected ({{ count($selectedQueueListingIds) }})
                                </button>
                            @endif
                            @if($this->userCan('listings.reject'))
                                <button
                                    type="button"
                                    wire:click="bulkRejectQueue"
                                    wire:confirm="Bulk reject all selected listings from moderation queue?"
                                    class="{{ $btn }} bg-amber-600 hover:bg-amber-500 text-white font-bold"
                                >
                                    Reject Selected
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="{{ $card }} overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="{{ $th }} w-10">
                                    <span class="sr-only">Select</span>
                                </th>
                                <th class="{{ $th }}">Listing Content</th>
                                <th class="{{ $th }}">Listing Owner</th>
                                <th class="{{ $th }}">Automated Screening</th>
                                <th class="{{ $th }}">Submitted</th>
                                <th class="{{ $th }}">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($pendingQueue as $l)
                            <tr class="border-t border-slate-100 hover:bg-slate-50/60 transition" wire:key="queue-{{ $l->id }}">
                                <td class="{{ $td }}">
                                    <input
                                        type="checkbox"
                                        value="{{ $l->id }}"
                                        wire:model.live="selectedQueueListingIds"
                                        class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]"
                                        aria-label="Select listing {{ $l->id }}"
                                    >
                                </td>
                                <td class="{{ $td }}">
                                    <div class="flex items-start gap-3">
                                        @if($l->media && $l->media->isNotEmpty())
                                            <div class="w-12 h-12 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 shrink-0">
                                                <img src="{{ $l->media->first()->url }}" alt="Preview" class="w-full h-full object-cover">
                                            </div>
                                        @else
                                            <div class="w-12 h-12 rounded-xl border border-slate-200 bg-slate-100 shrink-0 flex items-center justify-center text-slate-400 font-bold text-xs">
                                                No Img
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <a
                                                href="{{ route('listings.show', $l->slug) }}"
                                                target="_blank"
                                                class="font-black text-slate-950 hover:text-[#F20B83] transition line-clamp-1 block text-sm"
                                            >
                                                {{ $l->title }}
                                            </a>
                                            <div class="flex items-center gap-2 text-xs text-slate-500 font-semibold mt-0.5">
                                                <span class="text-slate-800">{{ $l->category?->name ?? 'Uncategorized' }}</span>
                                                <span>·</span>
                                                <span>{{ $l->city?->name ?? 'City' }}, {{ $l->state?->code ?? 'US' }}</span>
                                            </div>
                                            <p class="text-xs text-slate-600 line-clamp-1 mt-1 font-medium">
                                                {{ \Illuminate\Support\Str::limit($l->description, 120) }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="{{ $td }}">
                                    @if($l->user_id)
                                        <button
                                            type="button"
                                            wire:click="openViewUserModal({{ $l->user_id }})"
                                            class="text-left group focus:outline-none block"
                                            title="View owner details"
                                        >
                                            <div class="font-bold text-slate-950 group-hover:text-[#F20B83] transition flex items-center gap-1">
                                                <span>{{ $l->user?->name ?? 'Unknown' }}</span>
                                                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#F20B83] transition shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </div>
                                            <div class="text-[11px] font-mono text-slate-500 group-hover:text-slate-800 truncate max-w-[150px]">
                                                {{ $l->user?->email }}
                                            </div>
                                            <div class="text-[10px] font-bold text-sky-700 group-hover:underline">
                                                View Profile &rarr;
                                            </div>
                                        </button>
                                    @else
                                        <span class="text-slate-400 italic text-xs">Unassigned</span>
                                    @endif
                                </td>
                                <td class="{{ $td }}">
                                    <div class="space-y-1">
                                        @if($l->user?->is_age_verified)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                ✔ 18+ Age Verified
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                Pending 18+
                                            </span>
                                        @endif
                                        @if($l->rejection_count > 0)
                                            <span class="block text-[10px] text-amber-700 font-bold">
                                                Prior rejections: {{ $l->rejection_count }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="{{ $td }} whitespace-nowrap text-xs text-slate-600 font-semibold">
                                    <div>{{ $l->created_at?->diffForHumans() }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $l->created_at?->format('M j, Y H:i') }}</div>
                                </td>
                                <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                    <!-- Inspect Modal Button -->
                                    <button
                                        type="button"
                                        wire:click="openViewListingModal({{ $l->id }})"
                                        class="{{ $btn }} bg-sky-50 hover:bg-sky-100 text-sky-900 border border-sky-300 font-bold"
                                        title="Inspect Full Listing and Media"
                                    >
                                        Inspect
                                    </button>

                                    <!-- Quick Approve -->
                                    @if($this->userCan('listings.approve'))
                                        <button
                                            type="button"
                                            wire:click="approveListing({{ $l->id }})"
                                            class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white font-bold"
                                        >
                                            Approve
                                        </button>
                                    @endif

                                    <!-- Reject with Reason Modal -->
                                    @if($this->userCan('listings.reject'))
                                        <button
                                            type="button"
                                            wire:click="openRejectListingModal({{ $l->id }})"
                                            class="{{ $btn }} bg-amber-700 hover:bg-amber-600 text-white font-bold"
                                        >
                                            Reject
                                        </button>
                                    @endif

                                    <!-- Quarantine Suspension -->
                                    @if($this->userCan('listings.suspend'))
                                        <button
                                            type="button"
                                            wire:click="suspendListing({{ $l->id }})"
                                            wire:confirm="Quarantine and suspend this listing immediately?"
                                            class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white font-bold"
                                        >
                                            Quarantine
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center bg-slate-50/50">
                                    <div class="max-w-md mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto text-xl font-black">
                                            ✔
                                        </div>
                                        <h3 class="font-black text-slate-900 text-sm">Review Queue Is Clean</h3>
                                        <p class="text-xs text-slate-500 font-medium">
                                            All classified listings submitted by advertisers have been processed. New listings awaiting moderation will appear here automatically.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div class="p-3">{{ $pendingQueue->links() }}</div>
                </div>
            @endif

            {{-- ================= SUB-TAB 2: SUSPENSION APPEALS ================= --}}
            @if($moderationSubTab === 'appeals')
                <div class="{{ $card }} overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="{{ $th }}">Appellant User</th>
                                <th class="{{ $th }}">Original Suspension</th>
                                <th class="{{ $th }}">Appeal Statement</th>
                                <th class="{{ $th }}">Status</th>
                                <th class="{{ $th }}">Submitted</th>
                                <th class="{{ $th }}">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($appeals as $a)
                            <tr class="border-t border-slate-100 hover:bg-slate-50/60 transition" wire:key="appeal-{{ $a->id }}">
                                <td class="{{ $td }}">
                                    @if($a->user_id)
                                        <button
                                            type="button"
                                            wire:click="openViewUserModal({{ $a->user_id }})"
                                            class="text-left group focus:outline-none block"
                                        >
                                            <div class="font-black text-slate-950 group-hover:text-[#F20B83] transition">
                                                {{ $a->user?->name ?? 'Unknown' }}
                                            </div>
                                            <div class="text-[11px] font-mono text-slate-500">
                                                {{ $a->user?->email }}
                                            </div>
                                        </button>
                                    @else
                                        <span class="text-slate-400 italic">User #{{ $a->user_id }}</span>
                                    @endif
                                </td>
                                <td class="{{ $td }}">
                                    <div class="font-semibold text-slate-900 text-xs">
                                        {{ $a->suspension?->reason ?? 'Policy enforcement suspension' }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 capitalize">
                                        {{ $a->suspension?->suspension_type ?? 'Permanent' }} · {{ $a->suspension?->created_at?->format('M j, Y') }}
                                    </div>
                                </td>
                                <td class="{{ $td }}">
                                    <div class="text-xs text-slate-800 line-clamp-2 font-medium">
                                        {{ $a->appeal_reason }}
                                    </div>
                                </td>
                                <td class="{{ $td }}">
                                    <span class="px-2 py-0.5 rounded border text-xs font-black uppercase {{ $badge($a->status) }}">
                                        {{ $a->status }}
                                    </span>
                                </td>
                                <td class="{{ $td }} text-xs text-slate-600 whitespace-nowrap">
                                    {{ $a->created_at?->diffForHumans() }}
                                </td>
                                <td class="{{ $td }} whitespace-nowrap">
                                    @if($this->userCan('moderation.handle_appeals'))
                                        <button
                                            type="button"
                                            wire:click="openAppealModal({{ $a->id }})"
                                            class="{{ $btn }} bg-indigo-50 hover:bg-indigo-100 text-indigo-900 border border-indigo-300 font-bold"
                                        >
                                            Review Appeal
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center bg-slate-50/50">
                                    <div class="max-w-md mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center mx-auto text-xl font-black">
                                            ⚖
                                        </div>
                                        <h3 class="font-black text-slate-900 text-sm">No Active Appeals</h3>
                                        <p class="text-xs text-slate-500 font-medium">
                                            There are no pending account suspension appeals from users at this time.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div class="p-3">{{ $appeals->links() }}</div>
                </div>
            @endif

            {{-- ================= SUB-TAB 3: SAFETY REPORTS & ABUSE ================= --}}
            @if($moderationSubTab === 'reports')
                <div class="{{ $card }} overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="{{ $th }}">Risk & Reason</th>
                                <th class="{{ $th }}">Reported Target</th>
                                <th class="{{ $th }}">Reporter Details</th>
                                <th class="{{ $th }}">Status</th>
                                <th class="{{ $th }}">Submitted</th>
                                <th class="{{ $th }}">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($moderationReports as $r)
                            <tr class="border-t border-slate-100 hover:bg-slate-50/60 transition" wire:key="report-{{ $r->id }}">
                                <td class="{{ $td }}">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider border {{ $r->risk_level === 'critical' ? 'bg-rose-50 text-rose-700 border-rose-200' : ($r->risk_level === 'high' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-200') }}">
                                            {{ $r->risk_level ?? 'medium' }}
                                        </span>
                                        @if($r->is_urgent_takedown)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-rose-600 text-white">
                                                URGENT TAKEDOWN
                                            </span>
                                        @endif
                                    </div>
                                    <div class="font-bold text-slate-900">
                                        {{ ucwords(str_replace('_', ' ', $r->reason_code)) }}
                                    </div>
                                    <p class="text-xs text-slate-600 line-clamp-2 mt-0.5 font-medium">
                                        {{ $r->details }}
                                    </p>
                                </td>
                                <td class="{{ $td }}">
                                    @if($r->reportable_type === 'App\Modules\Listings\Models\Listing' && $r->reportable)
                                        <button
                                            type="button"
                                            wire:click="openViewListingModal({{ $r->reportable_id }})"
                                            class="text-left font-bold text-slate-900 hover:text-[#F20B83] transition line-clamp-1"
                                        >
                                            {{ $r->reportable->title }}
                                        </button>
                                        <div class="text-[10px] text-slate-500 font-mono">Listing #{{ $r->reportable_id }}</div>
                                    @elseif($r->reportable_type === 'App\Models\User' && $r->reportable)
                                        <button
                                            type="button"
                                            wire:click="openViewUserModal({{ $r->reportable_id }})"
                                            class="text-left font-bold text-slate-900 hover:text-[#F20B83] transition"
                                        >
                                            {{ $r->reportable->name }}
                                        </button>
                                        <div class="text-[10px] text-slate-500 font-mono">User #{{ $r->reportable_id }}</div>
                                    @else
                                        <span class="text-slate-500 text-xs font-mono">{{ class_basename($r->reportable_type) }} #{{ $r->reportable_id }}</span>
                                    @endif
                                </td>
                                <td class="{{ $td }}">
                                    <div class="font-semibold text-slate-800 text-xs">
                                        {{ $r->reporter?->name ?? 'Anonymous Community Member' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono">
                                        IP: {{ $r->reporter_ip ?: 'Protected' }}
                                    </div>
                                </td>
                                <td class="{{ $td }}">
                                    <span class="px-2 py-0.5 rounded border text-xs font-black uppercase {{ $badge($r->status) }}">
                                        {{ $r->status }}
                                    </span>
                                </td>
                                <td class="{{ $td }} whitespace-nowrap text-xs text-slate-600">
                                    {{ $r->created_at?->diffForHumans() }}
                                </td>
                                <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                    <button
                                        type="button"
                                        wire:click="openResolveReportModal({{ $r->id }})"
                                        class="{{ $btn }} bg-sky-50 hover:bg-sky-100 text-sky-900 border border-sky-300 font-bold"
                                    >
                                        Action
                                    </button>
                                    @if($r->reportable_type === 'App\Modules\Listings\Models\Listing')
                                        <button
                                            type="button"
                                            wire:click="quarantineReportedListing({{ $r->id }})"
                                            wire:confirm="Quarantine and immediately remove this reported listing from public view?"
                                            class="{{ $btn }} bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-300 font-bold"
                                        >
                                            Quarantine
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center bg-slate-50/50">
                                    <div class="max-w-md mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center mx-auto text-xl font-black">
                                            🛡
                                        </div>
                                        <h3 class="font-black text-slate-900 text-sm">No Safety Reports</h3>
                                        <p class="text-xs text-slate-500 font-medium">
                                            There are no unresolved user reports or takedown requests matching the current filters.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div class="p-3">{{ $moderationReports->links() }}</div>
                </div>
            @endif

            {{-- ================= SUB-TAB 4: AUTOMATED AI & RULE FLAGS ================= --}}
            @if($moderationSubTab === 'automated')
                <div class="{{ $card }} overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="{{ $th }}">Trigger Reason / Rule</th>
                                <th class="{{ $th }}">Target Item</th>
                                <th class="{{ $th }}">Automated Flags</th>
                                <th class="{{ $th }}">Status</th>
                                <th class="{{ $th }}">Flagged At</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($flaggedQueue as $fq)
                            <tr class="border-t border-slate-100 hover:bg-slate-50/60 transition" wire:key="flagged-{{ $fq->id }}">
                                <td class="{{ $td }}">
                                    <div class="font-bold text-slate-900 text-xs">{{ $fq->trigger_reason ?: 'Automated Policy Match' }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">Queue #{{ $fq->id }}</div>
                                </td>
                                <td class="{{ $td }}">
                                    <span class="font-mono text-xs text-slate-700">{{ class_basename($fq->moderatable_type) }} #{{ $fq->moderatable_id }}</span>
                                </td>
                                <td class="{{ $td }}">
                                    @if(!empty($fq->automated_flags))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach((array) $fq->automated_flags as $flagKey => $flagVal)
                                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-800 text-[10px] font-bold font-mono">
                                                    {{ is_string($flagKey) ? $flagKey . ':' : '' }} {{ is_array($flagVal) ? json_encode($flagVal) : $flagVal }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic text-xs">Standard screening trigger</span>
                                    @endif
                                </td>
                                <td class="{{ $td }}">
                                    <span class="px-2 py-0.5 rounded border text-xs font-black uppercase {{ $badge($fq->status) }}">
                                        {{ $fq->status }}
                                    </span>
                                </td>
                                <td class="{{ $td }} text-xs text-slate-600 whitespace-nowrap">
                                    {{ $fq->created_at?->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center bg-slate-50/50">
                                    <div class="max-w-md mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center mx-auto text-xl font-black">
                                            ⚡
                                        </div>
                                        <h3 class="font-black text-slate-900 text-sm">No Automated Rule Flags</h3>
                                        <p class="text-xs text-slate-500 font-medium">
                                            Automated text screening, image validation, and keyword rule checks have detected no violations.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div class="p-3">{{ $flaggedQueue->links() }}</div>
                </div>
            @endif

            {{-- ================= SUB-TAB 5: MODERATION AUDIT TRAIL ================= --}}
            @if($moderationSubTab === 'history')
                <div class="{{ $card }} overflow-x-auto">
                    <div class="p-4 border-b border-slate-100">
                        <h3 class="font-black text-slate-950 text-sm">Recent Moderation Actions & Policy Enforcement</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Immutable record of disciplinary actions, approvals, and takedowns performed by administrators.</p>
                    </div>
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="{{ $th }}">Administrator</th>
                                <th class="{{ $th }}">Action Taken</th>
                                <th class="{{ $th }}">Reason / Policy Note</th>
                                <th class="{{ $th }}">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($recentActions as $act)
                            <tr class="border-t border-slate-100 hover:bg-slate-50/60 transition" wire:key="act-{{ $act->id }}">
                                <td class="{{ $td }}">
                                    <div class="font-bold text-slate-900 text-xs">{{ $act->admin?->name ?? 'System Policy Engine' }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">{{ $act->admin?->email }}</div>
                                </td>
                                <td class="{{ $td }}">
                                    <span class="px-2 py-0.5 rounded border text-xs font-black uppercase bg-slate-100 text-slate-800 border-slate-200">
                                        {{ str_replace('_', ' ', $act->action_taken) }}
                                    </span>
                                </td>
                                <td class="{{ $td }}">
                                    <div class="text-xs text-slate-800 font-medium">{{ $act->reason ?: 'Standard policy enforcement' }}</div>
                                    @if($act->internal_notes)
                                        <div class="text-[11px] text-slate-500 italic mt-0.5">Note: {{ $act->internal_notes }}</div>
                                    @endif
                                </td>
                                <td class="{{ $td }} whitespace-nowrap text-xs text-slate-600 font-mono">
                                    {{ $act->created_at?->format('M j, Y H:i:s') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center bg-slate-50/50">
                                    <p class="text-xs text-slate-500 font-medium">No recent moderation actions recorded.</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        @endif

        {{-- ================= 7. VERIFICATION & AGE ASSURANCE ================= --}}
        @if($tab === 'verification')
            <div class="space-y-6">
                <!-- Top 4 KPI Metrics -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Pending Review -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-amber-500">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Pending Review</span>
                            <span class="p-2 bg-amber-50 rounded-xl text-amber-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($verificationStats['pending'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full {{ ($verificationStats['pending'] ?? 0) > 0 ? 'bg-amber-500 animate-pulse' : 'bg-slate-400' }}"></span>
                            <span>Awaiting staff inspection</span>
                        </div>
                    </div>

                    <!-- Verified 18+ Users -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-emerald-600">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Verified 18+ Members</span>
                            <span class="p-2 bg-emerald-50 rounded-xl text-emerald-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($verificationStats['total_verified_users'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold">
                            Full platform age compliant members
                        </div>
                    </div>

                    <!-- Approved Requests -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-blue-600">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Historical Approvals</span>
                            <span class="p-2 bg-blue-50 rounded-xl text-blue-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($verificationStats['approved'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold">
                            Credentials verified and stamped
                        </div>
                    </div>

                    <!-- Rejected Attempts -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-rose-500">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Rejected Requests</span>
                            <span class="p-2 bg-rose-50 rounded-xl text-rose-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($verificationStats['rejected'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold">
                            Underage or invalid submissions
                        </div>
                    </div>
                </div>

                <!-- Section Action Header & Real-time Filters -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
                    <div>
                        <h2 class="text-lg font-black text-slate-950 flex items-center gap-2">
                            <span>Age Assurance & 18+ Identity Verification</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-pink-50 text-[#F20B83] border border-pink-200">Trust & Safety</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Mandatory adult compliance gate (18 U.S.C. §§ 2251/2257, Texas HB 1181 & state mandates).</p>
                    </div>

                    @if($this->userCan('verification.manage'))
                        <button type="button" wire:click="openManualVerifyModal"
                            class="px-4 py-2 rounded-xl bg-[#F20B83] hover:bg-pink-600 text-white text-xs font-black shadow-sm flex items-center gap-1.5 transition whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            <span>+ Manual Verify Member</span>
                        </button>
                    @endif
                </div>

                <!-- Search & Filter Controls -->
                <div class="{{ $card }} p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                        <div class="sm:col-span-6 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" wire:model.live.debounce.300ms="verificationSearch"
                                placeholder="Search by member name, email, ID, or reference number..."
                                class="{{ $input }} pl-9 font-medium" />
                        </div>

                        <div class="sm:col-span-3">
                            <select wire:model.live="verificationStatusFilter" class="{{ $input }} font-medium">
                                <option value="all">All Statuses (Pending, Approved, Rejected)</option>
                                <option value="pending">Pending Review Only</option>
                                <option value="approved">Approved (18+ Verified)</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>

                        <div class="sm:col-span-3">
                            <select wire:model.live="verificationMethodFilter" class="{{ $input }} font-medium">
                                <option value="all">All Verification Methods</option>
                                <option value="digital_id">Digital ID Upload</option>
                                <option value="credit_card_token">Credit Card Token</option>
                                <option value="self_declaration">Self Declaration</option>
                                <option value="third_party_reference">Third-Party Gateway</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Real-time Verification Requests Table -->
                <div class="{{ $card }} overflow-x-auto shadow-sm">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="{{ $th }}">Member</th>
                                <th class="{{ $th }}">Method & Reference</th>
                                <th class="{{ $th }}">Declared DOB & Age</th>
                                <th class="{{ $th }}">Compliance Status</th>
                                <th class="{{ $th }}">Submission Timeline</th>
                                <th class="{{ $th }} text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($verifications as $v)
                                <tr class="border-t border-slate-100 hover:bg-slate-50/70 transition" wire:key="ver-{{ $v->id }}">
                                    <!-- User Column -->
                                    <td class="{{ $td }}">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-800 font-black text-xs flex items-center justify-center shrink-0 border border-slate-200">
                                                {{ strtoupper(substr($v->user?->name ?? 'U', 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-black text-slate-950 text-xs flex items-center gap-1.5 truncate">
                                                    <span>{{ $v->user?->name ?? 'User #' . $v->user_id }}</span>
                                                    @if($v->user?->is_age_verified)
                                                        <span class="text-emerald-600 text-xs" title="18+ Verified">✓</span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] text-slate-500 font-medium truncate">
                                                    {{ $v->user?->email ?? 'No email' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Method Column -->
                                    <td class="{{ $td }}">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold border
                                            {{ $v->method === 'digital_id' ? 'bg-purple-50 text-purple-700 border-purple-200' : ($v->method === 'credit_card_token' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-slate-100 text-slate-700 border-slate-200') }}">
                                            <span>{{ strtoupper(str_replace('_', ' ', $v->method)) }}</span>
                                        </span>
                                        @if($v->third_party_reference)
                                            <div class="text-[10px] font-mono text-slate-400 truncate max-w-[140px] mt-0.5">
                                                Ref: {{ $v->third_party_reference }}
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Declared DOB Column -->
                                    <td class="{{ $td }}">
                                        @if($v->declared_birthdate)
                                            <div class="font-bold text-slate-900 text-xs">
                                                {{ $v->declared_birthdate->format('M j, Y') }}
                                            </div>
                                            <div class="mt-0.5">
                                                @php
                                                    $age = (int) $v->declared_birthdate->age;
                                                @endphp
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold border {{ $age >= 18 ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200 font-black' }}">
                                                    {{ $age }} years old
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400">Not declared</span>
                                        @endif
                                    </td>

                                    <!-- Status Column -->
                                    <td class="{{ $td }}">
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $badge($v->status) }}">
                                            {{ strtoupper($v->status) }}
                                        </span>
                                    </td>

                                    <!-- Submission Timeline Column -->
                                    <td class="{{ $td }}">
                                        <div class="text-xs text-slate-800 font-semibold">
                                            {{ $v->created_at?->diffForHumans() ?? 'N/A' }}
                                        </div>
                                        @if($v->reviewed_by_admin_id)
                                            <div class="text-[10px] text-slate-500 mt-0.5">
                                                Reviewed by {{ $v->reviewer?->name ?? 'Admin' }}
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Actions Column -->
                                    <td class="{{ $td }} text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" wire:click="openInspectVerificationModal({{ $v->id }})"
                                                class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200" title="Inspect details">
                                                Inspect
                                            </button>
                                            @if($v->status === 'pending' && $this->userCan('verification.manage'))
                                                <button type="button" wire:click="approveAgeVerification({{ $v->id }})"
                                                    wire:confirm="Confirm this person is 18 or older and verified?"
                                                    class="{{ $btn }} bg-emerald-600 hover:bg-emerald-500 text-white font-bold" title="Approve 18+">
                                                    Approve 18+
                                                </button>
                                                <button type="button" wire:click="openRejectVerificationModal({{ $v->id }})"
                                                    class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white font-bold" title="Reject">
                                                    Reject
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="{{ $td }} text-center py-12 text-slate-500">
                                        <div class="max-w-xs mx-auto text-center space-y-2">
                                            <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xl">
                                                🛡️
                                            </div>
                                            <p class="font-bold text-slate-900">No verification requests found</p>
                                            <p class="text-xs text-slate-500">All members have been processed or match criteria.</p>
                                            @if($this->userCan('verification.manage'))
                                                <button type="button" wire:click="openManualVerifyModal" class="px-3 py-1.5 bg-[#F20B83] text-white rounded-xl text-xs font-bold shadow">
                                                    + Manually Verify Member Now
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="p-3 border-t border-slate-100 bg-slate-50/50">{{ $verifications->links() }}</div>
                </div>
            </div>
        @endif

        {{-- ================= NOTIFICATIONS & BROADCAST HUB ================= --}}
        @if($tab === 'notifications')
            <div class="space-y-6">
                <!-- Top 4 KPI Metrics -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Delivered -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-emerald-600">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Delivered Notifications</span>
                            <span class="p-2 bg-emerald-50 rounded-xl text-emerald-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($notificationStats['sent'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold">
                            Successful email, SMS, and in-app dispatches
                        </div>
                    </div>

                    <!-- Queued -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-blue-500">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Queued Dispatches</span>
                            <span class="p-2 bg-blue-50 rounded-xl text-blue-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($notificationStats['queued'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold">
                            Pending delivery worker pickup
                        </div>
                    </div>

                    <!-- Delivery Failures -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-rose-500">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Delivery Failures</span>
                            <span class="p-2 bg-rose-50 rounded-xl text-rose-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($notificationStats['failed'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold">
                            Bounces or carrier errors
                        </div>
                    </div>

                    <!-- Email Templates -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-pink-600">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Active Templates</span>
                            <span class="p-2 bg-pink-50 rounded-xl text-pink-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($notificationStats['templates_count'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold">
                            System notification templates
                        </div>
                    </div>
                </div>

                <!-- Sub-Navigation and Actions -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="setNotificationsSubTab('logs')"
                            class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center gap-2 {{ $notificationsSubTab === 'logs' ? 'bg-[#F20B83] text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                            <span>Delivery Audit Logs</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $notificationsSubTab === 'logs' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $notifications->total() }}</span>
                        </button>
                        <button type="button" wire:click="setNotificationsSubTab('templates')"
                            class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center gap-2 {{ $notificationsSubTab === 'templates' ? 'bg-[#F20B83] text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                            <span>Email & Alert Templates</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $notificationsSubTab === 'templates' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ count($templates) }}</span>
                        </button>
                    </div>

                    @if($this->userCan('settings.manage'))
                        <button type="button" wire:click="openBroadcastModal"
                            class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black shadow-sm flex items-center gap-1.5 transition whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                            <span>+ Send Broadcast Notification</span>
                        </button>
                    @endif
                </div>

                @if($notificationsSubTab === 'logs')
                    <!-- Search & Filter Controls -->
                    <div class="{{ $card }} p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                            <div class="sm:col-span-6 relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input type="text" wire:model.live.debounce.300ms="notificationSearch"
                                    placeholder="Search by subject, recipient, user, or notification type..."
                                    class="{{ $input }} pl-9 font-medium" />
                            </div>

                            <div class="sm:col-span-3">
                                <select wire:model.live="notificationStatusFilter" class="{{ $input }} font-medium">
                                    <option value="all">All Delivery Statuses</option>
                                    <option value="sent">Sent / Delivered</option>
                                    <option value="queued">Queued</option>
                                    <option value="failed">Failed Delivery</option>
                                </select>
                            </div>

                            <div class="sm:col-span-3">
                                <select wire:model.live="notificationChannelFilter" class="{{ $input }} font-medium">
                                    <option value="all">All Channels (Email, SMS, App)</option>
                                    <option value="email">Email Dispatches</option>
                                    <option value="sms">SMS Text Alerts</option>
                                    <option value="database">In-App Database Alerts</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Logs Table -->
                    <div class="{{ $card }} overflow-x-auto shadow-sm">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th class="{{ $th }}">Recipient / Member</th>
                                    <th class="{{ $th }}">Channel</th>
                                    <th class="{{ $th }}">Subject & Notification Type</th>
                                    <th class="{{ $th }}">Delivery Status</th>
                                    <th class="{{ $th }}">Timestamp</th>
                                    <th class="{{ $th }} text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($notifications as $n)
                                    <tr class="border-t border-slate-100 hover:bg-slate-50/70 transition" wire:key="notif-{{ $n->id }}">
                                        <!-- User Column -->
                                        <td class="{{ $td }}">
                                            <div class="font-black text-slate-950 text-xs">
                                                {{ $n->user?->name ?? 'User #' . $n->user_id }}
                                            </div>
                                            <div class="text-[11px] font-mono text-slate-600 truncate max-w-[180px]">
                                                {{ $n->recipient_target }}
                                            </div>
                                        </td>

                                        <!-- Channel Column -->
                                        <td class="{{ $td }}">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold border
                                                {{ $n->channel === 'email' ? 'bg-blue-50 text-blue-700 border-blue-200' : ($n->channel === 'sms' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-purple-50 text-purple-700 border-purple-200') }}">
                                                {{ strtoupper($n->channel) }}
                                            </span>
                                        </td>

                                        <!-- Subject & Type Column -->
                                        <td class="{{ $td }}">
                                            <div class="font-bold text-slate-900 text-xs">
                                                {{ $n->subject ?: 'Platform Alert' }}
                                            </div>
                                            <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                                {{ $n->notification_type }}
                                            </div>
                                            @if($n->error_details)
                                                <div class="text-[11px] font-mono text-rose-600 bg-rose-50 p-1 rounded mt-1 border border-rose-100">
                                                    Error: {{ Str::limit($n->error_details, 80) }}
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Status Column -->
                                        <td class="{{ $td }}">
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $badge($n->status) }}">
                                                {{ strtoupper($n->status) }}
                                            </span>
                                        </td>

                                        <!-- Timestamp Column -->
                                        <td class="{{ $td }}">
                                            <div class="text-xs text-slate-800 font-semibold">
                                                {{ $n->sent_at?->diffForHumans() ?? $n->created_at?->diffForHumans() }}
                                            </div>
                                            <div class="text-[10px] text-slate-400">
                                                {{ $n->sent_at?->format('M j, Y H:i') ?? 'Not sent' }}
                                            </div>
                                        </td>

                                        <!-- Actions Column -->
                                        <td class="{{ $td }} text-right">
                                            @if($this->userCan('settings.manage'))
                                                <button type="button" wire:click="resendNotification({{ $n->id }})"
                                                    class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200">
                                                    Resend
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="{{ $td }} text-center py-12 text-slate-500">
                                            No notification delivery logs found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="p-3 border-t border-slate-100 bg-slate-50/50">{{ $notifications->links() }}</div>
                    </div>
                @else
                    <!-- Templates Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($templates as $tpl)
                            <div class="{{ $card }} p-5 flex flex-col justify-between border-2 {{ $tpl->is_active ? 'border-slate-200' : 'border-slate-200 bg-slate-50/80 opacity-75' }}">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase {{ $tpl->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-200 text-slate-600' }}">
                                            {{ $tpl->is_active ? 'Active Template' : 'Disabled' }}
                                        </span>
                                        <span class="text-xs font-mono font-bold text-slate-400">{{ $tpl->slug }}</span>
                                    </div>
                                    <h3 class="text-base font-black text-slate-950">{{ $tpl->name }}</h3>
                                    <p class="text-xs text-slate-700 font-semibold mt-1">Subject: <span class="text-slate-900 font-normal">{{ $tpl->subject }}</span></p>

                                    @if(!empty($tpl->available_variables))
                                        <div class="mt-3 flex flex-wrap gap-1">
                                            @foreach((array) $tpl->available_variables as $var)
                                                <span class="px-2 py-0.5 bg-slate-100 rounded text-[10px] font-mono text-slate-700">@{{ {{ $var }} }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                                    @if($this->userCan('settings.manage'))
                                        <button type="button" wire:click="toggleTemplate({{ $tpl->id }})"
                                            class="{{ $btn }} {{ $tpl->is_active ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800' }}">
                                            {{ $tpl->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- ================= 8. REPORTS & INCIDENT MODERATION ================= --}}
        @if($tab === 'reports')
            <div wire:poll.10s class="space-y-6">
                <!-- Top Header & Live Telemetry Badge -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-rose-50 border border-rose-200 text-[#F20B83] flex items-center justify-center text-lg shadow-xs">🚨</span>
                            <span>Reports &amp; Incident Moderation</span>
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">Real-time community abuse reports, safety investigations, and urgent content takedown actions.</p>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 shadow-2xs">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span>Live Queue · Auto-updating (10s)</span>
                        </div>
                        <button 
                            type="button" 
                            wire:click="exportReportsCsv"
                            class="px-3.5 py-1.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 flex items-center gap-1.5 shadow-2xs transition"
                            title="Export full reports database for legal or compliance records"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Export CSV</span>
                        </button>
                    </div>
                </div>

                <!-- 4 KPI Stat Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Stat 1: Active Reports Queue -->
                    <div class="bg-white rounded-2xl p-4.5 border border-slate-200 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Incident Queue</span>
                            <div class="w-8 h-8 rounded-full bg-rose-50 text-[#F20B83] flex items-center justify-center font-bold text-xs">
                                ⚡
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="text-2xl font-black text-slate-900 flex items-center gap-2">
                                <span>{{ number_format($reportStats['active_queue']) }}</span>
                                @if($reportStats['urgent_count'] > 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white animate-pulse">
                                        {{ $reportStats['urgent_count'] }} URGENT
                                    </span>
                                @endif
                            </div>
                            <div class="text-[11px] text-slate-500 font-semibold mt-1 flex items-center gap-2">
                                <span class="text-rose-600 font-bold">{{ $reportStats['open'] }} open</span>
                                <span>·</span>
                                <span class="text-purple-600 font-bold">{{ $reportStats['investigating'] }} investigating</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stat 2: Critical & High Risk Alerts -->
                    <div class="bg-white rounded-2xl p-4.5 border border-slate-200 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">High &amp; Critical Risk</span>
                            <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs">
                                ⚠️
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="text-2xl font-black {{ $reportStats['critical_pending'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ number_format($reportStats['critical_pending']) }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-semibold mt-1">
                                Underage, trafficking &amp; fraud priority
                            </div>
                        </div>
                    </div>

                    <!-- Stat 3: Resolution Rate -->
                    <div class="bg-white rounded-2xl p-4.5 border border-slate-200 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Resolution Rate</span>
                            <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                                ✓
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="text-2xl font-black text-emerald-700">
                                {{ $reportStats['resolution_rate'] }}%
                            </div>
                            <div class="text-[11px] text-emerald-700 font-semibold mt-1">
                                {{ number_format($reportStats['resolved']) }} resolved cases closed
                            </div>
                        </div>
                    </div>

                    <!-- Stat 4: All-Time Reports Recorded -->
                    <div class="bg-white rounded-2xl p-4.5 border border-slate-200 shadow-xs flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Filed Reports</span>
                            <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                📋
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="text-2xl font-black text-slate-900">
                                {{ number_format($reportStats['total']) }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-semibold mt-1">
                                {{ number_format($reportStats['dismissed']) }} cleared as false alarms
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sub-Navigation Ribbon -->
                <div class="flex items-center gap-1.5 border-b border-slate-200 pb-3 overflow-x-auto">
                    <button 
                        type="button" 
                        wire:click="setReportSubTab('queue')"
                        class="px-3.5 py-2 rounded-xl text-xs font-black transition whitespace-nowrap flex items-center gap-1.5 {{ $reportSubTab === 'queue' ? 'bg-[#F20B83] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}"
                    >
                        <span>🚨</span>
                        <span>Live Incident Queue</span>
                        @if($reportStats['open'] > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $reportSubTab === 'queue' ? 'bg-white/30 text-white' : 'bg-rose-100 text-rose-800 font-black' }}">{{ $reportStats['open'] }}</span>
                        @endif
                    </button>
                    <button 
                        type="button" 
                        wire:click="setReportSubTab('trends')"
                        class="px-3.5 py-2 rounded-xl text-xs font-black transition whitespace-nowrap flex items-center gap-1.5 {{ $reportSubTab === 'trends' ? 'bg-[#F20B83] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}"
                    >
                        <span>📊</span>
                        <span>Abuse Analytics &amp; Breakdown</span>
                    </button>
                    <button 
                        type="button" 
                        wire:click="setReportSubTab('export')"
                        class="px-3.5 py-2 rounded-xl text-xs font-black transition whitespace-nowrap flex items-center gap-1.5 {{ $reportSubTab === 'export' ? 'bg-[#F20B83] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}"
                    >
                        <span>📜</span>
                        <span>Law Enforcement &amp; Legal Export</span>
                    </button>
                </div>

                {{-- SUB-TAB 1: LIVE INCIDENT QUEUE --}}
                @if($reportSubTab === 'queue')
                    <!-- Filter Toolbar -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                            <!-- Search -->
                            <div class="lg:col-span-5 relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.300ms="reportSearch" 
                                    placeholder="Search report #, keyword, target, reporter IP..." 
                                    class="{{ $input }} pl-9 text-xs font-semibold"
                                >
                            </div>

                            <!-- Status Filter -->
                            <div class="lg:col-span-2">
                                <select wire:model.live="reportStatusFilter" class="{{ $input }} text-xs font-bold">
                                    <option value="all">All Statuses</option>
                                    <option value="open">Open (Urgent)</option>
                                    <option value="investigating">Investigating</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="dismissed">Dismissed</option>
                                </select>
                            </div>

                            <!-- Severity / Risk Filter -->
                            <div class="lg:col-span-2">
                                <select wire:model.live="reportSeverityFilter" class="{{ $input }} text-xs font-bold">
                                    <option value="all">All Severities</option>
                                    <option value="critical">🔴 Critical Risk</option>
                                    <option value="high">🟠 High Risk</option>
                                    <option value="medium">🟡 Medium Risk</option>
                                    <option value="low">⚪ Low Risk</option>
                                </select>
                            </div>

                            <!-- Abuse Reason Filter -->
                            <div class="lg:col-span-2">
                                <select wire:model.live="reportReasonFilter" class="{{ $input }} text-xs font-bold">
                                    <option value="all">All Abuse Types</option>
                                    <option value="underage_suspected">🔞 Underage Suspicion</option>
                                    <option value="commercial_solicitation">🚫 Commercial Solicitation</option>
                                    <option value="financial_fraud_scam">💸 Fraud &amp; Scams</option>
                                    <option value="fake_profile">👤 Fake Profile</option>
                                    <option value="harassment">⚠️ Harassment</option>
                                    <option value="spam_duplicate">🗑️ Spam / Duplicate</option>
                                </select>
                            </div>

                            <!-- Reset Button -->
                            <div class="lg:col-span-1 flex justify-end">
                                <button 
                                    type="button" 
                                    wire:click="resetReportFilters"
                                    class="w-full py-2 px-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition text-center"
                                    title="Reset all filters"
                                >
                                    Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Incident Table -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-left">
                                <thead>
                                    <tr class="bg-slate-50/80">
                                        <th class="{{ $th }}"># ID / Time</th>
                                        <th class="{{ $th }}">Severity / Reason</th>
                                        <th class="{{ $th }}">Reported Target</th>
                                        <th class="{{ $th }}">Reporter &amp; Evidence</th>
                                        <th class="{{ $th }}">Incident Summary</th>
                                        <th class="{{ $th }}">Status</th>
                                        <th class="{{ $th }} text-right">Moderation Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($reports as $r)
                                        <tr class="hover:bg-slate-50/80 transition" wire:key="report-row-{{ $r->id }}">
                                            <!-- ID & Timestamp -->
                                            <td class="{{ $td }} whitespace-nowrap">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-black text-slate-900 text-xs">#{{ $r->id }}</span>
                                                    @if($r->is_urgent_takedown)
                                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-rose-600 text-white tracking-wider animate-pulse">
                                                            URGENT
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-[10px] text-slate-500 font-semibold mt-0.5" title="{{ $r->created_at?->toIso8601String() }}">
                                                    {{ $r->created_at?->diffForHumans() }}
                                                </div>
                                            </td>

                                            <!-- Severity & Reason -->
                                            <td class="{{ $td }}">
                                                <div class="flex items-center gap-1 mb-1">
                                                    @php
                                                        $riskClasses = match($r->risk_level) {
                                                            'critical' => 'bg-rose-50 text-rose-700 border-rose-200 font-black',
                                                            'high' => 'bg-amber-50 text-amber-700 border-amber-200 font-bold',
                                                            'medium' => 'bg-blue-50 text-blue-700 border-blue-200 font-bold',
                                                            default => 'bg-slate-100 text-slate-600 border-slate-200 font-medium',
                                                        };
                                                    @endphp
                                                    <span class="px-1.5 py-0.2 rounded text-[10px] uppercase border {{ $riskClasses }}">
                                                        {{ $r->risk_level ?? 'medium' }}
                                                    </span>
                                                </div>
                                                <div class="font-extrabold text-xs text-slate-900">
                                                    {{ ucwords(str_replace('_', ' ', $r->reason_code)) }}
                                                </div>
                                            </td>

                                            <!-- Reported Target -->
                                            <td class="{{ $td }}">
                                                @if($r->reportable_type === 'App\Modules\Listings\Models\Listing')
                                                    @php $targetListing = \App\Modules\Listings\Models\Listing::find($r->reportable_id); @endphp
                                                    @if($targetListing)
                                                        <div class="flex flex-col max-w-[220px]">
                                                            <a href="{{ route('listings.show', $targetListing->slug) }}" target="_blank" class="font-bold text-xs text-[#F20B83] hover:underline truncate" title="{{ $targetListing->title }}">
                                                                {{ $targetListing->title }}
                                                            </a>
                                                            <span class="text-[10px] text-slate-500">
                                                                Listing #{{ $targetListing->id }} · {{ $targetListing->category?->name ?? 'Classified' }}
                                                            </span>
                                                        </div>
                                                    @else
                                                        <span class="text-xs font-semibold text-slate-400 italic">Listing #{{ $r->reportable_id }} (Deleted)</span>
                                                    @endif
                                                @elseif($r->reportable_type === 'App\Models\User')
                                                    @php $targetUser = \App\Models\User::find($r->reportable_id); @endphp
                                                    @if($targetUser)
                                                        <div class="flex flex-col">
                                                            <span class="font-bold text-xs text-slate-900">{{ $targetUser->name }}</span>
                                                            <span class="text-[10px] font-mono text-slate-500">User #{{ $targetUser->id }} ({{ $targetUser->email }})</span>
                                                        </div>
                                                    @else
                                                        <span class="text-xs font-semibold text-slate-400 italic">User #{{ $r->reportable_id }} (Removed)</span>
                                                    @endif
                                                @else
                                                    <span class="text-xs font-semibold text-slate-600">{{ class_basename($r->reportable_type) }} #{{ $r->reportable_id }}</span>
                                                @endif
                                            </td>

                                            <!-- Reporter & Evidence -->
                                            <td class="{{ $td }}">
                                                <div class="font-bold text-xs text-slate-900">
                                                    {{ $r->reporter?->name ?? 'Anonymous Visitor' }}
                                                </div>
                                                <div class="text-[10px] text-slate-500 font-mono">
                                                    IP: {{ $r->reporter_ip ?? '127.0.0.1' }}
                                                </div>
                                                @php
                                                    $evCount = is_array($r->evidence_urls) ? count($r->evidence_urls) : ($r->evidence_urls ? count(json_decode($r->evidence_urls, true) ?: []) : 0);
                                                @endphp
                                                @if($evCount > 0)
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.2 rounded mt-0.5">
                                                        📎 {{ $evCount }} {{ Str::plural('attachment', $evCount) }}
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Details -->
                                            <td class="{{ $td }}">
                                                <p class="text-xs text-slate-700 max-w-xs line-clamp-2 leading-relaxed" title="{{ $r->details }}">
                                                    {{ $r->details }}
                                                </p>
                                                @if($r->resolution_notes)
                                                    <p class="text-[10px] text-emerald-700 font-semibold mt-1 bg-emerald-50 p-1 rounded border border-emerald-100">
                                                        Note: {{ Str::limit($r->resolution_notes, 60) }}
                                                    </p>
                                                @endif
                                            </td>

                                            <!-- Status -->
                                            <td class="{{ $td }} whitespace-nowrap">
                                                @php
                                                    $statusClasses = match($r->status) {
                                                        'open' => 'bg-rose-50 text-rose-700 border-rose-200 font-black',
                                                        'investigating' => 'bg-purple-50 text-purple-700 border-purple-200 font-black',
                                                        'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-black',
                                                        'dismissed' => 'bg-slate-100 text-slate-600 border-slate-200 font-bold',
                                                        default => 'bg-slate-100 text-slate-600 border-slate-200',
                                                    };
                                                @endphp
                                                <span class="px-2 py-0.5 rounded-full border text-[11px] uppercase {{ $statusClasses }}">
                                                    {{ $r->status }}
                                                </span>
                                                @if($r->resolvedBy)
                                                    <span class="block text-[10px] text-slate-400 mt-0.5">
                                                        by {{ $r->resolvedBy->name }}
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Actions -->
                                            <td class="{{ $td }} text-right space-x-1 whitespace-nowrap">
                                                <!-- Inspect Details -->
                                                <button 
                                                    type="button" 
                                                    wire:click="openInspectReportModal({{ $r->id }})"
                                                    class="{{ $btn }} bg-sky-50 hover:bg-sky-100 text-sky-800 border border-sky-200 font-extrabold"
                                                    title="Inspect complete incident narrative, evidence, and target"
                                                >
                                                    Inspect
                                                </button>

                                                <!-- Investigate Action -->
                                                @if($r->status === 'open')
                                                    <button 
                                                        type="button" 
                                                        wire:click="investigateReport({{ $r->id }})"
                                                        class="{{ $btn }} bg-purple-50 hover:bg-purple-100 text-purple-800 border border-purple-200 font-extrabold"
                                                        title="Mark under active staff investigation"
                                                    >
                                                        Investigate
                                                    </button>
                                                @endif

                                                <!-- Resolve / Action Modal -->
                                                @if(in_array($r->status, ['open', 'investigating']))
                                                    <button 
                                                        type="button" 
                                                        wire:click="openResolveReportModal({{ $r->id }})"
                                                        class="{{ $btn }} bg-[#F20B83] hover:bg-[#d00970] text-white font-extrabold shadow-2xs"
                                                        title="Conclude or dismiss report with actions"
                                                    >
                                                        Action
                                                    </button>
                                                @endif

                                                <!-- 1-Click Quarantine for Listings -->
                                                @if($r->reportable_type === 'App\Modules\Listings\Models\Listing' && in_array($r->status, ['open', 'investigating']))
                                                    <button 
                                                        type="button" 
                                                        wire:click="quarantineReportedListing({{ $r->id }})"
                                                        wire:confirm="Immediately quarantine and pull this listing from the public website?"
                                                        class="{{ $btn }} bg-rose-700 hover:bg-rose-800 text-white font-black shadow-2xs"
                                                        title="Urgent Takedown: Quarantine Listing Immediately"
                                                    >
                                                        Quarantine
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="p-12 text-center bg-slate-50/50">
                                                <div class="max-w-md mx-auto space-y-2">
                                                    <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl mx-auto font-black shadow-xs">
                                                        ✓
                                                    </div>
                                                    <h3 class="text-sm font-black text-slate-900">Incident Queue Clear</h3>
                                                    <p class="text-xs text-slate-500">No abuse or community reports match your current filter criteria.</p>
                                                    <button 
                                                        type="button" 
                                                        wire:click="resetReportFilters"
                                                        class="mt-2 px-3 py-1.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs transition inline-flex items-center gap-1"
                                                    >
                                                        Reset Filter View
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 border-t border-slate-100 bg-white">
                            {{ $reports->links() }}
                        </div>
                    </div>
                @endif

                {{-- SUB-TAB 2: ABUSE ANALYTICS & TRENDS --}}
                @if($reportSubTab === 'trends')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Reason Breakdown -->
                        <div class="{{ $card }} p-5 space-y-4">
                            <div class="border-b border-slate-100 pb-2">
                                <h3 class="text-sm font-black text-slate-900">Abuse Reason Distribution</h3>
                                <p class="text-[11px] text-slate-500">Breakdown of community reports by statutory violation category</p>
                            </div>
                            <div class="space-y-2.5">
                                @forelse($reportStats['reason_breakdown'] as $reason => $count)
                                    <div>
                                        <div class="flex justify-between text-xs font-bold text-slate-800 mb-1">
                                            <span>{{ ucwords(str_replace('_', ' ', $reason)) }}</span>
                                            <span class="font-mono text-[#F20B83]">{{ $count }} ({{ $reportStats['total'] > 0 ? round(($count / $reportStats['total']) * 100) : 0 }}%)</span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div class="bg-[#F20B83] h-2 rounded-full" style="width: {{ $reportStats['total'] > 0 ? ($count / $reportStats['total']) * 100 : 0 }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 italic">No breakdown data recorded.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Risk Level Breakdown -->
                        <div class="{{ $card }} p-5 space-y-4">
                            <div class="border-b border-slate-100 pb-2">
                                <h3 class="text-sm font-black text-slate-900">Incident Severity Classification</h3>
                                <p class="text-[11px] text-slate-500">Risk classification priority matrix</p>
                            </div>
                            <div class="space-y-3">
                                @php
                                    $riskColors = [
                                        'critical' => 'text-rose-700 bg-rose-500',
                                        'high' => 'text-amber-700 bg-amber-500',
                                        'medium' => 'text-blue-700 bg-blue-500',
                                        'low' => 'text-slate-700 bg-slate-400',
                                    ];
                                @endphp
                                @forelse($reportStats['risk_breakdown'] as $risk => $count)
                                    <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-full {{ $riskColors[$risk] ?? 'bg-slate-400' }}"></span>
                                            <span class="text-xs font-black uppercase text-slate-900">{{ $risk }} Risk</span>
                                        </div>
                                        <span class="font-mono font-black text-xs text-slate-900">{{ $count }} reports</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 italic">No severity data available.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif

                {{-- SUB-TAB 3: LAW ENFORCEMENT & LEGAL EXPORT --}}
                @if($reportSubTab === 'export')
                    <div class="{{ $card }} p-6 max-w-2xl mx-auto space-y-5 text-center">
                        <div class="w-14 h-14 rounded-3xl bg-rose-50 border-2 border-rose-200 text-[#F20B83] flex items-center justify-center text-2xl mx-auto shadow-xs">
                            📜
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900">Compliance &amp; Law Enforcement Export</h3>
                            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto leading-relaxed">
                                Generate an encrypted, timestamped CSV extract of all filed abuse complaints, reporter IP telemetry, investigation notes, and takedown actions for statutory compliance (18 U.S.C. § 2257 / FOSTA-SESTA).
                            </p>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-left text-xs space-y-2 text-slate-700">
                            <div class="flex justify-between">
                                <span class="font-semibold text-slate-500">Total Incident Records:</span>
                                <span class="font-mono font-black text-slate-900">{{ number_format($reportStats['total']) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold text-slate-500">Resolution Status:</span>
                                <span class="font-mono font-black text-emerald-700">{{ $reportStats['resolved'] }} Resolved · {{ $reportStats['dismissed'] }} Cleared</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold text-slate-500">Format:</span>
                                <span class="font-mono font-bold text-slate-900">RFC-4180 UTF-8 CSV</span>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            wire:click="exportReportsCsv"
                            class="px-6 py-3 rounded-xl bg-[#F20B83] hover:bg-[#d00970] text-white font-black text-xs shadow-md transition inline-flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span>Download Real-Time Compliance CSV</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif

        {{-- ================= 9. CONTACT ABUSE ================= --}}
        @if($tab === 'abuse')
            <div class="{{ $card }} overflow-x-auto">
                <div class="p-4 flex justify-between"><h2 class="font-bold text-slate-900">Flagged Contact Messages</h2><a href="{{ route('admin.moderation.messages.queue') }}" class="text-xs text-rose-600 hover:underline">Full message moderation tools &rarr;</a></div>
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">#</th><th class="{{ $th }}">Listing</th><th class="{{ $th }}">Recipient</th><th class="{{ $th }}">Received</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($abuseMessages as $m)
                        <tr class="border-t border-slate-200" wire:key="abuse-{{ $m->id }}">
                            <td class="{{ $td }}">{{ $m->id }}</td>
                            <td class="{{ $td }}">{{ $m->listing?->title ?? '—' }}</td>
                            <td class="{{ $td }}">{{ $m->recipient?->name ?? '—' }}</td>
                            <td class="{{ $td }}">{{ $m->created_at?->diffForHumans() }}</td>
                            <td class="{{ $td }}">@if($this->userCan('moderation.act'))<button wire:click="dismissFlaggedMessage({{ $m->id }})" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">Dismiss flag</button>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="{{ $td }} text-center text-slate-400">No flagged messages.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $abuseMessages->links() }}</div>
            </div>
        @endif

        {{-- ================= DMCA TAKEDOWNS (Phase 12) ================= --}}
        @if($tab === 'dmca')
            <div class="{{ $card }} p-4 mb-4 md:w-1/3">
                <select wire:model.live="dmcaStatusFilter" class="{{ $input }}" aria-label="DMCA status filter">
                    <option value="all">All statuses</option><option value="pending">Pending</option><option value="takedown_issued">Takedown issued</option><option value="counter_noticed">Counter-noticed</option><option value="rejected">Rejected</option><option value="resolved">Resolved</option>
                </select>
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Ticket</th><th class="{{ $th }}">Claimant</th><th class="{{ $th }}">Infringing URL</th><th class="{{ $th }}">Work Description</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($dmcaRequests as $dmca)
                        <tr class="border-t border-slate-200" wire:key="dmca-{{ $dmca->id }}">
                            <td class="{{ $td }} font-mono text-xs">{{ $dmca->ticket_number }}<div class="text-slate-500">{{ $dmca->created_at?->format('M j, Y') }}</div></td>
                            <td class="{{ $td }}"><div class="font-semibold">{{ $dmca->claimant_name }}</div><div class="text-xs text-slate-400">{{ $dmca->claimant_email }}</div></td>
                            <td class="{{ $td }} text-xs font-mono break-all max-w-xs">{{ $dmca->infringing_url }}</td>
                            <td class="{{ $td }} text-xs">{{ \Illuminate\Support\Str::limit($dmca->copyrighted_work, 120) }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($dmca->status) }}">{{ str_replace('_', ' ', $dmca->status) }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($dmca->status === 'pending')
                                    <button wire:click="openDmcaModal({{ $dmca->id }}, 'takedown_issued')" class="{{ $btn }} bg-rose-700 hover:bg-rose-600 text-white">Takedown</button>
                                    <button wire:click="openDmcaModal({{ $dmca->id }}, 'rejected')" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">Reject</button>
                                @elseif($dmca->status === 'takedown_issued')
                                    <button wire:click="openDmcaModal({{ $dmca->id }}, 'restored')" class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white">Restore</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No DMCA notices on file.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $dmcaRequests->links() }}</div>
            </div>
        @endif

        {{-- ================= LEGAL & LAW ENFORCEMENT (Phase 12) ================= --}}
        @if($tab === 'legal')
            <div class="{{ $card }} p-4 mb-4 md:w-1/3">
                <select wire:model.live="legalStatusFilter" class="{{ $input }}" aria-label="Legal status filter">
                    <option value="all">All statuses</option><option value="received">Received</option><option value="under_legal_review">Under legal review</option><option value="fulfilled">Fulfilled</option><option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Reference</th><th class="{{ $th }}">Agency / Officer</th><th class="{{ $th }}">Type / Case</th><th class="{{ $th }}">Target</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($legalRequests as $req)
                        <tr class="border-t border-slate-200" wire:key="legal-{{ $req->id }}">
                            <td class="{{ $td }} font-mono text-xs">{{ $req->reference_code }}<div class="text-slate-500">{{ $req->created_at?->format('M j, Y') }}</div></td>
                            <td class="{{ $td }}"><div class="font-semibold">{{ $req->agency_name }}</div><div class="text-xs text-slate-400">{{ $req->officer_name }} ({{ $req->official_email }})</div></td>
                            <td class="{{ $td }}"><span class="font-mono text-xs text-rose-600 uppercase">{{ str_replace('_', ' ', $req->request_type) }}</span><div class="text-xs text-slate-400">Case #{{ $req->case_number }}</div></td>
                            <td class="{{ $td }}">{{ $req->targetUser?->name ?? ($req->targetListing?->title ?? 'Platform Inquiry') }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($req->status) }}">{{ str_replace('_', ' ', $req->status) }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                <button wire:click="openLegalModal({{ $req->id }})" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">Review</button>
                                <button wire:click="freezeLegalEvidence({{ $req->id }})" class="{{ $btn }} bg-red-800 hover:bg-red-700 text-white">Freeze 2703(f)</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No law enforcement inquiries recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $legalRequests->links() }}</div>
            </div>
        @endif

        {{-- ================= STATE COMPLIANCE (Phase 12) ================= --}}
        @if($tab === 'compliance')
            <div class="flex justify-between items-center mb-3">
                <p class="text-xs text-slate-400">Statutory state age assurance, warning text, and geofencing configurations.</p>
                @if($this->userCan('settings.manage'))
                    <button wire:click="openStateComplianceModal" class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white">+ New State Rule</button>
                @endif
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">State</th><th class="{{ $th }}">Min Age</th><th class="{{ $th }}">Mandatory Digital ID</th><th class="{{ $th }}">Statutory Reference</th><th class="{{ $th }}">Warning Text</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($stateCompliances as $st)
                        <tr class="border-t border-slate-200" wire:key="st-{{ $st->id }}">
                            <td class="{{ $td }} font-semibold">{{ $st->state_name }} <span class="font-mono text-xs text-slate-400">({{ $st->state_code }})</span></td>
                            <td class="{{ $td }}">{{ $st->statutory_min_age }}+</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $st->requires_id_verification) }}">{{ $st->requires_id_verification ? 'Required' : 'Attestation' }}</span></td>
                            <td class="{{ $td }} text-xs font-mono text-slate-600">{{ $st->legal_reference ?? '—' }}</td>
                            <td class="{{ $td }} text-xs text-slate-400 max-w-xs truncate">{{ $st->mandatory_warning_text ?? 'Standard 18+ Gate' }}</td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($this->userCan('settings.manage'))
                                    <button wire:click="toggleStateIdRequirement({{ $st->id }})" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">{{ $st->requires_id_verification ? 'Disable ID' : 'Require ID' }}</button>
                                    <button wire:click="openStateComplianceModal({{ $st->id }})" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">Edit</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No state rules configured.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= EVIDENCE VAULT (Phase 12) ================= --}}
        @if($tab === 'evidence')
            <div class="mb-3 text-xs text-slate-400">
                Statutory 18 U.S.C. 2703(f) holds, subpoena preservation freezes, and litigation hold snapshots.
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Hold #</th><th class="{{ $th }}">Target Entity</th><th class="{{ $th }}">Reason</th><th class="{{ $th }}">Frozen By</th><th class="{{ $th }}">Expires</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($preservations as $pr)
                        <tr class="border-t border-slate-200" wire:key="hold-{{ $pr->id }}">
                            <td class="{{ $td }} font-mono text-xs">HOLD-{{ $pr->id }}</td>
                            <td class="{{ $td }}">{{ class_basename($pr->preservable_type) }} #{{ $pr->preservable_id }}</td>
                            <td class="{{ $td }} text-xs">{{ $pr->hold_reason }}</td>
                            <td class="{{ $td }}">{{ $pr->frozenBy?->name ?? 'System' }}</td>
                            <td class="{{ $td }} text-xs">{{ $pr->expires_at?->format('M j, Y') ?? 'Indefinite' }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $pr->is_active) }}">{{ $pr->is_active ? 'Active Hold' : 'Released' }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                <button wire:click="inspectPreservation({{ $pr->id }})" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">Snapshot</button>
                                @if($pr->is_active && $this->userCan('verification.manage'))
                                    <button wire:click="releaseEvidenceHold({{ $pr->id }})" wire:confirm="Release this evidence hold?" class="{{ $btn }} bg-amber-800 hover:bg-amber-700 text-white">Release</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="{{ $td }} text-center text-slate-400">No active evidence preservation holds.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $preservations->links() }}</div>
            </div>
        @endif

        {{-- ================= 3. CATEGORIES ================= --}}
        @if($tab === 'categories')
            <div class="flex justify-end mb-3"><a href="{{ route('admin.categories.create') }}" class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white">+ New Category</a></div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Category</th><th class="{{ $th }}">Slug</th><th class="{{ $th }}">Listings</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @foreach($categories as $c)
                        <tr class="border-t border-slate-200" wire:key="cat-{{ $c->id }}">
                            <td class="{{ $td }} {{ $c->parent_id ? 'pl-8' : 'font-semibold' }}">{{ $c->name }}</td>
                            <td class="{{ $td }} font-mono text-xs">{{ $c->slug }}</td>
                            <td class="{{ $td }}">{{ $c->listings_count }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $c->is_active) }}">{{ $c->is_active ? 'active' : 'inactive' }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                <button wire:click="toggleCategory({{ $c->id }})" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">{{ $c->is_active ? 'Deactivate' : 'Activate' }}</button>
                                <a href="{{ route('admin.categories.edit', $c) }}" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">Edit</a>
                                <a href="{{ route('admin.categories.attributes', $c) }}" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">Attributes</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 5. ATTRIBUTES ================= --}}
        @if($tab === 'attributes')
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Attribute</th><th class="{{ $th }}">Category</th><th class="{{ $th }}">Options</th><th class="{{ $th }}">Required</th><th class="{{ $th }}">Filterable</th><th class="{{ $th }}">Manage</th></tr></thead>
                    <tbody>
                    @forelse($attributes as $a)
                        <tr class="border-t border-slate-200" wire:key="attr-{{ $a->id }}">
                            <td class="{{ $td }} font-semibold">{{ $a->name }}</td>
                            <td class="{{ $td }}">{{ $a->category?->name ?? 'Global' }}</td>
                            <td class="{{ $td }}">{{ $a->options_count }}</td>
                            <td class="{{ $td }}">{{ $a->is_required ? 'Yes' : 'No' }}</td>
                            <td class="{{ $td }}">{{ $a->is_filterable ? 'Yes' : 'No' }}</td>
                            <td class="{{ $td }}">@if($a->category)<a href="{{ route('admin.categories.attributes', $a->category) }}" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">Manage</a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No attributes defined.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 4. LOCATIONS ================= --}}
        @if($tab === 'locations')
            <div class="flex justify-between items-center mb-3">
                <p class="text-sm text-slate-400">{{ $states->count() }} states/territories · {{ number_format($cityCount) }} cities</p>
                <a href="{{ route('admin.locations.index') }}" class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white">City, alias &amp; ZIP manager &rarr;</a>
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">State</th><th class="{{ $th }}">Code</th><th class="{{ $th }}">Cities</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @foreach($states as $s)
                        <tr class="border-t border-slate-200" wire:key="state-{{ $s->id }}">
                            <td class="{{ $td }}">{{ $s->name }}</td>
                            <td class="{{ $td }} font-mono">{{ $s->code }}</td>
                            <td class="{{ $td }}">{{ $s->cities_count }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $s->is_active) }}">{{ $s->is_active ? 'active' : 'inactive' }}</span></td>
                            <td class="{{ $td }}"><button wire:click="toggleState({{ $s->id }})" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">{{ $s->is_active ? 'Deactivate' : 'Activate' }}</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 10. PRICING ================= --}}
        @if($tab === 'pricing')
            @if($this->userCan('billing.manage_pricing'))
                <div class="flex justify-end mb-3"><button wire:click="openPlanModal" class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white">+ New Plan</button></div>
            @endif
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Plan</th><th class="{{ $th }}">Type</th><th class="{{ $th }}">Price</th><th class="{{ $th }}">Duration</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($pricingPlans as $p)
                        <tr class="border-t border-slate-200" wire:key="plan-{{ $p->id }}">
                            <td class="{{ $td }}"><div class="font-semibold">{{ $p->name }}</div><div class="text-xs text-slate-500 font-mono">{{ $p->slug }}</div></td>
                            <td class="{{ $td }}">{{ str_replace('_', ' ', $p->plan_type) }}</td>
                            <td class="{{ $td }}">${{ number_format($p->price_cents / 100, 2) }}</td>
                            <td class="{{ $td }}">{{ $p->duration_days }} days</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $p->is_active) }}">{{ $p->is_active ? 'active' : 'inactive' }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($this->userCan('billing.manage_pricing'))
                                    <button wire:click="openPlanModal({{ $p->id }})" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">Edit</button>
                                    <button wire:click="togglePlan({{ $p->id }})" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">{{ $p->is_active ? 'Disable' : 'Enable' }}</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No pricing plans configured.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 11. FEATURED ================= --}}
        @if($tab === 'featured')
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Listing</th><th class="{{ $th }}">Owner</th><th class="{{ $th }}">Placement</th><th class="{{ $th }}">Window</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($featuredListings as $f)
                        <tr class="border-t border-slate-200" wire:key="feat-{{ $f->id }}">
                            <td class="{{ $td }}">{{ $f->listing?->title ?? 'Deleted listing' }}</td>
                            <td class="{{ $td }}">{{ $f->user?->name }}</td>
                            <td class="{{ $td }}">{{ str_replace('_', ' ', $f->placement) }}</td>
                            <td class="{{ $td }} text-xs">{{ $f->starts_at?->format('M j') }} → {{ $f->ends_at?->format('M j, Y') }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($f->is_active && $f->ends_at?->isFuture()) }}">{{ $f->is_active && $f->ends_at?->isFuture() ? 'active' : 'ended' }}</span></td>
                            <td class="{{ $td }}">@if($f->is_active && $this->userCan('billing.manage_pricing'))<button wire:click="deactivateFeatured({{ $f->id }})" wire:confirm="End this featured placement now?" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Deactivate</button>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No featured placements.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $featuredListings->links() }}</div>
            </div>
        @endif

        {{-- ================= 12. SUBSCRIPTIONS ================= --}}
        @if($tab === 'subscriptions')
            <div class="space-y-6">
                <!-- Top 4 KPI Metrics -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- MRR -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-emerald-600">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Monthly Recurring Revenue</span>
                            <span class="p-2 bg-emerald-50 rounded-xl text-emerald-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            ${{ number_format($subscriptionStats['mrr'] ?? 0, 2) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Active membership base</span>
                        </div>
                    </div>

                    <!-- Active Subscribers -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-pink-600">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Active Subscribers</span>
                            <span class="p-2 bg-pink-50 rounded-xl text-pink-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($subscriptionStats['active_count'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold">
                            Across {{ number_format($subscriptionStats['total_subscribers'] ?? 0) }} unique paying members
                        </div>
                    </div>

                    <!-- Expiring Soon -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-amber-500">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Expiring in 7 Days</span>
                            <span class="p-2 bg-amber-50 rounded-xl text-amber-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($subscriptionStats['expiring_soon'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold">
                            Requires auto-renewal or manual extension
                        </div>
                    </div>

                    <!-- Canceled / Churned -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-rose-500">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Canceled & Churned</span>
                            <span class="p-2 bg-rose-50 rounded-xl text-rose-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($subscriptionStats['canceled_count'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold">
                            Inactive or past due accounts
                        </div>
                    </div>
                </div>

                <!-- Sub-Navigation and Primary Actions -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="setSubscriptionSubTab('subscribers')"
                            class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center gap-2 {{ $subscriptionSubTab === 'subscribers' ? 'bg-[#F20B83] text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                            <span>Subscribers & Memberships</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $subscriptionSubTab === 'subscribers' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $subscriptionStats['active_count'] ?? 0 }}</span>
                        </button>
                        <button type="button" wire:click="setSubscriptionSubTab('plans')"
                            class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center gap-2 {{ $subscriptionSubTab === 'plans' ? 'bg-[#F20B83] text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                            <span>Subscription Plans & Tiers</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $subscriptionSubTab === 'plans' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $allPlans->count() }}</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($this->userCan('billing.manage_pricing'))
                            <button type="button" wire:click="openAssignSubscriptionModal"
                                class="px-3.5 py-2 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-xs font-black shadow-sm flex items-center gap-1.5 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                <span>+ Assign Subscription</span>
                            </button>
                            <button type="button" wire:click="openPlanModal"
                                class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black shadow-sm flex items-center gap-1.5 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                <span>+ Create Plan</span>
                            </button>
                        @endif
                    </div>
                </div>

                @if($subscriptionSubTab === 'subscribers')
                    <!-- Search & Filter Controls -->
                    <div class="{{ $card }} p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                            <div class="sm:col-span-6 relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input type="text" wire:model.live.debounce.300ms="subscriptionSearch"
                                    placeholder="Search by user name, email, subscription ID, or gateway ID..."
                                    class="{{ $input }} pl-9 font-medium" />
                            </div>

                            <div class="sm:col-span-3">
                                <select wire:model.live="subscriptionStatusFilter" class="{{ $input }} font-medium">
                                    <option value="all">All Statuses (Active, Past Due, Canceled, Expired)</option>
                                    <option value="active">Active Only</option>
                                    <option value="past_due">Past Due</option>
                                    <option value="canceled">Canceled</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>

                            <div class="sm:col-span-3">
                                <select wire:model.live="subscriptionPlanFilter" class="{{ $input }} font-medium">
                                    <option value="all">All Pricing Plans</option>
                                    @foreach($allPlans as $pl)
                                        <option value="{{ $pl->id }}">{{ $pl->name }} (${{ number_format($pl->price_cents / 100, 2) }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Realtime Subscriptions Table -->
                    <div class="{{ $card }} overflow-x-auto shadow-sm">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th class="{{ $th }}">Subscriber</th>
                                    <th class="{{ $th }}">Plan & Tier</th>
                                    <th class="{{ $th }}">Gateway</th>
                                    <th class="{{ $th }}">Period & Remaining</th>
                                    <th class="{{ $th }}">Auto-Renew</th>
                                    <th class="{{ $th }}">Status</th>
                                    <th class="{{ $th }} text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subscriptions as $s)
                                    <tr class="border-t border-slate-100 hover:bg-slate-50/70 transition" wire:key="sub-{{ $s->id }}">
                                        <!-- User Column -->
                                        <td class="{{ $td }}">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-pink-100 text-[#F20B83] font-black text-xs flex items-center justify-center shrink-0 border border-pink-200">
                                                    {{ strtoupper(substr($s->user?->name ?? 'U', 0, 1)) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="font-black text-slate-950 text-xs truncate">
                                                        {{ $s->user?->name ?? 'User #' . $s->user_id }}
                                                    </div>
                                                    <div class="text-[11px] text-slate-500 font-medium truncate">
                                                        {{ $s->user?->email ?? 'No email' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Plan Column -->
                                        <td class="{{ $td }}">
                                            <div class="font-bold text-slate-900 text-xs">
                                                {{ $s->plan?->name ?? 'Custom Plan' }}
                                            </div>
                                            <div class="text-[11px] text-emerald-700 font-semibold font-mono">
                                                ${{ number_format(($s->plan?->price_cents ?? 0) / 100, 2) }}
                                                <span class="text-slate-400">/ {{ $s->plan?->duration_days ?? 30 }}d</span>
                                            </div>
                                        </td>

                                        <!-- Gateway Column -->
                                        <td class="{{ $td }}">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold border
                                                {{ str_contains($s->gateway, 'crypto') ? 'bg-purple-50 text-purple-700 border-purple-200' : (str_contains($s->gateway, 'stripe') ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-slate-100 text-slate-700 border-slate-200') }}">
                                                @if(str_contains($s->gateway, 'crypto'))
                                                    <span>⚡</span>
                                                @endif
                                                <span>{{ strtoupper(str_replace('_', ' ', $s->gateway)) }}</span>
                                            </span>
                                            @if($s->gateway_subscription_id)
                                                <div class="text-[10px] font-mono text-slate-400 truncate max-w-[120px] mt-0.5">
                                                    {{ $s->gateway_subscription_id }}
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Period & Remaining Column -->
                                        <td class="{{ $td }}">
                                            <div class="text-xs font-semibold text-slate-800">
                                                {{ $s->starts_at?->format('M j, Y') ?? 'N/A' }} → {{ $s->ends_at?->format('M j, Y') ?? 'Open' }}
                                            </div>
                                            <div class="mt-0.5">
                                                @if($s->ends_at && $s->ends_at->isPast())
                                                    <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">
                                                        Expired {{ $s->ends_at->diffForHumans() }}
                                                    </span>
                                                @elseif($s->ends_at && $s->status === 'active')
                                                    @php
                                                        $daysLeft = (int) now()->diffInDays($s->ends_at, false);
                                                    @endphp
                                                    <span class="text-[10px] font-bold {{ $daysLeft <= 7 ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-emerald-700 bg-emerald-50 border-emerald-200' }} px-1.5 py-0.5 rounded border">
                                                        {{ $daysLeft }} days remaining
                                                    </span>
                                                @else
                                                    <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">
                                                        Lifetime / Open
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Auto-Renew Column -->
                                        <td class="{{ $td }}">
                                            @if($this->userCan('billing.manage_pricing'))
                                                <button type="button" wire:click="toggleAutoRenew({{ $s->id }})"
                                                    title="Click to toggle auto-renewal"
                                                    class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-xs font-bold transition border
                                                    {{ $s->auto_renew ? 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200' }}">
                                                    <span class="w-2 h-2 rounded-full {{ $s->auto_renew ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                    <span>{{ $s->auto_renew ? 'ON' : 'OFF' }}</span>
                                                </button>
                                            @else
                                                <span class="text-xs font-bold {{ $s->auto_renew ? 'text-emerald-700' : 'text-slate-400' }}">
                                                    {{ $s->auto_renew ? 'ON' : 'OFF' }}
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Status Column -->
                                        <td class="{{ $td }}">
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $badge($s->status) }}">
                                                {{ strtoupper($s->status) }}
                                            </span>
                                        </td>

                                        <!-- Actions Column -->
                                        <td class="{{ $td }} text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button" wire:click="openViewSubscriptionModal({{ $s->id }})"
                                                    class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200" title="Inspect details">
                                                    View
                                                </button>
                                                @if($this->userCan('billing.manage_pricing'))
                                                    <button type="button" wire:click="extendSubscription({{ $s->id }}, 30)"
                                                        class="{{ $btn }} bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200" title="Add 30 days">
                                                        +30d
                                                    </button>
                                                    @if($s->status === 'active')
                                                        <button type="button" wire:click="cancelSubscription({{ $s->id }})"
                                                            wire:confirm="Cancel this subscription immediately?"
                                                            class="{{ $btn }} bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200" title="Cancel">
                                                            Cancel
                                                        </button>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="{{ $td }} text-center py-12 text-slate-500">
                                            <div class="max-w-xs mx-auto text-center space-y-2">
                                                <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xl">
                                                    📋
                                                </div>
                                                <p class="font-bold text-slate-900">No subscriptions found</p>
                                                <p class="text-xs text-slate-500">Try adjusting your search criteria or assign a subscription to a member.</p>
                                                @if($this->userCan('billing.manage_pricing'))
                                                    <button type="button" wire:click="openAssignSubscriptionModal" class="px-3 py-1.5 bg-[#F20B83] text-white rounded-xl text-xs font-bold shadow">
                                                        + Assign Subscription Now
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="p-3 border-t border-slate-100 bg-slate-50/50">{{ $subscriptions->links() }}</div>
                    </div>
                @else
                    <!-- Subscription Plans & Tiers Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        @forelse($allPlans as $plan)
                            <div class="{{ $card }} p-5 flex flex-col justify-between border-2 {{ $plan->is_active ? 'border-slate-200 hover:border-pink-300' : 'border-slate-200 bg-slate-50/80 opacity-75' }} transition">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider {{ $plan->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-200 text-slate-600' }}">
                                            {{ $plan->is_active ? 'Active Tier' : 'Disabled' }}
                                        </span>
                                        <span class="text-xs font-mono font-bold text-slate-500 uppercase">{{ $plan->plan_type }}</span>
                                    </div>
                                    <h3 class="text-lg font-black text-slate-950">{{ $plan->name }}</h3>
                                    <p class="text-xs text-slate-600 mt-1 line-clamp-2">{{ $plan->description ?: 'Standard membership tier' }}</p>

                                    <div class="mt-4 flex items-baseline gap-1">
                                        <span class="text-3xl font-black text-slate-950 font-mono">${{ number_format($plan->price_cents / 100, 2) }}</span>
                                        <span class="text-xs text-slate-500 font-bold">/ {{ $plan->duration_days }} days</span>
                                    </div>

                                    @if(!empty($plan->features))
                                        <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5">
                                            <div class="text-[10px] font-black uppercase tracking-wider text-slate-500">Included Features</div>
                                            @foreach((array) $plan->features as $feature)
                                                <div class="flex items-center gap-1.5 text-xs text-slate-700 font-medium">
                                                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                    <span class="truncate">{{ $feature }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
                                    <div class="text-[11px] font-bold text-slate-600">
                                        <span class="text-pink-600 font-black">{{ $plan->subscriptions()->where('status', 'active')->count() }}</span> Active Members
                                    </div>

                                    @if($this->userCan('billing.manage_pricing'))
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" wire:click="togglePlan({{ $plan->id }})"
                                                class="{{ $btn }} {{ $plan->is_active ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800' }}">
                                                {{ $plan->is_active ? 'Disable' : 'Enable' }}
                                            </button>
                                            <button type="button" wire:click="openPlanModal({{ $plan->id }})"
                                                class="{{ $btn }} bg-[#F20B83] hover:bg-pink-600 text-white shadow-xs">
                                                Edit Plan
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full {{ $card }} p-8 text-center text-slate-500">
                                No pricing plans configured yet.
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
        @endif

        {{-- ================= 13. PAYMENTS & REFUNDS ================= --}}
        @if($tab === 'payments')
            <!-- Platform Financial KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Card 1: Gross Platform Revenue -->
                <div class="{{ $card }} p-5 border-l-4 border-l-emerald-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Gross Platform Revenue</span>
                        <span class="p-2 bg-emerald-50 rounded-xl text-emerald-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                        ${{ number_format($financialStats['gross_revenue'], 2) }}
                    </div>
                    <div class="mt-1 text-xs text-slate-700 font-semibold flex items-center gap-1.5">
                        <span class="text-emerald-800 font-bold">${{ number_format($financialStats['fiat_revenue'], 2) }} fiat</span>
                        <span>•</span>
                        <span class="text-amber-800 font-bold">${{ number_format($financialStats['crypto_volume'], 2) }} crypto</span>
                    </div>
                </div>

                <!-- Card 2: Cryptocurrency Invoices -->
                <div class="{{ $card }} p-5 border-l-4 border-l-amber-600">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Crypto Invoices</span>
                        <span class="p-2 bg-amber-50 rounded-xl text-amber-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                        ${{ number_format($financialStats['crypto_volume'], 2) }}
                    </div>
                    <div class="mt-1 text-xs text-slate-700 font-semibold">
                        {{ $financialStats['crypto_orders_count'] }} Web3 blockchain orders (BTC/USDT/ETH/SOL)
                    </div>
                </div>

                <!-- Card 3: Circulating Wallet Credits -->
                <div class="{{ $card }} p-5 border-l-4 border-l-rose-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Circulating Credits</span>
                        <span class="p-2 bg-rose-50 rounded-xl text-rose-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                        {{ number_format($financialStats['wallet_credits_circulation']) }}
                        <span class="text-sm font-semibold text-slate-700">credits</span>
                    </div>
                    <div class="mt-1 text-xs text-slate-700 font-semibold">
                        Across {{ $financialStats['wallet_txns_count'] }} ledger operations
                    </div>
                </div>

                <!-- Card 4: Refunds & Risk Alerts -->
                <div class="{{ $card }} p-5 border-l-4 border-l-purple-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Disputes & Refunds</span>
                        <span class="p-2 bg-purple-50 rounded-xl text-purple-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                        ${{ number_format($financialStats['total_refunded'], 2) }}
                    </div>
                    <div class="mt-1 text-xs text-slate-700 font-semibold">
                        {{ $financialStats['refunds_count'] }} refunds issued • {{ $financialStats['fraud_alerts_count'] }} risk flags
                    </div>
                </div>
            </div>

            <!-- Segmented Sub-Tabs Navigation -->
            <div class="flex items-center space-x-1 border-b border-slate-200 overflow-x-auto pb-2 mb-4 text-sm font-semibold">
                <button wire:click="setPaymentSubTab('transactions')" class="px-3.5 py-2 rounded-xl transition flex items-center space-x-2 whitespace-nowrap {{ $paymentSubTab === 'transactions' ? 'bg-rose-600 text-white shadow' : 'text-slate-800 hover:bg-slate-100' }}">
                    <span>All Gateway Transactions</span>
                    <span class="px-1.5 py-0.5 rounded-full text-xs font-mono {{ $paymentSubTab === 'transactions' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-900 font-bold' }}">{{ $transactions->total() }}</span>
                </button>
                <button wire:click="setPaymentSubTab('crypto')" class="px-3.5 py-2 rounded-xl transition flex items-center space-x-2 whitespace-nowrap {{ $paymentSubTab === 'crypto' ? 'bg-amber-600 text-white shadow' : 'text-slate-800 hover:bg-slate-100' }}">
                    <span>Crypto & Web3 Orders</span>
                    <span class="px-1.5 py-0.5 rounded-full text-xs font-mono {{ $paymentSubTab === 'crypto' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-900 font-bold' }}">{{ $cryptoPayments->total() }}</span>
                </button>
                <button wire:click="setPaymentSubTab('treasury')" class="px-3.5 py-2 rounded-xl transition flex items-center space-x-2 whitespace-nowrap {{ $paymentSubTab === 'treasury' ? 'bg-emerald-600 text-white shadow' : 'text-slate-800 hover:bg-slate-100' }}">
                    <span>Hybrid Treasury</span>
                    <span class="px-1.5 py-0.5 rounded-full text-xs font-mono {{ $paymentSubTab === 'treasury' ? 'bg-white/20 text-white' : ($pendingApprovals->count() > 0 ? 'bg-amber-200 text-amber-950 font-black' : 'bg-slate-200 text-slate-900 font-bold') }}">
                        {{ $pendingApprovals->count() > 0 ? $pendingApprovals->count() . ' Pending' : 'Live' }}
                    </span>
                </button>
                <button wire:click="setPaymentSubTab('wallet')" class="px-3.5 py-2 rounded-xl transition flex items-center space-x-2 whitespace-nowrap {{ $paymentSubTab === 'wallet' ? 'bg-indigo-600 text-white shadow' : 'text-slate-800 hover:bg-slate-100' }}">
                    <span>Credit Wallet Ledger</span>
                    <span class="px-1.5 py-0.5 rounded-full text-xs font-mono {{ $paymentSubTab === 'wallet' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-900 font-bold' }}">{{ $walletTransactions->total() }}</span>
                </button>
                <button wire:click="setPaymentSubTab('packages')" class="px-3.5 py-2 rounded-xl transition flex items-center space-x-2 whitespace-nowrap {{ $paymentSubTab === 'packages' ? 'bg-[#F20B83] text-white shadow' : 'text-slate-800 hover:bg-slate-100' }}">
                    <span>💳 Credit Packages</span>
                    <span class="px-1.5 py-0.5 rounded-full text-xs font-mono {{ $paymentSubTab === 'packages' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-900 font-bold' }}">{{ $creditPackages->count() }}</span>
                </button>
                <button wire:click="setPaymentSubTab('rules')" class="px-3.5 py-2 rounded-xl transition flex items-center space-x-2 whitespace-nowrap {{ $paymentSubTab === 'rules' ? 'bg-purple-600 text-white shadow' : 'text-slate-800 hover:bg-slate-100' }}">
                    <span>Dynamic Pricing Rules</span>
                    <span class="px-1.5 py-0.5 rounded-full text-xs font-mono {{ $paymentSubTab === 'rules' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-900 font-bold' }}">{{ $pricingRules->count() }}</span>
                </button>
                <button wire:click="setPaymentSubTab('fraud')" class="px-3.5 py-2 rounded-xl transition flex items-center space-x-2 whitespace-nowrap {{ $paymentSubTab === 'fraud' ? 'bg-red-600 text-white shadow' : 'text-slate-800 hover:bg-slate-100' }}">
                    <span>Fraud & Velocity Alerts</span>
                    <span class="px-1.5 py-0.5 rounded-full text-xs font-mono {{ $paymentSubTab === 'fraud' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-900 font-bold' }}">{{ $fraudLogs->count() }}</span>
                </button>
                <button wire:click="setPaymentSubTab('refunds')" class="px-3.5 py-2 rounded-xl transition flex items-center space-x-2 whitespace-nowrap {{ $paymentSubTab === 'refunds' ? 'bg-slate-800 text-white shadow' : 'text-slate-800 hover:bg-slate-100' }}">
                    <span>Refunds Ledger</span>
                    <span class="px-1.5 py-0.5 rounded-full text-xs font-mono {{ $paymentSubTab === 'refunds' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-900 font-bold' }}">{{ $refunds->total() }}</span>
                </button>
            </div>

            <!-- Unified Search & Filter Toolbar -->
            <div class="{{ $card }} p-4 mb-5">
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                    <!-- Search bar -->
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="paymentSearch" placeholder="Search reference, order #, tx hash, user, email, reason..." class="{{ $input }} pl-9 text-xs sm:text-sm">
                    </div>

                    <!-- Filters & Actions -->
                    <div class="flex flex-wrap items-center gap-2">
                        @if($paymentSubTab === 'transactions')
                            <select wire:model.live="paymentGatewayFilter" class="{{ $input }} !w-auto text-xs sm:text-sm" aria-label="Gateway filter">
                                <option value="all">All Gateways</option>
                                @foreach($gatewaysList as $g)
                                    <option value="{{ $g }}">{{ strtoupper($g) }}</option>
                                @endforeach
                            </select>
                        @endif

                        @if(in_array($paymentSubTab, ['transactions', 'crypto'], true))
                            <select wire:model.live="paymentStatusFilter" class="{{ $input }} !w-auto text-xs sm:text-sm" aria-label="Payment status">
                                <option value="all">All Statuses</option>
                                <option value="completed">Completed / Confirmed</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed / Expired</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        @endif

                        @if($paymentSearch !== '' || $paymentGatewayFilter !== 'all' || $paymentStatusFilter !== 'all')
                            <button wire:click="resetPaymentFilters" class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs sm:text-sm" title="Reset Filters">
                                Reset
                            </button>
                        @endif

                        @if($paymentSubTab === 'packages')
                            <button wire:click="openCreatePackageModal" class="{{ $btn }} bg-[#F20B83] hover:bg-pink-600 text-white text-xs sm:text-sm font-semibold shadow-sm flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                + Create Credit Package
                            </button>
                        @endif

                        <button wire:click="openPricingRuleModal" class="{{ $btn }} bg-purple-700 hover:bg-purple-600 text-white text-xs sm:text-sm font-semibold shadow-sm">
                            + Dynamic Pricing Rule
                        </button>

                        <button wire:click="reconcileTransactions" class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-300 text-xs sm:text-sm font-semibold flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Reconcile
                            @if($unreconciledCount > 0)
                                <span class="ml-1.5 px-1.5 py-0.5 bg-amber-500/20 text-amber-800 rounded-full text-[10px] font-mono font-bold">{{ $unreconciledCount }}</span>
                            @endif
                        </button>
                    </div>
                </div>
            </div>

            {{-- SUB-TAB 1: ALL GATEWAY TRANSACTIONS --}}
            @if($paymentSubTab === 'transactions')
                <div class="{{ $card }} overflow-x-auto mb-6">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/50">
                                <th class="{{ $th }}">Reference</th>
                                <th class="{{ $th }}">Customer</th>
                                <th class="{{ $th }}">Plan / Upgrade</th>
                                <th class="{{ $th }}">Amount</th>
                                <th class="{{ $th }}">Gateway</th>
                                <th class="{{ $th }}">Status</th>
                                <th class="{{ $th }}">Date</th>
                                <th class="{{ $th }} text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                        @forelse($transactions as $t)
                            <tr class="hover:bg-slate-50/60 transition" wire:key="txn-{{ $t->id }}">
                                <td class="{{ $td }} font-mono text-xs">
                                    <div class="font-bold text-slate-900">{{ $t->reference_id }}</div>
                                    @if($t->is_reconciled)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200">
                                            ✓ reconciled
                                        </span>
                                    @endif
                                </td>
                                <td class="{{ $td }}">
                                    @if($t->user)
                                        <button wire:click="openViewUserModal({{ $t->user_id }})" class="text-left group hover:opacity-80">
                                            <div class="font-bold text-slate-900 group-hover:text-rose-600">{{ $t->user->name }}</div>
                                            <div class="text-xs text-slate-500 font-mono">{{ $t->user->email }}</div>
                                        </button>
                                    @else
                                        <span class="text-slate-400 font-italic">Guest User</span>
                                    @endif
                                </td>
                                <td class="{{ $td }} text-xs">
                                    <div class="font-bold text-slate-900">{{ $t->plan?->name ?? 'Standard Upgrade' }}</div>
                                    @if($t->listing)
                                        <button wire:click="openViewListingModal({{ $t->listing_id }})" class="text-[11px] text-rose-600 hover:underline truncate max-w-xs block text-left">
                                            #{{ $t->listing_id }} · {{ $t->listing->title }}
                                        </button>
                                    @endif
                                </td>
                                <td class="{{ $td }} font-mono font-black text-slate-950 text-sm">
                                    ${{ number_format($t->amount_cents / 100, 2) }}
                                </td>
                                <td class="{{ $td }} text-xs uppercase font-mono text-slate-600 font-semibold">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200">{{ $t->gateway }}</span>
                                </td>
                                <td class="{{ $td }}">
                                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold capitalize {{ $badge($t->status) }}">
                                        {{ $t->status }}
                                    </span>
                                </td>
                                <td class="{{ $td }} text-xs text-slate-600 whitespace-nowrap">
                                    {{ $t->created_at?->format('M j, Y H:i') }}
                                </td>
                                <td class="{{ $td }} text-right space-x-1.5 whitespace-nowrap">
                                    <button wire:click="openViewTransactionModal({{ $t->id }})" class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold border border-slate-300">
                                        View
                                    </button>
                                    @if($t->invoice)
                                        <a href="{{ route('account.invoices.show', $t->invoice) }}" target="_blank" class="{{ $btn }} bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold border border-rose-200">
                                            Invoice
                                        </a>
                                    @endif
                                    @if($t->status === 'completed' && $this->userCan('billing.refunds'))
                                        <button wire:click="openRefundModal({{ $t->id }})" class="{{ $btn }} bg-amber-700 hover:bg-amber-600 text-white text-xs font-semibold">
                                            Refund
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="{{ $td }} py-8 text-center text-slate-500 font-semibold">
                                    No transactions match your search and filter criteria.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div class="p-3 border-t border-slate-200">{{ $transactions->links() }}</div>
                </div>
            @endif

            {{-- SUB-TAB 2: CRYPTOCURRENCY & WEB3 INVOICES --}}
            @if($paymentSubTab === 'crypto')
                <div class="{{ $card }} overflow-x-auto mb-6">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/50">
                                <th class="{{ $th }}">Order ID / Provider</th>
                                <th class="{{ $th }}">Customer</th>
                                <th class="{{ $th }}">Crypto Asset</th>
                                <th class="{{ $th }}">Fiat Value</th>
                                <th class="{{ $th }}">Confirmations</th>
                                <th class="{{ $th }}">Status</th>
                                <th class="{{ $th }}">Date</th>
                                <th class="{{ $th }} text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                        @forelse($cryptoPayments as $c)
                            <tr class="hover:bg-slate-50/60 transition" wire:key="crypto-{{ $c->id }}">
                                <td class="{{ $td }} font-mono text-xs">
                                    <div class="font-bold text-slate-900">{{ $c->internal_order_id }}</div>
                                    @if($c->provider_payment_id)
                                        <span class="text-[10px] text-slate-500">{{ $c->provider_payment_id }}</span>
                                    @endif
                                </td>
                                <td class="{{ $td }}">
                                    @if($c->user)
                                        <button wire:click="openViewUserModal({{ $c->user_id }})" class="text-left group hover:opacity-80">
                                            <div class="font-bold text-slate-900 group-hover:text-rose-600">{{ $c->user->name }}</div>
                                            <div class="text-xs text-slate-500 font-mono">{{ $c->user->email }}</div>
                                        </button>
                                    @else
                                        <span class="text-slate-400">Anonymous</span>
                                    @endif
                                </td>
                                <td class="{{ $td }}">
                                    <div class="font-mono font-bold text-slate-950 text-sm">
                                        {{ $c->expected_amount }} {{ $c->payment_currency }}
                                    </div>
                                    <span class="inline-block px-1.5 py-0.2 text-[10px] font-mono rounded bg-amber-50 text-amber-800 border border-amber-200 font-semibold uppercase">
                                        {{ $c->payment_network }}
                                    </span>
                                </td>
                                <td class="{{ $td }} font-mono font-black text-slate-950 text-sm">
                                    ${{ number_format((float) ($c->fiat_amount ?? 0), 2) }}
                                </td>
                                <td class="{{ $td }} text-xs font-mono">
                                    <span class="px-2 py-0.5 rounded font-bold {{ $c->confirmations >= $c->required_confirmations ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                        {{ $c->confirmations }}/{{ $c->required_confirmations }} Confirmed
                                    </span>
                                </td>
                                <td class="{{ $td }}">
                                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold capitalize {{ $badge(strtolower($c->status)) }}">
                                        {{ $c->status }}
                                    </span>
                                </td>
                                <td class="{{ $td }} text-xs text-slate-600 whitespace-nowrap">
                                    {{ $c->created_at?->format('M j, Y H:i') }}
                                </td>
                                <td class="{{ $td }} text-right space-x-1.5 whitespace-nowrap">
                                    @if(!$c->isCompleted() && !$c->isExpired())
                                        <button wire:click="verifyCryptoPaymentOnChain({{ $c->id }})" wire:loading.attr="disabled" class="{{ $btn }} bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-semibold border border-emerald-200" title="Check blockchain node for confirmations">
                                            <span wire:loading.remove wire:target="verifyCryptoPaymentOnChain({{ $c->id }})">Verify On-Chain</span>
                                            <span wire:loading wire:target="verifyCryptoPaymentOnChain({{ $c->id }})">Checking...</span>
                                        </button>
                                    @endif
                                    <button wire:click="openViewCryptoModal({{ $c->id }})" class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold border border-slate-300">
                                        View Details
                                    </button>
                                    @if($c->getExplorerUrl())
                                        <a href="{{ $c->getExplorerUrl() }}" target="_blank" rel="noopener noreferrer" class="{{ $btn }} bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-semibold border border-amber-200" title="Inspect on blockchain explorer">
                                            Explorer ↗
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="{{ $td }} py-8 text-center text-slate-500 font-semibold">
                                    No cryptocurrency payments found.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div class="p-3 border-t border-slate-200">{{ $cryptoPayments->links() }}</div>
                </div>
            @endif

            {{-- SUB-TAB: HYBRID TREASURY & TRUST WALLET ENGINE --}}
            @if($paymentSubTab === 'treasury')
                <div class="space-y-6 mb-6">
                    <!-- Real-Time Local Feedback Alerts -->
                    @if($treasurySuccessMessage)
                        <div class="p-4 rounded-xl bg-emerald-50 border-2 border-emerald-400 text-emerald-950 font-bold text-xs flex items-center justify-between shadow-md transition-all animate-fadeIn">
                            <div class="flex items-center gap-2.5">
                                <span class="text-lg">✅</span>
                                <span class="font-bold leading-relaxed">{{ $treasurySuccessMessage }}</span>
                            </div>
                            <button type="button" wire:click="$set('treasurySuccessMessage', null)" class="text-emerald-700 hover:text-emerald-950 font-black text-base px-2 py-0.5">&times;</button>
                        </div>
                    @endif

                    @if($treasuryErrorMessage)
                        <div class="p-4 rounded-xl bg-rose-50 border-2 border-rose-400 text-rose-950 font-bold text-xs flex items-center justify-between shadow-md transition-all animate-fadeIn">
                            <div class="flex items-center gap-2.5">
                                <span class="text-lg">⚠️</span>
                                <span class="font-bold leading-relaxed">{{ $treasuryErrorMessage }}</span>
                            </div>
                            <button type="button" wire:click="$set('treasuryErrorMessage', null)" class="text-rose-700 hover:text-rose-950 font-black text-base px-2 py-0.5">&times;</button>
                        </div>
                    @endif

                    <!-- 1. Emergency Pause & Global Status Ribbon -->
                    <div class="rounded-2xl p-6 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6 border {{ $isEmergencyPaused ? 'bg-gradient-to-r from-red-950 via-rose-900 to-red-950 border-rose-500 text-white animate-pulse' : 'bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 border-slate-700 text-white' }}">
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-black {{ $isEmergencyPaused ? 'bg-red-500 text-white' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }}">
                                    {{ $isEmergencyPaused ? 'EMERGENCY PAUSE: ACTIVE' : 'TREASURY TRANSFERS: ACTIVE' }}
                                </span>
                                <span class="text-xs text-slate-300 font-mono">Hybrid Dual-Tier Trust Wallet Architecture</span>
                            </div>
                            <h3 class="text-xl font-black text-white">Hybrid Treasury Fund Management &amp; Sweeping Engine</h3>
                            <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
                                Manages collection of verified deposits into your **Temporary Trust Wallet**, automatic small-amount sweeping (&le; ${{ number_format($autoSweepThreshold, 2) }} USD), large-deposit admin approval queue (&gt; ${{ number_format($autoSweepThreshold, 2) }} USD), and manual consolidation to your **Main Trust Wallet**.
                            </p>
                            <p class="text-[11px] text-emerald-400 font-bold flex items-center gap-1.5">
                                <span>🔒</span>
                                <span>Safety Isolation: User balance credits are 100% independent of sweep transfers.</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <button 
                                wire:click="toggleTreasuryEmergencyPause" 
                                wire:confirm="Are you sure you want to change the Global Treasury Transfer state?" 
                                class="px-5 py-3 rounded-xl text-xs font-black transition flex items-center gap-2 shadow-lg {{ $isEmergencyPaused ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-500/25' : 'bg-red-600 hover:bg-red-500 text-white shadow-red-500/25' }}"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>{{ $isEmergencyPaused ? 'Resume Treasury Transfers' : 'Emergency Pause All Sweeps' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- 2. Dual-Tier Trust Wallet Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Temporary Treasury (Trust Wallet) -->
                        <div class="{{ $card }} p-6 border-l-4 border-l-amber-500">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                                <div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-50 text-amber-900 border border-amber-200">Tier 1 Collection</span>
                                    <h4 class="text-base font-black text-slate-950 mt-1">Temporary Treasury (Trust Wallet)</h4>
                                </div>
                                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center font-black">
                                    🛡️
                                </div>
                            </div>
                            <p class="text-xs text-slate-600 mt-2 font-semibold">
                                Temporary Trust Wallet receiving address used for initial automatic &amp; approved sweeps from user deposit addresses.
                            </p>
                            <div class="mt-4 space-y-2">
                                @forelse($treasuryWallets->where('tier', 'temporary_trust_wallet') as $tw)
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3 text-xs">
                                        <div>
                                            <span class="font-black text-slate-950 font-mono">{{ $tw->network_key }}</span>
                                            <p class="text-[11px] font-mono text-slate-600 truncate max-w-xs mt-0.5">{{ $tw->public_receiving_address }}</p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="font-black text-emerald-800 text-xs">{{ number_format($tw->available_balance, 4) }} Avail</span>
                                            <p class="text-[10px] text-slate-500 font-bold">Total: {{ number_format($tw->total_received, 4) }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-500 italic">No Temporary Trust Wallet configured.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Main Treasury (Primary Trust Wallet) -->
                        <div class="{{ $card }} p-6 border-l-4 border-l-emerald-600">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                                <div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-50 text-emerald-900 border border-emerald-200">Tier 2 Cold Storage</span>
                                    <h4 class="text-base font-black text-slate-950 mt-1">Main Treasury (Primary Trust Wallet)</h4>
                                </div>
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-800 flex items-center justify-center font-black">
                                    🏦
                                </div>
                            </div>
                            <p class="text-xs text-slate-600 mt-2 font-semibold">
                                Primary Trust Wallet destination where collected cryptocurrency is consolidated securely.
                            </p>
                            <div class="mt-4 space-y-2">
                                @forelse($treasuryWallets->where('tier', 'main_trust_wallet') as $mw)
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3 text-xs">
                                        <div>
                                            <span class="font-black text-slate-950 font-mono">{{ $mw->network_key }}</span>
                                            <p class="text-[11px] font-mono text-slate-600 truncate max-w-xs mt-0.5">{{ $mw->public_receiving_address }}</p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="font-black text-emerald-800 text-xs">{{ number_format($mw->available_balance, 4) }} Consolidated</span>
                                            <p class="text-[10px] text-slate-500 font-bold">Total: {{ number_format($mw->total_received, 4) }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-500 italic">No Main Trust Wallet configured.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- 3. Large Transfer Admin Approval Queue -->
                    <div class="{{ $card }} overflow-x-auto shadow-md">
                        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                            <div>
                                <h4 class="text-base font-black text-slate-950 flex items-center gap-2">
                                    <span>Large Deposit Sweep Approval Queue</span>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-black bg-purple-50 text-purple-950 border border-purple-200">
                                        Threshold: &gt; ${{ number_format($autoSweepThreshold, 2) }} USD
                                    </span>
                                </h4>
                                <p class="text-xs font-semibold text-slate-600 mt-0.5">
                                    Transfers exceeding the threshold require authorized administrator verification before moving to Trust Wallet.
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-xl text-xs font-black {{ $pendingApprovals->count() > 0 ? 'bg-amber-100 text-amber-950 border border-amber-300' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
                                {{ $pendingApprovals->count() }} Awaiting Approval
                            </span>
                        </div>

                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="{{ $th }}">Request / Invoice</th>
                                    <th class="{{ $th }}">Customer</th>
                                    <th class="{{ $th }}">Amount (USD &amp; Crypto)</th>
                                    <th class="{{ $th }}">Network &amp; Destination</th>
                                    <th class="{{ $th }}">Est. Network Fee</th>
                                    <th class="{{ $th }} text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($pendingApprovals as $appr)
                                <tr class="hover:bg-slate-50 transition" wire:key="appr-{{ $appr->id }}">
                                    <td class="{{ $td }}">
                                        <span class="font-black font-mono text-xs text-slate-950">#{{ $appr->id }}</span>
                                        <p class="text-xs font-mono text-slate-600">{{ $appr->sweepTransaction?->invoice?->invoice_number ?? 'Direct Deposit' }}</p>
                                    </td>
                                    <td class="{{ $td }}">
                                        <span class="font-black text-slate-950 text-xs">{{ $appr->sweepTransaction?->invoice?->user?->name ?? 'User #' . $appr->sweepTransaction?->invoice?->user_id }}</span>
                                        <p class="text-[11px] text-slate-500">{{ $appr->sweepTransaction?->invoice?->user?->email }}</p>
                                    </td>
                                    <td class="{{ $td }}">
                                        <span class="font-black text-slate-950 text-sm">${{ number_format($appr->requested_amount_usd, 2) }}</span>
                                        <p class="text-xs font-bold text-emerald-800">{{ $appr->requested_crypto_amount }} {{ $appr->sweepTransaction?->currency_symbol }}</p>
                                    </td>
                                    <td class="{{ $td }}">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-slate-100 text-slate-800 border border-slate-300">
                                            {{ $appr->network_key }}
                                        </span>
                                        <p class="text-[11px] font-mono text-slate-600 truncate max-w-xs mt-1">{{ $appr->destination_address }}</p>
                                    </td>
                                    <td class="{{ $td }}">
                                        <span class="font-black text-slate-800 text-xs">${{ number_format($appr->estimated_fee_usd, 2) }} USD</span>
                                    </td>
                                    <td class="{{ $td }} text-right whitespace-nowrap space-x-2">
                                        <button 
                                            wire:click="approveLargeSweep({{ $appr->id }})" 
                                            wire:confirm="Authorize transfer of ${{ number_format($appr->requested_amount_usd, 2) }} to your Temporary Trust Wallet?" 
                                            class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-xs transition"
                                        >
                                            Approve &amp; Sweep
                                        </button>
                                        <button 
                                            wire:click="rejectLargeSweep({{ $appr->id }})" 
                                            wire:confirm="Reject this sweep transfer? Funds will remain in deposit address." 
                                            class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-900 border border-rose-300 text-xs font-black transition"
                                        >
                                            Reject
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="{{ $td }} text-center py-8 text-slate-500 font-semibold">
                                        No large sweep transfers currently awaiting admin authorization. All small deposits below ${{ number_format($autoSweepThreshold, 2) }} are swept automatically.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 4. Phase 14.5: Gas Reserves & Sweep Cost Live Monitor -->
                    <div class="{{ $card }} p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                            <div>
                                <h4 class="text-base font-black text-slate-950 flex items-center gap-2">
                                    <span>Gas Reserve Management &amp; Sweep Cost Engine</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-50 text-emerald-900 border border-emerald-300">Phase 14.5 Active</span>
                                </h4>
                                <p class="text-xs font-semibold text-slate-600 mt-0.5">
                                    Monitors network-specific native gas reserves (TRX, ETH, BNB, BTC) and prevents sweeps when gas is low or unprofitable.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            @foreach($gasReserves as $gr)
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col justify-between space-y-3">
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="font-black text-xs text-slate-950 font-mono">{{ $gr->network_key }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase {{ $gr->current_balance <= $gr->replenishment_threshold ? 'bg-amber-100 text-amber-950 border border-amber-300' : 'bg-emerald-50 text-emerald-900 border border-emerald-300' }}">
                                                {{ $gr->current_balance <= $gr->replenishment_threshold ? 'Low Gas' : 'Optimal' }}
                                            </span>
                                        </div>
                                        <h5 class="text-xl font-black text-slate-950 mt-1">{{ number_format($gr->current_balance, 4) }} {{ $gr->gas_currency }}</h5>
                                        <p class="text-[11px] font-semibold text-slate-500 mt-0.5">Emergency Limit: {{ number_format($gr->emergency_reserve_threshold, 4) }} {{ $gr->gas_currency }}</p>
                                    </div>

                                    <div class="pt-2 border-t border-slate-200 flex items-center justify-between text-xs">
                                        <span class="text-[11px] text-slate-500 font-bold">Spent: {{ number_format($gr->spent_today, 4) }}</span>
                                        <button 
                                            wire:click="replenishGasReserve('{{ $gr->network_key }}', 50)" 
                                            class="px-2.5 py-1 rounded-lg bg-white hover:bg-slate-100 text-slate-800 text-[11px] font-black border border-slate-300 transition shadow-2xs"
                                        >
                                            + Replenish
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- 5. Manual Treasury Consolidation Form & Settings -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Manual Consolidation -->
                        <div class="{{ $card }} p-6 space-y-4">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                <div>
                                    <h4 class="text-base font-black text-slate-950">Manual Treasury Consolidation</h4>
                                    <p class="text-xs font-semibold text-slate-600">
                                        Consolidate collected funds from your **Temporary Trust Wallet** into your primary **Main Trust Wallet**.
                                    </p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-purple-50 text-purple-950 border border-purple-200">
                                    Instant Real-Time
                                </span>
                            </div>

                            <!-- Local Consolidation Alerts -->
                            @if($consolidationSuccessMessage)
                                <div class="p-3.5 rounded-xl bg-emerald-50 border-2 border-emerald-400 text-emerald-950 font-bold text-xs flex items-center justify-between shadow-xs animate-fadeIn">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">✅</span>
                                        <span class="leading-relaxed">{{ $consolidationSuccessMessage }}</span>
                                    </div>
                                    <button type="button" wire:click="$set('consolidationSuccessMessage', null)" class="text-emerald-800 font-black text-sm px-1.5">&times;</button>
                                </div>
                            @endif

                            @if($consolidationErrorMessage)
                                <div class="p-3.5 rounded-xl bg-rose-50 border-2 border-rose-400 text-rose-950 font-bold text-xs flex items-center justify-between shadow-xs animate-fadeIn">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">⚠️</span>
                                        <span class="leading-relaxed">{{ $consolidationErrorMessage }}</span>
                                    </div>
                                    <button type="button" wire:click="$set('consolidationErrorMessage', null)" class="text-rose-800 font-black text-sm px-1.5">&times;</button>
                                </div>
                            @endif

                            @php
                                $currentTempWallet = $treasuryWallets->where('tier', 'temporary_trust_wallet')->firstWhere('network_key', $manualSweepNetwork);
                                $tempAvailable = $currentTempWallet ? (float) $currentTempWallet->available_balance : 0.0;
                            @endphp

                            <!-- Live Temp Treasury Balance & Quick Simulation -->
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Available in Temporary Trust Wallet:</span>
                                    <div class="font-black text-lg text-slate-950 flex items-center gap-2 mt-0.5">
                                        <span>{{ number_format($tempAvailable, 4) }}</span>
                                        <span class="text-xs font-black text-purple-700 font-mono">{{ $manualSweepCurrency }} ({{ $manualSweepNetwork }})</span>
                                    </div>
                                </div>
                                <button 
                                    type="button" 
                                    wire:click="simulateTestDeposit" 
                                    wire:loading.attr="disabled"
                                    class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-black text-xs transition shadow-2xs flex items-center gap-1.5 shrink-0"
                                    title="Add $100 test balance to Temporary Trust Wallet so you can test consolidation transfer immediately"
                                >
                                    <span>⚡</span>
                                    <span wire:loading.remove wire:target="simulateTestDeposit">+ Load $100 Test Balance</span>
                                    <span wire:loading wire:target="simulateTestDeposit">Loading...</span>
                                </button>
                            </div>

                            <form wire:submit="executeManualConsolidation" class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-black text-slate-800 uppercase mb-1">Network</label>
                                        <select wire:model.live="manualSweepNetwork" class="{{ $input }} text-xs font-bold">
                                            <option value="TRC20">TRC20 (TRON)</option>
                                            <option value="ERC20">ERC20 (Ethereum)</option>
                                            <option value="BEP20">BEP20 (BNB Chain)</option>
                                            <option value="BTC">BTC (Bitcoin)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-black text-slate-800 uppercase mb-1">Currency</label>
                                        <select wire:model.live="manualSweepCurrency" class="{{ $input }} text-xs font-bold">
                                            <option value="USDT">USDT</option>
                                            <option value="USDC">USDC</option>
                                            <option value="BTC">BTC</option>
                                            <option value="ETH">ETH</option>
                                            <option value="BNB">BNB</option>
                                            <option value="TRX">TRX</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-black text-slate-800 uppercase">Amount to Transfer</label>
                                        <div class="flex items-center gap-1">
                                            <button type="button" wire:click="setSweepAmountPercent(25)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300">25%</button>
                                            <button type="button" wire:click="setSweepAmountPercent(50)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300">50%</button>
                                            <button type="button" wire:click="setSweepAmountPercent(75)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300">75%</button>
                                            <button type="button" wire:click="setSweepAmountPercent(100)" class="px-2 py-0.5 rounded text-[10px] font-black bg-purple-100 hover:bg-purple-200 text-purple-900 border border-purple-300">Max All</button>
                                        </div>
                                    </div>
                                    <input type="number" step="any" wire:model.live="manualSweepAmount" class="{{ $input }} text-xs font-bold" placeholder="e.g. 50.00">
                                    @error('manualSweepAmount') <span class="text-rose-600 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-black text-slate-800 uppercase mb-1">Registered Main Trust Wallet Address</label>
                                    <input type="text" wire:model.live="manualSweepDestination" class="{{ $input }} text-xs font-bold font-mono" placeholder="Must match configured Main Trust Wallet address">
                                    <p class="text-[11px] text-slate-500 font-semibold mt-1">Auto-synced with Main Trust Wallet. Must match your Main Trust Wallet address.</p>
                                    @error('manualSweepDestination') <span class="text-rose-600 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div class="pt-2 flex justify-end">
                                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-50 text-white text-xs font-black transition shadow-xs flex items-center gap-2">
                                        <span wire:loading.remove wire:target="executeManualConsolidation">Consolidate to Main Trust Wallet</span>
                                        <span wire:loading wire:target="executeManualConsolidation" class="flex items-center gap-2">
                                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                            <span>Consolidating Funds...</span>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Hybrid Settings Configuration Form -->
                        <div class="{{ $card }} p-6 space-y-4">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                                <div>
                                    <h4 class="text-base font-black text-slate-950">Treasury Thresholds &amp; Trust Wallet Config</h4>
                                    <p class="text-xs font-semibold text-slate-600">
                                        Configure auto-sweep limits and public receiving addresses. Never enter private keys.
                                    </p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-50 text-emerald-950 border border-emerald-200">
                                    Auto-Sync
                                </span>
                            </div>

                            <!-- Local Settings Alert -->
                            @if($settingsSuccessMessage)
                                <div class="p-3.5 rounded-xl bg-emerald-50 border-2 border-emerald-400 text-emerald-950 font-bold text-xs flex items-center justify-between shadow-xs animate-fadeIn">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">✅</span>
                                        <span class="leading-relaxed">{{ $settingsSuccessMessage }}</span>
                                    </div>
                                    <button type="button" wire:click="$set('settingsSuccessMessage', null)" class="text-emerald-800 font-black text-sm px-1.5">&times;</button>
                                </div>
                            @endif

                            <form wire:submit="saveTreasurySettings" class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-black text-slate-800 uppercase mb-1">Auto-Sweep Threshold ($)</label>
                                        <input type="number" step="1" wire:model="treasurySettings.auto_sweep_threshold_usd" class="{{ $input }} text-xs font-bold">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-black text-slate-800 uppercase mb-1">Large Approval Threshold ($)</label>
                                        <input type="number" step="1" wire:model="treasurySettings.large_transfer_approval_threshold_usd" class="{{ $input }} text-xs font-bold">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-black text-slate-800 uppercase mb-1">Temporary Trust Wallet (TRC20 TRON Address)</label>
                                    <input type="text" wire:model="treasurySettings.temp_trust_wallet_trc20" class="{{ $input }} text-xs font-bold font-mono">
                                </div>

                                <div>
                                    <label class="block text-xs font-black text-slate-800 uppercase mb-1">Main Trust Wallet (TRC20 TRON Address)</label>
                                    <input type="text" wire:model="treasurySettings.main_trust_wallet_trc20" class="{{ $input }} text-xs font-bold font-mono">
                                </div>

                                <div class="pt-2 flex justify-end">
                                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-black transition shadow-xs flex items-center gap-2">
                                        <span wire:loading.remove wire:target="saveTreasurySettings">Save Treasury Settings</span>
                                        <span wire:loading wire:target="saveTreasurySettings" class="flex items-center gap-2">
                                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                            <span>Saving Real-time...</span>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- 6. Recent Sweep Transactions Audit Table -->
                    <div class="{{ $card }} overflow-x-auto shadow-md">
                        <div class="p-5 border-b border-slate-200">
                            <h4 class="text-base font-black text-slate-950">Recent Treasury Sweep Transfers</h4>
                            <p class="text-xs font-semibold text-slate-600 mt-0.5">Audit trail of all executed and pending treasury sweeps.</p>
                        </div>

                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="{{ $th }}">Sweep ID</th>
                                    <th class="{{ $th }}">Type</th>
                                    <th class="{{ $th }}">Amount (USD &amp; Crypto)</th>
                                    <th class="{{ $th }}">Route</th>
                                    <th class="{{ $th }}">Gas Fee</th>
                                    <th class="{{ $th }}">Status</th>
                                    <th class="{{ $th }} text-right">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($sweepTransactions as $stx)
                                <tr class="hover:bg-slate-50 transition" wire:key="stx-{{ $stx->id }}">
                                    <td class="{{ $td }} font-mono font-bold text-xs text-slate-950">#{{ $stx->id }}</td>
                                    <td class="{{ $td }}">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase {{ $stx->sweep_type === 'automatic_small' ? 'bg-blue-50 text-blue-900 border border-blue-200' : ($stx->sweep_type === 'admin_approved_large' ? 'bg-purple-50 text-purple-950 border border-purple-200' : 'bg-slate-100 text-slate-800 border border-slate-300') }}">
                                            {{ str_replace('_', ' ', $stx->sweep_type) }}
                                        </span>
                                    </td>
                                    <td class="{{ $td }}">
                                        <span class="font-black text-slate-950 text-xs">${{ number_format($stx->amount_usd, 2) }}</span>
                                        <p class="text-[11px] font-bold text-emerald-800">{{ $stx->amount }} {{ $stx->currency_symbol }}</p>
                                    </td>
                                    <td class="{{ $td }}">
                                        <span class="text-[10px] font-mono text-slate-500">{{ Str::limit($stx->source_address, 12) }} &rarr; {{ Str::limit($stx->destination_address, 12) }}</span>
                                        <p class="text-[10px] font-bold text-slate-700 capitalize">{{ str_replace('_', ' ', $stx->destination_treasury_tier) }}</p>
                                    </td>
                                    <td class="{{ $td }}">
                                        <span class="text-xs font-mono font-bold text-slate-800">{{ $stx->estimated_gas_cost }} {{ $stx->gas_currency }}</span>
                                    </td>
                                    <td class="{{ $td }}">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-black {{ $stx->status === 'CONFIRMED' ? 'bg-emerald-50 text-emerald-900 border border-emerald-300' : ($stx->status === 'AWAITING_APPROVAL' ? 'bg-amber-100 text-amber-950 border border-amber-300' : 'bg-slate-100 text-slate-800 border border-slate-300') }}">
                                            {{ $stx->status }}
                                        </span>
                                    </td>
                                    <td class="{{ $td }} text-right text-xs font-bold text-slate-600">
                                        {{ $stx->created_at?->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="{{ $td }} text-center py-8 text-slate-500 font-semibold">
                                        No treasury sweep transactions recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- SUB-TAB 3: CREDIT WALLET PURCHASES & LEDGER --}}
            @if($paymentSubTab === 'wallet')
                <div class="{{ $card }} overflow-x-auto mb-6">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/50">
                                <th class="{{ $th }}">Reference</th>
                                <th class="{{ $th }}">Customer</th>
                                <th class="{{ $th }}">Transaction Type</th>
                                <th class="{{ $th }}">Credits Delta</th>
                                <th class="{{ $th }}">Balance Flow</th>
                                <th class="{{ $th }}">Description</th>
                                <th class="{{ $th }}">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                        @forelse($walletTransactions as $w)
                            <tr class="hover:bg-slate-50/60 transition" wire:key="wallet-{{ $w->id }}">
                                <td class="{{ $td }} font-mono text-xs font-bold text-slate-900">
                                    {{ $w->reference }}
                                </td>
                                <td class="{{ $td }}">
                                    @if($w->user)
                                        <button wire:click="openViewUserModal({{ $w->user_id }})" class="text-left group hover:opacity-80">
                                            <div class="font-bold text-slate-900 group-hover:text-rose-600">{{ $w->user->name }}</div>
                                            <div class="text-xs text-slate-500 font-mono">{{ $w->user->email }}</div>
                                        </button>
                                    @else
                                        <span class="text-slate-400">System</span>
                                    @endif
                                </td>
                                <td class="{{ $td }}">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase
                                        @if(str_contains($w->type, 'CREDIT') || str_contains($w->type, 'PURCHASE')) bg-emerald-50 text-emerald-800 border border-emerald-200
                                        @elseif(str_contains($w->type, 'DEBIT')) bg-rose-50 text-rose-800 border border-rose-200
                                        @else bg-slate-100 text-slate-800 border border-slate-200 @endif
                                    ">
                                        {{ str_replace('_', ' ', $w->type) }}
                                    </span>
                                </td>
                                <td class="{{ $td }} font-mono font-black text-sm {{ $w->amount > 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                    {{ $w->amount > 0 ? '+' : '' }}{{ number_format($w->amount) }} credits
                                </td>
                                <td class="{{ $td }} font-mono text-xs text-slate-700 font-semibold">
                                    {{ number_format($w->balance_before) }} → <span class="text-slate-950 font-bold">{{ number_format($w->balance_after) }}</span>
                                </td>
                                <td class="{{ $td }} text-xs text-slate-700 max-w-sm truncate font-medium">
                                    {{ $w->description ?? 'Direct ledger entry' }}
                                    @if($w->listing)
                                        <button wire:click="openViewListingModal({{ $w->listing_id }})" class="text-rose-600 hover:underline block font-semibold">
                                            Listing: {{ $w->listing->title }}
                                        </button>
                                    @endif
                                </td>
                                <td class="{{ $td }} text-xs text-slate-600 whitespace-nowrap">
                                    {{ $w->created_at?->format('M j, Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="{{ $td }} py-8 text-center text-slate-500 font-semibold">
                                    No wallet ledger entries found.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div class="p-3 border-t border-slate-200">{{ $walletTransactions->links() }}</div>
                </div>
            @endif

            {{-- SUB-TAB: CREDIT PACKAGES MANAGEMENT --}}
            @if($paymentSubTab === 'packages')
                <div class="space-y-6 mb-6">
                    <!-- Header Action Card -->
                    <div class="{{ $card }} p-5">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="font-black text-slate-950 text-base sm:text-lg">Credit Packages &amp; Checkout Bundles</h2>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-pink-100 text-[#F20B83]">
                                        {{ $creditPackages->where('is_active', true)->count() }} Active / {{ $creditPackages->count() }} Total
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 font-medium mt-1">
                                    Configure credit bundles offered to users on <code>/account/wallet</code>. Admin can edit prices/credits, toggle packages ON/OFF (hidden from checkout), and create or delete bundles in real-time.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('account.wallet.index') }}" target="_blank" class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 text-xs font-semibold flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                    View Customer Checkout
                                </a>
                                <button wire:click="openCreatePackageModal" class="{{ $btn }} bg-[#F20B83] hover:bg-pink-600 text-white font-bold text-xs sm:text-sm flex items-center gap-1.5 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    + Create Credit Package
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Live Customer Visual Cards Preview -->
                    <div>
                        <div class="flex items-center justify-between mb-3 px-1">
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <span>Live Visual Preview on Checkout Page</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            </h3>
                            <span class="text-[11px] text-slate-500 font-medium">Changes reflect immediately on user wallet page</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            @forelse($creditPackages as $pkg)
                                <div class="relative bg-white rounded-3xl border-2 transition shadow-sm p-5 flex flex-col justify-between {{ $pkg->is_popular ? 'border-[#F20B83] shadow-pink-100/50' : 'border-slate-200' }} {{ !$pkg->is_active ? 'opacity-60 bg-slate-50/80 border-dashed' : '' }}" wire:key="pkg-card-{{ $pkg->id }}">
                                    <!-- Badges Ribbon -->
                                    <div class="flex items-center justify-between gap-1 mb-3">
                                        @if($pkg->is_popular)
                                            <span class="bg-[#F20B83] text-white text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full shadow-xs">
                                                MOST POPULAR
                                            </span>
                                        @else
                                            <span class="text-[10px] font-mono text-slate-400 font-bold">#{{ $pkg->display_order }}</span>
                                        @endif

                                        @if($pkg->is_active)
                                            <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-600 border border-slate-300 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Disabled
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Package Header -->
                                    <div class="mb-4">
                                        <h4 class="text-base font-black text-slate-900 tracking-tight">{{ $pkg->name }}</h4>
                                        <div class="mt-2 flex items-baseline">
                                            <span class="text-2xl font-black text-slate-900 font-mono">${{ number_format($pkg->price_usd, 2) }}</span>
                                            <span class="ml-1 text-xs font-bold text-slate-500">USD</span>
                                        </div>
                                    </div>

                                    <!-- Credits breakdown -->
                                    <div class="space-y-1.5 py-3 border-y border-slate-100 text-xs mb-4">
                                        <div class="flex justify-between text-slate-600">
                                            <span>Base Credits:</span>
                                            <span class="font-mono font-bold text-slate-900">{{ number_format($pkg->credits) }}</span>
                                        </div>
                                        <div class="flex justify-between text-slate-600">
                                            <span>Bonus Credits:</span>
                                            <span class="font-mono font-bold text-emerald-600">+{{ number_format($pkg->bonus_credits) }} Free</span>
                                        </div>
                                        <div class="flex justify-between font-black text-slate-900 pt-1 border-t border-slate-100 text-sm">
                                            <span>Total Credits:</span>
                                            <span class="font-mono text-[#F20B83]">{{ number_format($pkg->total_credits) }}</span>
                                        </div>
                                    </div>

                                    <!-- Admin Card Controls -->
                                    <div class="space-y-2 pt-1">
                                        <!-- Toggle Status Button -->
                                        <button wire:click="togglePackageActive({{ $pkg->id }})" class="w-full py-2 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 {{ $pkg->is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300' : 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs' }}">
                                            @if($pkg->is_active)
                                                <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                <span>Turn Off (Hide from Users)</span>
                                            @else
                                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                <span>Turn On (Make Live)</span>
                                            @endif
                                        </button>

                                        <!-- Edit and Delete Actions -->
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <button wire:click="openEditPackageModal({{ $pkg->id }})" class="w-full py-1.5 px-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                <span>Edit</span>
                                            </button>
                                            <button wire:click="deletePackage({{ $pkg->id }})" wire:confirm="Are you sure you want to permanently delete the package '{{ $pkg->name }}'?" class="w-full py-1.5 px-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                <span>Delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full {{ $card }} p-8 text-center text-slate-500 font-semibold">
                                    No credit packages configured yet. Click "+ Create Credit Package" to add one.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Comprehensive Data Table -->
                    <div class="{{ $card }} overflow-x-auto">
                        <div class="px-5 py-3 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                            <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider">Packages Inventory &amp; Order Priority</h3>
                            <span class="text-xs text-slate-500">Display order dictates the sequence on checkout</span>
                        </div>
                        <table class="min-w-full text-xs">
                            <thead>
                                <tr class="text-slate-700 font-bold uppercase tracking-wider text-left border-b border-slate-200 bg-slate-50/70">
                                    <th class="py-3 px-4">Order</th>
                                    <th class="py-3 px-4">Package Name</th>
                                    <th class="py-3 px-4">Base Credits</th>
                                    <th class="py-3 px-4">Bonus Credits</th>
                                    <th class="py-3 px-4">Total Credits</th>
                                    <th class="py-3 px-4">Price (USD)</th>
                                    <th class="py-3 px-4">Unit Cost</th>
                                    <th class="py-3 px-4">Checkout Visibility</th>
                                    <th class="py-3 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @forelse($creditPackages as $pkg)
                                    <tr class="hover:bg-slate-50/60 transition" wire:key="pkg-tbl-{{ $pkg->id }}">
                                        <td class="py-3 px-4 font-mono font-bold text-slate-500">
                                            #{{ $pkg->display_order }}
                                        </td>
                                        <td class="py-3 px-4 font-black text-slate-950 flex items-center gap-2">
                                            <span>{{ $pkg->name }}</span>
                                            @if($pkg->is_popular)
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-pink-100 text-[#F20B83]">
                                                    POPULAR
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 font-mono font-bold text-slate-800">
                                            {{ number_format($pkg->credits) }}
                                        </td>
                                        <td class="py-3 px-4 font-mono font-bold text-emerald-600">
                                            +{{ number_format($pkg->bonus_credits) }}
                                        </td>
                                        <td class="py-3 px-4 font-mono font-black text-[#F20B83]">
                                            {{ number_format($pkg->total_credits) }}
                                        </td>
                                        <td class="py-3 px-4 font-mono font-black text-slate-900">
                                            ${{ number_format($pkg->price_usd, 2) }}
                                        </td>
                                        <td class="py-3 px-4 font-mono text-slate-600">
                                            ${{ $pkg->total_credits > 0 ? number_format($pkg->price_usd / $pkg->total_credits, 4) : '0' }} / cr
                                        </td>
                                        <td class="py-3 px-4">
                                            <button wire:click="togglePackageActive({{ $pkg->id }})" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black transition {{ $pkg->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-700 hover:bg-slate-300' }}" title="Click to toggle active state">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $pkg->is_active ? 'bg-emerald-600' : 'bg-slate-500' }}"></span>
                                                {{ $pkg->is_active ? 'Active (Live)' : 'Disabled (Hidden)' }}
                                            </button>
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-1">
                                            <button wire:click="openEditPackageModal({{ $pkg->id }})" class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-300 text-xs font-semibold">
                                                Edit
                                            </button>
                                            <button wire:click="deletePackage({{ $pkg->id }})" wire:confirm="Are you sure you want to delete '{{ $pkg->name }}'?" class="{{ $btn }} bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="py-6 text-center text-slate-500 font-semibold">
                                            No packages available.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- SUB-TAB 4: DYNAMIC PRICING RULES --}}
            @if($paymentSubTab === 'rules')
                <div class="{{ $card }} p-5 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="font-bold text-slate-950 text-base">Dynamic Pricing Rules (Overrides & Category Promos)</h2>
                            <p class="text-xs text-slate-600 font-medium">Configure category-based discounts, city promotions, and free permitted listings overrides.</p>
                        </div>
                        <button wire:click="openPricingRuleModal" class="{{ $btn }} bg-purple-700 hover:bg-purple-600 text-white font-semibold text-xs sm:text-sm">
                            + Dynamic Pricing Rule
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead>
                                <tr class="text-slate-700 font-bold uppercase tracking-wider text-left border-b border-slate-200 bg-slate-50/50">
                                    <th class="py-3 px-3">Rule Name</th>
                                    <th class="py-3 px-3">Target Scope</th>
                                    <th class="py-3 px-3">Pricing Adjustment</th>
                                    <th class="py-3 px-3">Status</th>
                                    <th class="py-3 px-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                            @forelse($pricingRules as $rule)
                                <tr wire:key="prule-{{ $rule->id }}" class="hover:bg-slate-50/60 transition">
                                    <td class="py-3 px-3 font-bold text-slate-900 text-sm">{{ $rule->rule_name }}</td>
                                    <td class="py-3 px-3 text-slate-600 font-medium">
                                        <div class="font-semibold text-slate-800">{{ $rule->plan?->name ?? 'All Plans' }}</div>
                                        @if($rule->category) <span class="text-xs text-rose-600 font-semibold">Cat: {{ $rule->category->name }}</span> @endif
                                        @if($rule->state) <span class="text-xs text-slate-500 font-mono">· State: {{ $rule->state->code }}</span> @endif
                                    </td>
                                    <td class="py-3 px-3 font-mono">
                                        @if($rule->is_free)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300">100% FREE</span>
                                        @elseif($rule->fixed_price_cents !== null)
                                            <span class="font-bold text-slate-950 text-sm">${{ number_format($rule->fixed_price_cents / 100, 2) }} Fixed</span>
                                        @elseif($rule->discount_percentage > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-purple-100 text-purple-800 border border-purple-300">-{{ $rule->discount_percentage }}% Off</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold {{ $rule->is_active ? 'bg-emerald-50 text-emerald-800 border border-emerald-300' : 'bg-slate-200 text-slate-700' }}">
                                            {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right space-x-1">
                                        <button wire:click="openPricingRuleModal({{ $rule->id }})" class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-900 border border-slate-300 text-xs font-semibold">Edit</button>
                                        <button wire:click="deletePricingRule({{ $rule->id }})" wire:confirm="Delete this pricing rule?" class="{{ $btn }} bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-center text-slate-500 font-semibold">No dynamic pricing rules configured. Default tier pricing applies.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- SUB-TAB 5: PAYMENT FRAUD & VELOCITY MONITORING --}}
            @if($paymentSubTab === 'fraud')
                <div class="{{ $card }} p-5 mb-6">
                    <h2 class="font-bold text-slate-950 text-base mb-2">Payment Fraud & Velocity Alerts (Last 20 Events)</h2>
                    <p class="text-xs text-slate-600 mb-4 font-medium">Real-time risk scoring, rapid charge attempt velocity, proxy detections, and blocklist actions.</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead>
                                <tr class="text-slate-700 font-bold uppercase tracking-wider text-left border-b border-slate-200 bg-slate-50/50">
                                    <th class="py-3 px-3">User / IP</th>
                                    <th class="py-3 px-3">Risk Score</th>
                                    <th class="py-3 px-3">Risk Tier</th>
                                    <th class="py-3 px-3">Flags Triggered</th>
                                    <th class="py-3 px-3">Action</th>
                                    <th class="py-3 px-3">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                            @forelse($fraudLogs as $flog)
                                <tr wire:key="flog-{{ $flog->id }}" class="hover:bg-slate-50/60 transition">
                                    <td class="py-3 px-3 font-mono">
                                        @if($flog->user)
                                            <button wire:click="openViewUserModal({{ $flog->user_id }})" class="font-bold text-slate-900 hover:text-rose-600 text-left block">
                                                {{ $flog->user->name }}
                                            </button>
                                        @else
                                            <span class="font-bold text-slate-900">Guest / Anonymous</span>
                                        @endif
                                        <span class="text-[10px] text-slate-500">{{ $flog->ip_address }}</span>
                                    </td>
                                    <td class="py-3 px-3 font-mono font-black text-sm {{ $flog->risk_score >= 70 ? 'text-red-600' : ($flog->risk_score >= 40 ? 'text-amber-700' : 'text-slate-800') }}">
                                        {{ $flog->risk_score }}/100
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase
                                            @if($flog->risk_tier === 'blocked') bg-red-100 text-red-700 border border-red-300
                                            @elseif($flog->risk_tier === 'high') bg-amber-100 text-amber-800 border border-amber-300
                                            @else bg-slate-100 text-slate-700 border border-slate-200 @endif
                                        ">
                                            {{ $flog->risk_tier }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-xs text-slate-700 font-semibold">
                                        {{ !empty($flog->flags) ? implode(', ', $flog->flags) : 'Clean' }}
                                    </td>
                                    <td class="py-3 px-3 text-xs font-mono uppercase text-slate-700 font-bold">{{ $flog->action_taken }}</td>
                                    <td class="py-3 px-3 text-slate-600">{{ $flog->created_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-6 text-center text-slate-500 font-semibold">No recent fraud alerts. Risk levels are normal.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- SUB-TAB 6: REFUNDS & DISPUTES LEDGER --}}
            @if($paymentSubTab === 'refunds')
                <div class="{{ $card }} overflow-x-auto mb-6">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/50">
                                <th class="{{ $th }}">Refund ID / Gateway Ref</th>
                                <th class="{{ $th }}">Original Transaction</th>
                                <th class="{{ $th }}">Customer</th>
                                <th class="{{ $th }}">Amount</th>
                                <th class="{{ $th }}">Reason</th>
                                <th class="{{ $th }}">Processed By</th>
                                <th class="{{ $th }}">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                        @forelse($refunds as $rf)
                            <tr class="hover:bg-slate-50/60 transition" wire:key="rf-{{ $rf->id }}">
                                <td class="{{ $td }} font-mono text-xs">
                                    <div class="font-bold text-slate-900">#RF-{{ $rf->id }}</div>
                                    <span class="text-[10px] text-slate-500">{{ $rf->gateway_refund_id ?? 'Manual Entry' }}</span>
                                </td>
                                <td class="{{ $td }} font-mono text-xs">
                                    @if($rf->transaction)
                                        <button wire:click="openViewTransactionModal({{ $rf->payment_transaction_id }})" class="text-rose-600 font-bold hover:underline">
                                            {{ $rf->transaction->reference_id }}
                                        </button>
                                    @else
                                        <span class="text-slate-400">Transaction #{{ $rf->payment_transaction_id }}</span>
                                    @endif
                                </td>
                                <td class="{{ $td }}">
                                    @if($rf->transaction?->user)
                                        <button wire:click="openViewUserModal({{ $rf->transaction->user_id }})" class="font-bold text-slate-900 hover:text-rose-600 text-left">
                                            {{ $rf->transaction->user->name }}
                                        </button>
                                    @else
                                        <span class="text-slate-400">Customer</span>
                                    @endif
                                </td>
                                <td class="{{ $td }} font-mono font-black text-rose-600 text-sm">
                                    -${{ number_format($rf->amount_cents / 100, 2) }}
                                </td>
                                <td class="{{ $td }} text-xs text-slate-800 font-semibold max-w-xs truncate">
                                    {{ $rf->reason }}
                                </td>
                                <td class="{{ $td }} text-xs text-slate-600 font-medium">
                                    {{ $rf->processedBy?->name ?? 'System Admin' }}
                                </td>
                                <td class="{{ $td }} text-xs text-slate-600 whitespace-nowrap">
                                    {{ $rf->created_at?->format('M j, Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="{{ $td }} py-8 text-center text-slate-500 font-semibold">
                                    No refunds have been recorded.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div class="p-3 border-t border-slate-200">{{ $refunds->links() }}</div>
                </div>
            @endif
        @endif

        {{-- ================= 16. ADVERTISEMENTS ================= --}}
        @if($tab === 'ads')
            <!-- Advertising Command Ribbon (4 KPI Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Card 1: Total Campaigns -->
                <div class="{{ $card }} p-5 border-l-4 border-l-purple-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-slate-700">Total Campaigns</p>
                            <h3 class="text-2xl font-black text-slate-950 mt-1">{{ number_format($adStats['total_campaigns'] ?? 0) }}</h3>
                            <p class="text-xs font-semibold text-slate-600 mt-0.5">{{ $adStats['active_campaigns'] ?? 0 }} Currently Active · {{ $adStats['paused_campaigns'] ?? 0 }} Paused</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-800 border border-purple-300 flex items-center justify-center font-black text-xl">
                            📢
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs font-bold text-purple-900 bg-purple-50/70 border border-purple-200 px-2 py-1 rounded-md">
                        <span>●</span><span>Full Multi-Slot Inventory</span>
                    </div>
                </div>

                <!-- Card 2: Active Placements -->
                <div class="{{ $card }} p-5 border-l-4 border-l-emerald-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-slate-700">Active Slots</p>
                            <h3 class="text-2xl font-black text-emerald-900 mt-1">{{ number_format($adStats['active_campaigns'] ?? 0) }}</h3>
                            <p class="text-xs font-semibold text-slate-600 mt-0.5">Live Delivery Across Network</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-300 flex items-center justify-center font-black text-xl">
                            ✓
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs font-bold text-emerald-900 bg-emerald-50/70 border border-emerald-200 px-2 py-1 rounded-md">
                        <span>●</span><span>Real-Time Ad Serving Enabled</span>
                    </div>
                </div>

                <!-- Card 3: Total Impressions -->
                <div class="{{ $card }} p-5 border-l-4 border-l-blue-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-slate-700">Total Impressions</p>
                            <h3 class="text-2xl font-black text-slate-950 mt-1">{{ number_format($adStats['total_impressions'] ?? 0) }}</h3>
                            <p class="text-xs font-semibold text-slate-600 mt-0.5">Verified Ad Views</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-800 border border-blue-300 flex items-center justify-center font-black text-xl">
                            👁️
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs font-bold text-blue-900 bg-blue-50/70 border border-blue-200 px-2 py-1 rounded-md">
                        <span>●</span><span>High-Impact Placements</span>
                    </div>
                </div>

                <!-- Card 4: Global Clicks & CTR -->
                <div class="{{ $card }} p-5 border-l-4 border-l-pink-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-slate-700">Avg. CTR & Revenue</p>
                            <h3 class="text-2xl font-black text-pink-900 mt-1">{{ $adStats['global_ctr'] ?? 0 }}%</h3>
                            <p class="text-xs font-semibold text-slate-600 mt-0.5">${{ number_format($adStats['total_revenue_usd'] ?? 0, 2) }} Pipeline Value</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-pink-50 text-pink-800 border border-pink-300 flex items-center justify-center font-black text-xl">
                            🎯
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs font-bold text-pink-900 bg-pink-50/70 border border-pink-200 px-2 py-1 rounded-md">
                        <span>●</span><span>{{ number_format($adStats['total_clicks'] ?? 0) }} Total Clicks Generated</span>
                    </div>
                </div>
            </div>

            <!-- Ad Monetization Sub-Navigation -->
            <div class="flex items-center gap-2 mb-5 border-b border-slate-200 pb-3">
                <button type="button" wire:click="setAdSubTab('campaigns')"
                    class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center gap-2 {{ $adSubTab === 'campaigns' ? 'bg-[#F20B83] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    <span>Active Campaigns</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $adSubTab === 'campaigns' ? 'bg-white/30 text-white' : 'bg-slate-300 text-slate-800' }}">{{ $adStats['total_campaigns'] ?? 0 }}</span>
                </button>
                <button type="button" wire:click="setAdSubTab('inventory')"
                    class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center gap-2 {{ $adSubTab === 'inventory' ? 'bg-[#F20B83] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    <span>Slot Architecture & Inventory</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $adSubTab === 'inventory' ? 'bg-white/30 text-white' : 'bg-slate-300 text-slate-800' }}">5 Slots</span>
                </button>
                <button type="button" wire:click="setAdSubTab('analytics')"
                    class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center gap-2 {{ $adSubTab === 'analytics' ? 'bg-[#F20B83] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    <span>Performance Analytics</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $adSubTab === 'analytics' ? 'bg-white/30 text-white' : 'bg-slate-300 text-slate-800' }}">{{ $adStats['global_ctr'] ?? 0 }}% CTR</span>
                </button>
            </div>

            @if($adSubTab === 'campaigns')
                <!-- Action Toolbar: Search, Filters & Add Placement -->
                <div class="{{ $card }} p-4 mb-4">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2.5 flex-1">
                            <!-- Search Box -->
                            <div class="relative min-w-[220px] max-w-sm flex-1">
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.300ms="adSearch" 
                                    class="{{ $input }} pl-8 text-xs font-bold text-slate-900" 
                                    placeholder="Search campaigns, advertisers, URLs..."
                                />
                                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>

                            <!-- Slot Filter Dropdown -->
                            <div class="min-w-[150px]">
                                <select wire:model.live="adSlotFilter" class="{{ $input }} text-xs font-bold text-slate-900">
                                    <option value="all">All Placement Slots</option>
                                    @foreach(\App\Modules\Admin\Models\AdPlacement::SLOTS as $s)
                                        <option value="{{ $s }}">{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Status Filter Dropdown -->
                            <div class="min-w-[130px]">
                                <select wire:model.live="adStatusFilter" class="{{ $input }} text-xs font-bold text-slate-900">
                                    <option value="all">All Statuses</option>
                                    <option value="active">Active Only</option>
                                    <option value="paused">Paused Only</option>
                                </select>
                            </div>
                        </div>

                        <!-- Create New Placement Button -->
                        <button 
                            type="button" 
                            wire:click="openAdModal" 
                            class="px-4 py-2.5 rounded-xl bg-[#F20B83] hover:bg-[#D00972] text-white text-xs font-black transition shadow-xs flex items-center justify-center gap-2 shrink-0"
                        >
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            <span>+ New Placement</span>
                        </button>
                    </div>
                </div>

                <!-- Ad Campaigns Table -->
                <div class="{{ $card }} overflow-x-auto shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="{{ $th }}">Campaign & Creative</th>
                                <th class="{{ $th }}">Placement Slot</th>
                                <th class="{{ $th }}">Schedule Window</th>
                                <th class="{{ $th }}">Rate / Price</th>
                                <th class="{{ $th }}">Impr / Clicks / CTR</th>
                                <th class="{{ $th }}">Live Delivery</th>
                                <th class="{{ $th }} text-right pr-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($ads as $ad)
                            <tr class="hover:bg-slate-50/70 transition" wire:key="ad-row-{{ $ad->id }}">
                                <td class="{{ $td }}">
                                    <div class="flex items-center gap-2.5">
                                        @if($ad->image_url)
                                            <button type="button" wire:click="openPreviewAdModal({{ $ad->id }})" class="shrink-0 group">
                                                <img src="{{ $ad->image_url }}" alt="{{ $ad->name }}" class="w-10 h-10 rounded-lg object-cover border border-slate-300 group-hover:ring-2 ring-pink-500 transition" />
                                            </button>
                                        @else
                                            <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-300 flex items-center justify-center text-slate-500 font-mono text-[10px] shrink-0 font-bold">
                                                CODE
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-black text-slate-950 text-xs truncate max-w-[220px]">{{ $ad->name }}</div>
                                            <div class="text-[11px] font-semibold text-slate-600 flex items-center gap-1.5 mt-0.5">
                                                <span>👤 {{ $ad->advertiser_name ?: 'Internal Sponsor' }}</span>
                                                @if($ad->target_url)
                                                    <span>·</span>
                                                    <a href="{{ $ad->target_url }}" target="_blank" class="text-pink-600 hover:underline truncate max-w-[140px]" title="{{ $ad->target_url }}">🔗 {{ parse_url($ad->target_url, PHP_URL_HOST) ?: 'Link' }}</a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="{{ $td }}">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-800 border border-slate-300 whitespace-nowrap">
                                        {{ str_replace('_', ' ', $ad->slot) }}
                                    </span>
                                </td>
                                <td class="{{ $td }}">
                                    <div class="text-xs font-bold text-slate-800">
                                        {{ $ad->starts_at?->format('M j, Y') ?? 'Immediate' }} &rarr; {{ $ad->ends_at?->format('M j, Y') ?? 'Continuous' }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-medium mt-0.5">
                                        @if(!$ad->ends_at || $ad->ends_at->isFuture())
                                            <span class="text-emerald-700 font-bold">● Active Window</span>
                                        @else
                                            <span class="text-rose-600 font-bold">Ended</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="{{ $td }}">
                                    <span class="font-black text-xs text-slate-900">
                                        {{ $ad->price_cents ? '$' . number_format($ad->price_cents / 100, 2) : 'Free / In-House' }}
                                    </span>
                                </td>
                                <td class="{{ $td }}">
                                    <div class="font-mono text-xs font-black text-slate-900">
                                        {{ number_format($ad->impressions) }} <span class="text-slate-400 font-normal">/</span> {{ number_format($ad->clicks) }}
                                    </div>
                                    <div class="mt-0.5">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold {{ $ad->clickThroughRate() > 2 ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $ad->clickThroughRate() }}% CTR
                                        </span>
                                    </div>
                                </td>
                                <td class="{{ $td }}">
                                    <button 
                                        type="button" 
                                        wire:click="toggleAd({{ $ad->id }})" 
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-black border transition {{ $ad->is_active ? 'bg-emerald-50 text-emerald-900 border-emerald-300 hover:bg-emerald-100' : 'bg-amber-50 text-amber-900 border-amber-300 hover:bg-amber-100' }}"
                                        title="Click to toggle active delivery state"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full {{ $ad->is_active ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                        <span>{{ $ad->is_active ? 'Active' : 'Paused' }}</span>
                                    </button>
                                </td>
                                <td class="{{ $td }} text-right pr-4 whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            wire:click="openPreviewAdModal({{ $ad->id }})" 
                                            class="px-2 py-1 rounded-lg bg-pink-50 hover:bg-pink-100 text-[#F20B83] text-xs font-bold transition border border-pink-200"
                                            title="Live Viewport Simulation"
                                        >
                                            Preview
                                        </button>
                                        <button 
                                            type="button" 
                                            wire:click="openAdModal({{ $ad->id }})" 
                                            class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition border border-slate-300"
                                            title="Edit Campaign"
                                        >
                                            Edit
                                        </button>
                                        <button 
                                            type="button" 
                                            wire:click="cloneAd({{ $ad->id }})" 
                                            class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition border border-slate-300"
                                            title="Clone Campaign"
                                        >
                                            Clone
                                        </button>
                                        <button 
                                            type="button" 
                                            wire:click="resetAdStats({{ $ad->id }})" 
                                            class="px-1.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition border border-slate-300"
                                            title="Reset Impression & Click Counters"
                                        >
                                            ↺
                                        </button>
                                        <button 
                                            type="button" 
                                            wire:click="confirmDeleteAd({{ $ad->id }})" 
                                            class="px-2 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-800 text-xs font-bold transition border border-rose-300"
                                            title="Delete Placement"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center bg-slate-50">
                                    <div class="max-w-sm mx-auto text-center space-y-2">
                                        <div class="text-3xl">📢</div>
                                        <p class="text-sm font-black text-slate-800">No Advertising Placements Found</p>
                                        <p class="text-xs text-slate-500">No campaigns match your current search or slot filter. Click below to publish a new placement.</p>
                                        <button 
                                            type="button" 
                                            wire:click="openAdModal" 
                                            class="mt-2 inline-flex items-center gap-1 px-3.5 py-2 rounded-xl bg-pink-600 hover:bg-pink-700 text-white font-bold text-xs"
                                        >
                                            <span>+ Create First Placement</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    @if(method_exists($ads, 'hasPages') && $ads->hasPages())
                        <div class="p-3 border-t border-slate-200 bg-slate-50">
                            {{ $ads->links() }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- SUB-TAB 2: SLOT ARCHITECTURE & INVENTORY --}}
            @if($adSubTab === 'inventory')
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    @foreach($slotInventory as $key => $slot)
                        <div class="{{ $card }} p-5 flex flex-col justify-between border-t-4 border-t-pink-600">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-pink-100 text-pink-900 border border-pink-200">
                                        {{ $slot['dimensions'] }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $slot['active_count'] > 0 ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $slot['active_count'] }} Active Live
                                    </span>
                                </div>
                                <h4 class="text-sm font-black text-slate-950 mt-1">{{ $slot['name'] }}</h4>
                                <p class="text-xs text-slate-600 font-medium mt-1 leading-relaxed">{{ $slot['location'] }}</p>
                                
                                <div class="mt-3 p-2.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 font-bold">Recommended Rate:</span>
                                        <span class="font-black text-slate-950">{{ $slot['suggested_rate'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 font-bold">Target Devices:</span>
                                        <span class="font-bold text-slate-800">{{ $slot['device'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 font-bold">Campaigns In Slot:</span>
                                        <span class="font-bold text-slate-800">{{ $slot['total_count'] }} total ({{ $slot['active_count'] }} live)</span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between">
                                <button type="button" wire:click="$set('adSlotFilter', '{{ $key }}'); setAdSubTab('campaigns')" class="text-xs font-bold text-pink-600 hover:underline">
                                    Filter Campaigns &rarr;
                                </button>
                                <button type="button" wire:click="openAdModal" class="px-3 py-1.5 rounded-xl bg-[#F20B83] hover:bg-[#D00972] text-white text-xs font-black shadow-xs">
                                    + Add To Slot
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- SUB-TAB 3: CONVERSION & ANALYTICS LEDGER --}}
            @if($adSubTab === 'analytics')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
                    <!-- Top Converting Placements -->
                    <div class="{{ $card }} p-5">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                            <div>
                                <h4 class="text-sm font-black text-slate-950">Top Converting Campaigns</h4>
                                <p class="text-xs text-slate-500 font-medium">Ranked by Click-Through Rate (CTR) and verified user engagement.</p>
                            </div>
                            <span class="text-xl">🏆</span>
                        </div>

                        <div class="space-y-3">
                            @forelse($topPerformers as $idx => $top)
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-900 font-black text-xs flex items-center justify-center shrink-0">
                                            #{{ $idx + 1 }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-xs font-black text-slate-950 truncate">{{ $top->name }}</p>
                                            <p class="text-[11px] text-slate-500 font-semibold">{{ $top->advertiser_name ?: 'Direct Sponsor' }} · {{ str_replace('_', ' ', $top->slot) }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="px-2 py-0.5 rounded text-xs font-black bg-emerald-100 text-emerald-900 border border-emerald-300">
                                            {{ $top->clickThroughRate() }}% CTR
                                        </span>
                                        <p class="text-[10px] text-slate-600 font-bold mt-0.5">{{ number_format($top->clicks) }} clicks / {{ number_format($top->impressions) }} impr</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500 text-center py-6">No performance records recorded yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Direct Sponsor Revenue Pipeline -->
                    <div class="{{ $card }} p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                                <div>
                                    <h4 class="text-sm font-black text-slate-950">Monetization & Traffic Health</h4>
                                    <p class="text-xs text-slate-500 font-medium">Direct billing valuation across all active advertising inventory.</p>
                                </div>
                                <span class="text-xl">💰</span>
                            </div>

                            <div class="space-y-3">
                                <div class="p-4 rounded-xl bg-gradient-to-br from-pink-50 to-purple-50 border border-pink-200">
                                    <span class="text-[11px] font-black uppercase text-pink-900">Current Monthly Ad Run Rate:</span>
                                    <div class="text-3xl font-black text-slate-950 mt-1">
                                        ${{ number_format($adStats['total_revenue_usd'] ?? 0, 2) }}
                                    </div>
                                    <p class="text-xs text-slate-600 font-medium mt-1">Combined monthly contract values of {{ $adStats['active_campaigns'] ?? 0 }} active sponsor campaigns.</p>
                                </div>

                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2 text-xs">
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-slate-700">Total Ad Impressions Delivered:</span>
                                        <span class="font-black text-slate-950">{{ number_format($adStats['total_impressions'] ?? 0) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-slate-700">Total Ad Clicks Generated:</span>
                                        <span class="font-black text-slate-950">{{ number_format($adStats['total_clicks'] ?? 0) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-slate-700">Global Average CTR:</span>
                                        <span class="font-black text-emerald-700">{{ $adStats['global_ctr'] ?? 0 }}%</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-slate-700">Live Delivery Status:</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-900">● 100% Operational</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 mt-4 border-t border-slate-200 flex justify-end">
                            <button type="button" wire:click="openAdModal" class="px-4 py-2 rounded-xl bg-[#F20B83] hover:bg-[#D00972] text-white text-xs font-black shadow-xs">
                                + Create New Monetized Campaign
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        {{-- ================= 14. SEO & MARKETING ================= --}}
        @if($tab === 'seo')
            <!-- SEO & Marketing Command Ribbon -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Card 1: Indexable URLs -->
                <div class="{{ $card }} p-5 border-l-4 border-l-emerald-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-slate-700">Indexed Footprint</p>
                            <h3 class="text-2xl font-black text-slate-950 mt-1">{{ number_format($seoStats['indexable_routes'] ?? 0) }}</h3>
                            <p class="text-xs font-semibold text-slate-600 mt-0.5">{{ $seoStats['sitemaps_count'] ?? 5 }} XML Sitemaps Active</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-300 flex items-center justify-center font-black text-xl">
                            <svg class="w-6 h-6 text-emerald-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs font-bold text-emerald-900 bg-emerald-50/70 border border-emerald-200 px-2 py-1 rounded-md">
                        <span>●</span><span>100% Crawlable Search Engine Ready</span>
                    </div>
                </div>

                <!-- Card 2: SERP Metadata Health -->
                <div class="{{ $card }} p-5 border-l-4 border-l-blue-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-slate-700">SERP Health Score</p>
                            <h3 class="text-2xl font-black text-slate-950 mt-1">{{ $seoStats['health_score'] ?? 98 }}%</h3>
                            <p class="text-xs font-semibold text-slate-600 mt-0.5">{{ $seoStats['total_overrides'] ?? 0 }} Route Overrides Configured</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-800 border border-blue-300 flex items-center justify-center font-black text-xl">
                            <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs font-bold text-blue-900 bg-blue-50/70 border border-blue-200 px-2 py-1 rounded-md">
                        <span>✓</span><span>OpenGraph &amp; Twitter Cards Active</span>
                    </div>
                </div>

                <!-- Card 3: Robots & Crawlers Directives -->
                <div class="{{ $card }} p-5 border-l-4 border-l-amber-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-slate-700">Crawl Directives</p>
                            <h3 class="text-2xl font-black text-slate-950 mt-1">{{ $seoStats['index_follow_count'] ?? 0 }} / {{ $seoStats['total_overrides'] ?? 0 }}</h3>
                            <p class="text-xs font-semibold text-slate-600 mt-0.5">Index, Follow Enabled</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-900 border border-amber-300 flex items-center justify-center font-black text-xl">
                            <svg class="w-6 h-6 text-amber-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs font-bold text-amber-950 bg-amber-50/70 border border-amber-200 px-2 py-1 rounded-md">
                        <span>●</span><span>/robots.txt Live Directives Serving</span>
                    </div>
                </div>

                <!-- Card 4: Organic Search Trends Demand -->
                <div class="{{ $card }} p-5 border-l-4 border-l-purple-600">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-slate-700">Organic Search Hits</p>
                            <h3 class="text-2xl font-black text-slate-950 mt-1">{{ number_format($searchTrends->sum('hits') ?? 0) }}</h3>
                            <p class="text-xs font-semibold text-slate-600 mt-0.5">{{ $searchTrends->count() }} Keyword Trends Logged</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-900 border border-purple-300 flex items-center justify-center font-black text-xl">
                            <svg class="w-6 h-6 text-purple-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-xs font-bold text-purple-950 bg-purple-50/70 border border-purple-200 px-2 py-1 rounded-md">
                        <span>★</span><span>Geo Search Demand Intel Active</span>
                    </div>
                </div>
            </div>

            <!-- SEO & Marketing Segmented Sub-Tabs Bar -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-6 border-b border-slate-200">
                <button 
                    wire:click="setSeoSubTab('metadata')" 
                    class="px-4 py-2 rounded-xl font-black text-xs transition flex items-center gap-2 whitespace-nowrap {{ $seoSubTab === 'metadata' ? 'bg-[#F20B65] text-white shadow-md' : 'bg-slate-100 text-slate-800 hover:bg-slate-200 border border-slate-200' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    <span>Page Metadata &amp; Overrides ({{ $seoMetas->total() ?? count($seoMetas) }})</span>
                </button>

                <button 
                    wire:click="setSeoSubTab('sitemaps')" 
                    class="px-4 py-2 rounded-xl font-black text-xs transition flex items-center gap-2 whitespace-nowrap {{ $seoSubTab === 'sitemaps' ? 'bg-[#F20B65] text-white shadow-md' : 'bg-slate-100 text-slate-800 hover:bg-slate-200 border border-slate-200' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>XML Sitemaps Engine (5)</span>
                </button>

                <button 
                    wire:click="setSeoSubTab('robots')" 
                    class="px-4 py-2 rounded-xl font-black text-xs transition flex items-center gap-2 whitespace-nowrap {{ $seoSubTab === 'robots' ? 'bg-[#F20B65] text-white shadow-md' : 'bg-slate-100 text-slate-800 hover:bg-slate-200 border border-slate-200' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Robots.txt &amp; Crawlers</span>
                </button>

                <button 
                    wire:click="setSeoSubTab('serp')" 
                    class="px-4 py-2 rounded-xl font-black text-xs transition flex items-center gap-2 whitespace-nowrap {{ $seoSubTab === 'serp' ? 'bg-[#F20B65] text-white shadow-md' : 'bg-slate-100 text-slate-800 hover:bg-slate-200 border border-slate-200' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>Live Google SERP Simulator</span>
                </button>

                <button 
                    wire:click="setSeoSubTab('marketing')" 
                    class="px-4 py-2 rounded-xl font-black text-xs transition flex items-center gap-2 whitespace-nowrap {{ $seoSubTab === 'marketing' ? 'bg-[#F20B65] text-white shadow-md' : 'bg-slate-100 text-slate-800 hover:bg-slate-200 border border-slate-200' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>Tracking Pixels &amp; Tags</span>
                </button>

                <button 
                    wire:click="setSeoSubTab('trends')" 
                    class="px-4 py-2 rounded-xl font-black text-xs transition flex items-center gap-2 whitespace-nowrap {{ $seoSubTab === 'trends' ? 'bg-[#F20B65] text-white shadow-md' : 'bg-slate-100 text-slate-800 hover:bg-slate-200 border border-slate-200' }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    <span>Organic Search Trends ({{ $searchTrends->count() }})</span>
                </button>
            </div>

            {{-- 1. SUB-TAB: METADATA & OPENGRAPH OVERRIDES --}}
            @if($seoSubTab === 'metadata')
                <!-- Toolbar & Actions -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-1">
                        <!-- Search Input -->
                        <div class="relative flex-1 max-w-md">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <input 
                                type="text" 
                                wire:model.live.debounce.300ms="seoSearch" 
                                placeholder="Search route path, meta title, keywords..." 
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 bg-white text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#F20B65] focus:border-[#F20B65] shadow-xs"
                            >
                        </div>

                        <!-- Robots Directive Filter -->
                        <select 
                            wire:model.live="seoRobotsFilter" 
                            class="px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#F20B65] shadow-xs"
                        >
                            <option value="all">All Robots Directives</option>
                            <option value="index, follow">index, follow (Indexed)</option>
                            <option value="noindex, follow">noindex, follow (Follow Links Only)</option>
                            <option value="index, nofollow">index, nofollow (Index No Pass)</option>
                            <option value="noindex, nofollow">noindex, nofollow (Blocked)</option>
                        </select>

                        @if($seoSearch || $seoRobotsFilter !== 'all')
                            <button 
                                wire:click="resetSeoFilters" 
                                class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-800 border border-slate-300 transition"
                            >
                                Reset
                            </button>
                        @endif
                    </div>

                    <!-- Right Buttons: Baseline Generator & + New SEO Entry -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <button 
                            wire:click="generateDefaultSeoMetas" 
                            class="px-4 py-2.5 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-950 border border-purple-300 text-xs font-black transition flex items-center gap-1.5 shadow-xs"
                        >
                            <svg class="w-4 h-4 text-purple-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>⚡ Generate Default Baselines</span>
                        </button>

                        <button 
                            wire:click="openSeoModal" 
                            class="px-4 py-2.5 rounded-xl bg-[#F20B65] hover:bg-[#d90858] text-white text-xs font-black transition flex items-center gap-1.5 shadow-md shadow-pink-500/20"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            <span>+ New SEO Override</span>
                        </button>
                    </div>
                </div>

                <!-- Overrides Table -->
                <div class="{{ $card }} overflow-x-auto shadow-md">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="{{ $th }}">Route / Canonical</th>
                                <th class="{{ $th }}">Meta Title &amp; Description</th>
                                <th class="{{ $th }}">Robots Directive</th>
                                <th class="{{ $th }}">Social Card / Assets</th>
                                <th class="{{ $th }} text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($seoMetas as $m)
                            <tr class="hover:bg-slate-50/80 transition" wire:key="seo-row-{{ $m->id }}">
                                <!-- Route & Canonical -->
                                <td class="{{ $td }} align-top">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 rounded-md bg-blue-50 text-blue-900 border border-blue-200 font-mono text-xs font-black">
                                            {{ $m->page_route ?? (class_basename($m->model_type) . ' #' . $m->model_id) }}
                                        </span>
                                    </div>
                                    @if($m->canonical_url)
                                        <p class="text-[11px] font-mono text-slate-600 mt-1 truncate max-w-xs flex items-center gap-1">
                                            <span class="text-slate-400">rel="canonical":</span>
                                            <span class="truncate">{{ $m->canonical_url }}</span>
                                        </p>
                                    @endif
                                </td>

                                <!-- Title & Description -->
                                <td class="{{ $td }} align-top max-w-md">
                                    <div class="font-black text-sm text-slate-950 flex items-center gap-2">
                                        <span>{{ $m->meta_title }}</span>
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ strlen($m->meta_title) <= 60 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                            {{ strlen($m->meta_title) }} chars
                                        </span>
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700 mt-1 line-clamp-2">
                                        {{ $m->meta_description ?: 'No meta description configured for this route.' }}
                                    </p>
                                    @if($m->meta_keywords)
                                        <div class="flex flex-wrap gap-1 mt-1.5">
                                            @foreach(array_slice(explode(',', $m->meta_keywords), 0, 4) as $kw)
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200">
                                                    {{ trim($kw) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>

                                <!-- Robots Directive -->
                                <td class="{{ $td }} align-top whitespace-nowrap">
                                    @if(str_contains($m->robots_directive, 'noindex'))
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black bg-rose-50 text-rose-900 border border-rose-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                            <span>{{ $m->robots_directive }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-900 border border-emerald-300">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            <span>{{ $m->robots_directive }}</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- OG Image / Assets -->
                                <td class="{{ $td }} align-top">
                                    @if($m->og_image_url)
                                        <div class="flex items-center gap-2">
                                            <img src="{{ $m->og_image_url }}" alt="OG Preview" class="w-12 h-8 object-cover rounded border border-slate-200 shadow-2xs">
                                            <span class="text-[10px] font-bold text-slate-700 truncate max-w-[120px]">OG Image Active</span>
                                        </div>
                                    @else
                                        <span class="text-xs font-semibold text-slate-500">Default Brand Card</span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="{{ $td }} align-top text-right whitespace-nowrap space-x-1.5">
                                    <button 
                                        wire:click="selectSerpPreview({{ $m->id }})" 
                                        class="px-2.5 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-900 text-xs font-black border border-blue-200 transition"
                                        title="Simulate Google Search Snippet"
                                    >
                                        SERP Preview
                                    </button>

                                    <button 
                                        wire:click="openSeoModal({{ $m->id }})" 
                                        class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-900 text-xs font-black border border-slate-300 transition"
                                    >
                                        Edit
                                    </button>

                                    <button 
                                        wire:click="deleteSeo({{ $m->id }})" 
                                        wire:confirm="Are you sure you want to delete this SEO metadata override?" 
                                        class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-900 text-xs font-black border border-rose-200 transition"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="{{ $td }} text-center py-12">
                                    <div class="max-w-md mx-auto text-center space-y-3">
                                        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-[#F20B65] border border-rose-200 flex items-center justify-center mx-auto text-xl font-black">
                                            🔍
                                        </div>
                                        <h4 class="text-base font-black text-slate-900">No SEO Overrides Match Filter</h4>
                                        <p class="text-xs font-semibold text-slate-600">Generate default baseline metadata for your core pages or create targeted SEO overrides.</p>
                                        <button wire:click="generateDefaultSeoMetas" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-black transition shadow-xs">
                                            ⚡ Generate Baseline SEO Overrides Now
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                    @if($seoMetas instanceof \Illuminate\Pagination\LengthAwarePaginator && $seoMetas->hasPages())
                        <div class="p-4 border-t border-slate-200 bg-slate-50">
                            {{ $seoMetas->links() }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- 2. SUB-TAB: XML SITEMAPS ENGINE --}}
            @if($seoSubTab === 'sitemaps')
                <div class="space-y-6">
                    <!-- Control Banner -->
                    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-2xl p-6 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6 border border-slate-700">
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Live Sitemap Engine</span>
                                <span class="text-xs text-slate-300 font-mono font-medium">RFC 8288 &amp; sitemaps.org compliant</span>
                            </div>
                            <h3 class="text-xl font-black tracking-tight text-white">XML Sitemaps Index &amp; Dynamic Feed Controller</h3>
                            <p class="text-xs text-slate-300 max-w-2xl">
                                Automatically indexes and aggregates all verified classified ads, category listings, 50 US state directories, and high-priority content pages for Google, Bing, and DuckDuckGo crawlers.
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <button 
                                wire:click="refreshSitemaps" 
                                class="px-4 py-2.5 rounded-xl bg-[#F20B65] hover:bg-[#d90858] text-white text-xs font-black transition flex items-center gap-2 shadow-lg shadow-pink-500/25"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>Purge Cache &amp; Ping Search Bots</span>
                            </button>
                        </div>
                    </div>

                    <!-- 5 Sitemaps Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach($sitemaps as $sm)
                            <div class="{{ $card }} p-5 flex flex-col justify-between hover:shadow-lg transition border-t-4 border-t-[#F20B65]">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase font-mono bg-slate-100 text-slate-800 border border-slate-300">
                                            {{ $sm['type'] }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-800 border border-emerald-300 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            <span>{{ $sm['status'] }}</span>
                                        </span>
                                    </div>

                                    <h4 class="text-base font-black text-slate-950">{{ $sm['name'] }}</h4>
                                    <p class="text-xs font-mono font-bold text-slate-600 mt-1 truncate">{{ $sm['route'] }}</p>

                                    <div class="grid grid-cols-3 gap-2 mt-4 p-3 rounded-xl bg-slate-50 border border-slate-200 text-center">
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase">Records</p>
                                            <p class="text-sm font-black text-slate-950 mt-0.5">{{ number_format($sm['count']) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase">Frequency</p>
                                            <p class="text-xs font-black text-slate-800 capitalize mt-1">{{ $sm['frequency'] }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase">Priority</p>
                                            <p class="text-xs font-black text-[#F20B65] mt-1">{{ $sm['priority'] }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-600">Auto-Generated XML</span>
                                    <a 
                                        href="{{ $sm['route'] }}" 
                                        target="_blank" 
                                        class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-900 text-xs font-black border border-slate-300 transition flex items-center gap-1"
                                    >
                                        <span>Inspect XML</span>
                                        <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 3. SUB-TAB: ROBOTS.TXT DIRECTIVES --}}
            @if($seoSubTab === 'robots')
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left 2 Cols: Live Rules & Breakdown -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="{{ $card }} p-6">
                            <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                                <div>
                                    <h4 class="text-lg font-black text-slate-950">Active /robots.txt Crawl Directives</h4>
                                    <p class="text-xs font-semibold text-slate-600 mt-0.5">Instructions communicated to automated search bot spiders and scrapers.</p>
                                </div>
                                <a 
                                    href="{{ url('/robots.txt') }}" 
                                    target="_blank" 
                                    class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-900 text-xs font-black border border-slate-300 transition flex items-center gap-1.5 shadow-2xs"
                                >
                                    <span>View Live /robots.txt</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </div>

                            <!-- Raw Robots.txt Viewer -->
                            <div class="mt-4 p-4 rounded-xl bg-slate-900 text-emerald-400 font-mono text-xs leading-relaxed shadow-inner overflow-x-auto">
                                <p class="text-slate-400"># LocalDateHub Search Engine Directives</p>
                                <p class="text-slate-400"># Generated dynamically with strict safety protections</p>
                                <p class="mt-2 text-white font-black">User-agent: *</p>
                                <p class="text-rose-400">Disallow: /admin/</p>
                                <p class="text-rose-400">Disallow: /account/</p>
                                <p class="text-rose-400">Disallow: /auth/</p>
                                <p class="text-rose-400">Disallow: /api/</p>
                                <p class="text-rose-400">Disallow: /livewire/</p>
                                <p class="mt-2 text-emerald-300">Allow: /</p>
                                <p class="text-emerald-300">Allow: /search</p>
                                <p class="text-emerald-300">Allow: /locations/</p>
                                <p class="text-emerald-300">Allow: /categories/</p>
                                <p class="text-emerald-300">Allow: /listings/</p>
                                <p class="mt-2 text-purple-300">Sitemap: {{ route('sitemap.index') }}</p>
                            </div>
                        </div>

                        <!-- Rule Policy Breakdown -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="{{ $card }} p-5 border-l-4 border-l-emerald-600">
                                <h5 class="text-sm font-black text-slate-950 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                    <span>Indexed Public Zones</span>
                                </h5>
                                <ul class="mt-3 space-y-1.5 text-xs font-semibold text-slate-700">
                                    <li class="flex items-center justify-between py-1 border-b border-slate-100">
                                        <span>Landing Page &amp; Home</span>
                                        <span class="font-mono text-emerald-700 font-black">Allow: /</span>
                                    </li>
                                    <li class="flex items-center justify-between py-1 border-b border-slate-100">
                                        <span>50 US States &amp; Top Metro Hubs</span>
                                        <span class="font-mono text-emerald-700 font-black">Allow: /locations/</span>
                                    </li>
                                    <li class="flex items-center justify-between py-1 border-b border-slate-100">
                                        <span>Categories &amp; Sub-Classifieds</span>
                                        <span class="font-mono text-emerald-700 font-black">Allow: /categories/</span>
                                    </li>
                                    <li class="flex items-center justify-between py-1">
                                        <span>Active Verified Classified Ads</span>
                                        <span class="font-mono text-emerald-700 font-black">Allow: /listings/</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="{{ $card }} p-5 border-l-4 border-l-rose-600">
                                <h5 class="text-sm font-black text-slate-950 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                                    <span>Protected Restricted Zones</span>
                                </h5>
                                <ul class="mt-3 space-y-1.5 text-xs font-semibold text-slate-700">
                                    <li class="flex items-center justify-between py-1 border-b border-slate-100">
                                        <span>Admin Enterprise Hub</span>
                                        <span class="font-mono text-rose-700 font-black">Disallow: /admin/</span>
                                    </li>
                                    <li class="flex items-center justify-between py-1 border-b border-slate-100">
                                        <span>Member Account &amp; Messages</span>
                                        <span class="font-mono text-rose-700 font-black">Disallow: /account/</span>
                                    </li>
                                    <li class="flex items-center justify-between py-1 border-b border-slate-100">
                                        <span>Authentication &amp; Passwords</span>
                                        <span class="font-mono text-rose-700 font-black">Disallow: /auth/</span>
                                    </li>
                                    <li class="flex items-center justify-between py-1">
                                        <span>Restricted REST API Endpoints</span>
                                        <span class="font-mono text-rose-700 font-black">Disallow: /api/</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Right Col: Crawler Bot Guidelines & Security -->
                    <div class="{{ $card }} p-6 space-y-4">
                        <h4 class="text-base font-black text-slate-950">Bot Directives &amp; Rate Limiting</h4>
                        <p class="text-xs font-semibold text-slate-600">
                            The crawl directives protect server CPU resources against abusive scraping while maximizing indexation bandwidth for verified search engine bots.
                        </p>

                        <div class="p-3.5 rounded-xl bg-purple-50 border border-purple-200 space-y-1">
                            <p class="text-xs font-black text-purple-950">Googlebot &amp; Bingbot Support</p>
                            <p class="text-xs font-semibold text-purple-900">
                                Supports Google-Extended, Bingbot, and Slurp crawling with automated XML sitemap detection via standard HTTP response headers.
                            </p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 space-y-1">
                            <p class="text-xs font-black text-amber-950">Private Contact Protection</p>
                            <p class="text-xs font-semibold text-amber-900">
                                Member phone numbers, direct emails, and inquiry forms are cloaked against automated harvesting bots using interactive JS modals.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 4. SUB-TAB: LIVE GOOGLE SERP SIMULATOR --}}
            @if($seoSubTab === 'serp')
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Left 5 Cols: Controls -->
                    <div class="lg:col-span-5 space-y-4">
                        <div class="{{ $card }} p-6 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                                <h4 class="text-base font-black text-slate-950">SERP Preview Controls</h4>
                                <div class="flex items-center gap-1 p-1 rounded-lg bg-slate-100 border border-slate-200">
                                    <button 
                                        wire:click="$set('serpDevice', 'desktop')" 
                                        class="px-2.5 py-1 rounded-md text-xs font-black transition {{ $serpDevice === 'desktop' ? 'bg-white text-slate-950 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}"
                                    >
                                        Desktop
                                    </button>
                                    <button 
                                        wire:click="$set('serpDevice', 'mobile')" 
                                        class="px-2.5 py-1 rounded-md text-xs font-black transition {{ $serpDevice === 'mobile' ? 'bg-white text-slate-950 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}"
                                    >
                                        Mobile
                                    </button>
                                </div>
                            </div>

                            <!-- Route Selector -->
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1">Route / Page URI</label>
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.200ms="serpPreviewRoute" 
                                    class="{{ $input }} font-mono text-xs font-bold" 
                                    placeholder="e.g. /locations/california/los-angeles"
                                >
                            </div>

                            <!-- Title Input with Character Meter -->
                            <div>
                                <div class="flex items-center justify-between text-xs font-black mb-1">
                                    <label class="text-slate-800 uppercase tracking-wider">Page Title Tag</label>
                                    <span class="{{ strlen($serpPreviewTitle) <= 60 ? 'text-emerald-700' : 'text-rose-600' }}">
                                        {{ strlen($serpPreviewTitle) }} / 60 optimal
                                    </span>
                                </div>
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.200ms="serpPreviewTitle" 
                                    maxlength="120" 
                                    class="{{ $input }} text-xs font-bold"
                                >
                                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
                                    <div 
                                        class="h-1.5 rounded-full {{ strlen($serpPreviewTitle) <= 60 ? 'bg-emerald-500' : 'bg-rose-500' }}" 
                                        style="width: {{ min(100, (strlen($serpPreviewTitle) / 60) * 100) }}%"
                                    ></div>
                                </div>
                            </div>

                            <!-- Description with Character Meter -->
                            <div>
                                <div class="flex items-center justify-between text-xs font-black mb-1">
                                    <label class="text-slate-800 uppercase tracking-wider">Meta Description</label>
                                    <span class="{{ strlen($serpPreviewDescription) <= 160 ? 'text-emerald-700' : 'text-rose-600' }}">
                                        {{ strlen($serpPreviewDescription) }} / 160 optimal
                                    </span>
                                </div>
                                <textarea 
                                    wire:model.live.debounce.200ms="serpPreviewDescription" 
                                    rows="4" 
                                    maxlength="300" 
                                    class="{{ $input }} text-xs font-bold leading-relaxed"
                                ></textarea>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1.5 overflow-hidden">
                                    <div 
                                        class="h-1.5 rounded-full {{ strlen($serpPreviewDescription) <= 160 ? 'bg-emerald-500' : 'bg-rose-500' }}" 
                                        style="width: {{ min(100, (strlen($serpPreviewDescription) / 160) * 100) }}%"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right 7 Cols: Google SERP Simulator Card -->
                    <div class="lg:col-span-7 space-y-4">
                        <div class="{{ $card }} p-6 sm:p-8">
                            <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg font-black text-blue-600">G</span>
                                    <span class="text-lg font-black text-rose-600">o</span>
                                    <span class="text-lg font-black text-amber-500">o</span>
                                    <span class="text-lg font-black text-blue-600">g</span>
                                    <span class="text-lg font-black text-emerald-600">l</span>
                                    <span class="text-lg font-black text-rose-600">e</span>
                                    <span class="text-xs font-bold text-slate-500 ml-1">Organic Result Snippet ({{ ucfirst($serpDevice) }} View)</span>
                                </div>
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-black uppercase bg-slate-100 text-slate-700 border border-slate-200">
                                    Live Render
                                </span>
                            </div>

                            <!-- Simulated Snippet Box -->
                            <div class="mt-6 p-6 rounded-2xl bg-white border border-slate-200 shadow-sm {{ $serpDevice === 'mobile' ? 'max-w-md mx-auto border-2 border-slate-300' : '' }}">
                                <!-- Breadcrumb row -->
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-pink-50 border border-pink-200 flex items-center justify-center text-[#F20B65] text-xs font-black shrink-0">
                                        ♥
                                    </div>
                                    <div class="truncate leading-tight">
                                        <p class="text-xs font-medium text-slate-800 truncate">LocalDateHub</p>
                                        <p class="text-[11px] text-slate-500 font-mono truncate">
                                            https://localdatehub.com<span class="text-slate-400">{{ $serpPreviewRoute ?: '/locations' }}</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- SERP Title (Google Blue) -->
                                <h3 class="text-lg sm:text-xl font-medium text-[#1a0dab] hover:underline cursor-pointer mt-2 leading-snug tracking-tight">
                                    {{ $serpPreviewTitle ?: 'LocalDateHub — Adult Classifieds & Dating' }}
                                </h3>

                                <!-- SERP Description Snippet -->
                                <p class="text-xs sm:text-sm text-[#4d5156] mt-1.5 leading-relaxed">
                                    {{ $serpPreviewDescription ?: 'Browse verified adult classifieds, dating personals, and discreet local connections on LocalDateHub. 100% free to post, verified members, and secure.' }}
                                </p>

                                <!-- Rich Snippets Simulator -->
                                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-3 text-xs text-slate-600 flex-wrap">
                                    <span class="flex items-center gap-1 text-amber-500 font-bold">
                                        ★★★★★ <strong class="text-slate-800">4.9</strong> (1,240 reviews)
                                    </span>
                                    <span>•</span>
                                    <span class="text-emerald-700 font-semibold">100% Free Classifieds</span>
                                    <span>•</span>
                                    <span class="text-slate-500 font-semibold">18+ Only</span>
                                </div>
                            </div>

                            <!-- Quality Check Alerts -->
                            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs font-bold">
                                <div class="p-3 rounded-xl {{ strlen($serpPreviewTitle) >= 30 && strlen($serpPreviewTitle) <= 60 ? 'bg-emerald-50 text-emerald-900 border border-emerald-300' : 'bg-amber-50 text-amber-900 border border-amber-300' }}">
                                    <span>{{ strlen($serpPreviewTitle) >= 30 && strlen($serpPreviewTitle) <= 60 ? '✓ Title Length Optimal' : '⚠ Title Length Warning (Keep 30-60 chars)' }}</span>
                                </div>
                                <div class="p-3 rounded-xl {{ strlen($serpPreviewDescription) >= 70 && strlen($serpPreviewDescription) <= 160 ? 'bg-emerald-50 text-emerald-900 border border-emerald-300' : 'bg-amber-50 text-amber-900 border border-amber-300' }}">
                                    <span>{{ strlen($serpPreviewDescription) >= 70 && strlen($serpPreviewDescription) <= 160 ? '✓ Snippet Description Optimal' : '⚠ Snippet Length Warning (Keep 70-160 chars)' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 5. SUB-TAB: TRACKING PIXELS & MARKETING TAGS --}}
            @if($seoSubTab === 'marketing')
                <div class="max-w-4xl space-y-6">
                    <div class="{{ $card }} p-6 sm:p-8 space-y-6">
                        <div class="pb-4 border-b border-slate-200">
                            <h4 class="text-lg font-black text-slate-950">Marketing Analytics, Pixels &amp; Tag Manager</h4>
                            <p class="text-xs font-semibold text-slate-600 mt-0.5">
                                Configure conversion tracking tags, Google Analytics 4, Tag Manager, and Meta Pixel. Settings are persisted to system configuration.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- GA4 -->
                            <div>
                                <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
                                    Google Analytics 4 (GA4) Measurement ID
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="marketingGaId" 
                                    placeholder="G-XXXXXXXXXX" 
                                    class="{{ $input }} font-mono text-xs font-bold"
                                >
                                <p class="text-[11px] font-semibold text-slate-500 mt-1">Collects traffic volume, pageviews, and bounce rate metrics.</p>
                            </div>

                            <!-- GTM -->
                            <div>
                                <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
                                    Google Tag Manager (GTM) Container ID
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="marketingGtmId" 
                                    placeholder="GTM-XXXXXXX" 
                                    class="{{ $input }} font-mono text-xs font-bold"
                                >
                                <p class="text-[11px] font-semibold text-slate-500 mt-1">Allows custom conversion events and trigger deployment.</p>
                            </div>

                            <!-- Meta Pixel -->
                            <div>
                                <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
                                    Meta (Facebook) Pixel ID
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="marketingPixelId" 
                                    placeholder="123456789012345" 
                                    class="{{ $input }} font-mono text-xs font-bold"
                                >
                                <p class="text-[11px] font-semibold text-slate-500 mt-1">Tracks advertising conversion events and ad campaign ROI.</p>
                            </div>
                        </div>

                        <!-- Custom Head Snippet -->
                        <div>
                            <label class="block text-xs font-black text-slate-900 uppercase tracking-wider mb-1">
                                Custom HTML &amp; JavaScript Head Tracking Scripts
                            </label>
                            <textarea 
                                wire:model="marketingCustomHead" 
                                rows="6" 
                                placeholder="<!-- Hotjar, Clarity, Twitter Pixel or custom analytics tags -->" 
                                class="{{ $input }} font-mono text-xs font-bold leading-relaxed"
                            ></textarea>
                            <p class="text-[11px] font-semibold text-slate-500 mt-1">Injected cleanly into the global document &lt;head&gt; across public pages.</p>
                        </div>

                        <!-- Save Button -->
                        <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                            <button 
                                wire:click="saveMarketingSettings" 
                                class="px-6 py-2.5 rounded-xl bg-[#F20B65] hover:bg-[#d90858] text-white text-xs font-black transition flex items-center gap-2 shadow-md shadow-pink-500/20"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>Save Marketing Tracking Tags</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 6. SUB-TAB: ORGANIC SEARCH TRENDS --}}
            @if($seoSubTab === 'trends')
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-black text-slate-950">Organic Keyword Demand &amp; Geo-Trends</h4>
                            <p class="text-xs font-semibold text-slate-600 mt-0.5">Real search queries executed by visitors across 50 US States.</p>
                        </div>
                    </div>

                    <div class="{{ $card }} overflow-x-auto shadow-md">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="{{ $th }}">Search Query</th>
                                    <th class="{{ $th }}">Target Location</th>
                                    <th class="{{ $th }}">Category</th>
                                    <th class="{{ $th }} text-center">Results Found</th>
                                    <th class="{{ $th }} text-center">Search Volume (Hits)</th>
                                    <th class="{{ $th }} text-right">Last Searched</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($searchTrends as $st)
                                <tr class="hover:bg-slate-50 transition" wire:key="trend-{{ $st->id }}">
                                    <td class="{{ $td }} font-black text-slate-950 text-sm">
                                        "{{ $st->query }}"
                                    </td>
                                    <td class="{{ $td }}">
                                        @if($st->city || $st->state)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-slate-100 text-slate-900 font-bold text-xs border border-slate-200">
                                                <span>📍</span>
                                                <span>{{ $st->city?->name ? $st->city->name . ', ' : '' }}{{ $st->state?->code ?? $st->state?->name ?? 'All US' }}</span>
                                            </span>
                                        @else
                                            <span class="text-xs font-semibold text-slate-500">Nationwide</span>
                                        @endif
                                    </td>
                                    <td class="{{ $td }}">
                                        @if($st->category)
                                            <span class="px-2.5 py-1 rounded-md bg-pink-50 text-pink-900 border border-pink-200 text-xs font-bold">
                                                {{ $st->category->name }}
                                            </span>
                                        @else
                                            <span class="text-xs font-semibold text-slate-500">All Categories</span>
                                        @endif
                                    </td>
                                    <td class="{{ $td }} text-center font-bold text-slate-800 text-xs">
                                        {{ number_format($st->results_count) }}
                                    </td>
                                    <td class="{{ $td }} text-center">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-black bg-purple-50 text-purple-950 border border-purple-200">
                                            🔥 {{ number_format($st->hits) }} hits
                                        </span>
                                    </td>
                                    <td class="{{ $td }} text-right text-xs font-bold text-slate-600">
                                        {{ $st->last_searched_at ? $st->last_searched_at->diffForHumans() : 'Recently' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="{{ $td }} text-center py-8 text-slate-500 font-semibold">
                                        No organic search trends recorded yet. Searches performed in the search bar appear here automatically.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif

        {{-- ================= 15. CMS PAGES & CONTENT HUB ================= --}}
        @if($tab === 'cms')
            <div class="space-y-6">
                <!-- Top 4 KPI Metrics -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total CMS Pages -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-pink-600">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Total CMS Pages</span>
                            <span class="p-2 bg-pink-50 rounded-xl text-[#F20B83]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($cmsStats['total_pages'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold">
                            Editorial and policy articles
                        </div>
                    </div>

                    <!-- Published Pages -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-emerald-600">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Live & Published</span>
                            <span class="p-2 bg-emerald-50 rounded-xl text-emerald-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($cmsStats['published_pages'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Accessible to public web visitors</span>
                        </div>
                    </div>

                    <!-- Draft Pages -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-amber-500">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Drafts / In Review</span>
                            <span class="p-2 bg-amber-50 rounded-xl text-amber-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($cmsStats['draft_pages'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold">
                            Unpublished internal revisions
                        </div>
                    </div>

                    <!-- Mandatory Policies -->
                    <div class="{{ $card }} p-5 border-l-4 border-l-blue-600">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Legal Compliance Pages</span>
                            <span class="p-2 bg-blue-50 rounded-xl text-blue-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                            {{ number_format($cmsStats['mandatory_pages'] ?? 0) }} / 4
                        </div>
                        <div class="mt-1 text-xs text-slate-600 font-semibold">
                            FOSTA, 2257, Terms & Privacy active
                        </div>
                    </div>
                </div>

                <!-- Action Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
                    <div>
                        <h2 class="text-lg font-black text-slate-950 flex items-center gap-2">
                            <span>CMS & Editorial Pages Management</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-pink-50 text-[#F20B83] border border-pink-200">SEO & Compliance</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Author, publish, and maintain platform informational, compliance, and user guide pages.</p>
                    </div>

                    @if($this->userCan('cms.manage'))
                        <button type="button" wire:click="openCmsModal"
                            class="px-4 py-2 rounded-xl bg-[#F20B83] hover:bg-pink-600 text-white text-xs font-black shadow-sm flex items-center gap-1.5 transition whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>+ Create New Page</span>
                        </button>
                    @endif
                </div>

                <!-- Search & Filters -->
                <div class="{{ $card }} p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                        <div class="sm:col-span-8 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" wire:model.live.debounce.300ms="cmsSearch"
                                placeholder="Search CMS pages by title, URL slug, or keywords..."
                                class="{{ $input }} pl-9 font-medium" />
                        </div>

                        <div class="sm:col-span-4">
                            <select wire:model.live="cmsStatusFilter" class="{{ $input }} font-medium">
                                <option value="all">All Statuses (Published & Drafts)</option>
                                <option value="published">Published & Live Only</option>
                                <option value="draft">Drafts Only</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Real-Time Interactive Table -->
                <div class="{{ $card }} overflow-x-auto shadow-sm">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="{{ $th }}">Page Title & URL Route</th>
                                <th class="{{ $th }}">Publication Status</th>
                                <th class="{{ $th }}">Last Updated</th>
                                <th class="{{ $th }}">Word Count</th>
                                <th class="{{ $th }} text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cmsPages as $p)
                                <tr class="border-t border-slate-100 hover:bg-slate-50/70 transition" wire:key="cms-{{ $p->id }}">
                                    <!-- Title & Slug -->
                                    <td class="{{ $td }}">
                                        <div class="font-black text-slate-950 text-xs">
                                            {{ $p->title }}
                                        </div>
                                        <div class="mt-0.5 flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px] font-semibold">
                                                /page/{{ $p->slug }}
                                            </span>
                                            @if(in_array($p->slug, ['terms-of-service', 'privacy-policy', 'safety-guidelines', 'community-standards']))
                                                <span class="text-[10px] font-bold text-blue-700 bg-blue-50 px-1.5 py-0.2 rounded border border-blue-200">
                                                    Mandatory
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="{{ $td }}">
                                        @if($this->userCan('cms.manage'))
                                            <button type="button" wire:click="toggleCmsPublish({{ $p->id }})"
                                                title="Click to toggle publication status"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold transition border
                                                {{ $p->is_published ? 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200' }}">
                                                <span class="w-2 h-2 rounded-full {{ $p->is_published ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                <span>{{ $p->is_published ? 'PUBLISHED' : 'DRAFT' }}</span>
                                            </button>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $badge((bool) $p->is_published) }}">
                                                {{ $p->is_published ? 'PUBLISHED' : 'DRAFT' }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Last Updated -->
                                    <td class="{{ $td }}">
                                        <div class="text-xs text-slate-800 font-semibold">
                                            {{ $p->updated_at?->diffForHumans() ?? 'N/A' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            {{ $p->updated_at?->format('M j, Y H:i') }}
                                        </div>
                                    </td>

                                    <!-- Word Count -->
                                    <td class="{{ $td }}">
                                        <span class="font-mono text-xs text-slate-700 font-semibold">
                                            {{ str_word_count(strip_tags($p->content)) }} words
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="{{ $td }} text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" wire:click="openPreviewCmsModal({{ $p->id }})"
                                                class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200" title="Preview article">
                                                Preview
                                            </button>
                                            @if($this->userCan('cms.manage'))
                                                <button type="button" wire:click="openCmsModal({{ $p->id }})"
                                                    class="{{ $btn }} bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200" title="Edit content">
                                                    Edit
                                                </button>
                                                <button type="button" wire:click="deleteCmsPage({{ $p->id }})"
                                                    wire:confirm="Are you sure you want to delete this CMS page permanently?"
                                                    class="{{ $btn }} bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200" title="Delete page">
                                                    Delete
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="{{ $td }} text-center py-12 text-slate-500">
                                        <div class="max-w-xs mx-auto text-center space-y-2">
                                            <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xl">
                                                📄
                                            </div>
                                            <p class="font-bold text-slate-900">No CMS pages found</p>
                                            <p class="text-xs text-slate-500">Try adjusting your search criteria or create a new page.</p>
                                            @if($this->userCan('cms.manage'))
                                                <button type="button" wire:click="openCmsModal" class="px-3 py-1.5 bg-[#F20B83] text-white rounded-xl text-xs font-bold shadow">
                                                    + Create New Page Now
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="p-3 border-t border-slate-100 bg-slate-50/50">{{ $cmsPages->links() }}</div>
                </div>
            </div>
        @endif

        {{-- ================= 18. STAFF ================= --}}
        @if($tab === 'staff')
            <!-- Staff Overview Metric Ribbon -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Card 1: Total Staff Members -->
                <div class="{{ $card }} p-5 border-l-4 border-l-rose-600">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Total Staff Team</span>
                        <span class="p-2 bg-rose-50 rounded-xl text-rose-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                        {{ $staffStats['total_staff'] }}
                    </div>
                    <div class="mt-1 text-xs text-slate-700 font-semibold">
                        {{ $staffStats['active_count'] }} active accounts across all modules
                    </div>
                </div>

                <!-- Card 2: System Administrators -->
                <div class="{{ $card }} p-5 border-l-4 border-l-purple-600">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Administrators</span>
                        <span class="p-2 bg-purple-50 rounded-xl text-purple-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                        {{ $staffStats['admin_count'] }}
                    </div>
                    <div class="mt-1 text-xs text-slate-700 font-semibold">
                        Super Admins & Executive Admins
                    </div>
                </div>

                <!-- Card 3: Trust & Safety Moderators -->
                <div class="{{ $card }} p-5 border-l-4 border-l-emerald-600">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Content Moderators</span>
                        <span class="p-2 bg-emerald-50 rounded-xl text-emerald-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                        {{ $staffStats['moderator_count'] }}
                    </div>
                    <div class="mt-1 text-xs text-slate-700 font-semibold">
                        Senior & Listing review agents
                    </div>
                </div>

                <!-- Card 4: Operations & Support -->
                <div class="{{ $card }} p-5 border-l-4 border-l-blue-600">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Specialist Officers</span>
                        <span class="p-2 bg-blue-50 rounded-xl text-blue-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-950 font-mono">
                        {{ $staffStats['other_staff_count'] }}
                    </div>
                    <div class="mt-1 text-xs text-slate-700 font-semibold">
                        Compliance, Support, Finance & SEO
                    </div>
                </div>
            </div>

            <!-- Toolbar: Search, Filters & Add Staff Button -->
            <div class="{{ $card }} p-4 mb-5">
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                    <!-- Search bar -->
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="staffSearch" placeholder="Search staff by name, email, display name..." class="{{ $input }} pl-9 text-xs sm:text-sm">
                    </div>

                    <!-- Role & Status Filters -->
                    <div class="flex flex-wrap items-center gap-2">
                        <select wire:model.live="staffRoleFilter" class="{{ $input }} !w-auto text-xs sm:text-sm" aria-label="Staff role filter">
                            <option value="all">All Roles</option>
                            @foreach(self::STAFF_ROLES as $rSlug)
                                <option value="{{ $rSlug }}">{{ $assignableRoles[$rSlug] ?? ucwords(str_replace('_', ' ', $rSlug)) }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="staffStatusFilter" class="{{ $input }} !w-auto text-xs sm:text-sm" aria-label="Staff status filter">
                            <option value="all">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="pending_verification">Pending</option>
                            <option value="suspended">Suspended</option>
                            <option value="closed">Closed</option>
                        </select>

                        @if($staffSearch !== '' || $staffRoleFilter !== 'all' || $staffStatusFilter !== 'all')
                            <button wire:click="resetStaffFilters" class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs sm:text-sm" title="Reset Filters">
                                Reset
                            </button>
                        @endif

                        <button wire:click="openCreateStaffModal" class="{{ $btn }} bg-[#F20B83] hover:bg-[#d90858] text-white text-xs sm:text-sm font-bold shadow flex items-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            <span>+ Add Staff Account</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Staff Accounts Table -->
            <div class="{{ $card }} overflow-x-auto mb-6">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/50">
                            <th class="{{ $th }}">Staff Member</th>
                            <th class="{{ $th }}">Assigned Role</th>
                            <th class="{{ $th }}">Status</th>
                            <th class="{{ $th }}">Security / Age</th>
                            <th class="{{ $th }}">Last Login</th>
                            <th class="{{ $th }} text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                    @forelse($staffMembers as $s)
                        <tr class="hover:bg-slate-50/60 transition" wire:key="staff-{{ $s->id }}">
                            <td class="{{ $td }}">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-black text-xs text-slate-800 shrink-0">
                                        {{ strtoupper(substr($s->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <button wire:click="openViewStaffModal({{ $s->id }})" class="font-bold text-slate-900 hover:text-rose-600 text-left block truncate">
                                            {{ $s->name }}
                                        </button>
                                        <div class="text-xs text-slate-500 font-mono truncate">{{ $s->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="{{ $td }}">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold
                                    @if(in_array($s->role, ['super_admin', 'admin'])) bg-purple-100 text-purple-900 border border-purple-200
                                    @elseif(str_contains($s->role, 'moderator')) bg-emerald-100 text-emerald-900 border border-emerald-200
                                    @elseif($s->role === 'compliance_officer') bg-amber-100 text-amber-900 border border-amber-200
                                    @else bg-blue-100 text-blue-900 border border-blue-200 @endif
                                ">
                                    {{ $assignableRoles[$s->role] ?? ucwords(str_replace('_', ' ', $s->role)) }}
                                </span>
                            </td>
                            <td class="{{ $td }}">
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold capitalize {{ $badge($s->account_status) }}">
                                    {{ $s->account_status }}
                                </span>
                            </td>
                            <td class="{{ $td }} text-xs">
                                @if($s->is_age_verified)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        ✓ 18+ Verified
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-500">
                                        Pending Age
                                    </span>
                                @endif
                            </td>
                            <td class="{{ $td }} text-xs text-slate-600 whitespace-nowrap font-medium">
                                {{ $s->last_login_at ? \Illuminate\Support\Carbon::parse($s->last_login_at)->diffForHumans() : '—' }}
                            </td>
                            <td class="{{ $td }} text-right space-x-1.5 whitespace-nowrap">
                                @if($s->id !== auth()->id())
                                    <!-- Direct Login Button (Without Password) -->
                                    <button wire:click="directLoginAsStaff({{ $s->id }})" class="{{ $btn }} bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 text-xs font-bold shadow-xs inline-flex items-center space-x-1" title="Direct Login without password">
                                        <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                        <span>Login As</span>
                                    </button>
                                @endif

                                <!-- View Staff Profile Modal -->
                                <button wire:click="openViewStaffModal({{ $s->id }})" class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 text-xs font-semibold">
                                    View
                                </button>

                                <!-- Edit Staff Details Modal -->
                                <button wire:click="openEditStaffModal({{ $s->id }})" class="{{ $btn }} bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 text-xs font-semibold">
                                    Edit
                                </button>

                                <!-- Assign / Change Role Modal -->
                                @if($s->id !== auth()->id() && ($s->role !== 'super_admin' || $isSuperAdmin))
                                    <button wire:click="openAssignRoleModal({{ $s->id }})" class="{{ $btn }} bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 text-xs font-semibold">
                                        Role
                                    </button>

                                    <!-- Delete Staff Member Modal -->
                                    <button wire:click="openDeleteStaffModal({{ $s->id }})" class="{{ $btn }} bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold">
                                        Delete
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="{{ $td }} py-8 text-center text-slate-500 font-semibold">
                                No staff accounts match your current search and role filters.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3 border-t border-slate-200">{{ $staffMembers->links() }}</div>
            </div>
        @endif

        {{-- ================= 19. ROLES & PERMISSIONS ================= --}}
        @if($tab === 'roles')
            @unless($isSuperAdmin)
                <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-700">Read-only: only a Super Administrator can edit the permission matrix.</div>
            @endunless
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead>
                        <tr>
                            <th class="{{ $th }} sticky left-0 bg-slate-50">Permission</th>
                            @foreach($roles as $role)<th class="{{ $th }} text-center whitespace-nowrap">{{ $role->name }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($permissionsByModule as $module => $perms)
                        <tr class="bg-pink-50/50"><td colspan="{{ $roles->count() + 1 }}" class="px-3 py-1 font-bold uppercase tracking-widest text-[10px] text-[#F20B83]">{{ $module }}</td></tr>
                        @foreach($perms as $perm)
                            <tr class="border-t border-slate-100 hover:bg-slate-50/60" wire:key="perm-{{ $perm->id }}">
                                <td class="px-3 py-1.5 font-mono sticky left-0 bg-white text-slate-800">{{ $perm->slug }}</td>
                                @foreach($roles as $role)
                                    @php $granted = $role->permissions->contains('id', $perm->id); $locked = !$isSuperAdmin || in_array($role->slug, ['super_admin', 'admin']); @endphp
                                    <td class="px-3 py-1.5 text-center">
                                        <input type="checkbox" @checked($granted) @disabled($locked)
                                            wire:click="toggleRolePermission({{ $role->id }}, {{ $perm->id }})"
                                            aria-label="{{ $role->name }} – {{ $perm->slug }}"
                                            class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83] disabled:opacity-50">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 20. SYSTEM SETTINGS & REAL-TIME EMAIL SUITE ================= --}}
        @if($tab === 'settings')
            <!-- Enterprise Settings Sub-Navigation Ribbon (Multi-Row Responsive Wrap & Fail-Safe Dual Engine) -->
            <div class="flex flex-wrap items-center gap-2 mb-6 border-b border-slate-200 pb-3">
                @foreach([
                    ['general', '🏢', 'General & Platform'],
                    ['branding', '🎨', 'Logo & Favicon'],
                    ['smtp', '✉️', 'SMTP & Mail Server'],
                    ['notifications', '🔔', 'Email Event Triggers', 'Live'],
                    ['safety', '🛡️', 'Safety & 2257 Legal'],
                    ['monetization', '💰', 'Monetization & Limits'],
                    ['locations', '📍', 'Search & Locations'],
                    ['security', '🔒', 'Security & Maintenance'],
                ] as $sTab)
                    <a
                        href="{{ route('admin.dashboard', ['tab' => 'settings', 'subtab' => $sTab[0]]) }}"
                        wire:click.prevent="setSettingsSubTab('{{ $sTab[0] }}')"
                        class="px-3.5 py-2 rounded-xl text-xs font-black transition whitespace-nowrap flex items-center gap-1.5 cursor-pointer select-none {{ $settingsSubTab === $sTab[0] ? 'bg-[#F20B83] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}"
                    >
                        <span>{{ $sTab[1] }}</span>
                        <span>{{ $sTab[2] }}</span>
                        @if(isset($sTab[3]))
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $settingsSubTab === $sTab[0] ? 'bg-white/30 text-white' : 'bg-pink-100 text-pink-900 font-extrabold' }}">{{ $sTab[3] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            <form wire:submit="saveSystemSettings" class="space-y-6">

                {{-- ================= SUB-TAB 1: GENERAL & PLATFORM ================= --}}
                @if($settingsSubTab === 'general')
                    <div class="{{ $card }} p-6 space-y-5">
                        <div class="border-b border-slate-200 pb-3">
                            <h3 class="text-base font-black text-slate-950 flex items-center gap-2">
                                <span>🏢 Platform Identity &amp; Contact Metadata</span>
                            </h3>
                            <p class="text-xs text-slate-500 font-medium">Core platform branding declarations displayed across public footers, contact pages, and metadata.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Platform Name</label>
                                <input type="text" wire:model="settingValues.site_name" class="{{ $input }} text-xs font-bold" placeholder="e.g. LocalDateHub">
                                <span class="text-[11px] text-slate-500">Global site brand name used across header, emails, and title tags.</span>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Platform Tagline</label>
                                <input type="text" wire:model="settingValues.tagline" class="{{ $input }} text-xs font-bold" placeholder="e.g. Safe &amp; Private Local Adult Personal Classifieds">
                                <span class="text-[11px] text-slate-500">Displayed beneath logo and in search engine descriptions.</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Customer Support Email</label>
                                <input type="email" wire:model="settingValues.support_email" class="{{ $input }} text-xs font-bold" placeholder="support@localdatehub.com">
                                <span class="text-[11px] text-slate-500">Public destination for user inquiries and contact forms.</span>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Support Telephone / Hotline</label>
                                <input type="text" wire:model="settingValues.support_phone" class="{{ $input }} text-xs font-bold" placeholder="+1 (800) 555-DATE">
                                <span class="text-[11px] text-slate-500">Optional customer care phone or SMS helpline.</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Telegram Support Channel / Handle</label>
                                <input type="text" wire:model="settingValues.support_telegram" class="{{ $input }} text-xs font-bold" placeholder="@LocalDateHubSupport">
                                <span class="text-[11px] text-slate-500">Direct encrypted support link for members and advertisers.</span>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Footer Legal &amp; Copyright Declaration</label>
                                <input type="text" wire:model="settingValues.copyright_text" class="{{ $input }} text-xs font-bold" placeholder="© 2026 LocalDateHub. All rights reserved. 18+ Adults Only.">
                                <span class="text-[11px] text-slate-500">Copyright string rendered at bottom of every public page.</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ================= SUB-TAB 2: BRANDING, LOGO & FAVICON ================= --}}
                @if($settingsSubTab === 'branding')
                    <div class="{{ $card }} p-6 space-y-6">
                        <div class="border-b border-slate-200 pb-3">
                            <h3 class="text-base font-black text-slate-950 flex items-center gap-2">
                                <span>🎨 Visual Assets, Logos &amp; Favicon Manager</span>
                            </h3>
                            <p class="text-xs text-slate-500 font-medium">Configure primary brand emblems, header logos, mobile logos, browser tab favicon, and theme color.</p>
                        </div>

                        <!-- Live Visual Preview Showcase -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Card A: Header Logo (Light Theme) -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                                <div>
                                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-2">Header Logo (White Background)</span>
                                    <div class="h-20 bg-white border border-slate-300 rounded-xl flex items-center justify-center p-3 shadow-inner">
                                        @if(!empty($settingValues['site_logo_url']))
                                            <img src="{{ $settingValues['site_logo_url'] }}" alt="Logo" class="max-h-12 max-w-full object-contain" onerror="this.src='/images/logo.png'">
                                        @else
                                            <span class="text-pink-600 font-black text-lg flex items-center gap-1.5">
                                                <span>❤️</span><span>LocalDateHub</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="block text-xs font-bold text-slate-800 mb-1">Logo URL (Light Background)</label>
                                    <input type="text" wire:model="settingValues.site_logo_url" class="{{ $input }} text-xs font-mono" placeholder="/images/logo.png">
                                </div>
                            </div>

                            <!-- Card B: Header Logo (Dark / Contrast) -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                                <div>
                                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-2">Header Logo (Dark / Footer)</span>
                                    <div class="h-20 bg-slate-900 border border-slate-800 rounded-xl flex items-center justify-center p-3 shadow-inner">
                                        @if(!empty($settingValues['site_logo_dark_url']))
                                            <img src="{{ $settingValues['site_logo_dark_url'] }}" alt="Logo Dark" class="max-h-12 max-w-full object-contain" onerror="this.src='/images/logo.png'">
                                        @else
                                            <span class="text-white font-black text-lg flex items-center gap-1.5">
                                                <span class="text-pink-500">❤️</span><span>LocalDateHub</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="block text-xs font-bold text-slate-800 mb-1">Logo URL (Dark Background)</label>
                                    <input type="text" wire:model="settingValues.site_logo_dark_url" class="{{ $input }} text-xs font-mono" placeholder="/images/logo-white.png">
                                </div>
                            </div>

                            <!-- Card C: Browser Favicon Simulation -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col justify-between">
                                <div>
                                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 block mb-2">Browser Tab Favicon Simulation</span>
                                    <!-- Simulated Browser Tab -->
                                    <div class="bg-slate-200 rounded-t-xl p-1 pt-1.5 border border-slate-300">
                                        <div class="bg-white rounded-t-lg px-3 py-1.5 flex items-center gap-2 border-b-2 border-pink-500 shadow-sm max-w-[200px]">
                                            <img src="{{ $settingValues['site_favicon_url'] ?? '/favicon.ico' }}" alt="Favicon" class="w-4 h-4 object-contain rounded" onerror="this.src='/favicon.ico'">
                                            <span class="text-[11px] font-bold text-slate-800 truncate">LocalDateHub — Adult Personals</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="block text-xs font-bold text-slate-800 mb-1">Browser Favicon URL (.ico / .png)</label>
                                    <input type="text" wire:model="settingValues.site_favicon_url" class="{{ $input }} text-xs font-mono" placeholder="/favicon.ico">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Mobile Compact Logo URL</label>
                                <input type="text" wire:model="settingValues.site_mobile_logo_url" class="{{ $input }} text-xs font-mono" placeholder="/images/logo-mobile.png">
                                <span class="text-[11px] text-slate-500">Condensed square or icon emblem displayed on mobile smartphone headers.</span>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Primary Brand Accent Color</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model="settingValues.primary_brand_color" class="h-9 w-12 rounded-lg border border-slate-300 cursor-pointer p-0.5">
                                    <input type="text" wire:model="settingValues.primary_brand_color" class="{{ $input }} text-xs font-mono font-bold uppercase" placeholder="#F20B83">
                                </div>
                                <span class="text-[11px] text-slate-500">Primary accent color used for buttons, highlight badges, and active tabs.</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ================= SUB-TAB 3: SMTP & MAIL SERVER ================= --}}
                @if($settingsSubTab === 'smtp')
                    <div class="space-y-6">
                        <div class="{{ $card }} p-6 space-y-5">
                            <div class="border-b border-slate-200 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <h3 class="text-base font-black text-slate-950 flex items-center gap-2">
                                        <span>✉️ SMTP Mail Transport &amp; Delivery Server</span>
                                    </h3>
                                    <p class="text-xs text-slate-500 font-medium">Configure outbound transactional SMTP credentials for high-deliverability email alerts.</p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-900 border border-emerald-300">
                                    ● TLS Handshake Ready
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-black text-slate-800 uppercase mb-1">Mail Driver</label>
                                    <select wire:model="settingValues.mail_mailer" class="{{ $input }} text-xs font-bold text-slate-900">
                                        <option value="smtp">SMTP (Recommended for Production)</option>
                                        <option value="sendmail">Sendmail / Local Postfix</option>
                                        <option value="mailgun">Mailgun API</option>
                                        <option value="ses">Amazon SES</option>
                                        <option value="log">Local Log (Development / Debug)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-slate-800 uppercase mb-1">SMTP Hostname</label>
                                    <input type="text" wire:model="settingValues.mail_host" class="{{ $input }} text-xs font-mono font-bold" placeholder="smtp.mailgun.org">
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-slate-800 uppercase mb-1">SMTP Port</label>
                                    <input type="number" wire:model="settingValues.mail_port" class="{{ $input }} text-xs font-mono font-bold" placeholder="587">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-black text-slate-800 uppercase mb-1">SMTP Username</label>
                                    <input type="text" wire:model="settingValues.mail_username" class="{{ $input }} text-xs font-mono" placeholder="postmaster@mg.localdatehub.com">
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-slate-800 uppercase mb-1">SMTP Password / API Key</label>
                                    <input type="password" wire:model="settingValues.mail_password" class="{{ $input }} text-xs font-mono" placeholder="••••••••••••••••">
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-slate-800 uppercase mb-1">Encryption Protocol</label>
                                    <select wire:model="settingValues.mail_encryption" class="{{ $input }} text-xs font-bold text-slate-900">
                                        <option value="tls">TLS (Port 587 - Recommended)</option>
                                        <option value="ssl">SSL (Port 465)</option>
                                        <option value="none">None / Plain (Port 25)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                                <div>
                                    <label class="block text-xs font-black text-slate-800 uppercase mb-1">Outgoing From Email Address</label>
                                    <input type="email" wire:model="settingValues.mail_from_address" class="{{ $input }} text-xs font-bold font-mono" placeholder="notifications@localdatehub.com">
                                    <span class="text-[11px] text-slate-500">Address displayed in recipient inbox "From" line.</span>
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-slate-800 uppercase mb-1">Outgoing Sender Display Name</label>
                                    <input type="text" wire:model="settingValues.mail_from_name" class="{{ $input }} text-xs font-bold" placeholder="LocalDateHub Notifications">
                                    <span class="text-[11px] text-slate-500">Friendly name displayed in email headers.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Live Interactive SMTP Connection Test Box -->
                        <div class="{{ $card }} p-5 border-l-4 border-l-purple-600">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="text-sm font-black text-slate-950 flex items-center gap-1.5">
                                        <span>⚡ Live SMTP Connection &amp; Delivery Diagnostic Test</span>
                                    </h4>
                                    <p class="text-xs text-slate-500 font-medium">Verify your mail server connection right now by sending an instant test message.</p>
                                </div>
                            </div>

                            @if($smtpTestSuccessMessage)
                                <div class="mb-3 p-3 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-950 font-bold text-xs flex items-center justify-between">
                                    <span>{{ $smtpTestSuccessMessage }}</span>
                                    <button type="button" wire:click="$set('smtpTestSuccessMessage', null)" class="text-emerald-800 font-black text-sm">&times;</button>
                                </div>
                            @endif

                            @if($smtpTestErrorMessage)
                                <div class="mb-3 p-3 rounded-xl bg-amber-50 border border-amber-300 text-amber-950 font-bold text-xs flex items-center justify-between">
                                    <span>⚠️ {{ $smtpTestErrorMessage }}</span>
                                    <button type="button" wire:click="$set('smtpTestErrorMessage', null)" class="text-amber-800 font-black text-sm">&times;</button>
                                </div>
                            @endif

                            <div class="flex flex-col sm:flex-row items-center gap-3">
                                <div class="relative flex-1 w-full">
                                    <input 
                                        type="email" 
                                        wire:model="testEmailRecipient" 
                                        wire:keydown.enter.prevent="sendTestEmail"
                                        class="{{ $input }} text-xs font-bold" 
                                        placeholder="Enter recipient email address (e.g. admin@localdatehub.com)"
                                    />
                                    @error('testEmailRecipient')<span class="text-rose-600 text-xs font-bold mt-1 block">{{ $message }}</span>@enderror
                                </div>
                                <button 
                                    type="button" 
                                    wire:click="sendTestEmail" 
                                    wire:loading.attr="disabled"
                                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-black text-xs transition shadow-xs flex items-center justify-center gap-2 shrink-0"
                                >
                                    <span wire:loading.remove wire:target="sendTestEmail">⚡ Send Test Email</span>
                                    <span wire:loading wire:target="sendTestEmail" class="flex items-center gap-1.5">
                                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                        <span>Dispatching Mail...</span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ================= SUB-TAB 4: REAL-TIME EMAIL EVENT TRIGGERS ================= --}}
                @if($settingsSubTab === 'notifications')
                    <div class="space-y-5">
                        <div class="{{ $card }} p-5">
                            <div class="border-b border-slate-200 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-black text-slate-950 flex items-center gap-2">
                                        <span>🔔 Real-Time Transactional Email Notification Matrix</span>
                                    </h3>
                                    <p class="text-xs text-slate-500 font-medium">Toggle instant automated email dispatches for user onboarding, classified moderation, payments, wallet balance, and chat alerts.</p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-900 border border-emerald-300">
                                    All 17 Triggers Operational
                                </span>
                            </div>
                        </div>

                        <!-- 1. Account & Membership Lifecycle -->
                        <div class="{{ $card }} p-5 sm:p-6 space-y-4">
                            <div class="flex items-center gap-2 pb-2 border-b border-slate-200">
                                <span class="p-1 rounded bg-blue-100 text-blue-700 font-black text-xs">👤</span>
                                <h4 class="text-sm font-black text-slate-900">1. Account &amp; Membership Lifecycle</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <!-- Trigger A: Email Verification Required -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Email Verification on Signup</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Require user to verify email link/OTP before accessing full account features.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_account_verification_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Trigger B: Welcome Email -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Account Created Welcome Email</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Send a warm welcome email upon successful account registration.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_welcome_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Trigger C: Account Approved -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Account Approved Notification</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Notify member when admin or moderation team approves account activation.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_account_approved_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Trigger D: 18+ Age & Identity Verified -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">18+ Identity Verified Badge Email</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Send congratulations email with Verified Badge confirmation.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_account_verified_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Trigger E: Password Reset -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5 md:col-span-2">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Password Reset Link / OTP Email</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Send secure one-time token link when user requests password recovery.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_password_reset_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Classified Listings & Post Moderation -->
                        <div class="{{ $card }} p-5 sm:p-6 space-y-4">
                            <div class="flex items-center gap-2 pb-2 border-b border-slate-200">
                                <span class="p-1 rounded bg-pink-100 text-pink-700 font-black text-xs">📝</span>
                                <h4 class="text-sm font-black text-slate-900">2. Classified Listings &amp; Moderation Events</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <!-- Trigger: Post Pending -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Post Pending Review Email</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Confirm receipt of listing and alert user it is queued for moderator check.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_post_pending_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Trigger: Post Published Live -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Post Published Live Email</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Celebrate with user when listing is published with direct public view link.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_post_published_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Trigger: Post Rejected -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Post Rejected with Reason Email</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Send transparent feedback explaining why ad was declined with edit advice.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_post_rejected_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Trigger: Post Deleted -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Post Deleted Notification Email</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Notify owner when an ad is removed or closed by admin.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_post_deleted_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Trigger: Post Expiring Soon -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5 md:col-span-2">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Listing Expiring Soon Alert</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Automated prompt 48 hours before listing expires encouraging renewal or VIP boost.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_post_expiring_soon_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Billing, Crypto Payments & Wallet Balance Alerts -->
                        <div class="{{ $card }} p-5 sm:p-6 space-y-4">
                            <div class="flex items-center gap-2 pb-2 border-b border-slate-200">
                                <span class="p-1 rounded bg-emerald-100 text-emerald-700 font-black text-xs">💳</span>
                                <h4 class="text-sm font-black text-slate-900">3. Billing, Crypto Payments &amp; Wallet Balance Alerts</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <!-- Trigger: Payment Pending -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Invoice Created / Awaiting Payment Email</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Send crypto deposit address and invoice order details immediately.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_payment_pending_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Trigger: Payment Received -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Payment Received (Blockchain Confirmed)</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Immediate receipt with transaction hash once node detects confirmations.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_payment_received_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Trigger: Payment Success & Credits Credited -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Payment Success &amp; Order Complete</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Confirm plan activation, featured boost badge, or credits added.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_payment_success_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Trigger: Payment Declined / Expired -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Payment Declined / Underpaid Alert</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Alert user if an invoice expired or underpaid with customer support link.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_payment_declined_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Trigger: Funds / Credits Added to Wallet -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Balance Credited to Wallet Email</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Notify member whenever credits are added (via admin bonus or deposit).</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_balance_added_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Trigger: Low Balance Alert -->
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Low Balance Warning Alert</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Alert user when wallet drops to or below threshold:</span>
                                        <div class="mt-2 flex items-center gap-1.5">
                                            <span class="text-[10px] font-bold text-slate-600">Threshold:</span>
                                            <input type="number" min="1" wire:model="settingValues.low_balance_alert_threshold" class="w-20 rounded-lg bg-slate-50 border border-slate-300 px-2 py-0.5 text-xs font-mono font-bold text-slate-900 focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]" placeholder="5">
                                            <span class="text-[10px] font-bold text-slate-600">credits</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_low_balance_alert_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Real-Time Chat & Instant Message Alerts -->
                        <div class="{{ $card }} p-5 sm:p-6 space-y-4">
                            <div class="flex items-center gap-2 pb-2 border-b border-slate-200">
                                <span class="p-1 rounded bg-purple-100 text-purple-700 font-black text-xs">💬</span>
                                <h4 class="text-sm font-black text-slate-900">4. Community &amp; Instant Message Alerts</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Instant Email Alert on New Message</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">When an advertiser or contact sends a private message, notify recipient immediately.</span>
                                    </div>
                                    <div class="shrink-0 flex items-center sm:self-center">
                                        <select wire:model="settingValues.mail_new_message_alert_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                    <div class="min-w-0 flex-1">
                                        <span class="text-xs font-black text-slate-900 block tracking-tight">Message Notification Anti-Spam Throttle</span>
                                        <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Limit email notifications to at most 1 alert every X minutes per sender:</span>
                                    </div>
                                    <div class="shrink-0 flex items-center gap-1.5 sm:self-center">
                                        <input type="number" min="1" max="60" wire:model="settingValues.mail_message_throttle_minutes" class="w-20 rounded-xl bg-slate-50 border border-slate-300 px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]" placeholder="5">
                                        <span class="text-xs font-bold text-slate-600">min</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ================= SUB-TAB 5: SAFETY & 2257 COMPLIANCE ================= --}}
                @if($settingsSubTab === 'safety')
                    <div class="{{ $card }} p-6 space-y-5">
                        <div class="border-b border-slate-200 pb-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-black text-slate-950 flex items-center gap-2">
                                    <span>🛡️ Trust, Safety &amp; Federal 2257 / FOSTA Compliance</span>
                                </h3>
                                <p class="text-xs text-slate-500 font-medium">Protect platform legal immunity, enforce strict adult verification, and prevent non-consensual exploitation.</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-900 border border-rose-300">
                                US Legal Grade
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Minimum Legal Age Requirement</label>
                                <select wire:model="settingValues.min_age_requirement" class="{{ $input }} text-xs font-bold text-slate-900">
                                    <option value="18">18+ (Standard US Legal Age)</option>
                                    <option value="21">21+ (Enhanced Strict Age)</option>
                                </select>
                                <span class="text-[11px] text-slate-500">Strict gate enforced across registration and verification. Cannot be under 18.</span>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Mandatory Age Assurance Before Posting</label>
                                <select wire:model="settingValues.mandatory_age_verification_to_post" class="{{ $input }} text-xs font-bold text-slate-900">
                                    <option value="true">Enforced (Texas HB 1181 &amp; US Standards)</option>
                                    <option value="false">Optional (Soft Verification)</option>
                                </select>
                                <span class="text-[11px] text-slate-500">Requires verified ID badge before user can submit personal ads.</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                <div class="min-w-0 flex-1">
                                    <span class="text-xs font-black text-slate-900 block tracking-tight">Automated Anti-Trafficking Keyword Scanning</span>
                                    <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Automated screening against high-risk human exploitation lexicons.</span>
                                </div>
                                <div class="shrink-0 flex items-center sm:self-center">
                                    <select wire:model="settingValues.anti_trafficking_enforcement" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                        <option value="true">Enabled</option>
                                        <option value="false">Disabled</option>
                                    </select>
                                </div>
                            </div>

                            <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                                <div class="min-w-0 flex-1">
                                    <span class="text-xs font-black text-slate-900 block tracking-tight">Photo EXIF &amp; GPS Metadata Auto-Stripping</span>
                                    <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Automatically strip GPS coordinates and camera serials on upload.</span>
                                </div>
                                <div class="shrink-0 flex items-center sm:self-center">
                                    <select wire:model="settingValues.photo_exif_strip_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                        <option value="true">Enabled</option>
                                        <option value="false">Disabled</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase mb-1">18 U.S.C. 2257 Record-Keeping Custodian Statement</label>
                            <textarea wire:model="settingValues.custodian_of_records_notice" rows="2" class="{{ $input }} text-xs font-mono font-medium"></textarea>
                            <span class="text-[11px] text-slate-500">Notice included in legal footers designating keeper of records.</span>
                        </div>
                    </div>
                @endif

                {{-- ================= SUB-TAB 6: MONETIZATION & LIMITS ================= --}}
                @if($settingsSubTab === 'monetization')
                    <div class="{{ $card }} p-6 space-y-5">
                        <div class="border-b border-slate-200 pb-3">
                            <h3 class="text-base font-black text-slate-950 flex items-center gap-2">
                                <span>💰 Monetization, Credit Costs &amp; Posting Limits</span>
                            </h3>
                            <p class="text-xs text-slate-500 font-medium">Configure free vs paid listing thresholds, photo allowances, and active duration limits.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Allow Free Standard Listings</label>
                                <select wire:model="settingValues.free_listings_enabled" class="{{ $input }} text-xs font-bold text-slate-900">
                                    <option value="true">Yes (Allow free basic classifieds)</option>
                                    <option value="false">No (Require paid credits for all postings)</option>
                                </select>
                                <span class="text-[11px] text-slate-500">When disabled, every ad submission deducts posting credits.</span>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Maximum Free Listings Per Member</label>
                                <input type="number" min="0" wire:model="settingValues.max_free_listings_per_user" class="{{ $input }} text-xs font-bold" placeholder="3">
                                <span class="text-[11px] text-slate-500">Max concurrent active free ads allowed without VIP package.</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Max Photos Allowed Per Listing</label>
                                <input type="number" min="1" max="30" wire:model="settingValues.max_photos_per_listing" class="{{ $input }} text-xs font-bold" placeholder="6">
                                <span class="text-[11px] text-slate-500">Upper limit on gallery photo uploads per ad.</span>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Listing Active Duration (Days)</label>
                                <input type="number" min="1" max="365" wire:model="settingValues.listing_active_days" class="{{ $input }} text-xs font-bold" placeholder="30">
                                <span class="text-[11px] text-slate-500">Number of days before ad automatically expires and needs renewal.</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ================= SUB-TAB 7: SEARCH & LOCATIONS ================= --}}
                @if($settingsSubTab === 'locations')
                    <div class="{{ $card }} p-6 space-y-5">
                        <div class="border-b border-slate-200 pb-3">
                            <h3 class="text-base font-black text-slate-950 flex items-center gap-2">
                                <span>📍 Geographic Search, Radius &amp; Mapping</span>
                            </h3>
                            <p class="text-xs text-slate-500 font-medium">Fine-tune geo-distance radius, measurement units, and search result sizing.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Default Search Radius</label>
                                <select wire:model="settingValues.default_search_radius_miles" class="{{ $input }} text-xs font-bold text-slate-900">
                                    <option value="10">10 Miles (Immediate Metro)</option>
                                    <option value="25">25 Miles (Standard County)</option>
                                    <option value="50">50 Miles (Greater Region)</option>
                                    <option value="100">100 Miles (Statewide Extended)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Distance Measurement Unit</label>
                                <select wire:model="settingValues.distance_unit" class="{{ $input }} text-xs font-bold text-slate-900">
                                    <option value="miles">Miles (USA Standard)</option>
                                    <option value="km">Kilometers (Metric)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Max Search Results Per Page</label>
                                <input type="number" min="10" max="250" wire:model="settingValues.max_search_results" class="{{ $input }} text-xs font-bold" placeholder="100">
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                            <div class="min-w-0 flex-1">
                                <span class="text-xs font-black text-slate-900 block tracking-tight">Anti-Triangulation Location Fuzzing</span>
                                <span class="text-[11px] text-slate-500 font-medium leading-relaxed block mt-0.5">Adds subtle random offset (1-3 miles) to map coordinates to prevent stalking or triangulation.</span>
                            </div>
                            <div class="shrink-0 flex items-center sm:self-center">
                                <select wire:model="settingValues.location_fuzzing_enabled" class="w-full sm:w-28 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                    <option value="true">Enabled</option>
                                    <option value="false">Disabled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ================= SUB-TAB 8: SECURITY & MAINTENANCE ================= --}}
                @if($settingsSubTab === 'security')
                    <div class="{{ $card }} p-6 space-y-5">
                        <div class="border-b border-slate-200 pb-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-black text-slate-950 flex items-center gap-2">
                                    <span>🔒 Administrative Security &amp; Maintenance Mode</span>
                                </h3>
                                <p class="text-xs text-slate-500 font-medium">Session guardrails, staff 2FA enforcement, brute-force protection, and emergency maintenance controls.</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-900 border border-amber-300">
                                🔒 Super Admin Restricted
                            </span>
                        </div>

                        <!-- Maintenance Mode Switch -->
                        <div class="p-4 rounded-2xl border {{ ($settingValues['maintenance_mode_enabled'] ?? 'false') === 'true' ? 'bg-rose-50 border-rose-300' : 'bg-white border-slate-200/90 shadow-2xs' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
                            <div class="min-w-0 flex-1">
                                <span class="text-xs font-black text-slate-900 block tracking-tight">Platform Maintenance Mode</span>
                                <span class="text-[11px] text-slate-600 font-medium leading-relaxed block mt-0.5">When enabled, regular visitors see maintenance screen while administrators retain login access.</span>
                            </div>
                            <div class="shrink-0 flex items-center sm:self-center">
                                <select wire:model="settingValues.maintenance_mode_enabled" class="w-full sm:w-44 rounded-xl bg-slate-50 border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-900 shadow-2xs focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer transition">
                                    <option value="false">Live Online</option>
                                    <option value="true">Maintenance Mode</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase mb-1">Maintenance Announcement Message</label>
                            <textarea wire:model="settingValues.maintenance_announcement_message" rows="2" class="{{ $input }} text-xs font-medium"></textarea>
                            <span class="text-[11px] text-slate-500">Message displayed to visitors when maintenance mode is active.</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Require 2FA for Staff Accounts</label>
                                <select wire:model="settingValues.admin_2fa_required" class="{{ $input }} text-xs font-bold text-slate-900">
                                    <option value="true">Enforced</option>
                                    <option value="false">Optional</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Admin Idle Session Timeout (min)</label>
                                <input type="number" min="5" max="1440" wire:model="settingValues.admin_session_timeout_minutes" class="{{ $input }} text-xs font-bold" placeholder="30">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase mb-1">Max Login Attempts (Brute-Force Lock)</label>
                                <input type="number" min="3" max="20" wire:model="settingValues.max_login_attempts" class="{{ $input }} text-xs font-bold" placeholder="5">
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Sticky Bottom Action Footer -->
                <div class="sticky bottom-4 z-20 backdrop-blur-md bg-white/95 p-4 rounded-2xl border border-slate-200 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-bold text-slate-700">All changes persist directly to production database with full audit trail logging.</span>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                        <button type="submit" wire:loading.attr="disabled" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-[#F20B83] hover:bg-[#D00972] disabled:opacity-50 text-white text-xs font-black transition shadow-xs flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="saveSystemSettings">Save Configuration Suite</span>
                            <span wire:loading wire:target="saveSystemSettings" class="flex items-center gap-2">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span>Saving Settings...</span>
                            </span>
                        </button>
                    </div>
                </div>

            </form>
        @endif

        {{-- ================= 21. AUDIT LOGS ================= --}}
        @if($tab === 'audit')
            <div class="{{ $card }} p-4 mb-4 grid md:grid-cols-2 gap-3">
                <input type="search" wire:model.live.debounce.400ms="auditActionSearch" placeholder="Filter by action (e.g. listing.approve)…" class="{{ $input }}" aria-label="Filter audit actions">
                <select wire:model.live="auditAdminFilter" class="{{ $input }}" aria-label="Filter by administrator">
                    <option value="">All administrators</option>
                    @foreach($auditAdmins as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                </select>
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">When</th><th class="{{ $th }}">Administrator</th><th class="{{ $th }}">Action</th><th class="{{ $th }}">Target</th><th class="{{ $th }}">IP</th><th class="{{ $th }}"></th></tr></thead>
                    <tbody>
                    @forelse($auditLogs as $log)
                        <tr class="border-t border-slate-200" wire:key="audit-{{ $log->id }}">
                            <td class="{{ $td }} whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="{{ $td }}">{{ $log->admin?->name ?? 'System' }}</td>
                            <td class="{{ $td }} font-mono text-rose-600">{{ $log->action }}</td>
                            <td class="{{ $td }}">{{ $log->target_type ? class_basename($log->target_type) : '—' }}{{ $log->target_id ? ' #' . $log->target_id : '' }}</td>
                            <td class="{{ $td }} font-mono text-xs">{{ $log->ip_address }}</td>
                            <td class="{{ $td }}"><button wire:click="inspectAuditLog({{ $log->id }})" class="{{ $btn }} bg-slate-200 hover:bg-slate-300 text-slate-900">Inspect</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No audit records.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $auditLogs->links() }}</div>
            </div>
        @endif

        @endif {{-- tabAllowed --}}
    </section>

    {{-- ================================================================= --}}
    {{-- MODALS                                                             --}}
    {{-- ================================================================= --}}
    @php
        $anyModal = $showCreateUserModal || $showViewUserModal || $showEditUserModal || $showDeleteUserModal
            || $showViewListingModal || $showEditListingModal || $showDeleteListingModal
            || $showSuspendUserModal || $showAssignRoleModal || $showRejectListingModal || $showAppealModal
            || $showResolveReportModal || $showInspectReportModal || $showRefundModal || $showPlanModal || $showPackageModal || $showPricingRuleModal || $showCmsModal || $showSeoModal
            || $showAdModal || $showAuditDetailModal || $showDmcaModal || $showLegalModal || $showStateComplianceModal
            || $showPreservationModal || $showViewTransactionModal || $showViewCryptoModal
            || $showAssignSubscriptionModal || $showViewSubscriptionModal
            || $showInspectVerificationModal || $showManualVerifyModal || $showRejectVerificationModal || $showBroadcastModal
            || $showPreviewCmsModal || $showDeleteAdModal || $showPreviewAdModal;
    @endphp
    @if($anyModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-4" role="dialog" aria-modal="true" wire:keydown.escape.window="closeModals">
            <div class="w-full {{ ($showViewListingModal || $showEditListingModal || $showViewUserModal || $showEditUserModal || $showInspectReportModal || $showViewTransactionModal || $showViewCryptoModal || $showViewSubscriptionModal || $showAssignSubscriptionModal || $showInspectVerificationModal || $showManualVerifyModal || $showBroadcastModal || $showPreviewCmsModal || $showPreviewAdModal) ? 'max-w-2xl' : 'max-w-xl' }} max-h-[90vh] overflow-y-auto rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">

                {{-- CREATE USER MODAL --}}
                @if($showCreateUserModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
                            <span>Create New User Account</span>
                        </h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <form wire:submit="createUser" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Full Legal Name *</label>
                                <input type="text" wire:model="createUserName" class="{{ $input }}" placeholder="e.g. Jessica Taylor" required>
                                @error('createUserName')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Public Display Name</label>
                                <input type="text" wire:model="createUserDisplayName" class="{{ $input }}" placeholder="e.g. JessicaT">
                                @error('createUserDisplayName')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Email Address *</label>
                                <input type="email" wire:model="createUserEmail" class="{{ $input }}" placeholder="user@example.com" required>
                                @error('createUserEmail')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Phone Number</label>
                                <input type="text" wire:model="createUserPhone" class="{{ $input }}" placeholder="+1 555 0192">
                                @error('createUserPhone')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Initial Password * (minimum 8 characters)</label>
                            <input type="password" wire:model="createUserPassword" class="{{ $input }}" placeholder="••••••••••••" required>
                            @error('createUserPassword')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">System Role *</label>
                                <select wire:model="createUserRole" class="{{ $input }}" required>
                                    @foreach(array_merge(['user' => 'User', 'advertiser' => 'Advertiser'], $assignableRoles) as $slug => $label)
                                        <option value="{{ $slug }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('createUserRole')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Account Status *</label>
                                <select wire:model="createUserAccountStatus" class="{{ $input }}" required>
                                    <option value="active">Active</option>
                                    <option value="pending_verification">Pending Verification</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="closed">Closed</option>
                                </select>
                                @error('createUserAccountStatus')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Initial Credits</label>
                                <input type="number" min="0" max="100000" wire:model="createUserCredits" class="{{ $input }}" placeholder="0">
                                @error('createUserCredits')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="createIsAgeVerified" wire:model="createUserIsAgeVerified" class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]">
                            <label for="createIsAgeVerified" class="text-xs font-bold text-slate-800 cursor-pointer">
                                18+ Age Verified Status (pre-approved for adult dating classifieds)
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 rounded-xl transition">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 bg-[#F20B83] hover:bg-[#d00970] text-white font-black rounded-xl text-xs shadow-md transition">
                                Create User
                            </button>
                        </div>
                    </form>
                @endif

                {{-- VIEW USER MODAL --}}
                @if($showViewUserModal && $viewUserData)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-rose-50 border-2 border-[#F20B83] text-[#F20B83] font-black text-lg flex items-center justify-center">
                                {{ strtoupper(substr($viewUserData['name'], 0, 1)) }}
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-950 flex items-center gap-2">
                                    <span>{{ $viewUserData['name'] }}</span>
                                    <span class="text-xs font-semibold text-slate-600 font-mono">#{{ $viewUserData['id'] }}</span>
                                </h3>
                                <div class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                                    <span class="capitalize text-slate-800 font-bold">{{ str_replace('_', ' ', $viewUserData['role']) }}</span>
                                    <span>·</span>
                                    <span class="px-2 py-0.5 rounded border text-[10px] font-black uppercase {{ $badge($viewUserData['account_status']) }}">
                                        {{ $viewUserData['account_status'] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-xl px-2">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs text-slate-800">
                        <!-- Details Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 bg-slate-50 rounded-2xl border border-slate-200">
                            <div>
                                <span class="text-slate-500 font-bold block text-[10px] uppercase">Public Display Name:</span>
                                <span class="font-black text-slate-950 text-sm">{{ $viewUserData['display_name'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 font-bold block text-[10px] uppercase">Email Address:</span>
                                <span class="font-mono font-bold text-slate-900">{{ $viewUserData['email'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 font-bold block text-[10px] uppercase">Phone:</span>
                                <span class="font-semibold text-slate-900">{{ $viewUserData['phone'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 font-bold block text-[10px] uppercase">18+ Age Verified:</span>
                                @if($viewUserData['is_age_verified'])
                                    <span class="text-emerald-700 font-black">✔ 18+ Age Verified</span>
                                @else
                                    <span class="text-slate-400 font-semibold">Not Verified</span>
                                @endif
                            </div>
                            <div>
                                <span class="text-slate-500 font-bold block text-[10px] uppercase">Wallet Credits Balance:</span>
                                <span class="font-mono font-black text-indigo-700 text-sm">{{ $viewUserData['wallet_balance'] }} cr</span>
                                <span class="text-[10px] text-slate-500">({{ $viewUserData['wallet_purchased'] }} purchased, {{ $viewUserData['wallet_spent'] }} spent)</span>
                            </div>
                            <div>
                                <span class="text-slate-500 font-bold block text-[10px] uppercase">Joined Date:</span>
                                <span class="font-semibold text-slate-800">{{ $viewUserData['created_at'] }}</span>
                            </div>
                        </div>

                        <!-- User Classified Listings Section -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-black text-slate-950 text-xs uppercase tracking-wider">
                                    Classified Listings ({{ $viewUserData['listings_count'] }} Total, {{ $viewUserData['active_listings_count'] }} Active)
                                </span>
                            </div>
                            @if(!empty($viewUserData['recent_listings']))
                                <div class="space-y-1.5 max-h-36 overflow-y-auto">
                                    @foreach($viewUserData['recent_listings'] as $ad)
                                        <div class="p-2 bg-white rounded-lg border border-slate-200 flex items-center justify-between">
                                            <a href="{{ route('listings.show', $ad['slug']) }}" target="_blank" class="font-bold text-slate-900 hover:text-[#F20B83] truncate max-w-xs">
                                                {{ $ad['title'] }}
                                            </a>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase {{ $badge($ad['status']) }}">{{ $ad['status'] }}</span>
                                                <span class="text-[10px] text-slate-400">{{ $ad['created_at'] }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-slate-400 italic">No classified ads published yet.</p>
                            @endif
                        </div>

                        <!-- Actions Inside View Modal -->
                        <div class="flex justify-between items-center pt-3 border-t border-slate-100">
                            <div>
                                @if($viewUserData['id'] !== auth()->id() && $viewUserData['id'] !== 1 && $viewUserData['role'] !== 'super_admin')
                                    <button
                                        type="button"
                                        wire:click="openDeleteUserModal({{ $viewUserData['id'] }})"
                                        class="px-3 py-1.5 text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition"
                                    >
                                        Delete User
                                    </button>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="closeModals" class="px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 rounded-xl transition">
                                    Close
                                </button>
                                <button
                                    type="button"
                                    wire:click="openEditUserModal({{ $viewUserData['id'] }})"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-xl text-xs transition"
                                >
                                    Edit Details
                                </button>
                                @if($viewUserData['id'] !== auth()->id() && ($viewUserData['role'] !== 'super_admin' || $this->isSuperAdmin()))
                                    <button
                                        type="button"
                                        wire:click="directLoginAsUser({{ $viewUserData['id'] }})"
                                        wire:confirm="Log in directly as {{ $viewUserData['name'] }} without password? You can return to admin at any time."
                                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-black rounded-xl text-xs transition flex items-center gap-1.5 shadow-sm"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                        <span>Login to This Account</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- EDIT USER MODAL --}}
                @if($showEditUserModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950">
                            Edit User Details #{{ $editUserId }}
                        </h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <form wire:submit="updateUser" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Full Legal Name *</label>
                                <input type="text" wire:model="editUserName" class="{{ $input }}" required>
                                @error('editUserName')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Public Display Name</label>
                                <input type="text" wire:model="editUserDisplayName" class="{{ $input }}">
                                @error('editUserDisplayName')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Email Address *</label>
                                <input type="email" wire:model="editUserEmail" class="{{ $input }}" required>
                                @error('editUserEmail')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Phone Number</label>
                                <input type="text" wire:model="editUserPhone" class="{{ $input }}">
                                @error('editUserPhone')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">System Role *</label>
                                <select wire:model="editUserRole" class="{{ $input }}" required>
                                    @foreach(array_merge(['user' => 'User', 'advertiser' => 'Advertiser'], $assignableRoles) as $slug => $label)
                                        <option value="{{ $slug }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('editUserRole')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Account Status *</label>
                                <select wire:model="editUserAccountStatus" class="{{ $input }}" required>
                                    <option value="active">Active</option>
                                    <option value="pending_verification">Pending Verification</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="closed">Closed</option>
                                </select>
                                @error('editUserAccountStatus')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Credits Balance</label>
                                <input type="number" min="0" max="100000" wire:model="editUserCredits" class="{{ $input }}">
                                @error('editUserCredits')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Change Password (leave blank to keep current)</label>
                            <input type="password" wire:model="editUserPassword" class="{{ $input }}" placeholder="Enter new password if updating">
                            @error('editUserPassword')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="editIsAgeVerified" wire:model="editUserIsAgeVerified" class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]">
                            <label for="editIsAgeVerified" class="text-xs font-bold text-slate-800 cursor-pointer">
                                18+ Age Verified Status
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 rounded-xl transition">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-xl text-xs shadow-md transition">
                                Save Changes
                            </button>
                        </div>
                    </form>
                @endif

                {{-- DELETE USER CONFIRMATION MODAL --}}
                @if($showDeleteUserModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-rose-700 flex items-center gap-2">
                            <span>Confirm User Deletion</span>
                        </h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs text-slate-700">
                        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl">
                            <p class="font-bold text-rose-950">
                                Are you sure you want to delete user <strong class="text-slate-950 font-black">[{{ $deleteUserName }}]</strong> ({{ $deleteUserEmail }})?
                            </p>
                            <p class="mt-1.5 text-rose-800 font-medium">
                                This will immediately revoke login access, soft-delete their account record, and suspend all active classified listings published by this user.
                            </p>
                        </div>

                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 rounded-xl transition">
                                Cancel
                            </button>
                            <button
                                type="button"
                                wire:click="deleteUser"
                                class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-black rounded-xl text-xs shadow-md transition"
                            >
                                Confirm Delete
                            </button>
                        </div>
                    </div>
                @endif

                {{-- VIEW LISTING DETAILS MODAL --}}
                @if($showViewListingModal && $viewListingData)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-pink-50 border border-pink-200 text-[#F20B83] flex items-center justify-center font-black text-sm">
                                📢
                            </span>
                            <div>
                                <h3 class="text-base font-black text-slate-950">
                                    Listing Details #{{ $viewListingData['id'] }}
                                </h3>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="px-2 py-0.5 rounded border text-[10px] font-black uppercase {{ $badge($viewListingData['status']) }}">
                                        {{ str_replace('_', ' ', $viewListingData['status']) }}
                                    </span>
                                    @if($viewListingData['is_featured'])
                                        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-black">
                                            ★ Spotlight Featured
                                        </span>
                                    @endif
                                    @if($viewListingData['is_verified_poster'])
                                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-black">
                                            ✔ Verified Poster
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <h4 class="text-lg font-black text-slate-950">{{ $viewListingData['title'] }}</h4>
                            <p class="text-xs text-slate-500 font-mono mt-0.5">Slug: {{ $viewListingData['slug'] }}</p>
                        </div>

                        <!-- Poster info -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-black uppercase text-slate-500 block">Posted By</span>
                                <span class="font-bold text-slate-900 text-xs">{{ $viewListingData['user_name'] ?? 'Anonymous' }}</span>
                                <span class="text-xs text-slate-500">({{ $viewListingData['user_email'] ?? 'No email' }})</span>
                            </div>
                            @if(!empty($viewListingData['user_id']))
                                <button type="button" wire:click="openViewUserModal({{ $viewListingData['user_id'] }})" class="px-3 py-1.5 rounded-lg bg-pink-50 hover:bg-pink-100 text-[#F20B83] border border-pink-200 text-xs font-bold transition">
                                    Inspect User Profile
                                </button>
                            @endif
                        </div>

                        <!-- Category & Location -->
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                                <span class="text-[10px] font-black uppercase text-slate-500 block">Category</span>
                                <span class="font-bold text-slate-900">{{ $viewListingData['category_name'] ?? 'Uncategorized' }}</span>
                            </div>
                            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                                <span class="text-[10px] font-black uppercase text-slate-500 block">Location</span>
                                <span class="font-bold text-slate-900">{{ $viewListingData['city_name'] ?? 'USA' }}{{ !empty($viewListingData['state_code']) ? ', ' . $viewListingData['state_code'] : '' }}</span>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <span class="text-[10px] font-black uppercase text-slate-500 block mb-1">Description</span>
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 leading-relaxed max-h-40 overflow-y-auto whitespace-pre-line">
                                {{ $viewListingData['description'] }}
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">Close</button>
                        </div>
                    </div>
                @endif

                {{-- CMS PAGE MODAL --}}
                @if($showCmsModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
                            <span>{{ $editingCmsId ? 'Edit CMS Article' : 'Create New CMS Page' }}</span>
                        </h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <form wire:submit="saveCmsPage" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Page Title *</label>
                            <input type="text" wire:model="cmsForm.title" class="{{ $input }}" placeholder="e.g. Terms of Service, Privacy Policy, Safety Tips" required>
                            @error('cmsForm.title')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">URL Slug</label>
                            <div class="flex items-center">
                                <span class="px-3 py-2 bg-slate-100 border border-r-0 border-slate-200 rounded-l-xl text-xs text-slate-500 font-mono">/page/</span>
                                <input type="text" wire:model="cmsForm.slug" class="w-full rounded-r-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 px-3 py-2 font-mono focus:bg-white focus:border-[#F20B83]" placeholder="terms-of-service">
                            </div>
                            @error('cmsForm.slug')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Content (HTML / Markdown) *</label>
                            <textarea wire:model="cmsForm.content" rows="8" class="{{ $input }} font-mono text-xs" placeholder="Article body, paragraphs, formatting..." required></textarea>
                            @error('cmsForm.content')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="cms_publish_check" wire:model="cmsForm.is_published" class="rounded border-slate-300 text-pink-600 focus:ring-pink-500">
                            <label for="cms_publish_check" class="text-xs font-bold text-slate-800 cursor-pointer">Publish immediately (visible to all public visitors at /page/{slug})</label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-100">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-[#F20B83] hover:bg-pink-600 text-white text-xs font-black shadow-md transition">Save CMS Page</button>
                        </div>
                    </form>
                @endif

                {{-- VIEW TRANSACTION RECEIPT MODAL --}}
                @if($showViewTransactionModal && $viewTransactionData)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center font-black text-sm">
                                💳
                            </span>
                            <div>
                                <h3 class="text-base font-black text-slate-950">
                                    Transaction Receipt
                                </h3>
                                <p class="text-xs font-mono text-slate-500">Ref: {{ $viewTransactionData['reference_id'] }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <div class="space-y-4">
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-black uppercase text-slate-500 block">Total Amount</span>
                                <span class="text-2xl font-black text-slate-950">{{ $viewTransactionData['amount_formatted'] }}</span>
                                <span class="text-xs text-slate-500 uppercase font-bold">{{ $viewTransactionData['currency'] }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-black uppercase text-slate-500 block mb-1">Status</span>
                                <span class="px-3 py-1 rounded-full text-xs font-black uppercase {{ $badge($viewTransactionData['status']) }}">
                                    {{ $viewTransactionData['status'] }}
                                </span>
                            </div>
                        </div>

                        <!-- Customer Details -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                            <span class="text-[10px] font-black uppercase text-slate-500 block">Customer Information</span>
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-900">{{ $viewTransactionData['user']['name'] }}</span>
                                <span class="text-slate-600">{{ $viewTransactionData['user']['email'] }}</span>
                            </div>
                        </div>

                        <!-- Gateway and Reconciliation -->
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                                <span class="text-[10px] font-black uppercase text-slate-500 block">Payment Gateway</span>
                                <span class="font-bold text-slate-900 uppercase">{{ $viewTransactionData['gateway'] }}</span>
                                @if(!empty($viewTransactionData['gateway_transaction_id']))
                                    <p class="text-[10px] font-mono text-slate-500 truncate mt-0.5">{{ $viewTransactionData['gateway_transaction_id'] }}</p>
                                @endif
                            </div>
                            <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                                <span class="text-[10px] font-black uppercase text-slate-500 block">Timestamp</span>
                                <span class="font-bold text-slate-900">{{ $viewTransactionData['created_at'] }}</span>
                                <span class="text-[10px] text-slate-500 block mt-0.5">{{ $viewTransactionData['is_reconciled'] ? '✓ Reconciled' : 'Unreconciled' }}</span>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">Close Receipt</button>
                        </div>
                    </div>
                @endif

                {{-- VIEW CRYPTO TRANSACTION MODAL --}}
                @if($showViewCryptoModal && $viewCryptoData)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-purple-50 border border-purple-200 text-purple-700 flex items-center justify-center font-black text-sm">
                                ⛓️
                            </span>
                            <div>
                                <h3 class="text-base font-black text-slate-950">
                                    Web3 Cryptocurrency Order
                                </h3>
                                <p class="text-xs font-mono text-slate-500">Invoice: {{ $viewCryptoData['order_id'] }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <div class="space-y-4">
                        <div class="p-4 bg-purple-50/60 border border-purple-200 rounded-2xl flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-black uppercase text-purple-700 block">Crypto Amount</span>
                                <span class="text-2xl font-black text-slate-950">{{ $viewCryptoData['expected_amount'] }} {{ $viewCryptoData['currency'] }}</span>
                                <span class="text-xs text-purple-800 font-bold">Network: {{ $viewCryptoData['network'] }} ({{ $viewCryptoData['fiat_amount'] }})</span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-black uppercase text-purple-700 block mb-1">Status</span>
                                <span class="px-3 py-1 rounded-full text-xs font-black uppercase {{ $badge($viewCryptoData['status']) }}">
                                    {{ $viewCryptoData['status'] }}
                                </span>
                            </div>
                        </div>

                        <!-- Customer Details -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                            <span class="text-[10px] font-black uppercase text-slate-500 block">User Account</span>
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-900">{{ $viewCryptoData['user']['name'] ?? 'Anonymous' }}</span>
                                <span class="text-slate-600">{{ $viewCryptoData['user']['email'] ?? '' }}</span>
                            </div>
                        </div>

                        <!-- Deposit Address -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                            <span class="text-[10px] font-black uppercase text-slate-500 block mb-1">Deposit Address</span>
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-xs font-bold text-slate-900 break-all select-all">{{ $viewCryptoData['payment_address'] }}</span>
                            </div>
                        </div>

                        <!-- Blockchain TX Hash & Explorer -->
                        @if(!empty($viewCryptoData['transaction_hash']))
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                                <span class="text-[10px] font-black uppercase text-slate-500 block mb-1">Transaction Hash</span>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-xs text-slate-700 break-all">{{ $viewCryptoData['transaction_hash'] }}</span>
                                    @if(!empty($viewCryptoData['explorer_url']))
                                        <a href="{{ $viewCryptoData['explorer_url'] }}" target="_blank" rel="noopener noreferrer" class="px-2.5 py-1 rounded-lg bg-pink-50 hover:bg-pink-100 text-[#F20B83] text-xs font-bold shrink-0 border border-pink-200">
                                            Explorer ↗
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">Close Details</button>
                        </div>
                    </div>
                @endif

                {{-- SUSPEND USER MODAL --}}
                @if($showSuspendUserModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950">Suspend User Account</h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>
                    <form wire:submit="suspendUser" class="space-y-3">
                        <label class="block text-xs font-black text-slate-800">Reason for suspension
                            <textarea wire:model="suspensionReason" rows="3" class="{{ $input }} mt-1" required placeholder="Specify violation, chargeback, fraud, etc..."></textarea>
                        </label>
                        @error('suspensionReason')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black shadow-md">Confirm Suspension</button>
                        </div>
                    </form>
                @endif

                {{-- ASSIGN ROLE MODAL --}}
                @if($showAssignRoleModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950">Assign Administrative Role</h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>
                    <form wire:submit="assignRole" class="space-y-3">
                        <label class="block text-xs font-black text-slate-800 mb-1">Select Role
                            <select wire:model="newRoleSlug" class="{{ $input }} text-xs font-bold mt-1" aria-label="Role">
                                @foreach(($assignableRoles ?? []) as $slug => $name)
                                    <option value="{{ $slug }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>
                        @error('newRoleSlug')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-[#F20B83] hover:bg-pink-600 text-white text-xs font-black shadow-md">Save Role</button>
                        </div>
                    </form>
                @endif

                {{-- REJECT LISTING MODAL --}}
                @if($showRejectListingModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950">Reject Classified Listing</h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>
                    <form wire:submit="rejectListing" class="space-y-3">
                        <label class="block text-xs font-black text-slate-800">Reason shown to the advertiser
                            <textarea wire:model="listingActionReason" rows="3" class="{{ $input }} mt-1" required placeholder="Specify why this listing violates terms..."></textarea>
                        </label>
                        @error('listingActionReason')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black shadow-md">Reject Listing</button>
                        </div>
                    </form>
                @endif

                {{-- DECIDE APPEAL MODAL --}}
                @if($showAppealModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950">Review Suspension Appeal #{{ $inspectedAppealData['id'] ?? $targetAppealId }}</h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>
                    <div class="space-y-3">
                        @if(!empty($inspectedAppealData))
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2 text-xs">
                                <div>
                                    <span class="text-[10px] font-black uppercase text-slate-500 block">Appellant</span>
                                    <span class="font-bold text-slate-900">{{ $inspectedAppealData['user_name'] }}</span>
                                    <span class="text-slate-500">({{ $inspectedAppealData['user_email'] }})</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-black uppercase text-slate-500 block">Original Suspension Reason</span>
                                    <p class="text-slate-800 font-medium">{{ $inspectedAppealData['suspension_reason'] }}</p>
                                </div>
                                <div>
                                    <span class="text-[10px] font-black uppercase text-slate-500 block">User Appeal Statement</span>
                                    <p class="text-slate-800 italic bg-white p-2 rounded-lg border border-slate-200">"{{ $inspectedAppealData['appeal_reason'] }}"</p>
                                </div>
                            </div>
                        @endif

                        <label class="block text-xs font-black text-slate-800">Decision Notes
                            <textarea wire:model="appealDecisionNotes" rows="3" class="{{ $input }} mt-1" placeholder="Internal or shared appeal review notes..."></textarea>
                        </label>
                        @error('appealDecisionNotes')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                            <button type="button" wire:click="decideAppeal('rejected')" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black shadow-md">Reject Appeal</button>
                            <button type="button" wire:click="decideAppeal('granted')" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md">Grant &amp; Reinstate</button>
                        </div>
                    </div>
                @endif

                {{-- INSPECT REPORT MODAL --}}
                @if($showInspectReportModal && $inspectReportData)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-rose-50 border border-rose-200 text-[#F20B83] font-black text-lg flex items-center justify-center shrink-0">
                                🚨
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-950 flex items-center gap-2">
                                    <span>Incident Report #{{ $inspectReportData['id'] }}</span>
                                    @if($inspectReportData['is_urgent_takedown'])
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-rose-600 text-white animate-pulse">URGENT TAKEDOWN</span>
                                    @endif
                                </h3>
                                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mt-0.5">
                                    <span class="font-bold text-slate-800">{{ ucwords(str_replace('_', ' ', $inspectReportData['reason_code'])) }}</span>
                                    <span>·</span>
                                    <span class="uppercase font-black text-[10px] px-1.5 py-0.2 rounded border {{ $inspectReportData['risk_level'] === 'critical' ? 'bg-rose-50 text-rose-700 border-rose-200' : ($inspectReportData['risk_level'] === 'high' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-600 border-slate-200') }}">
                                        {{ $inspectReportData['risk_level'] }} Risk
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-xl px-2">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs text-slate-800">
                        <!-- Target Entity Card -->
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                            <span class="text-[10px] uppercase font-black tracking-wider text-slate-400 block">Reported Target Entity</span>
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <div class="font-black text-sm text-slate-950">
                                        {{ $inspectReportData['target_title'] }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        Type: <strong class="text-slate-800">{{ $inspectReportData['target_details']['type'] ?? 'Entity' }}</strong>
                                        @if(isset($inspectReportData['target_details']['location']))
                                            · Location: <strong class="text-slate-800">{{ $inspectReportData['target_details']['location'] }}</strong>
                                        @endif
                                        @if(isset($inspectReportData['target_details']['status']))
                                            · Status: <span class="uppercase font-black text-[10px] px-1.5 py-0.2 rounded bg-white border border-slate-200">{{ $inspectReportData['target_details']['status'] }}</span>
                                        @endif
                                    </div>
                                </div>
                                @if($inspectReportData['target_url'])
                                    <a href="{{ $inspectReportData['target_url'] }}" target="_blank" class="px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-xs font-bold text-[#F20B83] shrink-0 inline-flex items-center gap-1 shadow-2xs transition">
                                        <span>View Public Ad</span>
                                        <span>↗</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Incident Narrative -->
                        <div class="p-3.5 bg-white rounded-2xl border border-slate-200 space-y-1.5">
                            <span class="text-[10px] uppercase font-black tracking-wider text-slate-400 block">Reporter Description &amp; Narrative</span>
                            <p class="text-xs text-slate-800 leading-relaxed font-medium bg-slate-50/50 p-2.5 rounded-xl border border-slate-100">
                                {{ $inspectReportData['details'] }}
                            </p>
                        </div>

                        <!-- Evidence Attachments if any -->
                        @if(!empty($inspectReportData['evidence_urls']))
                            <div class="p-3.5 bg-white rounded-2xl border border-slate-200 space-y-2">
                                <span class="text-[10px] uppercase font-black tracking-wider text-slate-400 block">Submitted Evidence ({{ count($inspectReportData['evidence_urls']) }})</span>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($inspectReportData['evidence_urls'] as $idx => $url)
                                        <a href="{{ $url }}" target="_blank" class="p-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 flex items-center justify-between transition group">
                                            <span class="text-xs font-bold text-slate-800 truncate">Evidence #{{ $idx + 1 }}</span>
                                            <span class="text-xs text-[#F20B83] group-hover:translate-x-0.5 transition">↗</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Reporter Telemetry & Audit Meta -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 p-3 bg-slate-50 rounded-2xl border border-slate-200 text-[11px]">
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Filed By:</span>
                                <span class="font-bold text-slate-900">{{ $inspectReportData['reporter_name'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Reporter IP:</span>
                                <span class="font-mono font-bold text-slate-900">{{ $inspectReportData['reporter_ip'] }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block text-[10px] uppercase">Submitted:</span>
                                <span class="font-semibold text-slate-700">{{ $inspectReportData['created_at'] }}</span>
                            </div>
                        </div>

                        @if($inspectReportData['resolution_notes'])
                            <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200 text-[11px] text-emerald-900">
                                <span class="font-bold block text-[10px] uppercase text-emerald-700">Resolution Finding / Notes:</span>
                                <p class="mt-0.5">{{ $inspectReportData['resolution_notes'] }}</p>
                                @if($inspectReportData['resolved_by'])
                                    <span class="block text-[10px] text-emerald-700 mt-1 font-semibold">Concluded by: {{ $inspectReportData['resolved_by'] }} on {{ $inspectReportData['resolved_at'] }}</span>
                                @endif
                            </div>
                        @endif

                        <!-- Modal Actions Footer -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-100">
                            <div>
                                @if($inspectReportData['target_is_listing'] && in_array($inspectReportData['status'], ['open', 'investigating']))
                                    <button 
                                        type="button" 
                                        wire:click="quarantineReportedListing({{ $inspectReportData['id'] }})"
                                        wire:confirm="Quarantine and immediately pull this listing from the live website?"
                                        class="px-3.5 py-2 text-xs font-black text-white bg-rose-700 hover:bg-rose-800 rounded-xl shadow-xs transition"
                                    >
                                        Quarantine Listing
                                    </button>
                                @endif
                                @if($inspectReportData['target_user_id'] && in_array($inspectReportData['status'], ['open', 'investigating']))
                                    <button 
                                        type="button" 
                                        wire:click="suspendReportedUser({{ $inspectReportData['id'] }})"
                                        wire:confirm="Suspend this advertiser's user account?"
                                        class="px-3.5 py-2 text-xs font-black text-rose-800 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition ml-1"
                                    >
                                        Suspend Advertiser
                                    </button>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                                    Close
                                </button>
                                @if($inspectReportData['status'] === 'open')
                                    <button 
                                        type="button" 
                                        wire:click="investigateReport({{ $inspectReportData['id'] }})"
                                        class="px-4 py-2 text-xs font-black text-purple-800 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition"
                                    >
                                        Investigate
                                    </button>
                                @endif
                                @if(in_array($inspectReportData['status'], ['open', 'investigating']))
                                    <button 
                                        type="button" 
                                        wire:click="openResolveReportModal({{ $inspectReportData['id'] }})"
                                        class="px-4 py-2 text-xs font-black text-white bg-[#F20B83] hover:bg-[#d00970] rounded-xl shadow-md transition"
                                    >
                                        Take Resolution Action
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- RESOLVE REPORT MODAL --}}
                @if($showResolveReportModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-rose-50 border border-rose-200 text-[#F20B83] flex items-center justify-center font-black text-sm">⚖️</span>
                            <h3 class="text-base font-black text-slate-950">Action Incident Report #{{ $targetReportId }}</h3>
                        </div>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>
                    <div class="space-y-4">
                        <!-- Resolution Action Selector -->
                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1.5">Resolution Decision *</label>
                            <div class="grid grid-cols-3 gap-2">
                                <label class="border rounded-xl p-3 cursor-pointer text-center transition {{ $reportResolutionAction === 'resolved' ? 'border-emerald-500 bg-emerald-50/50 text-emerald-900 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700' }}">
                                    <input type="radio" wire:model.live="reportResolutionAction" value="resolved" class="sr-only">
                                    <span class="block text-xs font-black">✓ Resolved</span>
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">Action taken &amp; closed</span>
                                </label>
                                <label class="border rounded-xl p-3 cursor-pointer text-center transition {{ $reportResolutionAction === 'investigating' ? 'border-purple-500 bg-purple-50/50 text-purple-900 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700' }}">
                                    <input type="radio" wire:model.live="reportResolutionAction" value="investigating" class="sr-only">
                                    <span class="block text-xs font-black">🔍 Investigating</span>
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">Keep in active review</span>
                                </label>
                                <label class="border rounded-xl p-3 cursor-pointer text-center transition {{ $reportResolutionAction === 'dismissed' ? 'border-slate-500 bg-slate-100 text-slate-900 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700' }}">
                                    <input type="radio" wire:model.live="reportResolutionAction" value="dismissed" class="sr-only">
                                    <span class="block text-xs font-black">✕ Dismiss</span>
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">False alarm / No violation</span>
                                </label>
                            </div>
                        </div>

                        <!-- Administrative Resolution Notes -->
                        <div>
                            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1">
                                Administrative Findings &amp; Notes
                            </label>
                            <textarea wire:model="reportResolutionNotes" rows="3" class="{{ $input }} text-xs font-medium" placeholder="Document moderation findings, action taken, or rationale for dismissal..."></textarea>
                        </div>

                        <!-- Attached Enforcement Checkboxes -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                            <span class="text-[10px] uppercase font-black text-slate-500 tracking-wider block">Attached Immediate Enforcement</span>
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-800">
                                <input type="checkbox" wire:model="reportQuarantineTarget" class="rounded text-[#F20B83] focus:ring-[#F20B83]">
                                <span>Immediately quarantine and unpublish reported listing</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-800">
                                <input type="checkbox" wire:model="reportSuspendTargetUser" class="rounded text-[#F20B83] focus:ring-[#F20B83]">
                                <span>Suspend reported advertiser user account (30 days)</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                                Cancel
                            </button>
                            <button type="button" wire:click="resolveReport" class="px-5 py-2 rounded-xl bg-[#F20B83] hover:bg-[#d00970] text-white text-xs font-black shadow-md transition">
                                Confirm &amp; Save Decision
                            </button>
                        </div>
                    </div>
                @endif

                {{-- ISSUE REFUND MODAL --}}
                @if($showRefundModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950">Issue Ledger Refund</h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>
                    <form wire:submit="issueRefund" class="space-y-3">
                        <label class="block text-xs font-black text-slate-800">Reason *
                            <input type="text" wire:model="refundReason" class="{{ $input }} mt-1" required maxlength="150" placeholder="e.g. Accidental duplicate payment, customer cancellation">
                        </label>
                        @error('refundReason')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror
                        <p class="text-[11px] text-slate-500">The full transaction amount will be refunded and recorded in the ledger and audit log.</p>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black shadow-md">Confirm Refund</button>
                        </div>
                    </form>
                @endif

                {{-- PRICING PLAN MODAL --}}
                @if($showPlanModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950">{{ $editingPlanId ? 'Edit Pricing Plan' : 'Create New Pricing Plan' }}</h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>
                    <form wire:submit="savePlan" class="space-y-3">
                        <label class="block text-xs font-black text-slate-800">Plan Name *
                            <input type="text" wire:model="planForm.name" class="{{ $input }} mt-1" placeholder="e.g. VIP 30-Day Boost" required>
                        </label>
                        @error('planForm.name')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror

                        <label class="block text-xs font-black text-slate-800">Slug
                            <input type="text" wire:model="planForm.slug" class="{{ $input }} mt-1" placeholder="auto-generated if blank">
                        </label>
                        @error('planForm.slug')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror

                        <label class="block text-xs font-black text-slate-800">Description
                            <textarea wire:model="planForm.description" rows="2" class="{{ $input }} mt-1" placeholder="Summary of perks and visibility..."></textarea>
                        </label>

                        <div class="grid grid-cols-3 gap-3">
                            <label class="block text-xs font-black text-slate-800">Price (USD)
                                <input type="number" step="0.01" min="0" wire:model="planForm.price" class="{{ $input }} mt-1" placeholder="19.99">
                            </label>
                            <label class="block text-xs font-black text-slate-800">Days
                                <input type="number" min="1" max="365" wire:model="planForm.duration_days" class="{{ $input }} mt-1" placeholder="30">
                            </label>
                            <label class="block text-xs font-black text-slate-800">Type
                                <select wire:model="planForm.plan_type" class="{{ $input }} text-xs font-bold mt-1">
                                    <option value="listing_upgrade">Listing upgrade</option>
                                    <option value="featured_spot">Featured spot</option>
                                    <option value="bump">Bump</option>
                                    <option value="membership">Membership</option>
                                </select>
                            </label>
                        </div>
                        @error('planForm.price')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror

                        <label class="flex items-center gap-2 text-xs font-bold text-slate-800 cursor-pointer pt-1">
                            <input type="checkbox" wire:model="planForm.is_active" class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]">
                            <span>Active Plan (Available for purchase)</span>
                        </label>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-[#F20B83] hover:bg-pink-600 text-white text-xs font-black shadow-md">Save Plan</button>
                        </div>
                    </form>
                @endif

                {{-- CREDIT PACKAGE CREATE/EDIT MODAL --}}
                @if($showPackageModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
                            <span>{{ $editingPackageId ? 'Edit Credit Package' : 'Create New Credit Package' }}</span>
                        </h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <form wire:submit="savePackage" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Package Name *</label>
                            <input type="text" wire:model="packageForm.name" class="{{ $input }}" placeholder="e.g. Starter Pack, Popular Pack" required>
                            @error('packageForm.name')<p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Price (USD) *</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500 font-bold">$</span>
                                    <input type="number" step="0.01" min="0.5" wire:model.live="packageForm.price_usd" class="{{ $input }} pl-7 font-mono font-bold" placeholder="9.99" required>
                                </div>
                                @error('packageForm.price_usd')<p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Display Order *</label>
                                <input type="number" min="1" wire:model="packageForm.display_order" class="{{ $input }} font-mono font-bold" required>
                                @error('packageForm.display_order')<p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Base Credits *</label>
                                <input type="number" min="1" wire:model.live="packageForm.credits" class="{{ $input }} font-mono font-bold" placeholder="100" required>
                                @error('packageForm.credits')<p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Bonus Credits (Free)</label>
                                <input type="number" min="0" wire:model.live="packageForm.bonus_credits" class="{{ $input }} font-mono font-bold" placeholder="0">
                                @error('packageForm.bonus_credits')<p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <!-- Calculation Preview Box -->
                        @php
                            $calcCredits = (int) ($packageForm['credits'] ?? 0);
                            $calcBonus = (int) ($packageForm['bonus_credits'] ?? 0);
                            $calcTotal = $calcCredits + $calcBonus;
                            $calcPrice = (float) ($packageForm['price_usd'] ?? 0);
                            $calcUnit = $calcTotal > 0 ? ($calcPrice / $calcTotal) : 0;
                        @endphp
                        <div class="bg-pink-50/60 border border-pink-200/80 rounded-2xl p-3.5 text-xs text-slate-800 space-y-1">
                            <div class="font-bold text-[#F20B83] text-[11px] uppercase tracking-wider">Live Package Metrics</div>
                            <div class="flex items-center justify-between text-slate-700">
                                <span>Total Credits Awarded:</span>
                                <span class="font-mono font-black text-slate-950">{{ number_format($calcTotal) }} credits</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-700">
                                <span>Effective Unit Cost:</span>
                                <span class="font-mono font-bold text-slate-900">${{ number_format($calcUnit, 4) }} / credit</span>
                            </div>
                        </div>

                        <div class="space-y-2 pt-2 border-t border-slate-100">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-800 cursor-pointer">
                                <input type="checkbox" wire:model="packageForm.is_popular" class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]">
                                <span>Mark as 'Most Popular' (Featured badge & highlighted pink border)</span>
                            </label>

                            <label class="flex items-center gap-2 text-xs font-bold text-slate-800 cursor-pointer">
                                <input type="checkbox" wire:model="packageForm.is_active" class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]">
                                <span>Active in Checkout (Visible to users on <code>/account/wallet</code>)</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-[#F20B83] hover:bg-pink-600 text-white text-xs font-black shadow-md transition">
                                {{ $editingPackageId ? 'Update Package' : 'Create Package' }}
                            </button>
                        </div>
                    </form>
                @endif

                {{-- PRICING RULE MODAL --}}
                @if($showPricingRuleModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950">{{ $editingPricingRuleId ? 'Edit Dynamic Rule' : 'New Pricing Rule' }}</h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>
                    <form wire:submit="savePricingRule" class="space-y-3">
                        <label class="block text-xs font-black text-slate-800">Rule Name *
                            <input type="text" wire:model="pricingRuleForm.rule_name" class="{{ $input }} mt-1" placeholder="e.g. California Weekend Promo" required>
                        </label>
                        @error('pricingRuleForm.rule_name')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-xs font-black text-slate-800">Discount (%)
                                <input type="number" min="0" max="100" wire:model="pricingRuleForm.discount_percentage" class="{{ $input }} mt-1" placeholder="20">
                            </label>
                            <label class="block text-xs font-black text-slate-800">Fixed Price ($)
                                <input type="number" step="0.01" min="0" wire:model="pricingRuleForm.fixed_price" class="{{ $input }} mt-1" placeholder="9.99">
                            </label>
                        </div>

                        <div class="flex items-center gap-4 text-xs font-bold text-slate-800 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="pricingRuleForm.is_free" class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]">
                                <span>Make Free</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="pricingRuleForm.is_active" class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]">
                                <span>Active</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-[#F20B83] hover:bg-pink-600 text-white text-xs font-black shadow-md">Save Rule</button>
                        </div>
                    </form>
                @endif

                {{-- SEO META MODAL --}}
                @if($showSeoModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950">{{ $editingSeoId ? 'Edit SEO Meta' : 'Add SEO Route' }}</h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>
                    <form wire:submit="saveSeo" class="space-y-3">
                        <label class="block text-xs font-black text-slate-800">Page Route *
                            <input type="text" wire:model="seoForm.page_route" class="{{ $input }} font-mono mt-1" placeholder="e.g. /, /search, /locations" required>
                        </label>
                        @error('seoForm.page_route')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror

                        <label class="block text-xs font-black text-slate-800">Meta Title *
                            <input type="text" wire:model="seoForm.meta_title" class="{{ $input }} mt-1" placeholder="SEO Title tag" required>
                        </label>
                        @error('seoForm.meta_title')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror

                        <label class="block text-xs font-black text-slate-800">Meta Description *
                            <textarea wire:model="seoForm.meta_description" rows="2" class="{{ $input }} mt-1" placeholder="Search engine snippet description..." required></textarea>
                        </label>
                        @error('seoForm.meta_description')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-xs font-black text-slate-800">Robots Directive
                                <select wire:model="seoForm.robots_directive" class="{{ $input }} text-xs font-bold mt-1">
                                    <option value="index, follow">index, follow</option>
                                    <option value="noindex, follow">noindex, follow</option>
                                    <option value="index, nofollow">index, nofollow</option>
                                    <option value="noindex, nofollow">noindex, nofollow</option>
                                </select>
                            </label>
                            <label class="block text-xs font-black text-slate-800">Keywords
                                <input type="text" wire:model="seoForm.meta_keywords" class="{{ $input }} mt-1" placeholder="dating, classifieds, local">
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-[#F20B83] hover:bg-pink-600 text-white text-xs font-black shadow-md">Save Meta</button>
                        </div>
                    </form>
                @endif

                {{-- CMS PREVIEW MODAL --}}
                @if($showPreviewCmsModal && $previewCmsData)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-black text-slate-950">{{ $previewCmsData['title'] }}</h3>
                                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $badge((bool) $previewCmsData['is_published']) }}">
                                    {{ $previewCmsData['is_published'] ? 'PUBLISHED' : 'DRAFT' }}
                                </span>
                            </div>
                            <p class="text-xs font-mono text-slate-500 mt-0.5">Route: /page/{{ $previewCmsData['slug'] }}</p>
                        </div>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <div class="space-y-4">
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 max-h-[60vh] overflow-y-auto prose prose-slate prose-sm max-w-none text-slate-800">
                            {!! $previewCmsData['content'] !!}
                        </div>

                        <div class="flex justify-between items-center pt-3 border-t border-slate-200 text-xs text-slate-500">
                            <span>Last updated: {{ $previewCmsData['updated_at'] }}</span>
                            <div class="flex gap-2">
                                <button type="button" wire:click="openCmsModal({{ $previewCmsData['id'] }})" class="px-4 py-1.5 rounded-lg bg-[#F20B83] hover:bg-pink-600 text-white font-bold text-xs">
                                    Edit Article
                                </button>
                                <button type="button" wire:click="closeModals" class="px-4 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-900 font-bold text-xs">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                @if($showAdModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
                            <span>{{ $editingAdId ? 'Edit Advertisement Placement' : 'Create New Ad Placement' }}</span>
                        </h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <form wire:submit="saveAd" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Campaign Name *</label>
                            <input type="text" wire:model="adForm.name" class="{{ $input }}" placeholder="e.g. VIP Club Sponsorship Banner" required>
                            @error('adForm.name')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Placement Slot *</label>
                                <select wire:model="adForm.slot" class="{{ $input }} text-xs font-bold text-slate-900" required>
                                    @foreach(\App\Modules\Admin\Models\AdPlacement::SLOTS as $slot)
                                        <option value="{{ $slot }}">{{ ucwords(str_replace('_', ' ', $slot)) }}</option>
                                    @endforeach
                                </select>
                                @error('adForm.slot')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Advertiser Name / Company</label>
                                <input type="text" wire:model="adForm.advertiser_name" class="{{ $input }}" placeholder="e.g. LocalDateHub Partners">
                                @error('adForm.advertiser_name')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Banner Image URL</label>
                            <input type="url" wire:model="adForm.image_url" class="{{ $input }}" placeholder="https://example.com/banners/ad-728x90.jpg">
                            @error('adForm.image_url')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Destination Target URL</label>
                            <input type="url" wire:model="adForm.target_url" class="{{ $input }}" placeholder="https://advertiser-site.com/offer">
                            @error('adForm.target_url')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Campaign Price / Sponsor Rate ($ USD)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-xs font-black text-slate-400">$</span>
                                    <input type="number" step="0.01" min="0" wire:model="adForm.price_dollars" class="{{ $input }} pl-7" placeholder="e.g. 250.00">
                                </div>
                                <span class="text-[10px] text-slate-500 font-medium">Flat fee or monthly rate for reporting.</span>
                                @error('adForm.price_dollars')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Slot Placement</label>
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800">
                                    @php
                                        $dims = [
                                            'homepage_top' => 'Leaderboard (970x250 / 728x90)',
                                            'sidebar' => 'Half-Page / Skyscraper (300x600)',
                                            'search_results' => 'In-Feed Native Card (728x90)',
                                            'listing_detail' => 'Showcase Header (728x90)',
                                            'footer' => 'Bottom Leaderboard (970x90)',
                                        ];
                                    @endphp
                                    <span class="text-pink-600 font-black">Dimensions:</span> {{ $dims[$adForm['slot'] ?? 'sidebar'] ?? 'Standard Banner' }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Custom HTML / Script Embed (Optional)</label>
                            <textarea wire:model="adForm.html_snippet" rows="2" class="{{ $input }} font-mono text-xs" placeholder="<!-- Optional Google AdSense, ExoClick, or Custom iFrame Code -->"></textarea>
                            <span class="text-[10px] text-slate-500 font-medium">Use if advertiser provided raw script or iframe tag instead of static image banner.</span>
                            @error('adForm.html_snippet')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Campaign Start Date</label>
                                <input type="date" wire:model="adForm.starts_at" class="{{ $input }}">
                                @error('adForm.starts_at')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Campaign End Date</label>
                                <input type="date" wire:model="adForm.ends_at" class="{{ $input }}">
                                @error('adForm.ends_at')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                            <div>
                                <span class="text-xs font-black text-slate-900 block">Placement Delivery Status</span>
                                <span class="text-[11px] text-slate-500 font-medium">When active, ad displays immediately to site visitors.</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="adForm.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-[#F20B83] hover:bg-[#D00972] text-white text-xs font-black transition shadow-xs">
                                {{ $editingAdId ? 'Update Placement' : 'Publish Ad Campaign' }}
                            </button>
                        </div>
                    </form>
                @endif

                {{-- DELETE AD MODAL --}}
                @if($showDeleteAdModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
                            <span class="text-rose-600">⚠️</span>
                            <span>Confirm Ad Campaign Deletion</span>
                        </h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <div class="space-y-4">
                        <p class="text-xs text-slate-700 font-medium leading-relaxed">
                            Are you sure you want to permanently delete advertisement campaign <strong class="text-slate-950 font-black">"{{ $deleteAdName }}"</strong>?
                            All associated impressions, clicks, and tracking metrics will be purged from the live rotation.
                        </p>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                                Cancel
                            </button>
                            <button type="button" wire:click="deleteAd" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black transition shadow-xs">
                                Delete Placement
                            </button>
                        </div>
                    </div>
                @endif

                {{-- PREVIEW AD MODAL --}}
                @if($showPreviewAdModal && $previewAdData)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-pink-100 text-pink-700 font-black text-sm">👁️</span>
                            <div>
                                <h3 class="text-base font-black text-slate-950">{{ $previewAdData['name'] }}</h3>
                                <p class="text-[11px] font-semibold text-slate-500">Live Creative Simulation · {{ ucwords(str_replace('_', ' ', $previewAdData['slot'])) }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <div class="space-y-4">
                        <!-- Simulated Slot Visual Container -->
                        <div class="p-4 rounded-2xl bg-slate-100 border border-slate-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Simulated Slot Viewport ({{ $previewAdData['slot'] }})</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black {{ $previewAdData['is_active'] ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $previewAdData['is_active'] ? '● Live Delivering' : 'Paused' }}
                                </span>
                            </div>

                            <div class="bg-white rounded-xl border border-slate-300 p-2 overflow-hidden flex items-center justify-center min-h-[140px] shadow-inner text-center">
                                @if(!empty($previewAdData['image_url']))
                                    <a href="{{ $previewAdData['target_url'] ?: '#' }}" target="_blank" rel="noopener" class="block group relative">
                                        <img src="{{ $previewAdData['image_url'] }}" alt="{{ $previewAdData['name'] }}" class="max-h-[220px] max-w-full rounded-lg object-contain mx-auto group-hover:opacity-95 transition" />
                                        <div class="absolute inset-0 bg-pink-600/10 opacity-0 group-hover:opacity-100 transition rounded-lg flex items-center justify-center">
                                            <span class="bg-white/90 text-slate-950 font-black text-xs px-2.5 py-1 rounded shadow">🔗 Test Click Link</span>
                                        </div>
                                    </a>
                                @elseif(!empty($previewAdData['html_snippet']))
                                    <div class="w-full text-left font-mono text-[11px] bg-slate-900 text-slate-100 p-3 rounded-lg overflow-x-auto">
                                        <p class="text-amber-400 font-bold mb-1">// Raw HTML / Script Tag Snippet:</p>
                                        <code>{{ $previewAdData['html_snippet'] }}</code>
                                    </div>
                                @else
                                    <div class="text-slate-400 font-semibold text-xs py-6">
                                        No image URL or HTML snippet provided for this placement.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Placement Specs Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                                <span class="text-[10px] font-black uppercase text-slate-500 block">Advertiser</span>
                                <span class="font-bold text-slate-900 truncate block mt-0.5">{{ $previewAdData['advertiser_name'] }}</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                                <span class="text-[10px] font-black uppercase text-slate-500 block">Sponsor Rate</span>
                                <span class="font-bold text-pink-700 block mt-0.5">{{ $previewAdData['price_formatted'] }}</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                                <span class="text-[10px] font-black uppercase text-slate-500 block">Impressions</span>
                                <span class="font-black text-slate-950 block mt-0.5">{{ number_format($previewAdData['impressions']) }}</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                                <span class="text-[10px] font-black uppercase text-slate-500 block">CTR / Clicks</span>
                                <span class="font-black text-emerald-700 block mt-0.5">{{ $previewAdData['ctr'] }}% ({{ number_format($previewAdData['clicks']) }})</span>
                            </div>
                        </div>

                        @if($previewAdData['target_url'])
                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                                <div class="truncate max-w-[80%]">
                                    <span class="text-[10px] font-black uppercase text-slate-500 block">Destination URL:</span>
                                    <span class="font-mono text-slate-800 text-[11px] truncate block">{{ $previewAdData['target_url'] }}</span>
                                </div>
                                <a href="{{ $previewAdData['target_url'] }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-pink-50 hover:bg-pink-100 text-[#F20B83] font-bold text-xs shrink-0 border border-pink-200">
                                    Visit Link ↗
                                </a>
                            </div>
                        @endif

                        <div class="flex justify-between items-center pt-3 border-t border-slate-200">
                            <span class="text-[11px] text-slate-500 font-medium">Window: {{ $previewAdData['starts_at'] }} → {{ $previewAdData['ends_at'] }}</span>
                            <div class="flex gap-2">
                                <button type="button" wire:click="openAdModal({{ $previewAdData['id'] }})" class="px-3.5 py-1.5 rounded-xl bg-[#F20B83] hover:bg-[#D00972] text-white text-xs font-black shadow-xs">
                                    Edit Campaign
                                </button>
                                <button type="button" wire:click="closeModals" class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold">
                                    Close Preview
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                @if($showAuditDetailModal && isset($inspectedAuditLog) && $inspectedAuditLog)
                    <h3 class="text-lg font-bold text-slate-900 mb-1">Audit Record #{{ $inspectedAuditLog->id }}</h3>
                    <p class="text-xs text-slate-400 mb-4"><span class="font-mono text-rose-600">{{ $inspectedAuditLog->action }}</span> · {{ $inspectedAuditLog->admin?->name ?? 'System' }} · {{ $inspectedAuditLog->created_at?->format('Y-m-d H:i:s') }}</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div><div class="text-xs font-bold text-slate-400 mb-1">Before</div><pre class="text-[11px] bg-slate-50 border border-slate-200 rounded-lg p-2 overflow-x-auto whitespace-pre-wrap">{{ json_encode($inspectedAuditLog->before_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'null' }}</pre></div>
                        <div><div class="text-xs font-bold text-slate-400 mb-1">After</div><pre class="text-[11px] bg-slate-50 border border-slate-200 rounded-lg p-2 overflow-x-auto whitespace-pre-wrap">{{ json_encode($inspectedAuditLog->after_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'null' }}</pre></div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-3">Target: {{ $inspectedAuditLog->target_type ?? '—' }} {{ $inspectedAuditLog->target_id ? '#' . $inspectedAuditLog->target_id : '' }} · IP {{ $inspectedAuditLog->ip_address }}</p>
                    <p class="text-[11px] text-slate-500 break-all">UA: {{ $inspectedAuditLog->user_agent }}</p>
                    <div class="flex justify-end pt-3"><button type="button" wire:click="closeModals" class="px-3 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-900 text-sm">Close</button></div>
                @endif

                @if($showDmcaModal)
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Process DMCA Takedown Notice</h3>
                    <form wire:submit="processDmca" class="space-y-3">
                        <label class="block text-sm">Decision Action
                            <select wire:model="dmcaAction" class="{{ $input }} mt-1">
                                <option value="takedown_issued">Issue Takedown (Remove Listing)</option>
                                <option value="counter_noticed">Counter-Notice Received</option>
                                <option value="rejected">Reject Notice (Deficient / Bad Faith)</option>
                                <option value="resolved">Mark Resolved</option>
                                <option value="restored">Restore Material</option>
                            </select>
                        </label>
                        <label class="block text-sm">Reviewer Notes
                            <textarea wire:model="dmcaNotes" rows="3" class="{{ $input }} mt-1" placeholder="Internal justification, claimant correspondence notes..."></textarea>
                        </label>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-600">Cancel</button>
                            <button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save Decision</button>
                        </div>
                    </form>
                @endif

                @if($showLegalModal)
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Law Enforcement / Legal Request Action</h3>
                    <form wire:submit="processLegalRequest" class="space-y-3">
                        <label class="block text-sm">Status Action
                            <select wire:model="legalStatusAction" class="{{ $input }} mt-1">
                                <option value="under_legal_review">Under Legal Review</option>
                                <option value="fulfilled">Fulfilled / Disclosed Pursuant to Process</option>
                                <option value="rejected">Rejected (Deficient / Quashed)</option>
                            </select>
                        </label>
                        <label class="block text-sm">Compliance &amp; Fulfillment Notes
                            <textarea wire:model="legalComplianceNotes" rows="4" class="{{ $input }} mt-1" required placeholder="Details on legal fulfillment, counsel sign-off, responsive records produced..."></textarea>
                        </label>
                        @error('legalComplianceNotes') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-600">Cancel</button>
                            <button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save Status</button>
                        </div>
                    </form>
                @endif

                @if($showStateComplianceModal)
                    <h3 class="text-lg font-bold text-slate-900 mb-3">{{ $editingStateComplianceId ? 'Edit' : 'New' }} State Compliance Rule</h3>
                    <form wire:submit="saveStateCompliance" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm">State Code
                                <input type="text" maxlength="2" wire:model="stateComplianceForm.state_code" class="{{ $input }} mt-1 uppercase font-mono" required>
                            </label>
                            <label class="block text-sm">State Name
                                <input type="text" wire:model="stateComplianceForm.state_name" class="{{ $input }} mt-1" required>
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm">Min Age
                                <input type="number" min="18" max="21" wire:model="stateComplianceForm.statutory_min_age" class="{{ $input }} mt-1" required>
                            </label>
                            <label class="block text-sm">Legal Reference
                                <input type="text" wire:model="stateComplianceForm.legal_reference" class="{{ $input }} mt-1" placeholder="e.g. TX HB 1181">
                            </label>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="stateComplianceForm.requires_id_verification" class="rounded border-slate-300 bg-slate-50 text-rose-600">
                            Require Mandatory Digital ID Verification
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="stateComplianceForm.requires_age_gate" class="rounded border-slate-300 bg-slate-50 text-rose-600">
                            Require 18+ Age Gate Affirmation
                        </label>
                        <label class="block text-sm">Mandatory Warning Text
                            <textarea wire:model="stateComplianceForm.mandatory_warning_text" rows="2" class="{{ $input }} mt-1"></textarea>
                        </label>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-600">Cancel</button>
                            <button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save Rule</button>
                        </div>
                    </form>
                @endif

                @if($showPreservationModal && $inspectedPreservation)
                    <h3 class="text-lg font-bold text-slate-900 mb-1">Evidence Hold #{{ $inspectedPreservation->id }}</h3>
                    <p class="text-xs text-slate-400 mb-3">{{ $inspectedPreservation->hold_reason }} · Frozen by {{ $inspectedPreservation->frozenBy?->name ?? 'System' }}</p>
                    <div class="text-xs font-bold text-slate-400 mb-1">Snapshot JSON Payload</div>
                    <pre class="text-[11px] bg-slate-50 border border-slate-200 rounded-lg p-3 max-h-60 overflow-y-auto whitespace-pre-wrap font-mono text-slate-600">{{ json_encode($inspectedPreservation->snapshot_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    <div class="flex justify-end pt-3">
                        <button type="button" wire:click="closeModals" class="px-3 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-900 text-sm">Close</button>
                    </div>
                @endif

                {{-- ASSIGN SUBSCRIPTION MODAL --}}
                @if($showAssignSubscriptionModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
                            <span>✨ Manually Assign Subscription</span>
                        </h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>
                    <form wire:submit="saveAssignedSubscription" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Target User / Member *</label>
                            <select wire:model="assignSubscriptionForm.user_id" class="{{ $input }}" required>
                                <option value="">-- Select Member --</option>
                                @foreach($assignableUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                            @error('assignSubscriptionForm.user_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Subscription Plan *</label>
                            <select wire:model="assignSubscriptionForm.pricing_plan_id" class="{{ $input }}" required>
                                <option value="">-- Select Plan Tier --</option>
                                @foreach($allPlans as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} (${{ number_format($p->price_cents / 100, 2) }} / {{ $p->duration_days }}d)</option>
                                @endforeach
                            </select>
                            @error('assignSubscriptionForm.pricing_plan_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Duration (Days) *</label>
                                <input type="number" min="1" max="730" wire:model="assignSubscriptionForm.duration_days" class="{{ $input }}" required>
                                @error('assignSubscriptionForm.duration_days')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Payment Gateway *</label>
                                <select wire:model="assignSubscriptionForm.gateway" class="{{ $input }}">
                                    <option value="manual_admin">Manual Admin Grant</option>
                                    <option value="crypto">In-House Crypto Gateway</option>
                                    <option value="stripe">Stripe</option>
                                    <option value="comped">Complimentary VIP</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="auto_renew_assign" wire:model="assignSubscriptionForm.auto_renew" class="rounded border-slate-300 text-pink-600 focus:ring-pink-500">
                            <label for="auto_renew_assign" class="text-xs font-bold text-slate-800 cursor-pointer">Enable Automatic Renewal at end of cycle</label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-100">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-[#F20B83] hover:bg-pink-600 text-white text-xs font-black shadow-md transition">Assign Membership</button>
                        </div>
                    </form>
                @endif

                {{-- VIEW SUBSCRIPTION DETAILS MODAL --}}
                @if($showViewSubscriptionModal && $viewSubscriptionData)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-black text-slate-950">Membership Record #{{ $viewSubscriptionData['id'] }}</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $badge($viewSubscriptionData['status']) }}">{{ strtoupper($viewSubscriptionData['status']) }}</span>
                        </div>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs text-slate-900">
                        <!-- Member & Plan Header -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                            <div>
                                <p class="text-slate-500 font-bold uppercase text-[10px] tracking-wider">Subscriber</p>
                                <p class="font-black text-slate-950 text-sm mt-0.5">{{ $viewSubscriptionData['user']['name'] ?? 'User #' . ($viewSubscriptionData['user']['id'] ?? 'N/A') }}</p>
                                <p class="text-slate-600 text-xs font-medium">{{ $viewSubscriptionData['user']['email'] ?? 'No email' }}</p>
                                <p class="text-[11px] text-slate-500 mt-1">Role: <span class="font-semibold text-slate-800">{{ strtoupper($viewSubscriptionData['user']['role'] ?? 'user') }}</span> · Wallet: <span class="font-mono font-bold text-emerald-700">{{ $viewSubscriptionData['user']['balance_credits'] ?? 0 }} credits</span></p>
                            </div>
                            <div>
                                <p class="text-slate-500 font-bold uppercase text-[10px] tracking-wider">Subscription Plan</p>
                                <p class="font-black text-slate-950 text-sm mt-0.5">{{ $viewSubscriptionData['plan']['name'] ?? 'Custom Plan' }}</p>
                                <p class="font-mono text-emerald-800 font-bold text-xs">{{ $viewSubscriptionData['plan']['price'] ?? '$0.00' }} / {{ $viewSubscriptionData['plan']['duration_days'] ?? 30 }} days</p>
                                <p class="text-[11px] text-slate-500 mt-1">{{ $viewSubscriptionData['plan']['description'] ?: 'Active membership plan' }}</p>
                            </div>
                        </div>

                        <!-- Technical & Gateway Attributes -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                <span class="text-slate-500 font-bold uppercase text-[10px] block">Gateway</span>
                                <span class="font-black text-slate-950 uppercase mt-0.5 block">{{ $viewSubscriptionData['gateway'] }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                <span class="text-slate-500 font-bold uppercase text-[10px] block">Auto-Renew Status</span>
                                <span class="font-black mt-0.5 block {{ $viewSubscriptionData['auto_renew'] ? 'text-emerald-700' : 'text-slate-600' }}">
                                    {{ $viewSubscriptionData['auto_renew'] ? '✓ ENABLED' : '✕ DISABLED' }}
                                </span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                <span class="text-slate-500 font-bold uppercase text-[10px] block">Gateway Reference ID</span>
                                <span class="font-mono text-[11px] text-slate-700 truncate mt-0.5 block">{{ $viewSubscriptionData['gateway_subscription_id'] ?: 'N/A' }}</span>
                            </div>
                        </div>

                        <!-- Lifecycle Dates -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1.5 font-mono text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500 font-sans font-medium">Activation Date:</span>
                                <span class="font-bold text-slate-900">{{ $viewSubscriptionData['starts_at'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 font-sans font-medium">Current Expiration:</span>
                                <span class="font-bold text-slate-900">{{ $viewSubscriptionData['ends_at'] ?? 'Open / Never' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 font-sans font-medium">Next Scheduled Renewal:</span>
                                <span class="font-bold text-slate-900">{{ $viewSubscriptionData['next_renewal_at'] ?? 'None' }}</span>
                            </div>
                            @if($viewSubscriptionData['canceled_at'])
                                <div class="flex justify-between text-rose-700">
                                    <span class="font-sans font-medium">Canceled Date:</span>
                                    <span class="font-bold">{{ $viewSubscriptionData['canceled_at'] }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Included Features -->
                        @if(!empty($viewSubscriptionData['plan']['features']))
                            <div class="p-3 bg-white rounded-xl border border-slate-200">
                                <span class="text-slate-500 font-bold uppercase text-[10px] block mb-1.5">Included Membership Perks</span>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach((array) $viewSubscriptionData['plan']['features'] as $feat)
                                        <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-800 text-[11px] font-semibold">✓ {{ $feat }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Footer Actions -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-3 border-t border-slate-200">
                            <div class="flex items-center gap-2">
                                @if($this->userCan('billing.manage_pricing'))
                                    <button type="button" wire:click="extendSubscription({{ $viewSubscriptionData['id'] }}, 30)"
                                        class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-600 text-white font-bold text-xs shadow-xs transition">
                                        +30 Days Extension
                                    </button>
                                    @if($viewSubscriptionData['status'] === 'active')
                                        <button type="button" wire:click="cancelSubscription({{ $viewSubscriptionData['id'] }})"
                                            wire:confirm="Cancel this subscription immediately?"
                                            class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-xs transition">
                                            Cancel Membership
                                        </button>
                                    @endif
                                @endif
                            </div>
                            <button type="button" wire:click="closeModals" class="px-4 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-900 font-bold text-xs transition">Close</button>
                        </div>
                    </div>
                @endif

                {{-- INSPECT VERIFICATION MODAL --}}
                @if($showInspectVerificationModal && $inspectedVerificationData)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-black text-slate-950">Age Assurance Record #{{ $inspectedVerificationData['id'] }}</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $badge($inspectedVerificationData['status']) }}">{{ strtoupper($inspectedVerificationData['status']) }}</span>
                        </div>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>

                    <div class="space-y-4 text-xs text-slate-900">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                            <div>
                                <p class="text-slate-500 font-bold uppercase text-[10px] tracking-wider">Member Details</p>
                                <p class="font-black text-slate-950 text-sm mt-0.5">{{ $inspectedVerificationData['user']['name'] }}</p>
                                <p class="text-slate-600 text-xs">{{ $inspectedVerificationData['user']['email'] }}</p>
                                <p class="text-[11px] text-slate-500 mt-1">Status: <span class="font-bold text-slate-800">{{ strtoupper($inspectedVerificationData['user']['account_status'] ?? 'active') }}</span></p>
                            </div>
                            <div>
                                <p class="text-slate-500 font-bold uppercase text-[10px] tracking-wider">Declared Birthdate & Age</p>
                                <p class="font-black text-slate-950 text-sm mt-0.5">{{ $inspectedVerificationData['declared_birthdate'] ?: 'Not declared' }}</p>
                                @if($inspectedVerificationData['age'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold mt-1 border {{ $inspectedVerificationData['age'] >= 18 ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' }}">
                                        {{ $inspectedVerificationData['age'] }} Years Old ({{ $inspectedVerificationData['age'] >= 18 ? 'Adult 18+' : 'Underage' }})
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                <span class="text-slate-500 font-bold uppercase text-[10px] block">Assurance Method</span>
                                <span class="font-black text-slate-950 uppercase mt-0.5 block">{{ str_replace('_', ' ', $inspectedVerificationData['method']) }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                <span class="text-slate-500 font-bold uppercase text-[10px] block">Third-Party Ref</span>
                                <span class="font-mono text-slate-800 text-[11px] truncate mt-0.5 block">{{ $inspectedVerificationData['third_party_reference'] ?: 'N/A' }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                <span class="text-slate-500 font-bold uppercase text-[10px] block">Reviewer</span>
                                <span class="font-bold text-slate-900 mt-0.5 block">{{ $inspectedVerificationData['reviewer'] ?: 'Pending Review' }}</span>
                            </div>
                        </div>

                        @if(!empty($inspectedVerificationData['metadata']))
                            <div class="p-3 bg-white rounded-xl border border-slate-200">
                                <span class="text-slate-500 font-bold uppercase text-[10px] block mb-1.5">Compliance Evidence & Security Metadata</span>
                                <pre class="text-[11px] bg-slate-50 p-2.5 rounded-lg border border-slate-100 font-mono text-slate-700 whitespace-pre-wrap max-h-40 overflow-y-auto">{{ json_encode($inspectedVerificationData['metadata'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        @endif

                        <div class="flex justify-between items-center pt-3 border-t border-slate-200">
                            <div class="flex gap-2">
                                @if($inspectedVerificationData['status'] === 'pending' && $this->userCan('verification.manage'))
                                    <button type="button" wire:click="approveAgeVerification({{ $inspectedVerificationData['id'] }})"
                                        wire:confirm="Confirm approval of 18+ age verification for this member?"
                                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xs transition">
                                        ✓ Approve 18+ Age
                                    </button>
                                    <button type="button" wire:click="openRejectVerificationModal({{ $inspectedVerificationData['id'] }})"
                                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-xs transition">
                                        ✕ Reject Request
                                    </button>
                                @endif
                            </div>
                            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-900 font-bold text-xs transition">Close</button>
                        </div>
                    </div>
                @endif

                {{-- MANUAL VERIFY MODAL --}}
                @if($showManualVerifyModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
                            <span>🛡️ Manually Verify 18+ Member</span>
                        </h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>
                    <form wire:submit="saveManualVerification" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Target Member *</label>
                            <select wire:model="manualVerifyForm.user_id" class="{{ $input }}" required>
                                <option value="">-- Select Member --</option>
                                @foreach($assignableUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                            @error('manualVerifyForm.user_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Verified Date of Birth *</label>
                                <input type="date" wire:model="manualVerifyForm.declared_birthdate" class="{{ $input }}" required max="{{ now()->subYears(18)->format('Y-m-d') }}">
                                @error('manualVerifyForm.declared_birthdate')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Assurance Method *</label>
                                <select wire:model="manualVerifyForm.method" class="{{ $input }}">
                                    <option value="digital_id">Physical/Digital ID Card Check</option>
                                    <option value="credit_card_token">Credit Card Authorization</option>
                                    <option value="third_party_reference">Third-Party KYC Identity Service</option>
                                    <option value="self_declaration">Self Affirmation (Signed)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Administrative Notes & Reference</label>
                            <textarea wire:model="manualVerifyForm.admin_notes" rows="2" class="{{ $input }}" placeholder="e.g. Government issued driver's license verified manually by compliance officer."></textarea>
                            @error('manualVerifyForm.admin_notes')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-100">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-[#F20B83] hover:bg-pink-600 text-white text-xs font-black shadow-md transition">Grant Age Verification</button>
                        </div>
                    </form>
                @endif

                {{-- REJECT VERIFICATION MODAL --}}
                @if($showRejectVerificationModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-rose-700 flex items-center gap-2">
                            <span>✕ Reject Age Assurance Request</span>
                        </h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>
                    <div class="space-y-4">
                        <p class="text-xs text-slate-600">Please provide a reason for rejecting this age assurance submission. The decision will be recorded in the audit logs.</p>
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Rejection Reason *</label>
                            <select wire:model="rejectionReason" class="{{ $input }}">
                                <option value="Underage: Declared birthdate indicates age is under statutory threshold of 18.">Underage: Declared age is under 18</option>
                                <option value="Invalid Identification: Document image was illegible, expired, or failed security checks.">Invalid Identification: Document illegible / expired</option>
                                <option value="Facial Match Failure: Selfie does not match photo on government document.">Facial Match Failure: Selfie does not match document</option>
                                <option value="Fraud Suspicion: Forged credentials or mismatched identity details.">Fraud Suspicion: Forged credentials or mismatch</option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-100">Cancel</button>
                            <button type="button" wire:click="confirmRejectVerification" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-black shadow-md transition">Confirm Rejection</button>
                        </div>
                    </div>
                @endif

                {{-- BROADCAST NOTIFICATION MODAL --}}
                @if($showBroadcastModal)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                        <h3 class="text-lg font-black text-slate-950 flex items-center gap-2">
                            <span>📢 Send Broadcast Notification</span>
                        </h3>
                        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
                    </div>
                    <form wire:submit="sendBroadcastNotification" class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Target Audience *</label>
                                <select wire:model.live="broadcastForm.target_audience" class="{{ $input }}">
                                    <option value="all_users">All Registered Members</option>
                                    <option value="active_only">Active Accounts Only</option>
                                    <option value="specific_user">Single Specific Member</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Delivery Channel *</label>
                                <select wire:model="broadcastForm.channel" class="{{ $input }}">
                                    <option value="database">In-App Notification Feed</option>
                                    <option value="email">Email Dispatch</option>
                                    <option value="sms">SMS Text Alert</option>
                                </select>
                            </div>
                        </div>

                        @if($broadcastForm['target_audience'] === 'specific_user')
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Target Member *</label>
                                <select wire:model="broadcastForm.specific_user_id" class="{{ $input }}" required>
                                    <option value="">-- Select Member --</option>
                                    @foreach($assignableUsers as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                    @endforeach
                                </select>
                                @error('broadcastForm.specific_user_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Notification Subject *</label>
                            <input type="text" wire:model="broadcastForm.subject" class="{{ $input }}" placeholder="e.g. Important Platform Update: New Security Features" required>
                            @error('broadcastForm.subject')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Notification Body / Message *</label>
                            <textarea wire:model="broadcastForm.message" rows="4" class="{{ $input }}" placeholder="Write message to be delivered to user(s)..." required></textarea>
                            @error('broadcastForm.message')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-100">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black shadow-md transition">Dispatch Broadcast Now</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>

