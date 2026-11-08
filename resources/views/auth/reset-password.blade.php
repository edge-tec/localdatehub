<x-layouts.auth title="Set New Password — LocalDateHub">
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-xl text-slate-800">
        <div class="mb-6 text-center">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Set New Password</h1>
            <p class="text-sm text-slate-600 mt-1">Please enter your account email and your new secure password.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf

            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $token }}">

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]">
                @error('email')
                    <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1">New Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]"
                    placeholder="Minimum 8 characters">
                @error('password')
                    <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1">Confirm New Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]"
                    placeholder="Repeat new password">
            </div>

            <button type="submit"
                class="w-full mt-2 bg-[#F20B83] hover:bg-[#d90858] active:bg-[#c0074d] text-white font-bold py-3.5 px-4 rounded-xl text-sm transition shadow-lg shadow-pink-500/20">
                Reset Password
            </button>
        </form>
    </div>
</x-layouts.auth>
