<?php

namespace App\Rules;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTurnstile implements ValidationRule
{
    public bool $implicit = true;

    public function __construct(
        protected ?string $ip = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $service = app(TurnstileService::class);
        if (!$service->isEnabled()) {
            return;
        }

        $result = $service->verify(is_string($value) ? $value : null, $this->ip ?? request()->ip());

        if (!$result['success']) {
            $fail($result['message'] ?? 'Human verification failed. Please complete the Cloudflare Turnstile check and try again.');
        }
    }
}
