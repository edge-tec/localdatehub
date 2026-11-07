<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
    <!-- Breadcrumbs -->
    <x-breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'User Account Dashboard']
    ]" />

    <!-- Dashboard Header & Top Bar -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-rose-600 to-pink-500 flex items-center justify-center text-white font-extrabold text-2xl shadow-lg shrink-0">
                @if(Auth::user()->profile?->avatar_path)
                    <img src="{{ asset('storage/' . Auth::user()->profile->avatar_path) }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover rounded-2xl">
                @else
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                @endif
            </div>
            <div class="space-y-1">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        {{ Auth::user()->profile?->display_name ?: Auth::user()->name }}
                    </h1>
                    @if(Auth::user()->is_age_verified)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            18+ Verified
                        </span>
                    @else
                        <button wire:click="setTab('age_assurance')" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 hover:bg-amber-500/30 transition">
                            Verify Age (18+)
                        </button>
                    @endif

                    @if($activeSubscription)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                            {{ $activeSubscription->plan?->name ?: 'Member' }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-400">
                    Member since {{ Auth::user()->created_at->format('M Y') }} &bull; Account Status: <strong class="capitalize text-slate-200">{{ Auth::user()->account_status }}</strong>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a
                href="{{ route('listings.create') }}"
                class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs transition shadow-lg shadow-rose-600/20 flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Post Personal Ad</span>
            </a>
            <button
                wire:click="openPromoteModal"
                class="px-4 py-2.5 bg-purple-600/20 hover:bg-purple-600/30 text-purple-300 border border-purple-500/30 font-bold rounded-xl text-xs transition flex items-center gap-1.5"
            >
                ★ Promote Ads
            </button>
        </div>
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <x-alert type="success" :dismissible="true">
            {{ session('success') }}
        </x-alert>
    @endif
    @if(session('error'))
        <x-alert type="error" :dismissible="true">
            {{ session('error') }}
        </x-alert>
    @endif

    <!-- Main Navigation Tabs Bar -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-2 shadow-lg overflow-x-auto scrollbar-none flex items-center gap-1 text-xs font-semibold whitespace-nowrap">
        <button
            wire:click="setTab('overview')"
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ $tab === 'overview' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span>Overview</span>
        </button>

        <button
            wire:click="setTab('listings')"
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ in_array($tab, ['listings', 'active', 'pending', 'rejected', 'expired', 'suspended']) ? 'bg-rose-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            <span>My Listings ({{ $totalListings }})</span>
        </button>

        <button
            wire:click="setTab('saved')"
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ $tab === 'saved' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <span>Saved Listings ({{ $savedListingsCount }})</span>
        </button>

        <button
            wire:click="setTab('messages')"
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ $tab === 'messages' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            <span>Messages</span>
            @if($unreadInquiriesCount > 0)
                <span class="px-1.5 py-0.2 bg-rose-500 text-white rounded-full text-[10px] font-bold">{{ $unreadInquiriesCount }}</span>
            @endif
        </button>

        <button
            wire:click="setTab('analytics')"
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ $tab === 'analytics' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Analytics</span>
        </button>

        <button
            wire:click="setTab('promotions')"
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ $tab === 'promotions' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            <span>Promotions</span>
        </button>

        <button
            wire:click="setTab('subscription')"
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ $tab === 'subscription' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <span>Subscription</span>
        </button>

        <button
            wire:click="setTab('billing')"
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ $tab === 'billing' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/></svg>
            <span>Billing</span>
        </button>

        <button
            wire:click="setTab('profile')"
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ $tab === 'profile' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Profile</span>
        </button>

        <button
            wire:click="setTab('privacy')"
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ $tab === 'privacy' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span>Privacy</span>
        </button>

        <button
            wire:click="setTab('age_assurance')"
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ $tab === 'age_assurance' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span>18+ Assurance</span>
        </button>

        <button
            wire:click="setTab('security')"
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ $tab === 'security' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            <span>Security</span>
        </button>

        <button
            wire:click="setTab('blocked_users')"
            class="px-3.5 py-2 rounded-xl transition flex items-center gap-2 {{ $tab === 'blocked_users' ? 'bg-rose-600 text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <span>Blocked Users ({{ count($blockedUsers) }})</span>
        </button>
    </div>

    <!-- TAB 1: OVERVIEW SECTION -->
    @if($tab === 'overview')
        <div class="space-y-8">
            <!-- Key Metric Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl shadow-lg">
                    <span class="text-xs text-slate-400 block font-medium">Active Ads</span>
                    <span class="text-2xl font-extrabold text-emerald-400 mt-1 block">{{ $activeListingsCount }}</span>
                    <button wire:click="setTab('listings'); $set('listingStatus', 'active')" class="text-[11px] text-rose-400 hover:underline mt-2 inline-block">View Active &rarr;</button>
                </div>

                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl shadow-lg">
                    <span class="text-xs text-slate-400 block font-medium">Pending Review</span>
                    <span class="text-2xl font-extrabold text-amber-300 mt-1 block">{{ $pendingListingsCount }}</span>
                    <button wire:click="setTab('listings'); $set('listingStatus', 'pending_review')" class="text-[11px] text-amber-400 hover:underline mt-2 inline-block">Under Review &rarr;</button>
                </div>

                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl shadow-lg">
                    <span class="text-xs text-slate-400 block font-medium">Needs Renewal</span>
                    <span class="text-2xl font-extrabold text-rose-400 mt-1 block">{{ $expiredListingsCount }}</span>
                    <button wire:click="setTab('listings'); $set('listingStatus', 'expired')" class="text-[11px] text-rose-400 hover:underline mt-2 inline-block">Renew Now &rarr;</button>
                </div>

                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl shadow-lg">
                    <span class="text-xs text-slate-400 block font-medium">Unread Messages</span>
                    <span class="text-2xl font-extrabold text-white mt-1 block">{{ $unreadInquiriesCount }}</span>
                    <button wire:click="setTab('messages')" class="text-[11px] text-rose-400 hover:underline mt-2 inline-block">Open Inbox &rarr;</button>
                </div>

                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl shadow-lg">
                    <span class="text-xs text-slate-400 block font-medium">Total Ad Views</span>
                    <span class="text-2xl font-extrabold text-purple-400 mt-1 block">{{ number_format($totalViews) }}</span>
                    <button wire:click="setTab('analytics')" class="text-[11px] text-purple-300 hover:underline mt-2 inline-block">Stats Breakdown &rarr;</button>
                </div>

                <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl shadow-lg">
                    <span class="text-xs text-slate-400 block font-medium">Favorites Earned</span>
                    <span class="text-2xl font-extrabold text-pink-400 mt-1 block">{{ $totalFavorites }}</span>
                    <span class="text-[11px] text-slate-500 mt-2 block">{{ $totalReplies }} Inquiries</span>
                </div>
            </div>

            <!-- Recent Listings & Inquiries Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Classifieds Card -->
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>My Recent Classifieds</span>
                            <span class="text-xs text-slate-500 font-normal">({{ $recentListings->count() }})</span>
                        </h2>
                        <button wire:click="setTab('listings')" class="text-xs text-rose-400 hover:underline font-semibold">View All &rarr;</button>
                    </div>

                    @if($recentListings->isNotEmpty())
                        <div class="divide-y divide-slate-800">
                            @foreach($recentListings as $ad)
                                <div class="py-3 flex items-center justify-between gap-3 text-xs">
                                    <div class="min-w-0">
                                        <a href="{{ route('listings.show', $ad->slug) }}" class="font-bold text-white hover:text-rose-400 truncate block">
                                            {{ $ad->title }}
                                        </a>
                                        <span class="text-slate-400 text-[11px]">
                                            {{ $ad->category?->name }} &bull; {{ $ad->city?->name }}, {{ $ad->city?->state?->code }} &bull; {{ $ad->view_count }} Views
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                            @if($ad->status === 'active') bg-emerald-500/20 text-emerald-400 border border-emerald-500/30
                                            @elseif($ad->status === 'pending_review') bg-amber-500/20 text-amber-300 border border-amber-500/30
                                            @elseif($ad->status === 'rejected') bg-rose-500/20 text-rose-400 border border-rose-500/30
                                            @else bg-slate-800 text-slate-400 @endif
                                        ">
                                            {{ str_replace('_', ' ', $ad->status) }}
                                        </span>
                                        <a href="{{ route('listings.edit', $ad->id) }}" class="text-slate-400 hover:text-white p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-xs text-slate-500 space-y-3">
                            <p>You haven't posted any classified advertisements yet.</p>
                            <a href="{{ route('listings.create') }}" class="px-4 py-2 bg-rose-600 text-white rounded-xl inline-block font-semibold">Post First Ad</a>
                        </div>
                    @endif
                </div>

                <!-- Recent Messages Card -->
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>Recent Inquiries</span>
                            @if($unreadInquiriesCount > 0)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400">{{ $unreadInquiriesCount }} new</span>
                            @endif
                        </h2>
                        <button wire:click="setTab('messages')" class="text-xs text-rose-400 hover:underline font-semibold">Inbox &rarr;</button>
                    </div>

                    @if($recentInquiries->isNotEmpty())
                        <div class="divide-y divide-slate-800">
                            @foreach($recentInquiries as $msg)
                                <div class="py-3 flex items-center justify-between gap-3 text-xs">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            @if(!$msg->is_read)
                                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            @endif
                                            <span class="font-bold text-white">{{ $msg->sender_name }}</span>
                                            <span class="text-slate-500 text-[11px]">&bull; {{ $msg->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-slate-400 text-[11px] truncate mt-0.5">
                                            Re: {{ $msg->listing?->title ?? 'General Inquiry' }}
                                        </p>
                                    </div>
                                    <button
                                        wire:click="inspectMessage({{ $msg->id }})"
                                        class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-[11px] font-semibold text-rose-400 shrink-0"
                                    >
                                        Read
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-xs text-slate-500">
                            No inquiries received yet. When members contact you, messages will safely appear here.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 2: MY LISTINGS (Covers Sections 2, 4, 5, 6, 7, 8) -->
    @if(in_array($tab, ['listings', 'active', 'pending', 'rejected', 'expired', 'suspended']))
        <div class="space-y-6">
            <!-- Filter & Search Controls -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Status Filter Tabs -->
                    <div class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
                        <button
                            wire:click="$set('listingStatus', 'all')"
                            class="px-3 py-1.5 rounded-lg transition {{ $listingStatus === 'all' ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-300 hover:text-white' }}"
                        >
                            All ({{ $totalListings }})
                        </button>
                        <button
                            wire:click="$set('listingStatus', 'active')"
                            class="px-3 py-1.5 rounded-lg transition {{ $listingStatus === 'active' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-300 hover:text-white' }}"
                        >
                            Active ({{ $activeListingsCount }})
                        </button>
                        <button
                            wire:click="$set('listingStatus', 'pending_review')"
                            class="px-3 py-1.5 rounded-lg transition {{ $listingStatus === 'pending_review' ? 'bg-amber-600 text-white' : 'bg-slate-800 text-slate-300 hover:text-white' }}"
                        >
                            Pending Review ({{ $pendingListingsCount }})
                        </button>
                        <button
                            wire:click="$set('listingStatus', 'rejected')"
                            class="px-3 py-1.5 rounded-lg transition {{ $listingStatus === 'rejected' ? 'bg-rose-700 text-white' : 'bg-slate-800 text-slate-300 hover:text-white' }}"
                        >
                            Rejected ({{ $rejectedListingsCount }})
                        </button>
                        <button
                            wire:click="$set('listingStatus', 'expired')"
                            class="px-3 py-1.5 rounded-lg transition {{ $listingStatus === 'expired' ? 'bg-purple-600 text-white' : 'bg-slate-800 text-slate-300 hover:text-white' }}"
                        >
                            Expired ({{ $expiredListingsCount }})
                        </button>
                        <button
                            wire:click="$set('listingStatus', 'suspended')"
                            class="px-3 py-1.5 rounded-lg transition {{ $listingStatus === 'suspended' ? 'bg-red-800 text-white' : 'bg-slate-800 text-slate-300 hover:text-white' }}"
                        >
                            Suspended ({{ $suspendedListingsCount }})
                        </button>
                    </div>

                    <a href="{{ route('listings.create') }}" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs transition self-start md:self-auto shrink-0">
                        + New Ad
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-slate-800">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="listingSearch"
                        placeholder="Search your listings by title..."
                        class="bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-rose-500"
                    >

                    <select
                        wire:model.live="listingCategory"
                        class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500"
                    >
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>

                    <select
                        wire:model.live="listingSort"
                        class="bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500"
                    >
                        <option value="newest">Sort: Newest First</option>
                        <option value="views">Sort: Most Views</option>
                        <option value="expires">Sort: Expiring Soonest</option>
                    </select>
                </div>

                <!-- Bulk Actions Toolbar -->
                @if(!empty($selectedListings))
                    <div class="p-3 bg-slate-950 border border-rose-500/30 rounded-xl flex items-center justify-between text-xs animate-fadeIn">
                        <span class="font-semibold text-rose-300">{{ count($selectedListings) }} listings selected</span>
                        <div class="flex items-center gap-2">
                            <button wire:click="bulkResumeListings" class="px-2.5 py-1 bg-emerald-600/20 text-emerald-300 border border-emerald-500/30 rounded-lg hover:bg-emerald-600/30">Activate</button>
                            <button wire:click="bulkPauseListings" class="px-2.5 py-1 bg-slate-800 text-slate-300 rounded-lg hover:bg-slate-700">Pause</button>
                            <button wire:click="bulkRenewListings" class="px-2.5 py-1 bg-purple-600/20 text-purple-300 border border-purple-500/30 rounded-lg hover:bg-purple-600/30">Renew (+30d)</button>
                            <button wire:click="bulkDeleteListings" wire:confirm="Are you sure you want to delete all selected listings?" class="px-2.5 py-1 bg-rose-600/20 text-rose-300 border border-rose-500/30 rounded-lg hover:bg-rose-600/30">Delete</button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Listings Stream -->
            @if($myListings->isNotEmpty())
                <div class="bg-slate-900 border border-slate-800 rounded-3xl divide-y divide-slate-800 overflow-hidden shadow-xl">
                    @foreach($myListings as $listing)
                        <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-850/50 transition">
                            <div class="flex items-start gap-4 min-w-0">
                                <input
                                    type="checkbox"
                                    value="{{ $listing->id }}"
                                    wire:model.live="selectedListings"
                                    class="mt-1 rounded bg-slate-950 border-slate-700 text-rose-600 focus:ring-rose-500"
                                >
                                <div class="space-y-1.5 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                            @if($listing->status === 'active') bg-emerald-500/20 text-emerald-400 border border-emerald-500/30
                                            @elseif($listing->status === 'pending_review') bg-amber-500/20 text-amber-300 border border-amber-500/30
                                            @elseif($listing->status === 'rejected') bg-rose-500/20 text-rose-400 border border-rose-500/30
                                            @elseif($listing->status === 'expired') bg-purple-500/20 text-purple-300 border border-purple-500/30
                                            @elseif($listing->status === 'suspended') bg-red-800 text-white
                                            @else bg-slate-800 text-slate-400 @endif
                                        ">
                                            {{ str_replace('_', ' ', $listing->status) }}
                                        </span>
                                        @if($listing->is_featured)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                ★ Featured
                                            </span>
                                        @endif
                                        <span class="text-xs text-slate-400">{{ $listing->category?->name }} &bull; {{ $listing->city?->name }}, {{ $listing->city?->state?->code }}</span>
                                    </div>

                                    <h3 class="text-sm font-bold text-white truncate">
                                        <a href="{{ route('listings.show', $listing->slug) }}" class="hover:text-rose-400 transition">{{ $listing->title }}</a>
                                    </h3>

                                    <!-- Rejection or Suspension Details Notice -->
                                    @if($listing->status === 'rejected' && $listing->rejection_reason)
                                        <div class="p-2.5 rounded-xl bg-rose-950/40 border border-rose-600/40 text-[11px] text-rose-200">
                                            <strong>Moderation Notice:</strong> {{ $listing->rejection_reason }}
                                        </div>
                                    @endif

                                    <div class="flex items-center gap-3 text-[11px] text-slate-400">
                                        <span>Views: <strong class="text-white">{{ $listing->view_count }}</strong></span>
                                        <span>&bull;</span>
                                        <span>Replies: <strong class="text-white">{{ $listing->reply_count }}</strong></span>
                                        <span>&bull;</span>
                                        <span>Favorites: <strong class="text-white">{{ $listing->favorite_count }}</strong></span>
                                        <span>&bull;</span>
                                        <span>Expires: {{ $listing->expires_at?->format('M d, Y') ?? 'Never' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                                @if($listing->status === 'active')
                                    <button wire:click="pauseListing({{ $listing->id }})" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 font-semibold transition">
                                        Pause
                                    </button>
                                @elseif($listing->status === 'paused')
                                    <button wire:click="resumeListing({{ $listing->id }})" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-xs text-white font-semibold transition">
                                        Resume
                                    </button>
                                @endif

                                @if($listing->status === 'expired')
                                    <button wire:click="renewListing({{ $listing->id }})" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-xs text-white font-semibold transition">
                                        Renew Ad
                                    </button>
                                @endif

                                @if(!$listing->is_featured && $listing->status === 'active')
                                    <button wire:click="openPromoteModal({{ $listing->id }})" class="px-3 py-1.5 rounded-lg bg-purple-600/20 hover:bg-purple-600/30 text-purple-300 border border-purple-500/30 text-xs font-semibold transition">
                                        Promote
                                    </button>
                                @endif

                                <a href="{{ route('listings.edit', $listing->id) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 font-semibold transition">
                                    Edit
                                </a>

                                <button
                                    wire:click="confirmDeleteListing({{ $listing->id }})"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition"
                                    title="Delete ad"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4">
                    {{ $myListings->links() }}
                </div>
            @else
                <x-card class="text-center py-12">
                    <x-empty-state
                        title="No Listings Found"
                        description="There are currently no classified listings matching your active filters."
                        actionText="Create a Classified Ad"
                        :actionUrl="route('listings.create')"
                    />
                </x-card>
            @endif
        </div>
    @endif

    <!-- TAB 3: SAVED LISTINGS (Section 9) -->
    @if($tab === 'saved')
        <div class="space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-white">Saved Personal Listings</h2>
                    <p class="text-xs text-slate-400 mt-1">Classified ads you have bookmarked for quick reference.</p>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="savedSearch"
                    placeholder="Search saved ads..."
                    class="bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-rose-500 w-full sm:w-64"
                >
            </div>

            @if($savedListings->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($savedListings as $listing)
                        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-xl flex flex-col justify-between">
                            <div class="p-5 space-y-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400 font-bold text-[10px]">
                                        {{ $listing->category?->name }}
                                    </span>
                                    <span class="text-slate-400 text-[11px]">{{ $listing->city?->name }}, {{ $listing->city?->state?->code }}</span>
                                </div>
                                <h3 class="text-sm font-bold text-white leading-snug line-clamp-2">
                                    <a href="{{ route('listings.show', $listing->slug) }}" class="hover:text-rose-400 transition">{{ $listing->title }}</a>
                                </h3>
                                <p class="text-xs text-slate-400 line-clamp-2">
                                    {{ $listing->description }}
                                </p>
                            </div>
                            <div class="p-4 bg-slate-950/60 border-t border-slate-800 flex items-center justify-between text-xs">
                                <a href="{{ route('listings.show', $listing->slug) }}" class="font-semibold text-rose-400 hover:underline">
                                    View Ad &rarr;
                                </a>
                                <button
                                    wire:click="removeFavorite({{ $listing->id }})"
                                    class="text-xs text-slate-400 hover:text-rose-400 font-medium"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4">
                    {{ $savedListings->links() }}
                </div>
            @else
                <x-card class="text-center py-12">
                    <x-empty-state
                        title="No Saved Listings"
                        description="You haven't bookmarked any advertisements yet. Click the heart icon on any listing to save it here."
                        actionText="Explore Classifieds"
                        :actionUrl="route('categories.index')"
                    />
                </x-card>
            @endif
        </div>
    @endif

    <!-- TAB 4: CONTACT MESSAGES (Section 10) -->
    @if($tab === 'messages')
        <div class="space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <button wire:click="$set('messageFilter', 'all')" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $messageFilter === 'all' ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-300' }}">All</button>
                    <button wire:click="$set('messageFilter', 'unread')" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $messageFilter === 'unread' ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-300' }}">Unread</button>
                    <button wire:click="$set('messageFilter', 'replied')" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $messageFilter === 'replied' ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-300' }}">Replied</button>
                    <button wire:click="$set('messageFilter', 'flagged')" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $messageFilter === 'flagged' ? 'bg-rose-600 text-white' : 'bg-slate-800 text-slate-300' }}">Flagged</button>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="messageSearch"
                    placeholder="Search messages by sender or subject..."
                    class="bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-rose-500 w-full md:w-72"
                >
            </div>

            @if($messages->isNotEmpty())
                <div class="bg-slate-900 border border-slate-800 rounded-3xl divide-y divide-slate-800 overflow-hidden shadow-xl">
                    @foreach($messages as $msg)
                        <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-850/50 transition {{ !$msg->is_read ? 'bg-slate-900/90' : 'bg-slate-900/40' }}">
                            <div class="space-y-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if(!$msg->is_read)
                                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-400">UNREAD</span>
                                    @endif
                                    @if($msg->is_replied)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400">REPLIED</span>
                                    @endif
                                    <span class="font-bold text-white text-xs">{{ $msg->sender_name }}</span>
                                    <span class="text-slate-400 text-[11px]">&bull; {{ $msg->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-xs text-slate-300 truncate">
                                    Re: {{ $msg->listing?->title ?? 'Classified Inquiry' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button wire:click="inspectMessage({{ $msg->id }})" class="px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition">
                                    Read Inquiry
                                </button>
                                <button wire:click="deleteMessage({{ $msg->id }})" wire:confirm="Delete this message?" class="p-1.5 text-slate-400 hover:text-rose-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4">
                    {{ $messages->links() }}
                </div>
            @else
                <x-card class="text-center py-12">
                    <x-empty-state
                        title="No Messages"
                        description="There are no inquiries matching your active message filter."
                    />
                </x-card>
            @endif
        </div>
    @endif

    <!-- TAB 5: ANALYTICS & METRICS (Section 11) -->
    @if($tab === 'analytics')
        <div class="space-y-8">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white">Listing Performance & Analytics</h2>
                    <p class="text-xs text-slate-400 mt-1">Aggregate views, inquiries, and engagement across your personal advertisements.</p>
                </div>
            </div>

            <!-- Aggregated KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-lg">
                    <span class="text-xs text-slate-400 uppercase font-semibold">Total Ad Impressions</span>
                    <span class="text-3xl font-extrabold text-white block mt-2">{{ number_format($totalViews) }}</span>
                </div>
                <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-lg">
                    <span class="text-xs text-slate-400 uppercase font-semibold">Total Inquiries Received</span>
                    <span class="text-3xl font-extrabold text-emerald-400 block mt-2">{{ number_format($totalReplies) }}</span>
                </div>
                <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-lg">
                    <span class="text-xs text-slate-400 uppercase font-semibold">Favorites Saved</span>
                    <span class="text-3xl font-extrabold text-pink-400 block mt-2">{{ number_format($totalFavorites) }}</span>
                </div>
                <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl shadow-lg">
                    <span class="text-xs text-slate-400 uppercase font-semibold">Inquiry Conversion</span>
                    <span class="text-3xl font-extrabold text-purple-400 block mt-2">
                        {{ $totalViews > 0 ? round(($totalReplies / $totalViews) * 100, 1) : 0 }}%
                    </span>
                </div>
            </div>

            <!-- Per-Listing Breakdown -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <h3 class="text-base font-bold text-white">Performance by Classified Advertisement</h3>
                @if($analyticsListings->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-300">
                            <thead class="bg-slate-950 text-slate-400 uppercase text-[10px] font-semibold border-b border-slate-800">
                                <tr>
                                    <th class="p-3">Listing Title</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3 text-right">Views</th>
                                    <th class="p-3 text-right">Replies</th>
                                    <th class="p-3 text-right">Favorites</th>
                                    <th class="p-3 text-right">Rate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                @foreach($analyticsListings as $ad)
                                    <tr class="hover:bg-slate-850/50">
                                        <td class="p-3 font-bold text-white">{{ Str::limit($ad->title, 40) }}</td>
                                        <td class="p-3 capitalize">{{ $ad->status }}</td>
                                        <td class="p-3 text-right font-mono">{{ $ad->view_count }}</td>
                                        <td class="p-3 text-right font-mono">{{ $ad->reply_count }}</td>
                                        <td class="p-3 text-right font-mono">{{ $ad->favorite_count }}</td>
                                        <td class="p-3 text-right font-mono font-bold text-emerald-400">
                                            {{ $ad->view_count > 0 ? round(($ad->reply_count / $ad->view_count) * 100, 1) : 0 }}%
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-xs text-slate-500 py-6 text-center">No active listings available for analytics.</p>
                @endif
            </div>
        </div>
    @endif

    <!-- TAB 6: PROMOTIONS & FEATURED LISTINGS (Section 12) -->
    @if($tab === 'promotions')
        <div class="space-y-8">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-white">Featured Promotions & Upgrades</h2>
                    <p class="text-xs text-slate-400 mt-1">Boost ad prominence with pinned top-of-category and city spotlights.</p>
                </div>
                <button wire:click="openPromoteModal" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl text-xs transition">
                    ★ Promote Active Ad
                </button>
            </div>

            <!-- Active Promotions Table -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <h3 class="text-base font-bold text-white">Your Active & Historical Promotions</h3>
                @if($activePromotions->isNotEmpty())
                    <div class="divide-y divide-slate-800">
                        @foreach($activePromotions as $promo)
                            <div class="py-3 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold text-white block">{{ $promo->listing?->title }}</span>
                                    <span class="text-slate-400 text-[11px]">
                                        Placement: <strong class="capitalize">{{ str_replace('_', ' ', $promo->placement) }}</strong> &bull;
                                        Expires: {{ $promo->ends_at->format('M d, Y') }}
                                    </span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $promo->is_active ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-800 text-slate-500' }}">
                                    {{ $promo->is_active ? 'Active Spotlight' : 'Ended' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-500 py-4 text-center">You have no active featured promotions.</p>
                @endif
            </div>

            <!-- Available Promotion Packages -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($pricingPlans as $plan)
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 uppercase">
                                {{ $plan->plan_type }}
                            </span>
                            <h4 class="text-lg font-bold text-white">{{ $plan->name }}</h4>
                            <div class="text-2xl font-extrabold text-white">
                                ${{ number_format($plan->price_cents / 100, 2) }}
                                <span class="text-xs font-normal text-slate-400">/ {{ $plan->duration_days }} days</span>
                            </div>
                            <p class="text-xs text-slate-400">{{ $plan->description }}</p>
                        </div>

                        <button
                            wire:click="openPromoteModal"
                            class="w-full py-2 bg-slate-800 hover:bg-rose-600 text-white font-semibold text-xs rounded-xl transition"
                        >
                            Select Upgrade
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- TAB 7: SUBSCRIPTION (Section 13) -->
    @if($tab === 'subscription')
        <div class="max-w-4xl space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
                <h2 class="text-xl font-bold text-white">Membership & Subscription Plan</h2>

                @if($activeSubscription)
                    <div class="p-6 rounded-2xl bg-purple-950/30 border border-purple-500/40 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-purple-400 tracking-wider">Current Active Plan</span>
                                <h3 class="text-2xl font-black text-white mt-0.5">{{ $activeSubscription->plan?->name }}</h3>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase">
                                Active
                            </span>
                        </div>
                        <div class="text-xs text-slate-300 space-y-1">
                            <p>Starts: {{ $activeSubscription->starts_at->format('M d, Y') }}</p>
                            <p>Renews / Expires: {{ $activeSubscription->ends_at?->format('M d, Y') ?? 'Auto-renewing' }}</p>
                            <p>Gateway: <strong class="uppercase font-mono">{{ $activeSubscription->gateway }}</strong></p>
                        </div>
                        <div class="pt-2">
                            <button
                                wire:click="cancelSubscription({{ $activeSubscription->id }})"
                                wire:confirm="Are you sure you want to cancel your subscription? Benefits will expire at the end of the current cycle."
                                class="px-4 py-2 bg-rose-600/20 text-rose-300 border border-rose-500/30 hover:bg-rose-600/30 text-xs font-bold rounded-xl transition"
                            >
                                Cancel Subscription
                            </button>
                        </div>
                    </div>
                @else
                    <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800 text-center space-y-4">
                        <h3 class="text-base font-bold text-white">Standard Free Membership</h3>
                        <p class="text-xs text-slate-400 max-w-md mx-auto">
                            You are currently on the free standard tier. Upgrade to Verified Plus for trusted age assurance badges and included monthly featured ad bumps.
                        </p>
                        @foreach($pricingPlans->where('plan_type', 'membership') as $membershipPlan)
                            <button
                                wire:click="subscribeToPlan({{ $membershipPlan->id }})"
                                class="px-6 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs transition"
                            >
                                Upgrade to {{ $membershipPlan->name }} (${{ number_format($membershipPlan->price_cents / 100, 2) }}/mo)
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- TAB 8: BILLING HISTORY (Section 14) -->
    @if($tab === 'billing')
        <div class="space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white">Billing & Payment History</h2>
                    <p class="text-xs text-slate-400 mt-1">Itemized ledger of all promotional ad placements and subscription receipts.</p>
                </div>
            </div>

            @if($billingTransactions->isNotEmpty())
                <div class="bg-slate-900 border border-slate-800 rounded-3xl divide-y divide-slate-800 overflow-hidden shadow-xl">
                    <div class="p-4 bg-slate-950 grid grid-cols-4 sm:grid-cols-5 text-slate-400 uppercase text-[10px] font-semibold">
                        <span>Date / Ref</span>
                        <span>Item / Description</span>
                        <span class="text-right">Amount</span>
                        <span class="text-center">Status</span>
                        <span class="text-right hidden sm:block">Method</span>
                    </div>
                    @foreach($billingTransactions as $txn)
                        <div class="p-4 grid grid-cols-4 sm:grid-cols-5 items-center text-xs text-slate-300">
                            <div>
                                <span class="font-bold text-white block">{{ $txn->created_at->format('M d, Y') }}</span>
                                <span class="font-mono text-[10px] text-slate-500">{{ $txn->reference_id }}</span>
                            </div>
                            <div class="truncate">
                                <span class="font-semibold text-slate-200">{{ $txn->plan?->name ?: 'Listing Upgrade' }}</span>
                                @if($txn->listing)
                                    <span class="block text-[11px] text-slate-500 truncate">{{ $txn->listing->title }}</span>
                                @endif
                            </div>
                            <div class="text-right font-mono font-bold text-white">
                                ${{ number_format($txn->amount_cents / 100, 2) }}
                            </div>
                            <div class="text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                    @if($txn->status === 'completed') bg-emerald-500/20 text-emerald-400 border border-emerald-500/30
                                    @elseif($txn->status === 'pending') bg-amber-500/20 text-amber-300 border border-amber-500/30
                                    @else bg-rose-500/20 text-rose-400 @endif
                                ">
                                    {{ $txn->status }}
                                </span>
                            </div>
                            <div class="text-right uppercase font-mono text-[10px] text-slate-400 hidden sm:block">
                                {{ $txn->payment_method }} ({{ $txn->gateway }})
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="pt-4">
                    {{ $billingTransactions->links() }}
                </div>
            @else
                <x-card class="text-center py-12">
                    <x-empty-state
                        title="No Billing Transactions"
                        description="You haven't made any purchases or promotion upgrades yet."
                    />
                </x-card>
            @endif
        </div>
    @endif

    <!-- TAB 9: PROFILE SETTINGS (Section 15) -->
    @if($tab === 'profile')
        <div class="max-w-3xl space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
                <h2 class="text-xl font-bold text-white">Public Profile Settings</h2>

                <form wire:submit="saveProfile" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                                Display Name / Handle <span class="text-rose-400">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="displayName"
                                required
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500"
                            >
                            @error('displayName') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                                Gender Identity
                            </label>
                            <input
                                type="text"
                                wire:model="gender"
                                placeholder="e.g. Female, Male, Non-binary"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                            Seeking / Match Intent
                        </label>
                        <input
                            type="text"
                            wire:model="seeking"
                            placeholder="e.g. Men for dating, Activity partner, Casual coffee"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                            Bio & About You
                        </label>
                        <textarea
                            wire:model="bio"
                            rows="4"
                            placeholder="Share a little bit about who you are..."
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">City</label>
                            <input type="text" wire:model="city" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">State Code</label>
                            <input type="text" wire:model="stateCode" placeholder="CA, NY, TX..." class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">ZIP Code</label>
                            <input type="text" wire:model="zipCode" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs transition">
                            Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- TAB 10: PRIVACY SETTINGS (Section 16) -->
    @if($tab === 'privacy')
        <div class="max-w-3xl space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
                <div>
                    <h2 class="text-xl font-bold text-white">Privacy & Geolocation Controls</h2>
                    <p class="text-xs text-slate-400 mt-1">Configure your location visibility and incoming communication filters.</p>
                </div>

                <form wire:submit="savePrivacySettings" class="space-y-5">
                    <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <span class="text-xs font-bold text-white block">Anti-Triangulation Location Fuzzing</span>
                            <span class="text-[11px] text-slate-400 block">Hide exact neighborhood and apply 300–800m GPS jitter to all your ads.</span>
                        </div>
                        <input type="checkbox" wire:model="hideLocationDetails" class="rounded bg-slate-900 border-slate-700 text-rose-600 focus:ring-rose-500 w-5 h-5">
                    </div>

                    <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <span class="text-xs font-bold text-white block">Hide Exact Age</span>
                            <span class="text-[11px] text-slate-400 block">Show only the "18+ Verified" badge without displaying your numerical birth year.</span>
                        </div>
                        <input type="checkbox" wire:model="hideAge" class="rounded bg-slate-900 border-slate-700 text-rose-600 focus:ring-rose-500 w-5 h-5">
                    </div>

                    <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <span class="text-xs font-bold text-white block">Allow Platform Contact Messages</span>
                            <span class="text-[11px] text-slate-400 block">Enable the protected encrypted contact form on all your listings.</span>
                        </div>
                        <input type="checkbox" wire:model="allowContactMessages" class="rounded bg-slate-900 border-slate-700 text-rose-600 focus:ring-rose-500 w-5 h-5">
                    </div>

                    <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <span class="text-xs font-bold text-white block">Require 18+ Age Verified Senders</span>
                            <span class="text-[11px] text-slate-400 block">Only accept contact inquiries from members who have completed age assurance.</span>
                        </div>
                        <input type="checkbox" wire:model="requireVerifiedSenders" class="rounded bg-slate-900 border-slate-700 text-rose-600 focus:ring-rose-500 w-5 h-5">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs transition">
                            Save Privacy Preferences
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- TAB 11: AGE ASSURANCE STATUS (Section 17) -->
    @if($tab === 'age_assurance')
        <div class="max-w-3xl space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
                <div>
                    <h2 class="text-xl font-bold text-white">18+ Adult Age Assurance & Compliance</h2>
                    <p class="text-xs text-slate-400 mt-1">LocalDateHub complies strictly with state and federal legal age verification mandates.</p>
                </div>

                @if(Auth::user()->is_age_verified)
                    <div class="p-5 rounded-2xl bg-emerald-950/40 border border-emerald-500/40 space-y-2 text-xs">
                        <div class="flex items-center gap-2 text-emerald-400 font-bold text-sm">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span>18+ Age Assurance Active</span>
                        </div>
                        <p class="text-slate-300">Your account is fully verified. Your personal advertisements feature the 18+ Age Verified trust badge.</p>
                    </div>
                @else
                    <form wire:submit="submitAgeAssurance" class="space-y-4">
                        <div class="p-4 bg-amber-500/10 border border-amber-500/30 rounded-2xl text-xs text-amber-200 space-y-1">
                            <strong class="font-bold text-amber-300">Legal Notice:</strong>
                            <p>You must be at least 18 years of age. All personal classified advertisements are moderated for adult legal compliance.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                                Your Date of Birth <span class="text-rose-400">*</span>
                            </label>
                            <input
                                type="date"
                                wire:model="declaredBirthdate"
                                required
                                class="bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500"
                            >
                            @error('declaredBirthdate') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-start gap-3 pt-2">
                            <input
                                type="checkbox"
                                wire:model="ageAffirmation"
                                id="ageAffirmation"
                                required
                                class="mt-0.5 rounded bg-slate-950 border-slate-700 text-rose-600 focus:ring-rose-500"
                            >
                            <label for="ageAffirmation" class="text-xs text-slate-300">
                                I solemnly affirm under penalty of perjury that I am at least 18 years old and legally permitted to access adult personal connections in my jurisdiction.
                            </label>
                        </div>
                        @error('ageAffirmation') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror

                        <div class="pt-2">
                            <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs transition">
                                Affirm Adult Age & Complete Verification
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <!-- TAB 12: SECURITY SETTINGS (Section 18) -->
    @if($tab === 'security')
        <div class="max-w-3xl space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
                <h2 class="text-xl font-bold text-white">Password & Account Security</h2>

                <form wire:submit="updatePassword" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Current Password</label>
                        <input type="password" wire:model="currentPassword" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500">
                        @error('currentPassword') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">New Password</label>
                            <input type="password" wire:model="newPassword" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500">
                            @error('newPassword') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Confirm New Password</label>
                            <input type="password" wire:model="newPasswordConfirmation" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs transition">
                            Update Password
                        </button>
                    </div>
                </form>

                <div class="pt-6 border-t border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-white">Two-Factor Authentication (2FA)</h3>
                            <p class="text-xs text-slate-400">TOTP Authenticator app protection (Google Authenticator, Authy).</p>
                        </div>
                        <a href="{{ route('account.security') }}" class="px-3.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-200 font-semibold">
                            Configure 2FA &rarr;
                        </a>
                    </div>
                </div>

                <!-- Danger Zone: Account Deletion (Section 20) -->
                <div class="pt-6 border-t border-rose-900/40 space-y-3">
                    <h3 class="text-sm font-bold text-rose-400 uppercase tracking-wider">Danger Zone</h3>
                    <p class="text-xs text-slate-400">Permanently delete your account and purge all active advertisements according to CCPA and GDPR regulations.</p>
                    <button
                        wire:click="confirmAccountDeletion"
                        class="px-4 py-2 bg-rose-600/20 text-rose-300 border border-rose-500/30 hover:bg-rose-600/30 font-bold rounded-xl text-xs transition"
                    >
                        Delete My Account
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 13: BLOCKED USERS (Section 19) -->
    @if($tab === 'blocked_users')
        <div class="max-w-4xl space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-6">
                <div>
                    <h2 class="text-xl font-bold text-white">Blocked Users</h2>
                    <p class="text-xs text-slate-400 mt-1">Blocked accounts cannot view your personal contact handles or submit inquiries to your classified ads.</p>
                </div>

                <!-- Add Block Form -->
                <form wire:submit="blockUser" class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-3">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Block a Member</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <input
                                type="text"
                                wire:model="blockUsername"
                                placeholder="Member username or email..."
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500"
                            >
                            @error('blockUsername') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <input
                                type="text"
                                wire:model="blockReason"
                                placeholder="Reason (e.g. Harassment, Spam)..."
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500"
                            >
                        </div>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-rose-600 text-white font-semibold text-xs rounded-xl transition">
                        Block User
                    </button>
                </form>

                <!-- Blocked List -->
                @if(count($blockedUsers) > 0)
                    <div class="divide-y divide-slate-800">
                        @foreach($blockedUsers as $block)
                            <div class="py-3 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold text-white block">{{ $block->blocked?->name ?? 'User #' . $block->blocked_id }}</span>
                                    <span class="text-slate-500 text-[11px]">Blocked on {{ $block->created_at?->format('M d, Y') }} &bull; {{ $block->reason }}</span>
                                </div>
                                <button
                                    wire:click="unblockUser({{ $block->id }})"
                                    class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 font-semibold"
                                >
                                    Unblock
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-500 py-4 text-center">Your blocked users list is currently empty.</p>
                @endif
            </div>
        </div>
    @endif

    <!-- MODAL 1: INSPECT CONTACT MESSAGE INQUIRY -->
    @if($showMessageModal && $inspectedMessage)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fadeIn">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h3 class="text-base font-bold text-white">Inquiry from {{ $inspectedMessage->sender_name }}</h3>
                        <span class="text-xs text-slate-400">{{ $inspectedMessage->created_at->format('M d, Y @ h:i A') }}</span>
                    </div>
                    <button wire:click="closeMessageModal" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
                </div>

                @if($inspectedMessage->listing)
                    <div class="p-3 bg-slate-950 rounded-xl text-xs space-y-0.5">
                        <span class="text-slate-400 block text-[11px]">Regarding Your Ad:</span>
                        <a href="{{ route('listings.show', $inspectedMessage->listing->slug) }}" target="_blank" class="text-rose-400 hover:underline font-bold block">
                            {{ $inspectedMessage->listing->title }}
                        </a>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 bg-slate-950 rounded-xl">
                        <span class="text-slate-400 block text-[10px]">Return Email:</span>
                        <a href="mailto:{{ $inspectedMessage->sender_email_encrypted }}" class="text-rose-400 font-mono font-bold hover:underline break-all">
                            {{ $inspectedMessage->sender_email_encrypted }}
                        </a>
                    </div>
                    <div class="p-3 bg-slate-950 rounded-xl">
                        <span class="text-slate-400 block text-[10px]">Phone Number:</span>
                        <span class="font-mono text-slate-200">{{ $inspectedMessage->sender_phone_encrypted ?: 'None' }}</span>
                    </div>
                </div>

                <div class="p-4 bg-slate-950 rounded-xl text-xs text-slate-200 whitespace-pre-line leading-relaxed max-h-60 overflow-y-auto">
                    {{ $inspectedMessage->message_encrypted }}
                </div>

                <div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <a
                            href="mailto:{{ $inspectedMessage->sender_email_encrypted }}?subject=Re: {{ urlencode($inspectedMessage->subject ?? 'Your Inquiry') }}"
                            class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs rounded-xl transition"
                        >
                            Email Reply
                        </a>
                        @if(!$inspectedMessage->is_replied)
                            <button wire:click="markMessageReplied({{ $inspectedMessage->id }})" class="px-3 py-1.5 bg-slate-800 text-slate-200 text-xs font-semibold rounded-xl hover:bg-slate-700">
                                Mark Replied
                            </button>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <button wire:click="flagMessage({{ $inspectedMessage->id }})" class="px-2.5 py-1.5 text-amber-400 hover:bg-amber-500/10 rounded-lg text-xs font-semibold">
                            Flag Spam
                        </button>
                        <button wire:click="deleteMessage({{ $inspectedMessage->id }})" class="px-2.5 py-1.5 text-rose-400 hover:bg-rose-500/10 rounded-lg text-xs font-semibold">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 2: PROMOTE LISTING MODAL -->
    @if($showPromoteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fadeIn">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>★ Promote Classified Listing</span>
                    </h3>
                    <button wire:click="closePromoteModal" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
                </div>

                <form wire:submit="activatePromotion" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                            Select Listing to Feature <span class="text-rose-400">*</span>
                        </label>
                        <select wire:model="promoteListingId" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                            <option value="">Choose an active ad...</option>
                            @foreach($userActiveListings as $actAd)
                                <option value="{{ $actAd->id }}">{{ $actAd->title }}</option>
                            @endforeach
                        </select>
                        @error('promoteListingId') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                            Promotion Package <span class="text-rose-400">*</span>
                        </label>
                        <select wire:model="selectedPlanId" required class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                            @foreach($pricingPlans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} — ${{ number_format($plan->price_cents / 100, 2) }} ({{ $plan->duration_days }} days)</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                            Placement Priority <span class="text-rose-400">*</span>
                        </label>
                        <select wire:model="selectedPlacement" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                            <option value="category_top">Top of Category & Search</option>
                            <option value="city_top">City Spotlight Banner</option>
                            <option value="homepage">Homepage Spotlight Showcase</option>
                        </select>
                    </div>

                    <div class="p-3 bg-purple-500/10 border border-purple-500/30 rounded-xl text-[11px] text-purple-300">
                        Featured ads receive highlighted borders, prioritized search indexing, and 3x average visitor engagement.
                    </div>

                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" wire:click="closePromoteModal" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs font-semibold rounded-xl">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl shadow-lg">Confirm & Activate Spotlight</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 3: CONFIRM DELETE LISTING -->
    @if($showDeleteListingModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fadeIn">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-sm w-full p-6 space-y-4 shadow-2xl">
                <h3 class="text-base font-bold text-white">Delete Listing?</h3>
                <p class="text-xs text-slate-400">
                    Are you sure you want to permanently delete this classified advertisement? This action cannot be undone.
                </p>
                <div class="pt-2 flex justify-end gap-3">
                    <button wire:click="$set('showDeleteListingModal', false)" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs font-semibold rounded-xl">Cancel</button>
                    <button wire:click="deleteListing" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl">Delete Ad</button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 4: CONFIRM ACCOUNT DELETION -->
    @if($showDeleteAccountModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fadeIn">
            <div class="bg-slate-900 border border-rose-900/60 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <h3 class="text-base font-bold text-rose-400">Permanently Delete Account?</h3>
                <p class="text-xs text-slate-300 leading-relaxed">
                    This action is immediate and irreversible. All your active classified advertisements, saved favorites, uploaded photos, and message archives will be permanently purged in accordance with privacy laws.
                </p>

                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-400 uppercase">Confirm Current Password:</label>
                    <input
                        type="password"
                        wire:model="deleteAccountPassword"
                        placeholder="Enter your account password..."
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white"
                    >
                    @error('deleteAccountPassword') <span class="text-rose-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button wire:click="$set('showDeleteAccountModal', false)" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs font-semibold rounded-xl">Cancel</button>
                    <button wire:click="deleteAccount" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl">Permanently Delete My Account</button>
                </div>
            </div>
        </div>
    @endif
</div>

