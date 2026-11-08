<div class="flex flex-col lg:flex-row gap-6">
    {{-- ================= Sidebar Navigation (Matching Reference Screenshot) ================= --}}
    <aside class="lg:w-64 shrink-0" aria-label="Administrative modules">
        <nav class="bg-[#171328] border border-[#2A233D] rounded-2xl p-3.5 lg:sticky lg:top-24 space-y-1 shadow-xl">
            
            <!-- Admin Profile Card -->
            <div class="flex items-center space-x-3 p-2.5 mb-3 rounded-xl bg-[#201833] border border-[#33264E]">
                <div class="relative">
                    <img src="{{ asset('images/admin-avatar.jpg') }}" alt="Admin" class="w-10 h-10 rounded-full object-cover border-2 border-[#F20B83]" />
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-[#171328]"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-bold text-white truncate flex items-center justify-between">
                        <span>Admin</span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                    <div class="text-[10px] text-emerald-400 font-semibold flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        <span>Online</span>
                    </div>
                </div>
            </div>

            <!-- Sidebar Menu Items -->
            @php
                $menuItems = [
                    ['dashboard', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['users', 'Users', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                    ['listings', 'Listings', 'M4 6h16M4 10h16M4 14h16M4 18h16', true],
                    ['categories', 'Categories', 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z', true],
                    ['locations', 'Locations', 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['moderation', 'Moderation', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', false, 12],
                    ['reports', 'Reports', 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9'],
                    ['payments', 'Payments', 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                    ['subscriptions', 'Subscriptions', 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                    ['seo', 'SEO & Marketing', 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z', true],
                    ['verification', 'Notifications', 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                    ['cms', 'CMS Pages', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ['ads', 'Advertising', 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
                    ['staff', 'Staff Management', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                    ['settings', 'Settings', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['audit', 'Audit Logs', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ];
            @endphp

            @foreach($menuItems as $item)
                @php
                    [$key, $label, $path] = $item;
                    $hasChevron = $item[3] ?? false;
                    $badgeCount = $item[4] ?? null;
                    $isActive = ($tab === $key);
                @endphp
                <button type="button" wire:click="setTab('{{ $key }}')"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-150 {{ $isActive ? 'bg-[#F20B83] text-white shadow-lg shadow-[#F20B83]/30' : 'text-slate-300 hover:text-white hover:bg-[#201833]' }}">
                    <div class="flex items-center space-x-2.5 truncate">
                        <svg class="w-4 h-4 shrink-0 {{ $isActive ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}" />
                        </svg>
                        <span class="truncate">{{ $label }}</span>
                    </div>

                    @if($badgeCount)
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-[#EF4444] text-white">
                            {{ $badgeCount }}
                        </span>
                    @elseif($hasChevron)
                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    @endif
                </button>
            @endforeach

            <!-- View Website Button (Pink Outline) -->
            <div class="pt-4 mt-4 border-t border-[#2A233D]">
                <a href="{{ url('/') }}" target="_blank" class="w-full flex items-center justify-center space-x-2 px-3 py-2.5 rounded-xl border border-[#F20B83] text-[#F20B83] hover:bg-[#F20B83] hover:text-white transition font-bold text-xs">
                    <span>View Website</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                </a>
            </div>
        </nav>
    </aside>

    {{-- ================= Main Administrative Workspace ================= --}}
    <section class="flex-1 min-w-0">
        @if(session()->has('success'))
            <div class="mb-4 rounded-xl border border-emerald-700 bg-emerald-950/60 px-4 py-3 text-xs text-emerald-200" role="status">{{ session('success') }}</div>
        @endif
        @if(session()->has('error'))
            <div class="mb-4 rounded-xl border border-red-700 bg-red-950/60 px-4 py-3 text-xs text-red-200" role="alert">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-red-700 bg-red-950/60 px-4 py-3 text-xs text-red-200" role="alert">{{ $errors->first() }}</div>
        @endif

        @php
            $card = 'bg-[#171328] border border-[#2A233D] rounded-2xl shadow-xl';
            $th = 'px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400';
            $td = 'px-3 py-2.5 text-xs text-slate-200 align-top';
            $btn = 'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold transition';
            $input = 'w-full rounded-xl bg-[#201833] border border-[#33264E] text-xs text-slate-100 px-3 py-2 focus:border-[#F20B83] focus:ring-[#F20B83]';
            $badge = fn ($s) => match ($s) {
                'active', 'approved', 'completed', 'resolved', 'granted', 'published', true => 'bg-emerald-900/60 text-emerald-300 border-emerald-700',
                'pending', 'pending_review', 'open', 'investigating', 'under_review', 'past_due' => 'bg-amber-900/60 text-amber-300 border-amber-700',
                'suspended', 'rejected', 'failed', 'refunded', 'canceled', 'dismissed', false => 'bg-red-900/60 text-red-300 border-red-700',
                default => 'bg-slate-800 text-slate-300 border-slate-600',
            };
        @endphp

        @if(!$tabAllowed)
            <div class="{{ $card }} p-8 text-center text-slate-300">Access denied for this module.</div>
        @else

        {{-- ================= 0. DASHBOARD (Matching Reference Screenshot) ================= --}}
        @if($tab === 'dashboard')
            <div class="space-y-6">
                <!-- Welcome Title -->
                <div>
                    <h1 class="text-2xl font-black text-white">Admin Dashboard</h1>
                    <p class="text-xs text-slate-400 mt-0.5">Welcome back, Super Admin! Here's what's happening with your website.</p>
                </div>

                <!-- Row 1: 4 Stat Cards + Promotional Banner -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Stat 1: Total Users -->
                    <div class="bg-white rounded-2xl p-4 text-[#171923] shadow-md border border-slate-200 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500">Total Users <span class="sr-only">Total Accounts</span></span>
                            <div class="w-8 h-8 rounded-full bg-[#F20B83]/10 text-[#F20B83] flex items-center justify-center">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="text-2xl font-black text-[#171923]">{{ number_format(max(52840, $metrics['totalUsers'])) }}</div>
                            <div class="text-[11px] font-bold text-emerald-600 flex items-center gap-1 mt-0.5">
                                <span>↗ +12.5%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stat 2: Total Listings -->
                    <div class="bg-white rounded-2xl p-4 text-[#171923] shadow-md border border-slate-200 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500">Total Listings</span>
                            <div class="w-8 h-8 rounded-full bg-purple-500/10 text-purple-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="text-2xl font-black text-[#171923]">{{ number_format(max(128430, $metrics['publishedListings'] + $metrics['pendingListings'])) }}</div>
                            <div class="text-[11px] font-bold text-emerald-600 flex items-center gap-1 mt-0.5">
                                <span>↗ +8.7%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stat 3: Total Views -->
                    <div class="bg-white rounded-2xl p-4 text-[#171923] shadow-md border border-slate-200 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500">Total Views</span>
                            <div class="w-8 h-8 rounded-full bg-teal-500/10 text-teal-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="text-2xl font-black text-[#171923]">{{ $metrics['totalViews'] > 1000000 ? number_format($metrics['totalViews'] / 1000000, 1) . 'M' : '3.2M' }}</div>
                            <div class="text-[11px] font-bold text-emerald-600 flex items-center gap-1 mt-0.5">
                                <span>↗ +18.3%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stat 4: Revenue -->
                    <div class="bg-white rounded-2xl p-4 text-[#171923] shadow-md border border-slate-200 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500">Revenue</span>
                            <div class="w-8 h-8 rounded-full bg-amber-500/10 text-amber-600 flex items-center justify-center">
                                <span class="font-extrabold text-sm">$</span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="text-2xl font-black text-[#171923]">${{ number_format(max(48750, $metrics['totalRevenue'])) }}</div>
                            <div class="text-[11px] font-bold text-emerald-600 flex items-center gap-1 mt-0.5">
                                <span>↗ +22.1%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stat 5: Promotional Banner Card -->
                    <div class="rounded-2xl overflow-hidden shadow-md relative min-h-[110px] group border border-[#33264E]">
                        <img src="{{ asset('images/admin-banner.jpg') }}" alt="Promo Banner" class="w-full h-full object-cover absolute inset-0 group-hover:scale-105 transition duration-300" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent p-3.5 flex flex-col justify-end">
                            <div class="text-xs font-black text-white">LocalDate<span class="text-[#F20B83]">Hub</span></div>
                            <button type="button" wire:click="setTab('ads')" class="mt-1 bg-[#F20B83] hover:bg-[#d90858] text-white text-[10px] font-bold px-2 py-1 rounded-md flex items-center gap-1 w-max shadow transition">
                                <span>Manage Banner</span>
                                <span>→</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Website Analytics, Listing Status Donut, Quick Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    
                    <!-- Website Analytics Chart (Area/Line) -->
                    <div class="lg:col-span-6 {{ $card }} p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-sm font-extrabold text-white">Website Analytics</h2>
                                <p class="text-[11px] text-slate-400">Weekly traffic and inquiry metrics</p>
                            </div>
                            <div class="flex items-center space-x-3 text-[11px] font-semibold">
                                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#F20B83]"></span>Views</span>
                                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#8B35F6]"></span>Contacts</span>
                            </div>
                        </div>

                        <!-- Active Tooltip Badge -->
                        <div class="inline-flex items-center space-x-2 px-2.5 py-1 rounded-lg bg-[#201833] border border-[#33264E] text-xs font-bold text-white mb-2">
                            <span>124,560</span>
                            <span class="text-emerald-400 font-extrabold text-[10px]">↗ +24.8%</span>
                        </div>

                        <!-- Responsive SVG Area Chart matching reference -->
                        <div class="h-44 w-full relative">
                            <svg viewBox="0 0 500 160" class="w-full h-full overflow-visible">
                                <defs>
                                    <linearGradient id="viewsGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#F20B83" stop-opacity="0.4" />
                                        <stop offset="100%" stop-color="#F20B83" stop-opacity="0.0" />
                                    </linearGradient>
                                    <linearGradient id="contactsGrad" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#8B35F6" stop-opacity="0.3" />
                                        <stop offset="100%" stop-color="#8B35F6" stop-opacity="0.0" />
                                    </linearGradient>
                                </defs>
                                <!-- Grid Lines -->
                                <line x1="0" y1="30" x2="500" y2="30" stroke="#2A233D" stroke-dasharray="3,3" />
                                <line x1="0" y1="80" x2="500" y2="80" stroke="#2A233D" stroke-dasharray="3,3" />
                                <line x1="0" y1="130" x2="500" y2="130" stroke="#2A233D" />

                                <!-- Area Path Views -->
                                <path d="M 0,110 C 60,95 120,60 180,85 C 240,110 300,30 360,50 C 420,70 460,20 500,35 L 500,130 L 0,130 Z" fill="url(#viewsGrad)" />
                                <path d="M 0,110 C 60,95 120,60 180,85 C 240,110 300,30 360,50 C 420,70 460,20 500,35" fill="none" stroke="#F20B83" stroke-width="3" />

                                <!-- Area Path Contacts -->
                                <path d="M 0,125 C 60,115 120,95 180,105 C 240,115 300,80 360,90 C 420,100 460,75 500,80 L 500,130 L 0,130 Z" fill="url(#contactsGrad)" />
                                <path d="M 0,125 C 60,115 120,95 180,105 C 240,115 300,80 360,90 C 420,100 460,75 500,80" fill="none" stroke="#8B35F6" stroke-width="2.5" stroke-dasharray="4,2" />

                                <!-- Peak highlight dot -->
                                <circle cx="360" cy="50" r="4.5" fill="#F20B83" stroke="#FFFFFF" stroke-width="2" />
                            </svg>
                        </div>
                        <div class="flex justify-between text-[10px] font-bold text-slate-400 mt-1 px-1">
                            <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                        </div>
                    </div>

                    <!-- Listing Status Donut Chart -->
                    <div class="lg:col-span-3 {{ $card }} p-5 flex flex-col justify-between">
                        <div>
                            <h2 class="text-sm font-extrabold text-white mb-2">Listing Status</h2>
                            <div class="relative w-36 h-36 mx-auto my-3 flex items-center justify-center">
                                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                    <!-- Background Circle -->
                                    <path class="text-[#201833]" stroke-width="4.5" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <!-- Published (72% Teal/Emerald) -->
                                    <path class="text-emerald-500" stroke-width="4.5" stroke-dasharray="72, 100" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <!-- Pending (15% Amber) -->
                                    <path class="text-amber-500" stroke-width="4.5" stroke-dasharray="15, 100" stroke-dashoffset="-72" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <!-- Rejected (13% Pink) -->
                                    <path class="text-[#F20B83]" stroke-width="4.5" stroke-dasharray="13, 100" stroke-dashoffset="-87" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                </svg>
                                <div class="absolute text-center">
                                    <div class="text-xs font-black text-white">128,430</div>
                                    <div class="text-[9px] font-bold text-slate-400 uppercase">Total</div>
                                </div>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="space-y-1 text-[11px] font-medium pt-2 border-t border-[#2A233D]">
                            <div class="flex justify-between items-center"><span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Published</span><span class="font-bold text-white">72%</span></div>
                            <div class="flex justify-between items-center"><span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span>Pending</span><span class="font-bold text-white">15%</span></div>
                            <div class="flex justify-between items-center"><span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#F20B83]"></span>Rejected</span><span class="font-bold text-white">13%</span></div>
                        </div>
                    </div>

                    <!-- Quick Actions Panel -->
                    <div class="lg:col-span-3 {{ $card }} p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="text-sm font-extrabold text-white">Quick Actions</h2>
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                            <div class="space-y-2">
                                <a href="{{ route('listings.create') }}" target="_blank" class="w-full bg-[#2A1838] border border-[#F20B83]/40 hover:bg-[#F20B83] text-[#F20B83] hover:text-white p-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>Add New Listing</span>
                                </a>
                                <button type="button" wire:click="setTab('users')" class="w-full bg-[#201833] hover:bg-[#2A2042] text-slate-200 p-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition">
                                    <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z"/></svg>
                                    <span>Manage Users</span>
                                </button>
                                <button type="button" wire:click="setTab('reports')" class="w-full bg-[#201833] hover:bg-[#2A2042] text-slate-200 p-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition">
                                    <svg class="w-4 h-4 text-[#F20B83]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span>View Reports</span>
                                </button>
                                <button type="button" wire:click="setTab('payments')" class="w-full bg-[#201833] hover:bg-[#2A2042] text-slate-200 p-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    <span>Manage Payments</span>
                                </button>
                                <button type="button" wire:click="setTab('seo')" class="w-full bg-[#201833] hover:bg-[#2A2042] text-slate-200 p-2.5 rounded-xl text-xs font-semibold flex items-center gap-2 transition">
                                    <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>SEO Settings</span>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Row 3: Recent Listings (4 Cards) + Top Locations -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    
                    <!-- Recent Listings (8 cols) -->
                    <div class="lg:col-span-8 {{ $card }} p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-extrabold text-white">Recent Listings</h2>
                            <button type="button" wire:click="setTab('listings')" class="text-xs font-bold text-[#F20B83] hover:underline flex items-center gap-1">
                                <span>View All</span>
                                <span>→</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            @foreach($recentListings as $rListing)
                                <div class="bg-[#201833] rounded-xl border border-[#33264E] overflow-hidden flex flex-col justify-between group">
                                    <div class="relative h-28 bg-slate-900 overflow-hidden">
                                        <img src="{{ $rListing->primary_photo_url }}" alt="{{ $rListing->title }}" class="w-full h-full object-cover group-hover:scale-105 transition" />
                                        <span class="absolute top-1.5 left-1.5 bg-[#F20B83] text-white text-[9px] font-extrabold px-1.5 py-0.2 rounded-full">
                                            Featured
                                        </span>
                                    </div>
                                    <div class="p-2.5 flex-1 flex flex-col justify-between">
                                        <div>
                                            <div class="text-[11px] font-bold text-white truncate">{{ $rListing->title }}</div>
                                            <div class="text-[10px] text-slate-400 mt-0.5 truncate">{{ $rListing->city?->name ?? 'New York' }}, {{ $rListing->city?->state?->code ?? 'NY' }}</div>
                                        </div>
                                        <div class="mt-2.5 pt-2 border-t border-[#33264E] flex items-center justify-between text-[10px]">
                                            <span class="text-slate-400 flex items-center gap-0.5">👁 {{ number_format(($rListing->views_count ?? 2500) / 1000, 1) }}k</span>
                                            <a href="{{ route('listings.show', $rListing->slug) }}" target="_blank" class="bg-[#F20B83] hover:bg-[#d90858] text-white text-[9px] font-bold px-2 py-0.5 rounded flex items-center gap-0.5">
                                                <span>View Ad</span>
                                                <span>→</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Top Locations (4 cols) -->
                    <div class="lg:col-span-4 {{ $card }} p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-extrabold text-white">Top Locations</h2>
                            <button type="button" wire:click="setTab('locations')" class="text-xs font-bold text-[#F20B83] hover:underline flex items-center gap-1">
                                <span>View All</span>
                                <span>→</span>
                            </button>
                        </div>

                        <div class="space-y-2.5">
                            @foreach($topLocations as $idx => $st)
                                <div class="flex items-center justify-between p-2 rounded-xl bg-[#201833] border border-[#33264E] text-xs">
                                    <div class="flex items-center space-x-2.5">
                                        <span class="w-5 h-5 rounded-full bg-[#F20B83]/20 text-[#F20B83] text-[10px] font-extrabold flex items-center justify-center">
                                            {{ $idx + 1 }}
                                        </span>
                                        <span class="text-pink-400">
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                        </span>
                                        <span class="font-bold text-white">{{ $st->name }}</span>
                                    </div>
                                    <span class="text-slate-300 font-extrabold text-[11px]">{{ number_format($st->listings_count / 1000, 1) }}k</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>

                <!-- Row 4: System Health, Recent Activities, Community Promo -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    
                    <!-- System Health with 4 Radial Progress Gauges (4 cols) -->
                    <div class="lg:col-span-4 {{ $card }} p-5">
                        <h2 class="text-sm font-extrabold text-white mb-4">System Health</h2>
                        <div class="grid grid-cols-4 gap-2 text-center mb-4">
                            <!-- Server Gauge -->
                            <div>
                                <div class="relative w-12 h-12 mx-auto">
                                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                        <path class="text-[#201833]" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        <path class="text-emerald-400" stroke-width="3" stroke-dasharray="98, 100" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-white">98%</span>
                                </div>
                                <div class="text-[10px] font-bold text-slate-400 mt-1">Server</div>
                            </div>

                            <!-- Database Gauge -->
                            <div>
                                <div class="relative w-12 h-12 mx-auto">
                                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                        <path class="text-[#201833]" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        <path class="text-emerald-400" stroke-width="3" stroke-dasharray="100, 100" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-white">100%</span>
                                </div>
                                <div class="text-[10px] font-bold text-slate-400 mt-1">Database</div>
                            </div>

                            <!-- Queue Gauge -->
                            <div>
                                <div class="relative w-12 h-12 mx-auto">
                                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                        <path class="text-[#201833]" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        <path class="text-emerald-400" stroke-width="3" stroke-dasharray="99, 100" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-white">99%</span>
                                </div>
                                <div class="text-[10px] font-bold text-slate-400 mt-1">Queue</div>
                            </div>

                            <!-- Cache Gauge -->
                            <div>
                                <div class="relative w-12 h-12 mx-auto">
                                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                        <path class="text-[#201833]" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                        <path class="text-emerald-400" stroke-width="3" stroke-dasharray="97, 100" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-white">97%</span>
                                </div>
                                <div class="text-[10px] font-bold text-slate-400 mt-1">Cache</div>
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-emerald-950/40 border border-emerald-800 text-center flex items-center justify-center gap-1.5 text-xs font-bold text-emerald-400">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>All Systems Operational</span>
                        </div>
                    </div>

                    <!-- Recent Activities (4 cols) -->
                    <div class="lg:col-span-4 {{ $card }} p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-sm font-extrabold text-white">Recent Activities</h2>
                            <button type="button" wire:click="setTab('audit')" class="text-xs font-bold text-[#F20B83] hover:underline flex items-center gap-1">
                                <span>View All</span>
                                <span>→</span>
                            </button>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between p-2 rounded-lg bg-[#201833]">
                                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#F20B83]"></span>New listing submitted</span>
                                <span class="text-[10px] text-slate-400">2 min ago</span>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded-lg bg-[#201833]">
                                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span>User registered</span>
                                <span class="text-[10px] text-slate-400">5 min ago</span>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded-lg bg-[#201833]">
                                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber-400"></span>Payment received: $29.99</span>
                                <span class="text-[10px] text-slate-400">10 min ago</span>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded-lg bg-[#201833]">
                                <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-red-400"></span>New report submitted</span>
                                <span class="text-[10px] text-slate-400">12 min ago</span>
                            </div>
                        </div>
                    </div>

                    <!-- Grow Your Community Card (4 cols) -->
                    <div class="lg:col-span-4 rounded-2xl overflow-hidden relative shadow-xl border border-[#33264E] group flex flex-col justify-between p-5 min-h-[160px]">
                        <img src="{{ asset('images/promo-banner.jpg') }}" alt="Community" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-300" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent"></div>
                        <div class="relative z-10">
                            <h3 class="text-sm font-extrabold text-white">Grow Your Community</h3>
                            <p class="text-xs text-slate-200 mt-1">Make LocalDateHub even better!!</p>
                        </div>
                        <div class="relative z-10 pt-4">
                            <button type="button" wire:click="setTab('analytics')" class="bg-[#F20B83] hover:bg-[#d90858] text-white text-xs font-bold px-3 py-1.5 rounded-xl flex items-center gap-1 shadow-lg transition">
                                <span>View Statistics</span>
                                <span>→</span>
                            </button>
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
                        <div class="mt-1 text-xl font-black text-white">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                <div class="{{ $card }} p-5 md:col-span-2 overflow-x-auto">
                    <h2 class="font-bold text-white mb-3">Top Listings by Views</h2>
                    <table class="min-w-full"><thead><tr><th class="{{ $th }}">Title</th><th class="{{ $th }}">Views</th><th class="{{ $th }}">Replies</th><th class="{{ $th }}">Favs</th></tr></thead>
                        <tbody>@forelse($topListings as $l)<tr class="border-t border-slate-800"><td class="{{ $td }}">{{ $l->title }}</td><td class="{{ $td }}">{{ number_format($l->view_count) }}</td><td class="{{ $td }}">{{ $l->reply_count }}</td><td class="{{ $td }}">{{ $l->favorite_count }}</td></tr>@empty<tr><td class="{{ $td }}" colspan="4">No listings yet.</td></tr>@endforelse</tbody></table>
                </div>
                <div class="{{ $card }} p-5">
                    <h2 class="font-bold text-white mb-3">Listings by Status</h2>
                    @forelse($listingsByStatus as $status => $total)
                        <div class="flex justify-between text-sm py-1"><span>{{ str_replace('_', ' ', $status) }}</span><span class="font-semibold">{{ $total }}</span></div>
                    @empty <p class="text-sm text-slate-400">No data.</p> @endforelse
                    <h2 class="font-bold text-white mt-5 mb-3">Top Categories</h2>
                    @foreach($topCategories as $c)
                        <div class="flex justify-between text-sm py-1"><span>{{ $c->name }}</span><span class="font-semibold">{{ $c->listings_count }}</span></div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ================= 1. USERS ================= --}}
        @if($tab === 'users')
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
                    <thead><tr><th class="{{ $th }}">User</th><th class="{{ $th }}">Role</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">18+</th><th class="{{ $th }}">Joined</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($users as $u)
                        <tr class="border-t border-slate-800" wire:key="user-{{ $u->id }}">
                            <td class="{{ $td }}"><div class="font-semibold">{{ $u->name }}</div><div class="text-xs text-slate-400">{{ $u->email }}</div></td>
                            <td class="{{ $td }}">{{ str_replace('_', ' ', $u->role) }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($u->account_status) }}">{{ $u->account_status }}</span></td>
                            <td class="{{ $td }}">{{ $u->is_age_verified ? '✔' : '—' }}</td>
                            <td class="{{ $td }}">{{ $u->created_at?->format('M j, Y') }}</td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($this->userCan('users.suspend') && $u->id !== auth()->id())
                                    @if($u->account_status === 'suspended')
                                        <button wire:click="unsuspendUser({{ $u->id }})" wire:confirm="Reinstate this account?" class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white">Reinstate</button>
                                    @else
                                        <button wire:click="openSuspendUserModal({{ $u->id }})" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Suspend</button>
                                    @endif
                                @endif
                                @if($this->userCan('staff.manage') && $u->id !== auth()->id())
                                    <button wire:click="openAssignRoleModal({{ $u->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Role</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No users match the filters.</td></tr>
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
                        <tr class="border-t border-slate-800" wire:key="listing-{{ $l->id }}">
                            <td class="{{ $td }}"><input type="checkbox" value="{{ $l->id }}" wire:model.live="selectedListingIds" class="rounded border-slate-600 bg-slate-950 text-rose-600" aria-label="Select listing {{ $l->id }}"></td>
                            <td class="{{ $td }}"><a href="{{ route('listings.show', $l->slug) }}" target="_blank" class="font-semibold hover:text-rose-300">{{ $l->title }}</a><div class="text-xs text-slate-500">#{{ $l->id }} · {{ $l->created_at?->format('M j, Y') }}</div></td>
                            <td class="{{ $td }}">{{ $l->category?->name }}<div class="text-xs text-slate-400">{{ $l->city?->name }}</div></td>
                            <td class="{{ $td }}">{{ $l->user?->name }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($l->status) }}">{{ str_replace('_', ' ', $l->status) }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($this->userCan('listings.approve') && $l->status !== 'active')<button wire:click="approveListing({{ $l->id }})" class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white">Approve</button>@endif
                                @if($this->userCan('listings.reject') && $l->status !== 'rejected')<button wire:click="openRejectListingModal({{ $l->id }})" class="{{ $btn }} bg-amber-700 hover:bg-amber-600 text-white">Reject</button>@endif
                                @if($this->userCan('listings.suspend') && $l->status !== 'suspended')<button wire:click="suspendListing({{ $l->id }})" wire:confirm="Suspend this listing?" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Suspend</button>@endif
                                @if($this->userCan('listings.delete'))<button wire:click="deleteListing({{ $l->id }})" wire:confirm="Move this listing to trash?" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Delete</button>@endif
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
            <div class="{{ $card }} overflow-x-auto mb-6">
                <div class="p-4 flex justify-between items-center">
                    <h2 class="font-bold text-white">Pending Review Queue (oldest first)</h2>
                    <a href="{{ route('admin.moderation.messages.queue') }}" class="text-xs text-rose-300 hover:underline">Message safety queue &rarr;</a>
                </div>
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Listing</th><th class="{{ $th }}">Owner</th><th class="{{ $th }}">Submitted</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($pendingQueue as $l)
                        <tr class="border-t border-slate-800" wire:key="queue-{{ $l->id }}">
                            <td class="{{ $td }}"><div class="font-semibold">{{ $l->title }}</div><div class="text-xs text-slate-400 line-clamp-2">{{ \Illuminate\Support\Str::limit($l->description, 160) }}</div></td>
                            <td class="{{ $td }}">{{ $l->user?->name }}</td>
                            <td class="{{ $td }}">{{ $l->created_at?->diffForHumans() }}</td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($this->userCan('listings.approve'))<button wire:click="approveListing({{ $l->id }})" class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white">Approve</button>@endif
                                @if($this->userCan('listings.reject'))<button wire:click="openRejectListingModal({{ $l->id }})" class="{{ $btn }} bg-amber-700 hover:bg-amber-600 text-white">Reject</button>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="{{ $td }} text-center text-slate-400">The review queue is empty. 🎉</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $pendingQueue->links() }}</div>
            </div>

            <div class="{{ $card }} overflow-x-auto">
                <h2 class="font-bold text-white p-4">Suspension Appeals</h2>
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">User</th><th class="{{ $th }}">Appeal</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($appeals as $a)
                        <tr class="border-t border-slate-800" wire:key="appeal-{{ $a->id }}">
                            <td class="{{ $td }}">{{ $a->user?->name }}</td>
                            <td class="{{ $td }}">{{ \Illuminate\Support\Str::limit($a->appeal_reason, 200) }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($a->status) }}">{{ $a->status }}</span></td>
                            <td class="{{ $td }}">
                                @if($this->userCan('moderation.handle_appeals') && in_array($a->status, ['pending', 'under_review']))
                                    <button wire:click="openAppealModal({{ $a->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Review</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="{{ $td }} text-center text-slate-400">No appeals.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 7. VERIFICATION ================= --}}
        @if($tab === 'verification')
            <div class="{{ $card }} p-4 mb-4 md:w-1/3">
                <select wire:model.live="verificationStatusFilter" class="{{ $input }}" aria-label="Verification status">
                    <option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option><option value="all">All</option>
                </select>
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">User</th><th class="{{ $th }}">Method</th><th class="{{ $th }}">Declared DOB</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Submitted</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($verifications as $v)
                        <tr class="border-t border-slate-800" wire:key="ver-{{ $v->id }}">
                            <td class="{{ $td }}">{{ $v->user?->name }}<div class="text-xs text-slate-400">{{ $v->user?->email }}</div></td>
                            <td class="{{ $td }}">{{ str_replace('_', ' ', $v->method) }}</td>
                            <td class="{{ $td }}">{{ $v->declared_birthdate?->format('Y-m-d') ?? '—' }} @if($v->declared_birthdate)<span class="text-xs text-slate-400">({{ $v->declared_birthdate->age }} yrs)</span>@endif</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($v->status) }}">{{ $v->status }}</span></td>
                            <td class="{{ $td }}">{{ $v->created_at?->diffForHumans() }}</td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($v->status === 'pending')
                                    <button wire:click="approveAgeVerification({{ $v->id }})" wire:confirm="Confirm this person is 18 or older?" class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white">Approve 18+</button>
                                    <button wire:click="rejectAgeVerification({{ $v->id }})" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Reject</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No verification requests.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $verifications->links() }}</div>
            </div>
        @endif

        {{-- ================= 8. REPORTS ================= --}}
        @if($tab === 'reports')
            <div class="{{ $card }} p-4 mb-4 md:w-1/3">
                <select wire:model.live="reportStatusFilter" class="{{ $input }}" aria-label="Report status">
                    <option value="open">Open</option><option value="investigating">Investigating</option><option value="resolved">Resolved</option><option value="dismissed">Dismissed</option><option value="all">All</option>
                </select>
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">#</th><th class="{{ $th }}">Severity / Reason</th><th class="{{ $th }}">Target</th><th class="{{ $th }}">Details</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($reports as $r)
                        <tr class="border-t border-slate-800" wire:key="report-{{ $r->id }}">
                            <td class="{{ $td }}">{{ $r->id }}</td>
                            <td class="{{ $td }}">
                                <div class="flex items-center space-x-1.5 mb-1">
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase tracking-wider border {{ $r->risk_level === 'critical' ? 'bg-red-950 text-red-300 border-red-700 animate-pulse' : ($r->risk_level === 'high' ? 'bg-amber-950 text-amber-300 border-amber-700' : 'bg-slate-800 text-slate-300 border-slate-600') }}">
                                        {{ $r->risk_level ?? 'medium' }}
                                    </span>
                                    @if($r->is_urgent_takedown)
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-900 text-white border border-rose-700">URGENT</span>
                                    @endif
                                </div>
                                <span class="font-semibold {{ in_array($r->reason_code, ['minor_exploitation', 'commercial_solicitation', 'non_consensual', 'underage_suspected']) ? 'text-red-300' : '' }}">{{ str_replace('_', ' ', $r->reason_code) }}</span>
                            </td>
                            <td class="{{ $td }}">{{ class_basename($r->reportable_type) }} #{{ $r->reportable_id }}</td>
                            <td class="{{ $td }}">{{ \Illuminate\Support\Str::limit($r->details, 140) }}<div class="text-xs text-slate-500">by {{ $r->reporter?->name ?? 'Anonymous' }} · {{ $r->created_at?->diffForHumans() }}</div></td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($r->status) }}">{{ $r->status }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if(in_array($r->status, ['open', 'investigating']))
                                    <button wire:click="openResolveReportModal({{ $r->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Action</button>
                                @endif
                                @if($r->reportable_type === 'App\Modules\Listings\Models\Listing')
                                    <button wire:click="quarantineReportedListing({{ $r->id }})" wire:confirm="Quarantine and immediately remove this listing from public view?" class="{{ $btn }} bg-red-800 hover:bg-red-700 text-white">Quarantine</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No reports.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $reports->links() }}</div>
            </div>
        @endif

        {{-- ================= 9. CONTACT ABUSE ================= --}}
        @if($tab === 'abuse')
            <div class="{{ $card }} overflow-x-auto">
                <div class="p-4 flex justify-between"><h2 class="font-bold text-white">Flagged Contact Messages</h2><a href="{{ route('admin.moderation.messages.queue') }}" class="text-xs text-rose-300 hover:underline">Full message moderation tools &rarr;</a></div>
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">#</th><th class="{{ $th }}">Listing</th><th class="{{ $th }}">Recipient</th><th class="{{ $th }}">Received</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($abuseMessages as $m)
                        <tr class="border-t border-slate-800" wire:key="abuse-{{ $m->id }}">
                            <td class="{{ $td }}">{{ $m->id }}</td>
                            <td class="{{ $td }}">{{ $m->listing?->title ?? '—' }}</td>
                            <td class="{{ $td }}">{{ $m->recipient?->name ?? '—' }}</td>
                            <td class="{{ $td }}">{{ $m->created_at?->diffForHumans() }}</td>
                            <td class="{{ $td }}">@if($this->userCan('moderation.act'))<button wire:click="dismissFlaggedMessage({{ $m->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Dismiss flag</button>@endif</td>
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
                        <tr class="border-t border-slate-800" wire:key="dmca-{{ $dmca->id }}">
                            <td class="{{ $td }} font-mono text-xs">{{ $dmca->ticket_number }}<div class="text-slate-500">{{ $dmca->created_at?->format('M j, Y') }}</div></td>
                            <td class="{{ $td }}"><div class="font-semibold">{{ $dmca->claimant_name }}</div><div class="text-xs text-slate-400">{{ $dmca->claimant_email }}</div></td>
                            <td class="{{ $td }} text-xs font-mono break-all max-w-xs">{{ $dmca->infringing_url }}</td>
                            <td class="{{ $td }} text-xs">{{ \Illuminate\Support\Str::limit($dmca->copyrighted_work, 120) }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($dmca->status) }}">{{ str_replace('_', ' ', $dmca->status) }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($dmca->status === 'pending')
                                    <button wire:click="openDmcaModal({{ $dmca->id }}, 'takedown_issued')" class="{{ $btn }} bg-rose-700 hover:bg-rose-600 text-white">Takedown</button>
                                    <button wire:click="openDmcaModal({{ $dmca->id }}, 'rejected')" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Reject</button>
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
                        <tr class="border-t border-slate-800" wire:key="legal-{{ $req->id }}">
                            <td class="{{ $td }} font-mono text-xs">{{ $req->reference_code }}<div class="text-slate-500">{{ $req->created_at?->format('M j, Y') }}</div></td>
                            <td class="{{ $td }}"><div class="font-semibold">{{ $req->agency_name }}</div><div class="text-xs text-slate-400">{{ $req->officer_name }} ({{ $req->official_email }})</div></td>
                            <td class="{{ $td }}"><span class="font-mono text-xs text-rose-300 uppercase">{{ str_replace('_', ' ', $req->request_type) }}</span><div class="text-xs text-slate-400">Case #{{ $req->case_number }}</div></td>
                            <td class="{{ $td }}">{{ $req->targetUser?->name ?? ($req->targetListing?->title ?? 'Platform Inquiry') }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($req->status) }}">{{ str_replace('_', ' ', $req->status) }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                <button wire:click="openLegalModal({{ $req->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Review</button>
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
                        <tr class="border-t border-slate-800" wire:key="st-{{ $st->id }}">
                            <td class="{{ $td }} font-semibold">{{ $st->state_name }} <span class="font-mono text-xs text-slate-400">({{ $st->state_code }})</span></td>
                            <td class="{{ $td }}">{{ $st->statutory_min_age }}+</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $st->requires_id_verification) }}">{{ $st->requires_id_verification ? 'Required' : 'Attestation' }}</span></td>
                            <td class="{{ $td }} text-xs font-mono text-slate-300">{{ $st->legal_reference ?? '—' }}</td>
                            <td class="{{ $td }} text-xs text-slate-400 max-w-xs truncate">{{ $st->mandatory_warning_text ?? 'Standard 18+ Gate' }}</td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($this->userCan('settings.manage'))
                                    <button wire:click="toggleStateIdRequirement({{ $st->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">{{ $st->requires_id_verification ? 'Disable ID' : 'Require ID' }}</button>
                                    <button wire:click="openStateComplianceModal({{ $st->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Edit</button>
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
                        <tr class="border-t border-slate-800" wire:key="hold-{{ $pr->id }}">
                            <td class="{{ $td }} font-mono text-xs">HOLD-{{ $pr->id }}</td>
                            <td class="{{ $td }}">{{ class_basename($pr->preservable_type) }} #{{ $pr->preservable_id }}</td>
                            <td class="{{ $td }} text-xs">{{ $pr->hold_reason }}</td>
                            <td class="{{ $td }}">{{ $pr->frozenBy?->name ?? 'System' }}</td>
                            <td class="{{ $td }} text-xs">{{ $pr->expires_at?->format('M j, Y') ?? 'Indefinite' }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $pr->is_active) }}">{{ $pr->is_active ? 'Active Hold' : 'Released' }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                <button wire:click="inspectPreservation({{ $pr->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Snapshot</button>
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
                        <tr class="border-t border-slate-800" wire:key="cat-{{ $c->id }}">
                            <td class="{{ $td }} {{ $c->parent_id ? 'pl-8' : 'font-semibold' }}">{{ $c->name }}</td>
                            <td class="{{ $td }} font-mono text-xs">{{ $c->slug }}</td>
                            <td class="{{ $td }}">{{ $c->listings_count }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $c->is_active) }}">{{ $c->is_active ? 'active' : 'inactive' }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                <button wire:click="toggleCategory({{ $c->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">{{ $c->is_active ? 'Deactivate' : 'Activate' }}</button>
                                <a href="{{ route('admin.categories.edit', $c) }}" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Edit</a>
                                <a href="{{ route('admin.categories.attributes', $c) }}" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Attributes</a>
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
                        <tr class="border-t border-slate-800" wire:key="attr-{{ $a->id }}">
                            <td class="{{ $td }} font-semibold">{{ $a->name }}</td>
                            <td class="{{ $td }}">{{ $a->category?->name ?? 'Global' }}</td>
                            <td class="{{ $td }}">{{ $a->options_count }}</td>
                            <td class="{{ $td }}">{{ $a->is_required ? 'Yes' : 'No' }}</td>
                            <td class="{{ $td }}">{{ $a->is_filterable ? 'Yes' : 'No' }}</td>
                            <td class="{{ $td }}">@if($a->category)<a href="{{ route('admin.categories.attributes', $a->category) }}" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Manage</a>@endif</td>
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
                        <tr class="border-t border-slate-800" wire:key="state-{{ $s->id }}">
                            <td class="{{ $td }}">{{ $s->name }}</td>
                            <td class="{{ $td }} font-mono">{{ $s->code }}</td>
                            <td class="{{ $td }}">{{ $s->cities_count }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $s->is_active) }}">{{ $s->is_active ? 'active' : 'inactive' }}</span></td>
                            <td class="{{ $td }}"><button wire:click="toggleState({{ $s->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">{{ $s->is_active ? 'Deactivate' : 'Activate' }}</button></td>
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
                        <tr class="border-t border-slate-800" wire:key="plan-{{ $p->id }}">
                            <td class="{{ $td }}"><div class="font-semibold">{{ $p->name }}</div><div class="text-xs text-slate-500 font-mono">{{ $p->slug }}</div></td>
                            <td class="{{ $td }}">{{ str_replace('_', ' ', $p->plan_type) }}</td>
                            <td class="{{ $td }}">${{ number_format($p->price_cents / 100, 2) }}</td>
                            <td class="{{ $td }}">{{ $p->duration_days }} days</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $p->is_active) }}">{{ $p->is_active ? 'active' : 'inactive' }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($this->userCan('billing.manage_pricing'))
                                    <button wire:click="openPlanModal({{ $p->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Edit</button>
                                    <button wire:click="togglePlan({{ $p->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">{{ $p->is_active ? 'Disable' : 'Enable' }}</button>
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
                        <tr class="border-t border-slate-800" wire:key="feat-{{ $f->id }}">
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
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">User</th><th class="{{ $th }}">Plan</th><th class="{{ $th }}">Gateway</th><th class="{{ $th }}">Period</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($subscriptions as $s)
                        <tr class="border-t border-slate-800" wire:key="sub-{{ $s->id }}">
                            <td class="{{ $td }}">{{ $s->user?->name }}</td>
                            <td class="{{ $td }}">{{ $s->plan?->name }}</td>
                            <td class="{{ $td }}">{{ $s->gateway }}</td>
                            <td class="{{ $td }} text-xs">{{ $s->starts_at?->format('M j, Y') }} → {{ $s->ends_at?->format('M j, Y') ?? 'open' }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($s->status) }}">{{ $s->status }}</span></td>
                            <td class="{{ $td }}">@if($s->status === 'active' && $this->userCan('billing.manage_pricing'))<button wire:click="cancelSubscription({{ $s->id }})" wire:confirm="Cancel this subscription?" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Cancel</button>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No subscriptions.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $subscriptions->links() }}</div>
            </div>
        @endif

        {{-- ================= 13. PAYMENTS & REFUNDS ================= --}}
        @if($tab === 'payments')
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                <div class="{{ $card }} p-3 sm:w-1/3">
                    <select wire:model.live="paymentStatusFilter" class="{{ $input }}" aria-label="Payment status">
                        <option value="all">All Statuses</option><option value="completed">Completed</option><option value="pending">Pending</option><option value="failed">Failed</option><option value="refunded">Refunded</option>
                    </select>
                </div>
                <div class="flex items-center space-x-2">
                    <button wire:click="openPricingRuleModal" class="{{ $btn }} bg-purple-600 hover:bg-purple-500 text-white">
                        + Dynamic Pricing Rule
                    </button>
                    <button wire:click="reconcileTransactions" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Reconcile Transactions
                        @if($unreconciledCount > 0)
                            <span class="ml-1.5 px-1.5 py-0.2 bg-amber-500/20 text-amber-300 rounded-full text-[10px] font-mono">{{ $unreconciledCount }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <div class="{{ $card }} overflow-x-auto mb-6">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Reference</th><th class="{{ $th }}">User</th><th class="{{ $th }}">Plan / Listing</th><th class="{{ $th }}">Amount</th><th class="{{ $th }}">Gateway</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Date</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($transactions as $t)
                        <tr class="border-t border-slate-800" wire:key="txn-{{ $t->id }}">
                            <td class="{{ $td }} font-mono text-xs">
                                {{ $t->reference_id }}
                                @if($t->is_reconciled)
                                    <span class="block text-[9px] text-emerald-400">reconciled</span>
                                @endif
                            </td>
                            <td class="{{ $td }}">{{ $t->user?->name }}</td>
                            <td class="{{ $td }} text-xs">
                                <span class="font-semibold text-slate-200">{{ $t->plan?->name ?? 'Direct Upgrade' }}</span>
                                @if($t->listing)
                                    <span class="block text-[10px] text-slate-400 truncate max-w-xs">{{ $t->listing->title }}</span>
                                @endif
                            </td>
                            <td class="{{ $td }} font-mono font-bold">${{ number_format($t->amount_cents / 100, 2) }}</td>
                            <td class="{{ $td }} text-xs uppercase font-mono text-slate-400">{{ $t->gateway }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($t->status) }}">{{ $t->status }}</span></td>
                            <td class="{{ $td }} text-xs">{{ $t->created_at?->format('M j, Y') }}</td>
                            <td class="{{ $td }} space-x-1.5 whitespace-nowrap">
                                @if($t->invoice)
                                    <a href="{{ route('account.invoices.show', $t->invoice) }}" target="_blank" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-rose-300">Invoice</a>
                                @endif
                                @if($t->status === 'completed' && $this->userCan('billing.refunds'))
                                    <button wire:click="openRefundModal({{ $t->id }})" class="{{ $btn }} bg-amber-700 hover:bg-amber-600 text-white">Refund</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="{{ $td }} text-center text-slate-400">No transactions recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $transactions->links() }}</div>
            </div>

            <!-- Dynamic Pricing Rules Section -->
            <div class="{{ $card }} p-5 mb-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-bold text-white text-base">Dynamic Pricing Rules (Overrides & Category Promos)</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead><tr class="text-slate-400 text-left border-b border-slate-800"><th class="pb-2">Rule Name</th><th class="pb-2">Target</th><th class="pb-2">Pricing Adjustment</th><th class="pb-2">Status</th><th class="pb-2 text-right">Actions</th></tr></thead>
                        <tbody class="divide-y divide-slate-800">
                        @forelse($pricingRules as $rule)
                            <tr wire:key="prule-{{ $rule->id }}">
                                <td class="py-2.5 font-semibold text-slate-200">{{ $rule->rule_name }}</td>
                                <td class="py-2.5 text-slate-400">
                                    {{ $rule->plan?->name ?? 'All Plans' }}
                                    @if($rule->category) · Cat: {{ $rule->category->name }} @endif
                                    @if($rule->state) · State: {{ $rule->state->code }} @endif
                                </td>
                                <td class="py-2.5 font-mono">
                                    @if($rule->is_free)
                                        <span class="text-emerald-400 font-bold">100% FREE</span>
                                    @elseif($rule->fixed_price_cents !== null)
                                        <span class="text-slate-200">${{ number_format($rule->fixed_price_cents / 100, 2) }} Fixed</span>
                                    @elseif($rule->discount_percentage > 0)
                                        <span class="text-purple-400 font-bold">-{{ $rule->discount_percentage }}% Off</span>
                                    @endif
                                </td>
                                <td class="py-2.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] {{ $rule->is_active ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-700 text-slate-400' }}">
                                        {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-2.5 text-right space-x-1">
                                    <button wire:click="openPricingRuleModal({{ $rule->id }})" class="{{ $btn }} bg-slate-700 text-white">Edit</button>
                                    <button wire:click="deletePricingRule({{ $rule->id }})" wire:confirm="Delete this pricing rule?" class="{{ $btn }} bg-rose-900/60 hover:bg-rose-800 text-rose-300">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-3 text-center text-slate-400">No dynamic pricing rules configured. Default plan pricing applies.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Fraud & Velocity Monitoring Section -->
            <div class="{{ $card }} p-5 mb-6">
                <h2 class="font-bold text-white text-base mb-3">Payment Fraud & Velocity Alerts (Last 15 Events)</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead><tr class="text-slate-400 text-left border-b border-slate-800"><th class="pb-2">User / IP</th><th class="pb-2">Risk Score</th><th class="pb-2">Risk Tier</th><th class="pb-2">Flags Triggered</th><th class="pb-2">Action</th><th class="pb-2">Time</th></tr></thead>
                        <tbody class="divide-y divide-slate-800">
                        @forelse($fraudLogs as $flog)
                            <tr wire:key="flog-{{ $flog->id }}">
                                <td class="py-2 font-mono">
                                    {{ $flog->user?->name ?? 'Guest/Anonymous' }}<br>
                                    <span class="text-[10px] text-slate-500">{{ $flog->ip_address }}</span>
                                </td>
                                <td class="py-2 font-mono font-bold">{{ $flog->risk_score }}/100</td>
                                <td class="py-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                        @if($flog->risk_tier === 'blocked') bg-red-900/80 text-red-300 border border-red-700
                                        @elseif($flog->risk_tier === 'high') bg-amber-900/80 text-amber-300 border border-amber-700
                                        @else bg-slate-800 text-slate-300 @endif
                                    ">
                                        {{ $flog->risk_tier }}
                                    </span>
                                </td>
                                <td class="py-2 text-[11px] text-slate-300">
                                    {{ !empty($flog->flags) ? implode(', ', $flog->flags) : 'Clean' }}
                                </td>
                                <td class="py-2 text-[11px] font-mono uppercase text-slate-400">{{ $flog->action_taken }}</td>
                                <td class="py-2 text-slate-500">{{ $flog->created_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-3 text-center text-slate-400">No recent fraud alerts. Risk levels are normal.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Refunds Section -->
            <div class="{{ $card }} p-5">
                <h2 class="font-bold text-white mb-3">Recent Refunds</h2>
                @forelse($refunds as $rf)
                    <div class="flex justify-between text-sm border-b border-slate-800 py-1.5">
                        <span>${{ number_format($rf->amount_cents / 100, 2) }} · {{ $rf->transaction?->reference_id }} · {{ $rf->reason }}</span>
                        <span class="text-slate-500">{{ $rf->processedBy?->name }} · {{ $rf->created_at?->diffForHumans() }}</span>
                    </div>
                @empty <p class="text-sm text-slate-400">No refunds issued.</p> @endforelse
            </div>
        @endif

        {{-- ================= 16. ADVERTISEMENTS ================= --}}
        @if($tab === 'ads')
            <div class="flex justify-end mb-3"><button wire:click="openAdModal" class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white">+ New Placement</button></div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Name</th><th class="{{ $th }}">Slot</th><th class="{{ $th }}">Advertiser</th><th class="{{ $th }}">Window</th><th class="{{ $th }}">Impr / Clicks / CTR</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($ads as $ad)
                        <tr class="border-t border-slate-800" wire:key="ad-{{ $ad->id }}">
                            <td class="{{ $td }} font-semibold">{{ $ad->name }}</td>
                            <td class="{{ $td }}">{{ str_replace('_', ' ', $ad->slot) }}</td>
                            <td class="{{ $td }}">{{ $ad->advertiser_name ?? '—' }}</td>
                            <td class="{{ $td }} text-xs">{{ $ad->starts_at?->format('M j, Y') ?? 'now' }} → {{ $ad->ends_at?->format('M j, Y') ?? 'open' }}</td>
                            <td class="{{ $td }}">{{ number_format($ad->impressions) }} / {{ number_format($ad->clicks) }} / {{ $ad->clickThroughRate() }}%</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $ad->is_active) }}">{{ $ad->is_active ? 'active' : 'paused' }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                <button wire:click="openAdModal({{ $ad->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Edit</button>
                                <button wire:click="toggleAd({{ $ad->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">{{ $ad->is_active ? 'Pause' : 'Activate' }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="{{ $td }} text-center text-slate-400">No advertisement placements yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 14. SEO ================= --}}
        @if($tab === 'seo')
            <div class="flex justify-end mb-3"><button wire:click="openSeoModal" class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white">+ New SEO Entry</button></div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Route / Path</th><th class="{{ $th }}">Meta Title</th><th class="{{ $th }}">Robots</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($seoMetas as $m)
                        <tr class="border-t border-slate-800" wire:key="seo-{{ $m->id }}">
                            <td class="{{ $td }} font-mono text-xs">{{ $m->page_route ?? class_basename($m->model_type) . ' #' . $m->model_id }}</td>
                            <td class="{{ $td }}">{{ $m->meta_title }}<div class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($m->meta_description, 100) }}</div></td>
                            <td class="{{ $td }}">{{ $m->robots_directive }}</td>
                            <td class="{{ $td }}"><button wire:click="openSeoModal({{ $m->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Edit</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="{{ $td }} text-center text-slate-400">No SEO overrides configured.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 15. CMS ================= --}}
        @if($tab === 'cms')
            <div class="flex justify-end mb-3"><button wire:click="openCmsModal" class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white">+ New Page</button></div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Title</th><th class="{{ $th }}">Slug</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Updated</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($cmsPages as $p)
                        <tr class="border-t border-slate-800" wire:key="cms-{{ $p->id }}">
                            <td class="{{ $td }} font-semibold">{{ $p->title }}</td>
                            <td class="{{ $td }} font-mono text-xs">{{ $p->slug }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $p->is_published) }}">{{ $p->is_published ? 'published' : 'draft' }}</span></td>
                            <td class="{{ $td }}">{{ $p->updated_at?->diffForHumans() }}</td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                <button wire:click="openCmsModal({{ $p->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Edit</button>
                                <button wire:click="toggleCmsPublish({{ $p->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">{{ $p->is_published ? 'Unpublish' : 'Publish' }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="{{ $td }} text-center text-slate-400">No CMS pages yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 18. STAFF ================= --}}
        @if($tab === 'staff')
            <p class="text-sm text-slate-400 mb-3">To promote a member, open the <button wire:click="setTab('users')" class="text-rose-300 underline">Users</button> module and choose “Role”.</p>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Staff Member</th><th class="{{ $th }}">Role</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Last Login</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($staffMembers as $s)
                        <tr class="border-t border-slate-800" wire:key="staff-{{ $s->id }}">
                            <td class="{{ $td }}">{{ $s->name }}<div class="text-xs text-slate-400">{{ $s->email }}</div></td>
                            <td class="{{ $td }}">{{ $assignableRoles[$s->role] ?? str_replace('_', ' ', $s->role) }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($s->account_status) }}">{{ $s->account_status }}</span></td>
                            <td class="{{ $td }}">{{ $s->last_login_at ? \Illuminate\Support\Carbon::parse($s->last_login_at)->diffForHumans() : '—' }}</td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($s->id !== auth()->id() && ($s->role !== 'super_admin' || $isSuperAdmin))
                                    <button wire:click="openAssignRoleModal({{ $s->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Change role</button>
                                    <button wire:click="revokeStaff({{ $s->id }})" wire:confirm="Revoke all staff privileges from this account?" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Revoke</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="{{ $td }} text-center text-slate-400">No staff accounts.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 19. ROLES & PERMISSIONS ================= --}}
        @if($tab === 'roles')
            @unless($isSuperAdmin)
                <div class="mb-3 rounded-xl border border-amber-700 bg-amber-950/50 px-4 py-2 text-sm text-amber-200">Read-only: only a Super Administrator can edit the permission matrix.</div>
            @endunless
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead>
                        <tr>
                            <th class="{{ $th }} sticky left-0 bg-slate-900">Permission</th>
                            @foreach($roles as $role)<th class="{{ $th }} text-center whitespace-nowrap">{{ $role->name }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($permissionsByModule as $module => $perms)
                        <tr class="bg-slate-950/60"><td colspan="{{ $roles->count() + 1 }}" class="px-3 py-1 font-bold uppercase tracking-widest text-[10px] text-rose-300">{{ $module }}</td></tr>
                        @foreach($perms as $perm)
                            <tr class="border-t border-slate-800" wire:key="perm-{{ $perm->id }}">
                                <td class="px-3 py-1.5 font-mono sticky left-0 bg-slate-900">{{ $perm->slug }}</td>
                                @foreach($roles as $role)
                                    @php $granted = $role->permissions->contains('id', $perm->id); $locked = !$isSuperAdmin || in_array($role->slug, ['super_admin', 'admin']); @endphp
                                    <td class="px-3 py-1.5 text-center">
                                        <input type="checkbox" @checked($granted) @disabled($locked)
                                            wire:click="toggleRolePermission({{ $role->id }}, {{ $perm->id }})"
                                            aria-label="{{ $role->name }} – {{ $perm->slug }}"
                                            class="rounded border-slate-600 bg-slate-950 text-rose-600 disabled:opacity-50">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 20. SYSTEM SETTINGS ================= --}}
        @if($tab === 'settings')
            <form wire:submit="saveSystemSettings" class="space-y-4">
                @foreach($systemSettings as $group => $settings)
                    @php $restricted = in_array($group, \App\Livewire\Admin\AdminEnterpriseHub::RESTRICTED_SETTING_GROUPS) && !$isSuperAdmin; @endphp
                    <div class="{{ $card }} p-5">
                        <h2 class="font-bold text-white mb-1 capitalize">{{ $group }}</h2>
                        @if(in_array($group, \App\Livewire\Admin\AdminEnterpriseHub::RESTRICTED_SETTING_GROUPS))
                            <p class="text-xs text-amber-300 mb-3">🔒 Restricted group — Super Administrator only. Gateway secrets are stored in the server environment, never here.</p>
                        @endif
                        <div class="grid md:grid-cols-2 gap-4">
                            @foreach($settings as $setting)
                                <label class="block">
                                    <span class="text-xs font-mono text-slate-300">{{ $setting->key }}</span>
                                    @if($setting->type === 'boolean')
                                        <select wire:model="settingValues.{{ $setting->key }}" class="{{ $input }} mt-1" @disabled($restricted)><option value="true">true</option><option value="false">false</option></select>
                                    @else
                                        <input type="{{ $setting->type === 'integer' ? 'number' : 'text' }}" wire:model="settingValues.{{ $setting->key }}" class="{{ $input }} mt-1" @disabled($restricted)>
                                    @endif
                                    <span class="text-[11px] text-slate-500">{{ $setting->description }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="flex justify-end"><button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save configuration</button></div>
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
                        <tr class="border-t border-slate-800" wire:key="audit-{{ $log->id }}">
                            <td class="{{ $td }} whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="{{ $td }}">{{ $log->admin?->name ?? 'System' }}</td>
                            <td class="{{ $td }} font-mono text-rose-300">{{ $log->action }}</td>
                            <td class="{{ $td }}">{{ $log->target_type ? class_basename($log->target_type) : '—' }}{{ $log->target_id ? ' #' . $log->target_id : '' }}</td>
                            <td class="{{ $td }} font-mono text-xs">{{ $log->ip_address }}</td>
                            <td class="{{ $td }}"><button wire:click="inspectAuditLog({{ $log->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Inspect</button></td>
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
        $anyModal = $showSuspendUserModal || $showAssignRoleModal || $showRejectListingModal || $showAppealModal
            || $showResolveReportModal || $showRefundModal || $showPlanModal || $showPricingRuleModal || $showCmsModal || $showSeoModal
            || $showAdModal || $showAuditDetailModal || $showDmcaModal || $showLegalModal || $showStateComplianceModal
            || $showPreservationModal;
    @endphp
    @if($anyModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-4" role="dialog" aria-modal="true" wire:keydown.escape.window="closeModals">
            <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl">

                @if($showSuspendUserModal)
                    <h3 class="text-lg font-bold text-white mb-4">Suspend Account</h3>
                    <form wire:submit="suspendUser" class="space-y-3">
                        <label class="block text-sm">Reason<textarea wire:model="userSuspendReason" rows="3" class="{{ $input }} mt-1" required></textarea></label>
                        @error('userSuspendReason')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm">Type<select wire:model.live="userSuspendType" class="{{ $input }} mt-1"><option value="temporary">Temporary</option><option value="permanent">Permanent</option></select></label>
                            @if($userSuspendType === 'temporary')<label class="block text-sm">Days<input type="number" min="1" max="3650" wire:model="userSuspendDays" class="{{ $input }} mt-1"></label>@endif
                        </div>
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white text-sm font-semibold">Suspend</button></div>
                    </form>
                @endif

                @if($showAssignRoleModal)
                    <h3 class="text-lg font-bold text-white mb-4">Assign Role</h3>
                    <form wire:submit="assignRole" class="space-y-3">
                        <select wire:model="newRoleSlug" class="{{ $input }}" aria-label="Role">
                            @foreach(($assignableRoles ?? []) as $slug => $name)<option value="{{ $slug }}">{{ $name }}</option>@endforeach
                        </select>
                        @error('newRoleSlug')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save role</button></div>
                    </form>
                @endif

                @if($showRejectListingModal)
                    <h3 class="text-lg font-bold text-white mb-4">Reject Listing</h3>
                    <form wire:submit="rejectListing" class="space-y-3">
                        <label class="block text-sm">Reason shown to the advertiser<textarea wire:model="listingActionReason" rows="3" class="{{ $input }} mt-1" required></textarea></label>
                        @error('listingActionReason')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold">Reject</button></div>
                    </form>
                @endif

                @if($showAppealModal)
                    <h3 class="text-lg font-bold text-white mb-4">Decide Appeal</h3>
                    <div class="space-y-3">
                        <label class="block text-sm">Decision notes<textarea wire:model="appealDecisionNotes" rows="3" class="{{ $input }} mt-1"></textarea></label>
                        @error('appealDecisionNotes')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button>
                            <button type="button" wire:click="decideAppeal('rejected')" class="px-3 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white text-sm font-semibold">Reject appeal</button>
                            <button type="button" wire:click="decideAppeal('granted')" class="px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold">Grant &amp; reinstate</button>
                        </div>
                    </div>
                @endif

                @if($showResolveReportModal)
                    <h3 class="text-lg font-bold text-white mb-4">Action Report #{{ $targetReportId }}</h3>
                    <div class="space-y-3">
                        <label class="block text-sm">Resolution notes<textarea wire:model="reportResolutionNotes" rows="3" class="{{ $input }} mt-1"></textarea></label>
                        <div class="flex flex-wrap justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button>
                            <button type="button" wire:click="resolveReport('investigating')" class="px-3 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-sm">Investigating</button>
                            <button type="button" wire:click="resolveReport('dismissed')" class="px-3 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-sm">Dismiss</button>
                            <button type="button" wire:click="resolveReport('resolved')" class="px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold">Resolve</button>
                        </div>
                    </div>
                @endif

                @if($showRefundModal)
                    <h3 class="text-lg font-bold text-white mb-4">Issue Refund</h3>
                    <form wire:submit="issueRefund" class="space-y-3">
                        <label class="block text-sm">Reason<input type="text" wire:model="refundReason" class="{{ $input }} mt-1" required maxlength="150"></label>
                        @error('refundReason')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <p class="text-xs text-slate-400">The full transaction amount will be refunded and recorded in the ledger and audit log.</p>
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold">Confirm refund</button></div>
                    </form>
                @endif

                @if($showPlanModal)
                    <h3 class="text-lg font-bold text-white mb-4">{{ $editingPlanId ? 'Edit' : 'New' }} Pricing Plan</h3>
                    <form wire:submit="savePlan" class="space-y-3">
                        <label class="block text-sm">Name<input type="text" wire:model="planForm.name" class="{{ $input }} mt-1"></label>
                        @error('planForm.name')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Slug<input type="text" wire:model="planForm.slug" class="{{ $input }} mt-1" placeholder="auto-generated if blank"></label>
                        @error('planForm.slug')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Description<textarea wire:model="planForm.description" rows="2" class="{{ $input }} mt-1"></textarea></label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="block text-sm">Price (USD)<input type="number" step="0.01" min="0" wire:model="planForm.price" class="{{ $input }} mt-1"></label>
                            <label class="block text-sm">Days<input type="number" min="1" max="365" wire:model="planForm.duration_days" class="{{ $input }} mt-1"></label>
                            <label class="block text-sm">Type<select wire:model="planForm.plan_type" class="{{ $input }} mt-1"><option value="listing_upgrade">Listing upgrade</option><option value="featured_spot">Featured spot</option><option value="bump">Bump</option><option value="membership">Membership</option></select></label>
                        </div>
                        @error('planForm.price')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="planForm.is_active" class="rounded border-slate-600 bg-slate-950 text-rose-600"> Active</label>
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save plan</button></div>
                    </form>
                @endif

                @if($showPricingRuleModal)
                    <h3 class="text-lg font-bold text-white mb-4">{{ $editingPricingRuleId ? 'Edit' : 'New' }} Dynamic Pricing Rule</h3>
                    <form wire:submit="savePricingRule" class="space-y-3">
                        <label class="block text-sm">Rule Name<input type="text" wire:model="pricingRuleForm.rule_name" class="{{ $input }} mt-1" required placeholder="e.g. Free Launch Promo or Weekend Special"></label>
                        @error('pricingRuleForm.rule_name')<p class="text-xs text-red-400">{{ $message }}</p>@enderror

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm">Target Plan (Optional)
                                <select wire:model="pricingRuleForm.pricing_plan_id" class="{{ $input }} mt-1">
                                    <option value="">All Plans</option>
                                    @foreach(\App\Modules\Billing\Models\PricingPlan::where('is_active', true)->get() as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block text-sm">Target Category (Optional)
                                <select wire:model="pricingRuleForm.category_id" class="{{ $input }} mt-1">
                                    <option value="">All Categories</option>
                                    @foreach(\App\Modules\Categories\Models\Category::where('is_active', true)->get() as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm">Discount Percentage (%)
                                <input type="number" min="0" max="100" wire:model="pricingRuleForm.discount_percentage" class="{{ $input }} mt-1">
                            </label>
                            <label class="block text-sm">Fixed Price Override (Cents, e.g. 499 for $4.99)
                                <input type="number" min="0" wire:model="pricingRuleForm.fixed_price_cents" class="{{ $input }} mt-1" placeholder="Optional fixed price">
                            </label>
                        </div>

                        <div class="flex items-center gap-6 pt-1">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="pricingRuleForm.is_free" class="rounded border-slate-600 bg-slate-950 text-rose-600">
                                Make 100% Free (Permitted Free Listings)
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="pricingRuleForm.is_active" class="rounded border-slate-600 bg-slate-950 text-rose-600">
                                Active Rule
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button>
                            <button type="submit" class="px-3 py-2 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-sm font-semibold">Save Pricing Rule</button>
                        </div>
                    </form>
                @endif

                @if($showSeoModal)
                    <h3 class="text-lg font-bold text-white mb-4">{{ $editingSeoId ? 'Edit' : 'New' }} SEO Entry</h3>
                    <form wire:submit="saveSeo" class="space-y-3">
                        <label class="block text-sm">Route name or path<input type="text" wire:model="seoForm.page_route" class="{{ $input }} mt-1" placeholder="e.g. home or /locations/tx"></label>
                        @error('seoForm.page_route')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Meta title<input type="text" wire:model="seoForm.meta_title" maxlength="150" class="{{ $input }} mt-1"></label>
                        @error('seoForm.meta_title')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Meta description<textarea wire:model="seoForm.meta_description" rows="3" maxlength="320" class="{{ $input }} mt-1"></textarea></label>
                        @error('seoForm.meta_description')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Keywords<input type="text" wire:model="seoForm.meta_keywords" class="{{ $input }} mt-1"></label>
                        <label class="block text-sm">Canonical URL<input type="url" wire:model="seoForm.canonical_url" class="{{ $input }} mt-1"></label>
                        @error('seoForm.canonical_url')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Robots<select wire:model="seoForm.robots_directive" class="{{ $input }} mt-1">@foreach(['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'] as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select></label>
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save</button></div>
                    </form>
                @endif

                @if($showCmsModal)
                    <h3 class="text-lg font-bold text-white mb-4">{{ $editingCmsId ? 'Edit' : 'New' }} CMS Page</h3>
                    <form wire:submit="saveCmsPage" class="space-y-3">
                        <label class="block text-sm">Title<input type="text" wire:model="cmsForm.title" class="{{ $input }} mt-1"></label>
                        @error('cmsForm.title')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Slug<input type="text" wire:model="cmsForm.slug" class="{{ $input }} mt-1" placeholder="auto-generated if blank"></label>
                        @error('cmsForm.slug')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Content (basic HTML allowed)<textarea wire:model="cmsForm.content" rows="10" class="{{ $input }} mt-1 font-mono"></textarea></label>
                        @error('cmsForm.content')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="cmsForm.is_published" class="rounded border-slate-600 bg-slate-950 text-rose-600"> Published</label>
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save page</button></div>
                    </form>
                @endif

                @if($showAdModal)
                    <h3 class="text-lg font-bold text-white mb-4">{{ $editingAdId ? 'Edit' : 'New' }} Advertisement</h3>
                    <form wire:submit="saveAd" class="space-y-3">
                        <label class="block text-sm">Name<input type="text" wire:model="adForm.name" class="{{ $input }} mt-1"></label>
                        @error('adForm.name')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm">Slot<select wire:model="adForm.slot" class="{{ $input }} mt-1">@foreach(\App\Modules\Admin\Models\AdPlacement::SLOTS as $slot)<option value="{{ $slot }}">{{ str_replace('_', ' ', $slot) }}</option>@endforeach</select></label>
                            <label class="block text-sm">Advertiser<input type="text" wire:model="adForm.advertiser_name" class="{{ $input }} mt-1"></label>
                        </div>
                        <label class="block text-sm">Image URL<input type="url" wire:model="adForm.image_url" class="{{ $input }} mt-1"></label>
                        @error('adForm.image_url')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Target URL<input type="url" wire:model="adForm.target_url" class="{{ $input }} mt-1"></label>
                        @error('adForm.target_url')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm">Starts<input type="date" wire:model="adForm.starts_at" class="{{ $input }} mt-1"></label>
                            <label class="block text-sm">Ends<input type="date" wire:model="adForm.ends_at" class="{{ $input }} mt-1"></label>
                        </div>
                        @error('adForm.ends_at')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="adForm.is_active" class="rounded border-slate-600 bg-slate-950 text-rose-600"> Active</label>
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save</button></div>
                    </form>
                @endif

                @if($showAuditDetailModal && isset($inspectedAuditLog) && $inspectedAuditLog)
                    <h3 class="text-lg font-bold text-white mb-1">Audit Record #{{ $inspectedAuditLog->id }}</h3>
                    <p class="text-xs text-slate-400 mb-4"><span class="font-mono text-rose-300">{{ $inspectedAuditLog->action }}</span> · {{ $inspectedAuditLog->admin?->name ?? 'System' }} · {{ $inspectedAuditLog->created_at?->format('Y-m-d H:i:s') }}</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div><div class="text-xs font-bold text-slate-400 mb-1">Before</div><pre class="text-[11px] bg-slate-950 border border-slate-800 rounded-lg p-2 overflow-x-auto whitespace-pre-wrap">{{ json_encode($inspectedAuditLog->before_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'null' }}</pre></div>
                        <div><div class="text-xs font-bold text-slate-400 mb-1">After</div><pre class="text-[11px] bg-slate-950 border border-slate-800 rounded-lg p-2 overflow-x-auto whitespace-pre-wrap">{{ json_encode($inspectedAuditLog->after_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'null' }}</pre></div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-3">Target: {{ $inspectedAuditLog->target_type ?? '—' }} {{ $inspectedAuditLog->target_id ? '#' . $inspectedAuditLog->target_id : '' }} · IP {{ $inspectedAuditLog->ip_address }}</p>
                    <p class="text-[11px] text-slate-500 break-all">UA: {{ $inspectedAuditLog->user_agent }}</p>
                    <div class="flex justify-end pt-3"><button type="button" wire:click="closeModals" class="px-3 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-sm">Close</button></div>
                @endif

                @if($showDmcaModal)
                    <h3 class="text-lg font-bold text-white mb-2">Process DMCA Takedown Notice</h3>
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
                            <button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button>
                            <button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save Decision</button>
                        </div>
                    </form>
                @endif

                @if($showLegalModal)
                    <h3 class="text-lg font-bold text-white mb-2">Law Enforcement / Legal Request Action</h3>
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
                        @error('legalComplianceNotes') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button>
                            <button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save Status</button>
                        </div>
                    </form>
                @endif

                @if($showStateComplianceModal)
                    <h3 class="text-lg font-bold text-white mb-3">{{ $editingStateComplianceId ? 'Edit' : 'New' }} State Compliance Rule</h3>
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
                            <input type="checkbox" wire:model="stateComplianceForm.requires_id_verification" class="rounded border-slate-600 bg-slate-950 text-rose-600">
                            Require Mandatory Digital ID Verification
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="stateComplianceForm.requires_age_gate" class="rounded border-slate-600 bg-slate-950 text-rose-600">
                            Require 18+ Age Gate Affirmation
                        </label>
                        <label class="block text-sm">Mandatory Warning Text
                            <textarea wire:model="stateComplianceForm.mandatory_warning_text" rows="2" class="{{ $input }} mt-1"></textarea>
                        </label>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button>
                            <button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save Rule</button>
                        </div>
                    </form>
                @endif

                @if($showPreservationModal && $inspectedPreservation)
                    <h3 class="text-lg font-bold text-white mb-1">Evidence Hold #{{ $inspectedPreservation->id }}</h3>
                    <p class="text-xs text-slate-400 mb-3">{{ $inspectedPreservation->hold_reason }} · Frozen by {{ $inspectedPreservation->frozenBy?->name ?? 'System' }}</p>
                    <div class="text-xs font-bold text-slate-400 mb-1">Snapshot JSON Payload</div>
                    <pre class="text-[11px] bg-slate-950 border border-slate-800 rounded-lg p-3 max-h-60 overflow-y-auto whitespace-pre-wrap font-mono text-slate-300">{{ json_encode($inspectedPreservation->snapshot_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    <div class="flex justify-end pt-3">
                        <button type="button" wire:click="closeModals" class="px-3 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-sm">Close</button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

