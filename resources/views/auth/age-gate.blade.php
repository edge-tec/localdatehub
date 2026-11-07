<x-layouts.auth>
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl text-center">
        <div class="w-16 h-16 rounded-full bg-rose-500/10 text-rose-500 mx-auto mb-4 flex items-center justify-center border border-rose-500/20">
            <span class="text-2xl font-black">18+</span>
        </div>

        <h1 class="text-2xl font-bold text-white tracking-tight">Age Verification Required</h1>
        <p class="text-sm font-semibold text-rose-400 mt-1 uppercase tracking-wider">Adults Only Notice</p>

        <div class="my-6 p-4 rounded-xl bg-slate-950/80 border border-slate-800/80 text-left text-xs text-slate-300 space-y-2.5 leading-relaxed">
            <p>
                <strong>LocalDateHub</strong> is a personal classifieds and dating connection platform intended exclusively for consenting adults aged 18 and older.
            </p>
            <p>
                By proceeding, you explicitly affirm that:
            </p>
            <ul class="list-disc list-inside space-y-1 text-slate-400">
                <li>You are at least 18 years of age (or the legal age of majority in your jurisdiction).</li>
                <li>You will not permit minors to access this platform or its contents.</li>
                <li>You acknowledge zero tolerance for commercial sexual exploitation, human trafficking, or non-consensual imagery.</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('age.confirm') }}" class="space-y-3">
            @csrf
            <button type="submit"
                class="w-full bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white font-bold py-3 px-4 rounded-xl text-sm transition shadow-lg shadow-rose-600/30">
                I Am 18 or Older — Enter
            </button>
            <a href="https://www.google.com" rel="nofollow"
                class="block w-full py-2.5 px-4 rounded-xl text-xs font-medium text-slate-400 hover:text-white bg-slate-800/50 hover:bg-slate-800 transition">
                I Am Under 18 — Exit
            </a>
        </form>
    </div>
</x-layouts.auth>