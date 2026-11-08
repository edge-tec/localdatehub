<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#100D20] text-slate-100 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login to Your Account — LocalDateHub | Connect • Meet • Enjoy</title>
    <meta name="description" content="Sign in to your LocalDateHub account to access your local adult classified advertisements, verified messages, and connections.">
    <meta name="robots" content="noindex, nofollow">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col font-sans selection:bg-[#F20B83] selection:text-white bg-[#100D20] text-slate-100 relative overflow-x-hidden">

    <!-- Ambient Gradient Background Orbs -->
    <div class="fixed top-0 left-1/4 w-[600px] h-[600px] bg-[#8B35F6]/15 rounded-full blur-[140px] pointer-events-none -z-10"></div>
    <div class="fixed bottom-0 right-1/4 w-[600px] h-[600px] bg-[#F20B83]/15 rounded-full blur-[140px] pointer-events-none -z-10"></div>

    <!-- Top Navigation Header -->
    <header class="w-full bg-[#171328]/80 backdrop-blur-md border-b border-[#2A233D] sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- Brand Logo & Tagline -->
                <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-[#F20B83] group-hover:scale-105 transition">
                        <svg class="w-9 h-9 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-white tracking-tight leading-none">
                            LocalDate<span class="text-[#F20B83]">Hub</span>
                        </div>
                        <div class="text-[11px] font-semibold text-slate-400 tracking-wider mt-1">
                            Connect • Meet • Enjoy
                        </div>
                    </div>
                </a>

                <!-- Header Right: Language Selector & Register Button -->
                <div class="flex items-center space-x-4" x-data="{ langOpen: false }">
                    
                    <!-- Language Selector (English Default) -->
                    <div class="relative hidden sm:block" @click.away="langOpen = false">
                        <button 
                            @click="langOpen = !langOpen" 
                            class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-[#201833] border border-[#33264E] text-xs font-semibold text-slate-200 hover:text-white transition"
                        >
                            <span class="text-sm">🇺🇸</span>
                            <span>English</span>
                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div 
                            x-show="langOpen" 
                            x-cloak 
                            class="absolute right-0 mt-2 w-36 rounded-xl bg-[#1C172B] border border-[#33264E] shadow-2xl py-1 z-50 text-xs"
                        >
                            <a href="#" class="block px-3 py-1.5 text-white bg-[#F20B83]/20 font-bold">🇺🇸 English</a>
                            <a href="#" class="block px-3 py-1.5 text-slate-300 hover:text-white hover:bg-[#2A233D]">🇪🇸 Español</a>
                            <a href="#" class="block px-3 py-1.5 text-slate-300 hover:text-white hover:bg-[#2A233D]">🇫🇷 Français</a>
                        </div>
                    </div>

                    <!-- Register Free Link -->
                    <a 
                        href="{{ route('register') }}" 
                        class="bg-transparent hover:bg-[#F20B83]/10 border-2 border-[#F20B83] text-[#F20B83] hover:text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm"
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
            <div class="lg:col-span-7 relative rounded-3xl overflow-hidden shadow-2xl border border-[#2D2146] min-h-[580px] lg:min-h-[640px] flex flex-col justify-between p-8 sm:p-12 group">
                <!-- Background Lifestyle Photo -->
                <img 
                    src="{{ asset('images/hero-model.jpg') }}" 
                    alt="LocalDateHub Lifestyle" 
                    class="absolute inset-0 w-full h-full object-cover object-center group-hover:scale-105 transition duration-700 -z-10"
                />

                <!-- Dark Purple / Violet Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-tr from-[#100D20] via-[#21112F]/85 to-[#100D20]/75 -z-10"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-[#100D20] via-transparent to-transparent -z-10"></div>

                <!-- Top Headline & Subtitle -->
                <div class="relative z-10 space-y-3">
                    <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-[#F20B83]/20 border border-[#F20B83]/40 text-[#F20B83] text-xs font-extrabold uppercase tracking-wider">
                        <span>★</span>
                        <span>Premium Adult Connections</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-tight">
                        Welcome <span class="text-transparent bg-clip-text bg-gradient-to-r from-white via-pink-200 to-[#F20B83]">Back!</span>
                    </h1>
                    <p class="text-base sm:text-lg text-slate-200 font-medium max-w-lg">
                        Connect with amazing people in your area. Discreet, safe, and privacy-protected.
                    </p>
                </div>

                <!-- Three Key Features with Neon Pink Circular Icons -->
                <div class="relative z-10 my-8 space-y-5">
                    
                    <!-- Feature 1: Find Your Match -->
                    <div class="flex items-start space-x-4 p-3.5 rounded-2xl bg-[#1C142E]/70 border border-[#3A2958] backdrop-blur-md hover:border-[#F20B83]/60 transition group/feat">
                        <div class="w-11 h-11 rounded-full bg-[#F20B83]/20 border border-[#F20B83] flex items-center justify-center text-[#F20B83] shrink-0 group-hover/feat:scale-110 transition shadow-[0_0_15px_rgba(242,11,131,0.4)]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white group-hover/feat:text-[#F20B83] transition">Find Your Match</h3>
                            <p class="text-xs text-slate-300 mt-0.5">Meet verified local adults seeking genuine dates and connections.</p>
                        </div>
                    </div>

                    <!-- Feature 2: Share Your Profile -->
                    <div class="flex items-start space-x-4 p-3.5 rounded-2xl bg-[#1C142E]/70 border border-[#3A2958] backdrop-blur-md hover:border-[#F20B83]/60 transition group/feat">
                        <div class="w-11 h-11 rounded-full bg-[#F20B83]/20 border border-[#F20B83] flex items-center justify-center text-[#F20B83] shrink-0 group-hover/feat:scale-110 transition shadow-[0_0_15px_rgba(242,11,131,0.4)]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white group-hover/feat:text-[#F20B83] transition">Share Your Profile</h3>
                            <p class="text-xs text-slate-300 mt-0.5">Post classifieds, upload photos, and build your local dating network.</p>
                        </div>
                    </div>

                    <!-- Feature 3: Safe & Secure -->
                    <div class="flex items-start space-x-4 p-3.5 rounded-2xl bg-[#1C142E]/70 border border-[#3A2958] backdrop-blur-md hover:border-[#F20B83]/60 transition group/feat">
                        <div class="w-11 h-11 rounded-full bg-[#F20B83]/20 border border-[#F20B83] flex items-center justify-center text-[#F20B83] shrink-0 group-hover/feat:scale-110 transition shadow-[0_0_15px_rgba(242,11,131,0.4)]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white group-hover/feat:text-[#F20B83] transition">Safe & Secure</h3>
                            <p class="text-xs text-slate-300 mt-0.5">Your privacy is our priority. Encrypted communications and 100% human moderation.</p>
                        </div>
                    </div>

                </div>

                <!-- Bottom Promotional Message with Decorative Neon Heart -->
                <div class="relative z-10 pt-4 border-t border-[#3A2958] flex items-center justify-between">
                    <div>
                        <div class="text-sm font-extrabold text-white">Join LocalDateHub Today!</div>
                        <div class="text-xs text-slate-300">Thousands of local personal ads updated daily across all 50 US states.</div>
                    </div>

                    <!-- Glowing Neon Heart Icon -->
                    <div class="text-[#F20B83] shrink-0">
                        <svg class="w-8 h-8 drop-shadow-[0_0_15px_rgba(242,11,131,0.8)] animate-pulse" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                    </div>
                </div>

            </div>

            <!-- RIGHT LOGIN CARD (~40% width on desktop) -->
            <div class="lg:col-span-5">
                <div class="bg-[#1C142E]/90 border-2 border-[#F20B83]/50 rounded-3xl p-6 sm:p-10 shadow-[0_0_60px_rgba(242,11,131,0.22)] backdrop-blur-2xl relative" x-data="{ showPass: false, submitting: false }">
                    
                    <!-- Card Header -->
                    <div class="text-center mb-6">
                        <!-- Small Heart Logo -->
                        <div class="w-12 h-12 rounded-full bg-[#F20B83]/20 border border-[#F20B83] flex items-center justify-center text-[#F20B83] mx-auto mb-3 shadow-[0_0_20px_rgba(242,11,131,0.4)]">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                        </div>

                        <div class="text-xs font-extrabold text-[#F20B83] uppercase tracking-wider">
                            LocalDateHub
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-1">
                            Login to Your Account
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1">
                            Welcome back! Please sign in to continue.
                        </p>
                    </div>

                    <!-- Flash Notifications / Errors -->
                    @if(session('status'))
                        <div class="mb-4 p-3.5 rounded-xl bg-emerald-950/80 border border-emerald-500/50 text-emerald-200 text-xs flex items-center space-x-2">
                            <span>✓</span>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 p-3.5 rounded-xl bg-rose-950/80 border border-rose-500/50 text-rose-200 text-xs flex items-center space-x-2">
                            <span>✕</span>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @if($errors->has('login') || $errors->has('email'))
                        <div class="mb-4 p-3.5 rounded-xl bg-rose-950/80 border border-rose-500/50 text-rose-200 text-xs flex items-center space-x-2">
                            <span>✕</span>
                            <span>{{ $errors->first('login') ?: $errors->first('email') }}</span>
                        </div>
                    @endif

                    <!-- Login Form -->
                    <form method="POST" action="{{ route('login') }}" class="space-y-4" @submit="submitting = true">
                        @csrf

                        <!-- Email Address / Username -->
                        <div>
                            <label for="login" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">
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
                                    placeholder="Email Address" 
                                    class="w-full bg-[#120E22] border border-[#3A2958] focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500 outline-none transition"
                                />
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                                    Password <span class="text-[#F20B83]">*</span>
                                </label>
                                <a 
                                    href="{{ route('password.request') }}" 
                                    class="text-xs font-semibold text-[#F20B83] hover:text-[#ff389b] hover:underline transition"
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
                                    class="w-full bg-[#120E22] border border-[#3A2958] focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] rounded-xl pl-10 pr-11 py-3 text-sm text-white placeholder-slate-500 outline-none transition"
                                />
                                <!-- Password Visibility Toggle Button -->
                                <button 
                                    type="button" 
                                    @click="showPass = !showPass" 
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white transition"
                                    aria-label="Toggle password visibility"
                                >
                                    <template x-if="!showPass">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </template>
                                    <template x-if="showPass">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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
                                    class="w-4 h-4 rounded bg-[#120E22] border-[#3A2958] text-[#F20B83] focus:ring-[#F20B83]"
                                />
                                <span class="text-xs text-slate-300 font-medium">Remember me</span>
                            </label>
                        </div>

                        <!-- Login Now Button -->
                        <button 
                            type="submit" 
                            :disabled="submitting" 
                            class="w-full mt-3 bg-gradient-to-r from-[#F20B83] via-[#FF1493] to-[#8B35F6] hover:opacity-95 disabled:opacity-50 text-white font-extrabold py-3.5 px-4 rounded-xl text-sm transition shadow-lg shadow-[#F20B83]/30 flex items-center justify-center space-x-2 group cursor-pointer"
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
                            <div class="w-full border-t border-[#3A2958]"></div>
                        </div>
                        <span class="relative bg-[#1C142E] px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            Or Continue With
                        </span>
                    </div>

                    <!-- Social Login Options (Google, Facebook) -->
                    <div class="space-y-2.5">
                        <!-- Continue with Google -->
                        <a 
                            href="{{ route('social.redirect', 'google') }}" 
                            class="w-full flex items-center justify-center space-x-3 bg-[#120E22] hover:bg-[#1A1430] border border-[#3A2958] text-white text-xs font-bold py-2.5 px-4 rounded-xl transition hover:border-[#F20B83]/50"
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

                        <!-- Continue with Facebook -->
                        <a 
                            href="{{ route('social.redirect', 'facebook') }}" 
                            class="w-full flex items-center justify-center space-x-3 bg-[#120E22] hover:bg-[#1A1430] border border-[#3A2958] text-white text-xs font-bold py-2.5 px-4 rounded-xl transition hover:border-[#F20B83]/50"
                        >
                            <!-- Facebook Blue SVG Icon -->
                            <svg class="w-4 h-4 fill-[#1877F2] shrink-0" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <span>Continue with Facebook</span>
                        </a>
                    </div>

                    <!-- Bottom Registration Navigation Link -->
                    <div class="mt-6 pt-6 border-t border-[#3A2958] text-center text-xs text-slate-300">
                        <span>New to LocalDateHub?</span>
                        <a 
                            href="{{ route('register') }}" 
                            class="font-bold text-[#F20B83] hover:text-[#ff389b] hover:underline ml-1 inline-flex items-center space-x-1"
                        >
                            <span>Create an Account</span>
                            <span>→</span>
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <!-- Full-Width Dark Footer -->
    <footer class="w-full bg-[#0E0B1A] border-t border-[#231A38] mt-12 py-10 text-slate-300 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            <!-- Three Information Groups (Security, Community, Support) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-8 border-b border-[#231A38]">
                
                <!-- 1. Security -->
                <div class="flex items-center space-x-3.5 p-4 rounded-2xl bg-[#161129] border border-[#2B2144]">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/40 flex items-center justify-center text-emerald-400 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-extrabold text-white text-sm">100% Secure</div>
                        <div class="text-[11px] text-slate-400">Your Data is Safe & Encrypted.</div>
                    </div>
                </div>

                <!-- 2. Community -->
                <div class="flex items-center space-x-3.5 p-4 rounded-2xl bg-[#161129] border border-[#2B2144]">
                    <div class="w-10 h-10 rounded-xl bg-[#8B35F6]/15 border border-[#8B35F6]/40 flex items-center justify-center text-[#8B35F6] shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.999-3.199a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-extrabold text-white text-sm">Active Community</div>
                        <div class="text-[11px] text-slate-400">Join Thousands of Verified Local Members.</div>
                    </div>
                </div>

                <!-- 3. Support -->
                <div class="flex items-center space-x-3.5 p-4 rounded-2xl bg-[#161129] border border-[#2B2144]">
                    <div class="w-10 h-10 rounded-xl bg-[#F20B83]/15 border border-[#F20B83]/40 flex items-center justify-center text-[#F20B83] shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 1.5a9 9 0 00-9 9v1.5a3 3 0 003 3h.75a.75.75 0 00.75-.75V9.75a.75.75 0 00-.75-.75H4.5a7.5 7.5 0 0115 0h-1.5a.75.75 0 00-.75.75v4.5c0 .414.336.75.75.75h.75a3 3 0 003-3V10.5a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-extrabold text-white text-sm">24/7 Support</div>
                        <div class="text-[11px] text-slate-400">We're Here to Help You Connect Safely.</div>
                    </div>
                </div>

            </div>

            <!-- Footer Navigation & Social Links -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                
                <!-- Links -->
                <div class="flex flex-wrap items-center gap-6 text-xs text-slate-400">
                    <a href="{{ Route::has('pages.about') ? route('pages.about') : url('/about') }}" class="hover:text-white transition">About Us</a>
                    <a href="{{ Route::has('pages.privacy') ? route('pages.privacy') : url('/privacy') }}" class="hover:text-white transition">Privacy Policy</a>
                    <a href="{{ Route::has('pages.terms') ? route('pages.terms') : url('/terms') }}" class="hover:text-white transition">Terms of Service</a>
                    <a href="{{ Route::has('pages.contact') ? route('pages.contact') : url('/contact') }}" class="hover:text-white transition">Contact Us</a>
                </div>

                <!-- Social Media Icons (Facebook, X, YouTube, Instagram) -->
                <div class="flex items-center space-x-4 text-slate-400">
                    <a href="#" class="hover:text-[#F20B83] transition" aria-label="Facebook">
                        <svg class="w-4 h-4 fill-currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="#" class="hover:text-[#F20B83] transition" aria-label="X / Twitter">
                        <svg class="w-4 h-4 fill-currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    <a href="#" class="hover:text-[#F20B83] transition" aria-label="YouTube">
                        <svg class="w-4 h-4 fill-currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </a>
                    <a href="#" class="hover:text-[#F20B83] transition" aria-label="Instagram">
                        <svg class="w-4 h-4 fill-currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                </div>

            </div>

            <!-- Legal Compliance & Disclaimer -->
            <div class="pt-4 border-t border-[#1C172B] text-center text-[11px] text-slate-500 space-y-1">
                <p>&copy; {{ date('Y') }} LocalDateHub. All rights reserved. Strictly 18+ adult classifieds and local dating community.</p>
                <p>Commercial sexual solicitation, prostitution, and unlawful trafficking are strictly prohibited and actively reported to law enforcement.</p>
            </div>

        </div>
    </footer>

</body>
</html>
