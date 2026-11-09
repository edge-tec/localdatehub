<div class="space-y-4">
    <!-- Category Groups Filter (depth 1) -->
    @if(!empty($groupCategories))
        <div class="flex flex-wrap gap-2">
            @foreach($groupCategories as $group)
                <button
                    type="button"
                    wire:click="selectGroup({{ $group['id'] }})"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold tracking-wide transition-all duration-200 border flex items-center space-x-1.5 {{ $selectedGroupId === $group['id'] ? 'bg-[#F20B83]/10 text-[#F20B83] border-[#F20B83]/30 font-bold shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300 hover:text-slate-900 hover:bg-slate-50' }}"
                >
                    <span>{{ $group['name'] }}</span>
                    @if($group['subcategories_count'] > 0)
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-slate-100 text-slate-400">
                            {{ $group['subcategories_count'] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>
    @endif

    <!-- Subcategories (depth 2) -->
    @if(!empty($subcategories))
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
            <span class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-2">
                Select Subcategory
            </span>
            <div class="flex flex-wrap gap-1.5">
                @foreach($subcategories as $sub)
                    <button
                        type="button"
                        wire:click="selectSubcategory({{ $sub['id'] }})"
                        class="px-3 py-1 rounded-md text-xs font-medium transition-all {{ $selectedSubcategoryId === $sub['id'] ? 'bg-[#F20B83] text-white font-bold shadow-xs' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100 hover:text-slate-900' }}"
                    >
                        @if(!empty($sub['code']))
                            <span class="font-bold text-[#F20B83] mr-1">[{{ $sub['code'] }}]</span>
                        @endif
                        {{ $sub['name'] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <input type="hidden" name="category" value="{{ $selectedCategorySlug }}">
</div>

