@props(['variant' => 'primary', 'size' => 'md'])

@php
    $baseClasses = 'inline-flex items-center justify-center font-semibold rounded-xl transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white cursor-pointer';
    
    $variantClasses = match($variant) {
        'primary' => 'bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-500 hover:to-pink-500 text-white shadow-lg shadow-rose-600/25 focus:ring-rose-500',
        'secondary' => 'bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 focus:ring-slate-600',
        'outline' => 'bg-transparent border border-rose-500/50 hover:border-rose-500 text-rose-600 hover:text-white hover:bg-rose-500/10 focus:ring-rose-500',
        'danger' => 'bg-red-600 hover:bg-red-500 text-white focus:ring-red-500',
        default => 'bg-slate-100 text-white hover:bg-slate-200',
    };

    $sizeClasses = match($size) {
        'sm' => 'text-xs px-3 py-1.5',
        'lg' => 'text-base px-6 py-3',
        default => 'text-sm px-4 py-2.5',
    };
@endphp

<button {{ $attributes->merge(['class' => "$baseClasses $variantClasses $sizeClasses"]) }}>
    {{ $slot }}
</button>
