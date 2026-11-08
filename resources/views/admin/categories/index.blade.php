<x-layouts.app title="Admin — Manage Categories & Hierarchy | LocalAdZone">
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Top Ribbon -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <div class="flex items-center space-x-2 text-rose-500 font-semibold text-xs tracking-wider uppercase">
                    <a href="{{ route('admin.locations.index') }}" class="hover:text-rose-600">Admin Control Panel</a>
                    <span>/</span>
                    <span>Dynamic Category Engine</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
                    Manage Categories & Hierarchy
                </h1>
                <p class="text-slate-400 text-xs sm:text-sm mt-1">
                    Configure lawful personal classified categories, subcategories, dynamic attributes, and safety guidelines.
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <a
                    href="{{ route('admin.categories.create') }}"
                    class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-semibold rounded-xl text-xs transition shadow-md flex items-center space-x-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Add Category</span>
                </a>
            </div>
        </div>

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

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                <span class="block text-xs text-slate-400 font-medium">Total Categories</span>
                <span class="text-xl font-bold text-slate-900 mt-1 block">{{ $totalCategories }}</span>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                <span class="block text-xs text-slate-400 font-medium">Active Categories</span>
                <span class="text-xl font-bold text-emerald-700 mt-1 block">{{ $activeCategories }}</span>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                <span class="block text-xs text-slate-400 font-medium">Total Listings</span>
                <span class="text-xl font-bold text-rose-600 mt-1 block">{{ $totalListings }}</span>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                <span class="block text-xs text-slate-400 font-medium">Max Allowed Depth</span>
                <span class="text-xl font-bold text-amber-700 mt-1 block">5 Levels</span>
            </div>
        </div>

        <!-- Hierarchical Tree Table -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                    Category Hierarchy Tree
                </h2>
                <span class="text-xs text-slate-400">
                    Depth 0 (Root) &bull; Depth 1 (Groups) &bull; Depth 2 (Subcategories)
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-slate-400 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-4">Category Name & Hierarchy</th>
                            <th class="py-3 px-4">Slug</th>
                            <th class="py-3 px-4">Code</th>
                            <th class="py-3 px-4">Order</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Ads</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60">
                        @php
                            $renderRow = function($cat, $indent = 0) use (&$renderRow) {
                        @endphp
                            <tr class="hover:bg-slate-100 transition {{ $cat->depth === 0 ? 'bg-white font-bold' : ($cat->depth === 1 ? 'bg-slate-50 font-semibold' : '') }}">
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2" style="padding-left: {{ $indent * 24 }}px;">
                                        @if($indent > 0)
                                            <span class="text-slate-600 select-none">└─</span>
                                        @endif
                                        <span class="{{ $cat->depth === 0 ? 'text-rose-600 text-sm' : ($cat->depth === 1 ? 'text-slate-900' : 'text-slate-600') }}">
                                            {{ $cat->name }}
                                        </span>
                                        @if($cat->is_featured)
                                            <span class="px-1.5 py-0.2 rounded bg-amber-500/10 text-amber-700 text-[10px] border border-amber-500/20">Featured</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-400">{{ $cat->slug }}</td>
                                <td class="py-3 px-4 font-mono">
                                    @if($cat->code)
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">{{ $cat->code }}</span>
                                    @else
                                        <span class="text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-400">{{ $cat->display_order }}</td>
                                <td class="py-3 px-4">
                                    @if($cat->is_active)
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 text-[10px]">Active</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-400 border border-slate-200 text-[10px]">Disabled</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-600 font-semibold">
                                    {{ $cat->listings_count }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('admin.categories.preview', $cat->id) }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-[11px] transition" title="Preview public view">
                                            Preview
                                        </a>
                                        <a href="{{ route('admin.categories.attributes', $cat->id) }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-rose-600 rounded text-[11px] transition" title="Manage dynamic attributes">
                                            Attributes
                                        </a>
                                        <a href="{{ route('admin.categories.edit', $cat->id) }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-900 rounded text-[11px] transition">
                                            Edit
                                        </a>
                                        <a href="{{ route('admin.categories.reassign', $cat->id) }}" class="px-2 py-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 border border-amber-500/20 rounded text-[11px] transition" title="Reassign listings">
                                            Reassign
                                        </a>
                                        <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete category \'{{ $cat->name }}\'?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 border border-rose-500/20 rounded text-[11px] transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @php
                            if ($cat->allChildren) {
                                foreach ($cat->allChildren as $child) {
                                    $renderRow($child, $indent + 1);
                                }
                            }
                        };
                        @endphp

                        @foreach($rootCategories as $root)
                            @php $renderRow($root, 0); @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>

