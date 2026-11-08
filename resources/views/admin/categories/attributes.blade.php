<x-layouts.app title="Dynamic Attributes: {{ $category->name }} — Admin | LocalAdZone">
    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <!-- Top Ribbon -->
        <div class="border-b border-slate-200 pb-5">
            <div class="flex items-center space-x-2 text-rose-500 font-semibold text-xs tracking-wider uppercase">
                <a href="{{ route('admin.categories.index') }}" class="hover:text-rose-600">Categories</a>
                <span>/</span>
                <a href="{{ route('admin.categories.edit', $category->id) }}" class="hover:text-rose-600">{{ $category->name }}</a>
                <span>/</span>
                <span>Dynamic Attributes</span>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-1">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900">
                        Category Attributes: {{ $category->name }}
                    </h1>
                    <p class="text-slate-400 text-xs mt-1">
                        Configure custom form inputs, validation rules, and search filters for this category.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.categories.edit', $category->id) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition">
                        &larr; Back to Category
                    </a>
                </div>
            </div>
        </div>

        @if(session('status'))
            <div class="p-4 bg-emerald-50 border border-emerald-600/40 rounded-xl text-xs text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <!-- Existing Direct Attributes List -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xl space-y-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                Direct Attributes for {{ $category->name }} ({{ $attributes->count() }})
            </h2>

            @if($attributes->isEmpty())
                <p class="text-xs text-slate-400">No direct attributes configured on this category yet.</p>
            @else
                <div class="space-y-4">
                    @foreach($attributes as $attr)
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-slate-900 text-sm">{{ $attr->name }}</span>
                                        <span class="font-mono text-xs px-2 py-0.5 rounded bg-white text-slate-600 border border-slate-200">
                                            {{ $attr->code }}
                                        </span>
                                        <span class="text-xs px-2 py-0.5 rounded bg-rose-500/10 text-rose-600 border border-rose-500/20 capitalize">
                                            {{ $attr->field_type }}
                                        </span>
                                        @if($attr->is_required)
                                            <span class="text-[10px] px-1.5 py-0.2 rounded bg-amber-500/10 text-amber-700 border border-amber-500/20">Required</span>
                                        @endif
                                        @if($attr->is_filterable)
                                            <span class="text-[10px] px-1.5 py-0.2 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20">Filterable</span>
                                        @endif
                                    </div>
                                    @if($attr->placeholder || $attr->help_text)
                                        <p class="text-xs text-slate-400 mt-1">
                                            {{ $attr->help_text ?: $attr->placeholder }}
                                        </p>
                                    @endif
                                </div>
                                <form action="{{ route('admin.categories.attributes.destroy', $attr->id) }}" method="POST" onsubmit="return confirm('Delete attribute \'{{ $attr->name }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-rose-600 hover:text-rose-600 p-1">
                                        Delete
                                    </button>
                                </form>
                            </div>

                            <!-- Options if select / radio / multiselect -->
                            @if(in_array($attr->field_type, ['select', 'radio', 'multiselect']))
                                <div class="pt-3 border-t border-slate-200 space-y-2">
                                    <span class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                                        Options ({{ $attr->options->count() }}):
                                    </span>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($attr->options as $opt)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-white border border-slate-200 text-xs text-slate-600">
                                                <span>{{ $opt->label }}</span>
                                                <span class="ml-1 text-[10px] font-mono text-slate-500">({{ $opt->value }})</span>
                                                <form action="{{ route('admin.categories.attributes.options.destroy', $opt->id) }}" method="POST" class="inline ml-1.5">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-rose-600 hover:text-rose-600 font-bold">&times;</button>
                                                </form>
                                            </span>
                                        @endforeach
                                    </div>

                                    <!-- Add Option Form -->
                                    <form action="{{ route('admin.categories.attributes.options.store', $attr->id) }}" method="POST" class="mt-2 flex items-center space-x-2">
                                        @csrf
                                        <input type="text" name="label" placeholder="Option label (e.g. Non-smoker)" required class="bg-white border border-slate-200 rounded-lg px-2.5 py-1 text-xs text-slate-900 focus:outline-none focus:ring-1 focus:ring-rose-500">
                                        <input type="text" name="value" placeholder="Value code (e.g. non_smoker)" required class="bg-white border border-slate-200 rounded-lg px-2.5 py-1 text-xs text-slate-900 font-mono focus:outline-none focus:ring-1 focus:ring-rose-500">
                                        <button type="submit" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-900 rounded-lg text-xs font-semibold">
                                            Add Option
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Add New Dynamic Attribute Form -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xl space-y-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                Add New Attribute to {{ $category->name }}
            </h2>

            <form action="{{ route('admin.categories.attributes.store', $category->id) }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs text-slate-600 font-semibold mb-1">
                            Attribute Name <span class="text-rose-600">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            required
                            placeholder="e.g. Smoking Habits"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:ring-1 focus:ring-rose-500 focus:outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-xs text-slate-600 font-semibold mb-1">
                            Attribute Code <span class="text-rose-600">*</span>
                        </label>
                        <input
                            type="text"
                            name="code"
                            required
                            placeholder="e.g. smoking"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 font-mono focus:ring-1 focus:ring-rose-500 focus:outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-xs text-slate-600 font-semibold mb-1">
                            Field Type <span class="text-rose-600">*</span>
                        </label>
                        <select
                            name="field_type"
                            required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:ring-1 focus:ring-rose-500 focus:outline-none"
                        >
                            <option value="text">Single-line Text</option>
                            <option value="textarea">Multi-line Textarea</option>
                            <option value="number">Numeric (e.g. height, age)</option>
                            <option value="select">Dropdown Select (One option)</option>
                            <option value="multiselect">Multi-Select (Multiple options)</option>
                            <option value="radio">Radio Buttons (One option)</option>
                            <option value="boolean">Checkbox / Toggle</option>
                            <option value="range">Numeric Range</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Placeholder Text</label>
                        <input
                            type="text"
                            name="placeholder"
                            placeholder="e.g. Select frequency..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:ring-1 focus:ring-rose-500 focus:outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Help Text / Subtitle</label>
                        <input
                            type="text"
                            name="help_text"
                            placeholder="e.g. Do you smoke or vape?"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:ring-1 focus:ring-rose-500 focus:outline-none"
                        >
                    </div>

                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Unit of Measure (Optional)</label>
                        <input
                            type="text"
                            name="unit"
                            placeholder="e.g. inches, lbs, yrs"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:ring-1 focus:ring-rose-500 focus:outline-none"
                        >
                    </div>
                </div>

                <div class="flex items-center space-x-6 text-xs text-slate-600 pt-2">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_required" value="1" class="rounded border-slate-200 bg-slate-50 text-rose-600 focus:ring-rose-500">
                        <span>Required Field for Posters</span>
                    </label>

                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_filterable" value="1" checked class="rounded border-slate-200 bg-slate-50 text-rose-600 focus:ring-rose-500">
                        <span>Show in Category Search Filters</span>
                    </label>

                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_searchable" value="1" checked class="rounded border-slate-200 bg-slate-50 text-rose-600 focus:ring-rose-500">
                        <span>Searchable in Keyword Index</span>
                    </label>
                </div>

                <div class="pt-3 flex justify-end">
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition shadow-md">
                        Add Attribute
                    </button>
                </div>
            </form>
        </div>

        <!-- Inherited & Global Attributes Reference -->
        @if($globalAttributes->isNotEmpty() || $inheritedAttributes->isNotEmpty())
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                    Global & Inherited Attributes in Effect
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($globalAttributes as $gAttr)
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900">{{ $gAttr->name }}</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-400">Global</span>
                            </div>
                            <span class="text-slate-400 text-[11px] block mt-1">Code: {{ $gAttr->code }} ({{ $gAttr->field_type }})</span>
                        </div>
                    @endforeach
                    @foreach($inheritedAttributes as $iAttr)
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900">{{ $iAttr->name }}</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-400">Inherited</span>
                            </div>
                            <span class="text-slate-400 text-[11px] block mt-1">Code: {{ $iAttr->code }} ({{ $iAttr->field_type }})</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>

