@props([
    'disabled' => false,
    'label' => null,
    'name' => null,
    'type' => 'text',
    'error' => null,
    'required' => false,
    'hint' => null,
])

<div class="space-y-1">
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
            {{ $label }}
            @if($required) <span class="text-rose-400">*</span> @endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        @if($name) name="{{ $name }}" id="{{ $name }}" @endif
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => 'w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:ring-2 focus:ring-rose-500 focus:outline-none transition disabled:opacity-50 disabled:cursor-not-allowed ' . ($error ? 'border-rose-500' : '')]) }}
    >

    @if($hint)
        <p class="text-[11px] text-slate-400">{{ $hint }}</p>
    @endif

    @if($error)
        <p class="text-xs text-rose-400 font-medium">{{ $error }}</p>
    @endif
</div>
