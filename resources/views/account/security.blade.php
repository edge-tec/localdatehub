<x-layouts.app title="Security & Two-Factor Authentication — Account Settings">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Account Header & Tabs -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Account Settings</h1>
                    <p class="text-sm text-slate-400 mt-1">Manage credentials, two-factor authentication, and account lifecycle.</p>
                </div>
            </div>

            <nav class="flex border-b border-slate-200 space-x-6 text-sm font-medium">
                <a href="{{ route('account.profile') }}" class="py-3 border-b-2 border-transparent text-slate-400 hover:text-slate-900 flex items-center gap-2 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Profile & Privacy
                </a>
                <a href="{{ route('account.security') }}" class="py-3 border-b-2 border-rose-500 text-rose-600 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Security & 2FA
                </a>
            </nav>
        </div>

        <div class="space-y-8">
            <!-- Password Update Section -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-2">Change Password</h2>
                <p class="text-xs text-slate-400 mb-6">Ensure your account uses a strong, unique password of at least 8 characters with letters and numbers.</p>

                <form method="POST" action="{{ route('account.security.password') }}" class="max-w-lg space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block text-xs font-medium text-slate-600 mb-1">Current Password</label>
                        <input id="current_password" type="password" name="current_password" required autocomplete="current-password"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-rose-500">
                        @error('current_password')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-medium text-slate-600 mb-1">New Password</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-rose-500"
                            placeholder="Minimum 8 characters">
                        @error('password')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-medium text-slate-600 mb-1">Confirm New Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-rose-500"
                            placeholder="Repeat new password">
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="bg-slate-100 hover:bg-slate-200 text-slate-900 font-semibold py-2.5 px-5 rounded-xl text-xs transition border border-slate-200">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Two-Factor Authentication (2FA) Section -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Two-Factor Authentication (TOTP)</h2>
                        <p class="text-xs text-slate-400 mt-1">Add an extra layer of protection to your account using Google Authenticator, Authy, or 1Password.</p>
                    </div>
                    @if($user->two_factor_enabled)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-700 border border-emerald-500/20">
                            Enabled & Active
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200">
                            Disabled
                        </span>
                    @endif
                </div>

                @if(!$user->two_factor_enabled && !$pendingSecret)
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-900">Protect your personal listings</p>
                            <p class="text-xs text-slate-400 mt-0.5">When enabled, you will be prompted for a secure 6-digit one-time token each time you sign in.</p>
                        </div>
                        <form method="POST" action="{{ route('account.security.2fa.generate') }}">
                            @csrf
                            <button type="submit"
                                class="bg-rose-600 hover:bg-rose-500 text-white font-semibold py-2 px-4 rounded-xl text-xs transition shadow-md shadow-rose-600/20 whitespace-nowrap">
                                Enable Two-Factor
                            </button>
                        </form>
                    </div>
                @elseif(!$user->two_factor_enabled && $pendingSecret)
                    <!-- Setup in progress -->
                    <div class="p-6 rounded-xl bg-slate-50 border border-slate-200 space-y-4">
                        <div class="space-y-1">
                            <h3 class="text-sm font-semibold text-slate-900">Complete Two-Factor Setup</h3>
                            <p class="text-xs text-slate-400">1. Open your authenticator app and manually add this secret key:</p>
                        </div>

                        <div class="p-3 bg-white border border-slate-200 rounded-lg flex items-center justify-between">
                            <code class="font-mono text-sm text-rose-600 tracking-wider select-all">{{ $pendingSecret }}</code>
                            <span class="text-[11px] text-slate-500">Base32 Secret</span>
                        </div>

                        <p class="text-xs text-slate-400">2. Enter the 6-digit code shown in your authenticator app to verify and activate 2FA:</p>

                        <form method="POST" action="{{ route('account.security.2fa.confirm') }}" class="flex flex-col sm:flex-row gap-3">
                            @csrf
                            <input type="text" name="two_factor_code" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                                class="w-full sm:w-48 bg-white border border-slate-200 rounded-lg px-3 py-2 text-center font-mono tracking-widest text-sm text-slate-900 focus:outline-none focus:border-rose-500"
                                placeholder="123456">
                            <button type="submit"
                                class="bg-rose-600 hover:bg-rose-500 text-white font-semibold py-2 px-5 rounded-xl text-xs transition shadow-md shadow-rose-600/20">
                                Verify & Activate
                            </button>
                        </form>
                        @error('two_factor_code')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <!-- 2FA is active, show disable option -->
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 space-y-4">
                        <div class="flex items-center gap-3 text-emerald-700 text-sm">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Your account is protected with Two-Factor Authentication.</span>
                        </div>

                        <form method="POST" action="{{ route('account.security.2fa.disable') }}" class="pt-3 border-t border-slate-200 flex flex-col sm:flex-row items-start sm:items-center gap-3">
                            @csrf
                            <input type="password" name="current_password" required placeholder="Enter password to disable"
                                class="w-full sm:w-64 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-900 focus:outline-none focus:border-rose-500">
                            <button type="submit"
                                class="bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-600 font-medium py-2 px-4 rounded-xl text-xs transition border border-slate-200">
                                Disable Two-Factor
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <!-- Danger Zone: Account Deletion -->
            <div class="bg-rose-50 border border-rose-200 rounded-2xl p-6 sm:p-8">
                <h2 class="text-lg font-bold text-rose-600 mb-1">Delete Account</h2>
                <p class="text-xs text-slate-400 mb-6">
                    Permanently delete your account and associated personal data in compliance with privacy regulations (GDPR & CCPA).
                    All your active classified listings will be archived and soft-deleted immediately.
                </p>

                <form method="POST" action="{{ route('account.destroy') }}" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This action cannot be reversed.')" class="max-w-md space-y-4">
                    @csrf
                    @method('DELETE')

                    <div>
                        <label for="delete_password" class="block text-xs font-medium text-slate-600 mb-1">Confirm with your current password</label>
                        <input id="delete_password" type="password" name="password" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-rose-500"
                            placeholder="Current password">
                        @error('password')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2.5 px-5 rounded-xl text-xs transition shadow-md shadow-slate-200">
                        Permanently Delete My Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
