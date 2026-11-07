@props(['type' => 'info', 'message' => null])

@php
    $classes = match($type) {
        'success' => 'bg-emerald-950/80 border-emerald-800 text-emerald-200',
        'danger', 'error' => 'bg-rose-950/80 border-rose-800 text-rose-200',
        'warning' => 'bg-amber-950/80 border-amber-800 text-amber-200',
        default => 'bg-slate-900 border-slate-700 text-slate-200',
    };
@endphp

<div x-data="{ show: true }" x-show="show" class="border rounded-xl p-4 mb-4 flex items-start justify-between {{ $classes }}">
    <div class="text-sm">
        {{ $message ?? $slot }}
    </div>
    <button @click="show = false" class="text-slate-400 hover:text-white ml-3">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
