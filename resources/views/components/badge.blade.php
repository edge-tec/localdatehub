@props(['variant' => 'neutral'])

@php
    $classes = match($variant) {
        'rose' => 'bg-gradient-to-r from-rose-50 via-pink-50 to-purple-50/50 text-[#F20B83] border border-rose-200/90 shadow-2xs font-black',
        'success' => 'bg-gradient-to-r from-emerald-50 to-teal-50 text-emerald-800 border border-emerald-200 shadow-2xs font-black',
        'warning' => 'bg-gradient-to-r from-amber-50 to-yellow-50 text-amber-900 border border-amber-200 shadow-2xs font-black',
        'info' => 'bg-gradient-to-r from-sky-50 to-blue-50 text-sky-800 border border-sky-200 shadow-2xs font-black',
        default => 'bg-slate-50 text-slate-800 border border-slate-200 shadow-2xs font-black',
    };
@endphp

<span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs tracking-tight transition {{ $classes }}">
    {{ $slot }}
</span>
