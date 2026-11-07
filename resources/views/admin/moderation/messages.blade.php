<x-layouts.app title="Trust & Safety — Inquiries & Abuse Reports Queue">
    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Admin Moderation', 'url' => route('admin.moderation.listings.queue')],
            ['label' => 'Messages & Safety Reports']
        ]" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Trust & Safety Moderation Queue
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full bg-rose-500/20 text-rose-400 font-bold text-xs border border-rose-500/30">
                        {{ $messages->total() }} Escalations
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    Review flagged inquiries, abuse reports, commercial sex work violations, and underage reports.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.moderation.listings.queue') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 border border-slate-700 transition">
                    Listings Moderation Queue &rarr;
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <x-alert type="success" :dismissible="true">
                {{ session('success') }}
            </x-alert>
        @endif

        <!-- Filter Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="{{ route('admin.moderation.messages.queue') }}" class="p-4 rounded-2xl bg-slate-900 border border-slate-800 hover:border-rose-500/50 transition">
                <span class="text-xs text-slate-400 block font-semibold">All Escalations</span>
                <span class="text-2xl font-extrabold text-white mt-1 block">{{ $totalFlagged + $totalSafetyReports }}</span>
            </a>
            <a href="{{ route('admin.moderation.messages.queue', ['type' => 'safety_report']) }}" class="p-4 rounded-2xl bg-slate-900 border border-slate-800 hover:border-amber-500/50 transition">
                <span class="text-xs text-amber-400 block font-semibold">User Safety Reports</span>
                <span class="text-2xl font-extrabold text-white mt-1 block">{{ $totalSafetyReports }}</span>
            </a>
            <a href="{{ route('admin.moderation.messages.queue', ['type' => 'listing_inquiry']) }}" class="p-4 rounded-2xl bg-slate-900 border border-slate-800 hover:border-rose-500/50 transition">
                <span class="text-xs text-rose-400 block font-semibold">Flagged Inquiries (Spam/Abuse)</span>
                <span class="text-2xl font-extrabold text-white mt-1 block">{{ $totalFlagged }}</span>
            </a>
        </div>

        <!-- Moderation Stream -->
        @if($messages->isNotEmpty())
            <div class="bg-slate-900 border border-slate-800 rounded-3xl divide-y divide-slate-800 overflow-hidden shadow-xl">
                @foreach($messages as $msg)
                    <div class="p-6 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                @if($msg->inquiry_type === 'safety_report')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase">
                                        Safety Report: {{ $msg->flagged_reason }}
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30 uppercase">
                                        Flagged Inquiry: {{ $msg->flagged_reason }}
                                    </span>
                                @endif

                                <span class="text-xs text-slate-400">Reporter / Sender: <strong class="text-white">{{ $msg->sender_name }}</strong></span>
                                <span class="text-xs text-slate-500">({{ $msg->sender_email_encrypted }})</span>
                                <span class="text-xs text-slate-500">&bull; IP: {{ $msg->client_ip ?? 'Unknown' }}</span>
                            </div>

                            <span class="text-xs text-slate-400">{{ $msg->created_at->diffForHumans() }}</span>
                        </div>

                        <!-- Listing Info -->
                        @if($msg->listing)
                            <div class="p-3 bg-slate-950 rounded-xl border border-slate-800/80 text-xs flex items-center justify-between">
                                <div>
                                    <span class="text-slate-400">Target Listing:</span>
                                    <a href="{{ route('listings.show', $msg->listing->slug) }}" target="_blank" class="text-rose-400 font-bold hover:underline ml-1">
                                        {{ $msg->listing->title }}
                                    </a>
                                    <span class="text-slate-500">({{ $msg->listing->city?->name }}, {{ $msg->listing->state?->code }} &bull; Status: {{ strtoupper($msg->listing->status) }})</span>
                                </div>
                                <span class="text-slate-400">Owner ID: #{{ $msg->listing->user_id }}</span>
                            </div>
                        @endif

                        <!-- Message Body -->
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300 whitespace-pre-line">
                            {{ $msg->message_encrypted }}
                        </div>

                        <!-- Moderation Actions -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <!-- Dismiss Flag -->
                            <form action="{{ route('admin.moderation.messages.dismiss', $msg->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold border border-slate-700 transition">
                                    Dismiss Flag (False Positive)
                                </button>
                            </form>

                            <!-- Suspend Listing -->
                            @if($msg->listing && $msg->listing->status !== 'suspended')
                                <form action="{{ route('admin.moderation.messages.action', $msg->id) }}" method="POST" onsubmit="return confirm('Immediately suspend this listing?');" class="inline">
                                    @csrf
                                    <input type="hidden" name="action" value="suspend_listing">
                                    <input type="hidden" name="note" value="Violated Trust & Safety policies following report #{{ $msg->id }}">
                                    <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold shadow-md shadow-rose-600/20 transition">
                                        Suspend Listing & Enforce Action
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $messages->links() }}
            </div>
        @else
            <x-card class="text-center py-12">
                <x-empty-state
                    title="Moderation Queue Clear"
                    description="There are currently no active flagged inquiries or safety escalation reports."
                    actionText="Return to Dashboard"
                    :actionUrl="route('home')"
                />
            </x-card>
        @endif
    </div>
</x-layouts.app>

