<div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xl text-left">
    <div class="flex items-center justify-between pb-4 border-b border-slate-200">
        <div class="flex items-center space-x-3">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Live Platform Health Probe</h3>
                <p class="text-xs text-slate-400">Livewire Reactive State • Last checked: <span class="font-mono text-slate-600">{{ $lastChecked }}</span></p>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <button wire:click="refreshStatus" wire:loading.attr="disabled" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 transition flex items-center space-x-1 cursor-pointer">
                <span wire:loading.remove>Refresh</span>
                <span wire:loading>Checking...</span>
            </button>
            <button wire:click="toggleDetailed" class="text-xs bg-rose-600/20 hover:bg-rose-600/30 text-rose-600 border border-rose-500/30 px-3 py-1.5 rounded-lg transition cursor-pointer">
                {{ $showDetailed ? 'Hide Details' : 'Deep Diagnostics' }}
            </button>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 my-4">
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold block">App Status</span>
            <span class="text-sm font-bold text-emerald-700 capitalize">{{ $healthData['status'] ?? 'Online' }}</span>
        </div>
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold block">Environment</span>
            <span class="text-sm font-bold text-slate-800 uppercase font-mono">{{ $healthData['env'] ?? config('app.env') }}</span>
        </div>
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold block">Framework</span>
            <span class="text-sm font-bold text-rose-600 font-mono">Laravel 13</span>
        </div>
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
            <span class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold block">Livewire Clicks</span>
            <span class="text-sm font-bold text-purple-700 font-mono flex items-center justify-between">
                <span>{{ $counter }}</span>
                <button wire:click="incrementTest" class="text-[10px] bg-purple-50 hover:bg-purple-800 text-purple-200 px-1.5 py-0.5 rounded cursor-pointer">+1</button>
            </span>
        </div>
    </div>

    @if($showDetailed && isset($healthData['checks']))
        <div class="mt-4 pt-4 border-t border-slate-200 space-y-2">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-600">Detailed Subsystem Checks</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                @foreach($healthData['checks'] as $checkName => $details)
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 flex justify-between items-center">
                        <span class="capitalize font-medium text-slate-600">{{ $checkName }}</span>
                        <div class="flex items-center space-x-2">
                            @if(isset($details['latency_ms']))
                                <span class="font-mono text-[10px] text-slate-500">{{ $details['latency_ms'] }}ms</span>
                            @endif
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ ($details['status'] ?? '') === 'healthy' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ $details['status'] ?? 'unknown' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
