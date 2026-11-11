<?php

namespace App\Http\Controllers\Advertising;

use App\Http\Controllers\Controller;
use App\Modules\Advertising\Models\AdCampaign;
use App\Modules\Advertising\Services\AdDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdDeliveryApiController extends Controller
{
    public function __construct(
        protected AdDeliveryService $deliveryService
    ) {}

    /**
     * Public, uncached JSON banner/ad delivery endpoint.
     * Supports single placement (`placement=...`) or multiple comma-separated placements (`placements=slot1,slot2`).
     */
    public function serve(Request $request): JsonResponse
    {
        $rawPlacements = $request->query('placements');
        $singleSlot = (string) ($request->query('placement') ?: $request->query('slot') ?: '');

        $requestedSlots = [];
        if ($rawPlacements !== null && $rawPlacements !== '') {
            $list = is_array($rawPlacements) ? $rawPlacements : explode(',', (string) $rawPlacements);
            $requestedSlots = array_values(array_unique(array_filter(array_map('trim', $list))));
        } elseif ($singleSlot !== '') {
            $requestedSlots = [trim($singleSlot)];
        } else {
            $requestedSlots = [AdCampaign::SLOT_HOMEPAGE_TOP];
        }

        $invalidSlots = array_values(array_diff($requestedSlots, AdCampaign::SLOTS));
        if ($invalidSlots !== []) {
            return response()->json([
                'ok' => false,
                'error' => 'Invalid placement slot(s): ' . implode(', ', $invalidSlots),
                'valid_slots' => AdCampaign::SLOTS,
                'ads' => [],
            ], 422, $this->noCacheHeaders());
        }

        $limit = max(1, min(10, $request->integer('limit', 3)));
        $recordImpressions = $request->boolean('record_impression', false);
        $device = strtolower(trim((string) $request->query('device', '')));
        if (!in_array($device, ['desktop', 'mobile', 'tablet'], true)) {
            $device = $this->deliveryService->detectDeviceType($request);
        }

        $context = array_filter([
            'device' => $device,
            'country' => $request->query('country') ? strtoupper(trim((string) $request->query('country'))) : null,
            'state' => $request->query('state') ? strtoupper(trim((string) $request->query('state'))) : null,
            'city_id' => $request->integer('city_id') ?: null,
            'zip_code' => $request->query('zip_code') ? trim((string) $request->query('zip_code')) : null,
            'category_id' => $request->integer('category_id') ?: null,
            'parent_category_id' => $request->integer('parent_category_id') ?: null,
            'strict_targeting' => $request->boolean('strict_targeting', false),
        ], fn ($v) => $v !== null && $v !== '');

        if (count($requestedSlots) === 1) {
            $slot = $requestedSlots[0];
            $ads = $this->deliveryService
                ->getAdsForSlot($slot, $context, $limit, $recordImpressions)
                ->values()
                ->all();

            return response()->json([
                'ok' => true,
                'slot' => $slot,
                'device' => $device,
                'count' => count($ads),
                'ads' => $ads,
                'timestamp' => now()->toIso8601String(),
            ], 200, $this->noCacheHeaders());
        }

        $bySlot = [];
        $totalCount = 0;
        foreach ($requestedSlots as $slot) {
            $slotAds = $this->deliveryService
                ->getAdsForSlot($slot, $context, $limit, $recordImpressions)
                ->values()
                ->all();
            $bySlot[$slot] = $slotAds;
            $totalCount += count($slotAds);
        }

        return response()->json([
            'ok' => true,
            'slots' => $requestedSlots,
            'device' => $device,
            'count' => $totalCount,
            'placements' => $bySlot,
            'timestamp' => now()->toIso8601String(),
        ], 200, $this->noCacheHeaders());
    }

    /**
     * @return array<string, string>
     */
    protected function noCacheHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }
}

