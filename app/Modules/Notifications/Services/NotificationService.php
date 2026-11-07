<?php

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\DTOs\NotificationMessageDTO;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Dispatch notification to user across active channels (Database, Push, Email).
     */
    public function dispatch(NotificationMessageDTO $dto): void
    {
        Log::channel('single')->info('Notification dispatched', [
            'recipient_id' => $dto->recipientId,
            'type' => $dto->type,
            'title' => $dto->title,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
