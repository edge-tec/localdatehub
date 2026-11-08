<x-layouts.app title="Reassign Category Listings — Admin | LocalAdZone">
    <div class="py-8 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="border-b border-slate-200 pb-5">
            <div class="flex items-center space-x-2 text-rose-500 font-semibold text-xs tracking-wider uppercase">
                <a href="{{ route('admin.categories.index') }}" class="hover:text-rose-600">Categories</a>
                <span>/</span>
                <span>Reassign & Migrate Listings</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 mt-1">
                Migrate Listings: {{ $category->name }}
            </h1>
            <p class="text-slate-400 text-xs mt-1">
                Safely move listings from this category to another category before deletion or restructuring.
            </p>
        </div>

        @if(session('error'))
            <div class="p-4 bg-rose-50 border border-rose-600/40 rounded-xl text-xs text-rose-600">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-xl">
            <!-- Warning / Current Count Box -->
            <div class="p-4 bg-amber-50 border border-amber-600/40 rounded-xl text-xs text-amber-700 flex items-start space-x-3">
                <svg class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <strong class="font-bold text-amber-700 block mb-0.5">Dependency Check:</strong>
                    Category <strong>{{ $category->name }}</strong> currently has <strong>{{ $listingsCount }}</strong> listing(s). You must select a destination category to reassign these listings.
                </div>
            </div>

            <!-- Migration Form -->
            <form action="{{ route('admin.categories.reassign.action', $category->id) }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                        Target Destination Category <span class="text-rose-600">*</span>
                    </label>
                    <select
                        name="target_category_id"
                        required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    >
                        <option value="">-- Select Destination Category --</option>
                        @foreach($targetOptions as $target)
                            <option value="{{ $target->id }}">
                                {{ $target->name }} ({{ $target->slug }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
                    <a href="{{ route('admin.categories.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition shadow-lg">
                        Migrate {{ $listingsCount }} Listings Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>

