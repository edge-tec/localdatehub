{{-- Enterprise Campaign Creation/Edit Modal + Responsive Preview/Postback Modal + Delete Confirmation --}}
@if($showCampaignModal)
    <div x-data="{ modalStep: 'general' }">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-pink-50 border border-pink-200 text-[#F20B83] flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                </span>
                <div>
                    <h3 class="text-base sm:text-lg font-black text-slate-950">
                        {{ $editingCampaignId ? 'Edit Promotional Campaign' : 'Create Promotional Advertising Campaign' }}
                    </h3>
                    <p class="text-[11px] font-medium text-slate-500">Configure creative assets, placements, targeting rules, and schedule caps.</p>
                </div>
            </div>
            <button type="button" wire:click="closeModals" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer" aria-label="Close modal">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Stepper Tab Bar -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 p-1.5 rounded-xl bg-slate-100 border border-slate-200 mb-4">
            @foreach([
                'general' => '1. General',
                'creative' => '2. Creative & Media',
                'targeting' => '3. Slots & Targeting',
                'schedule' => '4. Schedule & Budget',
            ] as $stepKey => $stepLabel)
                <button
                    type="button"
                    @click="modalStep = '{{ $stepKey }}'"
                    :class="modalStep === '{{ $stepKey }}' ? 'bg-white text-[#F20B83] shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                    class="px-3 py-2 rounded-lg text-xs transition text-center cursor-pointer"
                >
                    {{ $stepLabel }}
                </button>
            @endforeach
        </div>

        <form wire:submit="saveCampaign" class="space-y-4">
            <div class="max-h-[62vh] overflow-y-auto pr-1 space-y-4">
                <!-- STEP 1: General Information -->
                <div x-show="modalStep === 'general'" class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3.5">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">1. General Campaign Information</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Campaign Name *</label>
                            <input type="text" wire:model="campaignForm.name" class="{{ $input }}" placeholder="e.g. Summer VIP Dating Partner" required>
                            @error('campaignForm.name')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Campaign Type *</label>
                            <select wire:model.live="campaignForm.campaign_type" class="{{ $input }} text-xs font-bold text-slate-900">
                                <option value="banner">Banner Advertisement (Image / Native)</option>
                                <option value="affiliate">Affiliate Offer Campaign (CPA / CPL / CPS)</option>
                                <option value="hybrid">Hybrid Banner + Affiliate Offer</option>
                            </select>
                            @error('campaignForm.campaign_type')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Advertiser / Brand Name</label>
                            <input type="text" wire:model="campaignForm.advertiser_name" class="{{ $input }}" placeholder="e.g. MatchPartner Network">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Disclosure Badge Label *</label>
                            <select wire:model="campaignForm.disclosure_label" class="{{ $input }} text-xs font-bold text-slate-900">
                                @foreach(\App\Modules\Advertising\Models\AdCreative::DISCLOSURE_LABELS as $lbl)
                                    <option value="{{ $lbl }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-800 mb-1">Campaign Description</label>
                        <textarea wire:model="campaignForm.description" rows="2" class="{{ $input }} text-xs" placeholder="Short summary shown internally or as fallback ad copy..."></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-800 mb-1">Internal Campaign Notes</label>
                        <textarea wire:model="campaignForm.internal_notes" rows="2" class="{{ $input }} text-xs" placeholder="Account manager contact, IO number, payout rate notes..."></textarea>
                    </div>
                </div>

                <!-- STEP 2: Format Creative Configuration -->
                <div x-show="modalStep === 'creative'" x-cloak class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3.5">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">
                        2. {{ ($campaignForm['campaign_type'] ?? 'banner') === 'affiliate' ? 'Affiliate Offer Creative & Tracking' : 'Banner & Promotional Creative' }}
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">{{ ($campaignForm['campaign_type'] ?? 'banner') === 'affiliate' ? 'Affiliate Offer Title' : 'Advertisement Title' }}</label>
                            <input type="text" wire:model="campaignForm.creative_title" class="{{ $input }}" placeholder="Headline displayed on native cards">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">CTA Button Text</label>
                            <input type="text" wire:model="campaignForm.cta_text" class="{{ $input }}" placeholder="Learn More / Claim Offer / Join Now">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-800 mb-1">Short Promotional Description</label>
                        <textarea wire:model="campaignForm.creative_description" rows="2" class="{{ $input }} text-xs" placeholder="Compelling 1-2 line promotional copy shown below headline..."></textarea>
                    </div>

                    @if(($campaignForm['campaign_type'] ?? 'banner') !== 'affiliate')
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Destination URL</label>
                            <input type="url" wire:model="campaignForm.destination_url" class="{{ $input }}" placeholder="https://advertiser.com/landing-page">
                            @error('campaignForm.destination_url')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <!-- Desktop & Mobile Banner Upload Zone (Full Uncropped Banner Preview) -->
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Desktop Banner Uploader -->
                        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <label class="block text-xs font-black text-slate-900">Desktop Banner Image</label>
                                    <span class="text-[11px] font-semibold text-slate-400">Recommended: 728&times;90 or 1200&times;200 (Displayed at full aspect ratio)</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if(!empty($campaignForm['desktop_banner_url']))
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-rose-50 text-[#F20B83] border border-rose-200 text-[10px] font-black uppercase tracking-wider">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            <span>Uploaded</span>
                                        </span>
                                        <button type="button" wire:click="clearCampaignMedia('desktop_banner_url')" class="px-2.5 py-0.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-[10px] font-black text-rose-600 border border-rose-200 transition cursor-pointer">Remove</button>
                                    @endif
                                </div>
                            </div>

                            @if(!empty($campaignForm['desktop_banner_url']))
                                <div class="rounded-xl border border-slate-200 bg-slate-950/[0.03] p-2.5">
                                    <img src="{{ $campaignForm['desktop_banner_url'] }}" alt="Desktop Banner Preview" class="w-full h-auto max-h-72 object-contain mx-auto rounded-lg block" />
                                </div>
                            @endif

                            <label class="group flex flex-col items-center justify-center gap-1.5 p-3 rounded-2xl border-2 border-dashed border-slate-300 hover:border-[#F20B83] bg-slate-50/70 hover:bg-rose-50/40 transition-all cursor-pointer text-center">
                                <input type="file" wire:model="campaignDesktopBannerUpload" accept=".jpg,.jpeg,.png,.webp,.gif,image/*" class="sr-only" />
                                <span wire:loading.remove wire:target="campaignDesktopBannerUpload" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-[#F20B83] to-rose-600 group-hover:from-[#D00972] group-hover:to-rose-700 text-white text-[11px] font-black shadow-xs transition">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    <span>{{ !empty($campaignForm['desktop_banner_url']) ? 'Change Desktop Banner' : 'Upload Desktop Banner' }}</span>
                                </span>
                                <span wire:loading wire:target="campaignDesktopBannerUpload" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 text-white text-[11px] font-black">
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                                    <span>Uploading banner...</span>
                                </span>
                                <span class="text-[10px] font-semibold text-slate-500">JPG, PNG, WEBP or GIF (Max 5MB)</span>
                            </label>

                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Or Paste Banner URL</span>
                                <input type="text" wire:model="campaignForm.desktop_banner_url" class="{{ $input }} text-xs" placeholder="https://... or /storage/..." />
                            </div>
                            @error('campaignDesktopBannerUpload')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror
                            @error('campaignForm.desktop_banner_url')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror
                        </div>

                        <!-- Mobile Banner Uploader -->
                        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <label class="block text-xs font-black text-slate-900">Mobile Banner Image</label>
                                    <span class="text-[11px] font-semibold text-slate-400">Recommended: 320&times;100 or 320&times;250 (Displayed at full aspect ratio)</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if(!empty($campaignForm['mobile_banner_url']))
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-rose-50 text-[#F20B83] border border-rose-200 text-[10px] font-black uppercase tracking-wider">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            <span>Uploaded</span>
                                        </span>
                                        <button type="button" wire:click="clearCampaignMedia('mobile_banner_url')" class="px-2.5 py-0.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-[10px] font-black text-rose-600 border border-rose-200 transition cursor-pointer">Remove</button>
                                    @endif
                                </div>
                            </div>

                            @if(!empty($campaignForm['mobile_banner_url']))
                                <div class="rounded-xl border border-slate-200 bg-slate-950/[0.03] p-2.5">
                                    <img src="{{ $campaignForm['mobile_banner_url'] }}" alt="Mobile Banner Preview" class="w-full h-auto max-h-72 object-contain mx-auto rounded-lg block" />
                                </div>
                            @endif

                            <label class="group flex flex-col items-center justify-center gap-1.5 p-3 rounded-2xl border-2 border-dashed border-slate-300 hover:border-[#F20B83] bg-slate-50/70 hover:bg-rose-50/40 transition-all cursor-pointer text-center">
                                <input type="file" wire:model="campaignMobileBannerUpload" accept=".jpg,.jpeg,.png,.webp,.gif,image/*" class="sr-only" />
                                <span wire:loading.remove wire:target="campaignMobileBannerUpload" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-900 group-hover:bg-[#F20B83] text-white text-[11px] font-black shadow-xs transition">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    <span>{{ !empty($campaignForm['mobile_banner_url']) ? 'Change Mobile Banner' : 'Upload Mobile Banner' }}</span>
                                </span>
                                <span wire:loading wire:target="campaignMobileBannerUpload" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 text-white text-[11px] font-black">
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                                    <span>Uploading banner...</span>
                                </span>
                                <span class="text-[10px] font-semibold text-slate-500">JPG, PNG, WEBP or GIF (Max 5MB)</span>
                            </label>

                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Or Paste Banner URL</span>
                                <input type="text" wire:model="campaignForm.mobile_banner_url" class="{{ $input }} text-xs" placeholder="https://... or /storage/..." />
                            </div>
                            @error('campaignMobileBannerUpload')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror
                            @error('campaignForm.mobile_banner_url')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-800 mb-1">Image Alt Text</label>
                        <input type="text" wire:model="campaignForm.alt_text" class="{{ $input }}" placeholder="Descriptive accessible alt text">
                    </div>

                    @if(in_array($campaignForm['campaign_type'] ?? 'banner', ['affiliate', 'hybrid'], true))
                        <div class="p-3.5 rounded-xl bg-purple-50/60 border border-purple-200 space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-black text-purple-950 mb-1">Affiliate Brand Name</label>
                                    <input type="text" wire:model="campaignForm.affiliate_brand_name" class="{{ $input }}" placeholder="Partner brand shown on offer card">
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-purple-950 mb-1">Optional Offer Badge</label>
                                    <input type="text" wire:model="campaignForm.offer_badge" class="{{ $input }}" placeholder="e.g. Exclusive 50% OFF, VIP Trial">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-purple-950 mb-1">Affiliate Tracking URL (Preserved 100% Intact)</label>
                                <input type="url" wire:model="campaignForm.affiliate_tracking_url" class="{{ $input }} font-mono text-xs" placeholder="https://network.example.com/click?a=123&o=456&aff_sub=site">
                                <span class="text-[10px] text-purple-800 font-semibold">Existing tracking queries &amp; encoded URLs are never stripped or mutated.</span>
                                @error('campaignForm.affiliate_tracking_url')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-black text-purple-950">Promotional Thumbnail</label>
                                        @if(!empty($campaignForm['thumbnail_url']))
                                            <button type="button" wire:click="clearCampaignMedia('thumbnail_url')" class="text-[10px] font-bold text-rose-600 hover:underline cursor-pointer">Remove</button>
                                        @endif
                                    </div>
                                    @if(!empty($campaignForm['thumbnail_url']))
                                        <div class="rounded-lg border border-purple-200 bg-white p-1.5 mb-1.5">
                                            <img src="{{ $campaignForm['thumbnail_url'] }}" alt="Thumbnail Preview" class="w-full h-auto max-h-40 object-contain mx-auto rounded block" />
                                        </div>
                                    @endif
                                    <label class="flex items-center justify-center gap-1.5 w-full px-3 py-1.5 mb-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-[11px] font-black transition cursor-pointer">
                                        <input type="file" wire:model="campaignThumbnailUpload" accept=".jpg,.jpeg,.png,.webp,.gif,image/*" class="sr-only" />
                                        <svg wire:loading.remove wire:target="campaignThumbnailUpload" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                        <span wire:loading.remove wire:target="campaignThumbnailUpload">Upload Thumbnail</span>
                                        <span wire:loading wire:target="campaignThumbnailUpload">Uploading...</span>
                                    </label>
                                    <input type="text" wire:model="campaignForm.thumbnail_url" class="{{ $input }} text-xs" placeholder="https://... or /storage/...">
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-purple-950 mb-1">Click ID Param Key</label>
                                    <input type="text" wire:model="campaignForm.click_id_param" class="{{ $input }} text-xs font-mono" placeholder="e.g. click_id or s2">
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-purple-950 mb-1">SubID Param Key</label>
                                    <input type="text" wire:model="campaignForm.subid_param" class="{{ $input }} text-xs font-mono" placeholder="e.g. subid or aff_sub">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-purple-950 mb-1">Offer Expiration Date (Optional)</label>
                                <input type="datetime-local" wire:model="campaignForm.offer_expires_at" class="{{ $input }} text-xs">
                            </div>
                        </div>
                    @endif
                </div>

                <!-- STEP 3: Placements & Targeting -->
                <div x-show="modalStep === 'targeting'" x-cloak class="space-y-4">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">3. Placement Positions *</h4>
                            <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-bold">
                                <button type="button" wire:click="selectPlacementGroup('all')" class="px-2 py-1 rounded-md bg-white border border-slate-200 hover:border-pink-300 text-slate-700 cursor-pointer">Select All</button>
                                <button type="button" wire:click="selectPlacementGroup('feeds')" class="px-2 py-1 rounded-md bg-white border border-slate-200 hover:border-pink-300 text-slate-700 cursor-pointer">Feed Slots</button>
                                <button type="button" wire:click="selectPlacementGroup('banners')" class="px-2 py-1 rounded-md bg-white border border-slate-200 hover:border-pink-300 text-slate-700 cursor-pointer">Banner Slots</button>
                                <button type="button" wire:click="selectPlacementGroup('clear')" class="px-2 py-1 rounded-md bg-white border border-slate-200 hover:border-rose-300 text-rose-600 cursor-pointer">Clear</button>
                            </div>
                        </div>
                        @error('campaignForm.placements')<p class="text-xs text-rose-600 font-bold">{{ $message }}</p>@enderror
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach(\App\Modules\Advertising\Models\AdCampaign::SLOT_CATALOG as $slotKey => $slotMeta)
                                <label class="flex items-start gap-2 p-2 rounded-xl bg-white border border-slate-200 hover:border-pink-300 cursor-pointer text-xs">
                                    <input type="checkbox" value="{{ $slotKey }}" wire:model="campaignForm.placements" class="mt-0.5 rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]" />
                                    <span>
                                        <strong class="block text-slate-900 text-[11px]">{{ $slotMeta['label'] }}</strong>
                                        <span class="text-[10px] text-slate-500">{{ $slotMeta['dimensions'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">4. Audience Targeting (All Traffic by Default)</h4>
                            <span class="text-[11px] font-semibold text-slate-500">Pick specific targets or choose <strong class="text-emerald-700">All</strong> for nationwide reach.</span>
                        </div>

                        @php
                            $targetDimensions = [
                                [
                                    'key' => 'countries',
                                    'field' => 'target_countries',
                                    'label' => 'Target Countries (URL Scope)',
                                    'all_label' => 'All Countries',
                                    'badge_all' => 'All Countries',
                                    'placeholder' => 'Filter countries...',
                                    'items' => ($targetingOptions['countries'] ?? collect())->map(fn ($ctry) => [
                                        'value' => strtoupper((string) $ctry->code),
                                        'label' => $ctry->name . ' (' . strtoupper((string) $ctry->code) . ' · /' . strtolower((string) ($ctry->slug ?: $ctry->code)) . ')',
                                    ]),
                                ],
                                [
                                    'key' => 'states',
                                    'field' => 'target_states',
                                    'label' => 'Target States',
                                    'all_label' => 'All States (Nationwide)',
                                    'badge_all' => 'All States',
                                    'placeholder' => 'Filter states...',
                                    'items' => ($targetingOptions['states'] ?? collect())->map(fn ($st) => [
                                        'value' => (string) $st->code,
                                        'label' => $st->name . ' (' . $st->code . ($st->country ? ' · ' . $st->country->code : '') . ')',
                                    ]),
                                ],
                                [
                                    'key' => 'categories',
                                    'field' => 'target_categories',
                                    'label' => 'Target Categories',
                                    'all_label' => 'All Categories',
                                    'badge_all' => 'All Categories',
                                    'placeholder' => 'Filter categories...',
                                    'items' => ($targetingOptions['categories'] ?? collect())->map(fn ($cat) => [
                                        'value' => (string) $cat->id,
                                        'label' => (string) $cat->name,
                                    ]),
                                ],
                                [
                                    'key' => 'cities',
                                    'field' => 'target_cities',
                                    'label' => 'Target Cities',
                                    'all_label' => 'All Cities',
                                    'badge_all' => 'All Cities',
                                    'placeholder' => 'Filter cities...',
                                    'items' => ($targetingOptions['cities'] ?? collect())->map(fn ($ct) => [
                                        'value' => (string) $ct->id,
                                        'label' => $ct->name . ($ct->state ? ', ' . $ct->state->code : ''),
                                    ]),
                                ],
                            ];
                        @endphp

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            @foreach($targetDimensions as $dim)
                                <div
                                    x-data="{
                                        search: '',
                                        isAll() {
                                            const vals = $wire.campaignForm?.['{{ $dim['field'] }}'] || [];
                                            return vals.length === 0 || vals.includes('ALL');
                                        },
                                        selectedCount() {
                                            return ($wire.campaignForm?.['{{ $dim['field'] }}'] || []).filter((v) => v !== 'ALL').length;
                                        }
                                    }"
                                    class="rounded-xl border border-slate-200 bg-white overflow-hidden flex flex-col"
                                >
                                    <div class="px-2.5 py-2 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between gap-1.5">
                                        <label class="text-xs font-black text-slate-800 truncate">{{ $dim['label'] }}</label>
                                        <span
                                            x-show="isAll()"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0"
                                        >
                                            {{ $dim['badge_all'] }}
                                        </span>
                                        <span
                                            x-show="!isAll()"
                                            x-cloak
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-pink-50 text-[#F20B83] border border-pink-200 shrink-0"
                                            x-text="selectedCount() + ' Selected'"
                                        ></span>
                                    </div>

                                    <div class="px-2 py-1.5 border-b border-slate-100 flex items-center justify-between gap-1 bg-white">
                                        <div class="flex items-center gap-1 text-[10px] font-bold shrink-0">
                                            <button
                                                type="button"
                                                wire:click="selectTargetingScope('{{ $dim['key'] }}', 'all')"
                                                @click="search = ''"
                                                :class="isAll() ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-white text-slate-600 border-slate-200 hover:border-pink-300'"
                                                class="px-2 py-0.5 rounded-md border transition cursor-pointer"
                                            >
                                                {{ $dim['badge_all'] }}
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="selectTargetingScope('{{ $dim['key'] }}', 'every')"
                                                class="px-2 py-0.5 rounded-md bg-white border border-slate-200 hover:border-pink-300 text-slate-700 transition cursor-pointer"
                                            >
                                                Select All
                                            </button>
                                        </div>
                                        <input
                                            type="text"
                                            x-model="search"
                                            placeholder="{{ $dim['placeholder'] }}"
                                            class="w-full min-w-0 px-2 py-0.5 rounded-md border border-slate-200 text-[11px] text-slate-700 focus:outline-none focus:border-pink-400"
                                        >
                                    </div>

                                    <div class="max-h-36 overflow-y-auto divide-y divide-slate-100 text-xs">
                                        <button
                                            type="button"
                                            wire:click="selectTargetingScope('{{ $dim['key'] }}', 'all')"
                                            @click="search = ''"
                                            :class="isAll() ? 'bg-emerald-50/70 text-emerald-900 font-black' : 'bg-white text-slate-700 hover:bg-slate-50 font-semibold'"
                                            class="w-full px-2.5 py-1.5 flex items-center justify-between text-left transition cursor-pointer"
                                        >
                                            <span class="truncate">{{ $dim['all_label'] }}</span>
                                            <span
                                                :class="isAll() ? 'bg-emerald-600 border-emerald-600 text-white' : 'bg-white border-slate-300 text-transparent'"
                                                class="w-4 h-4 rounded-sm border inline-flex items-center justify-center shrink-0 text-[10px] font-black"
                                            >&check;</span>
                                        </button>

                                        @foreach($dim['items'] as $opt)
                                            <label
                                                data-filter="{{ strtolower($opt['label']) }}"
                                                x-show="!search || $el.dataset.filter.includes(search.toLowerCase())"
                                                class="px-2.5 py-1.5 flex items-center justify-between gap-2 hover:bg-pink-50/40 cursor-pointer text-slate-700"
                                            >
                                                <span class="truncate text-[11px] font-medium">{{ $opt['label'] }}</span>
                                                <input
                                                    type="checkbox"
                                                    value="{{ $opt['value'] }}"
                                                    wire:model="campaignForm.{{ $dim['field'] }}"
                                                    class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83] shrink-0"
                                                />
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Target ZIP Codes (Comma separated)</label>
                                <input type="text" wire:model="campaignForm.target_zip_codes" class="{{ $input }} text-xs" placeholder="e.g. 78701, 90210, 10001">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-800 mb-1">Target Devices (Check to restrict)</label>
                                <div class="flex flex-wrap items-center gap-4 pt-1.5 text-xs font-bold text-slate-800">
                                    @foreach(['desktop' => 'Desktop', 'mobile' => 'Mobile', 'tablet' => 'Tablet'] as $devKey => $devLabel)
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="checkbox" value="{{ $devKey }}" wire:model="campaignForm.target_devices" class="rounded border-slate-300 text-[#F20B83] focus:ring-[#F20B83]" />
                                            <span>{{ $devLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 4: Scheduling, Status, Priority, Budgets & Frequency Capping -->
                <div x-show="modalStep === 'schedule'" x-cloak class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3.5">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">5. Scheduling, Budgets &amp; Delivery Governance</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Campaign Status *</label>
                            <select wire:model="campaignForm.status" class="{{ $input }} text-xs font-bold text-slate-900">
                                @foreach(\App\Modules\Advertising\Models\AdCampaign::STATUSES as $st)
                                    <option value="{{ $st }}">{{ ucwords(str_replace('_', ' ', $st)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Priority (Higher Serves First)</label>
                            <input type="number" min="0" max="1000" wire:model="campaignForm.priority" class="{{ $input }} text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Session Frequency Cap</label>
                            <input type="number" min="1" max="100" wire:model="campaignForm.frequency_cap_per_session" class="{{ $input }} text-xs" placeholder="e.g. 2 per session">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Start Date &amp; Time</label>
                            <input type="datetime-local" wire:model="campaignForm.starts_at" class="{{ $input }} text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">End Date &amp; Time</label>
                            <input type="datetime-local" wire:model="campaignForm.ends_at" class="{{ $input }} text-xs">
                            @error('campaignForm.ends_at')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Daily Budget ($)</label>
                            <input type="number" step="0.01" min="0" wire:model="campaignForm.daily_budget_dollars" class="{{ $input }} text-xs" placeholder="50.00">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Total Budget ($)</label>
                            <input type="number" step="0.01" min="0" wire:model="campaignForm.total_budget_dollars" class="{{ $input }} text-xs" placeholder="1000.00">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Max Impressions</label>
                            <input type="number" min="1" wire:model="campaignForm.max_impressions" class="{{ $input }} text-xs" placeholder="Unlimited">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-800 mb-1">Max Clicks</label>
                            <input type="number" min="1" wire:model="campaignForm.max_clicks" class="{{ $input }} text-xs" placeholder="Unlimited">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Validation Summary (visible across any step) -->
            @if($errors->any())
                <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 font-bold">
                    Please review highlighted fields across the tabs before saving.
                </div>
            @endif

            <div class="pt-3 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 text-[11px] font-bold text-slate-500">
                    <button type="button" x-show="modalStep !== 'general'" @click="modalStep = modalStep === 'schedule' ? 'targeting' : (modalStep === 'targeting' ? 'creative' : 'general')" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer">&larr; Previous</button>
                    <button type="button" x-show="modalStep !== 'schedule'" @click="modalStep = modalStep === 'general' ? 'creative' : (modalStep === 'creative' ? 'targeting' : 'schedule')" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer">Next Step &rarr;</button>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-black cursor-pointer">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#F20B83] hover:bg-[#D00972] text-white text-xs font-black shadow-xs cursor-pointer">
                        {{ $editingCampaignId ? 'Save Campaign Updates' : 'Launch Promotional Campaign' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endif

@if($showPreviewCampaignModal && $previewCampaignData)
    <div x-data="{ viewport: 'desktop', copied: false }">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
            <h3 class="text-base sm:text-lg font-black text-slate-950">Campaign Preview &amp; S2S Postback Setup</h3>
            <button type="button" wire:click="closeModals" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer" aria-label="Close preview">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="space-y-4 text-xs">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-black text-slate-900 text-sm">{{ $previewCampaignData['name'] }}</span>
                        <span class="px-2 py-0.5 rounded-full bg-pink-100 text-[#F20B83] font-black uppercase text-[10px]">{{ $previewCampaignData['campaign_type'] }}</span>
                    </div>
                    <p class="text-slate-600 mt-1">
                        Impressions: <strong>{{ number_format($previewCampaignData['impressions']) }}</strong> ·
                        Clicks: <strong>{{ number_format($previewCampaignData['clicks']) }}</strong> ·
                        Conversions: <strong>{{ number_format($previewCampaignData['conversions']) }}</strong> ·
                        CTR: <strong>{{ $previewCampaignData['ctr'] }}%</strong>
                    </p>
                </div>
                <div class="inline-flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-200 shrink-0">
                    <button type="button" @click="viewport = 'desktop'" :class="viewport === 'desktop' ? 'bg-slate-900 text-white' : 'text-slate-600'" class="px-3 py-1 rounded-lg text-[11px] font-bold transition cursor-pointer">Desktop</button>
                    <button type="button" @click="viewport = 'mobile'" :class="viewport === 'mobile' ? 'bg-slate-900 text-white' : 'text-slate-600'" class="px-3 py-1 rounded-lg text-[11px] font-bold transition cursor-pointer">Mobile</button>
                </div>
            </div>

            <div :class="viewport === 'mobile' ? 'max-w-[320px]' : 'max-w-md'" class="mx-auto transition-all duration-200">
                <x-sponsored-listing-card :ad="$previewCampaignData['payload']" />
            </div>

            @php
                $postbackFullUrl = $previewCampaignData['postback_url'] . '?secret=' . $previewCampaignData['postback_secret'] . '&click_id={click_id}&tx_id={tx_id}&payout={payout}';
            @endphp
            <div class="p-4 rounded-2xl bg-slate-900 text-slate-100 space-y-2 font-mono text-[11px]">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-pink-400 font-bold uppercase font-sans text-xs">S2S Conversion Postback Endpoint</span>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText(@js($postbackFullUrl)); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-sans text-[10px] font-bold transition cursor-pointer"
                    >
                        <span x-text="copied ? 'Copied!' : 'Copy URL'">Copy URL</span>
                    </button>
                </div>
                <div class="break-all select-all bg-slate-950 p-2.5 rounded-lg border border-slate-700">
                    {{ $postbackFullUrl }}
                </div>
                <p class="text-slate-400 font-sans text-[11px]">Always include the secret parameter or <code>X-Ad-Postback-Secret</code> header. Unauthenticated requests are rejected with HTTP 403.</p>
            </div>
        </div>
    </div>
@endif

@if($showDeleteCampaignModal)
    <div class="space-y-4">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </span>
            <div>
                <h3 class="text-base sm:text-lg font-black text-slate-950">Confirm Campaign Deletion</h3>
                <p class="text-xs text-slate-600">This campaign will immediately stop serving across all classified placements.</p>
            </div>
        </div>
        <p class="text-xs text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-200">
            Delete campaign: <strong>{{ $deleteCampaignName }}</strong>?
        </p>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-black cursor-pointer">Cancel</button>
            <button type="button" wire:click="deleteCampaign" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black cursor-pointer">Delete Campaign</button>
        </div>
    </div>
@endif
