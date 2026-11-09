@props(['hover' => true])

<div {{ $attributes->merge(['class' => 'bg-white border border-slate-200/90 rounded-2xl p-6 shadow-xs ' . ($hover ? 'hover:border-slate-300 hover:shadow-md transition-all duration-200' : '')]) }}>
    {{ $slot }}
</div>
