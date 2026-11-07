<x-layouts.app title="Inquiry from {{ $message->sender_name }} — LocalDateHub">
    <div class="py-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'My Account', 'url' => route('account.profile')],
            ['label' => 'Messages', 'url' => route('account.messages.index')],
            ['label' => 'Inquiry #' . $message->id]
        ]" />

        <!-- Flash Messages -->
        @if(session('success'))
            <x-alert type="success" :dismissible="true">
                {{ session('success') }}
            </x-alert>
        @endif

        <!-- Main Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl sm:text-2xl font-bold text-white">
                            Inquiry from {{ $message->sender_name }}
                        </h1>
                        @if($message->is_replied)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase">Replied</span>
                        @endif
                        @if($message->is_flagged)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase">Flagged: {{ $message->flagged_reason }}</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-400">
                        Received {{ $message->created_at->format('M d, Y @ h:i A') }} ({{ $message->created_at->diffForHumans() }})
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('account.messages.index') }}" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 font-semibold transition border border-slate-700">
                        &larr; Back to Inbox
                    </a>
                </div>
            </div>

            <!-- Associated Classified Listing Context -->
            @if($message->listing)
                <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-between gap-4">
                    <div class="space-y-0.5">
                        <span class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold block">Regarding Your Advertisement:</span>
                        <a href="{{ route('listings.show', $message->listing->slug) }}" class="text-sm font-bold text-white hover:text-rose-400 transition">
                            {{ $message->listing->title }}
                        </a>
                        <span class="text-xs text-slate-400 block">
                            {{ $message->listing->category?->name }} &bull; {{ $message->listing->city?->name }}, {{ $message->listing->state?->code }}
                        </span>
                    </div>

                    <a href="{{ route('listings.show', $message->listing->slug) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-rose-400 transition shrink-0">
                        View Ad &rarr;
                    </a>
                </div>
            @endif

            <!-- Sender Contact Details (Decrypted for Recipient) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-1">
                    <span class="text-slate-400 uppercase tracking-wider text-[10px] font-semibold block">Sender Email:</span>
                    <a href="mailto:{{ $message->sender_email_encrypted }}?subject=Re: {{ urlencode($message->subject ?? 'Your Inquiry') }}" class="text-rose-400 hover:underline font-mono text-xs block break-all font-semibold">
                        {{ $message->sender_email_encrypted }}
                    </a>
                </div>

                <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-1">
                    <span class="text-slate-400 uppercase tracking-wider text-[10px] font-semibold block">Sender Phone / SMS:</span>
                    @if($message->sender_phone_encrypted)
                        <a href="tel:{{ $message->sender_phone_encrypted }}" class="text-emerald-400 hover:underline font-mono text-xs block font-semibold">
                            {{ $message->sender_phone_encrypted }}
                        </a>
                    @else
                        <span class="text-slate-500 italic">Not provided</span>
                    @endif
                </div>
            </div>

            <!-- Decrypted Message Payload -->
            <div class="space-y-2">
                <span class="text-xs font-bold text-slate-300 uppercase tracking-wider block">Message:</span>
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800 text-slate-200 text-sm leading-relaxed whitespace-pre-line font-normal">
                    {{ $message->message_encrypted }}
                </div>
            </div>

            <!-- Safety Reminder -->
            <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-300 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div class="leading-relaxed">
                    <strong>Trust & Safety Reminder:</strong> When replying, always meet in public venues. Never send wire transfers, cryptocurrency, gift cards, or advance deposits.
                </div>
            </div>

            <!-- Message Management Actions -->
            <div class="pt-4 border-t border-slate-800 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <!-- Direct Email Reply Button -->
                    <a
                        href="mailto:{{ $message->sender_email_encrypted }}?subject=Re: {{ urlencode($message->subject ?? 'Your Inquiry') }}"
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition shadow-md shadow-rose-600/20 flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        <span>Reply via Email</span>
                    </a>

                    @if(!$message->is_replied)
                        <form action="{{ route('account.messages.replied', $message->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-semibold text-xs border border-slate-700 transition">
                                Mark as Replied
                            </button>
                        </form>
                    @endif
                </div>

                <div class="flex items-center gap-2" x-data="{ flagOpen: false }">
                    <!-- Flag as Spam / Abuse -->
                    @if(!$message->is_flagged)
                        <div class="relative">
                            <button
                                type="button"
                                @click="flagOpen = !flagOpen"
                                class="px-3 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 font-semibold text-xs border border-amber-500/30 transition flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" /></svg>
                                <span>Flag as Spam</span>
                            </button>

                            <div
                                x-show="flagOpen"
                                @click.away="flagOpen = false"
                                x-cloak
                                class="absolute right-0 mt-2 w-56 rounded-xl bg-slate-900 border border-slate-800 p-3 shadow-2xl z-20 space-y-2"
                            >
                                <span class="text-[11px] font-bold text-white block">Select Reason:</span>
                                <form action="{{ route('account.messages.flag', $message->id) }}" method="POST" class="space-y-2">
                                    @csrf
                                    <select name="reason" class="w-full bg-slate-950 border border-slate-700 rounded-lg text-xs text-white p-1.5">
                                        <option value="spam">Commercial Spam</option>
                                        <option value="commercial_solicitation">Prostitution / Escort Solicitation</option>
                                        <option value="harassment">Harassment / Abusive</option>
                                        <option value="inappropriate">Inappropriate / Unwanted</option>
                                        <option value="other">Other Violation</option>
                                    </select>
                                    <button type="submit" class="w-full py-1 text-center bg-amber-600 hover:bg-amber-500 text-white font-bold text-[11px] rounded-lg">
                                        Submit Flag
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <!-- Delete Message -->
                    <form action="{{ route('account.messages.destroy', $message->id) }}" method="POST" onsubmit="return confirm('Permanently delete this inquiry from your inbox?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-rose-950/60 text-slate-400 hover:text-rose-400 font-semibold text-xs border border-slate-700 hover:border-rose-800/40 transition">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

