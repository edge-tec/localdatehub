@props(['type' => 'info', 'message' => null])

@php
    $classes = match($type) {
        'success' => 'bg-emerald-50 border-emerald-200 text-emerald-700',
        'danger', 'error' => 'bg-rose-50 border-rose-200 text-rose-600',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-700',
        default => 'bg-white border-slate-200 text-slate-800',
    };
@endphp

<div x-data="{ show: true }" x-show="show" class="border rounded-xl p-4 mb-4 flex items-start justify-between {{ $classes }}">
    <div class="text-sm">
        {{ $message ?? $slot }}
    </div>
    <button @click="show = false" class="text-slate-400 hover:text-slate-900 ml-3">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
