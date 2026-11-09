<x-layouts.app title="Identity Verification — LocalDateHub">
    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Breadcrumb & Header -->
        <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-500">
            <a href="{{ route('account.dashboard') }}" class="hover:text-slate-800 transition">My Account</a>
            <span>/</span>
            <span class="text-slate-900">Identity Verification</span>
        </nav>

        <div class="border-b border-slate-200 pb-4">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                Account Identity Verification
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 mt-1">
                Protecting our community from spam, bots, and fraudulent advertisers through secure, private identity assurance.
            </p>
        </div>

        <!-- Verification Status Cards -->
        @if($user->is_identity_verified)
            <div class="bg-gradient-to-r from-blue-50 to-emerald-50/50 border border-blue-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-4">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-2xl font-black shadow-md shrink-0">
                        ✓
                    </div>
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-blue-700">Verified Member</span>
                        <h2 class="text-xl font-black text-slate-900">Your Account is Identity-Verified</h2>
                        <p class="text-xs text-slate-600 mt-0.5">Verified on {{ $verification?->reviewed_at?->format('M d, Y') ?? 'Confirmed' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-blue-200/60 text-xs text-slate-700">
                    <div class="bg-white/80 p-3 rounded-xl border border-blue-100 flex items-center space-x-2">
                        <span class="text-blue-600 font-bold">★</span>
                        <span>Search Ranking Priority</span>
                    </div>
                    <div class="bg-white/80 p-3 rounded-xl border border-blue-100 flex items-center space-x-2">
                        <span class="text-blue-600 font-bold">✓</span>
                        <span>Verified Poster Badge</span>
                    </div>
                    <div class="bg-white/80 p-3 rounded-xl border border-blue-100 flex items-center space-x-2">
                        <span class="text-blue-600 font-bold">⚡</span>
                        <span>Automated Fast Approvals</span>
                    </div>
                </div>
            </div>
        @elseif($verification && $verification->isPending())
            <div class="bg-amber-50 border border-amber-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-3">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl font-black shrink-0">
                        ⏳
                    </div>
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-amber-800">Under Review</span>
                        <h2 class="text-lg font-black text-slate-900">Verification Application Pending</h2>
                        <p class="text-xs text-slate-600 mt-0.5">Submitted on {{ $verification->submitted_at?->format('M d, Y · h:i A') }}</p>
                    </div>
                </div>
                <p class="text-xs text-slate-700 leading-relaxed pt-2 border-t border-amber-200/80">
                    Your government-issued document and selfie have been securely encrypted and placed into the administrator review queue. Verification reviews are typically completed within 1 to 12 hours.
                </p>
            </div>
        @else
            @if($verification && $verification->status === 'rejected')
                <div class="bg-rose-50 border border-rose-200 rounded-3xl p-5 shadow-sm space-y-2">
                    <div class="flex items-center space-x-2 text-rose-800 font-bold text-sm">
                        <span>⚠️</span>
                        <span>Previous Application Declined</span>
                    </div>
                    <p class="text-xs text-rose-700">
                        <strong>Reason:</strong> {{ $verification->rejection_reason }}
                    </p>
                    <p class="text-[11px] text-slate-500">Please review the guidelines below and re-submit clear, valid documents.</p>
                </div>
            @endif

            <!-- Verification Submission Form -->
            <form 
                action="{{ route('account.verification.store') }}" 
                method="POST" 
                enctype="multipart/form-data"
                class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6"
            >
                @csrf

                <div>
                    <h3 class="text-lg font-black text-slate-900">Submit Verification Documents</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Please upload clear, legible photos of your government-issued ID and a live selfie. Documents are stored in encrypted private storage and never exposed publicly.
                    </p>
                </div>

                <!-- Document Type -->
                <div class="space-y-1.5">
                    <label for="document_type" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Document Type *
                    </label>
                    <select 
                        name="document_type" 
                        id="document_type" 
                        required
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-semibold focus:bg-white focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] focus:outline-none"
                    >
                        <option value="drivers_license">Driver's License</option>
                        <option value="passport">State / National Passport</option>
                        <option value="national_id">National Identity Card</option>
                        <option value="state_id">State-Issued Photo ID</option>
                    </select>
                    @error('document_type') <span class="text-xs text-rose-600 block">{{ $message }}</span> @enderror
                </div>

                <!-- Document Front -->
                <div class="space-y-1.5">
                    <label for="document_front" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Front of ID Document *
                    </label>
                    <input 
                        type="file" 
                        name="document_front" 
                        id="document_front" 
                        accept="image/jpeg,image/png,application/pdf"
                        required
                        class="w-full text-xs text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
                    >
                    <p class="text-[11px] text-slate-400">JPEG, PNG, or PDF up to 10MB. Must show all 4 corners and full legal name.</p>
                    @error('document_front') <span class="text-xs text-rose-600 block">{{ $message }}</span> @enderror
                </div>

                <!-- Document Back (Optional for Passports) -->
                <div class="space-y-1.5">
                    <label for="document_back" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Back of ID Document <span class="text-slate-400 font-normal">(Optional for passports)</span>
                    </label>
                    <input 
                        type="file" 
                        name="document_back" 
                        id="document_back" 
                        accept="image/jpeg,image/png,application/pdf"
                        class="w-full text-xs text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
                    >
                    <p class="text-[11px] text-slate-400">Required for driver's licenses and national ID cards.</p>
                    @error('document_back') <span class="text-xs text-rose-600 block">{{ $message }}</span> @enderror
                </div>

                <!-- Live Selfie -->
                <div class="space-y-1.5">
                    <label for="selfie" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Live Selfie Photo *
                    </label>
                    <input 
                        type="file" 
                        name="selfie" 
                        id="selfie" 
                        accept="image/jpeg,image/png"
                        required
                        class="w-full text-xs text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-pink-50 file:text-[#F20B83] hover:file:bg-pink-100 cursor-pointer"
                    >
                    <p class="text-[11px] text-slate-400">Clear frontal face selfie matching the ID document.</p>
                    @error('selfie') <span class="text-xs text-rose-600 block">{{ $message }}</span> @enderror
                </div>

                <!-- Privacy & Safety Guarantee -->
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 text-[11px] text-slate-600 space-y-1">
                    <span class="font-bold text-slate-800 block">🔒 Privacy &amp; Data Security Guarantee</span>
                    <p>
                        Your documents are strictly used by authorized human compliance officers to verify legal adult age and prevent bot fraud. Files are never shared with 3rd-party advertisers or exposed online.
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end pt-2">
                    <button 
                        type="submit" 
                        class="bg-[#F20B83] hover:bg-[#d90858] text-white px-8 py-3 rounded-full font-bold text-xs shadow-md transition flex items-center space-x-2"
                    >
                        <span>Submit Verification Request</span>
                        <span>→</span>
                    </button>
                </div>
            </form>
        @endif

    </div>
</x-layouts.app>

