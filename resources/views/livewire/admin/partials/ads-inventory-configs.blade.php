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
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black {{ $activeInSlot > 0 ? 'bg-rose-50 text-[#F20B83] border border-rose-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                            <svg class="w-3 h-3 shrink-0 {{ $activeInSlot > 0 ? 'text-[#F20B83]' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
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

                        @php
                            $rotationLabels = [
                                'priority_weighted' => 'Priority + Weighted Rotation',
                                'random' => 'Randomized Rotation',
                                'round_robin' => 'Strict Round-Robin',
                            ];
                            $currentRotation = $placementConfigsForm[$slotKey]['rotation_mode'] ?? 'priority_weighted';
                        @endphp
                        <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1">Rotation Strategy</label>
                            <button type="button" @click="open = !open"
                                class="w-full flex items-center justify-between gap-2 rounded-xl bg-white hover:bg-slate-50 border px-3 py-2 text-xs font-bold text-slate-900 transition-all cursor-pointer"
                                :class="open ? 'border-[#F20B83] ring-4 ring-[#F20B83]/10' : 'border-slate-200'">
                                <span class="flex items-center gap-2 truncate">
                                    <svg class="w-3.5 h-3.5 text-[#F20B83] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span class="truncate">{{ $rotationLabels[$currentRotation] ?? 'Priority + Weighted Rotation' }}</span>
                                </span>
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180 text-[#F20B83]' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                class="absolute left-0 right-0 mt-1.5 z-40 rounded-2xl bg-white/95 backdrop-blur-xl border border-slate-200/90 shadow-[0_18px_40px_-12px_rgba(15,23,42,0.22)] p-1.5 space-y-0.5">
                                @foreach($rotationLabels as $rotKey => $rotLabel)
                                    <button type="button" wire:click="$set('placementConfigsForm.{{ $slotKey }}.rotation_mode', '{{ $rotKey }}')" @click="open = false"
                                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer {{ $currentRotation === $rotKey ? 'bg-rose-50 text-[#F20B83]' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <span>{{ $rotLabel }}</span>
                                        @if($currentRotation === $rotKey) <svg class="w-3.5 h-3.5 text-[#F20B83] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> @endif
                                    </button>
                                @endforeach
                            </div>
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
