<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the primary unified user account dashboard.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'overview');

        return view('account.dashboard', [
            'initialTab' => $tab,
        ]);
    }
}
