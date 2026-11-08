@props(['hover' => true])

<div {{ $attributes->merge(['class' => 'bg-white border border-slate-200 rounded-2xl p-6 ' . ($hover ? 'hover:border-slate-200 transition duration-200' : '')]) }}>
    {{ $slot }}
</div>
