<x-layouts.auth>
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-white tracking-tight">Reset Password</h1>
            <p class="text-sm text-slate-400 mt-2 leading-relaxed">
                Forgot your password? No problem. Enter your account email address below, and we'll send you a secure password reset link.
            </p>
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-medium text-slate-300 mb-1">Account Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                    placeholder="you@example.com">
                @error('email')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                class="w-full mt-2 bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white font-semibold py-3 px-4 rounded-xl text-sm transition shadow-lg shadow-rose-600/30">
                Email Password Reset Link
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-800 text-center text-xs text-slate-400">
            Remember your password?
            <a href="{{ route('login') }}" class="font-medium text-rose-400 hover:text-rose-300 hover:underline ml-1">Back to login</a>
        </div>
    </div>
</x-layouts.auth>