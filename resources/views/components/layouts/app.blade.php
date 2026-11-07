<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('localdatehub.name', 'LocalDateHub') }} — {{ config('localdatehub.tagline') }}</title>
    <meta name="description" content="{{ $description ?? 'LocalDateHub is the privacy-first local adult dating, personal classifieds, and safe date spot discovery platform.' }}">

    <!-- Design Tokens & Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full flex flex-col font-sans selection:bg-rose-500 selection:text-white">
    <!-- 18+ Adult Safety Notice Banner -->
    <x-safety-banner />

    <!-- Main Navigation Bar -->
    <x-navbar />

    <!-- Flash Alert Notifications -->
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

    <!-- Main Page Content -->
    <main class="flex-grow">
        {{ $slot }}
    </main>

    <!-- Global Footer -->
    <x-footer />

    @livewireScripts
</body>
</html>
