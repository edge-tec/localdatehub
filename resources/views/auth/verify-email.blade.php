<x-layouts.auth>
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl">
        <div class="mb-6 text-center">
            <div class="w-12 h-12 rounded-full bg-rose-500/10 text-rose-400 mx-auto mb-3 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Verify Your Email</h1>
            <p class="text-sm text-slate-400 mt-2 leading-relaxed">
                Thanks for joining LocalDateHub! Before posting or responding to personal ads, please verify your email address by clicking on the link we just sent to your inbox.
            </p>
        </div>

        <div class="space-y-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit"
                    class="w-full bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white font-semibold py-3 px-4 rounded-xl text-sm transition shadow-lg shadow-rose-600/30">
                    Resend Verification Email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="text-center">
                @csrf
                <button type="submit" class="text-xs text-slate-400 hover:text-white transition">
                    Log out of this session
                </button>
            </form>
        </div>
    </div>
</x-layouts.auth>