<?php

namespace App\Jobs\EmailRelay;

use App\Modules\EmailRelay\Models\EmailRelayEvent;
use App\Modules\EmailRelay\Models\EmailRelayMessage;
use App\Modules\EmailRelay\Services\EmailRelayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessRelayReply implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public int $relayMessageId,
        public bool $isFromAdvertiser = true
    ) {
    }

    public function handle(EmailRelayService $service): void
    {
        $message = EmailRelayMessage::find($this->relayMessageId);
        if (!$message || $message->delivery_status === EmailRelayMessage::STATUS_BLOCKED) {
            return;
        }

        $service->sendRelayForward($message, $this->isFromAdvertiser);
    }

    public function failed(Throwable $exception): void
    {
        $message = EmailRelayMessage::find($this->relayMessageId);
        if (!$message) {
            return;
        }

        $message->update([
            'delivery_status' => EmailRelayMessage::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => Str::limit($exception->getMessage(), 500),
        ]);

        EmailRelayEvent::create([
            'relay_message_id' => $message->id,
            'event_type' => EmailRelayMessage::STATUS_FAILED,
            'event_payload' => ['error' => $exception->getMessage()],
            'created_at' => now(),
        ]);

        Log::error('ProcessRelayReply job failed', [
            'relay_message_id' => $message->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

