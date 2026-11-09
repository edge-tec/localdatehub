<x-layouts.app title="My Messages & Inquiries — LocalDateHub">
    <div class="py-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'My Account', 'url' => route('account.profile')],
            ['label' => 'Messages Inbox']
        ]" />

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Messages & Listing Inquiries
                    </h1>
                    @if($unreadCount > 0)
                        <span class="px-2.5 py-0.5 rounded-full bg-rose-500/20 text-rose-600 font-bold text-xs border border-rose-500/30">
                            {{ $unreadCount }} Unread
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    Manage secure, encrypted inquiries sent by other members regarding your active advertisements.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('account.listings.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-800 border border-slate-200 transition">
                    Manage My Ads
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <x-alert type="success" :dismissible="true">
                {{ session('success') }}
            </x-alert>
        @endif

        <!-- Filter Bar -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
            <!-- Tabs -->
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('account.messages.index') }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ !$unreadOnly && !request('replied') ? 'bg-[#F20B83] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:text-slate-900 hover:bg-slate-200' }}"
                >
                    All Messages
                </a>
                <a
                    href="{{ route('account.messages.index', ['unread' => 1]) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 {{ $unreadOnly ? 'bg-[#F20B83] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:text-slate-900 hover:bg-slate-200' }}"
                >
                    <span>Unread</span>
                    @if($unreadCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-[#d90858] text-white font-bold">{{ $unreadCount }}</span>
                    @endif
                </a>
                <a
                    href="{{ route('account.messages.index', ['replied' => 1]) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('replied') ? 'bg-[#F20B83] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:text-slate-900 hover:bg-slate-200' }}"
                >
                    Replied
                </a>
            </div>

            <!-- Filter by Listing -->
            <form method="GET" action="{{ route('account.messages.index') }}" class="flex items-center gap-2 w-full md:w-auto">
                @if($unreadOnly)
                    <input type="hidden" name="unread" value="1">
                @endif
                <label for="listing_id" class="text-xs text-slate-400 whitespace-nowrap">Filter Ad:</label>
                <select
                    id="listing_id"
                    name="listing_id"
                    onchange="this.form.submit()"
                    class="bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-900 focus:ring-1 focus:ring-rose-500 w-full md:w-64"
                >
                    <option value="">All My Classifieds</option>
                    @foreach($userListings as $listing)
                        <option value="{{ $listing->id }}" {{ (string) $selectedListingId === (string) $listing->id ? 'selected' : '' }}>
                            {{ Str::limit($listing->title, 35) }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <!-- Message List -->
        @if($messages->isNotEmpty())
            <div class="bg-white border border-slate-200 rounded-3xl divide-y divide-slate-200/80 overflow-hidden shadow-xl">
                @foreach($messages as $msg)
                    <div class="p-5 sm:p-6 transition hover:bg-slate-850/60 {{ !$msg->is_read ? 'bg-white' : 'bg-white' }} flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1.5 min-w-0 flex-1">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                @if(!$msg->is_read)
                                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-600 border border-rose-500/30 uppercase">Unread</span>
                                @endif

                                @if($msg->is_replied)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-700 border border-emerald-500/30 uppercase">Replied</span>
                                @endif

                                @if($msg->is_flagged)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-700 border border-amber-500/30 uppercase">Flagged</span>
                                @endif

                                <span class="font-bold text-slate-900 text-sm">{{ $msg->sender_name }}</span>
                                <span class="text-xs text-slate-400">&bull; {{ $msg->created_at->diffForHumans() }}</span>
                            </div>

                            @if($msg->listing)
                                <p class="text-xs text-slate-400">
                                    Regarding: <a href="{{ route('listings.show', $msg->listing->slug) }}" class="text-rose-600 hover:underline font-semibold">{{ $msg->listing->title }}</a>
                                    ({{ $msg->listing->city?->name }}, {{ $msg->listing->state?->code }})
                                </p>
                            @endif

                            <p class="text-xs text-slate-600 line-clamp-1 italic">
                                "{{ Str::limit($msg->message_encrypted, 120) }}"
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                            <a
                                href="{{ route('account.messages.show', $msg->id) }}"
                                class="px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition shadow-md shadow-rose-600/20"
                            >
                                Read & Reply
                            </a>

                            <form action="{{ route('account.messages.destroy', $msg->id) }}" method="POST" onsubmit="return confirm('Delete this inquiry from your inbox?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-slate-100 transition" title="Delete message">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
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
                    title="No Inquiries in Your Inbox"
                    description="When members reach out through your personal advertisements, their encrypted messages will safely appear here."
                    actionText="Browse Personals"
                    :actionUrl="route('categories.index')"
                />
            </x-card>
        @endif
    </div>
</x-layouts.app>

