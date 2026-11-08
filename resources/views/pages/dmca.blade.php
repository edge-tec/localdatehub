<x-layouts.app title="DMCA & Copyright Takedown Procedure">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Header -->
        <div class="border-b border-slate-800 pb-6 mb-8">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-rose-950 text-rose-300 border border-rose-800 mb-3">
                17 U.S.C. § 512 COMPLIANCE
            </span>
            <h1 class="text-3xl font-black text-white">DMCA &amp; Content Takedown Procedure</h1>
            <p class="mt-2 text-sm text-slate-400">
                LocalAdZone respects intellectual property rights and strictly prohibits non-consensual imagery, unauthorized personal likenesses, and copyright infringement.
            </p>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-xl border border-emerald-700 bg-emerald-950/60 p-4 text-sm text-emerald-200" role="status">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid md:grid-cols-3 gap-8">
            <!-- Left Info Panel -->
            <div class="space-y-6 text-sm text-slate-300">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                    <h2 class="text-base font-bold text-white mb-2">Designated DMCA Agent</h2>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        LocalAdZone Trust &amp; Safety Compliance<br>
                        Legal Operations Department<br>
                        Email: <a href="mailto:dmca@localadzone.com" class="text-rose-400 hover:underline">dmca@localadzone.com</a><br>
                        Response Time: Expedited (Under 24 Hours)
                    </p>
                </div>

                <div class="bg-red-950/30 border border-red-900/60 rounded-2xl p-5">
                    <h2 class="text-base font-bold text-red-300 mb-2">Urgent Safety / Non-Consensual Imagery</h2>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        If a listing contains intimate photos, telephone numbers, home addresses, or personal likeness posted without consent, submit this notice immediately. Urgent safety notices are prioritized for fast quarantine.
                    </p>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                    <h2 class="text-base font-bold text-white mb-2">Statutory Requirements</h2>
                    <ul class="text-xs text-slate-400 space-y-1.5 list-disc pl-4">
                        <li>Identification of the copyrighted or non-consensual work.</li>
                        <li>Direct URL of the listing or material.</li>
                        <li>Contact information of the claimant.</li>
                        <li>Attestation under penalty of perjury.</li>
                    </ul>
                </div>
            </div>

            <!-- Right Notice Form -->
            <div class="md:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8">
                <h2 class="text-xl font-bold text-white mb-4">Submit Takedown Notice</h2>

                <form method="POST" action="{{ route('dmca.submit') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="claimant_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">
                            Your Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="claimant_name" name="claimant_name" value="{{ old('claimant_name') }}" required
                            class="w-full rounded-lg bg-slate-950 border border-slate-800 text-sm text-slate-100 px-3.5 py-2.5 focus:border-rose-500 focus:ring-rose-500">
                        @error('claimant_name') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="claimant_email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">
                                Email Address <span class="text-rose-500">*</span>
                            </label>
                            <input type="email" id="claimant_email" name="claimant_email" value="{{ old('claimant_email') }}" required
                                class="w-full rounded-lg bg-slate-950 border border-slate-800 text-sm text-slate-100 px-3.5 py-2.5 focus:border-rose-500 focus:ring-rose-500">
                            @error('claimant_email') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="claimant_phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">
                                Telephone Number
                            </label>
                            <input type="tel" id="claimant_phone" name="claimant_phone" value="{{ old('claimant_phone') }}"
                                class="w-full rounded-lg bg-slate-950 border border-slate-800 text-sm text-slate-100 px-3.5 py-2.5 focus:border-rose-500 focus:ring-rose-500">
                            @error('claimant_phone') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="organization" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">
                            Organization / Represented Party (if applicable)
                        </label>
                        <input type="text" id="organization" name="organization" value="{{ old('organization') }}"
                            class="w-full rounded-lg bg-slate-950 border border-slate-800 text-sm text-slate-100 px-3.5 py-2.5 focus:border-rose-500 focus:ring-rose-500">
                    </div>

                    <div>
                        <label for="infringing_url" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">
                            Exact Infringing URL <span class="text-rose-500">*</span>
                        </label>
                        <input type="url" id="infringing_url" name="infringing_url" value="{{ old('infringing_url') }}" placeholder="https://localadzone.com/listings/..." required
                            class="w-full rounded-lg bg-slate-950 border border-slate-800 text-sm text-slate-100 px-3.5 py-2.5 focus:border-rose-500 focus:ring-rose-500 font-mono text-xs">
                        @error('infringing_url') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="copyrighted_work" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">
                            Description of Infringed Work / Likeness <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="copyrighted_work" name="copyrighted_work" rows="4" required
                            class="w-full rounded-lg bg-slate-950 border border-slate-800 text-sm text-slate-100 px-3.5 py-2.5 focus:border-rose-500 focus:ring-rose-500"
                            placeholder="Describe the copyrighted photograph, proprietary text, or non-consensual personal media appearing without authorization...">{{ old('copyrighted_work') }}</textarea>
                        @error('copyrighted_work') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-3 pt-2">
                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input type="checkbox" name="good_faith_attested" value="1" required @checked(old('good_faith_attested'))
                                class="mt-1 rounded bg-slate-950 border-slate-700 text-rose-600 focus:ring-rose-500">
                            <span class="text-xs text-slate-300">
                                I state in good faith that use of the material in the manner complained of is not authorized by the copyright owner, its agent, or the law.
                            </span>
                        </label>
                        @error('good_faith_attested') <p class="text-xs text-red-400">{{ $message }}</p> @enderror

                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input type="checkbox" name="perjury_attested" value="1" required @checked(old('perjury_attested'))
                                class="mt-1 rounded bg-slate-950 border-slate-700 text-rose-600 focus:ring-rose-500">
                            <span class="text-xs text-slate-300">
                                I state, under penalty of perjury, that the information in this notification is accurate and that I am the owner or authorized to act on behalf of the owner.
                            </span>
                        </label>
                        @error('perjury_attested') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="pt-2">
                        <label for="digital_signature" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1">
                            Electronic Signature (Type Your Full Legal Name) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="digital_signature" name="digital_signature" value="{{ old('digital_signature') }}" required
                            class="w-full rounded-lg bg-slate-950 border border-slate-800 text-sm text-slate-100 px-3.5 py-2.5 focus:border-rose-500 focus:ring-rose-500 font-mono">
                        @error('digital_signature') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="pt-4">
                        <button type="submit"
                            class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent text-sm font-bold rounded-lg text-white bg-rose-600 hover:bg-rose-500 transition shadow-lg shadow-rose-900/40">
                            Submit Expedited Takedown Notice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>

