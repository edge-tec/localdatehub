<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Display the Enterprise Administration Hub.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'dashboard');

        return view('admin.dashboard', [
            'initialTab' => $tab,
        ]);
    }

    /**
     * Stop active impersonation session and return to the original administrator account.
     */
    public function stopImpersonation(Request $request): RedirectResponse
    {
        $adminId = session()->pull('impersonator_admin_id');
        session()->forget('impersonator_admin_name');
        session()->forget('impersonation_target_role');

        if ($adminId) {
            $admin = User::find($adminId);
            if ($admin) {
                Auth::login($admin);
                $request->session()->regenerate();
                return redirect()->route('admin.index', ['tab' => 'users'])->with('success', "Exited user impersonation session. Returned to Administrator account ({$admin->name}).");
            }
        }

        return redirect()->route('home');
    }
}

