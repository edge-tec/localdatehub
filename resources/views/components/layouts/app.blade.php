@php
    $brandFaviconUrl = '/favicon.ico';
    $brandSiteName = config('localdatehub.name', 'LocalDateHub');
    $brandTagline = config('localdatehub.tagline', 'Your Local Adult Classifieds & Dating Community');
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
            $brandSettings = \App\Modules\Admin\Models\SystemSetting::whereIn('key', [
                'site_favicon_url',
                'site_name',
                'site_tagline',
            ])->pluck('value', 'key');
            if (!empty($brandSettings['site_favicon_url'])) {
                $brandFaviconUrl = $brandSettings['site_favicon_url'];
            }
            if (!empty($brandSettings['site_name'])) {
                $brandSiteName = $brandSettings['site_name'];
            }
            if (!empty($brandSettings['site_tagline'])) {
                $brandTagline = $brandSettings['site_tagline'];
            }
        }
    } catch (\Throwable $e) {
        // Fallback to defaults if DB is not yet migrated
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F8FAFC] text-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? $brandSiteName }} — {{ $brandTagline }}</title>
    <meta name="description" content="{{ $description ?? 'LocalDateHub is the privacy-first local adult dating, personal classifieds, and safe date spot discovery platform.' }}">
    <meta name="robots" content="{{ $robotsMeta ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' }}">
    <link rel="canonical" href="{{ $canonicalUrl ?? url()->current() }}">
    <link rel="icon" href="{{ asset($brandFaviconUrl) }}">
    <link rel="shortcut icon" href="{{ asset($brandFaviconUrl) }}">
    <link rel="apple-touch-icon" href="{{ asset($brandFaviconUrl) }}">
    <style>[x-cloak] { display: none !important; }</style>

    <!-- OpenGraph / Social Metadata -->
    <meta property="og:title" content="{{ $title ?? config('localdatehub.name', 'LocalDateHub') }}">
    <meta property="og:description" content="{{ $description ?? 'LocalDateHub is the privacy-first local adult dating, personal classifieds, and safe date spot discovery platform.' }}">
    <meta property="og:url" content="{{ $canonicalUrl ?? url()->current() }}">
    <meta property="og:type" content="website">

    <!-- Design Tokens & Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full flex flex-col font-sans selection:bg-[#F20B65] selection:text-white bg-[#F8FAFC] text-slate-800">
    <!-- Administrator Impersonation Active Banner -->
    @if(session()->has('impersonator_admin_id'))
        <div class="bg-gradient-to-r from-amber-600 via-orange-600 to-amber-700 text-white px-4 py-2.5 text-xs font-semibold shadow-lg sticky top-0 z-[100] border-b border-amber-400/30">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-center sm:text-left">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-white text-amber-900 uppercase tracking-wider animate-pulse">
                        ⚡ Impersonation Active
                    </span>
                    <span>
                        You are browsing as <strong class="underline decoration-white/60">{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}). Original Administrator: <strong>{{ session('impersonator_admin_name', 'Administrator') }}</strong>.
                    </span>
                </div>
                <a href="{{ route('admin.impersonate.stop') }}" class="inline-flex items-center gap-1.5 px-3 py-1 bg-white hover:bg-amber-50 text-amber-900 font-extrabold rounded-lg text-xs shadow-sm transition shrink-0">
                    <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    <span>Exit &amp; Return to Admin</span>
                </a>
            </div>
        </div>
    @endif

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
