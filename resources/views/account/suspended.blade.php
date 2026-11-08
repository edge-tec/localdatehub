<x-layouts.auth>
    <div class="bg-white border border-rose-200 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl">
        <div class="mb-6 text-center">
            <div class="w-14 h-14 rounded-full bg-rose-500/10 text-rose-500 mx-auto mb-3 flex items-center justify-center border border-rose-500/30">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Account Suspended</h1>
            <p class="text-sm text-rose-600 mt-1 font-medium">Policy Violation Notice</p>
        </div>

        <div class="space-y-4">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-2 leading-relaxed">
                <p>
                    Your account has been suspended by our Trust & Safety team due to a violation of LocalDateHub's Community Guidelines or Adult Personal Classifieds Terms of Service.
                </p>
                @if($suspension)
                    <div class="pt-2 border-t border-slate-200">
                        <span class="text-slate-400 font-semibold">Violation Summary:</span>
                        <p class="text-slate-800 mt-0.5">{{ $suspension->reason ?? 'Breach of Community Safety Guidelines.' }}</p>
                        @if($suspension->ends_at)
                            <p class="text-[11px] text-amber-700 mt-1">Suspension scheduled to end: {{ $suspension->ends_at->format('M d, Y H:i T') }}</p>
                        @else
                            <p class="text-[11px] text-rose-600 mt-1">Status: Indefinite / Permanent Suspension</p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Appeal Section -->
            @if($existingAppeal)
                <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-700">
                    <div class="font-semibold text-amber-700 mb-1">Appeal Under Review</div>
                    <p>You have an appeal currently pending review by our compliance team (Submitted {{ $existingAppeal->created_at->diffForHumans() }}). You will receive an email update once a determination has been made.</p>
                </div>
            @else
                <div class="pt-4 border-t border-slate-200">
                    <h2 class="text-sm font-semibold text-slate-900 mb-1">Submit an Appeal</h2>
                    <p class="text-xs text-slate-400 mb-3">If you believe this suspension was made in error, submit an explanation below for human review.</p>

                    <form method="POST" action="{{ route('account.appeal') }}" class="space-y-3">
                        @csrf
                        <div>
                            <textarea name="appeal_reason" rows="4" required minlength="20" maxlength="2000"
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs text-slate-900 placeholder-slate-500 focus:outline-none focus:border-rose-500"
                                placeholder="Explain why your account should be reinstated (minimum 20 characters)...">{{ old('appeal_reason') }}</textarea>
                            @error('appeal_reason')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                            class="w-full bg-slate-100 hover:bg-slate-200 text-slate-900 font-semibold py-2.5 px-4 rounded-xl text-xs transition border border-slate-200">
                            Submit Appeal to Trust & Safety
                        </button>
                    </form>
                </div>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="pt-4 text-center">
                @csrf
                <button type="submit" class="text-xs text-slate-400 hover:text-slate-900 transition">
                    Sign Out
                </button>
            </form>
        </div>
    </div>
</x-layouts.auth>
