<x-layouts.app title="Page Not Found (404) — LocalDateHub">
    <div class="py-24 bg-slate-50 min-h-[70vh] flex items-center justify-center">
        <div class="max-w-md mx-auto px-4 text-center">
            <div class="w-16 h-16 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-500 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span class="text-xs font-mono font-bold tracking-widest text-rose-500 uppercase">Error 404</span>
            <h1 class="text-3xl font-extrabold text-slate-900 mt-2">Listing or Page Not Found</h1>
            <p class="text-sm text-slate-400 mt-3 leading-relaxed">
                The classified ad or page you requested may have expired, been removed by its author, or never existed.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold shadow-md shadow-rose-600/20 transition">
                    Return to Homepage
                </a>
                <a href="{{ route('search.index') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold border border-slate-200 transition">
                    Search Classifieds
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>

