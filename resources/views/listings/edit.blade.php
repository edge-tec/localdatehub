<x-layouts.app title="Edit Listing '{{ $listing->title }}' | LocalAdZone">
    <div class="py-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="border-b border-slate-800 pb-5">
            <div class="flex items-center space-x-2 text-rose-500 font-semibold text-xs tracking-wider uppercase">
                <a href="{{ route('account.listings.index') }}" class="hover:text-rose-400">My Listings</a>
                <span>/</span>
                <span>Edit Listing</span>
            </div>
            <div class="flex items-center justify-between mt-1">
                <h1 class="text-3xl font-extrabold text-white">
                    Edit Classified: {{ $listing->title }}
                </h1>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold capitalize {{ $listing->status === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : ($listing->status === 'pending_review' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-slate-800 text-slate-300') }}">
                    Status: {{ str_replace('_', ' ', $listing->status) }}
                </span>
            </div>
            @if($listing->rejection_reason)
                <div class="mt-3 p-3 bg-rose-950/40 border border-rose-600/40 rounded-xl text-xs text-rose-200">
                    <strong class="font-bold text-rose-300">Moderator Rejection Feedback:</strong> {{ $listing->rejection_reason }}
                </div>
            @endif
        </div>

        @livewire('listing-wizard', ['listingId' => $listing->id])
    </div>
</x-layouts.app>
