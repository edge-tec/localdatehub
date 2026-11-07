<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Modules\Moderation\Models\AppealRequest;
use App\Modules\Moderation\Models\UserSuspension;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuspensionController extends Controller
{
    /**
     * Display the suspension notice screen.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $suspension = UserSuspension::where('user_id', $user?->id)
            ->where('is_lifted', false)
            ->latest()
            ->first();

        $existingAppeal = AppealRequest::where('user_id', $user?->id)
            ->where('status', 'pending')
            ->first();

        return view('account.suspended', [
            'user' => $user,
            'suspension' => $suspension,
            'existingAppeal' => $existingAppeal,
        ]);
    }

    /**
     * Submit an appeal request for a suspended account.
     */
    public function appeal(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'appeal_reason' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        $suspension = UserSuspension::where('user_id', $user->id)
            ->where('is_lifted', false)
            ->latest()
            ->first();

        AppealRequest::create([
            'user_id' => $user->id,
            'user_suspension_id' => $suspension?->id,
            'appeal_reason' => $request->input('appeal_reason'),
            'status' => 'pending',
        ]);

        return back()->with('status', 'Your appeal has been submitted to the compliance team for review.');
    }
}

