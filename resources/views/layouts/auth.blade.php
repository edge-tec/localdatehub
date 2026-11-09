<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 text-slate-800 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Account' }} — {{ config('localdatehub.name', 'LocalDateHub') }}</title>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col font-sans selection:bg-rose-500 selection:text-white bg-slate-50">
    <!-- Top Bar -->
    <header class="border-b border-slate-200 bg-white backdrop-blur-md py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center space-x-2.5 group">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-rose-600 to-pink-500 flex items-center justify-center text-white font-bold shadow-md shadow-rose-500/20">
                    L
                </div>
                <span class="text-xl font-bold tracking-tight text-slate-900">LocalDate<span class="text-[#F20B83]">Hub</span></span>
            </a>

            <div class="flex items-center space-x-3 text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-[#F20B83] border border-rose-200 uppercase tracking-wider">Strictly 18+</span>
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

    <!-- Minimal Footer -->
    <footer class="border-t border-slate-900 py-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 space-y-1">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved. Adult personal classifieds for verified adults 18+.</p>
            <p class="text-[11px] text-slate-600">Strictly prohibits prostitution, human trafficking, and non-consensual imagery.</p>
        </div>
    </footer>
</body>
</html>

