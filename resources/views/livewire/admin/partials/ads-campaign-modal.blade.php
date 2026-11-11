{{-- Enterprise Campaign Creation/Edit Modal + Preview/Postback Modal + Delete Confirmation --}}
@if($showCampaignModal)
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
        <h3 class="text-lg font-black text-slate-950">
            {{ $editingCampaignId ? 'Edit Promotional Campaign' : 'Create Promotional Advertising Campaign' }}
        </h3>
        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
    </div>

    <form wire:submit="saveCampaign" class="space-y-5 max-h-[75vh] overflow-y-auto pr-1">
        <!-- 1. General Information -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
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

        <!-- 2. Format Creative Configuration -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black text-slate-800 mb-1">Desktop Banner Image (Upload or URL)</label>
                        <input type="file" wire:model="campaignDesktopBannerUpload" accept=".jpg,.jpeg,.png,.webp,.gif" class="block w-full text-[11px] text-slate-600 mb-1.5" />
                        <input type="text" wire:model="campaignForm.desktop_banner_url" class="{{ $input }} text-xs" placeholder="https://... or uploaded /storage/..." />
                        @error('campaignDesktopBannerUpload')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                        @error('campaignForm.desktop_banner_url')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-800 mb-1">Mobile Banner Image (Upload or URL)</label>
                        <input type="file" wire:model="campaignMobileBannerUpload" accept=".jpg,.jpeg,.png,.webp,.gif" class="block w-full text-[11px] text-slate-600 mb-1.5" />
                        <input type="text" wire:model="campaignForm.mobile_banner_url" class="{{ $input }} text-xs" placeholder="https://... or uploaded /storage/..." />
                        @error('campaignMobileBannerUpload')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                        @error('campaignForm.mobile_banner_url')<p class="text-xs text-rose-600 mt-1 font-bold">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-slate-800 mb-1">Image Alt Text</label>
                    <input type="text" wire:model="campaignForm.alt_text" class="{{ $input }}" placeholder="Descriptive accessible alt text">
                </div>
            @endif

            @if(in_array($campaignForm['campaign_type'] ?? 'banner', ['affiliate', 'hybrid'], true))
                <div class="p-3 rounded-xl bg-purple-50/60 border border-purple-200 space-y-3">
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
                            <label class="block text-xs font-black text-purple-950 mb-1">Promotional Thumbnail</label>
                            <input type="file" wire:model="campaignThumbnailUpload" accept=".jpg,.jpeg,.png,.webp,.gif" class="block w-full text-[11px] text-slate-600 mb-1" />
                            <input type="text" wire:model="campaignForm.thumbnail_url" class="{{ $input }} text-xs" placeholder="https://... or /storage/...">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-purple-950 mb-1">Click ID Param Key (Optional)</label>
                            <input type="text" wire:model="campaignForm.click_id_param" class="{{ $input }} text-xs font-mono" placeholder="e.g. click_id or s2">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-purple-950 mb-1">SubID Param Key (Optional)</label>
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

        <!-- 3. Placement Positions -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">3. Placement Positions * (Select 1 or More)</h4>
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

        <!-- 4. Geo, Category & Device Targeting -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">4. Audience Targeting (Leave Empty for All Traffic)</h4>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-black text-slate-800 mb-1">Target States (Multi-select)</label>
                    <select multiple wire:model="campaignForm.target_states" class="{{ $input }} text-xs h-28">
                        @foreach(($targetingOptions['states'] ?? collect()) as $st)
                            <option value="{{ $st->code }}">{{ $st->name }} ({{ $st->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-800 mb-1">Target Categories (Multi-select)</label>
                    <select multiple wire:model="campaignForm.target_categories" class="{{ $input }} text-xs h-28">
                        @foreach(($targetingOptions['categories'] ?? collect()) as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-800 mb-1">Target Cities (Multi-select)</label>
                    <select multiple wire:model="campaignForm.target_cities" class="{{ $input }} text-xs h-28">
                        @foreach(($targetingOptions['cities'] ?? collect()) as $ct)
                            <option value="{{ $ct->id }}">{{ $ct->name }}{{ $ct->state ? ', ' . $ct->state->code : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-black text-slate-800 mb-1">Target ZIP Codes (Comma separated)</label>
                    <input type="text" wire:model="campaignForm.target_zip_codes" class="{{ $input }} text-xs" placeholder="e.g. 78701, 90210, 10001">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-800 mb-1">Target Devices (Check to restrict)</label>
                    <div class="flex items-center gap-4 pt-1.5 text-xs font-bold text-slate-800">
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

        <!-- 5. Scheduling, Status, Priority, Budgets & Frequency Capping -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
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

        <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-black">Cancel</button>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#F20B83] hover:bg-[#D00972] text-white text-xs font-black shadow-xs cursor-pointer">
                {{ $editingCampaignId ? 'Save Campaign Updates' : 'Launch Promotional Campaign' }}
            </button>
        </div>
    </form>
@endif

@if($showPreviewCampaignModal && $previewCampaignData)
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
        <h3 class="text-lg font-black text-slate-950">Campaign Preview &amp; S2S Postback Setup</h3>
        <button type="button" wire:click="closeModals" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
    </div>

    <div class="space-y-4 text-xs">
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
            <div class="flex items-center justify-between">
                <span class="font-black text-slate-900 text-sm">{{ $previewCampaignData['name'] }}</span>
                <span class="px-2 py-0.5 rounded-full bg-pink-100 text-[#F20B83] font-black uppercase text-[10px]">{{ $previewCampaignData['campaign_type'] }}</span>
            </div>
            <p class="text-slate-600">
                Impressions: <strong>{{ number_format($previewCampaignData['impressions']) }}</strong> ·
                Clicks: <strong>{{ number_format($previewCampaignData['clicks']) }}</strong> ·
                Conversions: <strong>{{ number_format($previewCampaignData['conversions']) }}</strong> ·
                CTR: <strong>{{ $previewCampaignData['ctr'] }}%</strong>
            </p>
        </div>

        <div class="max-w-xs mx-auto">
            <x-sponsored-listing-card :ad="$previewCampaignData['payload']" />
        </div>

        <div class="p-4 rounded-2xl bg-slate-900 text-slate-100 space-y-2 font-mono text-[11px]">
            <div class="text-pink-400 font-bold uppercase">S2S Conversion Postback Endpoint (Affiliate Network Callback)</div>
            <div class="break-all select-all bg-slate-950 p-2 rounded border border-slate-700">
                {{ $previewCampaignData['postback_url'] }}?secret={{ $previewCampaignData['postback_secret'] }}&amp;click_id={click_id}&amp;tx_id={tx_id}&amp;payout={payout}
            </div>
            <p class="text-slate-400 font-sans text-[11px]">Always include the secret parameter or <code>X-Ad-Postback-Secret</code> header. Unauthenticated requests are rejected with HTTP 403.</p>
        </div>
    </div>
@endif

@if($showDeleteCampaignModal)
    <div class="space-y-4">
        <h3 class="text-lg font-black text-slate-950">Confirm Campaign Deletion</h3>
        <p class="text-xs text-slate-700">Are you sure you want to delete campaign <strong>{{ $deleteCampaignName }}</strong>? This campaign will immediately stop serving across all classified placements.</p>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" wire:click="closeModals" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-black">Cancel</button>
            <button type="button" wire:click="deleteCampaign" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black cursor-pointer">Delete Campaign</button>
        </div>
    </div>
@endif

