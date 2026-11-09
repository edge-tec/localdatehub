<x-layouts.auth title="Create Account — LocalDateHub">
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-xl text-slate-800">
        <div class="mb-6 text-center">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Create Your Account</h1>
            <p class="text-sm text-slate-500 mt-1">Join LocalDateHub — Adult Personal Classifieds</p>
            <div class="mt-3 inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-gradient-to-r from-rose-50 via-pink-50 to-purple-50/60 text-[#F20B83] border border-rose-200 text-xs font-black shadow-xs">
                <span class="w-4 h-4 rounded-full bg-[#F20B83] text-white flex items-center justify-center text-[9px] font-black">18+</span>
                <span>Strictly 18+ Adult Community Only</span>
            </div>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <!-- Honeypot anti-bot field -->
            <input type="text" name="website_hp" value="" class="hidden" tabindex="-1" autocomplete="off">

            <!-- Full Name -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 mb-1">Full / Legal Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]"
                    placeholder="e.g. Alex Morgan">
                @error('name')
                    <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Username -->
            <div>
                <label for="username" class="block text-xs font-bold text-slate-700 mb-1">Public Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-sm font-semibold">@</span>
                    <input id="username" type="text" name="username" value="{{ old('username') }}" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]"
                        placeholder="alex_m">
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Unique handle visible on your listings.</p>
                @error('username')
                    <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]"
                    placeholder="you@example.com">
                <p class="text-[11px] text-slate-500 mt-1">Used for private notifications and account security.</p>
                @error('email')
                    <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone (Optional) -->
            <div>
                <label for="phone" class="block text-xs font-bold text-slate-700 mb-1">Phone Number <span class="text-slate-400 font-normal">(Optional)</span></label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]"
                    placeholder="+1 (555) 000-0000">
                @error('phone')
                    <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Birthdate (18+ Verification) -->
            <div>
                <label for="birthdate" class="block text-xs font-bold text-slate-700 mb-1">Date of Birth</label>
                <input id="birthdate" type="date" name="birthdate" value="{{ old('birthdate') }}" required
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]">
                <p class="text-[11px] text-amber-600 font-medium mt-1">Mandatory age assurance: You must be at least 18 years old.</p>
                @error('birthdate')
                    <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Location: State and City with Autocomplete -->
            <div 
                class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                x-data="{
                    selectedState: '{{ old('state_code', '') }}',
                    cities: [],
                    async onStateChange(event) {
                        const st = event.target.value;
                        this.selectedState = st;
                        this.cities = [];
                        if (!st) return;
                        try {
                            const res = await fetch(`/api/locations/states/${st.toLowerCase()}/cities`);
                            const json = await res.json();
                            if (json.success && json.data && json.data.cities) {
                                this.cities = json.data.cities;
                            } else {
                                const fb = await fetch('/data/us-cities.json');
                                const fbMap = await fb.json();
                                if (fbMap && fbMap[st.toLowerCase()]) {
                                    this.cities = fbMap[st.toLowerCase()];
                                }
                            }
                        } catch(e) {
                            try {
                                const fb = await fetch('/data/us-cities.json');
                                const fbMap = await fb.json();
                                if (fbMap && fbMap[st.toLowerCase()]) {
                                    this.cities = fbMap[st.toLowerCase()];
                                }
                            } catch(err) {}
                        }
                    },
                    init() {
                        if (this.selectedState) {
                            this.onStateChange({ target: { value: this.selectedState } });
                        }
                    }
                }"
            >
                <div>
                    <label for="state_code" class="block text-xs font-bold text-slate-700 mb-1">State</label>
                    <select id="state_code" name="state_code" required
                        x-model="selectedState"
                        @change="onStateChange($event)"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] cursor-pointer">
                        <option value="">Select State</option>
                        @foreach($states ?? [] as $state)
                            <option value="{{ $state->code }}" @selected(old('state_code') === $state->code)>
                                {{ $state->name }} ({{ $state->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('state_code')
                        <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="city" class="block text-xs font-bold text-slate-700 mb-1">City</label>
                    <input id="city" type="text" name="city" value="{{ old('city') }}" required
                        list="register-cities-list"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]"
                        :placeholder="!selectedState ? 'e.g. Austin' : 'Select or type city in ' + selectedState"
                        autocomplete="off">
                    <datalist id="register-cities-list">
                        <template x-for="c in cities" :key="c.id">
                            <option :value="c.name"></option>
                        </template>
                    </datalist>
                    @error('city')
                        <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Gender & Seeking (Optional) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="gender" class="block text-xs font-bold text-slate-700 mb-1">I am <span class="text-slate-400 font-normal">(Optional)</span></label>
                    <select id="gender" name="gender"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]">
                        <option value="">Select</option>
                        <option value="Man" @selected(old('gender') === 'Man')>Man</option>
                        <option value="Woman" @selected(old('gender') === 'Woman')>Woman</option>
                        <option value="Couple" @selected(old('gender') === 'Couple')>Couple</option>
                        <option value="Non-Binary" @selected(old('gender') === 'Non-Binary')>Non-Binary / Other</option>
                    </select>
                </div>

                <div>
                    <label for="seeking" class="block text-xs font-bold text-slate-700 mb-1">Seeking <span class="text-slate-400 font-normal">(Optional)</span></label>
                    <select id="seeking" name="seeking"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]">
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
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]"
                    placeholder="Minimum 8 characters">
                @error('password')
                    <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1">Confirm Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83]"
                    placeholder="Repeat password">
            </div>

            <!-- Compliance Checkboxes -->
            <div class="pt-3 border-t border-slate-200 space-y-3">
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="age_confirmed_18" value="1" @checked(old('age_confirmed_18'))
                        class="mt-0.5 rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]">
                    <span class="text-xs text-slate-600 leading-relaxed">
                        <strong class="text-slate-900 font-semibold">Age Assurance:</strong> I certify and swear under penalty of perjury that I am at least 18 years of age.
                    </span>
                </label>
                @error('age_confirmed_18')
                    <p class="text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror

                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="anti_trafficking_confirmed" value="1" @checked(old('anti_trafficking_confirmed'))
                        class="mt-0.5 rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]">
                    <span class="text-xs text-slate-600 leading-relaxed">
                        <strong class="text-slate-900 font-semibold">Anti-Trafficking Policy:</strong> I understand LocalDateHub strictly prohibits prostitution, commercial sexual services, minor exploitation, and non-consensual content. Violations result in immediate ban and law enforcement referral.
                    </span>
                </label>
                @error('anti_trafficking_confirmed')
                    <p class="text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror

                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted'))
                        class="mt-0.5 rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]">
                    <span class="text-xs text-slate-600 leading-relaxed">
                        I have read and agree to the <a href="{{ route('pages.terms') }}" class="text-[#F20B83] font-medium hover:underline">Terms of Service</a> and <a href="{{ route('pages.privacy') }}" class="text-[#F20B83] font-medium hover:underline">Privacy Policy</a>.
                    </span>
                </label>
                @error('terms_accepted')
                    <p class="text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <button type="submit"
                class="w-full mt-4 bg-[#F20B83] hover:bg-[#d90858] active:bg-[#c0074d] text-white font-bold py-3.5 px-4 rounded-xl text-sm transition shadow-lg shadow-pink-500/20">
                Create Adult Account
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-slate-600">
            Already have an account?
            <a href="{{ route('login') }}" class="font-bold text-[#F20B83] hover:underline ml-1">Sign in here</a>
        </div>
    </div>
</x-layouts.auth>
