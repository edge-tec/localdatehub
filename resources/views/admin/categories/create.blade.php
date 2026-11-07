<x-layouts.app title="Create Category — Admin | LocalAdZone">
    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="border-b border-slate-800 pb-5">
            <div class="flex items-center space-x-2 text-rose-500 font-semibold text-xs tracking-wider uppercase">
                <a href="{{ route('admin.categories.index') }}" class="hover:text-rose-400">Categories</a>
                <span>/</span>
                <span>Create New Category</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">
                Add New Classified Category
            </h1>
            <p class="text-slate-400 text-xs mt-1">
                Define a root category, category group, or specific personal subcategory.
            </p>
        </div>

        <form action="{{ route('admin.categories.store') }}" method="POST" class="bg-slate-900/80 border border-slate-800 rounded-2xl p-6 sm:p-8 space-y-6 shadow-xl">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Category Name <span class="text-rose-400">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        placeholder="e.g. Activity Partners"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    >
                    @error('name') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        URL Slug (Optional - auto-generated)
                    </label>
                    <input
                        type="text"
                        name="slug"
                        value="{{ old('slug') }}"
                        placeholder="e.g. activity-partners"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white font-mono focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    >
                    @error('slug') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Category Code (e.g. M4W, ACT)
                    </label>
                    <input
                        type="text"
                        name="code"
                        value="{{ old('code') }}"
                        placeholder="e.g. ACT"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white font-mono focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Parent Category (Leave empty for root)
                    </label>
                    <select
                        name="parent_id"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    >
                        <option value="">-- None (Top Level Root) --</option>
                        @foreach($parentOptions as $parent)
                            <option value="{{ $parent['id'] }}" {{ (old('parent_id', $selectedParentId) == $parent['id']) ? 'selected' : '' }}>
                                {{ $parent['formatted_name'] }} (depth {{ $parent['depth'] }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                    Category Description
                </label>
                <textarea
                    name="description"
                    rows="3"
                    placeholder="Describe what kind of personal advertisements belong in this category..."
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                >{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                    Category Safety Guidelines & Compliance Notice
                </label>
                <textarea
                    name="safety_guidelines"
                    rows="3"
                    placeholder="Specific safety recommendations shown to members posting in this category..."
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                >{{ old('safety_guidelines') }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Display Order
                    </label>
                    <input
                        type="number"
                        name="display_order"
                        value="{{ old('display_order', 1) }}"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Min Age Override
                    </label>
                    <input
                        type="number"
                        name="min_age_override"
                        value="{{ old('min_age_override', 18) }}"
                        min="18"
                        max="99"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Icon Name
                    </label>
                    <input
                        type="text"
                        name="icon"
                        value="{{ old('icon', 'heart') }}"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    >
                </div>
            </div>

            <!-- SEO Metadata -->
            <div class="pt-4 border-t border-slate-800 space-y-4">
                <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider">
                    SEO Metadata
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Meta Title</label>
                        <input
                            type="text"
                            name="meta_title"
                            value="{{ old('meta_title') }}"
                            placeholder="e.g. Local Activity Partners Classifieds | LocalAdZone"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white focus:ring-1 focus:ring-rose-500 focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Meta Description</label>
                        <input
                            type="text"
                            name="meta_description"
                            value="{{ old('meta_description') }}"
                            placeholder="e.g. Meet local activity companions for hiking, dining, and live concerts..."
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white focus:ring-1 focus:ring-rose-500 focus:outline-none"
                        >
                    </div>
                </div>
            </div>

            <!-- Status Toggles -->
            <div class="pt-4 border-t border-slate-800 flex items-center space-x-6 text-xs text-slate-300">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-700 bg-slate-950 text-rose-600 focus:ring-rose-500">
                    <span class="font-semibold text-white">Active (Visible to public)</span>
                </label>

                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-700 bg-slate-950 text-rose-600 focus:ring-rose-500">
                    <span class="font-semibold text-white">Featured Category</span>
                </label>
            </div>

            <div class="pt-5 border-t border-slate-800 flex items-center justify-end space-x-3">
                <a href="{{ route('admin.categories.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition shadow-lg">
                    Create Category
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>

