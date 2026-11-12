<?php

namespace App\Livewire\Admin;

use App\Modules\Analytics\Models\VisitorActivity;
use App\Modules\Analytics\Models\VisitorSession;
use App\Modules\Analytics\Services\LiveVisitorTrackingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Real-Time Live Visitor Monitoring · LocalDateHub')]
class LiveVisitorMonitor extends Component
{
    use WithPagination;

    public string $statusFilter = 'active';
    public string $deviceFilter = 'all';
    public string $countryFilter = '';
    public string $cityFilter = '';
    public string $sourceFilter = '';
    public string $search = '';
    public int $inactivityMinutes = 5;
    public ?int $selectedSessionId = null;

    public const COUNTRY_CENTROIDS = [
        'US' => [39.8283, -98.5795],
        'CA' => [56.1304, -106.3468],
        'GB' => [55.3781, -3.4360],
        'AU' => [-25.2744, 133.7751],
        'DE' => [51.1657, 10.4515],
        'FR' => [46.2276, 2.2137],
        'IN' => [20.5937, 78.9629],
        'BD' => [23.6850, 90.3563],
        'PK' => [30.3753, 69.3451],
        'BR' => [-14.2350, -51.9253],
        'IT' => [41.8719, 12.5674],
        'ES' => [40.4637, -3.7492],
        'NL' => [52.1326, 5.2913],
        'JP' => [36.2048, 138.2529],
        'SG' => [1.3521, 103.8198],
        'AE' => [23.4241, 53.8478],
        'ZA' => [-30.5595, 22.9375],
        'MX' => [23.6345, -102.5528],
        'PH' => [12.8797, 121.7740],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDeviceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCountryFilter(): void
    {
        $this->cityFilter = '';
        $this->resetPage();
    }

    public function updatingCityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSourceFilter(): void
    {
        $this->resetPage();
    }

    public function inspectVisitor(int $sessionId): void
    {
        $this->selectedSessionId = $sessionId;
    }

    public function closeVisitorDetails(): void
    {
        $this->selectedSessionId = null;
    }

    public function purgeExpiredHistory(LiveVisitorTrackingService $tracker): void
    {
        $deleted = $tracker->purgeOldRecords(30);
        session()->flash('visitor_status', "Removed {$deleted} expired visitor records older than 30 days.");
    }

    public function render(LiveVisitorTrackingService $tracker): View
    {
        $timeoutSeconds = max(120, $this->inactivityMinutes * 60);
        $tracker->expireInactiveSessions(120, $timeoutSeconds);

        $activeBase = VisitorSession::query()
            ->whereIn('status', [VisitorSession::STATUS_ONLINE, VisitorSession::STATUS_IDLE])
            ->where('last_activity_at', '>=', now()->subSeconds($timeoutSeconds));

        $summary = [
            'totalOnline' => (clone $activeBase)->count(),
            'desktopVisitors' => (clone $activeBase)->where('device_type', 'Desktop')->count(),
            'mobileVisitors' => (clone $activeBase)->where('device_type', 'Mobile')->count(),
            'tabletVisitors' => (clone $activeBase)->where('device_type', 'Tablet')->count(),
            'newVisitorsToday' => VisitorSession::whereDate('first_seen_at', today())->where('is_returning', false)->count(),
            'returningVisitors' => (clone $activeBase)->where('is_returning', true)->count(),
            'activeSessions' => (clone $activeBase)->where('status', VisitorSession::STATUS_ONLINE)->count(),
        ];

        $query = VisitorSession::query()->with('user:id,name,email');

        if ($this->statusFilter === 'active') {
            $query->whereIn('status', [VisitorSession::STATUS_ONLINE, VisitorSession::STATUS_IDLE]);
        } elseif ($this->statusFilter === 'online') {
            $query->where('status', VisitorSession::STATUS_ONLINE);
        } elseif ($this->statusFilter === 'idle') {
            $query->where('status', VisitorSession::STATUS_IDLE);
        }

        if ($this->deviceFilter !== 'all') {
            $query->where('device_type', $this->deviceFilter);
        }

        if ($this->countryFilter !== '') {
            $query->where('country_code', $this->countryFilter);
        }

        if ($this->cityFilter !== '') {
            $query->where('city_name', $this->cityFilter);
        }

        if ($this->sourceFilter !== '') {
            $query->where('traffic_source', $this->sourceFilter);
        }

        if (trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('ip_address', 'like', $term)
                    ->orWhere('visitor_id', 'like', $term)
                    ->orWhere('country_name', 'like', $term)
                    ->orWhere('country_code', 'like', $term)
                    ->orWhere('city_name', 'like', $term)
                    ->orWhere('region_name', 'like', $term);
            });
        }

        $visitors = $query->orderByRaw("CASE status WHEN 'online' THEN 1 WHEN 'idle' THEN 2 ELSE 3 END")
            ->orderByDesc('last_activity_at')
            ->paginate(20);

        $availableCountries = (clone $activeBase)
            ->select('country_code', 'country_name', DB::raw('COUNT(*) as total'))
            ->groupBy('country_code', 'country_name')
            ->orderByDesc('total')
            ->get();

        $availableCities = (clone $activeBase)
            ->when($this->countryFilter !== '', fn ($q) => $q->where('country_code', $this->countryFilter))
            ->whereNotNull('city_name')
            ->select('city_name', DB::raw('COUNT(*) as total'))
            ->groupBy('city_name')
            ->orderByDesc('total')
            ->pluck('city_name');

        $availableSources = (clone $activeBase)
            ->select('traffic_source')
            ->distinct()
            ->orderBy('traffic_source')
            ->pluck('traffic_source');

        $mapMarkers = (clone $activeBase)
            ->get(['id', 'country_code', 'country_name', 'region_name', 'city_name', 'latitude', 'longitude', 'status'])
            ->groupBy(fn ($s) => $s->country_code . '|' . ($s->city_name ?: 'Region'))
            ->map(function ($group) {
                /** @var VisitorSession $first */
                $first = $group->first();
                $coords = self::COUNTRY_CENTROIDS[$first->country_code] ?? [39.8283, -98.5795];
                $lat = $first->latitude ?? $coords[0];
                $lon = $first->longitude ?? $coords[1];

                return [
                    'country_code' => $first->country_code,
                    'country_name' => $first->country_name,
                    'city_name' => $first->city_name ?: ($first->region_name ?: $first->country_name),
                    'lat' => (float) $lat,
                    'lon' => (float) $lon,
                    'count' => $group->count(),
                    'online' => $group->where('status', VisitorSession::STATUS_ONLINE)->count(),
                    'idle' => $group->where('status', VisitorSession::STATUS_IDLE)->count(),
                ];
            })
            ->values()
            ->all();

        $recentActivities = VisitorActivity::with('session:id,visitor_id,ip_address,country_code,city_name,device_type')
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get();

        $selectedVisitor = $this->selectedSessionId
            ? VisitorSession::with(['user:id,name,email', 'pageviews', 'activities'])->find($this->selectedSessionId)
            : null;

        $this->dispatch('live-visitors-map-updated', markers: $mapMarkers);

        return view('livewire.admin.live-visitor-monitor', [
            'summary' => $summary,
            'visitors' => $visitors,
            'availableCountries' => $availableCountries,
            'availableCities' => $availableCities,
            'availableSources' => $availableSources,
            'mapMarkers' => $mapMarkers,
            'recentActivities' => $recentActivities,
            'selectedVisitor' => $selectedVisitor,
        ]);
    }
}

