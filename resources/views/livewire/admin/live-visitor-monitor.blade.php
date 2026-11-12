<div wire:poll.8s class="space-y-6">
    @once
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @endonce

    <!-- Top Header Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-[#F20B83] transition">Admin Dashboard</a>
                <span>/</span>
                <span class="text-slate-600">Live Visitors</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <span>Real-Time Live Visitor Monitoring</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-black">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>LIVE · 8s</span>
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Real-time visitor sessions, approximate IP geolocation, device telemetry, and live navigation activity.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border border-slate-200 shadow-2xs text-xs font-bold text-slate-700">
                <span class="text-slate-400">Timeout:</span>
                <select wire:model.live="inactivityMinutes" class="bg-transparent text-slate-900 font-black focus:outline-none cursor-pointer">
                    <option value="2">2 min</option>
                    <option value="5">5 min</option>
                    <option value="10">10 min</option>
                    <option value="15">15 min</option>
                </select>
            </div>
            <button type="button" wire:click="purgeExpiredHistory" wire:confirm="Delete visitor telemetry older than 30 days?" class="px-3.5 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
                Purge &gt;30d History
            </button>
            <a href="{{ route('admin.dashboard') }}" class="px-3.5 py-1.5 rounded-full bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold transition shadow-2xs">
                ← Back to Dashboard
            </a>
        </div>
    </div>

    @if(session('visitor_status'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-xs font-bold text-emerald-800">
            {{ session('visitor_status') }}
        </div>
    @endif

    <!-- 7 Summary Analytics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-7 gap-3.5">
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500">Total Online Visitors</div>
            <div class="text-2xl font-black text-slate-900 mt-1 flex items-center gap-2">
                <span>{{ number_format($summary['totalOnline']) }}</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            </div>
            <div class="text-[10px] font-semibold text-emerald-600 mt-1">Online + Idle</div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500">Active Sessions</div>
            <div class="text-2xl font-black text-[#F20B83] mt-1">{{ number_format($summary['activeSessions']) }}</div>
            <div class="text-[10px] font-semibold text-slate-400 mt-1">Actively browsing</div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500">Desktop Visitors</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($summary['desktopVisitors']) }}</div>
            <div class="text-[10px] font-semibold text-slate-400 mt-1">Workstation / Laptop</div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500">Mobile Visitors</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($summary['mobileVisitors']) }}</div>
            <div class="text-[10px] font-semibold text-slate-400 mt-1">iOS / Android</div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500">Tablet Visitors</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($summary['tabletVisitors']) }}</div>
            <div class="text-[10px] font-semibold text-slate-400 mt-1">iPad / Tablets</div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500">New Visitors Today</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($summary['newVisitorsToday']) }}</div>
            <div class="text-[10px] font-semibold text-blue-600 mt-1">First-time today</div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm">
            <div class="text-[11px] font-bold text-slate-500">Returning Visitors</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($summary['returningVisitors']) }}</div>
            <div class="text-[10px] font-semibold text-purple-600 mt-1">Repeat online</div>
        </div>
    </div>

    <!-- Live Visitor Map + Real-Time Activity Stream -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <!-- Interactive Geographical Map -->
        <div class="xl:col-span-2 bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm flex flex-col">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                <div>
                    <h2 class="text-sm font-black text-slate-900">Live Visitor Map (Approximate IP Geolocation)</h2>
                    <p class="text-[11px] text-slate-500">Click any marker to inspect visitor distribution by country and city. IP geolocation is approximate.</p>
                </div>
                <span class="text-[11px] font-bold text-slate-600 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-full">
                    {{ count($mapMarkers) }} Active Locations
                </span>
            </div>

            <div wire:ignore id="live-visitors-leaflet-map" class="w-full h-72 sm:h-80 rounded-2xl border border-slate-200 bg-slate-50 z-10"></div>

            <!-- Country Distribution Chips -->
            @if($availableCountries->isNotEmpty())
                <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-slate-100">
                    @foreach($availableCountries->take(8) as $c)
                        <button type="button" wire:click="$set('countryFilter', '{{ $countryFilter === $c->country_code ? '' : $c->country_code }}')" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border transition cursor-pointer {{ $countryFilter === $c->country_code ? 'bg-[#F20B83] text-white border-[#F20B83]' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                            <span>{{ $c->country_name }}</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $countryFilter === $c->country_code ? 'bg-white/20 text-white' : 'bg-white text-slate-900' }}">{{ $c->total }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Real-Time Activity Stream -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-sm flex flex-col">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-sm font-black text-slate-900">Real-Time Visitor Activity</h2>
                    <p class="text-[11px] text-slate-500">Arrivals, page transitions, idle &amp; departures</p>
                </div>
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            </div>

            <div class="space-y-2.5 overflow-y-auto max-h-80 pr-1">
                @forelse($recentActivities as $act)
                    <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-50/80 border border-slate-100 text-xs">
                        <span class="mt-1 w-2 h-2 rounded-full shrink-0 {{ match($act->event_type) {
                            'entered' => 'bg-emerald-500',
                            'navigated', 'pageview' => 'bg-[#F20B83]',
                            'idle' => 'bg-amber-500',
                            default => 'bg-slate-400',
                        } }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-black text-slate-800 uppercase text-[10px]">{{ $act->event_type }}</span>
                                <span class="text-[10px] text-slate-400 shrink-0">{{ $act->occurred_at?->diffForHumans() }}</span>
                            </div>
                            <div class="text-[11px] font-semibold text-slate-700 truncate">{{ $act->details ?: $act->page_title }}</div>
                            @if($act->session)
                                <div class="text-[10px] text-slate-400 truncate">
                                    {{ $act->session->ip_address }} · {{ $act->session->city_name ?: $act->session->country_code }} · {{ $act->session->device_type }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 text-xs text-slate-400">No live visitor activity recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <!-- Status & Device Quick Pills -->
            <div class="flex flex-wrap items-center gap-1.5">
                @foreach(['active' => 'Active (Online+Idle)', 'online' => 'Online Only', 'idle' => 'Idle', 'all' => 'All History'] as $key => $label)
                    <button type="button" wire:click="$set('statusFilter', '{{ $key }}')" class="px-3 py-1.5 rounded-full text-xs font-bold transition cursor-pointer {{ $statusFilter === $key ? 'bg-[#F20B83] text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        {{ $label }}
                    </button>
                @endforeach

                <span class="mx-1 text-slate-300">|</span>

                @foreach(['all' => 'All Devices', 'Desktop' => 'Desktop', 'Mobile' => 'Mobile', 'Tablet' => 'Tablet'] as $dKey => $dLabel)
                    <button type="button" wire:click="$set('deviceFilter', '{{ $dKey }}')" class="px-3 py-1.5 rounded-full text-xs font-bold transition cursor-pointer {{ $deviceFilter === $dKey ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
                        {{ $dLabel }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search IP, Visitor ID, Country, City..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-semibold text-slate-800 focus:bg-white focus:border-[#F20B83] focus:outline-none">

            <select wire:model.live="countryFilter" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-800 focus:bg-white focus:border-[#F20B83] focus:outline-none">
                <option value="">All Countries</option>
                @foreach($availableCountries as $c)
                    <option value="{{ $c->country_code }}">{{ $c->country_name }} ({{ $c->total }})</option>
                @endforeach
            </select>

            <select wire:model.live="cityFilter" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-800 focus:bg-white focus:border-[#F20B83] focus:outline-none">
                <option value="">All Cities</option>
                @foreach($availableCities as $cityName)
                    <option value="{{ $cityName }}">{{ $cityName }}</option>
                @endforeach
            </select>

            <select wire:model.live="sourceFilter" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-800 focus:bg-white focus:border-[#F20B83] focus:outline-none">
                <option value="">All Traffic Sources</option>
                @foreach($availableSources as $src)
                    <option value="{{ $src }}">{{ $src }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Live Visitors Data Table -->
    <div class="bg-white border border-slate-200/90 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Visitor &amp; Status</th>
                        <th class="px-4 py-3">IP &amp; Network</th>
                        <th class="px-4 py-3">Approx. Location</th>
                        <th class="px-4 py-3">Device &amp; Browser</th>
                        <th class="px-4 py-3">Current Page &amp; Source</th>
                        <th class="px-4 py-3">Session &amp; Activity</th>
                        <th class="px-4 py-3 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($visitors as $visitor)
                        <tr wire:click="inspectVisitor({{ $visitor->id }})" class="hover:bg-rose-50/40 transition cursor-pointer">
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ match($visitor->status) {
                                        'online' => 'bg-emerald-500',
                                        'idle' => 'bg-amber-400',
                                        default => 'bg-slate-300',
                                    } }}"></span>
                                    <span class="font-mono font-bold text-slate-900">{{ \Illuminate\Support\Str::limit($visitor->visitor_id, 14) }}</span>
                                </div>
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase {{ match($visitor->status) {
                                        'online' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                        'idle' => 'bg-amber-50 text-amber-700 border border-amber-200',
                                        default => 'bg-slate-100 text-slate-500',
                                    } }}">{{ $visitor->status }}</span>
                                    @if($visitor->is_returning)
                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700">Returning</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="font-mono font-bold text-slate-800">{{ $visitor->ip_address ?: 'Unknown' }}</div>
                                <div class="text-[11px] text-slate-500 truncate max-w-[170px]">{{ $visitor->isp ?: ($visitor->organization ?: 'Standard Network') }}</div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="font-bold text-slate-900">{{ $visitor->country_name }} ({{ $visitor->country_code }})</div>
                                <div class="text-[11px] text-slate-500">
                                    {{ collect([$visitor->city_name, $visitor->region_name])->filter()->implode(', ') ?: 'Region-level IP Geo' }}
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="font-bold text-slate-800">
                                    {{ $visitor->device_type }}
                                    @if($visitor->device_brand || $visitor->device_model)
                                        · {{ trim($visitor->device_brand . ' ' . $visitor->device_model) }}
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    {{ $visitor->os_name }} {{ $visitor->os_version }} · {{ $visitor->browser_name }} {{ $visitor->browser_version }}
                                </div>
                                @if($visitor->screen_resolution)
                                    <div class="text-[10px] font-mono text-slate-400">{{ $visitor->screen_resolution }}</div>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top max-w-[240px]">
                                <div class="font-bold text-slate-900 truncate">{{ $visitor->current_page_title ?: 'Browsing' }}</div>
                                <div class="text-[11px] text-[#F20B83] truncate">{{ $visitor->current_page_url }}</div>
                                <div class="text-[10px] text-slate-500 mt-0.5 truncate">
                                    Source: <span class="font-bold">{{ $visitor->traffic_source }}</span>
                                    @if($visitor->utm_campaign) · UTM: {{ $visitor->utm_campaign }} @endif
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top whitespace-nowrap">
                                <div class="font-bold text-slate-900">{{ $visitor->pageviews_count }} pages · {{ $visitor->formatted_duration }}</div>
                                <div class="text-[11px] text-slate-500">Active {{ $visitor->last_activity_at?->diffForHumans() }}</div>
                                <div class="text-[10px] text-slate-400">Started {{ $visitor->first_seen_at?->format('H:i:s') }}</div>
                            </td>

                            <td class="px-4 py-3 align-top text-right">
                                <button type="button" wire:click.stop="inspectVisitor({{ $visitor->id }})" class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-[#F20B83] text-[#F20B83] hover:text-white font-bold text-[11px] transition cursor-pointer">
                                    Inspect
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-xs text-slate-400 font-semibold">
                                No visitors match the current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($visitors->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $visitors->links() }}
            </div>
        @endif
    </div>

    <!-- Visitor Details Popup Modal -->
    @if($selectedVisitor)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-3 sm:p-6" wire:click.self="closeVisitorDetails">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-5 sm:p-7 space-y-5">
                <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full {{ $selectedVisitor->status === 'online' ? 'bg-emerald-500' : ($selectedVisitor->status === 'idle' ? 'bg-amber-400' : 'bg-slate-400') }}"></span>
                            <h3 class="text-lg font-black text-slate-900">Visitor Session Profile</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-slate-100 text-slate-700">{{ $selectedVisitor->status }}</span>
                        </div>
                        <p class="text-xs font-mono text-slate-500 mt-0.5">ID: {{ $selectedVisitor->visitor_id }} · IP: {{ $selectedVisitor->ip_address }}</p>
                    </div>
                    <button type="button" wire:click="closeVisitorDetails" class="p-2 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                        <div class="text-[11px] font-black uppercase text-slate-400">IP &amp; Approximate Geo</div>
                        <div><span class="font-bold text-slate-600">IP:</span> <span class="font-mono font-bold text-slate-900">{{ $selectedVisitor->ip_address }}</span></div>
                        <div><span class="font-bold text-slate-600">Country:</span> {{ $selectedVisitor->country_name }} ({{ $selectedVisitor->country_code }})</div>
                        <div><span class="font-bold text-slate-600">Region / City:</span> {{ collect([$selectedVisitor->region_name, $selectedVisitor->city_name])->filter()->implode(' / ') ?: 'Approximate Country Level' }}</div>
                        <div><span class="font-bold text-slate-600">ISP:</span> {{ $selectedVisitor->isp ?: 'N/A' }}</div>
                        <div><span class="font-bold text-slate-600">Org:</span> {{ $selectedVisitor->organization ?: 'N/A' }}</div>
                        <p class="text-[10px] text-slate-400 pt-1">Note: IP geolocation provides approximate region/city level only.</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                        <div class="text-[11px] font-black uppercase text-slate-400">Device &amp; Browser</div>
                        <div><span class="font-bold text-slate-600">Type:</span> {{ $selectedVisitor->device_type }}</div>
                        <div><span class="font-bold text-slate-600">Brand / Model:</span> {{ trim(($selectedVisitor->device_brand ?: '') . ' ' . ($selectedVisitor->device_model ?: '')) ?: 'Generic' }}</div>
                        <div><span class="font-bold text-slate-600">OS:</span> {{ $selectedVisitor->os_name }} {{ $selectedVisitor->os_version }}</div>
                        <div><span class="font-bold text-slate-600">Browser:</span> {{ $selectedVisitor->browser_name }} {{ $selectedVisitor->browser_version }}</div>
                        <div><span class="font-bold text-slate-600">Resolution:</span> {{ $selectedVisitor->screen_resolution ?: 'Pending heartbeat' }}</div>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                        <div class="text-[11px] font-black uppercase text-slate-400">Session &amp; Acquisition</div>
                        <div><span class="font-bold text-slate-600">Source:</span> {{ $selectedVisitor->traffic_source }}</div>
                        <div class="truncate"><span class="font-bold text-slate-600">Landing:</span> {{ $selectedVisitor->landing_page }}</div>
                        <div class="truncate"><span class="font-bold text-slate-600">Referrer:</span> {{ $selectedVisitor->referrer_url ?: 'Direct / None' }}</div>
                        <div><span class="font-bold text-slate-600">Started:</span> {{ $selectedVisitor->first_seen_at?->format('Y-m-d H:i:s') }}</div>
                        <div><span class="font-bold text-slate-600">Duration:</span> {{ $selectedVisitor->formatted_duration }} ({{ $selectedVisitor->pageviews_count }} pages)</div>
                        <div><span class="font-bold text-slate-600">Last Activity:</span> {{ $selectedVisitor->last_activity_at?->diffForHumans() }}</div>
                    </div>
                </div>

                <!-- Visited Pages & Activity History -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="p-4 rounded-2xl border border-slate-200 space-y-2">
                        <div class="text-xs font-black text-slate-900">Pages Visited in Session ({{ $selectedVisitor->pageviews->count() }})</div>
                        <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                            @foreach($selectedVisitor->pageviews as $pv)
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                    <div class="font-bold text-slate-800 truncate">{{ $pv->page_title ?: $pv->path }}</div>
                                    <div class="text-[11px] text-[#F20B83] truncate">{{ $pv->url }}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $pv->viewed_at?->format('H:i:s') }} · {{ $pv->response_time_ms }}ms</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl border border-slate-200 space-y-2">
                        <div class="text-xs font-black text-slate-900">Session Activity Timeline</div>
                        <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                            @foreach($selectedVisitor->activities as $act)
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <span class="text-[10px] font-black uppercase text-[#F20B83]">{{ $act->event_type }}</span>
                                        <div class="text-[11px] font-semibold text-slate-700 truncate">{{ $act->details ?: $act->page_title }}</div>
                                    </div>
                                    <span class="text-[10px] text-slate-400 shrink-0">{{ $act->occurred_at?->format('H:i:s') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        (function () {
            let mapInstance = null;
            let markersLayer = null;

            function renderVisitorMap(markers) {
                const container = document.getElementById('live-visitors-leaflet-map');
                if (!container || typeof L === 'undefined') return;

                if (!mapInstance) {
                    mapInstance = L.map(container, { scrollWheelZoom: false }).setView([25, 0], 2);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 18
                    }).addTo(mapInstance);
                    markersLayer = L.layerGroup().addTo(mapInstance);
                }

                markersLayer.clearLayers();
                (markers || []).forEach(function (m) {
                    const radius = Math.min(24, 8 + (m.count * 2));
                    const circle = L.circleMarker([m.lat, m.lon], {
                        radius: radius,
                        color: '#F20B83',
                        fillColor: '#F20B83',
                        fillOpacity: 0.45,
                        weight: 2
                    });
                    circle.bindPopup(
                        '<div style="font-family:sans-serif;font-size:12px;">' +
                        '<strong>' + (m.city_name || m.country_name) + '</strong><br>' +
                        'Visitors: <strong>' + m.count + '</strong> (' + m.online + ' online, ' + m.idle + ' idle)' +
                        '</div>'
                    );
                    circle.addTo(markersLayer);
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                renderVisitorMap(@json($mapMarkers));
            });
            document.addEventListener('livewire:navigated', function () {
                renderVisitorMap(@json($mapMarkers));
            });
            window.addEventListener('live-visitors-map-updated', function (e) {
                renderVisitorMap((e.detail && e.detail.markers) ? e.detail.markers : []);
            });
        })();
    </script>
</div>

