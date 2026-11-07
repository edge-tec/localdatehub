<x-layouts.auth>
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-white tracking-tight">Sign In to LocalDateHub</h1>
            <p class="text-sm text-slate-400 mt-1">Access your adult personal listings and messages</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <!-- Login Identifier (Email or Username) -->
            <div>
                <label for="login" class="block text-xs font-medium text-slate-300 mb-1">Email or Username</label>
                <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                    class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                    placeholder="you@example.com or @handle">
                @error('login')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="password" class="block text-xs font-medium text-slate-300">Password</label>
                    <a href="{{ route('password.request') }}" class="text-xs text-rose-400 hover:text-rose-300 hover:underline">
                        Forgot password?
                    </a>
                </div>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                    placeholder="••••••••">
                @error('password')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" value="1"
                        class="rounded bg-slate-950 border-slate-700 text-rose-500 focus:ring-rose-500 focus:ring-offset-slate-900">
                    <span class="text-xs text-slate-300">Remember this device</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                class="w-full mt-2 bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white font-semibold py-3 px-4 rounded-xl text-sm transition shadow-lg shadow-rose-600/30">
                Sign In
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-800 text-center text-xs text-slate-400">
            Don't have an account yet?
            <a href="{{ route('register') }}" class="font-medium text-rose-400 hover:text-rose-300 hover:underline ml-1">Create an account (18+)</a>
        </div>
    </div>
</x-layouts.auth>