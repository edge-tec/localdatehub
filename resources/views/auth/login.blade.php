@php
    $loginFaviconUrl = '/favicon.ico';
    $loginLogoUrl = '';
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
            $loginBrand = \App\Modules\Admin\Models\SystemSetting::whereIn('key', [
                'site_favicon_url',
                'site_logo_url',
            ])->pluck('value', 'key');
            if (!empty($loginBrand['site_favicon_url'])) {
                $loginFaviconUrl = $loginBrand['site_favicon_url'];
            }
            if (!empty($loginBrand['site_logo_url'])) {
                $loginLogoUrl = $loginBrand['site_logo_url'];
            }
        }
    } catch (\Throwable $e) {
    }
    $hasCustomLoginLogo = !empty($loginLogoUrl) && !in_array($loginLogoUrl, ['/images/logo.png', '/images/logo.svg'], true);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F8FAFC] text-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login to Your Account — LocalDateHub | Connect • Meet • Enjoy</title>
    <meta name="description" content="Sign in to your LocalDateHub account to access your local adult classified advertisements, verified messages, and connections.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset($loginFaviconUrl) }}">
    <link rel="shortcut icon" href="{{ asset($loginFaviconUrl) }}">
    <link rel="apple-touch-icon" href="{{ asset($loginFaviconUrl) }}">
    <style>[x-cloak] { display: none !important; }</style>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full flex flex-col font-sans selection:bg-[#F20B83] selection:text-white bg-[#F8FAFC] text-slate-800 relative overflow-x-hidden">

    <!-- Subtle Ambient Background Glow -->
    <div class="fixed top-0 left-1/4 w-[500px] h-[500px] bg-pink-100/60 rounded-full blur-[120px] pointer-events-none -z-10"></div>
    <div class="fixed bottom-0 right-1/4 w-[500px] h-[500px] bg-purple-100/50 rounded-full blur-[120px] pointer-events-none -z-10"></div>

    <!-- Top Navigation Header (Pure White) -->
    <header class="w-full bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- Brand Logo & Tagline -->
                <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                    @if($hasCustomLoginLogo)
                        <img src="{{ asset($loginLogoUrl) }}" alt="LocalDateHub" class="h-11 max-w-[200px] object-contain" onerror="this.style.display='none'">
                    @else
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-[#F20B83] group-hover:scale-105 transition">
                            <svg class="w-9 h-9 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-slate-900 tracking-tight leading-none">
                                LocalDate<span class="text-[#F20B83]">Hub</span>
                            </div>
                            <div class="text-[11px] font-semibold text-slate-500 tracking-wider mt-1">
                                Connect • Meet • Enjoy
                            </div>
                        </div>
                    @endif
                </a>

                <!-- Header Right: Home Link & Register Button -->
                <div class="flex items-center space-x-3">
                    <a 
                        href="{{ url('/') }}" 
                        class="hidden sm:inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-xs font-bold text-slate-700 transition"
                    >
                        <span>← Back to Home</span>
                    </a>

                    <!-- Register Free Link -->
                    <a 
                        href="{{ route('register') }}" 
                        class="bg-transparent hover:bg-pink-50 border-2 border-[#F20B83] text-[#F20B83] px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-xs"
                    >
                        <span>Register Free</span>
                        <span>→</span>
                    </a>
                </div>

            </div>
        </div>
    </header>

    <!-- Main Split-Screen Container -->
    <main class="flex-grow flex items-center justify-center py-8 sm:py-12 lg:py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl w-full mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
            
            <!-- LEFT HERO SECTION (~60% width on desktop) -->
            <div class="lg:col-span-7 relative rounded-3xl overflow-hidden shadow-xl border border-slate-200 min-h-[580px] lg:min-h-[640px] flex flex-col justify-between p-8 sm:p-12 group bg-white">
                <!-- Background Lifestyle Photo -->
                <img 
                    src="{{ asset('images/hero-model.jpg') }}" 
                    alt="LocalDateHub Lifestyle" 
                    class="absolute inset-0 w-full h-full object-cover object-center group-hover:scale-105 transition duration-700 -z-10"
                />

                <!-- Light Overlay for Readable Text -->
                <div class="absolute inset-0 bg-gradient-to-tr from-white/95 via-white/85 to-white/60 -z-10"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent -z-10"></div>

                <!-- Top Headline & Subtitle -->
                <div class="relative z-10 space-y-3">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-gradient-to-r from-rose-50 via-pink-50 to-purple-50/60 border border-rose-200/90 text-[#F20B83] text-xs font-black shadow-xs">
                        <span class="w-4 h-4 rounded-full bg-[#F20B83] text-white flex items-center justify-center text-[10px] font-black">★</span>
                        <span class="tracking-wide uppercase text-[11px]">Premium Adult Connections</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight leading-tight">
                        Welcome <span class="text-[#F20B83]">Back!</span>
                    </h1>
                    <p class="text-base sm:text-lg text-slate-600 font-medium max-w-lg">
                        Connect with amazing people in your area. Discreet, safe, and privacy-protected.
                    </p>
                </div>

                <!-- Three Key Features with Neon Pink Circular Icons -->
                <div class="relative z-10 my-8 space-y-4">
                    
                    <!-- Feature 1: Find Your Match -->
                    <div class="flex items-start space-x-4 p-4 rounded-2xl bg-white/90 border border-slate-200/90 backdrop-blur-md hover:border-[#F20B83]/60 transition shadow-sm group/feat">
                        <div class="w-11 h-11 rounded-full bg-pink-50 border border-[#F20B83]/40 flex items-center justify-center text-[#F20B83] shrink-0 group-hover/feat:scale-110 transition shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 group-hover/feat:text-[#F20B83] transition">Find Your Match</h3>
                            <p class="text-xs text-slate-600 mt-0.5">Meet verified local adults seeking genuine dates and connections.</p>
                        </div>
                    </div>

                    <!-- Feature 2: Share Your Profile -->
                    <div class="flex items-start space-x-4 p-4 rounded-2xl bg-white/90 border border-slate-200/90 backdrop-blur-md hover:border-[#F20B83]/60 transition shadow-sm group/feat">
                        <div class="w-11 h-11 rounded-full bg-pink-50 border border-[#F20B83]/40 flex items-center justify-center text-[#F20B83] shrink-0 group-hover/feat:scale-110 transition shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 group-hover/feat:text-[#F20B83] transition">Share Your Profile</h3>
                            <p class="text-xs text-slate-600 mt-0.5">Post classifieds, upload photos, and build your local dating network.</p>
                        </div>
                    </div>

                    <!-- Feature 3: Safe & Secure -->
                    <div class="flex items-start space-x-4 p-4 rounded-2xl bg-white/90 border border-slate-200/90 backdrop-blur-md hover:border-[#F20B83]/60 transition shadow-sm group/feat">
                        <div class="w-11 h-11 rounded-full bg-pink-50 border border-[#F20B83]/40 flex items-center justify-center text-[#F20B83] shrink-0 group-hover/feat:scale-110 transition shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 group-hover/feat:text-[#F20B83] transition">Safe & Secure</h3>
                            <p class="text-xs text-slate-600 mt-0.5">Your privacy is our priority. Encrypted communications and 100% human moderation.</p>
                        </div>
                    </div>

                </div>

                <!-- Bottom Promotional Message with Decorative Neon Heart -->
                <div class="relative z-10 pt-4 border-t border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-black text-slate-900">Join LocalDateHub Today!</div>
                        <div class="text-xs text-slate-600">Thousands of local personal ads updated daily across all 50 US states.</div>
                    </div>

                    <!-- Glowing Neon Heart Icon -->
                    <div class="text-[#F20B83] shrink-0">
                        <svg class="w-8 h-8 drop-shadow-md animate-pulse" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                    </div>
                </div>

            </div>

            <!-- RIGHT LOGIN CARD (~40% width on desktop, Pure White Card) -->
            <div class="lg:col-span-5">
                <div class="bg-white border-2 border-slate-200/90 rounded-3xl p-6 sm:p-10 shadow-xl relative" x-data="{ showPass: false, submitting: false }">
                    
                    <!-- Card Header -->
                    <div class="text-center mb-6">
                        <!-- Small Heart Logo -->
                        <div class="w-12 h-12 rounded-full bg-pink-50 border border-[#F20B83]/30 flex items-center justify-center text-[#F20B83] mx-auto mb-3 shadow-xs">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                        </div>

                        <div class="text-xs font-black text-[#F20B83] uppercase tracking-wider">
                            LocalDateHub
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">
                            Login to Your Account
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">
                            Welcome back! Please sign in to continue.
                        </p>
                    </div>

                    <!-- Flash Notifications / Errors -->
                    @if(session('status'))
                        <div class="mb-4 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center space-x-2 font-medium">
                            <span>✓</span>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center space-x-2 font-medium">
                            <span>✕</span>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @if($errors->has('login') || $errors->has('email'))
                        <div class="mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center space-x-2 font-medium">
                            <span>✕</span>
                            <span>{{ $errors->first('login') ?: $errors->first('email') }}</span>
                        </div>
                    @endif

                    <!-- Login Form -->
                    <form method="POST" action="{{ route('login') }}" class="space-y-4" @submit="submitting = true">
                        @csrf

                        <!-- Email Address / Username -->
                        <div>
                            <label for="login" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Email Address <span class="text-[#F20B83]">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                </div>
                                <input 
                                    id="login" 
                                    type="text" 
                                    name="login" 
                                    value="{{ old('login', old('email')) }}" 
                                    required 
                                    autofocus 
                                    autocomplete="username" 
                                    placeholder="Email Address or Username" 
                                    class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] rounded-xl pl-10 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 outline-none transition"
                                />
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Password <span class="text-[#F20B83]">*</span>
                                </label>
                                <a 
                                    href="{{ route('password.request') }}" 
                                    class="text-xs font-semibold text-[#F20B83] hover:text-[#d90858] hover:underline transition"
                                >
                                    Forgot Password?
                                </a>
                            </div>

                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                </div>
                                <input 
                                    id="password" 
                                    :type="showPass ? 'text' : 'password'" 
                                    name="password" 
                                    required 
                                    autocomplete="current-password" 
                                    placeholder="••••••••" 
                                    class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] rounded-xl pl-10 pr-11 py-3 text-sm text-slate-900 placeholder-slate-400 outline-none transition"
                                />
                                <!-- Password Visibility Toggle Button -->
                                <button 
                                    type="button" 
                                    @click="showPass = !showPass" 
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-700 transition"
                                    aria-label="Toggle password visibility"
                                >
                                    <template x-if="!showPass">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </template>
                                    <template x-if="showPass">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                    </template>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center space-x-2.5 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="remember" 
                                    value="1" 
                                    class="w-4 h-4 rounded bg-slate-100 border-slate-300 text-[#F20B83] focus:ring-[#F20B83]"
                                />
                                <span class="text-xs text-slate-600 font-medium">Remember me</span>
                            </label>
                        </div>

                        <x-turnstile action="login" class="pt-1" />

                        <!-- Login Now Button -->
                        <button 
                            type="submit" 
                            :disabled="submitting" 
                            class="w-full mt-3 bg-gradient-to-r from-[#F20B83] via-[#FF1493] to-[#8B35F6] hover:opacity-95 disabled:opacity-50 text-white font-extrabold py-3.5 px-4 rounded-xl text-sm transition shadow-md shadow-[#F20B83]/25 flex items-center justify-center space-x-2 group cursor-pointer"
                        >
                            <template x-if="submitting">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span>Login Now</span>
                            <span class="group-hover:translate-x-1 transition-transform">→</span>
                        </button>
                    </form>

                    <!-- OR Divider -->
                    <div class="relative my-6 text-center">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-200"></div>
                        </div>
                        <span class="relative bg-white px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            Or Continue With
                        </span>
                    </div>

                    <!-- Social Login Options (Google) -->
                    <div class="space-y-2.5">
                        <!-- Continue with Google -->
                        <a 
                            href="{{ route('social.redirect', 'google') }}" 
                            class="w-full flex items-center justify-center space-x-3 bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 text-xs font-bold py-2.5 px-4 rounded-xl transition hover:border-[#F20B83]/50 shadow-2xs"
                        >
                            <!-- Google Color SVG Icon -->
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.8-2.4 3.66v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.15z"/>
                                <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24z"/>
                                <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.17 0 9.99 0 12s.45 3.83 1.25 5.42l4.03-3.15z"/>
                                <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                            </svg>
                            <span>Continue with Google</span>
                        </a>
                    </div>

                    <!-- Bottom Registration Navigation Link -->
                    <div class="mt-6 pt-6 border-t border-slate-200 text-center text-xs text-slate-600">
                        <span>New to LocalDateHub?</span>
                        <a 
                            href="{{ route('register') }}" 
                            class="font-bold text-[#F20B83] hover:text-[#d90858] hover:underline ml-1 inline-flex items-center space-x-1"
                        >
                            <span>Create an Account</span>
                            <span>→</span>
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <!-- Full-Width Pure White Footer -->
    <footer class="w-full bg-white border-t border-slate-200 mt-12 py-10 text-slate-600 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            <!-- Three Information Groups (Security, Community, Support) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-8 border-b border-slate-200">
                
                <!-- 1. Security -->
                <div class="flex items-center space-x-3.5 p-4 rounded-2xl bg-slate-50 border border-slate-200">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-extrabold text-slate-900 text-sm">100% Secure</div>
                        <div class="text-[11px] text-slate-500">Your Data is Safe &amp; Encrypted.</div>
                    </div>
                </div>

                <!-- 2. Community -->
                <div class="flex items-center space-x-3.5 p-4 rounded-2xl bg-slate-50 border border-slate-200">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-200 flex items-center justify-center text-purple-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.999-3.199a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-extrabold text-slate-900 text-sm">Active Community</div>
                        <div class="text-[11px] text-slate-500">Join Thousands of Verified Local Members.</div>
                    </div>
                </div>

                <!-- 3. Support -->
                <div class="flex items-center space-x-3.5 p-4 rounded-2xl bg-slate-50 border border-slate-200">
                    <div class="w-10 h-10 rounded-xl bg-pink-50 border border-pink-200 flex items-center justify-center text-[#F20B83] shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 1.5a9 9 0 00-9 9v1.5a3 3 0 003 3h.75a.75.75 0 00.75-.75V9.75a.75.75 0 00-.75-.75H4.5a7.5 7.5 0 0115 0h-1.5a.75.75 0 00-.75.75v4.5c0 .414.336.75.75.75h.75a3 3 0 003-3V10.5a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-extrabold text-slate-900 text-sm">24/7 Support</div>
                        <div class="text-[11px] text-slate-500">We're Here to Help You Connect Safely.</div>
                    </div>
                </div>

            </div>

            <!-- Footer Navigation & Social Links -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                
                <!-- Links -->
                <div class="flex flex-wrap items-center gap-6 text-xs text-slate-600 font-medium">
                    <a href="{{ Route::has('pages.about') ? route('pages.about') : url('/about') }}" class="hover:text-[#F20B83] transition">About Us</a>
                    <a href="{{ Route::has('pages.privacy') ? route('pages.privacy') : url('/privacy') }}" class="hover:text-[#F20B83] transition">Privacy Policy</a>
                    <a href="{{ Route::has('pages.terms') ? route('pages.terms') : url('/terms') }}" class="hover:text-[#F20B83] transition">Terms of Service</a>
                    <a href="{{ Route::has('pages.contact') ? route('pages.contact') : url('/contact') }}" class="hover:text-[#F20B83] transition">Contact Us</a>
                </div>

                <!-- Platform Support & Telegram Link -->
                <div class="flex items-center space-x-4 text-slate-500">
                    <a href="https://t.me/LocalDateHubSupport" target="_blank" rel="noopener noreferrer" class="hover:text-[#F20B83] transition flex items-center gap-1.5 text-xs font-bold" aria-label="Official Telegram Support">
                        <span>💬</span>
                        <span>Telegram Support</span>
                    </a>
                    <span class="text-slate-300">&bull;</span>
                    <a href="{{ route('pages.contact') }}" class="hover:text-[#F20B83] transition text-xs font-bold" aria-label="Support Desk">
                        <span>Help Desk</span>
                    </a>
                </div>

            </div>

            <!-- Legal Compliance & Disclaimer -->
            <div class="pt-4 border-t border-slate-200 text-center text-[11px] text-slate-500 space-y-1">
                <p>&copy; {{ date('Y') }} LocalDateHub. All rights reserved. Strictly 18+ adult classifieds and local dating community.</p>
                <p>Commercial sexual solicitation, prostitution, and unlawful trafficking are strictly prohibited and actively reported to law enforcement.</p>
            </div>

        </div>
    </footer>

    @livewireScripts
</body>
</html>
