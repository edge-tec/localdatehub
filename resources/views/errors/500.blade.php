<x-layouts.app title="System Error (500) — LocalDateHub">
    <div class="py-24 bg-slate-950 min-h-[70vh] flex items-center justify-center">
        <div class="max-w-md mx-auto px-4 text-center">
            <div class="w-16 h-16 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-500 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span class="text-xs font-mono font-bold tracking-widest text-rose-500 uppercase">Error 500</span>
            <h1 class="text-3xl font-extrabold text-white mt-2">Server Exception</h1>
            <p class="text-sm text-slate-400 mt-3 leading-relaxed">
                An internal server condition prevented fulfillment of your request. Our engineering telemetry has been notified.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold shadow-md shadow-rose-600/20 transition">
                    Return to Homepage
                </a>
                <a href="{{ route('pages.contact') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition">
                    Report Issue
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
