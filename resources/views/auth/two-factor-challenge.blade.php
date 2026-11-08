<x-layouts.auth title="Two-Factor Authentication — LocalDateHub">
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-xl text-slate-800">
        <div class="mb-6 text-center">
            <div class="w-14 h-14 rounded-full bg-pink-100 text-[#F20B83] mx-auto mb-3 flex items-center justify-center border border-pink-200">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Two-Factor Authentication</h1>
            <p class="text-sm text-slate-600 mt-1">Open your authenticator app and enter the 6-digit verification code to confirm your identity.</p>
        </div>

        <form method="POST" action="{{ route('login.2fa.verify') }}" class="space-y-4">
            @csrf

            <div>
                <label for="two_factor_code" class="block text-xs font-bold text-slate-700 mb-1 text-center">Authentication Code</label>
                <input id="two_factor_code" type="text" name="two_factor_code" required autofocus maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-3 text-center text-2xl font-mono tracking-widest text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]"
                    placeholder="123456">
                @error('two_factor_code')
                    <p class="text-xs text-rose-500 font-medium mt-1 text-center">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                class="w-full mt-2 bg-[#F20B83] hover:bg-[#d90858] active:bg-[#c0074d] text-white font-bold py-3.5 px-4 rounded-xl text-sm transition shadow-lg shadow-pink-500/20">
                Verify Code & Continue
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-200 text-center text-xs text-slate-600">
            <a href="{{ route('login') }}" class="font-bold text-[#F20B83] hover:underline transition">
                ← Return to login
            </a>
        </div>
    </div>
</x-layouts.auth>
