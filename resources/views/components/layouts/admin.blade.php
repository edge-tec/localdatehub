<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Enterprise Admin Portal' }} — {{ config('localdatehub.name', 'LocalAdZone') }}</title>
    <meta name="robots" content="noindex, nofollow">

    <!-- Design Tokens & Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full flex flex-col font-sans selection:bg-rose-500 selection:text-white bg-slate-950">
    <!-- Top Administrative Header -->
    <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Brand & Environment Badge -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-2 text-rose-500 font-black text-xl tracking-wider">
                        <span>🛡️</span>
                        <span>LOCALADZONE <span class="text-xs text-rose-400 font-semibold uppercase tracking-widest border border-rose-500/40 rounded px-1.5 py-0.5">Admin</span></span>
                    </a>
                    <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-950 text-red-300 border border-red-800">
                        AUDITED SECURE CONSOLE
                    </span>
                </div>

                <!-- Admin User Info & Navigation -->
                <div class="flex items-center space-x-4">
                    <a href="{{ url('/') }}" target="_blank" class="text-xs text-slate-400 hover:text-white transition flex items-center space-x-1">
                        <span>View Public Site &rarr;</span>
                    </a>

                    @auth
                        <div class="flex items-center space-x-3 border-l border-slate-800 pl-4">
                            <div class="text-right">
                                <div class="text-xs font-semibold text-slate-200">{{ auth()->user()->name }}</div>
                                <span class="inline-block text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.2 rounded bg-rose-900/60 text-rose-300 border border-rose-700/50">
                                    {{ str_replace('_', ' ', auth()->user()->role) }}
                                </span>
                            </div>

                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-xs font-medium text-slate-400 hover:text-rose-400 border border-slate-700 hover:border-rose-500/50 rounded-lg px-2.5 py-1.5 transition">
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Global Alert Notifications -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full mt-4">
        @if(session('status'))
            <x-alert type="success" :message="session('status')" />
        @endif
        @if(session('error'))
            <x-alert type="danger" :message="session('error')" />
        @endif
        @if(isset($errors) && $errors->any())
            <x-alert type="danger" :message="$errors->first()" />
        @endif
    </div>

    <!-- Main Admin Workspace -->
    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-6">
        {{ $slot }}
    </main>

    <!-- Admin Footer -->
    <footer class="bg-slate-900 border-t border-slate-800 py-4 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center text-xs text-slate-500">
            <div>
                &copy; {{ date('Y') }} LocalAdZone Enterprise Administration Console. All operations logged in immutable audit records.
            </div>
            <div class="mt-2 md:mt-0 flex space-x-4">
                <span>Compliance: 18 U.S.C. 2257 Record-Keeping Active</span>
                <span>Session IP: {{ request()->ip() }}</span>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>

