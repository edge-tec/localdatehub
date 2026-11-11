{{-- Placement Slot Architecture & Dynamic Injection Frequency Controls --}}
<div class="space-y-6 mb-6">
    <div class="{{ $card }} p-5 border-l-4 border-l-[#F20B83]">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h4 class="text-sm font-black text-slate-950">Dynamic Slot Architecture &amp; Feed Injection Rules</h4>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Configure how frequently promotional ads appear inside classified feeds (e.g., 1 ad every 6 listings), rotation strategies, and session deduplication across all 13 slots.</p>
            </div>
            <button type="button" wire:click="openCampaignModal" class="px-3.5 py-2 rounded-xl bg-[#F20B83] hover:bg-[#D00972] text-white text-xs font-black shadow-xs shrink-0 cursor-pointer">
                + Launch Campaign Into Slot
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach(\App\Modules\Advertising\Models\AdCampaign::SLOT_CATALOG as $slotKey => $meta)
            @php
                $cfg = $placementConfigs->get($slotKey);
                $activeInSlot = \App\Modules\Advertising\Models\AdCampaignPlacement::where('placement_slot', $slotKey)
                    ->whereHas('campaign', fn ($q) => $q->eligibleForDelivery())
                    ->count();
            @endphp
            <div class="{{ $card }} p-4 flex flex-col justify-between border-t-4 {{ ($placementConfigsForm[$slotKey]['is_enabled'] ?? true) ? 'border-t-emerald-500' : 'border-t-slate-300' }}" wire:key="slot-cfg-{{ $slotKey }}">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-pink-50 text-[#F20B83] border border-pink-200">
                            {{ $meta['dimensions'] }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $activeInSlot > 0 ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-slate-100 text-slate-600' }}">
                            {{ $activeInSlot }} Live
                        </span>
                    </div>

                    <h5 class="text-sm font-black text-slate-950">{{ $meta['label'] }}</h5>
                    <p class="text-[11px] text-slate-600 font-medium mt-1 leading-relaxed">{{ $meta['description'] }}</p>

                    <div class="mt-3 p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5 text-xs">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-black text-slate-600 uppercase mb-1">1 Ad Every N Listings</label>
                                <input type="number" min="1" max="50" wire:model="placementConfigsForm.{{ $slotKey }}.insert_every_n" class="{{ $input }} py-1 text-xs font-bold" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-600 uppercase mb-1">Max Ads / Page</label>
                                <input type="number" min="0" max="20" wire:model="placementConfigsForm.{{ $slotKey }}.max_ads_per_page" class="{{ $input }} py-1 text-xs font-bold" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase mb-1">Rotation Mode</label>
                            <select wire:model="placementConfigsForm.{{ $slotKey }}.rotation_mode" class="{{ $input }} py-1 text-xs font-bold text-slate-900">
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

                <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <button type="button" wire:click="openCampaignModal(null, '{{ $slotKey }}')" class="text-[11px] font-bold text-[#F20B83] hover:underline cursor-pointer">
                        + Add To Slot
                    </button>
                    <button type="button" wire:click="savePlacementConfig('{{ $slotKey }}')" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-black transition cursor-pointer">
                        Save Rules
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>

