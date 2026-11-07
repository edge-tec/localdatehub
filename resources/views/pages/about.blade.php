<x-layouts.app title="About LocalDateHub — Privacy-First Adult Classifieds">
    <div class="py-12 bg-slate-950">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'About Us']
            ]" />

            <div class="mt-8">
                <x-badge variant="rose">Our Mission & Standards</x-badge>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white mt-3 tracking-tight">
                    Connecting Real Adults Safely & Privately
                </h1>
                <p class="mt-4 text-lg text-slate-300 leading-relaxed">
                    LocalDateHub is an independent, USA-focused personal classifieds platform designed for consenting adults seeking authentic connections, dating, and community.
                </p>
            </div>

            <!-- Content Cards -->
            <div class="mt-10 space-y-8">
                <x-card>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">Classic Classifieds Reimagined</h2>
                            <p class="mt-2 text-sm text-slate-300 leading-relaxed">
                                Unlike swipe-based algorithms designed to keep you trapped in endless loops, LocalDateHub revives the classic personal classified format. You write what you are looking for in your own authentic words, browse local ads at your own pace, and reach out directly through safe, protected communication channels.
                            </p>
                        </div>
                    </div>
                </x-card>

                <x-card>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">Privacy & Safety By Design</h2>
                            <p class="mt-2 text-sm text-slate-300 leading-relaxed">
                                We believe personal classifieds should not compromise personal security. Every listing goes through automated anti-triangulation location fuzzing (jittering coordinates by 300 to 800 meters) and automatic EXIF image stripping. Exact locations and IP addresses are never exposed to other members.
                            </p>
                        </div>
                    </div>
                </x-card>

                <x-card>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">Zero Tolerance for Exploitation</h2>
                            <p class="mt-2 text-sm text-slate-300 leading-relaxed">
                                In strict compliance with US federal legislation including FOSTA-SESTA (18 U.S.C. §§ 1591, 2421A), LocalDateHub is exclusively for lawful social and romantic personal advertisements. Commercial sex work, prostitution solicitation, escorts, non-consensual imagery, and minor participation are strictly prohibited and immediately reported to NCMEC and law enforcement.
                            </p>
                        </div>
                    </div>
                </x-card>

                <x-card>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">High-Performance USA Infrastructure</h2>
                            <p class="mt-2 text-sm text-slate-300 leading-relaxed">
                                Our platform is built on modern web standards: Laravel 13, Livewire 4, MySQL 8 spatial indexing, and Tailwind CSS. We cover all 50 US states, hundreds of cities, and thousands of zip codes with low-latency search and real-time moderation pipelines.
                            </p>
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- Call to Action -->
            <div class="mt-12 p-8 rounded-2xl bg-gradient-to-r from-rose-950/60 to-slate-900 border border-rose-900/40 text-center">
                <h3 class="text-2xl font-bold text-white">Ready to discover your local connections?</h3>
                <p class="mt-2 text-sm text-slate-300">Browse categories or post your own personal ad today.</p>
                <div class="mt-6 flex flex-wrap justify-center gap-4">
                    <a href="{{ route('categories.index') }}" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-sm transition shadow-lg shadow-rose-600/20">
                        Browse Personals
                    </a>
                    <a href="{{ route('listings.create') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-sm transition border border-slate-700">
                        Post an Ad (18+)
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

