<footer class="bg-slate-900 border-t border-slate-800 text-slate-400 text-sm mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <!-- Brand Column -->
            <div class="space-y-3">
                <div class="flex items-center space-x-2">
                    <div class="w-7 h-7 rounded-lg bg-rose-600 flex items-center justify-center text-white font-bold">L</div>
                    <span class="text-lg font-bold text-white tracking-tight">LocalDate<span class="text-rose-500">Hub</span></span>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    A privacy-first local dating and adult personal classifieds platform built on modern enterprise architecture. Connecting real adults safely across the United States.
                </p>
                <div class="inline-flex items-center space-x-1.5 text-xs text-rose-400 font-medium">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span>18+ Strictly Enforced</span>
                </div>
            </div>

            <!-- Discovery Column -->
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-200 mb-3">Discovery</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="{{ route('categories.index') }}" class="hover:text-white transition">Personals Categories</a></li>
                    <li><a href="{{ route('locations.index') }}" class="hover:text-white transition">US Locations Directory</a></li>
                    <li><a href="{{ route('search.index') }}" class="hover:text-white transition">Search Classifieds</a></li>
                    <li><a href="{{ route('listings.create') }}" class="hover:text-white transition">Post a Personal Ad</a></li>
                </ul>
            </div>

            <!-- Safety & Legal Column -->
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-200 mb-3">Trust & Legal</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="{{ route('pages.safety') }}" class="hover:text-white transition">Community Safety (18+)</a></li>
                    <li><a href="{{ route('dmca.show') }}" class="hover:text-white transition">DMCA & Takedown Notices</a></li>
                    <li><a href="{{ route('pages.terms') }}" class="hover:text-white transition">Terms of Service</a></li>
                    <li><a href="{{ route('pages.privacy') }}" class="hover:text-white transition">Privacy & Geolocation Policy</a></li>
                    <li><a href="{{ route('pages.contact') }}" class="hover:text-white transition">Contact & Report Abuse</a></li>
                </ul>
            </div>

            <!-- Platform Architecture Column -->
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-200 mb-3">Company & System</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="{{ route('pages.about') }}" class="hover:text-white transition">About LocalDateHub</a></li>
                    <li><a href="{{ route('pages.pricing') }}" class="hover:text-white transition">Pricing & Upgrades</a></li>
                    <li class="pt-2">
                        <div class="flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-slate-300">All Systems Operational</span>
                        </div>
                    </li>
                    <li>
                        <a href="{{ url('/health') }}" class="inline-block text-rose-400 hover:text-rose-300 font-medium text-xs">
                            System Health Probe →
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-500">
            <p>&copy; {{ date('Y') }} {{ config('localdatehub.name', 'LocalDateHub') }}. All rights reserved. Strictly 18+.</p>
            <div class="flex space-x-6 mt-4 sm:mt-0">
                <a href="{{ route('pages.terms') }}" class="hover:text-slate-400">Terms</a>
                <a href="{{ route('pages.privacy') }}" class="hover:text-slate-400">Privacy</a>
                <a href="{{ route('pages.safety') }}" class="hover:text-slate-400">Safety</a>
                <a href="{{ route('pages.contact') }}" class="hover:text-slate-400">Support</a>
                <a href="{{ url('/health') }}" class="hover:text-slate-400">Health API</a>
            </div>
        </div>
    </div>
</footer>
