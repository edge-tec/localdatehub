<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Services\WebhookHandlingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected WebhookHandlingService $webhookService
    ) {}

    public function handle(Request $request, string $gateway): JsonResponse
    {
        $response = $this->webhookService->handle($request, $gateway);

        return response()->json($response, 200);
    }
}