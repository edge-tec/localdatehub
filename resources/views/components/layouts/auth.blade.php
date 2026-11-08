<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F8FAFC] text-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Account' }} — {{ config('localdatehub.name', 'LocalDateHub') }}</title>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col font-sans selection:bg-[#F20B83] selection:text-white bg-[#F8FAFC] text-slate-800">
    <!-- Top Bar -->
    <header class="border-b border-slate-200 bg-white py-4 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center space-x-2.5 group">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-[#F20B83] group-hover:scale-105 transition">
                    <svg class="w-7 h-7 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                    </svg>
                </div>
                <span class="text-xl font-black tracking-tight text-slate-900">LocalDate<span class="text-[#F20B83]">Hub</span></span>
            </a>

            <div class="flex items-center space-x-3 text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-pink-50 text-[#F20B83] border border-pink-200 uppercase tracking-wider">Strictly 18+</span>
                <a href="{{ url('/') }}" class="text-slate-600 hover:text-[#F20B83] font-semibold transition">← Back to Site</a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-6">
            @if(session('status'))
                <x-alert type="success" :message="session('status')" />
            @endif
            @if(session('error'))
                <x-alert type="danger" :message="session('error')" />
            @endif
            @if($errors->any())
                <x-alert type="danger" :message="$errors->first()" />
            @endif

            {{ $slot }}
        </div>
    </main>

    <!-- Minimal White Footer -->
    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 space-y-1">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved. Adult personal classifieds for verified adults 18+.</p>
            <p class="text-[11px] text-slate-400">Strictly prohibits prostitution, human trafficking, and non-consensual imagery.</p>
        </div>
    </footer>
</body>
</html>
