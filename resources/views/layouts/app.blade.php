<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $seo->title ?? $title ?? 'LocalAdZone — USA Adult Personal Classifieds' }}</title>
    <meta name="description" content="{{ $seo->description ?? $description ?? 'LocalAdZone is the privacy-conscious local adult personal classifieds platform across all 50 US states.' }}">
    <meta name="robots" content="{{ $seo->robots ?? 'index, follow' }}">
    @if(!empty($seo?->canonicalUrl))
        <link rel="canonical" href="{{ $seo->canonicalUrl }}">
    @endif

    <!-- Open Graph & Social Cards -->
    <meta property="og:title" content="{{ $seo->title ?? $title ?? 'LocalAdZone' }}">
    <meta property="og:description" content="{{ $seo->description ?? $description ?? 'Adult Personal Classifieds' }}">
    <meta property="og:url" content="{{ $seo->canonicalUrl ?? url()->current() }}">
    <meta property="og:type" content="{{ $seo->ogType ?? 'website' }}">
    <meta property="og:image" content="{{ $seo->ogImage ?? asset('images/og-localadzone.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">

    @if(!empty($seo?->structuredData))
        <script type="application/ld+json">
        {!! json_encode($seo->structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>
    @endif

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
        @if($errors->any())
            <x-alert type="danger" :message="$errors->first()" />
        @endif
    </div>

    <!-- Main Page Content -->
    <main class="flex-grow">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Global Footer -->
    <x-footer />

    @livewireScripts
</body>
</html>
