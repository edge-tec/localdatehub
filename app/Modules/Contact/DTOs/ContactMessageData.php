<?php

namespace App\Modules\Contact\DTOs;

readonly class ContactMessageData
{
    public function __construct(
        public int $senderId,
        public int $recipientId,
        public string $content,
        public ?int $listingId = null,
        public string $messageType = 'text',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            senderId: (int) $data['sender_id'],
            recipientId: (int) $data['recipient_id'],
            content: trim($data['content']),
            listingId: isset($data['listing_id']) ? (int) $data['listing_id'] : null,
            messageType: $data['message_type'] ?? 'text',
        );
    }
}
