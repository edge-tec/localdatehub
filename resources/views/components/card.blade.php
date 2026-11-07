@props(['hover' => true])

<div {{ $attributes->merge(['class' => 'bg-slate-900 border border-slate-800 rounded-2xl p-6 ' . ($hover ? 'hover:border-slate-700 transition duration-200' : '')]) }}>
    {{ $slot }}
</div>
