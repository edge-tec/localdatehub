{{-- Enterprise Advertising Campaigns Hub (Banner & Affiliate Offer Campaigns) --}}
<div class="space-y-4 mb-6">
    <!-- Enterprise Toolbar -->
    <div class="{{ $card }} p-4 border-t-4 border-t-[#F20B83]">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2.5 flex-1">
                <div class="relative min-w-[210px] flex-1 max-w-xs">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="campaignSearch"
                        class="{{ $input }} pl-8 text-xs font-bold text-slate-900"
                        placeholder="Search enterprise campaigns, brands, URLs..."
                    />
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <select wire:model.live="campaignTypeFilter" class="{{ $input }} text-xs font-bold text-slate-900 w-auto">
                    <option value="all">All Formats</option>
                    <option value="banner">Banner Ads</option>
                    <option value="affiliate">Affiliate Offers</option>
                    <option value="hybrid">Hybrid</option>
                </select>

                <select wire:model.live="campaignStatusFilter" class="{{ $input }} text-xs font-bold text-slate-900 w-auto">
                    <option value="all">All 8 Statuses</option>
                    @foreach(\App\Modules\Advertising\Models\AdCampaign::STATUSES as $st)
                        <option value="{{ $st }}">{{ ucwords(str_replace('_', ' ', $st)) }}</option>
                    @endforeach
                </select>

                <select wire:model.live="campaignSlotFilter" class="{{ $input }} text-xs font-bold text-slate-900 w-auto">
                    <option value="all">All 13 Placements</option>
                    @foreach(\App\Modules\Advertising\Models\AdCampaign::SLOTS as $s)
                        <option value="{{ $s }}">{{ \App\Modules\Advertising\Models\AdCampaign::SLOT_CATALOG[$s]['label'] ?? ucwords(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>

            <button
                type="button"
                wire:click="openCampaignModal"
                class="px-4 py-2.5 rounded-xl bg-[#F20B83] hover:bg-[#D00972] text-white text-xs font-black transition shadow-xs flex items-center justify-center gap-2 shrink-0 cursor-pointer"
            >
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>+ New Promotional Campaign</span>
            </button>
        </div>
    </div>

    <!-- Enterprise Campaigns Table -->
    <div class="{{ $card }} overflow-x-auto shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="{{ $th }}">Campaign & Format</th>
                    <th class="{{ $th }}">Placements & Targeting</th>
                    <th class="{{ $th }}">Schedule & Priority</th>
                    <th class="{{ $th }}">Budget & Caps</th>
                    <th class="{{ $th }}">Impr / Clicks / Conv</th>
                    <th class="{{ $th }}">Status</th>
                    <th class="{{ $th }} text-right pr-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
            @forelse($campaigns as $camp)
                @php
                    $creative = $camp->creatives->first();
                    $thumb = $creative?->resolvedImageUrl('desktop');
                    $statusColors = [
                        'active' => 'bg-emerald-50 text-emerald-900 border-emerald-300',
                        'scheduled' => 'bg-blue-50 text-blue-900 border-blue-300',
                        'pending_approval' => 'bg-indigo-50 text-indigo-900 border-indigo-300',
                        'paused' => 'bg-amber-50 text-amber-900 border-amber-300',
                        'draft' => 'bg-slate-100 text-slate-700 border-slate-300',
                        'expired' => 'bg-rose-50 text-rose-800 border-rose-200',
                        'rejected' => 'bg-red-100 text-red-900 border-red-300',
                        'archived' => 'bg-zinc-100 text-zinc-700 border-zinc-300',
                    ];
                @endphp
                <tr class="hover:bg-slate-50/70 transition" wire:key="camp-row-{{ $camp->id }}">
                    <td class="{{ $td }}">
                        <div class="flex items-center gap-2.5">
                            @if($thumb)
                                <button type="button" wire:click="openPreviewCampaignModal({{ $camp->id }})" class="shrink-0 group">
                                    <img src="{{ $thumb }}" alt="{{ $camp->name }}" class="w-11 h-11 rounded-lg object-cover border border-slate-300 group-hover:ring-2 ring-pink-500 transition" />
                                </button>
                            @else
                                <div class="w-11 h-11 rounded-lg bg-pink-50 border border-pink-200 flex items-center justify-center text-[#F20B83] font-black text-[10px] shrink-0 uppercase">
                                    {{ $camp->campaign_type === 'affiliate' ? 'AFF' : 'AD' }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-black text-slate-950 text-xs truncate max-w-[180px]">{{ $camp->name }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase {{ $camp->campaign_type === 'affiliate' ? 'bg-purple-100 text-purple-900 border border-purple-200' : 'bg-blue-100 text-blue-900 border border-blue-200' }}">
                                        {{ $camp->campaign_type }}
                                    </span>
                                </div>
                                <div class="text-[11px] font-semibold text-slate-600 truncate max-w-[220px] mt-0.5">
                                    {{ $creative?->affiliate_brand_name ?: ($camp->advertiser_name ?: 'Direct Partner') }}
                                    @if($creative?->offer_badge)
                                        · <span class="text-[#F20B83] font-black">{{ $creative->offer_badge }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="{{ $td }}">
                        <div class="flex flex-wrap gap-1 max-w-[200px]">
                            @foreach($camp->placements->take(3) as $pl)
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                    {{ str_replace('_', ' ', $pl->placement_slot) }}
                                </span>
                            @endforeach
                            @if($camp->placements->count() > 3)
                                <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-200 text-slate-700">+{{ $camp->placements->count() - 3 }}</span>
                            @endif
                        </div>
                        <div class="text-[10px] text-slate-500 font-semibold mt-1">
                            @if($camp->targetingRules->isEmpty())
                                Global Targeting (All Geo / Categories / Devices)
                            @else
                                {{ $camp->targetingRules->count() }} Targeting {{ Str::plural('Rule', $camp->targetingRules->count()) }}
                            @endif
                        </div>
                    </td>
                    <td class="{{ $td }}">
                        <div class="text-[11px] font-bold text-slate-800">
                            {{ $camp->starts_at?->format('M j, Y') ?? 'Immediate' }} &rarr; {{ $camp->ends_at?->format('M j, Y') ?? 'Continuous' }}
                        </div>
                        <div class="text-[10px] text-slate-500 font-bold mt-0.5">
                            Priority: <span class="text-slate-900">{{ $camp->priority }}</span> · Weight: {{ $camp->weight }}
                        </div>
                    </td>
                    <td class="{{ $td }}">
                        <div class="text-xs font-black text-slate-900">
                            {{ $camp->total_budget_cents ? '$' . number_format($camp->total_budget_cents / 100, 2) . ' max' : ($camp->price_cents ? '$' . number_format($camp->price_cents / 100, 2) : 'Unlimited') }}
                        </div>
                        <div class="text-[10px] text-slate-500 font-semibold">
                            Spent: ${{ number_format($camp->spent_cents / 100, 2) }}
                            @if($camp->frequency_cap_per_session)
                                · Cap {{ $camp->frequency_cap_per_session }}/sess
                            @endif
                        </div>
                    </td>
                    <td class="{{ $td }}">
                        <div class="font-mono text-xs font-black text-slate-900">
                            {{ number_format($camp->total_impressions) }} / {{ number_format($camp->total_clicks) }} / <span class="text-emerald-700">{{ number_format($camp->total_conversions) }}</span>
                        </div>
                        <div class="text-[10px] font-bold text-slate-600 mt-0.5">
                            CTR: <span class="text-slate-900">{{ $camp->clickThroughRate() }}%</span> · Rev: ${{ number_format($camp->total_conversion_revenue_cents / 100, 2) }}
                        </div>
                    </td>
                    <td class="{{ $td }}">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase border {{ $statusColors[$camp->status] ?? 'bg-slate-100 text-slate-800 border-slate-300' }}">
                            {{ str_replace('_', ' ', $camp->status) }}
                        </span>
                    </td>
                    <td class="{{ $td }} text-right pr-4 whitespace-nowrap">
                        <div class="inline-flex items-center gap-1">
                            @if($camp->status === 'active')
                                <button type="button" wire:click="updateCampaignStatus({{ $camp->id }}, 'paused')" class="px-2 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-900 text-xs font-bold border border-amber-200 transition">Pause</button>
                            @else
                                <button type="button" wire:click="updateCampaignStatus({{ $camp->id }}, 'active')" class="px-2 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-900 text-xs font-bold border border-emerald-200 transition">Activate</button>
                            @endif
                            <button type="button" wire:click="openPreviewCampaignModal({{ $camp->id }})" class="px-2 py-1 rounded-lg bg-pink-50 hover:bg-pink-100 text-[#F20B83] text-xs font-bold border border-pink-200 transition">Preview</button>
                            <button type="button" wire:click="openCampaignModal({{ $camp->id }})" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold border border-slate-300 transition">Edit</button>
                            <button type="button" wire:click="duplicateCampaign({{ $camp->id }})" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold border border-slate-300 transition">Duplicate</button>
                            <button type="button" wire:click="confirmDeleteCampaign({{ $camp->id }})" class="px-2 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-800 text-xs font-bold border border-rose-300 transition">Delete</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center bg-slate-50">
                        <div class="max-w-md mx-auto space-y-2">
                            <p class="text-sm font-black text-slate-800">No Enterprise Advertising Campaigns Yet</p>
                            <p class="text-xs text-slate-500">Launch responsive Banner Ads or Affiliate Offers with geo, category &amp; device targeting, frequency capping, and S2S conversion postbacks.</p>
                            <button type="button" wire:click="openCampaignModal" class="mt-2 inline-flex items-center gap-1 px-4 py-2 rounded-xl bg-[#F20B83] hover:bg-[#D00972] text-white font-bold text-xs cursor-pointer">
                                <span>+ Create Enterprise Campaign</span>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        @if(method_exists($campaigns, 'hasPages') && $campaigns->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</div>

