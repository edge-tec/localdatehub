@props(['text' => 'Loading content...'])

<div {{ $attributes->merge(['class' => 'flex items-center justify-center p-8 space-x-3 text-xs text-slate-400']) }}>
    <svg class="animate-spin w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
    </svg>
    <span>{{ $text }}</span>
</div>

