<x-layouts.app title="Review Verification #{{ $verification->id }} — Admin">
    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8" x-data="{ rejectModal: false, resubmitModal: false, suspendModal: false }">

        <!-- Breadcrumb & Header -->
        <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-500">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-800 transition">Admin Dashboard</a>
            <span>/</span>
            <a href="{{ route('admin.verifications.index') }}" class="hover:text-slate-800 transition">Verification Queue</a>
            <span>/</span>
            <span class="text-slate-900">Application #{{ $verification->id }}</span>
        </nav>

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                        Review: {{ $verification->user?->name }}
                    </h1>
                    @php
                        $canonical = $verification->canonicalStatus();
                        $badge = match($canonical) {
                            'approved' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'pending' => 'bg-amber-100 text-amber-800 border-amber-200 animate-pulse',
                            'under_review' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                            'resubmission_required' => 'bg-orange-100 text-orange-800 border-orange-200',
                            'rejected' => 'bg-rose-100 text-rose-800 border-rose-200',
                            'revoked' => 'bg-purple-100 text-purple-800 border-purple-200',
                            'expired' => 'bg-amber-50 text-amber-800 border-amber-200',
                            default => 'bg-slate-100 text-slate-600 border-slate-200',
                        };
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider border {{ $badge }}">
                        {{ $verification->statusLabel() }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Submitted: {{ $verification->submitted_at?->format('M d, Y · h:i A') }} · User Email: {{ $verification->user?->email }}
                    @if($verification->reviewer)
                        · Reviewer: {{ $verification->reviewer->name }}
                    @endif
                    @if($verification->expires_at)
                        · Expires: {{ $verification->expires_at->format('M d, Y') }}
                    @endif
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                @if(!$verification->isVerified())
                    @if($verification->isPending())
                        <form action="{{ route('admin.verifications.under-review', $verification) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-xs transition">
                                Mark Under Review
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('admin.verifications.approve', $verification) }}" method="POST" onsubmit="return confirm('Approve verification and grant Blue Verified Badge?');">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs shadow-xs transition flex items-center space-x-1.5">
                            <span>✓ Approve &amp; Verify (Blue Badge)</span>
                        </button>
                    </form>

                    <button 
                        type="button" 
                        @click="resubmitModal = true"
                        class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-xs transition"
                    >
                        ↻ Request Resubmission
                    </button>

                    <button 
                        type="button" 
                        @click="rejectModal = true"
                        class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs shadow-xs transition"
                    >
                        ✕ Reject
                    </button>
                @else
                    <button 
                        type="button" 
                        @click="suspendModal = true"
                        class="bg-amber-600 hover:bg-amber-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs shadow-xs transition"
                    >
                        Revoke Blue Badge
                    </button>
                @endif
            </div>
        </div>

        <!-- 3-Column Documents Inspection Grid -->
        <div class="space-y-4">
            <h2 class="text-base font-black text-slate-900 uppercase tracking-wider text-xs">Submitted Verification Artifacts</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- 1. Front of Document -->
                <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-3 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-800">1. Document Front</span>
                            <span class="text-[10px] font-bold text-slate-400 capitalize">{{ str_replace('_', ' ', $verification->document_type) }}</span>
                        </div>
                        <div class="mt-3 aspect-4/3 bg-slate-50 border border-slate-200 rounded-2xl overflow-hidden flex items-center justify-center">
                            @if(str_ends_with(strtolower($verification->document_front_path), '.pdf'))
                                <span class="text-xs font-bold text-slate-600">PDF Document</span>
                            @else
                                <img 
                                    src="{{ route('admin.verifications.document', [$verification, 'front']) }}" 
                                    alt="Document Front"
                                    class="w-full h-full object-contain"
                                >
                            @endif
                        </div>
                    </div>
                    <a 
                        href="{{ route('admin.verifications.document', [$verification, 'front']) }}" 
                        target="_blank"
                        class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl font-bold text-xs text-center block transition"
                    >
                        Open Full File ↗
                    </a>
                </div>

                <!-- 2. Back of Document -->
                <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-3 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-800">2. Document Back</span>
                            <span class="text-[10px] font-bold text-slate-400">{{ $verification->document_back_path ? 'Provided' : 'N/A' }}</span>
                        </div>
                        <div class="mt-3 aspect-4/3 bg-slate-50 border border-slate-200 rounded-2xl overflow-hidden flex items-center justify-center">
                            @if($verification->document_back_path)
                                @if(str_ends_with(strtolower($verification->document_back_path), '.pdf'))
                                    <span class="text-xs font-bold text-slate-600">PDF Document</span>
                                @else
                                    <img 
                                        src="{{ route('admin.verifications.document', [$verification, 'back']) }}" 
                                        alt="Document Back"
                                        class="w-full h-full object-contain"
                                    >
                                @endif
                            @else
                                <span class="text-xs text-slate-400 italic">No back document required/provided</span>
                            @endif
                        </div>
                    </div>
                    @if($verification->document_back_path)
                        <a 
                            href="{{ route('admin.verifications.document', [$verification, 'back']) }}" 
                            target="_blank"
                            class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl font-bold text-xs text-center block transition"
                        >
                            Open Full File ↗
                        </a>
                    @else
                        <div class="py-2 text-center text-xs text-slate-400">—</div>
                    @endif
                </div>

                <!-- 3. Live Selfie -->
                <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm space-y-3 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-800">3. Live Selfie</span>
                            <span class="text-[10px] font-bold text-slate-400">Facial Match</span>
                        </div>
                        <div class="mt-3 aspect-4/3 bg-slate-50 border border-slate-200 rounded-2xl overflow-hidden flex items-center justify-center">
                            <img 
                                src="{{ route('admin.verifications.document', [$verification, 'selfie']) }}" 
                                alt="Selfie Photo"
                                class="w-full h-full object-contain"
                            >
                        </div>
                    </div>
                    <a 
                        href="{{ route('admin.verifications.document', [$verification, 'selfie']) }}" 
                        target="_blank"
                        class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl font-bold text-xs text-center block transition"
                    >
                        Open Full File ↗
                    </a>
                </div>
            </div>
        </div>

        <!-- Immutable Audit Trail -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Application Audit Trail &amp; Access Log</h3>
            <div class="divide-y divide-slate-100 text-xs">
                @foreach($verification->auditLogs as $log)
                    <div class="py-3 flex items-start justify-between gap-4">
                        <div>
                            <span class="font-bold text-slate-900">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</span>
                            <p class="text-slate-600 mt-0.5">{{ $log->details }}</p>
                        </div>
                        <div class="text-right text-[11px] text-slate-400 font-mono shrink-0">
                            <div>{{ $log->created_at->format('M d, Y · h:i A') }}</div>
                            <div>By: {{ $log->actor?->name ?: 'System' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Reject Modal -->
        <div 
            x-show="rejectModal" 
            x-cloak 
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-4">
                <h3 class="text-lg font-black text-slate-900">Reject Verification Application</h3>
                <p class="text-xs text-slate-600">Please supply a specific reason for rejection to assist the candidate in correcting their submission.</p>

                <form action="{{ route('admin.verifications.reject', $verification) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Rejection Reason *:</label>
                        <textarea 
                            name="rejection_reason" 
                            rows="3" 
                            required 
                            placeholder="e.g. Document image was blurry; name on ID did not match account; selfie was obstructed..."
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-2">
                        <button type="button" @click="rejectModal = false" class="px-4 py-2 text-xs font-bold text-slate-600">Cancel</button>
                        <button type="submit" class="bg-rose-600 text-white px-5 py-2 rounded-xl text-xs font-bold shadow-xs">Confirm Rejection</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Request Resubmission Modal -->
        <div 
            x-show="resubmitModal" 
            x-cloak 
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-4">
                <h3 class="text-lg font-black text-slate-900">Request Document Resubmission</h3>
                <p class="text-xs text-slate-600">Ask the user to upload a clearer ID card photo or live selfie before approval.</p>

                <form action="{{ route('admin.verifications.resubmit', $verification) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Instructions for User *:</label>
                        <textarea 
                            name="reason" 
                            rows="3" 
                            required 
                            placeholder="e.g. Please upload a brighter photo showing all 4 corners of your ID..."
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-2">
                        <button type="button" @click="resubmitModal = false" class="px-4 py-2 text-xs font-bold text-slate-600">Cancel</button>
                        <button type="submit" class="bg-orange-500 text-white px-5 py-2 rounded-xl text-xs font-bold shadow-xs">Request Resubmission</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Revoke / Suspend Modal -->
        <div 
            x-show="suspendModal" 
            x-cloak 
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-4">
                <h3 class="text-lg font-black text-slate-900">Revoke Blue Verified Badge</h3>
                <p class="text-xs text-slate-600">This will immediately revoke identity verification and remove the Blue Verified badge across all listings and profile views.</p>

                <form action="{{ route('admin.verifications.revoke', $verification) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Revocation Reason *:</label>
                        <textarea 
                            name="reason" 
                            rows="3" 
                            required 
                            placeholder="e.g. Verification expired, invalid ID discovered, or policy violation..."
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-2">
                        <button type="button" @click="suspendModal = false" class="px-4 py-2 text-xs font-bold text-slate-600">Cancel</button>
                        <button type="submit" class="bg-amber-600 text-white px-5 py-2 rounded-xl text-xs font-bold shadow-xs">Confirm Revoke</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.app>

