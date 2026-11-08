<x-layouts.app title="Edit Listing '{{ $listing->title }}' | LocalAdZone">
    <div class="py-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="border-b border-slate-200 pb-5">
            <div class="flex items-center space-x-2 text-rose-500 font-semibold text-xs tracking-wider uppercase">
                <a href="{{ route('account.listings.index') }}" class="hover:text-rose-600">My Listings</a>
                <span>/</span>
                <span>Edit Listing</span>
            </div>
            <div class="flex items-center justify-between mt-1">
                <h1 class="text-3xl font-extrabold text-slate-900">
                    Edit Classified: {{ $listing->title }}
                </h1>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold capitalize {{ $listing->status === 'active' ? 'bg-emerald-500/20 text-emerald-700 border border-emerald-500/30' : ($listing->status === 'pending_review' ? 'bg-amber-500/20 text-amber-700 border border-amber-500/30' : 'bg-slate-100 text-slate-600') }}">
                    Status: {{ str_replace('_', ' ', $listing->status) }}
                </span>
            </div>
            @if($listing->rejection_reason)
                <div class="mt-3 p-3 bg-rose-50 border border-rose-600/40 rounded-xl text-xs text-rose-600">
                    <strong class="font-bold text-rose-600">Moderator Rejection Feedback:</strong> {{ $listing->rejection_reason }}
                </div>
            @endif
        </div>

        @livewire('listing-wizard', ['listingId' => $listing->id])
    </div>
</x-layouts.app>

