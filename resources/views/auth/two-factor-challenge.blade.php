<x-layouts.auth>
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl">
        <div class="mb-6 text-center">
            <div class="w-12 h-12 rounded-full bg-rose-500/10 text-rose-400 mx-auto mb-3 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Two-Factor Authentication</h1>
            <p class="text-sm text-slate-400 mt-1">Open your authenticator app and enter the 6-digit verification code to confirm your identity.</p>
        </div>

        <form method="POST" action="{{ route('login.2fa.verify') }}" class="space-y-4">
            @csrf

            <div>
                <label for="two_factor_code" class="block text-xs font-medium text-slate-300 mb-1 text-center">Authentication Code</label>
                <input id="two_factor_code" type="text" name="two_factor_code" required autofocus maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                    class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-3 text-center text-xl font-mono tracking-widest text-white placeholder-slate-600 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                    placeholder="123456">
                @error('two_factor_code')
                    <p class="text-xs text-rose-400 mt-1 text-center">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                class="w-full mt-2 bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white font-semibold py-3 px-4 rounded-xl text-sm transition shadow-lg shadow-rose-600/30">
                Verify Code & Continue
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-800 text-center text-xs text-slate-400">
            <a href="{{ route('login') }}" class="font-medium text-slate-400 hover:text-white transition">
                ← Return to login
            </a>
        </div>
    </div>
</x-layouts.auth>
