<x-layouts.app title="Access Forbidden (403) — LocalDateHub">
    <div class="py-24 bg-slate-50 min-h-[70vh] flex items-center justify-center">
        <div class="max-w-md mx-auto px-4 text-center">
            <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <span class="text-xs font-mono font-bold tracking-widest text-amber-500 uppercase">Error 403</span>
            <h1 class="text-3xl font-extrabold text-slate-900 mt-2">Access Restricted</h1>
            <p class="text-sm text-slate-400 mt-3 leading-relaxed">
                You do not have administrative or authorization clearance to view or edit this restricted resource.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold shadow-md shadow-rose-600/20 transition">
                    Return to Homepage
                </a>
                <a href="{{ route('account.profile') }}" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold border border-slate-200 transition">
                    My Account
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>

