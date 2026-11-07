<?php

namespace App\Modules\Contact\Services;

use App\Modules\Contact\DTOs\ContactMessageData;
use App\Support\Exceptions\ProhibitedContentException;

class ContactService
{
    protected array $prohibitedPatterns = [
        '/\b(wire\s*transfer|cash\s*app|venmo|gift\s*card|crypto|bitcoin|western\s*union)\b/i',
        '/\b(escort|ppm|hourly\s*rate|pay\s*per\s*meet)\b/i',
    ];

    /**
     * Validate message content for user safety and scam patterns.
     *
     * @throws ProhibitedContentException
     */
    public function validateMessageSafety(ContactMessageData $data): void
    {
        foreach ($this->prohibitedPatterns as $pattern) {
            if (preg_match($pattern, $data->content)) {
                throw new ProhibitedContentException(
                    'Safety Alert: Off-platform financial solicitations and commercial terms are prohibited in messages.'
                );
            }
        }
    }
}
