@props(['variant' => 'neutral'])

@php
    $classes = match($variant) {
        'rose' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
        'success' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
        'warning' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
        'info' => 'bg-sky-500/10 text-sky-400 border border-sky-500/20',
        default => 'bg-slate-800 text-slate-300 border border-slate-700',
    };
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $classes }}">
    {{ $slot }}
</span>
