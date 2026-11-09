<x-layouts.app title="{{ $category->seo_metadata['meta_title'] ?? ($category->name . ' Classifieds | LocalDateHub') }}">
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Admin Preview Banner -->
        @if($isPreview)
            <div class="bg-amber-50 border border-amber-600/50 rounded-2xl p-4 text-amber-700 text-xs flex items-center justify-between shadow-lg">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-5 h-5 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <span><strong>ADMIN PREVIEW MODE:</strong> This category is currently <strong>DISABLED</strong> from public display. You are viewing how it renders to visitors.</span>
                </div>
                <a href="{{ route('admin.categories.edit', $category->id) }}" class="px-3 py-1 bg-amber-500 text-slate-950 font-bold rounded-lg hover:bg-amber-400 transition">
                    Edit Category
                </a>
            </div>
        @endif

        <!-- Breadcrumbs Navigation -->
        <nav class="flex text-xs text-slate-400 space-x-2 items-center" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-slate-900">Home</a>
            <span>/</span>
            <a href="{{ route('categories.index') }}" class="hover:text-slate-900">Categories</a>
            @foreach($breadcrumbs as $crumb)
                <span>/</span>
                @if($loop->last)
                    <span class="text-slate-900 font-medium">{{ $crumb['name'] }}</span>
                @else
                    <a href="{{ $crumb['url'] }}" class="hover:text-slate-900">{{ $crumb['name'] }}</a>
                @endif
            @endforeach
        </nav>

        <!-- Category Header -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 backdrop-blur-md">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3">
                        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                            {{ $category->name }}
                        </h1>
                        @if(!empty($category->code))
                            <span class="px-2.5 py-0.5 text-xs font-mono font-bold bg-rose-500/20 text-rose-600 border border-rose-500/30 rounded-lg">
                                {{ $category->code }}
                            </span>
                        @endif
                        @if(!$category->is_active)
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-400 border border-slate-200 rounded-md">
                                Inactive
                            </span>
                        @endif
                    </div>
                    <p class="text-slate-600 mt-2 text-sm max-w-2xl leading-relaxed">
                        {{ $category->description ?: 'Browse adult personal classifieds and dating connections in this category.' }}
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <a
                        href="#listings"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-semibold rounded-xl text-xs transition shadow-md flex items-center space-x-1.5"
                    >
                        <span>Browse {{ $listings->total() }} Ads</span>
                    </a>
                </div>
            </div>

            <!-- Safety Guidelines Box -->
            @if(!empty($category->safety_guidelines))
                <div class="mt-6 pt-6 border-t border-slate-200 flex items-start space-x-3 text-xs text-rose-600/90 bg-rose-50 p-3.5 rounded-xl border border-rose-200">
                    <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <strong class="font-bold text-rose-600">Category Safety Guidelines:</strong>
                        {{ $category->safety_guidelines }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Subcategories Grid (if any) -->
        @if($subcategories->isNotEmpty())
            <div class="space-y-3">
                <h2 class="text-sm font-bold text-slate-600 uppercase tracking-wider">
                    Explore {{ $category->name }} Subcategories
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach($subcategories as $sub)
                        <a
                            href="{{ route('categories.show', $sub->slug) }}"
                            class="p-4 bg-white border border-slate-200 hover:border-rose-500/50 rounded-2xl transition group block"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-slate-900 group-hover:text-rose-600 text-sm transition">
                                    {{ $sub->name }}
                                </span>
                                @if(!empty($sub->code))
                                    <span class="text-[10px] font-mono text-slate-400">
                                        [{{ $sub->code }}]
                                    </span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-400 mt-1">
                                {{ $sub->listings_count }} active {{ Str::plural('ad', $sub->listings_count) }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Dynamic Attribute Filters -->
        @if($attributes->isNotEmpty())
            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                    Filter by Attributes
                </div>
                <form action="{{ route('categories.show', $category->slug) }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach($attributes as $attr)
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                {{ $attr->name }}
                            </label>
                            @if(in_array($attr->field_type, ['select', 'radio']))
                                <select
                                    name="attr[{{ $attr->code }}]"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-900 focus:ring-1 focus:ring-rose-500 focus:outline-none"
                                >
                                    <option value="">Any {{ $attr->name }}</option>
                                    @foreach($attr->options as $opt)
                                        <option value="{{ $opt->value }}" {{ request("attr.{$attr->code}") === $opt->value ? 'selected' : '' }}>
                                            {{ $opt->label }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif($attr->field_type === 'number')
                                <input
                                    type="number"
                                    name="attr[{{ $attr->code }}]"
                                    value="{{ request("attr.{$attr->code}") }}"
                                    placeholder="{{ $attr->placeholder ?: 'Filter ' . $attr->name }}"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-900 focus:ring-1 focus:ring-rose-500 focus:outline-none"
                                >
                            @else
                                <input
                                    type="text"
                                    name="attr[{{ $attr->code }}]"
                                    value="{{ request("attr.{$attr->code}") }}"
                                    placeholder="{{ $attr->placeholder ?: 'Filter ' . $attr->name }}"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-900 focus:ring-1 focus:ring-rose-500 focus:outline-none"
                                >
                            @endif
                        </div>
                    @endforeach
                    <div class="flex items-end">
                        <button type="submit" class="w-full py-1.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-900 font-semibold rounded-xl text-xs transition">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Listings List -->
        <div id="listings" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-900">
                    {{ $category->name }} Listings ({{ $listings->total() }})
                </h2>
            </div>

            @if($listings->isEmpty())
                <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center space-y-3">
                    <svg class="w-10 h-10 text-slate-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <p class="text-slate-400 text-sm">No active listings currently found in this category.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($listings as $listing)
                        <div class="bg-white border border-slate-200 hover:border-slate-200 rounded-2xl p-5 transition space-y-3">
                            <div class="flex items-center justify-between text-xs text-slate-400">
                                <span class="text-rose-600 font-semibold">{{ $listing->city?->name }}, {{ $listing->state?->code }}</span>
                                <span>{{ $listing->published_at?->diffForHumans() }}</span>
                            </div>
                            <h3 class="font-bold text-slate-900 text-base hover:text-rose-600 transition line-clamp-1">
                                {{ $listing->title }}
                            </h3>
                            <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed">
                                {{ $listing->description }}
                            </p>
                            <div class="pt-2 border-t border-slate-200 flex items-center justify-between text-xs text-slate-400">
                                <span>Age: {{ $listing->poster_age }}</span>
                                <span class="capitalize">{{ str_replace('_', ' ', $listing->intent) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $listings->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>

