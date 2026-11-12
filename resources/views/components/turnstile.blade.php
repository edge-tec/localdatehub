@props([
    'theme' => 'light',
    'size' => 'flexible',
    'action' => null,
])

@php
    $turnstile = app(\App\Services\TurnstileService::class);
    $enabled = $turnstile->isEnabled();
    $siteKey = $turnstile->siteKey();
@endphp

@if($enabled)
    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce

    <div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
        <div
            class="cf-turnstile"
            data-sitekey="{{ $siteKey }}"
            data-theme="{{ $theme }}"
            data-size="{{ $size }}"
            @if($action) data-action="{{ $action }}" @endif
        ></div>

        @error('cf-turnstile-response')
            <p class="text-xs font-bold text-rose-600 flex items-center gap-1.5" role="alert">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <span>{{ $message }}</span>
            </p>
        @enderror
    </div>
@endif
