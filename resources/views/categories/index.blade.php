<x-layouts.app title="Browse Categories — USA Adult Personals & Dating Classifieds | LocalAdZone">
    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <!-- Hero Header -->
        <div class="border-b border-slate-200 pb-8">
            <div class="flex items-center space-x-2 text-rose-500 font-semibold text-xs tracking-wider uppercase">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/></svg>
                <span>Dynamic Category Directory</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2 tracking-tight">
                USA Adult Personal Classifieds Directory
            </h1>
            <p class="text-slate-400 mt-2 text-sm sm:text-base max-w-3xl">
                Browse all personal dating, companion, and adult connection categories across the United States. Find verified singles, activity partners, and local community meetups.
            </p>
        </div>

        <!-- Safety Warning Banner -->
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 sm:p-5 flex items-start space-x-3.5">
            <svg class="w-6 h-6 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="text-xs text-rose-600/90 leading-relaxed">
                <strong class="font-bold text-rose-600 block mb-0.5">Lawful 18+ Personal Connections Only:</strong>
                Commercial sexual services, prostitution, solicitation, trafficking, and exploitation are strictly prohibited and permanently banned. All advertisers must be at least 18 years of age.
            </div>
        </div>

        <!-- Hierarchical Category Groups -->
        @foreach($rootCategories as $root)
            <div class="space-y-6">
                @if($rootCategories->count() > 1)
                    <h2 class="text-2xl font-bold text-slate-900 border-l-4 border-rose-500 pl-3">
                        {{ $root->name }}
                    </h2>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($root->allActiveChildren as $group)
                        <div class="bg-white border border-slate-200 rounded-2xl p-5 hover:border-slate-200 transition flex flex-col justify-between shadow-lg">
                            <div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                        </div>
                                        <a href="{{ route('categories.show', $group->slug) }}" class="text-lg font-bold text-slate-900 hover:text-rose-600 transition">
                                            {{ $group->name }}
                                        </a>
                                    </div>
                                    @if(!empty($group->code))
                                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                            {{ $group->code }}
                                        </span>
                                    @endif
                                </div>

                                <p class="text-xs text-slate-400 mt-2.5 line-clamp-2">
                                    {{ $group->description }}
                                </p>

                                <!-- Subcategories -->
                                @if($group->allActiveChildren->isNotEmpty())
                                    <div class="mt-4 pt-4 border-t border-slate-200 space-y-1.5">
                                        <span class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-2">
                                            Subcategories
                                        </span>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($group->allActiveChildren as $sub)
                                                <a
                                                    href="{{ route('categories.show', $sub->slug) }}"
                                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-50 border border-slate-200 text-slate-600 hover:text-slate-900 hover:border-rose-500/50 hover:bg-white transition"
                                                >
                                                    @if(!empty($sub->code))
                                                        <span class="font-bold text-rose-600 mr-1.5 text-[10px]">
                                                            {{ $sub->code }}
                                                        </span>
                                                    @endif
                                                    <span>{{ $sub->name }}</span>
                                                    @if($sub->listings_count > 0)
                                                        <span class="ml-1.5 text-[10px] text-slate-400">
                                                            ({{ $sub->listings_count }})
                                                        </span>
                                                    @endif
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-5 pt-3 border-t border-slate-200 flex items-center justify-between text-xs text-slate-400">
                                <span>{{ $group->listings_count }} Active Ads</span>
                                <a href="{{ route('categories.show', $group->slug) }}" class="text-rose-600 hover:text-rose-600 font-semibold flex items-center space-x-1">
                                    <span>Browse Hub</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.app>

