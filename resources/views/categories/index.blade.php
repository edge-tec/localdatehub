<x-layouts.app title="Browse Categories — USA Personals & Classifieds Directory | LocalDateHub">
    <div class="py-8 sm:py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 sm:space-y-10">
        
        <!-- Header Banner & Search Discovery -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-rose-50 via-pink-50/60 to-purple-50/50 border border-rose-200/80 p-6 sm:p-10 shadow-xs">
            <div class="relative z-10 max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white text-[#F20B65] border border-rose-200 text-xs font-black uppercase tracking-wider shadow-2xs mb-3">
                    <svg class="w-3.5 h-3.5 fill-[#F20B65]" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/></svg>
                    <span>Dynamic Category Directory</span>
                </div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                    USA Adult Personal Classifieds Directory
                </h1>
                <p class="text-slate-700 font-medium text-sm sm:text-base mt-2.5 leading-relaxed">
                    Explore all personal dating, companion, and adult connection categories across the United States. Find verified singles, activity partners, and local community meetups.
                </p>
            </div>
            
            <!-- Ambient Background Graphic -->
            <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-[#F20B65]/10 rounded-full blur-3xl pointer-events-none"></div>
        </div>

        <!-- Safety Notice Banner -->
        <div class="bg-gradient-to-r from-rose-50 to-pink-50/40 border border-rose-200 rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 shadow-2xs">
            <div class="w-9 h-9 rounded-xl bg-rose-100 text-[#F20B65] flex items-center justify-center shrink-0 mt-0.5 shadow-2xs">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="text-xs sm:text-sm text-slate-800 leading-relaxed">
                <strong class="font-black text-[#F20B65] block mb-0.5">Lawful 18+ Personal Connections Only:</strong>
                Commercial sexual services, prostitution, solicitation, trafficking, and exploitation are strictly prohibited and permanently banned. All advertisers must be at least 18 years of age.
            </div>
        </div>

        <!-- Hierarchical Category Groups Grid -->
        @foreach($rootCategories as $root)
            <div class="space-y-6">
                @if($rootCategories->count() > 1)
                    <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
                        <div class="w-2.5 h-6 rounded-full" style="background-color: #F20B65 !important;"></div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            {{ $root->name }}
                        </h2>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-7 items-stretch">
                    @foreach($root->allActiveChildren as $group)
                        <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-7 shadow-xs hover:shadow-xl hover:border-pink-300 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group relative overflow-hidden">
                            <!-- Top Decorative Color Ribbon -->
                            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#F20B65] via-pink-400 to-purple-500 opacity-80 group-hover:opacity-100 transition-opacity"></div>

                            <div>
                                <!-- Card Header: Icon, Title & Code -->
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-200/70 flex items-center justify-center shrink-0 shadow-2xs group-hover:scale-105 transition-all duration-300" style="color: #F20B65 !important;">
                                            <svg class="w-6 h-6 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <a href="{{ route('categories.show', $group->slug) }}" class="text-lg sm:text-xl font-black text-slate-900 group-hover:text-[#F20B65] transition-colors block leading-tight">
                                                {{ $group->name }}
                                            </a>
                                            <span class="text-xs font-bold text-slate-500 block mt-0.5">
                                                {{ $group->listings_count }} Active Ads Available
                                            </span>
                                        </div>
                                    </div>
                                    @if(!empty($group->code))
                                        <span class="text-[10px] font-mono font-black px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
                                            {{ $group->code }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Description (High contrast text) -->
                                <p class="text-xs sm:text-sm text-slate-600 font-normal leading-relaxed line-clamp-2 mt-2">
                                    {{ $group->description ?: 'Browse local adult connections, verified personals, and authentic dating meetups.' }}
                                </p>

                                <!-- Subcategories Grid -->
                                @if($group->allActiveChildren->isNotEmpty())
                                    <div class="mt-5 pt-4 border-t border-slate-100 space-y-2.5">
                                        <span class="block text-[11px] font-black text-slate-400 uppercase tracking-wider">
                                            Subcategories
                                        </span>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($group->allActiveChildren as $sub)
                                                <a
                                                    href="{{ route('categories.show', $sub->slug) }}"
                                                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-50 border border-slate-200 text-slate-800 hover:text-[#F20B65] hover:border-pink-300 hover:bg-pink-50/40 transition-all duration-200 shadow-2xs"
                                                >
                                                    @if(!empty($sub->code))
                                                        <span class="font-black text-[#F20B65] mr-1.5 text-[10px]">
                                                            {{ $sub->code }}
                                                        </span>
                                                    @endif
                                                    <span>{{ $sub->name }}</span>
                                                    @if($sub->listings_count > 0)
                                                        <span class="ml-1.5 px-1.5 py-0.2 rounded-full text-[10px] bg-white border border-slate-200 text-slate-600 font-extrabold">
                                                            {{ $sub->listings_count }}
                                                        </span>
                                                    @endif
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Card Footer Action -->
                            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-500">
                                    {{ $group->allActiveChildren->count() }} Classifications
                                </span>
                                <a 
                                    href="{{ route('categories.show', $group->slug) }}" 
                                    class="inline-flex items-center gap-1.5 text-xs font-black text-[#F20B65] group-hover:translate-x-0.5 transition-transform"
                                >
                                    <span>Browse Hub</span>
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

    </div>
</x-layouts.app>
