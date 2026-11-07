<x-layouts.app title="Edit Profile — Account Settings">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Account Header & Tabs -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Account Settings</h1>
                    <p class="text-sm text-slate-400 mt-1">Manage your public persona, contact preferences, and privacy controls.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        {{ ucfirst($user->account_status) }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700">
                        Role: {{ ucfirst($user->role) }}
                    </span>
                </div>
            </div>

            <nav class="flex border-b border-slate-800 space-x-6 text-sm font-medium">
                <a href="{{ route('account.profile') }}" class="py-3 border-b-2 border-rose-500 text-rose-400 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Profile & Privacy
                </a>
                <a href="{{ route('account.security') }}" class="py-3 border-b-2 border-transparent text-slate-400 hover:text-white flex items-center gap-2 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Security & 2FA
                </a>
            </nav>
        </div>

        <div class="space-y-8">
            <!-- Avatar / Photo Section -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8">
                <h2 class="text-lg font-bold text-white mb-2">Profile Photo</h2>
                <p class="text-xs text-slate-400 mb-6">Photos are automatically sanitized upon upload. All EXIF geolocation metadata and camera serial numbers are permanently stripped for your safety.</p>

                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <div class="relative">
                        @if($profile->avatar_path)
                            <img src="{{ Storage::url($profile->avatar_path) }}" alt="{{ $user->name }}" class="w-24 h-24 rounded-full object-cover border-2 border-rose-500/50 shadow-md">
                        @else
                            <div class="w-24 h-24 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-2xl font-bold text-rose-500">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('account.profile.avatar') }}" enctype="multipart/form-data" class="flex-1 w-full flex flex-col sm:flex-row items-center gap-3">
                        @csrf
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required
                            class="block w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700 file:cursor-pointer">
                        <button type="submit"
                            class="w-full sm:w-auto shrink-0 bg-slate-800 hover:bg-slate-700 text-white font-semibold py-2.5 px-5 rounded-xl text-xs transition border border-slate-700">
                            Upload Photo
                        </button>
                    </form>
                </div>
            </div>

            <!-- Profile Details Form -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8">
                <h2 class="text-lg font-bold text-white mb-6">Profile Information & Preferences</h2>

                <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Full / Real Name -->
                        <div>
                            <label for="name" class="block text-xs font-medium text-slate-300 mb-1">Account Name</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                                class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-rose-500">
                            @error('name')
                                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Display Name -->
                        <div>
                            <label for="display_name" class="block text-xs font-medium text-slate-300 mb-1">Public Display Name</label>
                            <input id="display_name" type="text" name="display_name" value="{{ old('display_name', $profile->display_name) }}"
                                class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-rose-500"
                                placeholder="Display name on ads">
                            @error('display_name')
                                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Username (Readonly) -->
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Handle</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-sm">@</span>
                                <input type="text" value="{{ $user->username }}" disabled
                                    class="w-full bg-slate-950/60 border border-slate-800 rounded-lg pl-8 pr-3.5 py-2.5 text-sm text-slate-400 cursor-not-allowed">
                            </div>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-xs font-medium text-slate-300 mb-1">Phone Number <span class="text-slate-500">(Optional)</span></label>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}"
                                class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-rose-500">
                            @error('phone')
                                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Gender -->
                        <div>
                            <label for="gender" class="block text-xs font-medium text-slate-300 mb-1">Gender</label>
                            <select id="gender" name="gender"
                                class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-rose-500">
                                <option value="">Select Gender</option>
                                <option value="Man" @selected(old('gender', $profile->gender) === 'Man')>Man</option>
                                <option value="Woman" @selected(old('gender', $profile->gender) === 'Woman')>Woman</option>
                                <option value="Couple" @selected(old('gender', $profile->gender) === 'Couple')>Couple</option>
                                <option value="Non-Binary" @selected(old('gender', $profile->gender) === 'Non-Binary')>Non-Binary / Other</option>
                            </select>
                        </div>

                        <!-- Seeking -->
                        <div>
                            <label for="seeking" class="block text-xs font-medium text-slate-300 mb-1">Seeking</label>
                            <select id="seeking" name="seeking"
                                class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-rose-500">
                                <option value="">Select Seeking</option>
                                <option value="Women" @selected(old('seeking', $profile->seeking) === 'Women')>Women</option>
                                <option value="Men" @selected(old('seeking', $profile->seeking) === 'Men')>Men</option>
                                <option value="Couples" @selected(old('seeking', $profile->seeking) === 'Couples')>Couples</option>
                                <option value="All" @selected(old('seeking', $profile->seeking) === 'All')>All / Anyone</option>
                            </select>
                        </div>
                    </div>

                    <!-- Location Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        <div>
                            <label for="city" class="block text-xs font-medium text-slate-300 mb-1">City</label>
                            <input id="city" type="text" name="city" value="{{ old('city', $profile->city) }}" required
                                class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-rose-500">
                            @error('city')
                                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="state_code" class="block text-xs font-medium text-slate-300 mb-1">State</label>
                            <select id="state_code" name="state_code" required
                                class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-rose-500">
                                @foreach($states ?? [] as $state)
                                    <option value="{{ $state->code }}" @selected(old('state_code', $profile->state_code) === $state->code)>
                                        {{ $state->name }} ({{ $state->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('state_code')
                                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="zip_code" class="block text-xs font-medium text-slate-300 mb-1">ZIP Code</label>
                            <input id="zip_code" type="text" name="zip_code" value="{{ old('zip_code', $profile->zip_code) }}"
                                class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-rose-500"
                                placeholder="e.g. 78701">
                        </div>
                    </div>

                    <!-- Bio -->
                    <div>
                        <label for="bio" class="block text-xs font-medium text-slate-300 mb-1">Personal Bio</label>
                        <textarea id="bio" name="bio" rows="4"
                            class="w-full bg-slate-950 border border-slate-700 rounded-lg p-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-rose-500"
                            placeholder="Share what kind of connections or dates you are seeking...">{{ old('bio', $profile->bio) }}</textarea>
                        @error('bio')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Privacy Controls -->
                    <div class="pt-4 border-t border-slate-800 space-y-3">
                        <h3 class="text-sm font-semibold text-white">Privacy Controls</h3>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="hide_location_details" value="1" @checked(old('hide_location_details', $profile->hide_location_details))
                                class="rounded bg-slate-950 border-slate-700 text-rose-500 focus:ring-rose-500 focus:ring-offset-slate-900">
                            <span class="text-xs text-slate-300">Hide precise neighborhood/ZIP on my public listings (show only City & State)</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="hide_age" value="1" @checked(old('hide_age', $profile->hide_age))
                                class="rounded bg-slate-950 border-slate-700 text-rose-500 focus:ring-rose-500 focus:ring-offset-slate-900">
                            <span class="text-xs text-slate-300">Hide my exact age number on public ad previews</span>
                        </label>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="submit"
                            class="bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white font-semibold py-2.5 px-6 rounded-xl text-sm transition shadow-lg shadow-rose-600/30">
                            Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>