<x-layouts.app title="My Classified Ads — LocalAdZone">
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <div class="flex items-center space-x-2 text-rose-500 font-semibold text-xs tracking-wider uppercase">
                    <a href="{{ route('account.profile') }}" class="hover:text-rose-600">Account</a>
                    <span>/</span>
                    <span>My Advertisements</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
                    Manage My Classified Listings
                </h1>
                <p class="text-slate-400 text-xs sm:text-sm mt-1">
                    Monitor publication status, renew expiring posts, update details, or create new personal ads.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('account.payments.index') }}"
                    class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-xl text-xs transition flex items-center space-x-1.5"
                >
                    <span>₿ Payment History</span>
                </a>
                <a
                    href="{{ route('listings.create') }}"
                    class="px-4 py-2.5 bg-[#F20B83] hover:bg-[#d90858] text-white font-bold rounded-xl text-xs transition shadow-md shadow-[#F20B83]/20 flex items-center space-x-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Post New Ad</span>
                </a>
            </div>
        </div>

        @if(request('boost_success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-bold flex items-center gap-2 shadow-xs">
                <span class="text-base">🚀</span>
                <span>Your listing boost has been successfully confirmed and activated! It is now pinned and spotlighted.</span>
            </div>
        @endif

        @if(session('status'))
            <div class="p-4 bg-emerald-50 border border-emerald-600/40 rounded-xl text-xs text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-rose-50 border border-rose-600/40 rounded-xl text-xs text-rose-600">
                {{ session('error') }}
            </div>
        @endif

        <!-- Status Filter Tabs -->
        <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
            <a
                href="{{ route('account.listings.index') }}"
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition border border-slate-200 {{ empty($currentStatus) ? 'bg-[#F20B83] text-white border-[#F20B83] shadow-xs' : 'bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}"
            >
                All ({{ $counts['all'] }})
            </a>
            <a
                href="{{ route('account.listings.index', ['status' => 'active']) }}"
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition border border-slate-200 {{ $currentStatus === 'active' ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}"
            >
                Active ({{ $counts['active'] }})
            </a>
            <a
                href="{{ route('account.listings.index', ['status' => 'pending_review']) }}"
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition border border-slate-200 {{ $currentStatus === 'pending_review' ? 'bg-amber-600 text-white border-amber-600 shadow-xs' : 'bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}"
            >
                Pending Review ({{ $counts['pending'] }})
            </a>
            <a
                href="{{ route('account.listings.index', ['status' => 'draft']) }}"
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition border border-slate-200 {{ $currentStatus === 'draft' ? 'bg-blue-600 text-white border-blue-600 shadow-xs' : 'bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}"
            >
                Drafts ({{ $counts['draft'] }})
            </a>
            <a
                href="{{ route('account.listings.index', ['status' => 'rejected']) }}"
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition border border-slate-200 {{ $currentStatus === 'rejected' ? 'bg-rose-700 text-white border-rose-700 shadow-xs' : 'bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}"
            >
                Rejected ({{ $counts['rejected'] }})
            </a>
            <a
                href="{{ route('account.listings.index', ['status' => 'expired']) }}"
                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition {{ $currentStatus === 'expired' ? 'bg-slate-200 text-slate-900 shadow' : 'bg-white text-slate-400 hover:text-slate-900' }}"
            >
                Expired ({{ $counts['expired'] }})
            </a>
        </div>

        <!-- Listings Grid / List -->
        @if($listings->isEmpty())
            <div class="bg-white border border-slate-200 rounded-3xl p-12 text-center space-y-4">
                <svg class="w-12 h-12 text-slate-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-lg font-bold text-slate-900">No listings found in this view</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                    Create your first personal classified advertisement or check other status filters.
                </p>
                <a
                    href="{{ route('listings.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl transition"
                >
                    Create a Listing
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($listings as $listing)
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 hover:border-slate-200 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-lg">
                        <div class="flex items-start space-x-4">
                            @if($listing->primaryPhoto)
                                <img src="{{ asset('storage/' . ($listing->primaryPhoto->thumbnail_path ?: $listing->primaryPhoto->file_path)) }}" class="w-20 h-20 rounded-xl object-cover border border-slate-200 shrink-0" alt="Thumbnail">
                            @else
                                <div class="w-20 h-20 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-600 shrink-0">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif

                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $listing->status === 'active' ? 'bg-emerald-500/20 text-emerald-700 border border-emerald-500/30' : ($listing->status === 'pending_review' ? 'bg-amber-500/20 text-amber-700 border border-amber-500/30' : ($listing->status === 'rejected' ? 'bg-rose-500/20 text-rose-600 border border-rose-500/30' : 'bg-slate-100 text-slate-400 border border-slate-200')) }}">
                                        {{ str_replace('_', ' ', $listing->status) }}
                                    </span>
                                    <span class="text-xs text-rose-600 font-semibold">{{ $listing->category?->name }}</span>
                                    <span class="text-xs text-slate-400">&bull; {{ $listing->city?->name }}, {{ $listing->state?->code }}</span>
                                </div>

                                <a href="{{ route('listings.show', $listing->slug) }}" class="font-bold text-slate-900 hover:text-rose-600 transition text-base block">
                                    {{ $listing->title }}
                                </a>

                                <div class="text-xs text-slate-400 flex flex-wrap items-center gap-3">
                                    <span>Views: {{ $listing->view_count }}</span>
                                    @if($listing->expires_at)
                                        <span>Expires in: {{ $listing->expires_at->diffForHumans() }}</span>
                                    @endif
                                </div>

                                @if($listing->rejection_reason)
                                    <div class="text-[11px] text-rose-600 bg-rose-50 p-2 rounded-lg border border-rose-200 mt-1">
                                        <strong>Rejection note:</strong> {{ $listing->rejection_reason }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 shrink-0">
                            @if($listing->canBeRenewed())
                                <form action="{{ route('account.listings.renew', $listing->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-semibold transition">
                                        Renew 30d
                                    </button>
                                </form>
                            @endif

                            @if($listing->status === 'active')
                                <form action="{{ route('account.listings.pause', $listing->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition">
                                        Pause
                                    </button>
                                </form>
                            @elseif($listing->status === 'paused')
                                <form action="{{ route('account.listings.resume', $listing->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-semibold transition">
                                        Resume
                                    </button>
                                </form>
                            @endif

                            @if($listing->status === 'active')
                                <a href="{{ route('boost.checkout', $listing->id) }}" class="px-3 py-1.5 bg-[#F20B83] hover:bg-[#d90858] text-white rounded-xl text-xs font-black transition flex items-center gap-1 shadow-xs">
                                    <span>🚀 Boost</span>
                                </a>
                            @endif

                            <a href="{{ route('listings.edit', $listing->id) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-900 rounded-xl text-xs font-semibold transition">
                                Edit
                            </a>

                            <form action="{{ route('account.listings.destroy', $listing->id) }}" method="POST" onsubmit="return confirm('Delete listing \'{{ $listing->title }}\'?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 border border-rose-500/20 rounded-xl text-xs font-semibold transition">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach

                <div class="mt-6">
                    {{ $listings->links() }}
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>

