<x-layouts.app title="Edit Category '{{ $category->name }}' — Admin | LocalAdZone">
    <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="border-b border-slate-800 pb-5">
            <div class="flex items-center space-x-2 text-rose-500 font-semibold text-xs tracking-wider uppercase">
                <a href="{{ route('admin.categories.index') }}" class="hover:text-rose-400">Categories</a>
                <span>/</span>
                <span>Edit Category</span>
            </div>
            <div class="flex items-center justify-between mt-1">
                <h1 class="text-2xl font-extrabold text-white">
                    Edit Category: {{ $category->name }}
                </h1>
                <a href="{{ route('admin.categories.preview', $category->id) }}" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs rounded-lg transition">
                    Preview Public Page
                </a>
            </div>
        </div>

        <form action="{{ route('admin.categories.update', $category->id) }}" method="POST" class="bg-slate-900/80 border border-slate-800 rounded-2xl p-6 sm:p-8 space-y-6 shadow-xl">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Category Name <span class="text-rose-400">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $category->name) }}"
                        required
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    >
                    @error('name') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        URL Slug
                    </label>
                    <input
                        type="text"
                        name="slug"
                        value="{{ old('slug', $category->slug) }}"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white font-mono focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    >
                    @error('slug') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Category Code (e.g. M4W)
                    </label>
                    <input
                        type="text"
                        name="code"
                        value="{{ old('code', $category->code) }}"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white font-mono focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Parent Category (Current Depth: {{ $category->depth }})
                    </label>
                    <select
                        name="parent_id"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    >
                        <option value="">-- None (Top Level Root) --</option>
                        @foreach($parentOptions as $parent)
                            <option value="{{ $parent['id'] }}" {{ (old('parent_id', $category->parent_id) == $parent['id']) ? 'selected' : '' }}>
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
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                >{{ old('description', $category->description) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                    Category Safety Guidelines & Compliance Notice
                </label>
                <textarea
                    name="safety_guidelines"
                    rows="3"
                    class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                >{{ old('safety_guidelines', $category->safety_guidelines) }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Display Order
                    </label>
                    <input
                        type="number"
                        name="display_order"
                        value="{{ old('display_order', $category->display_order) }}"
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
                        value="{{ old('min_age_override', $category->min_age_override) }}"
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
                        value="{{ old('icon', $category->icon) }}"
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
                            value="{{ old('meta_title', $category->seo_metadata['meta_title'] ?? '') }}"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white focus:ring-1 focus:ring-rose-500 focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Meta Description</label>
                        <input
                            type="text"
                            name="meta_description"
                            value="{{ old('meta_description', $category->seo_metadata['meta_description'] ?? '') }}"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white focus:ring-1 focus:ring-rose-500 focus:outline-none"
                        >
                    </div>
                </div>
            </div>

            <!-- Status Toggles -->
            <div class="pt-4 border-t border-slate-800 flex items-center space-x-6 text-xs text-slate-300">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-950 text-rose-600 focus:ring-rose-500">
                    <span class="font-semibold text-white">Active (Visible to public)</span>
                </label>

                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $category->is_featured) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-950 text-rose-600 focus:ring-rose-500">
                    <span class="font-semibold text-white">Featured Category</span>
                </label>
            </div>

            <div class="pt-5 border-t border-slate-800 flex items-center justify-between">
                <a href="{{ route('admin.categories.attributes', $category->id) }}" class="text-xs text-rose-400 hover:text-rose-300 font-semibold flex items-center space-x-1">
                    <span>Manage Dynamic Attributes &rarr;</span>
                </a>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.categories.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition shadow-lg">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.app>

