@props([
    'title' => 'No items found',
    'description' => 'Try broadening your search filters or check back soon.',
])

<div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-10 sm:p-14 text-center space-y-4 max-w-lg mx-auto">
    <div class="w-14 h-14 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-center text-slate-500 mx-auto shadow-inner">
        {{ $icon ?? '' }}
        @if(!isset($icon))
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
        @endif
    </div>

    <div>
        <h3 class="text-base sm:text-lg font-bold text-white">{{ $title }}</h3>
        <p class="text-xs sm:text-sm text-slate-400 mt-1 max-w-sm mx-auto leading-relaxed">
            {{ $description }}
        </p>
    </div>

    @if($slot->isNotEmpty())
        <div class="pt-2">
            {{ $slot }}
        </div>
    @endif
</div>
