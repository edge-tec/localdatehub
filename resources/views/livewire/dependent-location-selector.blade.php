<div class="space-y-4">
    <!-- Hidden inputs for traditional form submissions -->
    <input type="hidden" name="{{ $fieldNamePrefix ? $fieldNamePrefix . '[state_code]' : 'state_code' }}" value="{{ $selectedState }}">
    <input type="hidden" name="{{ $fieldNamePrefix ? $fieldNamePrefix . '[city_id]' : 'city_id' }}" value="{{ $selectedCityId }}">
    <input type="hidden" name="{{ $fieldNamePrefix ? $fieldNamePrefix . '[zip_code]' : 'zip_code' }}" value="{{ $zipCode }}">

    <!-- Fast ZIP Code Auto-Detector -->
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">
            Quick Auto-Fill by 5-Digit ZIP <span class="text-slate-500 font-normal">(Optional)</span>
        </label>
        <div class="relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="zipCode" 
                maxlength="5"
                pattern="[0-9]{5}"
                inputmode="numeric"
                placeholder="e.g. 78701 (Type to auto-select State & City)"
                class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2 text-xs font-mono text-slate-900 placeholder-slate-500 focus:outline-none focus:border-rose-500">
            <div wire:loading wire:target="zipCode" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <svg class="animate-spin h-3.5 w-3.5 text-rose-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- State and Dependent City Selectors Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- 1. Searchable State Selector -->
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">
                State / Territory <span class="text-rose-500">*</span>
            </label>
            <select 
                wire:model.live="selectedState" 
                class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500">
                <option value="">Select State or Territory</option>
                <optgroup label="US States & DC">
                    @foreach($states->where('is_territory', false) as $st)
                        <option value="{{ $st->code }}">{{ $st->name }} ({{ $st->code }})</option>
                    @endforeach
                </optgroup>
                @if($includeTerritories && $states->where('is_territory', true)->isNotEmpty())
                    <optgroup label="US Territories">
                        @foreach($states->where('is_territory', true) as $st)
                            <option value="{{ $st->code }}">{{ $st->name }} ({{ $st->code }})</option>
                        @endforeach
                    </optgroup>
                @endif
            </select>
        </div>

        <!-- 2. Dependent City Selector -->
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">
                City / Municipality <span class="text-rose-500">*</span>
            </label>
            @if(!$selectedState)
                <select disabled class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-xs text-slate-500 cursor-not-allowed">
                    <option>Select a State first...</option>
                </select>
            @else
                <select 
                    wire:model.live="selectedCityId" 
                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500">
                    <option value="">Select City in {{ $selectedState }}</option>
                    @foreach($cities as $ct)
                        <option value="{{ $ct->id }}">
                            {{ $ct->name }} @if($ct->is_popular) ★ @endif
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>
</div>

