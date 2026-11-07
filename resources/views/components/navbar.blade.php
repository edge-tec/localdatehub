<header class="bg-slate-900/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-40" x-data="{ mobileMenuOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo & Brand -->
            <div class="flex items-center space-x-3">
                <a href="{{ url('/') }}" class="flex items-center space-x-2.5 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-rose-600 to-pink-500 flex items-center justify-center text-white shadow-lg shadow-rose-500/25 group-hover:scale-105 transition duration-200">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">LocalDate<span class="text-rose-500">Hub</span></span>
                </a>
            </div>

            <!-- Desktop Navigation Links -->
            <nav class="hidden md:flex items-center space-x-6 text-sm font-medium text-slate-300">
                <a href="{{ url('/') }}" class="hover:text-white transition">Browse Personals</a>
                <a href="#categories" class="hover:text-white transition">Categories</a>
                <a href="#date-spots" class="hover:text-white transition">Date Spots</a>
                <a href="{{ url('/health') }}" class="hover:text-white transition">System Health</a>
                <a href="#safety-standards" class="text-rose-400 hover:text-rose-300 transition flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Trust & Safety</span>
                </a>
            </nav>

            <!-- Actions / Auth CTA -->
            <div class="hidden md:flex items-center space-x-3">
                <a href="#sign-in" class="text-sm font-medium text-slate-300 hover:text-white px-3 py-2">Sign In</a>
                <a href="#post-ad" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white shadow-md shadow-rose-600/20 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Post Personal Ad
                </a>
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="md:hidden flex items-center">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-slate-400 hover:text-white p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer -->
    <div x-show="mobileMenuOpen" class="md:hidden border-b border-slate-800 bg-slate-900 px-4 pt-2 pb-4 space-y-2">
        <a href="{{ url('/') }}" class="block px-3 py-2 text-base font-medium text-slate-300 hover:bg-slate-800 rounded-md">Browse Personals</a>
        <a href="#categories" class="block px-3 py-2 text-base font-medium text-slate-300 hover:bg-slate-800 rounded-md">Categories</a>
        <a href="#date-spots" class="block px-3 py-2 text-base font-medium text-slate-300 hover:bg-slate-800 rounded-md">Date Spots</a>
        <a href="{{ url('/health') }}" class="block px-3 py-2 text-base font-medium text-slate-300 hover:bg-slate-800 rounded-md">System Health</a>
        <a href="#safety-standards" class="block px-3 py-2 text-base font-medium text-rose-400 hover:bg-slate-800 rounded-md">Trust & Safety (18+)</a>
        <div class="pt-2 border-t border-slate-800 flex flex-col space-y-2">
            <a href="#sign-in" class="w-full text-center py-2 text-sm font-medium text-slate-300 hover:text-white">Sign In</a>
            <a href="#post-ad" class="w-full text-center py-2 text-sm font-semibold rounded-lg bg-rose-600 text-white">Post Personal Ad</a>
        </div>
    </div>
</header>
