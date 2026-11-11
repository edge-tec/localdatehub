{{-- Placement Slot Architecture & Dynamic Injection Frequency Controls --}}
<div class="space-y-5">
    <!-- Top Architecture Banner -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs border-l-4 border-l-[#F20B83]">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-pink-50 border border-pink-200 text-[#F20B83] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    </span>
                    <h4 class="text-sm sm:text-base font-black text-slate-950">Dynamic Slot Architecture &amp; Feed Injection Rules</h4>
                </div>
                <p class="text-xs text-slate-600 font-medium leading-relaxed">
                    Configure insertion intervals (e.g., 1 ad every 6 listings), maximum ads per page, weighted or round-robin rotation, and session deduplication across all 13 slots.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button type="button" wire:click="saveAllPlacementConfigs" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>Save All Slot Rules</span>
                </button>
                <button type="button" wire:click="openCampaignModal" class="px-4 py-2.5 rounded-xl bg-[#F20B83] hover:bg-[#D00972] text-white text-xs font-black shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span>Launch Into Slot</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 13 Slots Responsive Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach(\App\Modules\Advertising\Models\AdCampaign::SLOT_CATALOG as $slotKey => $meta)
            @php
                $isEnabled = (bool) ($placementConfigsForm[$slotKey]['is_enabled'] ?? true);
                $activeInSlot = \App\Modules\Advertising\Models\AdCampaignPlacement::where('placement_slot', $slotKey)
                    ->whereHas('campaign', fn ($q) => $q->eligibleForDelivery())
                    ->count();
            @endphp
            <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col justify-between shadow-xs hover:shadow-md transition border-t-4 {{ $isEnabled ? 'border-t-emerald-500' : 'border-t-slate-300 opacity-85' }}" wire:key="slot-cfg-{{ $slotKey }}">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2.5">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-pink-50 text-[#F20B83] border border-pink-200">
                            {{ $meta['dimensions'] }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $activeInSlot > 0 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $activeInSlot > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                            <span>{{ $activeInSlot }} Active Live</span>
                        </span>
                    </div>

                    <h5 class="text-sm font-black text-slate-950">{{ $meta['label'] }}</h5>
                    <p class="text-xs text-slate-600 font-medium mt-1 leading-relaxed">{{ $meta['description'] }}</p>

                    <div class="mt-4 p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-3 text-xs">
                        <div class="grid grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[10px] font-black text-slate-600 uppercase mb-1">1 Ad Every N Listings</label>
                                <input type="number" min="1" max="50" wire:model="placementConfigsForm.{{ $slotKey }}.insert_every_n" class="w-full rounded-lg bg-white border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:border-[#F20B83] focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-600 uppercase mb-1">Max Ads / Page</label>
                                <input type="number" min="0" max="20" wire:model="placementConfigsForm.{{ $slotKey }}.max_ads_per_page" class="w-full rounded-lg bg-white border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:border-[#F20B83] focus:outline-none" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase mb-1">Rotation Strategy</label>
                            <select wire:model="placementConfigsForm.{{ $slotKey }}.rotation_mode" class="w-full rounded-lg bg-white border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:border-[#F20B83] focus:outline-none">
                                <option value="priority_weighted">Priority + Weighted Rotation</option>
                                <option value="random">Randomized Rotation</option>
                                <option value="round_robin">Strict Round-Robin</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <label class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-700 cursor-pointer">
                                <input type="checkbox" wire:model="placementConfigsForm.{{ $slotKey }}.dedupe_session" class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]" />
                                <span>Session Dedup</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-700 cursor-pointer">
                                <input type="checkbox" wire:model="placementConfigsForm.{{ $slotKey }}.is_enabled" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600" />
                                <span>Slot Enabled</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="pt-3.5 mt-4 border-t border-slate-100 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="$set('campaignSlotFilter', '{{ $slotKey }}'); setAdSubTab('campaigns')" class="text-[11px] font-bold text-slate-600 hover:text-slate-950 cursor-pointer">
                            View Ads &rarr;
                        </button>
                        <button type="button" wire:click="openCampaignModal(null, '{{ $slotKey }}')" class="text-[11px] font-black text-[#F20B83] hover:underline cursor-pointer">
                            + Add To Slot
                        </button>
                    </div>
                    <button type="button" wire:click="savePlacementConfig('{{ $slotKey }}')" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-black transition cursor-pointer">
                        Save Rules
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>
