<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F8FAFC] text-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin Dashboard' }} — LocalDateHub</title>
    <meta name="robots" content="noindex, nofollow">

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
                    <div class="relative" @click.away="notificationsOpen = false">
                        <button @click="notificationsOpen = !notificationsOpen" class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 hover:text-slate-900 transition relative">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <!-- Pink Badge '3' -->
                            <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-[#F20B83] text-white text-[10px] font-bold flex items-center justify-center shadow">
                                3
                            </span>
                        </button>
                        <div x-show="notificationsOpen" x-cloak class="absolute right-0 mt-2 w-72 rounded-2xl bg-white border border-slate-200 shadow-2xl p-3 z-50 text-xs">
                            <div class="font-bold text-slate-900 mb-2">Notifications (3 unread)</div>
                            <div class="space-y-2 text-slate-700">
                                <div class="p-2 rounded-xl bg-slate-50 border border-slate-200">New listing pending moderation in New York.</div>
                                <div class="p-2 rounded-xl bg-slate-50 border border-slate-200">Payment received: $49.00 for Featured Plan.</div>
                                <div class="p-2 rounded-xl bg-slate-50 border border-slate-200">New user registered with 18+ age verification.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Language Selector (US Flag + English) -->
                    <div class="relative hidden sm:block" @click.away="langOpen = false">
                        <button @click="langOpen = !langOpen" class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-bold text-slate-700 hover:text-slate-900 transition">
                            <span class="text-sm">🇺🇸</span>
                            <span>English</span>
                            <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>

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

                        <div x-show="userMenuOpen" x-cloak class="absolute right-0 mt-2 w-48 rounded-2xl bg-white border border-slate-200 shadow-2xl py-1.5 z-50 text-xs">
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 font-medium text-slate-700 hover:bg-pink-50 hover:text-[#F20B83]">Admin Console</a>
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
