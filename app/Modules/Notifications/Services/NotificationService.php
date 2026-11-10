<?php

namespace App\Modules\Notifications\Services;

use App\Models\User;
use App\Modules\Notifications\Models\NotificationLog;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Dispatch notification with user preferences checks and delivery logging.
     */
    public function send(User $user, Notification $notification, string $type, ?string $subject = null, ?string $messageBody = null): bool
    {
        // 1. Check User Notification Preferences
        $prefs = $user->preferences?->notification_settings ?? [];
        if (isset($prefs[$type]) && $prefs[$type] === false) {
            Log::info("Notification [{$type}] suppressed due to user preferences: User #{$user->id}");
            return false;
        }

        // 2. Log Delivery Attempt in notifications_log
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'channel' => 'mail',
            'notification_type' => $type,
            'subject' => $subject ?: class_basename($notification),
            'message' => $messageBody,
            'recipient_target' => $user->email,
            'status' => 'queued',
            'is_read' => false,
            'read_at' => null,
        ]);

        try {
            $user->notify($notification);

            $log->status = 'sent';
            $log->sent_at = now();
            $log->save();

            return true;
        } catch (\Throwable $e) {
            $log->status = 'failed';
            $log->error_details = $e->getMessage();
            $log->save();

            Log::error("Failed sending notification [{$type}] to User #{$user->id}: " . $e->getMessage());
            return false;
        }
    }
}