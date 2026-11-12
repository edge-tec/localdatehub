<x-layouts.auth title="Reset Password — LocalDateHub">
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-xl text-slate-800">
        <div class="mb-6 text-center">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Reset Password</h1>
            <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                Forgot your password? No problem. Enter your account email address below, and we'll send you a secure password reset link.
            </p>
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 mb-1">Account Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]"
                    placeholder="you@example.com">
                @error('email')
                    <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                @enderror
            </div>

            <x-turnstile action="forgot_password" class="pt-1" />

            <button type="submit"
                class="w-full mt-2 bg-[#F20B83] hover:bg-[#d90858] active:bg-[#c0074d] text-white font-bold py-3.5 px-4 rounded-xl text-sm transition shadow-lg shadow-pink-500/20">
                Email Password Reset Link
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-200 text-center text-xs text-slate-600">
            Remember your password?
            <a href="{{ route('login') }}" class="font-bold text-[#F20B83] hover:underline ml-1">Back to login</a>
        </div>
    </div>
</x-layouts.auth>
