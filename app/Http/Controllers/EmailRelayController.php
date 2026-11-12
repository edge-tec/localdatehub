<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendEmailRelayRequest;
use App\Modules\EmailRelay\Models\EmailRelayMessage;
use App\Modules\EmailRelay\Services\EmailRelayService;
use App\Modules\Listings\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class EmailRelayController extends Controller
{
    public function __construct(protected EmailRelayService $relayService)
    {
    }

    public function send(SendEmailRelayRequest $request, Listing $listing): JsonResponse
    {
        if ($listing->status !== Listing::STATUS_ACTIVE) {
            return response()->json([
                'ok' => false,
                'message' => 'This listing is not currently accepting relay messages.',
            ], 422);
        }

        $ip = (string) $request->ip();
        $perMinKey = 'email-relay:min:' . sha1($ip);
        $maxPerMin = (int) config('email_relay.rate_limit.per_ip_per_minute', 5);

        if (RateLimiter::tooManyAttempts($perMinKey, $maxPerMin)) {
            return response()->json([
                'ok' => false,
                'message' => 'Too many relay messages sent recently. Please wait a minute before trying again.',
            ], 429);
        }
        RateLimiter::hit($perMinKey, 60);

        try {
            $attachments = $request->file('attachments', []);
            if (!is_array($attachments)) {
                $attachments = $attachments ? [$attachments] : [];
            }

            $message = $this->relayService->initiateInAppRelay(
                $listing,
                $request->validated(),
                $attachments,
                $ip
            );

            if ($message->delivery_status === EmailRelayMessage::STATUS_BLOCKED) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Your message could not be sent because it triggered automated spam or security protection.',
                ], 422);
            }

            return response()->json([
                'ok' => true,
                'message' => 'Your message has been securely sent through LocalDateHub. Your personal email address remains private.',
                'relay_status' => $message->delivery_status,
            ], 202);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

