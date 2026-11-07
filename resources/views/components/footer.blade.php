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
                    A privacy-first local dating and adult personal classifieds platform built on modern enterprise architecture. Connecting real adults safely.
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
                    <li><a href="#categories" class="hover:text-white transition">Local Dating Categories</a></li>
                    <li><a href="#browse-cities" class="hover:text-white transition">Major Cities Directory</a></li>
                    <li><a href="#date-spots" class="hover:text-white transition">Curated Date Spots</a></li>
                    <li><a href="#activity-partners" class="hover:text-white transition">Activity Companions</a></li>
                </ul>
            </div>

            <!-- Safety & Legal Column -->
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-200 mb-3">Trust & Safety</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="#safety-standards" class="hover:text-white transition">Community Standards</a></li>
                    <li><a href="#anti-trafficking" class="hover:text-white transition">Anti-Trafficking Policy</a></li>
                    <li><a href="#location-privacy" class="hover:text-white transition">Location Fuzzing & Privacy</a></li>
                    <li><a href="#report-abuse" class="hover:text-white transition">Report Violations</a></li>
                </ul>
            </div>

            <!-- Platform Architecture Column -->
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-200 mb-3">Platform Status</h4>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-slate-300">All Systems Operational</span>
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Laravel 13 • Livewire 4 • MySQL GIS • Tailwind CSS
                    </p>
                    <a href="{{ url('/health') }}" class="inline-block mt-2 text-rose-400 hover:text-rose-300 font-medium text-xs">
                        View System Health Probe →
                    </a>
                </div>
            </div>
        </div>

        <div class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-500">
            <p>&copy; {{ date('Y') }} {{ config('localdatehub.name', 'LocalDateHub') }}. All rights reserved. Strictly 18+.</p>
            <div class="flex space-x-6 mt-4 sm:mt-0">
                <a href="#terms" class="hover:text-slate-400">Terms of Service</a>
                <a href="#privacy" class="hover:text-slate-400">Privacy Policy</a>
                <a href="#cookies" class="hover:text-slate-400">Cookie Preferences</a>
                <a href="{{ url('/health') }}" class="hover:text-slate-400">Health Endpoint</a>
            </div>
        </div>
    </div>
</footer>
