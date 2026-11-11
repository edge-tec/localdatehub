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
            <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-2xl text-xs font-bold text-slate-700">
                <a 
                    href="{{ route('admin.verifications.index', ['status' => 'pending']) }}"
                    class="px-4 py-2 rounded-xl transition flex items-center space-x-1.5 {{ $currentStatus === 'pending' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}"
                >
                    <span>Pending</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $counts['pending'] > 0 ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-700' }}">
                        {{ $counts['pending'] }}
                    </span>
                </a>

                <a 
                    href="{{ route('admin.verifications.index', ['status' => 'verified']) }}"
                    class="px-4 py-2 rounded-xl transition flex items-center space-x-1.5 {{ $currentStatus === 'verified' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}"
                >
                    <span>Verified</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 text-slate-700">
                        {{ $counts['verified'] }}
                    </span>
                </a>

                <a 
                    href="{{ route('admin.verifications.index', ['status' => 'rejected']) }}"
                    class="px-4 py-2 rounded-xl transition flex items-center space-x-1.5 {{ $currentStatus === 'rejected' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}"
                >
                    <span>Rejected</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 text-slate-700">
                        {{ $counts['rejected'] }}
                    </span>
                </a>

                <a 
                    href="{{ route('admin.verifications.index', ['status' => 'all']) }}"
                    class="px-4 py-2 rounded-xl transition {{ $currentStatus === 'all' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800' }}"
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
                                        $badge = match($v->status) {
                                            'verified' => 'bg-blue-100 text-blue-800 border-blue-200',
                                            'pending', 'under_review' => 'bg-amber-100 text-amber-800 border-amber-200 animate-pulse',
                                            'rejected' => 'bg-rose-100 text-rose-800 border-rose-200',
                                            'suspended' => 'bg-purple-100 text-purple-800 border-purple-200',
                                            default => 'bg-slate-100 text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider border {{ $badge }}">
                                        {{ str_replace('_', ' ', $v->status) }}
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

