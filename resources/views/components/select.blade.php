@props([
    'disabled' => false,
    'label' => null,
    'name' => null,
    'error' => null,
    'required' => false,
    'hint' => null,
    'options' => [],
    'selected' => null,
])

<div class="space-y-1.5">
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
            {{ $label }}
            @if($required) <span class="text-[#F20B83]">*</span> @endif
        </label>
    @endif

    <select
        @if($name) name="{{ $name }}" id="{{ $name }}" @endif
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => 'w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 focus:bg-white focus:border-[#F20B83] focus:ring-2 focus:ring-[#F20B83]/20 focus:outline-none transition duration-150 disabled:opacity-50 disabled:bg-slate-100 disabled:cursor-not-allowed cursor-pointer ' . ($error ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : '')]) }}
    >
        @if(!empty($options))
            @foreach($options as $val => $optLabel)
                <option value="{{ $val }}" {{ (string) $selected === (string) $val ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endforeach
        @endif
        {{ $slot }}
    </select>

    @if($hint)
        <p class="text-[11px] text-slate-500">{{ $hint }}</p>
    @endif

    @if($error)
        <p class="text-xs text-red-600 font-semibold flex items-center gap-1">
            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
