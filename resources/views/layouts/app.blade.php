<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F8F7FA] text-[#171923] antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $seo->title ?? $seoTitle ?? $title ?? 'LocalDateHub — Local Classifieds & Dating Marketplace' }}</title>
    <meta name="description" content="{{ $seo->description ?? $seoDescription ?? $description ?? 'LocalDateHub is the privacy-conscious local classifieds and personal dating marketplace across the UK, Canada, Australia, Ireland, New Zealand, and USA.' }}">
    <meta name="robots" content="{{ $seo->robots ?? 'index, follow' }}">
    @if(!empty($seo?->canonicalUrl) || !empty($canonicalUrl))
        <link rel="canonical" href="{{ $seo->canonicalUrl ?? $canonicalUrl }}">
    @endif
    @if(!empty($hreflangLinks) && is_iterable($hreflangLinks))
        @foreach($hreflangLinks as $link)
            <link rel="alternate" hreflang="{{ $link['hreflang'] }}" href="{{ $link['href'] }}">
        @endforeach
    @endif

    <!-- Open Graph & Social Cards -->
    <meta property="og:title" content="{{ $seo->title ?? $seoTitle ?? $title ?? 'LocalDateHub' }}">
    <meta property="og:description" content="{{ $seo->description ?? $seoDescription ?? $description ?? 'Local Classifieds & Dating Marketplace' }}">
    <meta property="og:url" content="{{ $seo->canonicalUrl ?? $canonicalUrl ?? url()->current() }}">
    <meta property="og:type" content="{{ $seo->ogType ?? 'website' }}">
    <meta property="og:image" content="{{ $seo->ogImage ?? asset('images/promo-banner.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">

    @if(!empty($seo?->structuredData))
        <script type="application/ld+json">
        {!! json_encode($seo->structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>
    @endif
    @stack('head')

    <!-- Design Tokens & Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full flex flex-col font-sans selection:bg-[#F20B65] selection:text-white bg-[#F8F7FA] text-[#171923]">
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
