<x-layouts.app title="Create Personal Classified Listing | LocalAdZone">
    <div class="py-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="border-b border-slate-800 pb-5">
            <div class="flex items-center space-x-2 text-rose-500 font-semibold text-xs tracking-wider uppercase">
                <a href="{{ route('account.listings.index') }}" class="hover:text-rose-400">My Listings</a>
                <span>/</span>
                <span>Listing Creation Wizard</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-1">
                Post an Adult Personal Classified Ad
            </h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">
                Connect with verified local adults in the United States. Follow the 12-step guided wizard below.
            </p>
        </div>

        @livewire('listing-wizard')
    </div>
</x-layouts.app>
