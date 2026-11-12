<?php

namespace App\Http\Controllers;

use App\Modules\Analytics\Services\LiveVisitorTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisitorTelemetryController extends Controller
{
    public function heartbeat(Request $request, LiveVisitorTrackingService $tracker): JsonResponse
    {
        $user = Auth::user();
        if ($user && ($user->is_admin || in_array($user->role, ['super_admin', 'admin', 'moderator', 'support', 'finance_admin'], true))) {
            return response()->json(['tracked' => false]);
        }

        $validated = $request->validate([
            'state' => 'nullable|in:active,idle,left',
            'screen_resolution' => 'nullable|string|max:32',
            'page_title' => 'nullable|string|max:255',
            'page_url' => 'nullable|string|max:500',
        ]);

        $session = $tracker->recordHeartbeat($request, $validated);

        return response()->json([
            'tracked' => $session !== null,
            'status' => $session?->status,
        ]);
    }
}
