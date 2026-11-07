<?php

namespace App\Modules\Notifications\DTOs;

readonly class NotificationMessageDTO
{
    public function __construct(
        public int $recipientId,
        public string $title,
        public string $body,
        public string $type,
        public ?string $actionUrl = null,
        public array $metadata = [],
    ) {}
}
