<?php

namespace App\Http\Controllers;

use App\Modules\EmailRelay\Services\EmailRelayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboundEmailController extends Controller
{
    public function __construct(protected EmailRelayService $relayService)
    {
    }

    public function receive(Request $request, string $provider = 'cloudflare'): JsonResponse
    {
        if (!$this->relayService->verifyWebhookSignature($provider, $request)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid inbound webhook signature.',
            ], 403);
        }

        $message = $this->relayService->processInboundWebhook($provider, $request->all());

        if (!$message) {
            return response()->json([
                'ok' => false,
                'message' => 'Relay target not found, disabled, or relay is inactive.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'status' => $message->delivery_status,
            'message_id' => $message->message_id,
        ], 202);
    }
}

