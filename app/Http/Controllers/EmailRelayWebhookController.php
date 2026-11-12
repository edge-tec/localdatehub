<?php

namespace App\Http\Controllers;

use App\Modules\EmailRelay\Services\EmailRelayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailRelayWebhookController extends Controller
{
    public function __construct(protected EmailRelayService $relayService)
    {
    }

    public function handle(Request $request, string $provider = 'mailgun'): JsonResponse
    {
        if (!$this->relayService->verifyWebhookSignature($provider, $request)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid delivery webhook signature.',
            ], 403);
        }

        $updated = $this->relayService->recordDeliveryStatusEvent($provider, $request->all());

        return response()->json([
            'ok' => true,
            'processed' => $updated !== null,
            'delivery_status' => $updated?->delivery_status,
        ]);
    }
}

