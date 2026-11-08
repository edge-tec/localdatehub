<x-layouts.auth title="Age Verification — LocalDateHub">
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-xl text-center text-slate-800">
        <div class="w-16 h-16 rounded-full bg-pink-100 text-[#F20B83] mx-auto mb-4 flex items-center justify-center border border-pink-200">
            <span class="text-2xl font-black">18+</span>
        </div>

        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Age Verification Required</h1>
        <p class="text-xs font-bold text-[#F20B83] mt-1 uppercase tracking-wider">Adults Only Notice</p>

        <div class="my-6 p-5 rounded-2xl bg-slate-50 border border-slate-200 text-left text-xs text-slate-700 space-y-2.5 leading-relaxed">
            <p>
                <strong class="text-slate-900 font-bold">LocalDateHub</strong> is a personal classifieds and dating connection platform intended exclusively for consenting adults aged 18 and older.
            </p>
            <p class="font-medium text-slate-800">
                By proceeding, you explicitly affirm that:
            </p>
            <ul class="list-disc list-inside space-y-1 text-slate-600">
                <li>You are at least 18 years of age (or the legal age of majority in your jurisdiction).</li>
                <li>You will not permit minors to access this platform or its contents.</li>
                <li>You acknowledge zero tolerance for commercial sexual exploitation, human trafficking, or non-consensual imagery.</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('age.confirm') }}" class="space-y-3">
            @csrf
            <button type="submit"
                class="w-full bg-[#F20B83] hover:bg-[#d90858] active:bg-[#c0074d] text-white font-bold py-3.5 px-4 rounded-xl text-sm transition shadow-lg shadow-pink-500/20">
                I Am 18 or Older — Enter
            </button>
            <a href="https://www.google.com" rel="nofollow"
                class="block w-full py-2.5 px-4 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 transition">
                I Am Under 18 — Exit
            </a>
        </form>
    </div>
</x-layouts.auth>
