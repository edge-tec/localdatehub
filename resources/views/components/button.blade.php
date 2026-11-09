@props(['variant' => 'primary', 'size' => 'md'])

@php
    $baseClasses = 'inline-flex items-center justify-center font-semibold rounded-xl transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white cursor-pointer active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none disabled:cursor-not-allowed';
    
    $variantClasses = match($variant) {
        'primary' => 'bg-[#F20B83] hover:bg-[#d90858] text-white shadow-sm hover:shadow-md focus:ring-[#F20B83]',
        'secondary' => 'bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 focus:ring-slate-500',
        'outline' => 'bg-transparent border border-[#F20B83] text-[#F20B83] hover:text-[#d90858] hover:bg-pink-50 focus:ring-[#F20B83]',
        'danger' => 'bg-red-600 hover:bg-red-500 text-white shadow-sm hover:shadow-md focus:ring-red-500',
        default => 'bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 focus:ring-slate-500',
    };

    $sizeClasses = match($size) {
        'sm' => 'text-xs px-3 py-1.5 gap-1.5',
        'lg' => 'text-base px-6 py-3 gap-2.5',
        default => 'text-sm px-4 py-2.5 gap-2',
    };
@endphp

<button {{ $attributes->merge(['class' => "$baseClasses $variantClasses $sizeClasses"]) }}>
    {{ $slot }}
</button>
