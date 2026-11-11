{{-- Real-Time Ad Delivery Diagnostics Modal --}}
@if($showDiagnosticModal && $diagnosticData)
    @php
        $d = $diagnosticData;
        $isEligible = (bool) ($d['eligible'] ?? false);
        $deliveryStatus = $d['delivery_status'] ?? ($isEligible ? 'LIVE DELIVERING' : 'BLOCKED');
    @endphp
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl flex items-center justify-center border {{ $isEligible ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        @if($isEligible)
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        @endif
                    </svg>
                </span>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-base sm:text-lg font-black text-slate-950">Real-Time Ad Delivery Diagnostics</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase border {{ $isEligible ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' }}">
                            {{ $d['eligibility_label'] ?? ($isEligible ? 'ELIGIBLE' : 'NOT ELIGIBLE') }}
                        </span>
                    </div>
                    <p class="text-xs font-semibold text-slate-500 mt-0.5">
                        Campaign: <strong class="text-slate-900">{{ $d['campaign_name'] ?? '' }}</strong> &middot; Status: <strong class="uppercase">{{ $d['status'] ?? '' }}</strong> &middot; Delivery: <strong>{{ $deliveryStatus }}</strong>
                    </p>
                </div>
            </div>
            <button type="button" wire:click="closeModals" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer" aria-label="Close diagnostics">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Blocking Reasons / Health Alerts --}}
        @if(!empty($d['blocking_reasons']))
            <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 space-y-2">
                <div class="text-xs font-black uppercase tracking-wider text-rose-900">Why Campaign Is Not Delivering</div>
                <ul class="space-y-1 text-xs font-bold text-rose-800 list-disc pl-4">
                    @foreach($d['blocking_reasons'] as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 flex flex-wrap items-center justify-between gap-2">
                <div class="text-xs font-black text-emerald-900">
                    All server-side eligibility, schedule, creative, placement &amp; API delivery checks passed.
                </div>
                <span class="px-2.5 py-0.5 rounded-full bg-white text-emerald-800 border border-emerald-300 text-[10px] font-black uppercase">
                    API &amp; Frontend Ready
                </span>
            </div>
        @endif

        @if(!empty($d['warnings']))
            <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 space-y-1">
                <div class="text-[11px] font-black uppercase tracking-wider text-amber-900">Warnings</div>
                <ul class="space-y-1 text-xs font-semibold text-amber-800 list-disc pl-4">
                    @foreach($d['warnings'] as $warn)
                        <li>{{ $warn }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Core Diagnostic Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
            {{-- Schedule & Timing --}}
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
                <div class="text-[11px] font-black uppercase tracking-wider text-slate-500">Schedule &amp; Timing</div>
                <div class="flex justify-between gap-2"><span class="text-slate-600">Starts:</span><strong class="text-slate-900">{{ $d['schedule']['starts_at_human'] ?? 'Immediate' }}</strong></div>
                <div class="flex justify-between gap-2"><span class="text-slate-600">Ends:</span><strong class="text-slate-900">{{ $d['schedule']['ends_at_human'] ?? 'Ongoing' }}</strong></div>
                <div class="flex justify-between gap-2"><span class="text-slate-600">Server Time:</span><span class="font-mono text-slate-700">{{ $d['schedule']['server_now_human'] ?? '' }}</span></div>
            </div>

            {{-- Telemetry & Last Activity --}}
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
                <div class="text-[11px] font-black uppercase tracking-wider text-slate-500">Last Impression &amp; Click Telemetry</div>
                <div class="flex justify-between gap-2">
                    <span class="text-slate-600">Last Impression:</span>
                    <strong class="text-slate-900">{{ $d['telemetry']['last_impression_human'] ?? 'Never' }}</strong>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-slate-600">Last Click:</span>
                    <strong class="text-slate-900">{{ $d['telemetry']['last_click_human'] ?? 'Never' }}</strong>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-slate-600">Totals:</span>
                    <strong class="text-slate-900 font-mono">{{ number_format($d['caps']['total_impressions'] ?? 0) }} impr / {{ number_format($d['caps']['total_clicks'] ?? 0) }} clicks</strong>
                </div>
            </div>

            {{-- Targeting Rules --}}
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
                <div class="text-[11px] font-black uppercase tracking-wider text-slate-500">Geo, Category &amp; Device Targeting</div>
                <div class="flex justify-between gap-2"><span class="text-slate-600">Geographic Status:</span><strong class="text-slate-900 text-right">{{ $d['targeting']['geo_summary'] ?? 'Global' }}</strong></div>
                <div class="flex justify-between gap-2"><span class="text-slate-600">Device Status:</span><strong class="text-slate-900 text-right">{{ $d['targeting']['device_summary'] ?? 'All Devices' }}</strong></div>
                <div class="flex justify-between gap-2"><span class="text-slate-600">Category Status:</span><strong class="text-slate-900 text-right">{{ $d['targeting']['category_summary'] ?? 'All Categories' }}</strong></div>
            </div>

            {{-- Creative & API Delivery Health --}}
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
                <div class="text-[11px] font-black uppercase tracking-wider text-slate-500">Creative &amp; API Delivery Status</div>
                <div class="flex justify-between gap-2">
                    <span class="text-slate-600">Desktop Banner:</span>
                    <strong class="{{ ($d['creative']['desktop_file_ok'] ?? false) ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ !empty($d['creative']['desktop_banner_url']) ? (($d['creative']['desktop_file_ok'] ?? false) ? 'Available' : 'Missing on Disk') : 'Not Set' }}
                    </strong>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-slate-600">Mobile Banner:</span>
                    <strong class="{{ ($d['creative']['mobile_file_ok'] ?? false) ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ !empty($d['creative']['mobile_banner_url']) ? (($d['creative']['mobile_file_ok'] ?? false) ? 'Available' : 'Missing on Disk') : 'Not Set' }}
                    </strong>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-slate-600">Frequency / Caps:</span>
                    <strong class="text-slate-900">
                        {{ !empty($d['caps']['frequency_cap_per_session']) ? $d['caps']['frequency_cap_per_session'] . '/sess' : 'No Session Cap' }}
                        &middot; {{ ($d['caps']['caps_ok'] ?? true) ? 'Within Limits' : 'Capped' }}
                    </strong>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-slate-600">Public Serve API:</span>
                    <a href="{{ $d['api_delivery']['api_endpoint'] ?? '#' }}" target="_blank" class="font-bold text-[#F20B83] hover:underline truncate max-w-[220px]">
                        {{ ($d['api_delivery']['overall_ok'] ?? false) ? 'HTTP 200 Serving' : 'Check JSON Response' }} &rarr;
                    </a>
                </div>
            </div>
        </div>

        {{-- Selected Placements Table --}}
        <div class="rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-3.5 py-2 bg-slate-50 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-700">
                Selected Placements &amp; Live Slot Delivery Check
            </div>
            <div class="divide-y divide-slate-100 text-xs">
                @forelse($d['placements'] ?? [] as $pl)
                    <div class="px-3.5 py-2.5 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <strong class="text-slate-900">{{ $pl['label'] }}</strong>
                            <span class="ml-1.5 text-[10px] font-mono text-slate-400">({{ $pl['slot'] }})</span>
                            <div class="text-[10px] text-slate-500">Every {{ $pl['insert_every_n'] }} listings &middot; Max {{ $pl['max_ads_per_page'] }}/page &middot; {{ str_replace('_', ' ', $pl['rotation_mode']) }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black border {{ ($pl['slot_enabled'] ?? false) ? 'bg-slate-100 text-slate-700 border-slate-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                                {{ ($pl['slot_enabled'] ?? false) ? 'Slot Enabled' : 'Slot Disabled' }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black border {{ ($pl['api_delivered'] ?? false) ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200' }}">
                                {{ ($pl['api_delivered'] ?? false) ? 'Serving Live' : 'Not Returned' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-slate-500">No placements assigned to this campaign.</div>
                @endforelse
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-200">
            <div class="flex flex-wrap items-center gap-2">
                @if(isset($d['schedule']['starts_ok']) && !$d['schedule']['starts_ok'])
                    <button type="button" wire:click="startCampaignImmediately({{ $d['campaign_id'] }})" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black transition cursor-pointer">
                        Start Immediately
                    </button>
                @endif
                <button type="button" wire:click="flushAdDeliveryCache({{ $d['campaign_id'] }})" class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-black transition cursor-pointer">
                    Flush Cache &amp; Re-Check
                </button>
                <button type="button" wire:click="openCampaignModal({{ $d['campaign_id'] }})" class="px-3.5 py-1.5 rounded-xl bg-pink-50 hover:bg-pink-100 text-[#F20B83] border border-pink-200 text-xs font-black transition cursor-pointer">
                    Edit Campaign
                </button>
            </div>
            <button type="button" wire:click="closeModals" class="px-4 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold cursor-pointer">
                Close
            </button>
        </div>
    </div>
@endif

