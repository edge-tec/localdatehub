<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#111126] text-slate-100 antialiased">
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
<body class="min-h-full flex flex-col font-sans selection:bg-[#F20B83] selection:text-white bg-[#111126] text-slate-100">

    <!-- Top Administrative Navigation Bar -->
    <header class="bg-[#171328] border-b border-[#2A233D] sticky top-0 z-50 shadow-lg" x-data="{ userMenuOpen: false, langOpen: false, notificationsOpen: false }">
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
                            <div class="text-xl font-black text-white tracking-tight leading-none">
                                LocalDate<span class="text-[#F20B83]">Hub</span>
                            </div>
                            <div class="text-[10px] font-medium text-slate-400 tracking-wider mt-0.5">
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
                            class="w-full bg-[#201833] border border-[#33264E] rounded-xl pl-10 pr-4 py-2 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] transition"
                        />
                    </div>
                </div>

                <!-- Right: Notifications, Language & Admin Profile -->
                <div class="flex items-center space-x-4">
                    
                    <!-- Notification Bell -->
                    <div class="relative" @click.away="notificationsOpen = false">
                        <button @click="notificationsOpen = !notificationsOpen" class="w-9 h-9 rounded-xl bg-[#201833] border border-[#33264E] flex items-center justify-center text-slate-300 hover:text-white transition relative">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <!-- Pink Badge '3' -->
                            <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-[#F20B83] text-white text-[10px] font-bold flex items-center justify-center shadow">
                                3
                            </span>
                        </button>
                        <div x-show="notificationsOpen" x-cloak class="absolute right-0 mt-2 w-72 rounded-xl bg-[#1C172B] border border-[#33264E] shadow-2xl p-3 z-50 text-xs">
                            <div class="font-bold text-white mb-2">Notifications (3 unread)</div>
                            <div class="space-y-2 text-slate-300">
                                <div class="p-2 rounded bg-[#201833] border border-[#33264E]">New listing pending moderation in New York.</div>
                                <div class="p-2 rounded bg-[#201833] border border-[#33264E]">Payment received: $49.00 for Featured Plan.</div>
                                <div class="p-2 rounded bg-[#201833] border border-[#33264E]">New user registered with 18+ age verification.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Language Selector (US Flag + English) -->
                    <div class="relative hidden sm:block" @click.away="langOpen = false">
                        <button @click="langOpen = !langOpen" class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-[#201833] border border-[#33264E] text-xs font-semibold text-slate-200 hover:text-white transition">
                            <span class="text-sm">🇺🇸</span>
                            <span>English</span>
                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>

                    <!-- Admin Profile Avatar & Role -->
                    <div class="relative" @click.away="userMenuOpen = false">
                        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center space-x-2.5 p-1 rounded-xl hover:bg-[#201833] transition">
                            <img 
                                src="{{ asset('images/admin-avatar.jpg') }}" 
                                alt="Admin Avatar" 
                                class="w-8 h-8 rounded-full object-cover border-2 border-[#F20B83]"
                            />
                            <div class="text-left hidden sm:block">
                                <div class="text-xs font-bold text-white flex items-center gap-1">
                                    <span>{{ auth()->user()?->name ?? 'Super Admin' }}</span>
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </button>

                        <div x-show="userMenuOpen" x-cloak class="absolute right-0 mt-2 w-48 rounded-xl bg-[#1C172B] border border-[#33264E] shadow-2xl py-1.5 z-50 text-xs">
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-slate-200 hover:bg-[#F20B83]/20 hover:text-white">Admin Console</a>
                            <a href="{{ url('/') }}" target="_blank" class="block px-4 py-2 text-slate-200 hover:bg-[#F20B83]/20 hover:text-white">View Public Site ↗</a>
                            <div class="border-t border-[#33264E] my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-rose-400 hover:bg-[#F20B83]/20 font-semibold">Sign Out</button>
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
