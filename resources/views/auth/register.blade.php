<x-layouts.auth>
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-white tracking-tight">Create Your Account</h1>
            <p class="text-sm text-slate-400 mt-1">Join LocalDateHub — Adult Personal Classifieds</p>
            <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Strictly 18+ Adult Community Only
            </div>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <!-- Honeypot anti-bot field -->
            <input type="text" name="website_hp" value="" class="hidden" tabindex="-1" autocomplete="off">

            <!-- Full Name -->
            <div>
                <label for="name" class="block text-xs font-medium text-slate-300 mb-1">Full / Legal Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                    class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                    placeholder="e.g. Alex Morgan">
                @error('name')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Username -->
            <div>
                <label for="username" class="block text-xs font-medium text-slate-300 mb-1">Public Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-sm">@</span>
                    <input id="username" type="text" name="username" value="{{ old('username') }}" required
                        class="w-full bg-slate-950 border border-slate-700 rounded-lg pl-8 pr-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                        placeholder="alex_m">
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Unique handle visible on your listings.</p>
                @error('username')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-xs font-medium text-slate-300 mb-1">Email Address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                    class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                    placeholder="you@example.com">
                <p class="text-[11px] text-slate-500 mt-1">Used for private notifications and account security.</p>
                @error('email')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone (Optional) -->
            <div>
                <label for="phone" class="block text-xs font-medium text-slate-300 mb-1">Phone Number <span class="text-slate-500">(Optional)</span></label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                    class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                    placeholder="+1 (555) 000-0000">
                @error('phone')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Birthdate (18+ Verification) -->
            <div>
                <label for="birthdate" class="block text-xs font-medium text-slate-300 mb-1">Date of Birth</label>
                <input id="birthdate" type="date" name="birthdate" value="{{ old('birthdate') }}" required
                    class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500">
                <p class="text-[11px] text-amber-400/90 mt-1">Mandatory age assurance: You must be at least 18 years old.</p>
                @error('birthdate')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Location: State and City -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="state_code" class="block text-xs font-medium text-slate-300 mb-1">State</label>
                    <select id="state_code" name="state_code" required
                        class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500">
                        <option value="">Select State</option>
                        @foreach($states ?? [] as $state)
                            <option value="{{ $state->code }}" @selected(old('state_code') === $state->code)>
                                {{ $state->name }} ({{ $state->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('state_code')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="city" class="block text-xs font-medium text-slate-300 mb-1">City</label>
                    <input id="city" type="text" name="city" value="{{ old('city') }}" required
                        class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                        placeholder="e.g. Austin">
                    @error('city')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Gender & Seeking (Optional) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="gender" class="block text-xs font-medium text-slate-300 mb-1">I am <span class="text-slate-500">(Optional)</span></label>
                    <select id="gender" name="gender"
                        class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500">
                        <option value="">Select</option>
                        <option value="Man" @selected(old('gender') === 'Man')>Man</option>
                        <option value="Woman" @selected(old('gender') === 'Woman')>Woman</option>
                        <option value="Couple" @selected(old('gender') === 'Couple')>Couple</option>
                        <option value="Non-Binary" @selected(old('gender') === 'Non-Binary')>Non-Binary / Other</option>
                    </select>
                </div>

                <div>
                    <label for="seeking" class="block text-xs font-medium text-slate-300 mb-1">Seeking <span class="text-slate-500">(Optional)</span></label>
                    <select id="seeking" name="seeking"
                        class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500">
                        <option value="">Select</option>
                        <option value="Women" @selected(old('seeking') === 'Women')>Women</option>
                        <option value="Men" @selected(old('seeking') === 'Men')>Men</option>
                        <option value="Couples" @selected(old('seeking') === 'Couples')>Couples</option>
                        <option value="All" @selected(old('seeking') === 'All')>All / Anyone</option>
                    </select>
                </div>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-medium text-slate-300 mb-1">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                    class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                    placeholder="Minimum 8 characters">
                @error('password')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-xs font-medium text-slate-300 mb-1">Confirm Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                    class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                    placeholder="Repeat password">
            </div>

            <!-- Compliance Checkboxes -->
            <div class="pt-3 border-t border-slate-800 space-y-3">
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="age_confirmed_18" value="1" @checked(old('age_confirmed_18'))
                        class="mt-0.5 rounded bg-slate-950 border-slate-700 text-rose-500 focus:ring-rose-500 focus:ring-offset-slate-900">
                    <span class="text-xs text-slate-300 leading-relaxed">
                        <strong class="text-white">Age Assurance:</strong> I certify and swear under penalty of perjury that I am at least 18 years of age.
                    </span>
                </label>
                @error('age_confirmed_18')
                    <p class="text-xs text-rose-400">{{ $message }}</p>
                @enderror

                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="anti_trafficking_confirmed" value="1" @checked(old('anti_trafficking_confirmed'))
                        class="mt-0.5 rounded bg-slate-950 border-slate-700 text-rose-500 focus:ring-rose-500 focus:ring-offset-slate-900">
                    <span class="text-xs text-slate-300 leading-relaxed">
                        <strong class="text-white">Anti-Trafficking Policy:</strong> I understand LocalDateHub strictly prohibits prostitution, commercial sexual services, minor exploitation, and non-consensual content. Violations result in immediate ban and law enforcement referral.
                    </span>
                </label>
                @error('anti_trafficking_confirmed')
                    <p class="text-xs text-rose-400">{{ $message }}</p>
                @enderror

                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted'))
                        class="mt-0.5 rounded bg-slate-950 border-slate-700 text-rose-500 focus:ring-rose-500 focus:ring-offset-slate-900">
                    <span class="text-xs text-slate-300 leading-relaxed">
                        I have read and agree to the <a href="#" class="text-rose-400 hover:underline">Terms of Service</a> and <a href="#" class="text-rose-400 hover:underline">Privacy Policy</a>.
                    </span>
                </label>
                @error('terms_accepted')
                    <p class="text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <button type="submit"
                class="w-full mt-4 bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white font-semibold py-3 px-4 rounded-xl text-sm transition shadow-lg shadow-rose-600/30">
                Create Adult Account
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-400">
            Already have an account?
            <a href="{{ route('login') }}" class="font-medium text-rose-400 hover:text-rose-300 hover:underline ml-1">Sign in here</a>
        </div>
    </div>
</x-layouts.auth>
