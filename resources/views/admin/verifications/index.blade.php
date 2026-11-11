<x-layouts.app title="Identity Verification Queue — Admin">
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-800 transition">Admin Dashboard</a>
                    <span>/</span>
                    <span class="text-slate-900">Identity Verification</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    Identity Verification Queue
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                    Review submitted government documents and selfies. Grant verified badges and organic search priority.
                </p>
            </div>

            <!-- Status Tabs -->
            <div class="flex flex-wrap items-center gap-1 bg-slate-100 p-1 rounded-2xl text-xs font-bold text-slate-700">
                <a 
                    href="{{ route('admin.verifications.index', ['status' => 'pending']) }}"
                    class="px-3 py-2 rounded-xl transition flex items-center space-x-1.5 {{ $currentStatus === 'pending' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}"
                >
                    <span>Pending</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $counts['pending'] > 0 ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-700' }}">
                        {{ $counts['pending'] }}
                    </span>
                </a>

                <a 
                    href="{{ route('admin.verifications.index', ['status' => 'under_review']) }}"
                    class="px-3 py-2 rounded-xl transition flex items-center space-x-1.5 {{ $currentStatus === 'under_review' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}"
                >
                    <span>Under Review</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-indigo-100 text-indigo-800">
                        {{ $counts['under_review'] ?? 0 }}
                    </span>
                </a>

                <a 
                    href="{{ route('admin.verifications.index', ['status' => 'verified']) }}"
                    class="px-3 py-2 rounded-xl transition flex items-center space-x-1.5 {{ in_array($currentStatus, ['verified', 'approved'], true) ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}"
                >
                    <span>Approved</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-blue-100 text-blue-800">
                        {{ $counts['verified'] }}
                    </span>
                </a>

                <a 
                    href="{{ route('admin.verifications.index', ['status' => 'resubmission_required']) }}"
                    class="px-3 py-2 rounded-xl transition flex items-center space-x-1.5 {{ $currentStatus === 'resubmission_required' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}"
                >
                    <span>Resubmit</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-orange-100 text-orange-800">
                        {{ $counts['resubmission_required'] ?? 0 }}
                    </span>
                </a>

                <a 
                    href="{{ route('admin.verifications.index', ['status' => 'rejected']) }}"
                    class="px-3 py-2 rounded-xl transition flex items-center space-x-1.5 {{ $currentStatus === 'rejected' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}"
                >
                    <span>Rejected</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-100 text-rose-800">
                        {{ $counts['rejected'] }}
                    </span>
                </a>

                <a 
                    href="{{ route('admin.verifications.index', ['status' => 'revoked']) }}"
                    class="px-3 py-2 rounded-xl transition flex items-center space-x-1.5 {{ in_array($currentStatus, ['revoked', 'suspended'], true) ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}"
                >
                    <span>Revoked</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 text-slate-700">
                        {{ $counts['revoked'] ?? 0 }}
                    </span>
                </a>

                <a 
                    href="{{ route('admin.verifications.index', ['status' => 'expired']) }}"
                    class="px-3 py-2 rounded-xl transition flex items-center space-x-1.5 {{ $currentStatus === 'expired' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}"
                >
                    <span>Expired</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 text-slate-700">
                        {{ $counts['expired'] ?? 0 }}
                    </span>
                </a>

                <a 
                    href="{{ route('admin.verifications.index', ['status' => 'all']) }}"
                    class="px-3 py-2 rounded-xl transition {{ $currentStatus === 'all' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}"
                >
                    All ({{ $counts['all'] }})
                </a>
            </div>
        </div>

        <!-- Verifications Table -->
        <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-5 py-3.5">User Candidate</th>
                            <th class="px-5 py-3.5">Document Type</th>
                            <th class="px-5 py-3.5">Submission Date</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Reviewer</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($verifications as $v)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-900">{{ $v->user?->name }}</div>
                                    <div class="text-[10px] text-slate-400">ID: {{ $v->user_id }} · {{ $v->user?->email }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-bold text-slate-800 capitalize">{{ str_replace('_', ' ', $v->document_type) }}</span>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        Front + {{ $v->document_back_path ? 'Back +' : '' }} Selfie
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-mono text-[11px] text-slate-700">{{ $v->submitted_at ? $v->submitted_at->format('M d, Y · h:i A') : $v->created_at->format('M d, Y') }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    @php
                                        $canonical = $v->canonicalStatus();
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
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider border {{ $badge }}">
                                        {{ $v->statusLabel() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-slate-600">{{ $v->reviewer?->name ?: '—' }}</span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a 
                                        href="{{ route('admin.verifications.show', $v) }}" 
                                        class="bg-[#F20B83] hover:bg-[#d90858] text-white px-4 py-1.5 rounded-xl font-bold text-xs shadow-2xs transition inline-flex items-center space-x-1"
                                    >
                                        <span>Inspect &amp; Review</span>
                                        <span>→</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-400 italic">
                                    No identity verification requests found in this queue.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($verifications->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $verifications->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.app>

