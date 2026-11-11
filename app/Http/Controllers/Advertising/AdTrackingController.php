<?php

namespace App\Http\Controllers\Advertising;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\AdPlacement;
use App\Modules\Advertising\Models\AdCampaign;
use App\Modules\Advertising\Models\AdCreative;
use App\Modules\Advertising\Services\AdTrackingService;
use App\Modules\Advertising\Services\AdUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdTrackingController extends Controller
{
    public function __construct(
        protected AdTrackingService $trackingService
    ) {}

    /**
     * Track outbound ad click securely and redirect to banner destination or intact affiliate URL.
     */
    public function click(Request $request, AdCampaign $campaign): RedirectResponse
    {
        $creativeId = $request->integer('creative');
        $creative = $creativeId
            ? AdCreative::where('campaign_id', $campaign->id)->find($creativeId)
            : $campaign->primaryCreative;

        $slot = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $request->query('slot', 'direct')), 0, 60) ?: 'direct';

        $result = $this->trackingService->recordClickAndBuildRedirect(
            $campaign,
            $creative,
            $slot,
            $request,
            [
                'state' => $request->query('state'),
                'city_id' => $request->integer('city_id') ?: null,
                'category_id' => $request->integer('category_id') ?: null,
            ]
        );

        return redirect()->away($result['redirect_url']);
    }

    /**
     * Track outbound click on a legacy AdPlacement record safely.
     */
    public function legacyClick(Request $request, AdPlacement $placement): RedirectResponse
    {
        if (!$this->trackingService->isBotRequest($request)) {
            $placement->increment('clicks');
        }

        $target = AdUrlService::isSafeDestinationUrl($placement->target_url)
            ? (string) $placement->target_url
            : url('/');

        return redirect()->away($target);
    }

    /**
     * Record viewport-visible ad impression via optional client beacon.
     */
    public function impression(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_uuid' => ['required', 'string', 'max:36'],
            'creative_id' => ['nullable', 'integer'],
            'slot' => ['required', 'string', 'max:60'],
            'state' => ['nullable', 'string', 'max:10'],
            'city_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
        ]);

        $campaign = AdCampaign::where('uuid', $validated['campaign_uuid'])->first();
        if (!$campaign || !$campaign->isCurrentlyEligible()) {
            return response()->json(['recorded' => false], 404);
        }

        $creative = !empty($validated['creative_id'])
            ? AdCreative::where('campaign_id', $campaign->id)->find((int) $validated['creative_id'])
            : $campaign->primaryCreative;

        $row = $this->trackingService->recordImpression(
            $campaign,
            $creative,
            $validated['slot'],
            $request,
            [
                'state' => $validated['state'] ?? null,
                'city_id' => $validated['city_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
            ]
        );

        return response()->json([
            'recorded' => $row !== null,
        ]);
    }

    /**
     * Authenticated/Signed Server-to-Server (S2S) Affiliate Conversion Postback.
     */
    public function postback(Request $request, AdCampaign $campaign): JsonResponse
    {
        $providedSecret = (string) ($request->header('X-Ad-Postback-Secret') ?: $request->input('secret', ''));
        $expectedSecret = (string) $campaign->postback_secret;

        if ($expectedSecret === '' || $providedSecret === '' || !hash_equals($expectedSecret, $providedSecret)) {
            return response()->json([
                'ok' => false,
                'error' => 'Invalid or missing postback secret.',
            ], 403);
        }

        $validated = $request->validate([
            'click_id' => ['nullable', 'string', 'max:64'],
            'tx_id' => ['nullable', 'string', 'max:120'],
            'sub_id' => ['nullable', 'string', 'max:120'],
            'payout' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'revenue' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'currency' => ['nullable', 'string', 'max:10'],
        ]);

        $conversion = $this->trackingService->recordConversion($campaign, $validated + ['secret' => $providedSecret]);

        return response()->json([
            'ok' => true,
            'conversion_token' => $conversion->conversion_token,
            'campaign_id' => $campaign->id,
            'status' => $conversion->status,
        ]);
    }
}

