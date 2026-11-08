<footer class="bg-white border-t border-slate-200 text-slate-600 text-sm mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <!-- Brand Column -->
            <div class="space-y-3">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-[#F20B65]">
                        <svg class="w-7 h-7 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xl font-black text-slate-900 tracking-tight">LocalDate<span class="text-[#F20B65]">Hub</span></span>
                        <div class="text-[10px] text-slate-500 tracking-wider">Connect • Meet • Enjoy</div>
                    </div>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    A premium, privacy-first local adult classifieds and dating connections platform across all 50 US states. Connecting real adults safely and discreetly.
                </p>
                <div class="inline-flex items-center space-x-2 text-xs text-[#F20B65] font-semibold bg-[#F20B65]/10 px-2.5 py-1 rounded-full">
                    <span class="w-2 h-2 rounded-full bg-[#F20B65] animate-pulse"></span>
                    <span>18+ Strictly Enforced • FOSTA-SESTA Compliant</span>
                </div>
            </div>

            <!-- Discovery Column -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-3">Discovery</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="{{ route('categories.index') }}" class="hover:text-[#F20B65] transition">Browse All Categories</a></li>
                    <li><a href="{{ route('locations.index') }}" class="hover:text-[#F20B65] transition">All 50 US States Directory</a></li>
                    <li><a href="{{ route('search.index') }}" class="hover:text-[#F20B65] transition">Advanced Search Engine</a></li>
                    <li><a href="{{ route('listings.create') }}" class="hover:text-[#F20B65] transition">Post a Classified Ad</a></li>
                </ul>
            </div>

            <!-- Safety & Legal Column -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-3">Trust & Legal</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="{{ route('pages.safety') }}" class="hover:text-[#F20B65] transition">Community Safety & Age Assurance</a></li>
                    <li><a href="{{ route('dmca.show') }}" class="hover:text-[#F20B65] transition">DMCA & Legal Takedown Notices</a></li>
                    <li><a href="{{ route('pages.terms') }}" class="hover:text-[#F20B65] transition">Terms of Service</a></li>
                    <li><a href="{{ route('pages.privacy') }}" class="hover:text-[#F20B65] transition">Privacy & Geolocation Shield</a></li>
                    <li><a href="{{ route('pages.contact') }}" class="hover:text-[#F20B65] transition">Contact Support & Report Abuse</a></li>
                </ul>
            </div>

            <!-- Company & Status Column -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-3">About & Systems</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="{{ route('pages.about') }}" class="hover:text-[#F20B65] transition">About LocalDateHub</a></li>
                    <li><a href="{{ route('pages.pricing') }}" class="hover:text-[#F20B65] transition">Pricing & Upgrades</a></li>
                    <li class="pt-2">
                        <div class="flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="text-slate-700 font-semibold text-xs">All Systems Operational</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-slate-200 pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved. Strictly 18+ adult classifieds and local dating community.</p>
            <p class="text-[11px] text-slate-500">Commercial sexual solicitation, prostitution, and unlawful trafficking are strictly prohibited and actively reported.</p>
        </div>
    </div>
</footer>
