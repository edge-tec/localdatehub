<div class="relative w-full" x-data="{ open: @entangle('isOpen') }" @click.outside="open = false">
    <!-- Input Field -->
    <div class="relative flex items-center">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </div>

        <input 
            type="text" 
            wire:model.live.debounce.200ms="query"
            wire:keydown.arrow-down.prevent="incrementHighlight"
            wire:keydown.arrow-up.prevent="decrementHighlight"
            wire:keydown.enter.prevent="selectHighlighted"
            wire:keydown.escape="isOpen = false"
            placeholder="{{ $placeholder }}"
            class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-10 pr-10 py-3 text-sm text-white placeholder-slate-400 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500 transition shadow-inner">

        <!-- Loading spinner -->
        <div wire:loading class="absolute inset-y-0 right-0 pr-3.5 flex items-center">
            <svg class="animate-spin h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>

    <!-- Autocomplete Results Dropdown -->
    @if($isOpen && $results->isNotEmpty())
        <div class="absolute left-0 right-0 mt-2 bg-slate-900 border border-slate-800 rounded-xl shadow-2xl overflow-hidden z-50 divide-y divide-slate-800/60 max-h-80 overflow-y-auto">
            @foreach($results as $index => $item)
                <button 
                    type="button"
                    wire:click="selectItem({{ $index }})"
                    class="w-full text-left px-4 py-3 flex items-center justify-between transition hover:bg-slate-800/80 {{ $selectedIndex === $index ? 'bg-slate-800' : '' }}">
                    <div class="flex items-center space-x-3">
                        <!-- Icon according to type -->
                        @if($item['type'] === 'zip')
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-500/20 text-xs font-mono font-bold">
                                ZIP
                            </div>
                        @elseif($item['type'] === 'alias')
                            <div class="w-8 h-8 rounded-lg bg-pink-500/10 text-pink-400 flex items-center justify-center shrink-0 border border-pink-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                        @elseif($item['type'] === 'state')
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center shrink-0 border border-amber-500/20 text-xs font-bold">
                                {{ $item['state_code'] }}
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-400 flex items-center justify-center shrink-0 border border-rose-500/20">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                        @endif

                        <div>
                            <div class="text-sm font-semibold text-white tracking-tight">
                                {{ $item['title'] }}
                            </div>
                            <div class="text-xs text-slate-400">
                                {{ $item['subtitle'] }}
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 text-xs text-slate-500">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-800 text-slate-400 border border-slate-700 uppercase">
                            {{ $item['type'] }}
                        </span>
                    </div>
                </button>
            @endforeach
        </div>
    @elseif($isOpen && strlen(trim($query)) >= 2)
        <div class="absolute left-0 right-0 mt-2 bg-slate-900 border border-slate-800 rounded-xl p-4 text-center text-xs text-slate-400 shadow-xl z-50">
            No US localities or aliases found matching "<span class="text-white">{{ $query }}</span>".
        </div>
    @endif
</div>

